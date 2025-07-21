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
        // Test simple - affichage message
        return '<div class="alert alert-info">
                    <strong>🎯 HOOK DisplayCustomerAccount ACTIF !</strong>
                    <br>Module Quote Manager chargé avec succès.
                </div>';
    }
}
