<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_4($module)
{
    $db = Db::getInstance();
    $quoteTable = _DB_PREFIX_ . 'quote';

    $columns = [
        'global_reduction_percent' => 'ADD `global_reduction_percent` decimal(20,6) NOT NULL DEFAULT \'0.000000\' AFTER `total_discount_wt`',
        'global_reduction_amount' => 'ADD `global_reduction_amount` decimal(20,6) NOT NULL DEFAULT \'0.000000\' AFTER `global_reduction_percent`',
        'total_global_discount' => 'ADD `total_global_discount` decimal(20,6) NOT NULL DEFAULT \'0.000000\' AFTER `global_reduction_amount`',
        'total_global_discount_wt' => 'ADD `total_global_discount_wt` decimal(20,6) NOT NULL DEFAULT \'0.000000\' AFTER `total_global_discount`',
    ];

    foreach ($columns as $column => $alterClause) {
        if (!myquotemanager_upgrade_024_column_exists($quoteTable, $column)
            && !$db->execute('ALTER TABLE `' . bqSQL($quoteTable) . '` ' . $alterClause)) {
            return false;
        }
    }

    return true;
}

function myquotemanager_upgrade_024_column_exists($table, $column)
{
    return !empty(Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "' . pSQL($column) . '"'
    ));
}
