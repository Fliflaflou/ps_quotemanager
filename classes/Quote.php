<?php
class Quote extends ObjectModel
    {
        public $reference;
        public $id_customer;
        public $id_address_delivery;
        public $id_address_invoice;
        public $id_currency;
        public $id_lang;
        public $id_quote_status;
        public $total_products;
        public $total_products_wt;
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
                    'required' => false
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
                    'copy_post' => false
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
            }
        }

        /**
         * Generate unique quote reference
         * 
         * @return string
         */
        public static function generateReference()
        {
            $prefix = 'Q';
            $year = date('Y');
            $month = date('m');
            $pattern = $prefix . $year . $month;
            
            // Get next number for this month - REQUÊTE SUR UNE LIGNE
            $sql = sprintf(
                'SELECT reference FROM %squote WHERE reference LIKE \'%s%%\' ORDER BY reference DESC LIMIT 1',
                _DB_PREFIX_,
                pSQL($pattern)
            );
            
            $last_reference = Db::getInstance()->getValue($sql);
            
            if ($last_reference) {
                // Extract number from reference (ex: Q20241201 → 1)
                $last_number = (int)substr($last_reference, strlen($pattern));
                $next_number = $last_number + 1;
            } else {
                $next_number = 1;
            }

            $reference = $pattern . sprintf('%04d', $next_number);

            // Security check: ensure reference doesn't already exist
            $check_sql = sprintf(
                'SELECT COUNT(*) FROM %squote WHERE reference = \'%s\'',
                _DB_PREFIX_,
                pSQL($reference)
            );
            $exists = Db::getInstance()->getValue($check_sql);

            // If it exists (very rare case), try next numbers
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
         * Get quote by reference
         * 
         * @param string $reference
         * @return Quote|false
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
         * Get quotes for a customer
         * 
         * @param int $id_customer
         * @param bool $active_only
         * @return array
         */
        public static function getByCustomer($id_customer, $active_only = true)
        {
            $sql = new DbQuery();
            $sql->select('q.*, qsl.name as status_name, qs.color as status_color')
                ->from('quote', 'q')
                ->leftJoin('quote_status', 'qs', 'q.id_quote_status = qs.id_quote_status')
                ->leftJoin('quote_status_lang', 'qsl', 'qs.id_quote_status = qsl.id_quote_status AND qsl.id_lang = ' . (int)Context::getContext()->language->id)
                ->where('q.id_customer = ' . (int)$id_customer);
                
            if ($active_only) {
                $sql->where('q.valid = 1');
            }
            
            $sql->orderBy('q.date_add DESC');
            
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        }

        /**
         * Calculate totals from quote products
         * 
         * @return bool
         */
        public function calculateTotals()
        {
            if (!$this->id_quote) {
                return false;
            }
            
            $products = QuoteProduct::getByQuote($this->id_quote);
            
            $total_products = 0;
            $total_products_wt = 0;
            
            foreach ($products as $product_data) {
                $total_products += $product_data['total_price_tax_excl'];
                $total_products_wt += $product_data['total_price_tax_incl'];
            }
            
            $this->total_products = $total_products;
            $this->total_products_wt = $total_products_wt;
            $this->total_paid_tax_excl = $total_products + $this->total_shipping;
            $this->total_paid = $total_products_wt + $this->total_shipping_wt;
            
            return true;
        }

        /**
         * Get quote products
         * 
         * @return array
         */
        public function getProducts()
        {
            if (!$this->id_quote) {
                return [];
            }
            
            return QuoteProduct::getByQuote($this->id_quote);
        }

        /**
         * Check if quote is expired
         * 
         * @return bool
         */
        public function isExpired()
        {
            if (!$this->date_exp) {
                return false;
            }
            
            return strtotime($this->date_exp) < time();
        }

        /**
         * Check if quote can be converted to order
         * 
         * @return bool
         */
        public function canConvertToOrder()
        {
            // Quote must be valid and not expired
            if (!$this->valid || $this->isExpired()) {
                return false;
            }
            
            // Must have products
            $products = $this->getProducts();
            if (empty($products)) {
                return false;
            }
            
            // Check stock availability for all products
            foreach ($products as $product) {
                $available_stock = StockAvailable::getQuantityAvailableByProduct(
                    $product['id_product'], 
                    $product['id_product_attribute']
                );
                
                if ($available_stock < $product['quantity']) {
                    return false;
                }
            }
            
            return true;
        }
        

        /**
         * Convert quote to order
         * 
         * @return Order|false
         */
        public function convertToOrder()
        {
            if (!$this->canConvertToOrder()) {
                return false;
            }
            
            // Validate customer
            $customer = new Customer($this->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                return false;
            }
            
            // Create new cart for this customer
            $cart = new Cart();
            $cart->id_customer = $this->id_customer;
            $cart->id_address_delivery = $this->id_address_delivery;
            $cart->id_address_invoice = $this->id_address_invoice;
            $cart->id_currency = $this->id_currency;
            $cart->id_lang = $this->id_lang;
            
            if (!$cart->save()) {
                return false;
            }
            
            // Add products to cart
            $products = $this->getProducts();
            foreach ($products as $product) {
                $cart->updateQty(
                    $product['quantity'],
                    $product['id_product'],
                    $product['id_product_attribute'],
                    false,
                    'up'
                );
            }
            
            // Use existing payment module
            $payment_module = Module::getInstanceByName('bankwire');
            if (!$payment_module) {
                return false;
            }
            
            try {
                $order_id = $payment_module->validateOrder(
                    $cart->id,
                    Configuration::get('PS_OS_PREPARATION'),
                    $this->total_paid,
                    'Quote Conversion',
                    'Quote #' . $this->reference . ' converted to order',
                    [],
                    $this->id_currency,
                    false,
                    $customer->secure_key
                );
                
                if ($order_id) {
                    // Update quote status to "converted" (status ID 4)
                    $this->id_quote_status = 4;
                    $this->update();
                    
                    return new Order($order_id);
                }
            } catch (Exception $e) {
                PrestaShopLogger::addLog(
                    'Quote conversion error: ' . $e->getMessage(),
                    3,
                    null,
                    'Quote',
                    $this->id_quote,
                    true
                );
            }
            
            return false;
        }

        /**
         * Duplicate quote
         * 
         * @return Quote|false
         */
        public function duplicate()
        {
            $new_quote = new Quote();
            $new_quote->id_customer = $this->id_customer;
            $new_quote->id_address_delivery = $this->id_address_delivery;
            $new_quote->id_address_invoice = $this->id_address_invoice;
            $new_quote->id_currency = $this->id_currency;
            $new_quote->id_lang = $this->id_lang;
            $new_quote->id_quote_status = 1; // Draft status
            $new_quote->message = $this->message;
            $new_quote->notes = $this->notes;
            $new_quote->reference = $new_quote->generateReference();
            
            if ($new_quote->save()) {
                // Duplicate products
                $products = $this->getProducts();
                foreach ($products as $product) {
                    $quote_product = new QuoteProduct();
                    $quote_product->id_quote = $new_quote->id_quote;
                    $quote_product->id_product = $product['id_product'];
                    $quote_product->id_product_attribute = $product['id_product_attribute'];
                    $quote_product->quantity = $product['quantity'];
                    $quote_product->unit_price_tax_excl = $product['unit_price_tax_excl'];
                    $quote_product->unit_price_tax_incl = $product['unit_price_tax_incl'];
                    $quote_product->save();
                }
                
                $new_quote->calculateTotals();
                $new_quote->update();
                
                return $new_quote;
            }
            
            return false;
        }
    }
?>
