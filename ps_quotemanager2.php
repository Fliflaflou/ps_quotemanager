<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_QuoteManager extends Module
{
    public function __construct()
    {
        $this->name = 'ps_quotemanager';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
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

    public function uninstall()
    {
        // Exécuter les requêtes SQL de désinstallation
        $this->executeSqlFile('uninstall.sql');

        // Désenregistrer les hooks
        foreach ($this->getRegisteredHooks() as $hook) {
            $this->unregisterHook($hook);
        }

        // Supprimer les configurations
        $this->deleteConfigurations();

        // Toujours appeler la méthode parent::uninstall() à la fin
        return parent::uninstall();
    }

    protected function executeSqlFile($filename)
    {
        $sqlFile = dirname(__FILE__) . '/sql/' . $filename;

        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
            $queries = preg_split("/;\s*[\r\n]+/", $sql);

            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    Db::getInstance()->execute($query);
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

