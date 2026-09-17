<?php
/**
 * Admin Tabs Installation Management
 */
class QuoteSystemTabsInstaller
{
    private $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }
    
    /**
     * Install all admin tabs
     */
    public function install()
    {
        return $this->installQuoteTab() 
            && $this->installStatusTab()
            && $this->installSettingsTab();
    }
    
    /**
     * Uninstall all admin tabs
     */
    public function uninstall()
    {
        return $this->uninstallTab('AdminQuote')
            && $this->uninstallTab('AdminQuoteStatus')
            && $this->uninstallTab('AdminQuoteSettings');
    }
    
    /**
     * Install main quote tab
     */
    private function installQuoteTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminQuote';
        $tab->module = $this->module->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $tab->icon = 'description';
        $tab->position = 1;
        
        // Multilingue
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Liste des devis';
        }
        
        return $tab->add();
    }
    
    /**
     * Install quote status tab
     */
    private function installStatusTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminQuoteStatus';
        $tab->module = $this->module->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $tab->icon = 'flag';
        $tab->position = 2;
        
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Statuts des devis';
        }
        
        return $tab->add();
    }
    
    /**
     * Install settings tab
     */
    private function installSettingsTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminQuoteSettings';
        $tab->module = $this->module->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $tab->icon = 'cogs';
        $tab->position = 3;
        
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Configuration';
        }
        
        return $tab->add();
    }
    
    /**
     * Uninstall a specific tab
     */
    private function uninstallTab($class_name)
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }
}
