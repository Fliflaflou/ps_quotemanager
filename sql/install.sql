-- Table des devis
CREATE TABLE IF NOT EXISTS `ps_quote` (
    `id_quote` int(11) NOT NULL AUTO_INCREMENT,
    `id_customer` int(11) NOT NULL,
    `id_employee` int(11) DEFAULT NULL,
    `id_shop` int(11) NOT NULL,
    `id_lang` int(11) NOT NULL,
    `id_currency` int(11) NOT NULL,
    `reference` varchar(32) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    `date_exp` datetime DEFAULT NULL,
    `total_products` decimal(20,6) NOT NULL,
    `total_tax_incl` decimal(20,6) NOT NULL,
    `total_tax_excl` decimal(20,6) NOT NULL,
    `id_quote_status` int(11) NOT NULL,
    `note` text,
    PRIMARY KEY (`id_quote`),
    KEY `id_customer` (`id_customer`),
    KEY `id_employee` (`id_employee`),
    KEY `id_shop` (`id_shop`),
    KEY `id_lang` (`id_lang`),
    KEY `id_currency` (`id_currency`),
    KEY `id_quote_status` (`id_quote_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des produits des devis
CREATE TABLE IF NOT EXISTS `ps_quote_product` (
    `id_quote_product` int(11) NOT NULL AUTO_INCREMENT,
    `id_quote` int(11) NOT NULL,
    `id_product` int(11) NOT NULL,
    `id_product_attribute` int(11) DEFAULT NULL,
    `quantity` int(11) NOT NULL,
    `price_tax_incl` decimal(20,6) NOT NULL,
    `price_tax_excl` decimal(20,6) NOT NULL,
    `reduction_percent` decimal(20,6) DEFAULT 0,
    `reduction_amount` decimal(20,6) DEFAULT 0,
    PRIMARY KEY (`id_quote_product`),
    KEY `id_quote` (`id_quote`),
    KEY `id_product` (`id_product`),
    KEY `id_product_attribute` (`id_product_attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des statuts des devis
CREATE TABLE IF NOT EXISTS `ps_quote_status` (
    `id_quote_status` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `color` varchar(32) NOT NULL,
    `send_email` tinyint(1) NOT NULL DEFAULT 0,
    `email_template` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id_quote_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des réservations de stock
CREATE TABLE IF NOT EXISTS `ps_stock_reservation` (
    `id_stock_reservation` int(11) NOT NULL AUTO_INCREMENT,
    `id_quote` int(11) NOT NULL,
    `id_product` int(11) NOT NULL,
    `id_product_attribute` int(11) DEFAULT NULL,
    `quantity` int(11) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    `date_exp` datetime DEFAULT NULL,
    PRIMARY KEY (`id_stock_reservation`),
    KEY `id_quote` (`id_quote`),
    KEY `id_product` (`id_product`),
    KEY `id_product_attribute` (`id_product_attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
