<?php
/**
 * Quote Manager Installation and Uninstallation Handler
 */
class MyQuoteManagerInstaller
{
    private $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }
    
    // ========================================
    // INSTALLATION METHODS
    // ========================================
    
    /**
     * Vérification et définition des permissions
     */
    public function checkPermissions()
    {
        $modulePath = _PS_MODULE_DIR_ . $this->module->name . '/';
        return $this->setPermissions($modulePath);
    }
    
    /**
     * Installation des onglets admin
     */
    public function installTabs()
    {
        require_once dirname(__FILE__) . '/tabs.php';
        $tabs_installer = new QuoteSystemTabsInstaller($this->module);
        return $tabs_installer->install();
    }
    
    /**
     * Installation des données par défaut
     */
    public function installDefaultData()
    {
        // Installation des statuts par défaut (délégué à la classe métier)
        if (!QuoteStatus::installDefaultStatuses()) {
            return false;
        }

        return true;
    }
    
    /**
     * Installation des configurations par défaut
     */
    public function installDefaultConfiguration()
    {
        $configs = [
            'PS_QUOTEMANAGER_ENABLED' => 1,
            'PS_QUOTEMANAGER_AUTO_EXPIRE' => 30,
            'PS_QUOTEMANAGER_EMAIL_TEMPLATE' => 'quote_template',
            'PS_QUOTEMANAGER_TAX_CALCULATION' => 1,
            'PS_QUOTEMANAGER_REFERENCE_PREFIX' => 'QUO',
            'PS_QUOTEMANAGER_REFERENCE_DATE_ORDER' => 'Ymd',
            'PS_QUOTEMANAGER_DEFAULT_VALIDITY' => 30,
            'PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS' => 1,
            'PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED' => 0,
            'PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS' => 3,
            'PS_QUOTEMANAGER_EXPIRATION_CRON_TOKEN' => Tools::passwdGen(32),
            'PS_QUOTEMANAGER_PAYMENT_INFO' => "Paiement par chèque à l'ordre du GAEC du Merlanson\nPaiement par virement IBAN : FR76 1680 7005 8737 2711 6521 832 – Id. banque : CCBPFRPPGRE – banque populaire AURA Agri Drôme Ardèche\nGaec Agréé du Merlanson - Siret 9188872771 00012",
            'PS_QUOTEMANAGER_PDF_PRICE_DISPLAY' => 'tax_incl',
            'QUOTE_REFERENCE_PREFIX' => 'QUO',
            'QUOTE_REFERENCE_DATE_ORDER' => 'Ymd',
            'QUOTE_DEFAULT_VALIDITY' => 30,
            'QUOTE_EMAIL_NOTIFICATIONS' => 1,
        ];
        
        foreach ($configs as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Exécution d'un fichier SQL
     */
    public function executeSqlFile($filename)
    {
        $sql_file = dirname(__FILE__) . '/sql/' . $filename;
        
        if (!file_exists($sql_file)) {
            PrestaShopLogger::addLog("SQL file not found: {$filename}", 3);
            return false;
        }
        
        $sql_content = file_get_contents($sql_file);
        if (!$sql_content) {
            return false;
        }

        // Standardize placeholders used by install/uninstall SQL files.
        $sql_content = str_replace(
            ['PREFIX_', '{PREFIX}', 'ENGINE_TYPE'],
            [_DB_PREFIX_, _DB_PREFIX_, _MYSQL_ENGINE_],
            $sql_content
        );
        
        $sql_queries = preg_split("/;\s*[\r\n]+/", $sql_content);
        
        foreach ($sql_queries as $query) {
            $query = trim($query);
            if (empty($query)) {
                continue;
            }
            
            if (!Db::getInstance()->execute($query)) {
                PrestaShopLogger::addLog("SQL Error: " . Db::getInstance()->getMsgError() . " - Query: " . $query, 3);
                return false;
            }
        }
        
        return true;
    }
    
    // ========================================
    // UNINSTALLATION METHODS
    // ========================================
    
    /**
     * Désinstallation des onglets admin
     */
    public function uninstallTabs()
    {
        require_once dirname(__FILE__) . '/tabs.php';
        $tabs_installer = new QuoteSystemTabsInstaller($this->module);
        return $tabs_installer->uninstall();
    }
    
    /**
     * Suppression des données (optionnel)
     */
    public function uninstallData($keepData = false)
    {
        PrestaShopLogger::addLog('QuoteManager: uninstallData, keepData=' . ($keepData ? 'true' : 'false'), 1);
        
        if ($keepData) {
            PrestaShopLogger::addLog('QuoteManager: Données utilisateur conservées', 1);
            return true; // ✅ CORRIGÉ
        }
        
        // Supprimer toutes les données via SQL
        if (!$this->executeSqlFile('uninstall.sql')) {
            PrestaShopLogger::addLog('QuoteManager: Erreur exécution uninstall.sql', 3);
            return false;
        }
        
        PrestaShopLogger::addLog('QuoteManager: Toutes les données supprimées', 1);
        return true;
    }
    
    /**
     * Suppression des configurations
     */
    public function uninstallConfiguration($keepConfig = false)
    {
        if ($keepConfig) {
            return true; // Garder la configuration
        }
        
        $configs = [
            'PS_QUOTEMANAGER_ENABLED',
            'PS_QUOTEMANAGER_AUTO_EXPIRE',
            'PS_QUOTEMANAGER_EMAIL_TEMPLATE',
            'PS_QUOTEMANAGER_TAX_CALCULATION',
            'PS_QUOTEMANAGER_REFERENCE_PREFIX',
            'PS_QUOTEMANAGER_REFERENCE_DATE_ORDER',
            'PS_QUOTEMANAGER_DEFAULT_VALIDITY',
            'PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS',
            'PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED',
            'PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS',
            'PS_QUOTEMANAGER_EXPIRATION_CRON_TOKEN',
            'PS_QUOTEMANAGER_PAYMENT_INFO',
            'PS_QUOTEMANAGER_PDF_PRICE_DISPLAY',
            'QUOTE_REFERENCE_PREFIX',
            'QUOTE_REFERENCE_DATE_ORDER',
            'QUOTE_DEFAULT_VALIDITY',
            'QUOTE_EMAIL_NOTIFICATIONS',
        ];
        
        foreach ($configs as $key) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }
        
        return true;
    }
    
    // ========================================
    // HELPER METHODS
    // ========================================
    
    /**
     * Définition récursive des permissions
     */
    private function setPermissions($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        if (is_dir($path)) {
            @chmod($path, 0775);
            
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                if (!$this->setPermissions($path . '/' . $item)) {
                    return false;
                }
            }
        } else {
            @chmod($path, 0664);
        }

        return true; // ✅ CORRIGÉ
    }
    
    /**
     * Vérification de l'intégrité des tables
     */
    public function checkDatabaseIntegrity()
    {
        $tables = [
            _DB_PREFIX_ . 'quote',
            _DB_PREFIX_ . 'quote_product',
            _DB_PREFIX_ . 'quote_status',
            _DB_PREFIX_ . 'quote_status_lang',
            _DB_PREFIX_ . 'stock_reservation',
        ];
        
        foreach ($tables as $table) {
            $sql = 'SHOW TABLES LIKE "' . $table . '"';
            if (!Db::getInstance()->getValue($sql)) {
                return false;
            }
        }
        
        return true;
    }
}
