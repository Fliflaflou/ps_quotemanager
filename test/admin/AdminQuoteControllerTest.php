<?php
require_once 'config/config.inc.php';
require_once 'controllers/admin/AdminQuoteController.php';

class AdminQuoteControllerTest
{
    public function testControllerInstantiation()
    {
        echo "1. Test d'instanciation du contrôleur...\n";
        
        try {
            $controller = new AdminQuoteController();
            echo "   ✅ Contrôleur instancié avec succès\n";
            
            // Vérifier les propriétés de base
            echo "   - Table: " . $controller->table . "\n";
            echo "   - Classe: " . $controller->className . "\n";
            echo "   - Bootstrap: " . ($controller->bootstrap ? 'Oui' : 'Non') . "\n";
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
        }
    }
    
    public function testFieldsList()
    {
        echo "\n2. Test des champs de liste...\n";
        
        try {
            $controller = new AdminQuoteController();
            $fields = $controller->fields_list;
            
            echo "   ✅ Champs configurés: " . count($fields) . "\n";
            foreach ($fields as $field => $config) {
                echo "   - {$field}: {$config['title']}\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
        }
    }
    
    public function testGetList()
    {
        echo "\n3. Test de récupération de la liste...\n";
        
        try {
            $controller = new AdminQuoteController();
            
            // Simuler un appel getList
            $id_lang = 1;
            $list = $controller->getList($id_lang, 'id_quote', 'DESC', 0, 10);
            
            echo "   ✅ Liste récupérée\n";
            echo "   - Nombre d'éléments: " . count($list) . "\n";
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
        }
    }
}

// Exécution des tests
$test = new AdminQuoteControllerTest();
$test->testControllerInstantiation();
$test->testFieldsList();
$test->testGetList();
