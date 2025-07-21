<?php
// /myquotemanager/hooks/DisplayAdminOrderHook.php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_QuoteManagerDisplayAdminOrderHook
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function render(array $params = []): string
    {
        // Test simple - bouton factice
        return '<div class="alert alert-success" style="margin: 10px 0;">
                    <strong>🎯 HOOK DisplayAdminOrder ACTIF !</strong>
                    <br><button class="btn btn-primary" disabled>
                        📋 Créer un devis (TEST)
                    </button>
                </div>';
    }
}
