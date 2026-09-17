<?php
// /myquotemanager/hooks/DisplayHeaderHook.php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MyQuoteManagerDisplayHeaderHook
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
