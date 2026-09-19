<?php

class QuoteOrderPaymentModule extends PaymentModule
{
    public function __construct(Module $module)
    {
        $this->id = (int) $module->id;
        $this->name = $module->name;
        $this->displayName = $module->displayName;
        $this->active = true;
        $this->context = Context::getContext();
    }
}

class QuoteOrderConverter
{
    private $module;
    private $last_error = '';

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    public function convert(Quote $quote, $id_address_delivery = 0, $id_address_invoice = 0, $id_carrier = null, $id_order_state = 0)
    {
        if ((int) $quote->id_order > 0) {
            return new Order((int) $quote->id_order);
        }

        if (!$quote->canConvertToOrder()) {
            return $this->fail('Le devis doit contenir des produits et ne pas etre expire');
        }

        $customer = new Customer((int) $quote->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return $this->fail('Client du devis introuvable');
        }

        $id_address_delivery = $this->getValidCustomerAddressId($customer, $id_address_delivery)
            ?: $this->getCustomerAddressId($quote, $customer);
        $id_address_invoice = $this->getValidCustomerAddressId($customer, $id_address_invoice)
            ?: $this->getValidCustomerAddressId($customer, (int) $quote->id_address_invoice)
            ?: $id_address_delivery;
        if (!$id_address_delivery || !$id_address_invoice) {
            return $this->fail('Le client doit disposer d une adresse pour creer la commande');
        }

        $cart = new Cart();
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = $id_address_delivery;
        $cart->id_address_invoice = $id_address_invoice;
        $cart->id_currency = (int) $quote->id_currency;
        $cart->id_lang = (int) $quote->id_lang;
        $cart->id_shop = (int) Context::getContext()->shop->id;
        $cart->id_shop_group = (int) Context::getContext()->shop->id_shop_group;
        $cart->secure_key = $customer->secure_key;
        $selectedCarrierId = $id_carrier !== null
            ? $this->getValidCarrierId($id_carrier)
            : $this->getValidCarrierId((int) $quote->id_carrier);

        try {
            $cartCreated = $cart->add();
        } catch (Throwable $exception) {
            if ((int) $cart->id > 0) {
                $cart->delete();
            }

            return $this->fail($this->formatCartCreationError($exception));
        }

        if (!$cartCreated) {
            return $this->fail('Impossible de creer le panier de la commande');
        }

        if (!StockReservation::releaseQuoteStock((int) $quote->id)) {
            $cart->delete();
            return $this->fail(StockReservation::getLastError());
        }

        $context = Context::getContext();
        $context->customer = $customer;
        $context->cart = $cart;
        $context->currency = new Currency((int) $cart->id_currency);

        foreach ($quote->getProducts() as $quote_product) {
            $id_product = (int) $quote_product['id_product'];
            $id_product_attribute = (int) $quote_product['id_product_attribute'];
            $quantity = (int) $quote_product['quantity'];
            $update_result = $cart->updateQty(
                $quantity,
                $id_product,
                $id_product_attribute,
                false,
                'up',
                $id_address_delivery
            );
            if (!$update_result) {
                $available_quantity = (int) StockAvailable::getQuantityAvailableByProduct(
                    $id_product,
                    $id_product_attribute,
                    (int) Context::getContext()->shop->id
                );
                $product = new Product($id_product, false, (int) Configuration::get('PS_LANG_DEFAULT'));
                $minimal_quantity = $id_product_attribute > 0
                    ? (int) ProductAttribute::getAttributeMinimalQty($id_product_attribute)
                    : (int) $product->minimal_quantity;
                $out_of_stock = (int) StockAvailable::outOfStock($id_product);
                $cart->delete();
                StockReservation::synchronizeQuoteStock((int) $quote->id);
                return $this->fail(sprintf(
                    'Impossible d ajouter le produit #%d (declinaison #%d) au panier : resultat %s, quantite demandee %d, stock visible %d, disponible_commande=%d, quantite_minimale=%d, hors_stock=%d',
                    $id_product,
                    $id_product_attribute,
                    var_export($update_result, true),
                    $quantity,
                    $available_quantity,
                    (int) $product->available_for_order,
                    $minimal_quantity,
                    $out_of_stock
                ));
            }
        }

        if ($selectedCarrierId > 0) {
            $deliveryOption = $this->getDeliveryOptionForCarrier($cart, $id_address_delivery, $selectedCarrierId);
            if (!$deliveryOption) {
                $cart->delete();
                StockReservation::synchronizeQuoteStock((int) $quote->id);
                return $this->fail('Le transporteur sélectionné n est pas disponible pour cette adresse');
            }
            $cart->setDeliveryOption([$id_address_delivery => $deliveryOption]);
        }
        $cart->update();
        $payment_module = new QuoteOrderPaymentModule($this->module);
        // Always create the order in a neutral, non-logable state so PrestaShop
        // never auto-registers a payment based on the cart's raw (unadjusted) total.
        $neutral_order_state = $this->getUnpaidOrderStateId();
        if ($neutral_order_state <= 0) {
            $cart->delete();
            StockReservation::synchronizeQuoteStock((int) $quote->id);
            return $this->fail('Aucun statut de commande non payé n est disponible');
        }
        $requested_order_state = $this->getValidOrderStateId($id_order_state);

        try {
            $created = $payment_module->validateOrder(
                (int) $cart->id,
                $neutral_order_state,
                (float) $cart->getOrderTotal(true, Cart::BOTH),
                'Conversion de devis ' . $quote->reference,
                $quote->message ?: null,
                [],
                null,
                true,
                $cart->secure_key
            );
        } catch (Exception $exception) {
            StockReservation::synchronizeQuoteStock((int) $quote->id);
            return $this->fail($exception->getMessage());
        }

        $id_order = (int) Order::getIdByCartId((int) $cart->id);
        if (!$created || $id_order <= 0) {
            StockReservation::synchronizeQuoteStock((int) $quote->id);
            return $this->fail('La commande n a pas pu etre creee');
        }

        $order = new Order($id_order);
        $this->applyQuotePrices($quote, $order);
        if ($selectedCarrierId <= 0) {
            $this->clearOrderCarrier($order);
        }

        // Apply the vendor's chosen status afterward: setCurrentState() never
        // registers a payment, unlike validateOrder() with a logable state.
        if ($requested_order_state > 0 && $requested_order_state !== $neutral_order_state) {
            $order->setCurrentState($requested_order_state);
        }

        $converted_status_id = QuoteStatus::getConvertedStatusId();
        if (!$converted_status_id) {
            return $this->fail('Le statut Transforme en commande est introuvable');
        }

        $quote->id_order = $id_order;
        $quote->id_quote_status = $converted_status_id;
        $quote->valid = true;
        if (!$quote->update()) {
            return $this->fail('La commande a ete creee mais le devis n a pas pu etre mis a jour');
        }

        return $order;
    }

    private function formatCartCreationError(Throwable $exception)
    {
        $message = $exception->getMessage();

        if (stripos($message, 'last_seek_key') !== false) {
            $hookModules = $this->getCartAddHookModuleNames();
            $moduleHint = $hookModules
                ? ' Modules branches sur l ajout panier : ' . implode(', ', $hookModules) . '.'
                : '';

            return 'Impossible de creer le panier : un module tiers utilise la colonne SQL manquante '
                . '`last_seek_key`.' . $moduleHint
                . ' Mettez a jour ou reinstallez le module concerne afin d executer sa migration de base de donnees.';
        }

        return 'Impossible de creer le panier : ' . $message;
    }

    private function getCartAddHookModuleNames()
    {
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT DISTINCT m.`name` FROM `' . _DB_PREFIX_ . 'module` m'
                . ' INNER JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module`'
                . ' INNER JOIN `' . _DB_PREFIX_ . 'hook` h ON h.`id_hook` = hm.`id_hook`'
                . ' WHERE m.`active` = 1'
                . ' AND hm.`id_shop` = ' . (int) Context::getContext()->shop->id
                . ' AND h.`name` IN ("actionObjectAddAfter", "actionObjectCartAddAfter")'
                . ' ORDER BY m.`name`'
            );

            return array_values(array_filter(array_column((array) $rows, 'name')));
        } catch (Throwable $exception) {
            return [];
        }
    }

    private function getCustomerAddressId(Quote $quote, Customer $customer)
    {
        foreach ([(int) $quote->id_address_delivery, (int) $quote->id_address_invoice] as $id_address) {
            if ($id_address > 0 && Customer::customerHasAddress((int) $customer->id, $id_address)) {
                return $id_address;
            }
        }

        return (int) Address::getFirstCustomerAddressId((int) $customer->id);
    }

    private function getValidCustomerAddressId(Customer $customer, $id_address)
    {
        $id_address = (int) $id_address;

        return $id_address > 0 && Customer::customerHasAddress((int) $customer->id, $id_address)
            ? $id_address
            : 0;
    }

    private function getValidCarrierId($id_carrier)
    {
        $id_carrier = (int) $id_carrier;
        if ($id_carrier <= 0) {
            return 0;
        }

        $carrier = new Carrier($id_carrier);

        return Validate::isLoadedObject($carrier) && $carrier->active ? $id_carrier : 0;
    }

    private function getDeliveryOptionForCarrier(Cart $cart, $id_address_delivery, $id_carrier)
    {
        $options = $cart->getDeliveryOptionList(null, true);
        foreach ((array) ($options[(int) $id_address_delivery] ?? []) as $optionKey => $option) {
            if (isset($option['carrier_list'][(int) $id_carrier])) {
                return $optionKey;
            }
        }

        return '';
    }

    private function getDefaultCarrierId()
    {
        $carriers = Carrier::getCarriers((int) Context::getContext()->language->id, true, false, false, null, Carrier::ALL_CARRIERS);

        return !empty($carriers) ? (int) $carriers[0]['id_carrier'] : 0;
    }

    private function clearOrderCarrier(Order $order)
    {
        $order->id_carrier = 0;
        $order->total_shipping_tax_excl = 0.0;
        $order->total_shipping_tax_incl = 0.0;
        $order->total_shipping = 0.0;
        $order->update();
        Db::getInstance()->delete('order_carrier', 'id_order = ' . (int) $order->id);
    }

    public function getUnpaidOrderStateId()
    {
        $configuredStateId = (int) Configuration::get('PS_OS_BANKWIRE');
        if ($configuredStateId > 0) {
            $configuredState = new OrderState($configuredStateId);
            if ($this->isNeutralUnpaidState($configuredState)) {
                return $configuredStateId;
            }
        }

        $states = OrderState::getOrderStates((int) Context::getContext()->language->id, true);
        $preferredStateIds = [];
        foreach ((array) $states as $state) {
            if (!$this->isNeutralUnpaidStateData($state)) {
                continue;
            }

            $stateName = $this->normalizeStateName($state['name'] ?? '');
            if (strpos($stateName, 'attente') !== false
                || strpos($stateName, 'pending') !== false
                || strpos($stateName, 'awaiting') !== false
                || strpos($stateName, 'virement') !== false
                || strpos($stateName, 'bank wire') !== false
                || strpos($stateName, 'cheque') !== false
                || strpos($stateName, 'check') !== false
            ) {
                $preferredStateIds[] = (int) $state['id_order_state'];
            }
        }

        $stateId = (int) reset($preferredStateIds);

        if ($stateId > 0) {
            PrestaShopLogger::addLog(
                'QuoteOrderConverter: PS_OS_BANKWIRE est inutilisable, utilisation du statut neutre #' . $stateId,
                2
            );
        }

        return $stateId;
    }

    private function getValidOrderStateId($id_order_state)
    {
        $id_order_state = (int) $id_order_state;
        if ($id_order_state <= 0) {
            return 0;
        }

        $orderState = new OrderState($id_order_state);

        return Validate::isLoadedObject($orderState) && !$orderState->deleted ? $id_order_state : 0;
    }

    private function isNeutralUnpaidState(OrderState $state)
    {
        if (!Validate::isLoadedObject($state)) {
            return false;
        }

        $stateName = is_array($state->name) ? implode(' ', $state->name) : (string) $state->name;

        return !$state->deleted
            && !(int) $state->logable
            && !(int) $state->paid
            && !$this->isNegativePaymentState($stateName);
    }

    private function isNeutralUnpaidStateData(array $state)
    {
        return !(int) ($state['deleted'] ?? 0)
            && !(int) ($state['logable'] ?? 0)
            && !(int) ($state['paid'] ?? 0)
            && !$this->isNegativePaymentState((string) ($state['name'] ?? ''));
    }

    private function isNegativePaymentState($stateName)
    {
        $normalizedName = $this->normalizeStateName($stateName);
        foreach (['annule', 'cancel', 'canceled', 'cancelled', 'erreur', 'error', 'refus', 'refused', 'rembours', 'refund'] as $negativeTerm) {
            if (strpos($normalizedName, $negativeTerm) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizeStateName($stateName)
    {
        return strtolower(strtr(trim((string) $stateName), [
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Î' => 'I', 'Ï' => 'I', 'î' => 'i', 'ï' => 'i',
            'Ô' => 'O', 'Ö' => 'O', 'ô' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
        ]));
    }

    private function applyQuotePrices(Quote $quote, Order $order)
    {
        $lines = $quote->computeProductLines();

        $grouped_products = [];
        $net_total_excl = 0.0;
        foreach ($lines as $line) {
            $key = $line['id_product'] . ':' . $line['id_product_attribute'];
            if (!isset($grouped_products[$key])) {
                $grouped_products[$key] = [
                    'quantity' => 0,
                    'raw_excl' => 0.0,
                    'raw_incl' => 0.0,
                    'net_excl' => 0.0,
                    'net_incl' => 0.0,
                ];
            }
            $grouped_products[$key]['quantity'] += $line['quantity'];
            $grouped_products[$key]['raw_excl'] += $line['raw_tax_excl'];
            $grouped_products[$key]['raw_incl'] += $line['raw_tax_incl'];
            $grouped_products[$key]['net_excl'] += $line['net_tax_excl'];
            $grouped_products[$key]['net_incl'] += $line['net_tax_incl'];
            $net_total_excl += $line['net_tax_excl'];
        }

        // Allocate the whole-quote discount proportionally across product groups so each
        // order line carries its share of both the product-level and the global reduction,
        // and the sum of order_detail totals matches the quote's totals exactly.
        $global_discount_excl = (float) $quote->total_global_discount;
        $global_discount_incl = (float) $quote->total_global_discount_wt;
        $keys = array_keys($grouped_products);
        $last_index = count($keys) - 1;
        $remaining_excl = $global_discount_excl;
        $remaining_incl = $global_discount_incl;

        foreach ($keys as $index => $key) {
            if ($index !== $last_index && $net_total_excl > 0) {
                $share = $grouped_products[$key]['net_excl'] / $net_total_excl;
                $allocated_excl = round($global_discount_excl * $share, 2);
                $allocated_incl = round($global_discount_incl * $share, 2);
            } else {
                // Last group absorbs the rounding remainder so totals reconcile exactly.
                $allocated_excl = round($remaining_excl, 2);
                $allocated_incl = round($remaining_incl, 2);
            }
            $remaining_excl -= $allocated_excl;
            $remaining_incl -= $allocated_incl;

            $grouped_products[$key]['final_excl'] = max(0, round($grouped_products[$key]['net_excl'] - $allocated_excl, 2));
            $grouped_products[$key]['final_incl'] = max(0, round($grouped_products[$key]['net_incl'] - $allocated_incl, 2));
        }

        foreach ($order->getProducts() as $order_product) {
            $key = (int) $order_product['product_id'] . ':' . (int) $order_product['product_attribute_id'];
            if (empty($grouped_products[$key])) {
                continue;
            }

            $group = $grouped_products[$key];
            $quantity = max(1, (int) $group['quantity']);
            $final_excl = (float) $group['final_excl'];
            $final_incl = (float) $group['final_incl'];
            $reduction_tax_excl = max(0, round($group['raw_excl'] - $final_excl, 2));
            $reduction_tax_incl = max(0, round($group['raw_incl'] - $final_incl, 2));

            Db::getInstance()->update('order_detail', [
                'product_price' => (float) $group['raw_excl'] / $quantity,
                'unit_price_tax_excl' => $final_excl / $quantity,
                'unit_price_tax_incl' => $final_incl / $quantity,
                'total_price_tax_excl' => $final_excl,
                'total_price_tax_incl' => $final_incl,
                'reduction_percent' => 0,
                'reduction_amount' => 0,
                'reduction_amount_tax_excl' => $reduction_tax_excl,
                'reduction_amount_tax_incl' => $reduction_tax_incl,
            ], '`id_order_detail` = ' . (int) $order_product['id_order_detail']);
        }

        $order->total_products = (float) $quote->total_products;
        $order->total_products_wt = (float) $quote->total_products_wt;
        $order->total_shipping_tax_excl = (float) $quote->total_shipping;
        $order->total_shipping_tax_incl = (float) $quote->total_shipping_wt;
        $order->total_shipping = $order->total_shipping_tax_incl;
        $order->total_paid_tax_excl = (float) $quote->total_paid_tax_excl;
        $order->total_paid_tax_incl = (float) $quote->total_paid;
        $order->total_paid = (float) $quote->total_paid;
        $order->total_paid_real = 0.0;
        $order->update();
    }

    private function fail($message)
    {
        $this->last_error = $message;
        PrestaShopLogger::addLog('QuoteOrderConverter: ' . $message, 3);

        return false;
    }
}