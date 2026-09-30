<?php
/**
 * Admin controller pour la gestion des statuts de devis
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

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
        
        $this->meta_title = [$this->l('Gestion des statuts de devis')];
        
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
            'stock_lock' => [
                'title' => $this->l('Verrouillage stock'),
                'align' => 'center',
                'type' => 'bool'
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

        $this->toolbar_btn['new'] = [
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('Ajouter un statut'),
            'icon' => 'process-icon-new'
        ];
        
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
                    'type' => 'switch',
                    'label' => $this->l('Verrouiller le stock'),
                    'name' => 'stock_lock',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'stock_lock_on',
                            'value' => 1,
                            'label' => $this->l('Oui')
                        ],
                        [
                            'id' => 'stock_lock_off',
                            'value' => 0,
                            'label' => $this->l('Non')
                        ]
                    ],
                    'hint' => $this->l('Cette option est définie à la création et ne peut plus être modifiée.')
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

    /**
     * Affichage du badge couleur dans la liste
     */
    public function displayColorBadge($value, $row)
    {
        if (empty($value) || empty($row['name'])) {
            return '<span class="badge badge-secondary">-</span>';
        }
        
        // Vérifier que la couleur a un format valide
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            $value = '#cccccc'; // Couleur par défaut
        }
        
        return '<span class="badge" style="background-color: ' . Tools::safeOutput($value) . '; color: white; border: 1px solid #ddd;">' 
            . Tools::safeOutput($row['name']) . '</span>';
    }

    /**
     * Add a prominent creation action above the generic status list.
     */
    public function renderList()
    {
        $add_url = self::$currentIndex . '&add' . $this->table . '&token=' . $this->token;
        $button = '<div class="text-center" style="margin: 0 0 20px;">'
            . '<a href="' . htmlspecialchars($add_url, ENT_QUOTES, 'UTF-8') . '" class="btn btn-success btn-lg">'
            . '<i class="icon-plus"></i> ' . Tools::safeOutput($this->l('Ajouter un statut'))
            . '</a></div>';

        return $button . parent::renderList();
    }

    /**
     * The legacy view action is the edit entry point for quote statuses.
     */
    public function initContent()
    {
        if (Tools::getIsset('view' . $this->table) && (int)Tools::getValue($this->identifier)) {
            $this->display = 'edit';
        }

        parent::initContent();
    }

    /**
     * The stock policy is only selectable while creating a status.
     */
    public function renderForm()
    {
        if ((int)Tools::getValue($this->identifier)) {
            $this->fields_form['input'] = array_values(array_filter(
                $this->fields_form['input'],
                function ($input) {
                    return $input['name'] !== 'stock_lock';
                }
            ));
        }

        return parent::renderForm();
    }
    
    /**
     * Validation avant suppression
     */
    public function processDelete()
    {
        $id_quote_status = (int)Tools::getValue('id_quote_status');
        
        if (!$id_quote_status) {
            $this->errors[] = $this->l('ID de statut invalide');
            return false;
        }
        
        // Vérifier si le statut est utilisé par des devis
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'quote` WHERE `id_quote_status` = ' . $id_quote_status;
        $count = Db::getInstance()->getValue($sql);
        
        if ($count > 0) {
            $this->errors[] = sprintf(
                $this->l('Impossible de supprimer ce statut car il est utilisé par %d devis.'),
                $count
            );
            return false;
        }
        
        return parent::processDelete();
    }

    /**
     * Validation des données du formulaire
     */
    public function processAdd()
    {
        if (!$this->validateForm()) {
            return false;
        }
        
        return parent::processAdd();
    }
    
    public function processUpdate()
    {
        $id_quote_status = (int)Tools::getValue('id_quote_status');
        $existing_status = new QuoteStatus($id_quote_status);
        if (!Validate::isLoadedObject($existing_status)) {
            $this->errors[] = $this->l('Statut de devis introuvable.');
            return false;
        }

        // The stock policy is immutable after status creation.
        $_POST['stock_lock'] = (int)$existing_status->stock_lock;

        if (!$this->validateForm()) {
            return false;
        }
        
        return parent::processUpdate();
    }
    
    /**
     * Validation personnalisée du formulaire
     */
    protected function validateForm()
    {
        $name = Tools::getValue('name');
        $color = Tools::getValue('color');
        $position = Tools::getValue('position');
        $stock_lock = Tools::getValue('stock_lock', 0);
        
        // Validation du nom
        if (empty($name) || strlen($name) < 2) {
            $this->errors[] = $this->l('Le nom du statut doit contenir au moins 2 caractères');
            return false;
        }
        
        // Validation de la couleur (format hexadécimal)
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $this->errors[] = $this->l('La couleur doit être au format hexadécimal (#RRGGBB)');
            return false;
        }
        
        // Validation de la position
        if (!is_numeric($position) || $position < 0) {
            $this->errors[] = $this->l('La position doit être un nombre positif');
            return false;
        }

        if (!in_array((int)$stock_lock, [0, 1], true)) {
            $this->errors[] = $this->l('Le verrouillage stock doit être activé ou désactivé.');
            return false;
        }
        
        return true;
    }
}
