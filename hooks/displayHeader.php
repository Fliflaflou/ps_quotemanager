<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_QuoteManagerDisplayHeaderHook implements HookInterface
{
    public function __construct(
        private Module $module,
        private array $data = []
    ) {
    }

    public function render(array $params): string
    {
        // CSS pour toutes les pages
        $this->module->registerStylesheet(
            'module-myquotemanager-style',
            'modules/'.$this->module->name.'/views/css/myquotemanager.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );

        // JavaScript uniquement en front-office
        if (!$this->module->context->controller instanceof AdminController) {
            $this->module->registerJavascript(
                'module-myquotemanager-js',
                'modules/'.$this->module->name.'/views/js/myquotemanager.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );
        }

        // CSS spécifique au back-office
        if ($this->module->context->controller instanceof AdminController) {
            $this->module->registerStylesheet(
                'module-myquotemanager-admin-style',
                'modules/'.$this->module->name.'/views/css/admin.css',
                [
                    'media' => 'all',
                    'priority' => 150,
                ]
            );
        }

        return '';
    }
}
