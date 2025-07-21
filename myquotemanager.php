<?php
if (!defined('_PS_VERSION_')) {
    exit;
}


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
    
    private function loadClasses()
    {
        $baseDir = _PS_MODULE_DIR_ . $this->name . '/';
        
        $classFiles = [
            // Classes métier
            'classes/Quote.php',
            'classes/QuoteProduct.php', 
            'classes/QuoteStatus.php',
            
            // Interfaces
            'hooks/HookInterface.php',
            
            // Hooks
            'hooks/DisplayHeaderHook.php',
            'hooks/DisplayCustomerAccountHook.php',
            'hooks/DisplayAdminOrderHook.php',
            'hooks/ActionValidateOrderHook.php',
        ];
        
        foreach ($classFiles as $file) {
            $filepath = $baseDir . $file;
            if (!file_exists($filepath)) {
                throw new Exception("Missing class file: {$file}");
            }
            require_once $filepath;
        }
    }

    public function install()
    {
        $this->loadClasses();
        // Initialiser l'installeur
        require_once _PS_MODULE_DIR_ . $this->name . '/install/installer.php';
        $installer = new MyQuoteManagerInstaller($this);
        
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
        $this->loadClasses();
        // Log de début de désinstallation
        PrestaShopLogger::addLog('QuoteManager: Début de la désinstallation', 1);

        // Initialiser l'installeur
        require_once _PS_MODULE_DIR_ . $this->name . '/install/installer.php';
        $installer = new MyQuoteManagerInstaller($this);
        
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
    public function hookDisplayHeader(array $params = []): string
    {
        $hook = new Ps_QuoteManagerDisplayHeaderHook($this);
        return $hook->render($params ?? []);
    }

    // Hook pour afficher le lien dans le compte client
    public function hookDisplayCustomerAccount(array $params = null): string
    {
        try {
            $hook = new Ps_QuoteManagerDisplayCustomerAccountHook($this);
            return $hook->render($params ?? []);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('QuoteManager Hook Error: ' . $e->getMessage(), 3);
            return '';
        }
    }

    // Hook pour afficher le devis dans la page de commande admin
     public function hookDisplayAdminOrder(array $params = null): string
    {
        try {
            $hook = new Ps_QuoteManagerDisplayAdminOrderHook($this);
            return $hook->render($params ?? []);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('QuoteManager Hook Error: ' . $e->getMessage(), 3);
            return '';
        }
    }

    // Hook pour traiter la validation de commande
    public function hookActionValidateOrder(?array $params = null): bool
    {
        try {
            $hook = new Ps_QuoteManagerActionValidateOrderHook($this);
            return $hook->execute($params ?? []);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('QuoteManager Action Error: ' . $e->getMessage(), 3);
            return false;
        }
    }
}
