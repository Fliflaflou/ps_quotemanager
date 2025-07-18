<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/Quote.php';
require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/QuoteProduct.php';
require_once _PS_MODULE_DIR_ . 'myquotemanager/classes/QuoteStatus.php';

class MyQuoteManager extends Module
{
    public function __construct()
    {
        $this->name = 'myquotemanager';
        $this->tab = 'front_office_features';
        $this->version = '0.0.4';
        $this->author = 'PageFlottante';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Devis Manager');
        $this->description = $this->l('Module de création et de traitement de devis pour Prestashop');
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);
    }

    // public function install()
    // {
    //     // Vérifier les permissions avant l'installation
    //     require_once _PS_MODULE_DIR_ . $this->name . '/install/install.php';
    //     Ps_QuoteManagerInstaller::checkPermissions();

    //     if (!parent::install()) {
    //         $this->_errors[] = $this->l('Failed to install parent module');
    //         return false;
    //     }

    //     // Installation de la base de données
    //     if (!$this->installDb()) {
    //         $this->_errors[] = $this->l('Failed to install database');
    //         return false;
    //     }

    //     // Installation des données par défaut
    //     if (!$this->installDefaultData()) {
    //         $this->_errors[] = $this->l('Failed to install default data');
    //         return false;
    //     }

    //     // Installation des onglets admin

    //     if (!$this->installTab()) {
    //         $this->_errors[] = $this->l('Failed to install admin tabs');
    //         return false;
    //     }


    //     // Enregistrement des hooks
    //     $hooks = $this->getRegisteredHooks();
    //     foreach ($hooks as $hook) {
    //         if (!$this->registerHook($hook)) {
    //             $this->_errors[] = $this->l('Failed to register hook: ') . $hook;
    //             return false;
    //         }
    //     }

    //     // Configuration par défaut
    //     Configuration::updateValue('PS_QUOTEMANAGER_ENABLED', 1);

    //     return true;
    // }

    public function install()
    {
        // Initialiser l'installeur
        require_once _PS_MODULE_DIR_ . $this->name . '/install/installer.php';
        $installer = new Ps_QuoteManagerInstaller($this);
        
        // Vérifier les permissions
        if (!$installer->checkPermissions()) {
            $this->_errors[] = $this->l('Permission check failed');
            return false;
        }

        // Installation parent
        if (!parent::install()) {
            $this->_errors[] = $this->l('Failed to install parent module');
            return false;
        }

        // Installation de la base de données
        if (!$installer->executeSqlFile('install.sql')) {
            $this->_errors[] = $this->l('Failed to install database');
            return false;
        }

        // Installation des données par défaut
        if (!$installer->installDefaultData()) {
            $this->_errors[] = $this->l('Failed to install default data');
            return false;
        }

        // Installation des onglets admin
        if (!$installer->installTabs()) {
            $this->_errors[] = $this->l('Failed to install admin tabs');
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
        if (!$installer->installDefaultConfiguration()) {
            $this->_errors[] = $this->l('Failed to install default configuration');
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        PrestaShopLogger::addLog('QuoteManager: Début de la désinstallation', 1);

        // Initialiser l'installeur
        require_once _PS_MODULE_DIR_ . $this->name . '/install/installer.php';
        $installer = new Ps_QuoteManagerInstaller($this);
        
        // Vérifier si l'utilisateur veut conserver les données
        $keepData = (bool)Configuration::get('PS_QUOTEMANAGER_KEEP_DATA');
        $keepConfig = (bool)Configuration::get('PS_QUOTEMANAGER_KEEP_CONFIG');

        // Désinstallation des onglets admin
        if (!$installer->uninstallTabs()) {
            $this->_errors[] = $this->l('Failed to uninstall admin tabs');
            return false;
        }
        
        // Désinstallation des données
        if (!$installer->uninstallData($keepData)) {
            $this->_errors[] = $this->l('Failed to uninstall data');
            return false;
        }
        
        // Désinstallation de la configuration
        if (!$installer->uninstallConfiguration($keepConfig)) {
            $this->_errors[] = $this->l('Failed to uninstall configuration');
            return false;
        }
        
        // Désinstallation parent
        if (!parent::uninstall()) {
            $this->_errors[] = $this->l('Failed to uninstall parent module');
            return false;
        }

        PrestaShopLogger::addLog('QuoteManager: Désinstallation terminée', 1);
        return true;
    }




    // public function uninstall()
    // {
    //     // Appeler le nettoyage avant la désinstallation
    //     require_once _PS_MODULE_DIR_ . $this->name . '/install/uninstall.php';
    //     Ps_QuoteManagerUninstaller::cleanUp();

    //     // Désenregistrer les hooks
    //     foreach ($this->getRegisteredHooks() as $hook) {
    //         $this->unregisterHook($hook);
    //     }

    //     // Supprimer les configurations
    //     $this->deleteConfigurations();

    //     // Supprimer les tables de la base de données
    //     if (!$this->uninstallDb()) {
    //         $this->_errors[] = $this->l('Failed to uninstall database');
    //         return false;
    //     }

    //     return parent::uninstall();
    // }
    

    // protected function installDb()
    // {
    //     $sqlFiles = [
    //         dirname(__FILE__) . '/sql/install.sql'
    //     ];

    //     foreach ($sqlFiles as $sqlFile) {
    //         if (file_exists($sqlFile)) {
    //             $sql = file_get_contents($sqlFile);
    //             $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
    //             $sql = str_replace('ENGINE_TYPE', _MYSQL_ENGINE_, $sql);
    //             $queries = preg_split("/;\s*[\r\n]+/", $sql);
                
    //             foreach ($queries as $query) {
    //                 $query = trim($query);
    //                 if (!empty($query)) {
    //                     if (!Db::getInstance()->execute($query)) {
    //                         return false;
    //                     }
    //                 }
    //             }
    //         }
    //     }
        
    //     return true;
    // }
    
    // protected function uninstallDb()
    // {
    //     $sqlFile = dirname(__FILE__) . '/sql/uninstall.sql';
        
    //     if (file_exists($sqlFile)) {
    //         $sql = file_get_contents($sqlFile);
    //         $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
    //         $queries = preg_split("/;\s*[\r\n]+/", $sql);
            
    //         foreach ($queries as $query) {
    //             $query = trim($query);
    //             if (!empty($query)) {
    //                 if (!Db::getInstance()->execute($query)) {
    //                     return false;
    //                 }
    //             }
    //         }
    //     }
        
    //     return true;
    // }
    
    // protected function deleteConfigurations()
    // {
    //     $configurations = [
    //         'PS_QUOTEMANAGER_ENABLED',
    //         'PS_QUOTEMANAGER_VALIDITY_DAYS',
    //         'PS_QUOTEMANAGER_AUTO_APPROVE',
    //         'PS_QUOTEMANAGER_EMAIL_ADMIN',
    //         'PS_QUOTEMANAGER_EMAIL_CUSTOMER',
    //     ];
        
    //     foreach ($configurations as $config) {
    //         Configuration::deleteByName($config);
    //     }
    // }
    


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
