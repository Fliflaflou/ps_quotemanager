<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_0($module)
{
    $db = Db::getInstance();
    $quoteTable = _DB_PREFIX_ . 'quote';

    if (!myquotemanager_upgrade_020_column_exists($quoteTable, 'id_carrier')
        && !$db->execute(
            'ALTER TABLE `' . bqSQL($quoteTable) . '` ADD `id_carrier` INT(11) NULL AFTER `id_address_invoice`'
        )) {
        return false;
    }

    if (!Configuration::hasKey('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER')) {
        Configuration::updateValue('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER', 'Ymd');
    }
    if (!Configuration::hasKey('QUOTE_REFERENCE_DATE_ORDER')) {
        Configuration::updateValue('QUOTE_REFERENCE_DATE_ORDER', 'Ymd');
    }

    return true;
}

function myquotemanager_upgrade_020_column_exists($table, $column)
{
    return !empty(Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "' . pSQL($column) . '"'
    ));
}
