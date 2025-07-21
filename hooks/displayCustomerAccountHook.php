<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_QuoteManagerDisplayCustomerAccountHook implements HookInterface
{
    public function __construct(
        private Module $module,
        private array $data = []
    ) {
    }

    public function render(array $params): string
    {
        // Vérifier que le client est connecté
        if (!$this->module->context->customer->isLogged()) {
            return '';
        }

        // Récupérer le nombre de devis du client (TODO: implémenter)
        $customerId = (int)$this->module->context->customer->id;
        $nbQuotes = $this->getCustomerQuotesCount($customerId);

        // Assigner les variables au template
        $this->module->context->smarty->assign([
            'quote_link' => $this->module->context->link->getModuleLink($this->module->name, 'quotes'),
            'nb_quotes' => $nbQuotes,
            'module_name' => $this->module->name,
        ]);

        return $this->module->display(
            $this->module->getLocalPath(),
            'views/templates/hooks/displayCustomerAccount.tpl'
        );
    }

    /**
     * Compter les devis du client
     */
    private function getCustomerQuotesCount(int $customerId): int
    {
        // TODO: Implémenter la requête SQL
        // Pour l'instant, retourner 0
        return 0;
    }
}
