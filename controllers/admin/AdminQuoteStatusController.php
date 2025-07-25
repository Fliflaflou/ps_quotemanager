<?php
/**
 * Admin controller pour la gestion des statuts de devis
 */

class AdminQuoteStatusController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'quote_status';
        $this->className = 'QuoteStatus';
        $this->identifier = 'id_quote_status';
        $this->lang = false;
        
        parent::__construct();
        
        $this->meta_title = $this->l('Gestion des statuts de devis');
        
        // Configuration de la liste
        $this->fields_list = [
            'id_quote_status' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Nom du statut'),
                'width' => 'auto'
            ],
            'color' => [
                'title' => $this->l('Couleur'),
                'width' => 100,
                'callback' => 'displayColorBadge'
            ],
            'position' => [
                'title' => $this->l('Position'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'position' => 'position'
            ],
            'date_add' => [
                'title' => $this->l('Date de création'),
                'align' => 'right',
                'type' => 'datetime',
                'width' => 150
            ]
        ];
        
        // Actions sur les lignes
        $this->actions = ['view', 'edit', 'delete'];
        
        // Actions en masse
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Supprimer la sélection'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Supprimer les éléments sélectionnés ?')
            ]
        ];
        
        // Configuration du formulaire
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Statut de devis'),
                'icon' => 'icon-flag'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nom du statut'),
                    'name' => 'name',
                    'size' => 50,
                    'required' => true,
                    'hint' => $this->l('Nom du statut affiché dans le back-office')
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Couleur'),
                    'name' => 'color',
                    'size' => 20,
                    'required' => true,
                    'hint' => $this->l('Couleur d\'affichage du statut (format hexadécimal)')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Position'),
                    'name' => 'position',
                    'size' => 10,
                    'hint' => $this->l('Position d\'affichage (ordre croissant)')
                ]
            ],
            'submit' => [
                'title' => $this->l('Enregistrer')
            ]
        ];
    }

    public function renderView()
    {
        $id_quote = Tools::getValue('id_quote');
        
        if (!$id_quote || !($quote = new Quote($id_quote))) {
            $this->errors[] = 'Devis introuvable';
            return $this->renderList();
        }
        
        $quote_data = [
            'quote' => $quote,
            'products' => $quote->getProducts(),
            'customer_addresses' => $quote->loadCustomerAddresses(),
            'is_expired' => $quote->isExpired(),
            'can_convert' => $quote->canConvertToOrder(),
        ];
        
        $quote_statuses = QuoteStatus::getQuoteStatuses($this->context->language->id);
        
        $this->context->smarty->assign([
            'quote_data' => $quote_data,
            'quote_statuses' => $quote_statuses,
            'admin_token' => Tools::getAdminTokenLite('AdminQuote'),
            'current_index' => self::$currentIndex,
        ]);
        
        // CSS/JS
        $this->addCSS($this->module->getPathUri() . 'views/css/admin_quote_view.css');
        
        return $this->createTemplate('quote_view.tpl')->fetch();
    }
    
    /**
     * Affichage du badge couleur
     */
    public function displayColorBadge($value, $row)
    {
        return '<span class="badge" style="background-color: ' . $value . '; color: white;">' 
             . $row['name'] . '</span>';
    }
    
    /**
     * Validation avant suppression
     */
    public function processDelete()
    {
        $id_quote_status = (int)Tools::getValue('id_quote_status');
        
        // Vérifier si le statut est utilisé
        $quotes_using_status = Quote::getByStatusId($id_quote_status);
        
        if (count($quotes_using_status) > 0) {
            $this->errors[] = sprintf(
                $this->l('Impossible de supprimer ce statut car il est utilisé par %d devis.'),
                count($quotes_using_status)
            );
            return false;
        }
        
        return parent::processDelete();
    }
}
