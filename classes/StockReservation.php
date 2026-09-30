<?php

class StockReservation
{
	private static $last_error = '';

	public static function getLastError()
	{
		return self::$last_error;
	}

	public static function isQuoteStockLocked($id_quote)
	{
		return (bool) Db::getInstance()->getValue(
			'SELECT qs.`stock_lock` FROM `' . _DB_PREFIX_ . 'quote` q'
			. ' INNER JOIN `' . _DB_PREFIX_ . 'quote_status` qs'
			. ' ON (qs.`id_quote_status` = q.`id_quote_status`)'
			. ' WHERE q.`id_quote` = ' . (int) $id_quote
		);
	}

	public static function getReservedQuantityForQuoteProduct($id_quote, $id_product, $id_product_attribute = 0)
	{
		return (int) Db::getInstance()->getValue(
			'SELECT COALESCE(SUM(`quantity_reserved`), 0) FROM `' . _DB_PREFIX_ . 'stock_reservation`'
			. ' WHERE `id_quote` = ' . (int) $id_quote
			. ' AND `id_product` = ' . (int) $id_product
			. ' AND COALESCE(`id_product_attribute`, 0) = ' . (int) $id_product_attribute
			. ' AND `active` = 1'
		);
	}

	/**
	 * Synchronize active reservations with the current quote products.
	 */
	public static function synchronizeQuoteStock($id_quote)
	{
		self::$last_error = '';

		if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
			return true;
		}

		$quote = new Quote((int) $id_quote);
		if (!Validate::isLoadedObject($quote)) {
			return self::fail('Devis introuvable pour la reservation de stock');
		}

		$desired = self::getQuoteQuantities((int) $quote->id);
		$reserved = self::getActiveReservations((int) $quote->id);
		$db = Db::getInstance();
		$id_shop = self::getStockShopId();

		if (!$db->execute('START TRANSACTION')) {
			return self::fail('Impossible de demarrer la reservation de stock');
		}

		foreach (array_unique(array_merge(array_keys($desired), array_keys($reserved))) as $key) {
			$target_quantity = isset($desired[$key]['quantity']) ? $desired[$key]['quantity'] : 0;
			$reserved_quantity = isset($reserved[$key]['quantity']) ? $reserved[$key]['quantity'] : 0;
			$delta = $target_quantity - $reserved_quantity;

			if ($delta === 0) {
				continue;
			}

			$product = isset($desired[$key]) ? $desired[$key] : $reserved[$key];
			if ($delta > 0) {
				$available_quantity = (int) StockAvailable::getQuantityAvailableByProduct(
					$product['id_product'],
					$product['id_product_attribute'],
					$id_shop
				);
				if ($available_quantity < $delta) {
					$db->execute('ROLLBACK');
					return self::fail('Stock insuffisant pour verrouiller le devis');
				}
			}

			if (!StockAvailable::updateQuantity(
				$product['id_product'],
				$product['id_product_attribute'],
				-$delta,
				$id_shop
			)) {
				$db->execute('ROLLBACK');
				return self::fail('Impossible de mettre a jour le stock reserve');
			}
		}

		if (!$db->delete('stock_reservation', '`id_quote` = ' . (int) $quote->id . ' AND `active` = 1')) {
			$db->execute('ROLLBACK');
			return self::fail('Impossible de mettre a jour les reservations de stock');
		}

		$expiration = self::getReservationExpiration($quote);
		foreach ($desired as $product) {
			if (!$db->insert('stock_reservation', [
				'id_quote' => (int) $quote->id,
				'id_product' => (int) $product['id_product'],
				'id_product_attribute' => (int) $product['id_product_attribute'],
				'quantity_reserved' => (int) $product['quantity'],
				'date_reservation' => date('Y-m-d H:i:s'),
				'date_expiration' => $expiration,
				'active' => 1,
			])) {
				$db->execute('ROLLBACK');
				return self::fail('Impossible d enregistrer les reservations de stock');
			}
		}

		if (!$db->execute('COMMIT')) {
			$db->execute('ROLLBACK');
			return self::fail('Impossible de confirmer les reservations de stock');
		}

		return true;
	}

	/**
	 * Release all active reservations for a quote and restore catalog stock.
	 */
	public static function releaseQuoteStock($id_quote)
	{
		self::$last_error = '';

		if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
			return true;
		}

		$reserved = self::getActiveReservations((int) $id_quote);
		if (empty($reserved)) {
			return true;
		}

		$db = Db::getInstance();
		$id_shop = self::getStockShopId();
		if (!$db->execute('START TRANSACTION')) {
			return self::fail('Impossible de demarrer la liberation de stock');
		}

		foreach ($reserved as $product) {
			if (!StockAvailable::updateQuantity(
				$product['id_product'],
				$product['id_product_attribute'],
				$product['quantity'],
				$id_shop
			)) {
				$db->execute('ROLLBACK');
				return self::fail('Impossible de liberer le stock reserve');
			}
		}

		if (!$db->update('stock_reservation', ['active' => 0], '`id_quote` = ' . (int) $id_quote . ' AND `active` = 1')
			|| !$db->execute('COMMIT')) {
			$db->execute('ROLLBACK');
			return self::fail('Impossible de finaliser la liberation de stock');
		}

		return true;
	}

	private static function getStockShopId()
	{
		$id_shop = (int) Context::getContext()->shop->id;

		return $id_shop > 0 ? $id_shop : null;
	}

	private static function getQuoteQuantities($id_quote)
	{
		$rows = Db::getInstance()->executeS(
			'SELECT `id_product`, COALESCE(`id_product_attribute`, 0) AS `id_product_attribute`, SUM(`quantity`) AS `quantity`'
			. ' FROM `' . _DB_PREFIX_ . 'quote_product`'
			. ' WHERE `id_quote` = ' . (int) $id_quote
			. ' GROUP BY `id_product`, COALESCE(`id_product_attribute`, 0)'
		);

		return self::indexQuantities($rows);
	}

	private static function getActiveReservations($id_quote)
	{
		$rows = Db::getInstance()->executeS(
			'SELECT `id_product`, COALESCE(`id_product_attribute`, 0) AS `id_product_attribute`, SUM(`quantity_reserved`) AS `quantity`'
			. ' FROM `' . _DB_PREFIX_ . 'stock_reservation`'
			. ' WHERE `id_quote` = ' . (int) $id_quote . ' AND `active` = 1'
			. ' GROUP BY `id_product`, COALESCE(`id_product_attribute`, 0)'
		);

		return self::indexQuantities($rows);
	}

	private static function indexQuantities($rows)
	{
		$quantities = [];
		foreach ($rows as $row) {
			$key = (int) $row['id_product'] . ':' . (int) $row['id_product_attribute'];
			$quantities[$key] = [
				'id_product' => (int) $row['id_product'],
				'id_product_attribute' => (int) $row['id_product_attribute'],
				'quantity' => (int) $row['quantity'],
			];
		}

		return $quantities;
	}

	private static function getReservationExpiration(Quote $quote)
	{
		if (!empty($quote->date_exp) && $quote->date_exp !== '0000-00-00') {
			return $quote->date_exp . ' 23:59:59';
		}

		return date('Y-m-d H:i:s', strtotime('+30 days'));
	}

	private static function fail($message)
	{
		self::$last_error = $message;
		PrestaShopLogger::addLog('StockReservation: ' . $message, 3);

		return false;
	}
}
