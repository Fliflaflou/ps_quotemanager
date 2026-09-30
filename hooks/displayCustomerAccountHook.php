<?php
// /myquotemanager/hooks/DisplayCustomerAccountHook.php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_QuoteManagerDisplayCustomerAccountHook
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function render(array $params = []): string
    {
        return '';
    }
}
