<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_1($module)
{
    if (!Configuration::hasKey('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER')) {
        Configuration::updateValue('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER', 'Ymd');
    }

    if (!Configuration::hasKey('PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS')) {
        Configuration::updateValue('PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS', 1);
    }

    return true;
}