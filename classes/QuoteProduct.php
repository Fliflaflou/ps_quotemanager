<?php
    class QuoteProduct extends ObjectModel {
        public $id_quote;
        public $id_product;
        public $id_product_attribute;
        public $quantity;
        public $unit_price_tax_excl;
        public $unit_price_tax_incl;
        public $price_tax_excl;
        public $price_tax_incl;
        public $tax_rate;
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
                'unit_price_tax_excl' => array(
                    'type' => self::TYPE_FLOAT,
                    'validate' => 'isPrice',
                    'required' => true
                ),
                'unit_price_tax_incl' => array(
                    'type' => self::TYPE_FLOAT,
                    'validate' => 'isPrice',
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

        public static function addProductToQuote($id_quote, $id_product, $id_product_attribute = 0, $quantity = 1, $unit_price_tax_excl = 0, $unit_price_tax_incl = 0, $notes = ''){
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

                // Get product information
                $product_info = self::getProductInfoForQuote($id_product, $id_product_attribute);
                if (!$product_info) {
                    return false;
                }

                // Calculate prices if not provided
                if ($unit_price_tax_excl == 0 && $unit_price_tax_incl == 0) {
                    $prices = self::calculateProductPrices($id_product, $id_product_attribute, $quantity);
                    $unit_price_tax_excl = $prices['unit_price_tax_excl'];
                    $unit_price_tax_incl = $prices['unit_price_tax_incl'];
                }

                // Calculate totals
                $price_tax_excl = $unit_price_tax_excl * $quantity;
                $price_tax_incl = $unit_price_tax_incl * $quantity;

                // Calculate tax rate
                $tax_rate = $unit_price_tax_excl > 0 ? (($unit_price_tax_incl - $unit_price_tax_excl) / $unit_price_tax_excl) * 100 : 0;

                // Create QuoteProduct
                $quote_product = new QuoteProduct();
                $quote_product->id_quote = $id_quote;
                $quote_product->id_product = $id_product;
                $quote_product->id_product_attribute = $id_product_attribute;
                $quote_product->quantity = $quantity;
                $quote_product->unit_price_tax_excl = $unit_price_tax_excl;
                $quote_product->unit_price_tax_incl = $unit_price_tax_incl;
                $quote_product->price_tax_excl = $price_tax_excl;
                $quote_product->price_tax_incl = $price_tax_incl;
                $quote_product->tax_rate = $tax_rate;
                $quote_product->product_name = $product_info['name'];
                $quote_product->product_reference = $product_info['reference'];
                $quote_product->product_ean13 = $product_info['ean13'];
                $quote_product->product_attributes = $product_info['attributes'];
                $quote_product->notes = $notes;

                if ($quote_product->save()) {
                    // Update quote totals
                    $quote->updateTotals();
                    return $quote_product;
                }

                return false;
        } 
        public static function updateQuantity($id_quote_product, $quantity){
                if (!Validate::isUnsignedId($id_quote_product) || !Validate::isUnsignedInt($quantity)) {
                    return false;
                }

                $quote_product = new QuoteProduct($id_quote_product);
                if (!Validate::isLoadedObject($quote_product)) {
                    return false;
                }

                // Update quantity and totals
                $quote_product->quantity = $quantity;
                $quote_product->price_tax_excl = $quote_product->unit_price_tax_excl * $quantity;
                $quote_product->price_tax_incl = $quote_product->unit_price_tax_incl * $quantity;

                if ($quote_product->save()) {
                    // Update quote totals
                    $quote = new Quote($quote_product->id_quote);
                    $quote->updateTotals();
                    return true;
                }

                return false;
            }

            /**
             * Remove product from quote
             * 
             * @param int $id_quote_product
             * @return bool
             */
            public static function removeProductFromQuote($id_quote_product)
            {
                if (!Validate::isUnsignedId($id_quote_product)) {
                    return false;
                }

                $quote_product = new QuoteProduct($id_quote_product);
                if (!Validate::isLoadedObject($quote_product)) {
                    return false;
                }

                $id_quote = $quote_product->id_quote;

                if ($quote_product->delete()) {
                    // Update quote totals
                    $quote = new Quote($id_quote);
                    $quote->updateTotals();
                    return true;
                }

                return false;
            }

            /**
             * Get product information for quote
             * 
             * @param int $id_product
             * @param int $id_product_attribute
             * @return array|false
             */
            protected static function getProductInfoForQuote($id_product, $id_product_attribute = 0){
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

                // Get product attributes if exists
                $attributes = '';
                if ($id_product_attribute > 0) {
                    $attributes = Product::getProductAttributesIds($id_product, $id_product_attribute);
                    if ($attributes) {
                        $attr_names = array();
                        foreach ($attributes as $attr) {
                            $attr_names[] = $attr['attribute_name'] . ': ' . $attr['value'];
                        }
                        $attributes = implode(', ', $attr_names);
                    }
                }

                return array(
                    'name' => $product_info['name'],
                    'reference' => $product_info['reference'],
                    'ean13' => $product_info['ean13'],
                    'attributes' => $attributes
                );
            }

        /**
         * Calculate product prices
         * 
         * @param int $id_product
         * @param int $id_product_attribute
         * @param int $quantity
         * @return array
         */
        protected static function calculateProductPrices($id_product, $id_product_attribute = 0, $quantity = 1){
            $context = Context::getContext();
            
            // Get product price
            $price_tax_excl = Product::getPriceStatic(
                $id_product,
                false,
                $id_product_attribute,
                6,
                null,
                false,
                true,
                $quantity,
                false,
                $context->customer->id,
                null,
                $context->shop->id
            );

            $price_tax_incl = Product::getPriceStatic(
                $id_product,
                true,
                $id_product_attribute,
                6,
                null,
                false,
                true,
                $quantity,
                false,
                $context->customer->id,
                null,
                $context->shop->id
            );

            return array(
                'unit_price_tax_excl' => $price_tax_excl,
                'unit_price_tax_incl' => $price_tax_incl
            );
        }

        /**
         * Get quote total by quote ID
         * 
         * @param int $id_quote
         * @return array
         */
        public static function getQuoteTotals($id_quote){
            if (!Validate::isUnsignedId($id_quote)) {
                return array(
                    'total_products' => 0,
                    'total_tax_excl' => 0,
                    'total_tax_incl' => 0
                );
            }

            $sql = sprintf(
                'SELECT 
                    SUM(quantity) as total_products,
                    SUM(price_tax_excl) as total_tax_excl,
                    SUM(price_tax_incl) as total_tax_incl
                FROM %squote_product
                WHERE id_quote = %d',
                _DB_PREFIX_,
                (int)$id_quote
            );

            $result = Db::getInstance()->getRow($sql);
            
            return array(
                'total_products' => $result['total_products'] ? (int)$result['total_products'] : 0,
                'total_tax_excl' => $result['total_tax_excl'] ? (float)$result['total_tax_excl'] : 0,
                'total_tax_incl' => $result['total_tax_incl'] ? (float)$result['total_tax_incl'] : 0
            );
        }

        /**
         * Récupère tous les produits d'un devis
         * 
         * @param int $id_quote ID du devis
         * @return array Liste des produits
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
                unit_price_tax_excl,
                unit_price_tax_incl,
                (quantity * unit_price_tax_excl) as total_price_tax_excl,
                (quantity * unit_price_tax_incl) as total_price_tax_incl
            FROM `' . _DB_PREFIX_ . 'quote_product` 
            WHERE `id_quote` = ' . (int)$id_quote;
            
            return Db::getInstance()->executeS($sql);
        }


        /**
         * Check if product is available for quote
         * 
         * @param int $id_product
         * @param int $id_product_attribute
         * @param int $quantity
         * @return bool
         */
        public static function isProductAvailable($id_product, $id_product_attribute = 0, $quantity = 1){
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
         * Duplicate quote products to another quote
         * 
         * @param int $id_quote_source
         * @param int $id_quote_target
         * @return bool
         */
        public static function duplicateQuoteProducts($id_quote_source, $id_quote_target){
            if (!Validate::isUnsignedId($id_quote_source) || !Validate::isUnsignedId($id_quote_target)) {
                return false;
            }

            $products = self::getProductsByQuoteId($id_quote_source);
            
            foreach ($products as $product) {
                $quote_product = new QuoteProduct();
                $quote_product->id_quote = $id_quote_target;
                $quote_product->id_product = $product['id_product'];
                $quote_product->id_product_attribute = $product['id_product_attribute'];
                $quote_product->quantity = $product['quantity'];
                $quote_product->unit_price_tax_excl = $product['unit_price_tax_excl'];
                $quote_product->unit_price_tax_incl = $product['unit_price_tax_incl'];
                $quote_product->price_tax_excl = $product['price_tax_excl'];
                $quote_product->price_tax_incl = $product['price_tax_incl'];
                $quote_product->tax_rate = $product['tax_rate'];
                $quote_product->product_name = $product['product_name'];
                $quote_product->product_reference = $product['product_reference'];
                $quote_product->product_ean13 = $product['product_ean13'];
                $quote_product->product_attributes = $product['product_attributes'];
                $quote_product->notes = $product['notes'];

                if (!$quote_product->save()) {
                    return false;
                }
            }

            return true;
        }
    }