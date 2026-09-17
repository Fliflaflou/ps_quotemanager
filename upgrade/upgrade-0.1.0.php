<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_1_0($module)
{
    $db = Db::getInstance();
    $quote_table = _DB_PREFIX_ . 'quote';
    $status_table = _DB_PREFIX_ . 'quote_status';
    $reservation_table = _DB_PREFIX_ . 'stock_reservation';

    if (!myquotemanager_column_exists($quote_table, 'id_order')
        && !$db->execute('ALTER TABLE `' . bqSQL($quote_table) . '` ADD `id_order` INT(11) NULL AFTER `id_quote_status`')) {
        return false;
    }

    if (!myquotemanager_index_exists($quote_table, 'id_order')
        && !$db->execute('ALTER TABLE `' . bqSQL($quote_table) . '` ADD KEY `id_order` (`id_order`)')) {
        return false;
    }

    if (!myquotemanager_column_exists($quote_table, 'id_carrier')
        && !$db->execute('ALTER TABLE `' . bqSQL($quote_table) . '` ADD `id_carrier` INT(11) NULL AFTER `id_address_invoice`')) {
        return false;
    }

    if (!myquotemanager_column_exists($status_table, 'stock_lock')
        && !$db->execute('ALTER TABLE `' . bqSQL($status_table) . '` ADD `stock_lock` TINYINT(1) NOT NULL DEFAULT 0 AFTER `send_email`')) {
        return false;
    }

    if (!$db->execute(
        'CREATE TABLE IF NOT EXISTS `' . bqSQL($reservation_table) . '` (
            `id_stock_reservation` INT(11) NOT NULL AUTO_INCREMENT,
            `id_quote` INT(11) NOT NULL,
            `id_product` INT(11) NOT NULL,
            `id_product_attribute` INT(11) DEFAULT NULL,
            `quantity_reserved` INT(10) NOT NULL,
            `date_reservation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_expiration` DATETIME NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_stock_reservation`),
            KEY `id_quote` (`id_quote`),
            KEY `id_product` (`id_product`),
            KEY `expiration` (`date_expiration`, `active`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
    )) {
        return false;
    }

    if (!$db->execute(
        'UPDATE `' . bqSQL($status_table) . '` SET `stock_lock` = 1
        WHERE `name` IN ("En attente", "Validé", "Transformé en commande")'
    )) {
        return false;
    }

    return true;
}

function myquotemanager_column_exists($table, $column)
{
    return !empty(Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "' . pSQL($column) . '"'
    ));
}

function myquotemanager_index_exists($table, $index)
{
    return !empty(Db::getInstance()->executeS(
        'SHOW INDEX FROM `' . bqSQL($table) . '` WHERE `Key_name` = "' . pSQL($index) . '"'
    ));
}