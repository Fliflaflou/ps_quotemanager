<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ps_quotemanager/classes/Quote.php';
require_once _PS_MODULE_DIR_ . 'ps_quotemanager/classes/QuoteProduct.php';
require_once _PS_MODULE_DIR_ . 'ps_quotemanager/classes/QuoteStatus.php';

class Ps_QuoteManager extends Module
{
    public function __construct()
    {
        $this->name = 'ps_quotemanager';
        $this->tab = 'front_office_features';
        $this->version = '0.0.1';
        $this->author = 'PageFlottante';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Devis Manager');
        $this->description = $this->l('Module de création et de traitement de devis pour Prestashop');
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        // Vérifier les permissions avant l'installation
        require_once _PS_MODULE_DIR_ . $this->name . '/install/install.php';
        Ps_QuoteManagerInstaller::checkPermissions();

        if (!parent::install()) {
            $this->_errors[] = $this->l('Failed to install parent module');
            return false;
        }

        // Installation de la base de données
        if (!$this->installDb()) {
            $this->_errors[] = $this->l('Failed to install database');
            return false;
        }

        // Installation des données par défaut
        if (!$this->installDefaultData()) {
            $this->_errors[] = $this->l('Failed to install default data');
            return false;
        }


        // Enregistrement des hooks
        $hooks = $this->getRegisteredHooks();
        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $this->_errors[] = $this->l('Failed to register hook: ') . $hook;
                return false;
            }
        }

        // Configuration par défaut
        Configuration::updateValue('PS_QUOTEMANAGER_ENABLED', 1);

        return true;
    }

    public function uninstall()
    {
        // Appeler le nettoyage avant la désinstallation
        require_once _PS_MODULE_DIR_ . $this->name . '/install/uninstall.php';
        Ps_QuoteManagerUninstaller::cleanUp();

        // Désenregistrer les hooks
        foreach ($this->getRegisteredHooks() as $hook) {
            $this->unregisterHook($hook);
        }

        // Supprimer les configurations
        $this->deleteConfigurations();

        // Supprimer les tables de la base de données
        if (!$this->uninstallDb()) {
            $this->_errors[] = $this->l('Failed to uninstall database');
            return false;
        }

        return parent::uninstall();
    }
    
    protected function installDb()
    {
        $sqlFiles = [
            dirname(__FILE__) . '/sql/install.sql'
        ];

        foreach ($sqlFiles as $sqlFile) {
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
                $sql = str_replace('ENGINE_TYPE', _MYSQL_ENGINE_, $sql);
                $queries = preg_split("/;\s*[\r\n]+/", $sql);
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        if (!Db::getInstance()->execute($query)) {
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    protected function uninstallDb()
    {
        $sqlFile = dirname(__FILE__) . '/sql/uninstall.sql';
        
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
            $queries = preg_split("/;\s*[\r\n]+/", $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if (!Db::getInstance()->execute($query)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    protected function deleteConfigurations()
    {
        $configurations = [
            'PS_QUOTEMANAGER_ENABLED',
            'PS_QUOTEMANAGER_VALIDITY_DAYS',
            'PS_QUOTEMANAGER_AUTO_APPROVE',
            'PS_QUOTEMANAGER_EMAIL_ADMIN',
            'PS_QUOTEMANAGER_EMAIL_CUSTOMER',
        ];
        
        foreach ($configurations as $config) {
            Configuration::deleteByName($config);
        }
    }
    
    protected function getRegisteredHooks()
    {
        return [
            'displayHeader',
            'displayCustomerAccount',
            'displayAdminOrder',
            'actionValidateOrder',
        ];
    }
    
    // Hook pour ajouter des assets dans le header
    public function hookDisplayHeader()
    {
        if (file_exists(_PS_MODULE_DIR_ . $this->name . '/hooks/displayHeader.php')) {
            include_once _PS_MODULE_DIR_ . $this->name . '/hooks/displayHeader.php';
        }
    }
}
