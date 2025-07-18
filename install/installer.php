<?php
/**
 * Quote Manager Installation and Uninstallation Handler
 */
class Ps_QuoteManagerInstaller
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
            'PS_QUOTEMANAGER_AUTO_EXPIRE' => 30, // jours
            'PS_QUOTEMANAGER_EMAIL_TEMPLATE' => 'quote_template',
            'PS_QUOTEMANAGER_TAX_CALCULATION' => 1,
        ];
        
        foreach ($configs as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
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
            // Supprimer seulement les données par défaut
            if (!QuoteStatus::uninstallDefaultStatuses()) {
                PrestaShopLogger::addLog('QuoteManager: Erreur suppression statuts par défaut', 3);
                return false;
            }
            PrestaShopLogger::addLog('QuoteManager: Données utilisateur conservées', 1);
            return true;
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
     * Suppression de la base de données
     */

    public function executeSqlFile($filename)
    {
        $sql_file = dirname(__FILE__) . '/sql/' . $filename;
        
        if (!file_exists($sql_file)) {
            return false;
        }
        
        $sql_content = file_get_contents($sql_file);
        if ($sql_content === false) {
            return false;
        }
        
        // Remplacer le préfixe
        $sql_content = str_replace('{PREFIX}', _DB_PREFIX_, $sql_content);
        
        // Séparer les requêtes
        $queries = preg_split('/;\s*$/m', $sql_content);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!Db::getInstance()->execute($query)) {
                    return false;
                }
            }
        }
        
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

        return true;
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
