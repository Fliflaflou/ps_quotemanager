<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_5($module)
{
    if (!Configuration::hasKey('PS_QUOTEMANAGER_PDF_PRICE_DISPLAY')) {
        return Configuration::updateValue('PS_QUOTEMANAGER_PDF_PRICE_DISPLAY', 'tax_incl');
    }

    return true;
}