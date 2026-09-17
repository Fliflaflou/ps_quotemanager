<?php
require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/StockReservation.php';

class QuoteProduct extends ObjectModel {
    public $id_quote;
    public $id_product;
    public $id_product_attribute;
    public $quantity;
    public $price_tax_excl;
    public $price_tax_incl;
    public $tax_rate;
    public $reduction_percent;
    public $reduction_amount;
    public $product_name;
    public $product_reference;
    public $product_ean13;
    public $product_attributes;
    public $notes;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'quote_product',
        'primary' => 'id_quote_product',
        'fields' => array(
            'id_quote' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'id_product' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'id_product_attribute' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false
            ),
            'quantity' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'price_tax_excl' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => true
            ),
            'price_tax_incl' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => true
            ),
            'tax_rate' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => false
            ),
            'reduction_percent' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPercentage',
                'required' => false
            ),
            'reduction_amount' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ),
            'product_name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255
            ),
            'product_reference' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isReference',
                'required' => false,
                'size' => 64
            ),
            'product_ean13' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isEan13',
                'required' => false,
                'size' => 13
            ),
            'product_attributes' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'required' => false,
                'size' => 255
            ),
            'notes' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'required' => false
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => false
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => false
            )
        ),
    );

    public static function getProductsByQuoteId($id_quote)
    {
        if (!Validate::isUnsignedId($id_quote)) {
            return array();
        }

        $sql = sprintf(
            'SELECT qp.*, p.active, p.out_of_stock, p.quantity as stock_quantity
            FROM %squote_product qp
            LEFT JOIN %sproduct p ON (qp.id_product = p.id_product)
            WHERE qp.id_quote = %d
            ORDER BY qp.id_quote_product',
            _DB_PREFIX_,
            _DB_PREFIX_,
            (int)$id_quote
        );

        return Db::getInstance()->executeS($sql);
    }

    public static function addProductToQuote($id_quote, $id_product, $id_product_attribute = 0, $quantity = 1, $price_tax_excl = 0, $price_tax_incl = 0, $notes = '', $reduction_percent = 0, $reduction_amount = 0)
    {
        // Validation
        if (!Validate::isUnsignedId($id_quote) || !Validate::isUnsignedId($id_product) || !Validate::isUnsignedInt($quantity)) {
            return false;
        }

        // Check if product exists
        $product = new Product($id_product, false, Context::getContext()->language->id);
        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        // Check if quote exists
        $quote = new Quote($id_quote);
        if (!Validate::isLoadedObject($quote)) {
            return false;
        }

        if ((int) $quote->id_order > 0) {
            return false;
        }

        if ($quantity > self::getAvailableQuantityForQuote($id_quote, $id_product, $id_product_attribute)) {
            return false;
        }

        // Get product info
        $product_info = self::getProductInfoForQuote($id_product, $id_product_attribute);
        if (!$product_info) {
            return false;
        }

        // Cap reduction at 100% of the line total
        $reduction_percent = (float) $reduction_percent;
        $reduction_amount = (float) $reduction_amount;
        $line_total = $price_tax_excl * $quantity;
        $total_reduction = $reduction_amount + round($line_total * ($reduction_percent / 100), 2);
        if ($total_reduction > $line_total) {
            return false;
        }

        // Create QuoteProduct
        $quote_product = new QuoteProduct();
        $quote_product->id_quote = $id_quote;
        $quote_product->id_product = $id_product;
        $quote_product->id_product_attribute = $id_product_attribute;
        $quote_product->quantity = $quantity;
        $quote_product->price_tax_excl = $price_tax_excl;
        $quote_product->price_tax_incl = $price_tax_incl;
        $quote_product->reduction_percent = $reduction_percent;
        $quote_product->reduction_amount = $reduction_amount;
        $quote_product->product_name = $product_info['name'];
        $quote_product->product_reference = $product_info['reference'];
        $quote_product->product_ean13 = $product_info['ean13'];
        $quote_product->product_attributes = isset($product_info['attributes']) ? $product_info['attributes'] : '';
        $quote_product->notes = $notes;

        if ($quote_product->save()) {
            if (StockReservation::isQuoteStockLocked($id_quote)
                && !StockReservation::synchronizeQuoteStock($id_quote)) {
                $quote_product->delete();
                return false;
            }

            // Update quote totals
            $quote->updateTotals();
            return $quote_product;
        }

        return false;
    }

    public static function updateQuantity($id_quote_product, $quantity)
    {
        if (!Validate::isUnsignedId($id_quote_product) || !Validate::isUnsignedInt($quantity)) {
            return false;
        }

        $quote_product = new QuoteProduct($id_quote_product);
        if (!Validate::isLoadedObject($quote_product)) {
            return false;
        }

        $quote = new Quote((int) $quote_product->id_quote);
        if (!Validate::isLoadedObject($quote) || (int) $quote->id_order > 0) {
            return false;
        }

        $previous_quantity = (int) $quote_product->quantity;

        $available_quantity = self::getAvailableQuantityForQuote(
            $quote_product->id_quote,
            $quote_product->id_product,
            $quote_product->id_product_attribute
        );
        if ($quantity > ($available_quantity + (int) $quote_product->quantity)) {
            return false;
        }

        // Update quantity (les prix sont unitaires, pas besoin de recalculer)
        $quote_product->quantity = $quantity;

        if ($quote_product->save()) {
            if (StockReservation::isQuoteStockLocked($quote_product->id_quote)
                && !StockReservation::synchronizeQuoteStock($quote_product->id_quote)) {
                $quote_product->quantity = $previous_quantity;
                $quote_product->save();
                StockReservation::synchronizeQuoteStock($quote_product->id_quote);
                return false;
            }

            // Update quote totals
            $quote = new Quote($quote_product->id_quote);
            if (method_exists($quote, 'updateTotals')) {
                $quote->updateTotals();
            }
            return true;
        }

        return false;
    }

    public static function updateReduction($id_quote_product, $reduction_percent = 0, $reduction_amount = 0)
    {
        if (!Validate::isUnsignedId($id_quote_product)) {
            return false;
        }

        $quote_product = new QuoteProduct($id_quote_product);
        if (!Validate::isLoadedObject($quote_product)) {
            return false;
        }

        $quote = new Quote((int) $quote_product->id_quote);
        if (!Validate::isLoadedObject($quote) || (int) $quote->id_order > 0) {
            return false;
        }

        // Validation: reduction_percent and reduction_amount must not exceed 100% total
        $reduction_percent = (float) $reduction_percent;
        $reduction_amount = (float) $reduction_amount;

        if ($reduction_percent < 0 || $reduction_percent > 100 || $reduction_amount < 0) {
            return false;
        }

        // Cap at 100% discount
        $line_total = $quote_product->price_tax_excl * $quote_product->quantity;
        $max_reduction = $line_total;
        
        $total_reduction = $reduction_amount;
        if ($reduction_percent > 0) {
            $total_reduction += round($line_total * ($reduction_percent / 100), 2);
        }
        
        if ($total_reduction > $max_reduction) {
            return false;
        }

        // Update reduction
        $quote_product->reduction_percent = $reduction_percent;
        $quote_product->reduction_amount = $reduction_amount;

        if ($quote_product->save()) {
            // Update quote totals
            $quote->updateTotals();
            return true;
        }

        return false;
    }

    public function delete()
    {
        $id_quote = (int) $this->id_quote;
        $quote = new Quote($id_quote);
        if (!Validate::isLoadedObject($quote) || (int) $quote->id_order > 0) {
            return false;
        }

        if (!parent::delete()) {
            return false;
        }

        $quote->updateTotals();

        return !StockReservation::isQuoteStockLocked($id_quote)
            || StockReservation::synchronizeQuoteStock($id_quote);
    }

    /**
     * Remove product from quote
     */
    public static function removeProductFromQuote($id_quote_product, $id_quote = null)
    {
        if (!Validate::isUnsignedId($id_quote_product)) {
            return false;
        }

        $quote_product = new QuoteProduct($id_quote_product);
        if (!Validate::isLoadedObject($quote_product)) {
            return false;
        }

        if ($id_quote !== null && (int) $quote_product->id_quote !== (int) $id_quote) {
            return false;
        }

        $id_quote = (int) $quote_product->id_quote;

        if ($quote_product->delete()) {
            // Update quote totals
            $quote = new Quote($id_quote);
            if (method_exists($quote, 'updateTotals')) {
                $quote->updateTotals();
            }
            return true;
        }

        return false;
    }

    /**
     * Get product information for quote
     */
    protected static function getProductInfoForQuote($id_product, $id_product_attribute = 0)
    {
        $id_lang = Context::getContext()->language->id;
        
        $sql = sprintf(
            'SELECT p.reference, p.ean13, pl.name
            FROM %sproduct p
            LEFT JOIN %sproduct_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = %d)
            WHERE p.id_product = %d',
            _DB_PREFIX_,
            _DB_PREFIX_,
            (int)$id_lang,
            (int)$id_product
        );

        $product_info = Db::getInstance()->getRow($sql);
        
        if (!$product_info) {
            return false;
        }

        if ((int) $id_product_attribute > 0) {
            $combination = new Combination((int) $id_product_attribute);
            if (Validate::isLoadedObject($combination)) {
                $product_info['reference'] = $combination->reference ?: $product_info['reference'];
                $product_info['ean13'] = $combination->ean13 ?: $product_info['ean13'];
                $attributes = $combination->getAttributesName((int) $id_lang);
                $product_info['attributes'] = implode(', ', array_column((array) $attributes, 'name'));
            }
        }

        return $product_info;
    }

    /**
     * Récupère tous les produits d'un devis
     */
    public static function getByQuote($id_quote)
    {
        if (!$id_quote) {
            return array();
        }
        
        $sql = 'SELECT 
            id_quote_product,
            id_quote,
            id_product,
            id_product_attribute,
            quantity,
            price_tax_excl,
            price_tax_incl,
            product_name,
            product_reference,
            product_ean13,
            product_attributes,
            tax_rate,
            notes,
            reduction_percent,
            reduction_amount,
            ROUND(quantity * price_tax_excl, 2) as total_price_tax_excl,
            ROUND(quantity * price_tax_incl, 2) as total_price_tax_incl
        FROM `' . _DB_PREFIX_ . 'quote_product` 
        WHERE `id_quote` = ' . (int)$id_quote;
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Check if product is available for quote
     */
    public static function isProductAvailable($id_product, $id_product_attribute = 0, $quantity = 1)
    {
        // Check if product exists and is active
        $product = new Product($id_product);
        if (!Validate::isLoadedObject($product) || !$product->active) {
            return false;
        }

        // Check stock if stock management is enabled
        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $stock_quantity = StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);
            
            if ($stock_quantity < $quantity && !$product->out_of_stock) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the remaining stock available for a product in a quote.
     */
    public static function getAvailableQuantityForQuote($id_quote, $id_product, $id_product_attribute = 0)
    {
        if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
            return PHP_INT_MAX;
        }

        $stock_quantity = (int) StockAvailable::getQuantityAvailableByProduct(
            (int) $id_product,
            (int) $id_product_attribute,
            (int) Context::getContext()->shop->id
        );
        $quoted_quantity = (int) Db::getInstance()->getValue(
            'SELECT COALESCE(SUM(`quantity`), 0) FROM `' . _DB_PREFIX_ . 'quote_product`'
            . ' WHERE `id_quote` = ' . (int) $id_quote
            . ' AND `id_product` = ' . (int) $id_product
            . ' AND `id_product_attribute` = ' . (int) $id_product_attribute
        );
        $reserved_quantity = StockReservation::getReservedQuantityForQuoteProduct(
            $id_quote,
            $id_product,
            $id_product_attribute
        );

        return max(0, $stock_quantity - max(0, $quoted_quantity - $reserved_quantity));
    }

    /**
     * Return the maximum quantity permitted for one existing quote line.
     */
    public static function getMaximumQuantityForQuoteProduct($id_quote, $id_product, $id_product_attribute = 0)
    {
        if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
            return PHP_INT_MAX;
        }

        $stock_quantity = (int) StockAvailable::getQuantityAvailableByProduct(
            (int) $id_product,
            (int) $id_product_attribute,
            (int) Context::getContext()->shop->id
        );
        $reserved_quantity = StockReservation::getReservedQuantityForQuoteProduct(
            $id_quote,
            $id_product,
            $id_product_attribute
        );

        return max(0, $stock_quantity + $reserved_quantity);
    }

    /**
     * Duplicate quote products to another quote
     */
    public static function duplicateQuoteProducts($id_quote_source, $id_quote_target)
    {
        if (!Validate::isUnsignedId($id_quote_source) || !Validate::isUnsignedId($id_quote_target)) {
            return false;
        }

        $products = self::getByQuote($id_quote_source);
        
        foreach ($products as $product_data) {
            $quote_product = new QuoteProduct();
            $quote_product->id_quote = $id_quote_target;
            $quote_product->id_product = $product_data['id_product'];
            $quote_product->id_product_attribute = $product_data['id_product_attribute'];
            $quote_product->quantity = $product_data['quantity'];
            $quote_product->price_tax_excl = $product_data['price_tax_excl'];
            $quote_product->price_tax_incl = $product_data['price_tax_incl'];
            $quote_product->product_name = $product_data['product_name'];
            $quote_product->product_reference = $product_data['product_reference'];
            $quote_product->product_ean13 = $product_data['product_ean13'];
            $quote_product->product_attributes = $product_data['product_attributes'];
            $quote_product->tax_rate = $product_data['tax_rate'];
            $quote_product->notes = $product_data['notes'];
            
            if (!$quote_product->save()) {
                return false;
            }
        }
        
        return true;
    }
}
