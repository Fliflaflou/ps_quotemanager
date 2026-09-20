<?php

require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/StockReservation.php';

class Quote extends ObjectModel
{
    public $id_quote;
    public $reference;
    public $id_customer;
    public $id_address_delivery;
    public $id_address_invoice;
    public $id_carrier;
    public $id_currency;
    public $id_lang;
    public $id_quote_status;
    public $id_order;
    public $total_products;
    public $total_products_wt;
    public $total_discount;
    public $total_discount_wt;
    public $global_reduction_percent = 0;
    public $global_reduction_amount = 0;
    public $total_global_discount = 0;
    public $total_global_discount_wt = 0;
    public $total_shipping;
    public $total_shipping_wt;
    public $total_paid;
    public $total_paid_tax_excl;
    public $date_exp;
    public $message;
    public $notes;
    public $valid;
    public $date_add;
    public $date_upd;

    /** @var bool Not persisted: true when a carrier is set but no delivery option matched it. */
    public $shipping_unavailable = false;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'quote',
        'primary' => 'id_quote',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => [
            'reference' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 32
            ],
            'id_customer' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'id_address_delivery' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false
            ],
            'id_address_invoice' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false
            ],
            'id_carrier' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false
            ],
            'id_currency' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'id_lang' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'id_quote_status' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'id_order' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false
            ],
            'total_products' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_products_wt' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_discount' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_discount_wt' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'global_reduction_percent' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPercentage',
                'required' => false
            ],
            'global_reduction_amount' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_global_discount' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_global_discount_wt' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_shipping' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_shipping_wt' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_paid' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'total_paid_tax_excl' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => false
            ],
            'date_exp' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => false,
                'allow_null' => true
            ],
            'message' => [
                'type' => self::TYPE_HTML,
                'validate' => 'isCleanHtml',
                'required' => false,
                'size' => 65000
            ],
            'notes' => [
                'type' => self::TYPE_HTML,
                'validate' => 'isCleanHtml',
                'required' => false,
                'size' => 65000
            ],
            'valid' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'copy_post' => false,
                'allow_null' => false
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'copy_post' => false
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate', 
                'copy_post' => false
            ]
        ]
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        
        // Set default values
        if (!$this->id_quote) {
            $this->reference = $this->generateReference();
            $this->valid = false;
            $this->total_products = 0;
            $this->total_products_wt = 0;
            $this->total_shipping = 0;
            $this->total_shipping_wt = 0;
            $this->total_paid = 0;
            $this->total_paid_tax_excl = 0;
            if ($this->id_customer) {
                $this->loadCustomerAddresses();
            }
        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        // The admin form only submits id_customer; addresses must be resolved here.
        if (!$this->id_address_delivery && !$this->id_address_invoice && $this->id_customer) {
            $this->loadCustomerAddresses();
        }

        if (!parent::add($auto_date, $null_values)) {
            return false;
        }

        if (self::statusLocksStock($this->id_quote_status)
            && !StockReservation::synchronizeQuoteStock((int) $this->id)) {
            parent::delete();
            return false;
        }

        self::sendOwnerNotification('created', $this);

        return true;
    }

    public function update($null_values = false)
    {
        if (!(int) $this->id) {
            return parent::update($null_values);
        }

        // Backfill addresses for quotes created before automatic address assignment existed.
        if (!$this->id_address_delivery && !$this->id_address_invoice && $this->id_customer) {
            $this->loadCustomerAddresses();
        }

        $stored_quote = Db::getInstance()->getRow(
            'SELECT `id_quote_status`, `id_order` FROM `' . _DB_PREFIX_ . 'quote` WHERE `id_quote` = ' . (int) $this->id
        );
        $previous_status_id = (int) $stored_quote['id_quote_status'];
        $stored_order_id = (int) $stored_quote['id_order'];
        if ($stored_order_id > 0) {
            $this->id_order = $stored_order_id;
            $this->id_quote_status = QuoteStatus::getConvertedStatusId();
        }
        $was_stock_locked = self::statusLocksStock($previous_status_id)
            && !$stored_order_id;
        $will_lock_stock = self::statusLocksStock($this->id_quote_status) && !(int) $this->id_order;
        $reservation_changed = false;

        if (!$was_stock_locked && $will_lock_stock) {
            if (!StockReservation::synchronizeQuoteStock((int) $this->id)) {
                return false;
            }
            $reservation_changed = true;
        } elseif ($was_stock_locked && !$will_lock_stock) {
            if (!StockReservation::releaseQuoteStock((int) $this->id)) {
                return false;
            }
            $reservation_changed = true;
        }

        if (parent::update($null_values)) {
            if ($previous_status_id !== (int) $this->id_quote_status) {
                $status = QuoteStatus::getStatusById((int) $this->id_quote_status);
                if (!empty($status['send_email'])) {
                    self::sendOwnerNotification('status_changed', $this, $status['name']);
                }
            }

            return true;
        }

        if ($reservation_changed) {
            if ($was_stock_locked) {
                StockReservation::synchronizeQuoteStock((int) $this->id);
            } else {
                StockReservation::releaseQuoteStock((int) $this->id);
            }
        }

        return false;
    }

    private static function sendOwnerNotification($event, Quote $quote, $statusName = null)
    {
        if (!(int) Configuration::get('PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS', Configuration::get('QUOTE_EMAIL_NOTIFICATIONS', 1))) {
            return false;
        }

        $ownerEmail = Configuration::get('PS_SHOP_EMAIL');
        if (!Validate::isEmail($ownerEmail)) {
            PrestaShopLogger::addLog('Quote: adresse e-mail propriétaire invalide', 2, null, 'Quote');
            return false;
        }

        $customer = new Customer((int) $quote->id_customer);
        $customerName = Validate::isLoadedObject($customer)
            ? trim($customer->firstname . ' ' . $customer->lastname)
            : '';
        $subject = $event === 'created'
            ? sprintf('Nouveau devis %s', $quote->reference)
            : sprintf('Changement de statut du devis %s', $quote->reference);
        $idLang = (int) ($quote->id_lang ?: Context::getContext()->language->id);

        $sent = Mail::Send(
            $idLang,
            'quote_notification',
            $subject,
            [
                '{quote_reference}' => $quote->reference,
                '{event}' => $event === 'created' ? 'Création' : 'Changement de statut',
                '{customer_name}' => $customerName,
                '{status_name}' => (string) $statusName,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            ],
            $ownerEmail,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'myquotemanager/mails/'
        );

        if (!$sent) {
            PrestaShopLogger::addLog('Quote: échec de notification e-mail ' . $event, 2, null, 'Quote');
        }

        return (bool) $sent;
    }

    public function delete()
    {
        $id_quote = (int) $this->id;
        $stock_was_released = StockReservation::releaseQuoteStock($id_quote);
        if (!$stock_was_released) {
            return false;
        }

        $db = Db::getInstance();
        $db->execute('START TRANSACTION');

        try {
            if (!$db->delete('quote_product', '`id_quote` = ' . $id_quote)
                || !$db->delete('stock_reservation', '`id_quote` = ' . $id_quote)
                || !parent::delete()) {
                throw new RuntimeException('Unable to delete quote data');
            }

            $db->execute('COMMIT');
            return true;
        } catch (Throwable $exception) {
            $db->execute('ROLLBACK');
            PrestaShopLogger::addLog('Quote deletion failed: ' . $exception->getMessage(), 3, null, 'Quote', $id_quote);
        }

        if (self::statusLocksStock($this->id_quote_status)) {
            StockReservation::synchronizeQuoteStock($id_quote);
        }

        return false;
    }

    private static function statusLocksStock($id_quote_status)
    {
        $status = QuoteStatus::getStatusById((int) $id_quote_status);

        return !empty($status) && (bool) $status['stock_lock'];
    }

    private function loadCustomerAddresses()
    {
        $customer = new Customer($this->id_customer);
        if (Validate::isLoadedObject($customer)) {
            $addresses = $customer->getAddresses((int)Context::getContext()->language->id);
            if (!empty($addresses)) {
                $this->id_address_delivery = $addresses[0]['id_address'];
                $this->id_address_invoice = $addresses[0]['id_address'];
            }
        }
    }

    /**
     * Generate unique quote reference
     */
    public static function generateReference()
    {
        $prefix = trim((string) Configuration::get('PS_QUOTEMANAGER_REFERENCE_PREFIX', ''));
        if ($prefix === '') {
            $prefix = trim((string) Configuration::get('QUOTE_REFERENCE_PREFIX', ''));
        }
        $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', $prefix);
        $prefix = substr($prefix, 0, 20);
        if ($prefix === '') {
            $prefix = 'Q';
        }

        $dateOrder = (string) Configuration::get(
            'PS_QUOTEMANAGER_REFERENCE_DATE_ORDER',
            Configuration::get('QUOTE_REFERENCE_DATE_ORDER', 'Ymd')
        );
        if (!in_array($dateOrder, ['Ymd', 'Ydm', 'mYd', 'mdY', 'dYm', 'dmY'], true)) {
            $dateOrder = 'Ymd';
        }
        $dateParts = [
            'Y' => date('Y'),
            'm' => date('m'),
            'd' => date('d'),
        ];
        $datePart = '';
        foreach (str_split($dateOrder) as $dateToken) {
            $datePart .= $dateParts[$dateToken];
        }
        $pattern = $prefix . $datePart;
        
        // Get next number for this month
        $sql = sprintf(
            'SELECT reference FROM %squote WHERE reference LIKE \'%s%%\' ORDER BY reference DESC',
            _DB_PREFIX_,
            pSQL($pattern)
        );
        
        $last_reference = Db::getInstance()->getValue($sql);
        
        if ($last_reference) {
            $last_number = (int)substr($last_reference, strlen($pattern));
            $next_number = $last_number + 1;
        } else {
            $next_number = 1;
        }

        $reference = $pattern . sprintf('%04d', $next_number);

        // Security check
        $check_sql = sprintf(
            'SELECT COUNT(*) FROM %squote WHERE reference = \'%s\'',
            _DB_PREFIX_,
            pSQL($reference)
        );
        $exists = Db::getInstance()->getValue($check_sql);

        while ($exists > 0) {
            $next_number++;
            $reference = $pattern . sprintf('%04d', $next_number);
            $check_sql = sprintf(
                'SELECT COUNT(*) FROM %squote WHERE reference = \'%s\'',
                _DB_PREFIX_,
                pSQL($reference)
            );
            $exists = Db::getInstance()->getValue($check_sql);
        }

        return $reference;
    }

    /**
     * Get all quotes with pagination
     */
    public static function getAllQuotes($limit = 10, $offset = 0, $status_filter = null)
    {
        $sql = new DbQuery();
        $sql->select('q.*, c.firstname, c.lastname, qs.name as status_name, qs.color as status_color')
            ->from('quote', 'q')
            ->leftJoin('customer', 'c', 'q.id_customer = c.id_customer')
            ->leftJoin('quote_status', 'qs', 'q.id_quote_status = qs.id_quote_status');

        if ($status_filter !== null) {
            $sql->where('q.id_quote_status = ' . (int)$status_filter);
        }
        
        $sql->orderBy('q.date_add DESC')
            ->limit($limit, $offset);
        
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get quote by reference
     */
    public static function getByReference($reference)
    {
        $sql = 'SELECT id_quote FROM ' . _DB_PREFIX_ . 'quote WHERE reference = "' . pSQL($reference) . '"';
        $id_quote = Db::getInstance()->getValue($sql);
        
        if ($id_quote) {
            return new Quote($id_quote);
        }
        
        return false;
    }

    /**
     * Get all quotes for a customer.
     */
    public static function getByCustomer($id_customer)
    {
        if (!Validate::isUnsignedId($id_customer)) {
            return [];
        }

        $sql = new DbQuery();
        $sql->select('q.*')
            ->from('quote', 'q')
            ->where('q.id_customer = ' . (int) $id_customer)
            ->orderBy('q.date_add DESC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get products attached to this quote.
     */
    public function getProducts()
    {
        if (!Validate::isUnsignedId((int) $this->id)) {
            return [];
        }

        return QuoteProduct::getByQuote((int) $this->id);
    }

    /**
     * Compute per-product line totals after product-level reductions.
     * Returns raw and net (post product-reduction) tax excl/incl amounts per line,
     * plus the product/attribute ids needed to map back to order details.
     */
    public function computeProductLines()
    {
        $products = $this->getProducts();
        $lines = [];

        foreach ($products as $product) {
            $line_total_tax_excl = isset($product['total_price_tax_excl'])
                ? (float) $product['total_price_tax_excl']
                : round((float) $product['price_tax_excl'] * (int) $product['quantity'], 2);
            $line_total_tax_incl = isset($product['total_price_tax_incl'])
                ? (float) $product['total_price_tax_incl']
                : round((float) $product['price_tax_incl'] * (int) $product['quantity'], 2);

            $raw_tax_excl = $line_total_tax_excl;
            $raw_tax_incl = $line_total_tax_incl;

            // Apply product-level reductions
            $reduction_amount = (float) $product['reduction_amount'];
            $reduction_percent = (float) $product['reduction_percent'];
            $product_reduction_tax_excl = 0.0;
            $product_reduction_tax_incl = 0.0;

            if ($reduction_amount > 0 || $reduction_percent > 0) {
                // reduction_amount is always in tax_excl
                $product_reduction_tax_excl = $reduction_amount;
                if ($reduction_percent > 0) {
                    $product_reduction_tax_excl += round($line_total_tax_excl * ($reduction_percent / 100), 2);
                }

                // Cap reduction to line total (max 100%)
                $product_reduction_tax_excl = min($product_reduction_tax_excl, $line_total_tax_excl);

                // Calculate reduction in TTC using tax rate
                $tax_rate = isset($product['tax_rate']) ? (float) $product['tax_rate'] : 0;
                $product_reduction_tax_incl = round($product_reduction_tax_excl * (1 + $tax_rate / 100), 2);

                $line_total_tax_excl -= $product_reduction_tax_excl;
                $line_total_tax_incl -= $product_reduction_tax_incl;
            }

            $lines[] = [
                'id_product' => (int) $product['id_product'],
                'id_product_attribute' => (int) $product['id_product_attribute'],
                'quantity' => (int) $product['quantity'],
                'raw_tax_excl' => round($raw_tax_excl, 2),
                'raw_tax_incl' => round($raw_tax_incl, 2),
                'product_reduction_tax_excl' => round($product_reduction_tax_excl, 2),
                'product_reduction_tax_incl' => round($product_reduction_tax_incl, 2),
                'net_tax_excl' => round($line_total_tax_excl, 2),
                'net_tax_incl' => round($line_total_tax_incl, 2),
            ];
        }

        return $lines;
    }

    /**
     * Recalculate monetary totals from quote products.
     */
    public function calculateTotals()
    {
        $lines = $this->computeProductLines();

        $total_tax_excl = 0.0;
        $total_tax_incl = 0.0;
        $total_discount_tax_excl = 0.0;
        $total_discount_tax_incl = 0.0;

        foreach ($lines as $line) {
            $total_tax_excl += $line['net_tax_excl'];
            $total_tax_incl += $line['net_tax_incl'];
            $total_discount_tax_excl += $line['product_reduction_tax_excl'];
            $total_discount_tax_incl += $line['product_reduction_tax_incl'];
        }

        // Apply global (whole-quote) reduction on top of the product-net totals
        $global_reduction_percent = (float) $this->global_reduction_percent;
        $global_reduction_amount = (float) $this->global_reduction_amount;
        $global_reduction_tax_excl = 0.0;
        $global_reduction_tax_incl = 0.0;

        if ($global_reduction_percent > 0 || $global_reduction_amount > 0) {
            $global_reduction_tax_excl = $global_reduction_amount + round($total_tax_excl * ($global_reduction_percent / 100), 2);
            // Cap at 100% of the remaining (product-net) total
            $global_reduction_tax_excl = min($global_reduction_tax_excl, $total_tax_excl);

            // Keep the tax_excl/tax_incl ratio consistent with the remaining total
            $ratio = $total_tax_excl > 0 ? ($total_tax_incl / $total_tax_excl) : 1;
            $global_reduction_tax_incl = round($global_reduction_tax_excl * $ratio, 2);

            $total_tax_excl -= $global_reduction_tax_excl;
            $total_tax_incl -= $global_reduction_tax_incl;
        }

        $this->total_products = round($total_tax_excl, 2);
        $this->total_products_wt = round($total_tax_incl, 2);
        
        $shipping = $this->calculateShippingTotals();
        $this->total_shipping = round($shipping['tax_excl'], 2);
        $this->total_shipping_wt = round($shipping['tax_incl'], 2);
        $this->shipping_unavailable = !empty($shipping['unavailable']);
        
        // Product-level discounts only (displayed as "Réductions produits")
        $this->total_discount = round($total_discount_tax_excl, 2);
        $this->total_discount_wt = round($total_discount_tax_incl, 2);

        // Whole-quote discount (displayed as "Réduction globale du devis")
        $this->total_global_discount = round($global_reduction_tax_excl, 2);
        $this->total_global_discount_wt = round($global_reduction_tax_incl, 2);
        
        $this->total_paid_tax_excl = round($this->total_products + $this->total_shipping, 2);
        $this->total_paid = round($this->total_products_wt + $this->total_shipping_wt, 2);

        return true;
    }

    private function calculateShippingTotals()
    {
        $empty = ['tax_excl' => 0.0, 'tax_incl' => 0.0, 'unavailable' => false];
        $unavailable = ['tax_excl' => 0.0, 'tax_incl' => 0.0, 'unavailable' => true];
        $customer = new Customer((int) $this->id_customer);
        $idCarrier = (int) $this->id_carrier;
        if (!Validate::isLoadedObject($customer) || !$idCarrier) {
            return $empty;
        }

        $idAddress = (int) $this->id_address_delivery;
        if (!$idAddress || !Customer::customerHasAddress((int) $customer->id, $idAddress)) {
            // Legacy quotes created before addresses were auto-assigned: fall back to the customer's own address.
            $idAddress = (int) Address::getFirstCustomerAddressId((int) $customer->id);
        }
        if (!$idAddress) {
            PrestaShopLogger::addLog(
                'Quote: aucune adresse de livraison disponible pour le calcul des frais de port (devis #' . (int) $this->id . ')',
                2,
                null,
                'Quote'
            );
            return $unavailable;
        }

        $cart = new Cart();
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = $idAddress;
        $cart->id_address_invoice = (int) $this->id_address_invoice ?: $idAddress;
        $cart->id_currency = (int) $this->id_currency;
        $cart->id_lang = (int) $this->id_lang;
        $cart->id_shop = (int) Context::getContext()->shop->id;
        $cart->id_shop_group = (int) Context::getContext()->shop->id_shop_group;
        $cart->secure_key = $customer->secure_key;
        if (!$cart->add()) {
            PrestaShopLogger::addLog(
                'Quote: impossible de créer le panier temporaire pour le calcul des frais de port (devis #' . (int) $this->id . ')',
                2,
                null,
                'Quote'
            );
            return $unavailable;
        }

        $context = Context::getContext();
        $previousCustomer = $context->customer;
        $previousCart = $context->cart;
        $previousCurrency = $context->currency;
        $context->customer = $customer;
        $context->cart = $cart;
        $context->currency = new Currency((int) $cart->id_currency);

        $valid = true;
        foreach ($this->getProducts() as $product) {
            if (!$cart->updateQty((int) $product['quantity'], (int) $product['id_product'], (int) $product['id_product_attribute'], false, 'up', $idAddress)) {
                $valid = false;
                break;
            }
        }

        $shipping = $empty;
        if ($valid) {
            $options = $cart->getDeliveryOptionList(null, true);
            $carrierFound = false;
            foreach ((array) ($options[$idAddress] ?? []) as $optionKey => $option) {
                if (isset($option['carrier_list'][$idCarrier])) {
                    $carrierFound = true;
                    $cart->setDeliveryOption([$idAddress => $optionKey]);
                    $shipping['tax_excl'] = (float) $cart->getPackageShippingCost($idCarrier, false);
                    $shipping['tax_incl'] = (float) $cart->getPackageShippingCost($idCarrier, true);
                    break;
                }
            }
            if (!$carrierFound) {
                $shipping = $unavailable;
                PrestaShopLogger::addLog(
                    'Quote: transporteur #' . $idCarrier . ' indisponible pour l’adresse #' . $idAddress
                        . ' (zone, groupe client ou poids hors grille) - devis #' . (int) $this->id,
                    2,
                    null,
                    'Quote'
                );
            }
        } else {
            $shipping = $unavailable;
        }


        $cart->delete();
        $context->customer = $previousCustomer;
        $context->cart = $previousCart;
        $context->currency = $previousCurrency;

        return $shipping;
    }

    /**
     * Recalculate and persist quote totals.
     */
    public function updateTotals()
    {
        if (!Validate::isUnsignedId((int) $this->id)) {
            return false;
        }

        if (!$this->calculateTotals()) {
            return false;
        }

        return $this->update();
    }

    /**
     * Check whether quote is expired based on date_exp.
     */
    public function isExpired()
    {
        if (empty($this->date_exp)) {
            return false;
        }

        return strtotime($this->date_exp) < strtotime(date('Y-m-d'));
    }

    /**
     * Persist a whole-quote discount (percent and/or fixed amount) and recompute totals.
     */
    public function applyGlobalDiscount($discount_percent = 0, $discount_amount = 0)
    {
        if ((int) $this->id_order > 0) {
            return false;
        }

        $discount_percent = (float) $discount_percent;
        $discount_amount = (float) $discount_amount;

        if ($discount_percent < 0 || $discount_percent > 100 || $discount_amount < 0) {
            return false;
        }

        $this->global_reduction_percent = $discount_percent;
        $this->global_reduction_amount = $discount_amount;

        return $this->updateTotals();
    }

    /**
     * Conversion can start only if quote has products and is not expired.
     */
    public function canConvertToOrder()
    {
        if ($this->isExpired()) {
            return false;
        }

        return count($this->getProducts()) > 0;
    }

    /**
     * Reassign this quote to a different customer, without touching quote_product
     * rows (so stock already allocated to this quote is preserved, unlike duplicate()).
     */
    public function changeCustomer($new_id_customer)
    {
        if ((int) $this->id_order > 0) {
            return false;
        }

        $new_id_customer = (int) $new_id_customer;
        $customer = new Customer($new_id_customer);
        if (!Validate::isLoadedObject($customer) || !$customer->active || $customer->deleted) {
            return false;
        }

        if ((int) $this->id_customer === $new_id_customer) {
            return true;
        }

        $this->id_customer = $new_id_customer;
        $this->id_address_delivery = (int) Address::getFirstCustomerAddressId($new_id_customer) ?: null;
        $this->id_address_invoice = $this->id_address_delivery;

        if (!$this->update()) {
            return false;
        }

        return $this->updateTotals();
    }

    /**
     * Duplicate quote and products into a new draft quote.
     */
    public function duplicate()
    {
        if (!Validate::isUnsignedId((int) $this->id)) {
            return false;
        }

        $copy = new self();
        $copy->reference = self::generateReference();
        $copy->id_customer = (int) $this->id_customer;
        $copy->id_address_delivery = $this->id_address_delivery ? (int) $this->id_address_delivery : null;
        $copy->id_address_invoice = $this->id_address_invoice ? (int) $this->id_address_invoice : null;
        $copy->id_currency = (int) $this->id_currency;
        $copy->id_lang = (int) $this->id_lang;
        $copy->id_quote_status = (int) $this->id_quote_status;
        $copy->date_exp = $this->date_exp;
        $copy->message = $this->message;
        $copy->notes = $this->notes;
        $copy->valid = false;

        if (!$copy->add()) {
            return false;
        }

        if (!QuoteProduct::duplicateQuoteProducts((int) $this->id, (int) $copy->id)) {
            return false;
        }

        $copy->updateTotals();

        return $copy;
    }
}
?>
