<?php
class Ps_QuoteManagerDisplayHeaderHook implements HookInterface
{
    public function __construct(
        private Module $module,
        private array $data = []
    ) {
    }

    public function render(array $params): string
    {
        $this->module->registerStylesheet(
            'module-ps_quotemanager-style',
            'modules/'.$this->module->name.'/views/css/front.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );

        $this->module->registerJavascript(
            'module-ps_quotemanager-js',
            'modules/'.$this->module->name.'/views/js/front.js',
            [
                'position' => 'bottom',
                'priority' => 150,
            ]
        );

        return '';
    }
}
