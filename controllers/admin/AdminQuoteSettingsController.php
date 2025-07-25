<?php
/**
 * Admin controller pour la configuration du module devis
 */

class AdminQuoteSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        
        $this->meta_title = $this->l('Configuration des devis');
    }
    
    public function initContent()
    {
        $this->content = $this->renderConfigurationForm();
        parent::initContent();
    }
    
    /**
     * Formulaire de configuration
     */
    public function renderConfigurationForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration générale'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Préfixe des références'),
                        'name' => 'QUOTE_REFERENCE_PREFIX',
                        'size' => 20,
                        'desc' => $this->l('Préfixe utilisé pour les références de devis (ex: QUO)')
                    ],
                    [
                        'type' => 'text', 
                        'label' => $this->l('Durée de validité par défaut'),
                        'name' => 'QUOTE_DEFAULT_VALIDITY',
                        'size' => 10,
                        'suffix' => $this->l('jours'),
                        'desc' => $this->l('Nombre de jours de validité par défaut pour un nouveau devis')
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Conversion auto en commande'),
                        'name' => 'QUOTE_AUTO_CONVERT',
                        'is_bool' => true,
                        'desc' => $this->l('Convertir automatiquement les devis approuvés en commandes'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activé')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé')
                            ]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Notifications email'),
                        'name' => 'QUOTE_EMAIL_NOTIFICATIONS',
                        'is_bool' => true,
                        'desc' => $this->l('Envoyer des notifications email lors des changements de statut'),
                        'values' => [
                            [
                                'id' => 'email_on',
                                'value' => 1,
                                'label' => $this->l('Activé')
                            ],
                            [
                                'id' => 'email_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé')
                            ]
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer les paramètres')
                ]
            ]
        ];
        
        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminQuoteSettings');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;
        $helper->default_form_language = $this->context->language->id;
        
        // Valeurs actuelles
        $helper->fields_value['QUOTE_REFERENCE_PREFIX'] = Configuration::get('QUOTE_REFERENCE_PREFIX', 'QUO');
        $helper->fields_value['QUOTE_DEFAULT_VALIDITY'] = Configuration::get('QUOTE_DEFAULT_VALIDITY', 30);
        $helper->fields_value['QUOTE_AUTO_CONVERT'] = Configuration::get('QUOTE_AUTO_CONVERT', 0);
        $helper->fields_value['QUOTE_EMAIL_NOTIFICATIONS'] = Configuration::get('QUOTE_EMAIL_NOTIFICATIONS', 1);
        
        // Traitement du formulaire
        if (Tools::isSubmit('submitConfiguration')) {
            Configuration::updateValue('QUOTE_REFERENCE_PREFIX', Tools::getValue('QUOTE_REFERENCE_PREFIX'));
            Configuration::updateValue('QUOTE_DEFAULT_VALIDITY', (int)Tools::getValue('QUOTE_DEFAULT_VALIDITY'));
            Configuration::updateValue('QUOTE_AUTO_CONVERT', (int)Tools::getValue('QUOTE_AUTO_CONVERT'));
            Configuration::updateValue('QUOTE_EMAIL_NOTIFICATIONS', (int)Tools::getValue('QUOTE_EMAIL_NOTIFICATIONS'));
            
            $this->confirmations[] = $this->l('Paramètres sauvegardés avec succès !');
        }
        
        return $helper->generateForm([$fields_form]);
    }
}
