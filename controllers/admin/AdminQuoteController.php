<?php
/**
 * Admin controller pour la gestion des devis
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/HTMLTemplateQuotePdf.php';
require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/QuoteOrderConverter.php';

class AdminQuoteController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'quote';
        $this->className = 'Quote';
        $this->identifier = 'id_quote';
        $this->lang = false;
        
        parent::__construct();
        
        $this->meta_title = $this->l('Gestion des devis');
        
        // Configuration de la liste
        $this->fields_list = [
            'id_quote' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'reference' => [
                'title' => $this->l('Référence'),
                'width' => 'auto'
            ],
            'customer_name' => [
                'title' => $this->l('Client'),
                'width' => 'auto'
            ],
            'total_paid' => [
                'title' => $this->l('Montant TTC'),
                'align' => 'right',
                'type' => 'price',
                'currency' => true
            ],
            'status_name' => [
                'title' => $this->l('Statut'),
                'width' => 100,
                'callback' => 'displayStatusBadge'
            ],
            'date_exp' => [
                'title' => $this->l('Expire le'),
                'align' => 'center',
                'type' => 'date'
            ],
            'date_add' => [
                'title' => $this->l('Créé le'),
                'align' => 'center',
                'type' => 'datetime'
            ]
        ];
        
        // Actions sur les lignes
        $this->actions = ['view', 'edit', 'pdf', 'email', 'duplicate', 'convert', 'delete'];

        // Pagination : la plupart des boutiques accumulent largement plus de 50 devis
        // avant leur expiration, 100 par défaut évite de paginer inutilement.
        $this->_pagination = [50, 100, 300, 1000];
        $this->_default_pagination = 100;

        // Affiche le bouton "Exporter" ; processExport() est surchargé pour exporter
        // tous les devis (et pas seulement la page courante) avec le détail des réductions.
        $this->allow_export = true;

        // Bouton d'ajout
        $this->toolbar_btn['new'] = [
            'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
            'desc' => $this->l('Créer un devis'),
            'icon' => 'process-icon-new'
        ];
    }
    
    /**
     * Modification de la requête pour les jointures
     */
    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {
        $this->_select = '
            CONCAT(c.firstname, " ", c.lastname) as customer_name,
            qs.name as status_name,
            qs.color as status_color
        ';
        
        $this->_join = '
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON (a.`id_customer` = c.`id_customer`)
            LEFT JOIN `'._DB_PREFIX_.'quote_status` qs ON (a.`id_quote_status` = qs.`id_quote_status`)
        ';
        
        return parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $id_lang_shop);
    }

    /**
     * Export every quote (ignoring pagination/filters) as a detailed CSV, including
     * product-level and whole-quote discount amounts.
     */
    public function processExport($text_delimiter = '"')
    {
        if (!$this->access('view')) {
            return;
        }

        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }

        $rows = Db::getInstance()->executeS(
            'SELECT a.*, CONCAT(c.firstname, " ", c.lastname) as customer_name, c.email as customer_email,'
            . ' qs.name as status_name'
            . ' FROM `' . _DB_PREFIX_ . 'quote` a'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (a.`id_customer` = c.`id_customer`)'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'quote_status` qs ON (a.`id_quote_status` = qs.`id_quote_status`)'
            . ' ORDER BY a.`id_quote` ASC'
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Type: application/force-download; charset=UTF-8');
        header('Cache-Control: no-store, no-cache');
        header('Content-disposition: attachment; filename="devis_' . date('Y-m-d_His') . '.csv"');

        $fd = fopen('php://output', 'wb');
        // BOM so Excel opens the UTF-8 file with accented characters correctly.
        fwrite($fd, "\xEF\xBB\xBF");

        fputcsv($fd, [
            $this->l('ID'),
            $this->l('Référence'),
            $this->l('Client'),
            $this->l('Email'),
            $this->l('Statut'),
            $this->l('Devis validé'),
            $this->l('Total produits HT'),
            $this->l('Total produits TTC'),
            $this->l('Réduction produits HT'),
            $this->l('Réduction produits TTC'),
            $this->l('Réduction globale %'),
            $this->l('Réduction globale montant fixe'),
            $this->l('Réduction globale HT'),
            $this->l('Réduction globale TTC'),
            $this->l('Transport HT'),
            $this->l('Transport TTC'),
            $this->l('Total devis HT'),
            $this->l('Total devis TTC'),
            $this->l('ID commande'),
            $this->l('Créé le'),
            $this->l('Expire le'),
        ], ';', $text_delimiter);

        foreach ($rows as $row) {
            fputcsv($fd, [
                (int) $row['id_quote'],
                $row['reference'],
                trim((string) $row['customer_name']),
                $row['customer_email'],
                $row['status_name'] ?: $this->l('Statut inconnu'),
                (int) $row['valid'] ? $this->l('Oui') : $this->l('Non'),
                number_format((float) $row['total_products'], 2, ',', ''),
                number_format((float) $row['total_products_wt'], 2, ',', ''),
                number_format((float) $row['total_discount'], 2, ',', ''),
                number_format((float) $row['total_discount_wt'], 2, ',', ''),
                number_format((float) $row['global_reduction_percent'], 2, ',', ''),
                number_format((float) $row['global_reduction_amount'], 2, ',', ''),
                number_format((float) $row['total_global_discount'], 2, ',', ''),
                number_format((float) $row['total_global_discount_wt'], 2, ',', ''),
                number_format((float) $row['total_shipping'], 2, ',', ''),
                number_format((float) $row['total_shipping_wt'], 2, ',', ''),
                number_format((float) $row['total_paid_tax_excl'], 2, ',', ''),
                number_format((float) $row['total_paid'], 2, ',', ''),
                (int) $row['id_order'] ?: '',
                $row['date_add'],
                $row['date_exp'],
            ], ';', $text_delimiter);
        }

        fclose($fd);
        exit;
    }

    /**
     * Render colored status badge in BO list.
     */
    public function displayStatusBadge($value, $row)
    {
        $label = !empty($value) ? $value : $this->l('Statut inconnu');
        $color = !empty($row['status_color']) ? $row['status_color'] : '#6c757d';

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#6c757d';
        }

        return '<span class="badge" style="background-color:' . pSQL($color) . ';color:#fff;">' . Tools::safeOutput($label) . '</span>';
    }

    /**
     * Render the PDF download action in the native quote list.
     */
    public function displayPdfLink($token, $id)
    {
        if (!$this->access('view')) {
            return '';
        }

        $href = self::$currentIndex . '&generatequotepdf=1&id_quote=' . (int) $id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-default" title="'
            . Tools::safeOutput($this->l('Télécharger le PDF')) . '"><i class="icon-file-pdf-o"></i> '
            . Tools::safeOutput($this->l('PDF')) . '</a>';
    }

    /**
     * Render the email action in the native quote list.
     */
    public function displayEmailLink($token, $id)
    {
        if (!$this->access('edit')) {
            return '';
        }

        $href = self::$currentIndex . '&sendquoteemail=1&return_to_list=1&id_quote=' . (int) $id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-default" title="'
            . Tools::safeOutput($this->l('Envoyer le devis par e-mail'))
            . '" onclick="return confirm(\'' . Tools::safeOutput($this->l('Envoyer ce devis par e-mail au client ?'))
            . '\');"><i class="icon-envelope"></i> '
            . Tools::safeOutput($this->l('E-mail')) . '</a>';
    }

    public function displayConvertLink($token, $id)
    {
        if (!$this->access('edit')) {
            return '';
        }

        $quote = new Quote((int) $id);
        if (!Validate::isLoadedObject($quote)) {
            return '';
        }

        if ((int) $quote->id_order > 0) {
            return '<a href="' . $this->getOrderAdminLink((int) $quote->id_order) . '" class="btn btn-success" title="'
                . Tools::safeOutput($this->l('Voir la commande')) . '"><i class="icon-shopping-cart"></i> '
                . Tools::safeOutput($this->l('Commande')) . '</a>';
        }

        if (!$quote->canConvertToOrder()) {
            return '';
        }

        $href = self::$currentIndex . '&convertquote=1&id_quote=' . (int) $quote->id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-success" title="'
            . Tools::safeOutput($this->l('Transformer en commande'))
            . '" onclick="return confirm(\'' . Tools::safeOutput($this->l('Transformer ce devis en commande ?'))
            . '\');"><i class="icon-shopping-cart"></i> '
            . Tools::safeOutput($this->l('Convertir')) . '</a>';
    }

    public function displayDuplicateLink($token, $id)
    {
        if (!$this->access('add')) {
            return '';
        }

        $href = self::$currentIndex . '&duplicatequote=1&id_source_quote=' . (int) $id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-default" title="'
            . Tools::safeOutput($this->l('Dupliquer le devis')) . '"><i class="icon-copy"></i> '
            . Tools::safeOutput($this->l('Dupliquer')) . '</a>';
    }

    public function displayViewLink($token, $id, $name = null)
    {
        $href = self::$currentIndex . '&viewquote=1&id_quote=' . (int) $id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-default" title="'
            . Tools::safeOutput($this->l('Voir le devis')) . '"><i class="icon-eye"></i> '
            . Tools::safeOutput($this->l('Voir')) . '</a>';
    }

    public function displayEditLink($token, $id, $name = null)
    {
        $href = self::$currentIndex . '&updatequote=1&id_quote=' . (int) $id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-default" title="'
            . Tools::safeOutput($this->l('Modifier le devis')) . '"><i class="icon-edit"></i> '
            . Tools::safeOutput($this->l('Modifier')) . '</a>';
    }

    public function displayDeleteLink($token, $id, $name = null)
    {
        $href = self::$currentIndex . '&deletequote=1&id_quote=' . (int) $id . '&token=' . $this->token;

        return '<a href="' . $href . '" class="btn btn-danger" title="'
            . Tools::safeOutput($this->l('Supprimer le devis'))
            . '" onclick="return confirm(\'' . Tools::safeOutput($this->l('Supprimer ce devis ?'))
            . '\');"><i class="icon-trash"></i> '
            . Tools::safeOutput($this->l('Supprimer')) . '</a>';
    }
    
    /**
     * Formulaire de création/édition
     */
    public function renderForm()
    {
        $this->addJS($this->module->getPathUri() . 'views/js/admin_quote_form.js');

        // Options pour les clients
        $customers = $this->getCustomersSortedByName();
        $customer_options = [];
        foreach ($customers as $customer) {
            $customer_options[] = [
                'id_option' => $customer['id_customer'],
            'name' => $customer['lastname'] . ' ' . $customer['firstname'] . ' (' . $customer['email'] . ')'
            ];
        }
        
        // Options pour les statuts
        $status_options = [];
        $statuses = QuoteStatus::getQuoteStatuses(false);
        if (empty($statuses)) {
            // Initialize default statuses if the table exists but has no data.
            QuoteStatus::installDefaultStatuses();
            $statuses = QuoteStatus::getQuoteStatuses(false);
        }

        foreach ($statuses as $status) {
            $status_options[] = [
                'id_option' => (int) $status['id_quote_status'],
                'name' => $status['name']
            ];
        }
        
        // Options pour les devises
        $currencies = Currency::getCurrencies();
        $currency_options = [];
        foreach ($currencies as $currency) {
            $currency_options[] = [
                'id_option' => $currency['id_currency'],
                'name' => $currency['name'] . ' (' . $currency['iso_code'] . ')'
            ];
        }
        
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Informations du devis'),
                'icon' => 'icon-file-text-o'
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'id_lang',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Référence'),
                    'name' => 'reference',
                    'required' => true,
                    'col' => 6
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Client'),
                    'name' => 'id_customer',
                    'required' => true,
                    'options' => [
                        'query' => $customer_options,
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'col' => 6,
                    'desc' => '<a id="quote-add-customer-btn" class="btn btn-default btn-sm" href="'
                        . $this->getCreateCustomerUrl() . '"><i class="icon-plus"></i> '
                        . $this->l('Créer un nouveau client') . '</a>'
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Statut'),
                    'name' => 'id_quote_status',
                    'required' => true,
                    'options' => [
                        'query' => $status_options,
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'col' => 6
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Devise'),
                    'name' => 'id_currency',
                    'required' => true,
                    'options' => [
                        'query' => $currency_options,
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'col' => 6
                ],
                [
                    'type' => 'date',
                    'label' => $this->l('Date d\'expiration'),
                    'name' => 'date_exp',
                    'col' => 6
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Note'),
                    'name' => 'notes',
                    'rows' => 5,
                    'col' => 12
                ]
            ],
            'submit' => [
                'title' => $this->l('Enregistrer')
            ]
        ];

        if ($this->display == 'add') {
            $source_quote = $this->getQuoteToDuplicate();
            $default_status_id = null;
            if (!empty($status_options)) {
                $default_status_id = (int) $status_options[0]['id_option'];
            }

            $this->fields_value['id_lang'] = (int) $this->context->language->id;
            if ($source_quote) {
                $this->fields_value['reference'] = Quote::generateReference();
                $this->fields_value['id_customer'] = $this->getDuplicateCustomerId($source_quote);
                $this->fields_value['id_currency'] = (int) $source_quote->id_currency;
                $this->fields_value['date_exp'] = $source_quote->date_exp;
                $this->fields_value['notes'] = $source_quote->notes;
                $this->fields_value['id_quote_status'] = $this->getDraftStatusId() ?: $default_status_id;
                $this->fields_form['legend']['title'] = $this->l('Dupliquer le devis ') . $source_quote->reference;
                foreach ($this->fields_form['input'] as &$input) {
                    if ($input['name'] === 'id_customer') {
                        $input['label'] = $this->l('Client du nouveau devis');
                        $input['desc'] = $this->l('Choisissez le client auquel la copie doit être rattachée');
                        break;
                    }
                }
                unset($input);
                $this->fields_form['input'][] = [
                    'type' => 'hidden',
                    'name' => 'id_source_quote',
                ];
                $this->fields_form['input'][] = [
                    'type' => 'hidden',
                    'name' => 'duplicatequote',
                ];
                $this->fields_value['id_source_quote'] = (int) $source_quote->id;
                $this->fields_value['duplicatequote'] = 1;
            } elseif ($default_status_id) {
                $this->fields_value['id_quote_status'] = $default_status_id;
            }
            if (!$source_quote) {
                $this->fields_value['id_currency'] = (int) $this->context->currency->id;
                $defaultValidityDays = max(1, (int) Configuration::get(
                    'PS_QUOTEMANAGER_DEFAULT_VALIDITY',
                    Configuration::get('QUOTE_DEFAULT_VALIDITY', 30)
                ));
                $this->fields_value['date_exp'] = date('Y-m-d', strtotime('+' . $defaultValidityDays . ' days'));
            }
        }
        
        return parent::renderForm();
    }

    /**
     * Link to the native PrestaShop customer creation form used by order creation.
     */
    private function getCreateCustomerUrl()
    {
        return $this->context->link->getAdminLink('AdminCustomers', true, [], [
            'addcustomer' => 1,
            'liteDisplaying' => 1,
            'submitFormAjax' => 1,
        ]);
    }
    
    /**
     * Actions personnalisées
     */
    public function initProcess()
    {
        parent::initProcess();

        if (Tools::isSubmit('duplicatequote') || Tools::isSubmit('id_source_quote')) {
            if ($this->getQuoteToDuplicate()) {
                $this->display = 'add';
                $this->action = Tools::isSubmit('submitAddquote') ? 'save' : 'new';
            } else {
                $this->errors[] = $this->l('Sélectionnez un devis source à dupliquer');
                $this->display = 'list';
                $this->action = 'list';
            }
            return;
        }

        // Action pour voir les détails
        if (Tools::isSubmit('viewquote')) {
            $this->action = 'view';
        }
        // Action pour ajouter un produit
        elseif (Tools::isSubmit('addproduct')) {
            $this->action = 'addproduct';
        }
    }
    
    /**
     * Affichage des détails du devis
     */
    public function renderView()
    {
        $id_quote = (int)Tools::getValue('id_quote');

        if (!$id_quote) {
            $this->errors[] = $this->l('ID du devis manquant');
            return $this->renderList();
        }

        $quote = new Quote($id_quote);
        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = $this->l('Devis introuvable');
            return $this->renderList();
        }

        // Récupérer le client
        $customer = new Customer($quote->id_customer);

        // Récupérer les produits du devis
        $quote_products = QuoteProduct::getByQuote($id_quote);

        // Enrichir les données des produits
        foreach ($quote_products as &$product_data) {
            $product = new Product($product_data['id_product'], false, $this->context->language->id);
            $product_data['name'] = is_array($product->name)
                ? reset($product->name)
                : $product->name;
            $product_data['reference'] = $product_data['product_reference'] ?: $product->reference;
            $product_data['available_quantity'] = QuoteProduct::getAvailableQuantityForQuote(
                $id_quote,
                $product_data['id_product'],
                $product_data['id_product_attribute']
            ) + (int) $product_data['quantity'];
            $product_data['maximum_quantity'] = QuoteProduct::getMaximumQuantityForQuoteProduct(
                $id_quote,
                $product_data['id_product'],
                $product_data['id_product_attribute']
            );
            $product_data['stock_shortage'] = Configuration::get('PS_STOCK_MANAGEMENT')
                && (int) $product_data['quantity'] > (int) $product_data['maximum_quantity'];
        }

        // Récupérer les produits du catalogue avec prix de base
        $products = $this->buildCatalogProductsForQuoteForm($id_quote);

        // Liste des clients pour le changement de client
        $customers = $this->getCustomersSortedByName();
        $customer_options = [];
        foreach ($customers as $customer_row) {
            $customer_options[] = [
                'id_customer' => (int) $customer_row['id_customer'],
                'name' => $customer_row['lastname'] . ' ' . $customer_row['firstname'] . ' (' . $customer_row['email'] . ')',
            ];
        }

        // Préparer les données pour le template
        $carriers = Carrier::getCarriers((int) $this->context->language->id, true, false, false, null, Carrier::ALL_CARRIERS);
        $selectedCarrierId = (int) $quote->id_carrier;
        $selectedCarrier = new Carrier($selectedCarrierId, (int) $this->context->language->id);
        $orderStates = OrderState::getOrderStates((int) $this->context->language->id, true);
        $defaultOrderStateId = (new QuoteOrderConverter($this->module))->getUnpaidOrderStateId();
        $tpl_vars = [
            'quote' => $quote,
            'customer' => [
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
                'id_customer' => $customer->id
            ],
            'quote_statuses' => QuoteStatus::getQuoteStatuses(false),
            'quote_products' => $quote_products,
            'customer_addresses' => $customer->getAddresses((int) $this->context->language->id),
            'carriers' => $carriers,
            'default_carrier_id' => !empty($carriers) ? (int) $carriers[0]['id_carrier'] : 0,
            'selected_carrier_id' => $selectedCarrierId,
            'selected_carrier_name' => Validate::isLoadedObject($selectedCarrier) ? $selectedCarrier->name : '',
            'order_states' => $orderStates,
            'default_order_state_id' => $defaultOrderStateId,
            'has_stock_shortage' => $this->hasStockShortage($quote_products),
            'products' => $products,
            'currency' => new Currency($quote->id_currency),
            'current_index' => self::$currentIndex,
            'token' => $this->token,
            'add_product_url' => self::$currentIndex . '&addproduct&id_quote=' . $id_quote . '&token=' . $this->token,
            'convert_quote_url' => self::$currentIndex . '&convertquote=1&id_quote=' . $id_quote . '&token=' . $this->token,
            'change_customer_url' => self::$currentIndex . '&viewquote&id_quote=' . $id_quote . '&token=' . $this->token,
            'customer_options' => $customer_options,
            'order_admin_link' => (int) $quote->id_order > 0 ? $this->getOrderAdminLink((int) $quote->id_order) : null,
            'can_view_quote' => $this->access('view'),
            'can_edit_quote' => $this->access('edit'),
            'can_add_quote' => $this->access('add'),
            'module_dir' => _MODULE_DIR_ . 'myquotemanager/'
        ];

        $this->context->smarty->assign($tpl_vars);

        // 🎯 CHEMIN CORRIGÉ ICI :
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'myquotemanager/views/templates/admin/quote_view.tpl');
    }

    /**
     * Formulaire d'ajout de produit
     */
    public function processAddProduct()
    {
        $id_quote = (int)Tools::getValue('id_quote');
        
        if (!$id_quote) {
            $this->errors[] = $this->l('ID du devis manquant');
            return $this->renderList();
        }
        
        $quote = new Quote($id_quote);
        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = $this->l('Devis introuvable');
            return $this->renderList();
        }
        
        // Récupérer tous les produits actifs
        $products = $this->buildCatalogProductsForQuoteForm($id_quote);
        $product_options = [];
        
        foreach ($products as $product) {
            foreach ($product['combinations'] as $combination) {
                $product_options[] = [
                    'id_option' => $product['id_product'],
                    'name' => $combination['display_name'],
                    'id_product_attribute' => (int) $combination['id_product_attribute'],
                ];
            }
        }
        
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Ajouter un produit au devis #') . $quote->reference,
                'icon' => 'icon-plus'
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'id_quote'
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Produit'),
                    'name' => 'id_product',
                    'required' => true,
                    'options' => [
                        'query' => $product_options,
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'col' => 8
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Quantité'),
                    'name' => 'quantity',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'col' => 3
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Prix unitaire HT'),
                    'name' => 'price_tax_excl',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'suffix' => '€',
                    'col' => 3
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Prix unitaire TTC'),
                    'name' => 'price_tax_incl',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'suffix' => '€',
                    'col' => 3
                ]
            ],
            'submit' => [
                'title' => $this->l('Ajouter le produit'),
                'class' => 'btn btn-success'
            ],
            'buttons' => [
                'cancel' => [
                    'title' => $this->l('Retour'),
                    'href' => self::$currentIndex . '&viewquote&id_quote=' . $id_quote . '&token=' . $this->token,
                    'class' => 'btn btn-default'
                ]
            ]
        ];
        
        // Si soumission du formulaire
        if (Tools::isSubmit('submitAddquote_product') || Tools::isSubmit('id_product')) {
            $id_product = (int)Tools::getValue('id_product');
            $id_product_attribute = (int)Tools::getValue('id_product_attribute');
            $quantity = (int)Tools::getValue('quantity');
            $prices = $this->resolveAdjustedPricesFromRequest($id_product, $id_product_attribute);
            $price_tax_excl = $prices['price_tax_excl'];
            $price_tax_incl = $prices['price_tax_incl'];
            $reduction_percent = (float) Tools::getValue('reduction_percent', 0);
            $reduction_amount = (float) Tools::getValue('reduction_amount', 0);
            
            if ($id_product && $quantity > 0 && $price_tax_excl >= 0 && $price_tax_incl >= 0) {
                $available_quantity = QuoteProduct::getAvailableQuantityForQuote($id_quote, $id_product, $id_product_attribute);
                if ($quantity > $available_quantity) {
                    $this->errors[] = sprintf(
                        $this->l('Quantité demandée supérieure au stock disponible (%d).'),
                        $available_quantity
                    );
                } elseif (QuoteProduct::addProductToQuote(
                    $id_quote,
                    $id_product,
                    $id_product_attribute,
                    $quantity,
                    $price_tax_excl,
                    $price_tax_incl,
                    '',
                    $reduction_percent,
                    $reduction_amount
                )) {
                    
                    $this->confirmations[] = $this->l('Produit ajouté avec succès');
                    Tools::redirectAdmin(self::$currentIndex . '&viewquote&id_quote=' . $id_quote . '&token=' . $this->token);
                } else {
                    $this->errors[] = $this->l('Erreur lors de l\'ajout du produit');
                }
            } else {
                $this->errors[] = $this->l('Veuillez remplir tous les champs correctement');
            }
        }
        
        // Assigner les valeurs par défaut
        $this->fields_value = [
            'id_quote' => $id_quote
        ];
        
        return parent::renderForm();
    }

    /**
     * AJAX handler: add product to quote and return row/totals payload.
     */
    private function processAddProductAjax()
    {
        $id_quote = (int) Tools::getValue('id_quote');
        $id_product = (int) Tools::getValue('id_product');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');
        $quantity = (int) Tools::getValue('quantity');
        $reduction_percent = (float) Tools::getValue('reduction_percent', 0);
        $reduction_amount = (float) Tools::getValue('reduction_amount', 0);

        if ($id_quote <= 0 || $id_product <= 0 || $quantity <= 0) {
            return [
                'success' => false,
                'message' => $this->l('Paramètres invalides pour l\'ajout produit'),
            ];
        }

        if ($reduction_percent < 0 || $reduction_percent > 100 || $reduction_amount < 0) {
            return [
                'success' => false,
                'message' => $this->l('La réduction saisie est invalide'),
            ];
        }

        $prices = $this->resolveAdjustedPricesFromRequest($id_product, $id_product_attribute);
        $price_tax_excl = $prices['price_tax_excl'];
        $price_tax_incl = $prices['price_tax_incl'];

        $available_quantity = QuoteProduct::getAvailableQuantityForQuote($id_quote, $id_product, $id_product_attribute);
        if ($quantity > $available_quantity) {
            return [
                'success' => false,
                'message' => sprintf(
                    $this->l('Quantité demandée supérieure au stock disponible (%d).'),
                    $available_quantity
                ),
                'stock_quantity' => $available_quantity,
            ];
        }

        $line_total = $price_tax_excl * $quantity;
        $total_reduction = $reduction_amount + round($line_total * ($reduction_percent / 100), 2);
        if ($total_reduction > $line_total) {
            return [
                'success' => false,
                'message' => $this->l('La réduction ne peut pas dépasser 100% du prix du produit'),
            ];
        }

        $quote_product = QuoteProduct::addProductToQuote(
            $id_quote,
            $id_product,
            $id_product_attribute,
            $quantity,
            $price_tax_excl,
            $price_tax_incl,
            '',
            $reduction_percent,
            $reduction_amount
        );

        if (!$quote_product) {
            return [
                'success' => false,
                'message' => $this->l('Erreur lors de l\'ajout du produit'),
            ];
        }

        $quote = new Quote($id_quote);
        $product = new Product($id_product, false, $this->context->language->id);

        $product_name = is_array($product->name) ? reset($product->name) : $product->name;
        $reference = $product->reference;

        $quote_product_id = (int) ($quote_product->id ?: $quote_product->id_quote_product);

        $total_excl = round($price_tax_excl * $quantity, 2);
        $total_incl = round($price_tax_incl * $quantity, 2);
        $tax_rate = (float) $quote_product->tax_rate;
        $reduction_tax_excl = round($total_reduction, 2);
        $reduction_tax_incl = round($reduction_tax_excl * (1 + $tax_rate / 100), 2);

        return [
            'success' => true,
            'message' => $this->l('Produit ajouté avec succès'),
            'product' => [
                'id_quote_product' => $quote_product_id,
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'name' => $quote_product->product_name,
                'reference' => $quote_product->product_reference,
                'attributes' => $quote_product->product_attributes,
                'quantity' => $quantity,
                'price_tax_excl' => $price_tax_excl,
                'price_tax_incl' => $price_tax_incl,
                'total_excl' => $total_excl,
                'total_incl' => $total_incl,
                'reduction_percent' => $reduction_percent,
                'reduction_amount' => $reduction_amount,
                'line_total_excl_after' => round($total_excl - $reduction_tax_excl, 2),
                'line_total_incl_after' => round($total_incl - $reduction_tax_incl, 2),
            ],
            'stock_quantity' => QuoteProduct::getAvailableQuantityForQuote($id_quote, $id_product, $id_product_attribute),
            'shipping_tax_excl' => (float) $quote->total_shipping,
            'shipping_tax_incl' => (float) $quote->total_shipping_wt,
            'totals' => [
                'total_products' => (float) $quote->total_products,
                'total_products_wt' => (float) $quote->total_products_wt,
                'total_discount' => (float) $quote->total_discount,
                'total_discount_wt' => (float) $quote->total_discount_wt,
                'total_global_discount' => (float) $quote->total_global_discount,
                'total_global_discount_wt' => (float) $quote->total_global_discount_wt,
                'total_paid_tax_excl' => (float) $quote->total_paid_tax_excl,
                'total_paid' => (float) $quote->total_paid,
                'total_shipping' => (float) $quote->total_shipping,
                'total_shipping_wt' => (float) $quote->total_shipping_wt,
            ],
            'currency_id' => (int) $quote->id_currency,
        ];
    }
    
    /**
     * Mise à jour des totaux du devis
     */
    private function updateQuoteTotals($id_quote)
    {
        $quote = new Quote($id_quote);
        if (Validate::isLoadedObject($quote)) {
            $quote->updateTotals();
        }
    }

    /**
     * Suppression d'un produit du devis
     */
    public function processDeleteProduct()
    {
        $id_quote_product = (int)Tools::getValue('id_quote_product');
        $id_quote = (int)Tools::getValue('id_quote');
        
        if (!$id_quote_product || !$id_quote) {
            $this->errors[] = $this->l('Paramètres manquants');
            return false;
        }
        
        $quote_product = new QuoteProduct($id_quote_product);
        if (!Validate::isLoadedObject($quote_product) || (int) $quote_product->id_quote !== $id_quote) {
            $this->errors[] = $this->l('Produit introuvable ou non associé à ce devis');
            return false;
        }

        if ($quote_product->delete()) {
            $this->updateQuoteTotals($id_quote);
            $this->confirmations[] = $this->l('Produit supprimé avec succès');
            return true;
        } else {
            $this->errors[] = $this->l('Erreur lors de la suppression');
            return false;
        }
    }

    /**
     * AJAX handler: remove product from quote and return updated totals.
     */
    private function processDeleteProductAjax()
    {
        $id_quote_product = (int) Tools::getValue('id_quote_product');
        $id_quote = (int) Tools::getValue('id_quote');

        if ($id_quote_product <= 0 || $id_quote <= 0) {
            return [
                'success' => false,
                'message' => $this->l('Paramètres manquants'),
            ];
        }

        if (!QuoteProduct::removeProductFromQuote($id_quote_product, $id_quote)) {
            return [
                'success' => false,
                'message' => $this->l('Produit introuvable ou non associé à ce devis'),
            ];
        }

        $quote = new Quote($id_quote);

        return [
            'success' => true,
            'message' => $this->l('Produit supprimé avec succès'),
            'totals' => [
                'total_products' => (float) $quote->total_products,
                'total_products_wt' => (float) $quote->total_products_wt,
                'total_discount' => (float) $quote->total_discount,
                'total_discount_wt' => (float) $quote->total_discount_wt,
                'total_global_discount' => (float) $quote->total_global_discount,
                'total_global_discount_wt' => (float) $quote->total_global_discount_wt,
                'total_paid_tax_excl' => (float) $quote->total_paid_tax_excl,
                'total_paid' => (float) $quote->total_paid,
                'total_shipping' => (float) $quote->total_shipping,
                'total_shipping_wt' => (float) $quote->total_shipping_wt,
            ],
            'remaining_products' => count(QuoteProduct::getByQuote($id_quote)),
            'currency_id' => (int) $quote->id_currency,
        ];
    }

    /**
     * AJAX handler: update a quote product quantity and return refreshed totals.
     */
    private function processUpdateProductQuantityAjax()
    {
        $id_quote = (int) Tools::getValue('id_quote');
        $id_quote_product = (int) Tools::getValue('id_quote_product');
        $quantity = (int) Tools::getValue('quantity');
        $quote_product = new QuoteProduct($id_quote_product);

        if ($id_quote <= 0 || $quantity <= 0 || !Validate::isLoadedObject($quote_product)
            || (int) $quote_product->id_quote !== $id_quote) {
            return [
                'success' => false,
                'message' => $this->l('Paramètres invalides pour la mise à jour du produit'),
            ];
        }

        $maximum_quantity = QuoteProduct::getAvailableQuantityForQuote(
            $id_quote,
            $quote_product->id_product,
            $quote_product->id_product_attribute
        ) + (int) $quote_product->quantity;

        if ($quantity > $maximum_quantity || !QuoteProduct::updateQuantity($id_quote_product, $quantity)) {
            return [
                'success' => false,
                'message' => sprintf($this->l('Quantité demandée supérieure au stock disponible (%d).'), $maximum_quantity),
                'maximum_quantity' => $maximum_quantity,
            ];
        }

        $quote = new Quote($id_quote);

        return [
            'success' => true,
            'message' => $this->l('Quantité mise à jour avec succès'),
            'quantity' => $quantity,
            'maximum_quantity' => QuoteProduct::getAvailableQuantityForQuote(
                $id_quote,
                $quote_product->id_product,
                $quote_product->id_product_attribute
            ) + $quantity,
            'line_total_excl' => round($quote_product->price_tax_excl * $quantity, 2),
            'line_total_incl' => round($quote_product->price_tax_incl * $quantity, 2),
            'totals' => [
                'total_products' => (float) $quote->total_products,
                'total_products_wt' => (float) $quote->total_products_wt,
                'total_discount' => (float) $quote->total_discount,
                'total_discount_wt' => (float) $quote->total_discount_wt,
                'total_global_discount' => (float) $quote->total_global_discount,
                'total_global_discount_wt' => (float) $quote->total_global_discount_wt,
                'total_paid_tax_excl' => (float) $quote->total_paid_tax_excl,
                'total_paid' => (float) $quote->total_paid,
            ],
        ];
    }

    /**
     * AJAX handler: update a quote product reduction (percent or amount) and return refreshed totals.
     */
    private function processUpdateProductReductionAjax()
    {
        $id_quote = (int) Tools::getValue('id_quote');
        $id_quote_product = (int) Tools::getValue('id_quote_product');
        $reduction_percent = (float) Tools::getValue('reduction_percent', 0);
        $reduction_amount = (float) Tools::getValue('reduction_amount', 0);
        
        $quote_product = new QuoteProduct($id_quote_product);

        if ($id_quote <= 0 || !Validate::isLoadedObject($quote_product)
            || (int) $quote_product->id_quote !== $id_quote) {
            return [
                'success' => false,
                'message' => $this->l('Paramètres invalides pour la mise à jour de la réduction'),
            ];
        }

        if ($reduction_percent < 0 || $reduction_percent > 100 || $reduction_amount < 0) {
            return [
                'success' => false,
                'message' => $this->l('La réduction saisie est invalide'),
            ];
        }

        // Cap reductions at 100%
        $line_total = $quote_product->price_tax_excl * $quote_product->quantity;
        $total_reduction = $reduction_amount + ($line_total * $reduction_percent / 100);
        if ($total_reduction > $line_total) {
            return [
                'success' => false,
                'message' => $this->l('La réduction ne peut pas dépasser 100% du prix du produit'),
            ];
        }

        if (!QuoteProduct::updateReduction($id_quote_product, $reduction_percent, $reduction_amount)) {
            return [
                'success' => false,
                'message' => $this->l('Erreur lors de la mise à jour de la réduction'),
            ];
        }

        $quote = new Quote($id_quote);
        $quote_product = new QuoteProduct($id_quote_product);
        
        // Calculate line totals after reduction
        $line_total_excl_before = $quote_product->price_tax_excl * $quote_product->quantity;
        $line_total_incl_before = $quote_product->price_tax_incl * $quote_product->quantity;
        
        $reduction_tax_excl = $reduction_amount + ($line_total_excl_before * $reduction_percent / 100);
        $tax_rate = (float) $quote_product->tax_rate;
        $reduction_tax_incl = round($reduction_tax_excl * (1 + $tax_rate / 100), 2);
        
        return [
            'success' => true,
            'message' => $this->l('Réduction mise à jour avec succès'),
            'reduction_percent' => $reduction_percent,
            'reduction_amount' => $reduction_amount,
            'reduction_tax_excl' => round($reduction_tax_excl, 2),
            'reduction_tax_incl' => round($reduction_tax_incl, 2),
            'line_total_excl_after' => round($line_total_excl_before - $reduction_tax_excl, 2),
            'line_total_incl_after' => round($line_total_incl_before - $reduction_tax_incl, 2),
            'totals' => [
                'total_products' => (float) $quote->total_products,
                'total_products_wt' => (float) $quote->total_products_wt,
                'total_discount' => (float) $quote->total_discount,
                'total_discount_wt' => (float) $quote->total_discount_wt,
                'total_global_discount' => (float) $quote->total_global_discount,
                'total_global_discount_wt' => (float) $quote->total_global_discount_wt,
                'total_shipping' => (float) $quote->total_shipping,
                'total_shipping_wt' => (float) $quote->total_shipping_wt,
                'total_paid_tax_excl' => (float) $quote->total_paid_tax_excl,
                'total_paid' => (float) $quote->total_paid,
            ],
        ];
    }
    
    /**
     * AJAX handler: update global quote discount (percent or amount).
     */
    private function processUpdateGlobalReductionAjax()
    {
        $id_quote = (int) Tools::getValue('id_quote');
        $global_reduction_percent = (float) Tools::getValue('global_reduction_percent', 0);
        $global_reduction_amount = (float) Tools::getValue('global_reduction_amount', 0);
        
        $quote = new Quote($id_quote);

        if ($id_quote <= 0 || !Validate::isLoadedObject($quote)) {
            return [
                'success' => false,
                'message' => $this->l('Paramètres invalides pour la mise à jour de la réduction globale'),
            ];
        }

        if ((int) $quote->id_order > 0) {
            return [
                'success' => false,
                'message' => $this->l('Ce devis a déjà été transformé en commande'),
            ];
        }

        // Validate inputs
        if ($global_reduction_percent < 0 || $global_reduction_percent > 100) {
            return [
                'success' => false,
                'message' => $this->l('Le pourcentage de réduction doit être entre 0 et 100'),
            ];
        }

        if ($global_reduction_amount < 0) {
            return [
                'success' => false,
                'message' => $this->l('La réduction montant doit être positive'),
            ];
        }

        if (!$quote->applyGlobalDiscount($global_reduction_percent, $global_reduction_amount)) {
            return [
                'success' => false,
                'message' => $this->l('Erreur lors de la mise à jour de la réduction globale'),
            ];
        }

        return [
            'success' => true,
            'message' => $this->l('Réduction globale mise à jour avec succès'),
            'global_reduction_percent' => (float) $quote->global_reduction_percent,
            'global_reduction_amount' => (float) $quote->global_reduction_amount,
            'global_discount_excl' => (float) $quote->total_global_discount,
            'global_discount_incl' => (float) $quote->total_global_discount_wt,
            'totals' => [
                'total_products' => (float) $quote->total_products,
                'total_products_wt' => (float) $quote->total_products_wt,
                'total_discount' => (float) $quote->total_discount,
                'total_discount_wt' => (float) $quote->total_discount_wt,
                'total_global_discount' => (float) $quote->total_global_discount,
                'total_global_discount_wt' => (float) $quote->total_global_discount_wt,
                'total_shipping' => (float) $quote->total_shipping,
                'total_shipping_wt' => (float) $quote->total_shipping_wt,
                'total_paid_tax_excl' => (float) $quote->total_paid_tax_excl,
                'total_paid' => (float) $quote->total_paid,
            ],
        ];
    }
    
    /**
     * Process général
     */
    public function postProcess()
    {
        if ((int) Tools::getValue('ajax') === 1) {
            $action = (string) Tools::getValue('action');

            if ($action === 'addProductInline') {
                if (!$this->access('edit')) {
                    $this->sendJsonResponse(['success' => false, 'message' => $this->l('Vous n’avez pas l’autorisation requise pour cette action')], 403);
                }
                $this->sendJsonResponse($this->processAddProductAjax());
            }

            if ($action === 'removeProductInline') {
                if (!$this->access('edit')) {
                    $this->sendJsonResponse(['success' => false, 'message' => $this->l('Vous n’avez pas l’autorisation requise pour cette action')], 403);
                }
                $this->sendJsonResponse($this->processDeleteProductAjax());
            }

            if ($action === 'updateProductQuantityInline') {
                if (!$this->access('edit')) {
                    $this->sendJsonResponse(['success' => false, 'message' => $this->l('Vous n’avez pas l’autorisation requise pour cette action')], 403);
                }
                $this->sendJsonResponse($this->processUpdateProductQuantityAjax());
            }

            if ($action === 'updateProductReductionInline') {
                if (!$this->access('edit')) {
                    $this->sendJsonResponse(['success' => false, 'message' => $this->l('Vous n\'avez pas l\'autorisation requise pour cette action')], 403);
                }
                $this->sendJsonResponse($this->processUpdateProductReductionAjax());
            }

            if ($action === 'updateGlobalReductionInline') {
                if (!$this->access('edit')) {
                    $this->sendJsonResponse(['success' => false, 'message' => $this->l('Vous n\'avez pas l\'autorisation requise pour cette action')], 403);
                }
                $this->sendJsonResponse($this->processUpdateGlobalReductionAjax());
            }

            $this->sendJsonResponse([
                'success' => false,
                'message' => $this->l('Action AJAX inconnue'),
            ], 400);
        }

        if (Tools::isSubmit('generatequotepdf')) {
            if (!$this->requireQuotePermission('view')) {
                return false;
            }
            $this->processGenerateQuotePdf();
        }

        if (Tools::isSubmit('sendquoteemail')) {
            if (!$this->requireQuotePermission('edit')) {
                return false;
            }
            $this->processSendQuoteEmail();
        }

        if (Tools::isSubmit('convertquote')) {
            if (!$this->requireQuotePermission('edit')) {
                return false;
            }
            $this->processConvertQuoteToOrder();
        }

        // Rediriger l'edition vers la vue unifiee (edition + produits)
        if (Tools::getIsset('updatequote')) {
            $id_quote = (int) Tools::getValue('id_quote');
            if ($id_quote > 0) {
                Tools::redirectAdmin(self::$currentIndex . '&viewquote&id_quote=' . $id_quote . '&token=' . $this->token);
            }
        }

        // Affichage détails
        if (Tools::getIsset('viewquote')) {
            if (Tools::isSubmit('submitUpdateQuoteInline')) {
                $this->processUpdateQuoteInline();
            }

            if (Tools::isSubmit('submitChangeQuoteCustomer')) {
                $this->processChangeQuoteCustomer();
            }

            return $this->renderView();
        }
        
        // Ajout de produit
        if (Tools::getIsset('addproduct')) {
            if (!$this->requireQuotePermission('edit')) {
                return false;
            }
            return $this->processAddProduct();
        }
        
        // Suppression de produit
        if (Tools::getIsset('deleteproduct')) {
            if (!$this->requireQuotePermission('edit')) {
                return false;
            }
            if ($this->processDeleteProduct()) {
                $id_quote = (int)Tools::getValue('id_quote');
                Tools::redirectAdmin(self::$currentIndex . '&viewquote&id_quote=' . $id_quote . '&token=' . $this->token);
            }
        }
        
        return parent::postProcess();
    }

    /**
     * Route duplicated quote submissions before AdminController creates a generic Quote.
     */
    public function processAdd()
    {
        if (Tools::isSubmit('id_source_quote')) {
            if (!$this->requireQuotePermission('add')) {
                return false;
            }
            return $this->processDuplicateQuote();
        }

        $quote = parent::processAdd();
        if ($quote instanceof Quote && Validate::isLoadedObject($quote)) {
            Tools::redirectAdmin(
                self::$currentIndex . '&viewquote&id_quote=' . (int) $quote->id . '&token=' . $this->token
            );
        }

        return $quote;
    }

    /**
     * Download the current quote as a PDF document.
     */
    private function processGenerateQuotePdf()
    {
        $quote = $this->getQuoteFromRequest();
        if (!$quote) {
            return false;
        }

        $pdf = new PDF($quote, 'QuotePdf', $this->context->smarty);
        $pdf->render();
        exit;
    }

    /**
     * Send the quote PDF to the customer by email.
     */
    private function processSendQuoteEmail()
    {
        $quote = $this->getQuoteFromRequest();
        if (!$quote) {
            return false;
        }

        $customer = new Customer((int) $quote->id_customer);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            $this->errors[] = $this->l('Le client du devis ne possède pas d’adresse e-mail valide');
            return false;
        }

        $pdf = new PDF($quote, 'QuotePdf', $this->context->smarty);
        $attachment = [
            'content' => $pdf->render(false),
            'name' => $pdf->getFilename(),
            'mime' => 'application/pdf',
        ];
        $id_lang = (int) ($quote->id_lang ?: $this->context->language->id);
        $subject = sprintf($this->l('Votre devis %s'), $quote->reference);
        $sent = Mail::Send(
            $id_lang,
            'quote_pdf',
            $subject,
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{quote_reference}' => $quote->reference,
                '{date_exp}' => $quote->date_exp ?: $this->l('Non définie'),
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            ],
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            $attachment,
            null,
            _PS_MODULE_DIR_ . 'myquotemanager/mails/'
        );

        if (!$sent) {
            $this->errors[] = $this->l('L’envoi de l’e-mail du devis a échoué');
            return false;
        }

        $this->confirmations[] = $this->l('Le devis a été envoyé par e-mail au client');
        $redirect = self::$currentIndex . '&token=' . $this->token;
        if (!Tools::getValue('return_to_list')) {
            $redirect .= '&viewquote&id_quote=' . (int) $quote->id;
        }
        Tools::redirectAdmin($redirect);
    }

    /**
     * Load and validate the quote targeted by a PDF or email action.
     */
    private function getQuoteFromRequest()
    {
        $quote = new Quote((int) Tools::getValue('id_quote'));
        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = $this->l('Devis introuvable');
            return false;
        }

        return $quote;
    }

    private function requireQuotePermission($action)
    {
        if ($this->access($action)) {
            return true;
        }

        $this->errors[] = $this->l('Vous n’avez pas l’autorisation requise pour cette action');

        return false;
    }

    private function processConvertQuoteToOrder()
    {
        $quote = $this->getQuoteFromRequest();
        if (!$quote) {
            return false;
        }

        $converter = new QuoteOrderConverter($this->module);
        $order = $converter->convert(
            $quote,
            (int) Tools::getValue('id_address_delivery'),
            (int) Tools::getValue('id_address_invoice'),
            Tools::getIsset('id_carrier') ? (int) Tools::getValue('id_carrier') : null,
            (int) Tools::getValue('id_order_state')
        );
        if (!$order || !Validate::isLoadedObject($order)) {
            $this->errors[] = $converter->getLastError() ?: $this->l('La conversion du devis en commande a échoué');
            return false;
        }

        Tools::redirectAdmin($this->getOrderAdminLink((int) $order->id));
    }

    private function getOrderAdminLink($id_order)
    {
        return $this->context->link->getAdminLink('AdminOrders', true, [], [
            'id_order' => (int) $id_order,
            'vieworder' => 1,
        ]);
    }

    private function getQuoteToDuplicate()
    {
        if (!Tools::isSubmit('duplicatequote') && !Tools::isSubmit('id_source_quote')) {
            return false;
        }

        $source_quote_id = (int) Tools::getValue('id_source_quote', Tools::getValue('id_quote'));
        $quote = new Quote($source_quote_id);

        return Validate::isLoadedObject($quote) ? $quote : false;
    }

    private function getDraftStatusId()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_quote_status` FROM `' . _DB_PREFIX_ . 'quote_status`'
            . ' WHERE `name` = "Brouillon" AND `active` = 1 AND `deleted` = 0'
        );
    }

    private function processDuplicateQuote()
    {
        $source_quote = new Quote((int) Tools::getValue('id_source_quote'));
        if (!Validate::isLoadedObject($source_quote)) {
            $this->errors[] = $this->l('Devis source introuvable');
            $this->display = 'add';
            return false;
        }

        $id_customer = (int) Tools::getValue('id_customer', $this->getDuplicateCustomerId($source_quote));
        $id_currency = (int) Tools::getValue('id_currency', $source_quote->id_currency);
        if (!$id_customer) {
            $id_customer = $this->getDuplicateCustomerId($source_quote);
        }
        if (!$id_currency) {
            $id_currency = (int) $source_quote->id_currency;
        }
        $date_exp = Tools::getValue('date_exp');
        $notes = Tools::getValue('notes');
        $customer = new Customer($id_customer);
        $currency = new Currency($id_currency);
        $draft_status_id = $this->getDraftStatusId();

        if (!Validate::isLoadedObject($customer) || !(int) $customer->active) {
            $this->errors[] = $this->l('Le client sélectionné est introuvable ou inactif');
            $this->display = 'add';
            return false;
        }

        if (!Validate::isLoadedObject($currency) || !(int) $currency->active || (int) $currency->deleted) {
            $this->errors[] = $this->l('La devise sélectionnée est introuvable ou inactive');
            $this->display = 'add';
            return false;
        }

        if (!$draft_status_id) {
            $this->errors[] = $this->l('Le statut Brouillon est introuvable');
            $this->display = 'add';
            return false;
        }

        $copy = new Quote();
        $copy->reference = Quote::generateReference();
        $copy->id_customer = $id_customer;
        $copy->id_address_delivery = (int) Address::getFirstCustomerAddressId($id_customer);
        $copy->id_address_invoice = $copy->id_address_delivery;
        $copy->id_currency = $id_currency;
        $copy->id_lang = (int) ($source_quote->id_lang ?: $this->context->language->id);
        $copy->id_quote_status = $draft_status_id;
        $copy->date_exp = !empty($date_exp) ? $date_exp : null;
        $copy->message = $source_quote->message;
        $copy->notes = $notes;
        $copy->valid = false;

        if (!$copy->add() || !QuoteProduct::duplicateQuoteProducts((int) $source_quote->id, (int) $copy->id)) {
            if ((int) $copy->id) {
                $copy->delete();
            }
            $this->errors[] = $this->l('La duplication du devis a échoué');
            $this->display = 'add';
            return false;
        }

        $copy->updateTotals();
        Tools::redirectAdmin(self::$currentIndex . '&viewquote&id_quote=' . (int) $copy->id . '&token=' . $this->token);
    }

    private function hasStockShortage(array $quote_products)
    {
        foreach ($quote_products as $product) {
            if (!empty($product['stock_shortage'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Use the source customer when active, otherwise preselect an active customer.
     */
    private function getDuplicateCustomerId(Quote $source_quote)
    {
        $source_customer = new Customer((int) $source_quote->id_customer);
        if (Validate::isLoadedObject($source_customer) && (int) $source_customer->active) {
            return (int) $source_customer->id;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT `id_customer` FROM `' . _DB_PREFIX_ . 'customer`'
            . ' WHERE `active` = 1 AND `deleted` = 0 ORDER BY `id_customer` ASC'
        );
    }

    /**
     * Inline update for quote main fields from unified view.
     */
    private function processUpdateQuoteInline()
    {
        if (!$this->requireQuotePermission('edit')) {
            return false;
        }

        $id_quote = (int) Tools::getValue('id_quote');
        $quote = new Quote($id_quote);

        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = $this->l('Devis introuvable');
            return false;
        }

        $id_quote_status = (int) Tools::getValue('id_quote_status');
        $id_carrier = (int) Tools::getValue('id_carrier');
        $date_exp = Tools::getValue('date_exp');
        $notes = Tools::getValue('notes');

        if ($id_quote_status <= 0) {
            $this->errors[] = $this->l('Statut invalide');
            return false;
        }

        $status = QuoteStatus::getStatusById($id_quote_status);
        if (!$status || !(int)$status['active']) {
            $this->errors[] = $this->l('Le statut sélectionné est indisponible');
            return false;
        }

        $carrier = $id_carrier > 0 ? new Carrier($id_carrier) : null;
        if ($id_carrier > 0 && (!Validate::isLoadedObject($carrier) || !$carrier->active || $carrier->deleted)) {
            $this->errors[] = $this->l('Le transporteur sélectionné est indisponible');
            return false;
        }

        if ($id_quote_status === QuoteStatus::getConvertedStatusId() && !(int) $quote->id_order) {
            $this->errors[] = $this->l('Utilisez l’action de conversion pour transformer ce devis en commande');
            return false;
        }

        $quote->id_quote_status = $id_quote_status;
        $quote->id_carrier = $id_carrier;
        $quote->date_exp = !empty($date_exp) ? $date_exp : null;
        $quote->notes = $notes;

        $quote->calculateTotals();
        if ($quote->update()) {
            $this->confirmations[] = $this->l('Devis mis à jour avec succès');
            return true;
        }

        $stock_error = StockReservation::getLastError();
        $this->errors[] = $stock_error ?: $this->l('Erreur lors de la mise à jour du devis');
        return false;
    }

    /**
     * Reassign an existing quote to a different customer (e.g. "put it under my company instead").
     */
    private function processChangeQuoteCustomer()
    {
        if (!$this->requireQuotePermission('edit')) {
            return false;
        }

        $id_quote = (int) Tools::getValue('id_quote');
        $quote = new Quote($id_quote);

        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = $this->l('Devis introuvable');
            return false;
        }

        if ((int) $quote->id_order > 0) {
            $this->errors[] = $this->l('Ce devis a déjà été transformé en commande, le client ne peut plus être modifié');
            return false;
        }

        $new_id_customer = (int) Tools::getValue('new_id_customer');
        if ($new_id_customer <= 0) {
            $this->errors[] = $this->l('Veuillez sélectionner un client');
            return false;
        }

        if (!$quote->changeCustomer($new_id_customer)) {
            $this->errors[] = $this->l('Impossible de changer le client de ce devis (client introuvable ou inactif)');
            return false;
        }

        $this->confirmations[] = $this->l('Le devis a été rattaché au nouveau client');
        return true;
    }

    private function getCustomersSortedByName()
    {
        $customers = Customer::getCustomers();
        usort($customers, function ($first, $second) {
            return strnatcasecmp($first['lastname'], $second['lastname'])
                ?: strnatcasecmp($first['firstname'], $second['firstname']);
        });

        return $customers;
    }

    /**
     * Build product list with default catalog prices for quote forms.
     */
    private function buildCatalogProductsForQuoteForm($id_quote = 0)
    {
        $id_lang = (int) $this->context->language->id;
        $products = Product::getProducts($id_lang, 0, 0, 'name', 'ASC', false, false);
        $catalog_products = [];

        foreach ($products as $product) {
            $id_product = (int) $product['id_product'];

            $combinations = [];
            $productObject = new Product($id_product, false, $id_lang);
            $combinationRows = $productObject->getAttributeCombinations($id_lang);
            $default_id_product_attribute = (int) Product::getDefaultAttribute($id_product);
            foreach ((array) $combinationRows as $combination) {
                $id_product_attribute = (int) $combination['id_product_attribute'];
                if (!isset($combinations[$id_product_attribute])) {
                    $combinations[$id_product_attribute] = [
                        'reference' => isset($combination['reference']) ? $combination['reference'] : '',
                        'attributes' => [],
                    ];
                }
                $designation = trim((string) ($combination['attribute_name'] ?? ''));
                if ($designation !== '' && !in_array($designation, $combinations[$id_product_attribute]['attributes'], true)) {
                    $combinations[$id_product_attribute]['attributes'][] = $designation;
                }
            }

            if (!$combinations) {
                $combinations[0] = [
                    'reference' => $product['reference'],
                    'attributes' => [],
                ];
            }

            $catalogProduct = [
                'id_product' => $id_product,
                'name' => $product['name'],
                'reference' => $product['reference'],
                'has_combinations' => count($combinations) > 1 || !isset($combinations[0]),
                'base_price_tax_excl' => 0,
                'base_price_tax_incl' => 0,
                'available_quantity' => null,
                'combinations' => [],
            ];

            foreach ($combinations as $id_product_attribute => $combination) {
                $price_tax_excl = (float) Product::getPriceStatic($id_product, false, (int) $id_product_attribute);
                $price_tax_incl = (float) Product::getPriceStatic($id_product, true, (int) $id_product_attribute);
                $attributeLabel = implode(' - ', $combination['attributes']);
                $reference = $combination['reference'] ?: $product['reference'];

                $catalogProduct['combinations'][] = [
                    'id_product_attribute' => (int) $id_product_attribute,
                        'display_name' => $product['name'] . ($attributeLabel ? ' - ' . $attributeLabel : '') . ' - ' . $reference,
                    'reference' => $reference,
                    'base_price_tax_excl' => $price_tax_excl,
                    'base_price_tax_incl' => $price_tax_incl,
                    'is_default' => (int) $id_product_attribute === $default_id_product_attribute,
                    'available_quantity' => $id_quote > 0
                        ? QuoteProduct::getAvailableQuantityForQuote($id_quote, $id_product, (int) $id_product_attribute)
                        : null,
                ];
                if ((int) $id_product_attribute === 0) {
                    $catalogProduct['base_price_tax_excl'] = $price_tax_excl;
                    $catalogProduct['base_price_tax_incl'] = $price_tax_incl;
                    $catalogProduct['available_quantity'] = $id_quote > 0
                        ? QuoteProduct::getAvailableQuantityForQuote($id_quote, $id_product, 0)
                        : null;
                }
            }

            $catalog_products[] = $catalogProduct;
        }

        return $catalog_products;
    }

    /**
     * Normalize decimal strings from form inputs.
     */
    private function normalizePriceInput($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    /**
     * Compute final prices from base price and adjustment fields from request.
     */
    private function resolveAdjustedPricesFromRequest($id_product = 0, $id_product_attribute = 0)
    {
        $price_tax_excl = $this->normalizePriceInput(Tools::getValue('price_tax_excl'));
        $price_tax_incl = $this->normalizePriceInput(Tools::getValue('price_tax_incl'));

        $base_price_tax_excl = $this->normalizePriceInput(Tools::getValue('base_price_tax_excl'));
        $base_price_tax_incl = $this->normalizePriceInput(Tools::getValue('base_price_tax_incl'));
        $adjustment_type = Tools::getValue('adjustment_type');
        $adjustment_value = $this->normalizePriceInput(Tools::getValue('adjustment_value'));

        if ((int) $id_product > 0) {
            $catalog_price_tax_excl = (float) Product::getPriceStatic(
                (int) $id_product,
                false,
                (int) $id_product_attribute
            );
            $catalog_price_tax_incl = (float) Product::getPriceStatic(
                (int) $id_product,
                true,
                (int) $id_product_attribute
            );
            if ($catalog_price_tax_excl > 0 && $catalog_price_tax_incl > 0) {
                $base_price_tax_excl = $catalog_price_tax_excl;
                $base_price_tax_incl = $catalog_price_tax_incl;
            }
        }

        if ($base_price_tax_excl > 0 && $base_price_tax_incl > 0) {
            if ($adjustment_type === 'percent') {
                $multiplier = 1 + ($adjustment_value / 100);
                $price_tax_excl = round($base_price_tax_excl * $multiplier, 6);
                $price_tax_incl = round($base_price_tax_incl * $multiplier, 6);
            } elseif ($adjustment_type === 'amount') {
                $price_tax_incl = max(0, $base_price_tax_incl + $adjustment_value);
                $tax_multiplier = $base_price_tax_incl / $base_price_tax_excl;
                $price_tax_excl = round($price_tax_incl / $tax_multiplier, 6);
            }
        }

        return [
            'price_tax_excl' => max(0, $price_tax_excl),
            'price_tax_incl' => max(0, $price_tax_incl),
        ];
    }

    /**
     * Emit a clean JSON response for AJAX calls.
     */
    private function sendJsonResponse(array $payload, $statusCode = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code((int) $statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload);
        exit;
    }
}