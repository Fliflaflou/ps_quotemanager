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
        // Test simple - ajout CSS inline pour vérifier
        return '<style id="quote-manager-header-hook">
                    body::before {
                        content: "🎯 HOOK DisplayHeader ACTIF - Quote Manager";
                        position: fixed;
                        top: 0;
                        right: 0;
                        background: #28a745;
                        color: white;
                        padding: 5px 10px;
                        font-size: 12px;
                        z-index: 9999;
                    }
                </style>';
    }
}
