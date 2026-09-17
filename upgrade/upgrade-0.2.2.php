<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_2($module)
{
    $db = Db::getInstance();
    $quoteTable = _DB_PREFIX_ . 'quote';

    if (!myquotemanager_upgrade_022_column_exists($quoteTable, 'date_exp_notification_sent')
        && !$db->execute(
            'ALTER TABLE `' . bqSQL($quoteTable) . '` ADD `date_exp_notification_sent` DATETIME NULL AFTER `date_exp`'
        )) {
        return false;
    }

    if (!Configuration::hasKey('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED')) {
        Configuration::updateValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED', 0);
    }
    if (!Configuration::hasKey('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS')) {
        Configuration::updateValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS', 3);
    }
    if (!Configuration::hasKey('PS_QUOTEMANAGER_EXPIRATION_CRON_TOKEN')) {
        Configuration::updateValue('PS_QUOTEMANAGER_EXPIRATION_CRON_TOKEN', Tools::passwdGen(32));
    }

    return true;
}

function myquotemanager_upgrade_022_column_exists($table, $column)
{
    return !empty(Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "' . pSQL($column) . '"'
    ));
}