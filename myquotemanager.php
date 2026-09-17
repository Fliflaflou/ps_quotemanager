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
        $this->version = '0.2.4';
        $this->author = 'PageFlottante';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        if (!$this->loadClasses()) {
            PrestaShopLogger::addLog('MyQuoteManager: Classes non chargées (uninstall en cours ?)', 1);
        }
        $this->displayName = $this->l('Devis Manager');
        $this->description = $this->l('Module de création et de traitement de devis pour Prestashop');
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);
    }
    
    private function loadClasses(): bool
    {
        // NE PAS CHARGER DURANT LA DÉSINSTALLATION
        if ($this->isUninstalling()) {
            return false;
        }

        $baseDir = _PS_MODULE_DIR_ . $this->name . '/';
        
        $classFiles = [
            // Classes métier
            'classes/Quote.php',
            'classes/QuoteProduct.php',
            'classes/QuoteStatus.php',
            'classes/StockReservation.php',

            //Services
            // 'src/Service/OrderToQuoteConverter.php',
            
            // Hooks
            'hooks/displayHeaderHook.php',
            'hooks/displayCustomerAccountHook.php',
            'hooks/displayAdminOrderHook.php',
            'hooks/actionValidateOrderHook.php',
        ];
        
        try {
            foreach ($classFiles as $file) {
                $filepath = $baseDir . $file;
                if (!file_exists($filepath)) {
                    PrestaShopLogger::addLog("MyQuoteManager: Missing class file: {$file}", 2);
                    return false; // ← RETURN FALSE au lieu d'Exception
                }
                require_once $filepath;
            }
            return true;
        } catch (Exception $e) {
            PrestaShopLogger::addLog("MyQuoteManager: Error loading classes: " . $e->getMessage(), 3);
            return false;
        }
    }

    /**
     * Détecte si on est en cours de désinstallation
     */
    private function isUninstalling(): bool
    {
        return (
            defined('PS_INSTALLATION_IN_PROGRESS') ||
            isset($_GET['uninstall']) ||
            isset($_POST['uninstall']) ||
            (isset($_POST['action']) && $_POST['action'] === 'uninstall') ||
            (isset($_GET['configure']) && isset($_GET['uninstall']))
        );
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
        // DEBUG: Vérifier que le hook est appelé
        error_log("=== HOOK DisplayHeader APPELÉ ===");
        
        try {
            $hook = new MyQuoteManagerDisplayHeaderHook($this);
            $html = $hook->render($params ?? []);
            
            error_log("HTML généré: " . $html);
            return $html;
            
        } catch (Exception $e) {
            error_log("ERREUR hook DisplayHeader: " . $e->getMessage());
            return "<!-- ERREUR HOOK -->";
        }
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
        if (!class_exists('Ps_QuoteManagerActionValidateOrderHook')) {
            return true;
        }

        try {
            $hook = new Ps_QuoteManagerActionValidateOrderHook($this);
            return $hook->execute($params ?? []);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('QuoteManager Action Error: ' . $e->getMessage(), 3);
            return false;
        }
    }
}
