-- Table des statuts de devis
CREATE TABLE IF NOT EXISTS `PREFIX_quote_status` ( 
  `id_quote_status` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `position` int(10) NOT NULL DEFAULT '0',
  `color` varchar(7) NOT NULL DEFAULT '#ffffff',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_quote_status`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table des traductions des statuts de devis
CREATE TABLE IF NOT EXISTS `PREFIX_quote_status_lang` (
  `id_quote_status` int(11) NOT NULL,
  `id_lang` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id_quote_status`, `id_lang`),
  KEY `id_quote_status` (`id_quote_status`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table des devis
CREATE TABLE IF NOT EXISTS `PREFIX_quote` (
  `id_quote` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(32) NOT NULL,
  `id_customer` int(11) NOT NULL,
  `id_quote_status` int(11) NOT NULL,
  `total_products_ht` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_products_ttc` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_discount_ht` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_discount_ttc` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_ht` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_ttc` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `id_currency` int(11) NOT NULL DEFAULT '1',
  `conversion_rate` decimal(13,6) NOT NULL DEFAULT '1.000000',
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_quote`),
  UNIQUE KEY `reference` (`reference`),
  KEY `id_customer` (`id_customer`),
  KEY `id_quote_status` (`id_quote_status`),
  KEY `valid_until` (`valid_until`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table des produits du devis
CREATE TABLE IF NOT EXISTS `PREFIX_quote_product` (
  `id_quote_product` int(11) NOT NULL AUTO_INCREMENT,
  `id_quote` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_product_attribute` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price_tax_incl` decimal(20,6) NOT NULL,
  `price_tax_excl` decimal(20,6) NOT NULL,
  `reduction_percent` decimal(20,6) DEFAULT '0.000000',
  `reduction_amount` decimal(20,6) DEFAULT '0.000000',
  `product_name` varchar(255) NOT NULL,
  `product_reference` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_quote_product`),
  KEY `id_quote` (`id_quote`),
  KEY `id_product` (`id_product`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table des réservations de stock
CREATE TABLE IF NOT EXISTS `PREFIX_stock_reservation` (
  `id_stock_reservation` int(11) NOT NULL AUTO_INCREMENT,
  `id_quote` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_product_attribute` int(11) DEFAULT NULL,
  `quantity_reserved` int(10) NOT NULL,
  `date_reservation` datetime NOT NULL,
  `date_expiration` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_stock_reservation`),
  KEY `id_quote` (`id_quote`),
  KEY `id_product` (`id_product`),
  KEY `expiration` (`date_expiration`, `active`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;
