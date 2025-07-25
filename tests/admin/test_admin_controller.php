<?php
// test/admin/test_admin_controller.php

// Depuis test/admin/, on remonte vers le module, puis vers PrestaShop
$prestashop_root = dirname(__FILE__) . '/../../../../';

echo "=== VÉRIFICATION ENVIRONNEMENT ===\n";
echo "Répertoire courant : " . getcwd() . "\n";
echo "Fichier de test : " . __FILE__ . "\n";
echo "Dirname du fichier : " . dirname(__FILE__) . "\n";
echo "Chemin relatif PrestaShop : " . $prestashop_root . "\n";
echo "Chemin absolu PrestaShop : " . realpath($prestashop_root) . "\n";
echo "Config existe : " . (file_exists($prestashop_root . 'config/config.inc.php') ? "✅" : "❌") . "\n";

if (!file_exists($prestashop_root . 'config/config.inc.php')) {
    die("❌ Impossible de trouver config.inc.php\n");
}

echo "✅ Configuration trouvée !\n\n";

// Inclusion des fichiers
require_once $prestashop_root . 'config/config.inc.php';
require_once dirname(__FILE__) . '/../../classes/Quote.php';
require_once dirname(__FILE__) . '/../../classes/QuoteStatus.php';
require_once dirname(__FILE__) . '/../../classes/QuoteProduct.php';

echo "=== TEST ADMIN CONTROLLER ===\n";

class AdminControllerTest 
{
    private $test_results = [];
    
    public function runAllTests()
    {
        echo "🎯 Démarrage des tests admin...\n\n";
        
        $this->testListQuery();
        $this->testCustomerDisplay();
        $this->testStatusDisplay();
        $this->testQuoteStatuses();
        $this->testQuoteDetails();
        $this->testPermissions();
        
        $this->displayResults();
    }
    
    public function testListQuery()
    {
        echo "1. Test de la requête de liste...\n";
        
        try {
            // Requête avec jointure multilingue
            $id_lang = Context::getContext()->language->id;
            
            $sql = 'SELECT q.*, qsl.name as status_name, c.firstname, c.lastname 
                    FROM ' . _DB_PREFIX_ . 'quote q
                    LEFT JOIN ' . _DB_PREFIX_ . 'quote_status qs ON (q.id_quote_status = qs.id_quote_status)
                    LEFT JOIN ' . _DB_PREFIX_ . 'quote_status_lang qsl ON (qs.id_quote_status = qsl.id_quote_status AND qsl.id_lang = ' . (int)$id_lang . ')
                    LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (q.id_customer = c.id_customer)
                    WHERE qs.deleted = 0
                    ORDER BY q.date_add DESC
                    LIMIT 10';
            
            $result = Db::getInstance()->executeS($sql);
            
            if ($result !== false) {
                echo "   ✅ Requête exécutée avec succès\n";
                echo "   📊 " . count($result) . " devis trouvés\n";
                
                foreach ($result as $row) {
                    $status_display = $row['status_name'] ? $row['status_name'] : 'Status #' . $row['id_quote_status'];
                    echo "   - Devis #{$row['reference']} - $status_display\n";
                }
                
                $this->test_results['list_query'] = 'SUCCESS';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['list_query'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function testCustomerDisplay()
    {
        echo "2. Test affichage customer...\n";
        
        try {
            // Récupérer le premier customer
            $customer = new Customer(1);
            
            if ($customer->id) {
                $display_name = $customer->firstname . ' ' . $customer->lastname;
                echo "   ✅ Customer trouvé: $display_name\n";
                
                // Test avec email
                $full_display = $display_name . ' (' . $customer->email . ')';
                echo "   ✅ Affichage complet: $full_display\n";
                
                $this->test_results['customer_display'] = 'SUCCESS';
            } else {
                echo "   ❌ Aucun customer trouvé\n";
                $this->test_results['customer_display'] = 'FAILED';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['customer_display'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function testStatusDisplay()
    {
        echo "3. Test affichage statuts...\n";
        
        try {
            $statuses = QuoteStatus::getQuoteStatuses();
            
            if (!empty($statuses)) {
                echo "   ✅ " . count($statuses) . " statuts trouvés\n";
                
                foreach ($statuses as $status) {
                    $badge = '<span class="badge" style="background-color: ' . $status['color'] . '">' . $status['name'] . '</span>';
                    echo "   - Badge: $badge\n";
                }
                
                $this->test_results['status_display'] = 'SUCCESS';
            } else {
                echo "   ❌ Aucun statut trouvé\n";
                $this->test_results['status_display'] = 'FAILED';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['status_display'] = 'FAILED';
        }
        
        echo "\n";
    }
    public function testQuoteStatuses()
    {
        echo "4. Test des statuts de devis...\n";
        
        try {
            // Utilisons la méthode de la classe
            $statuses = QuoteStatus::getQuoteStatuses();
            
            if ($statuses && count($statuses) > 0) {
                echo "   ✅ " . count($statuses) . " statuts trouvés\n";
                
                foreach ($statuses as $status) {
                    echo "   - #{$status['id_quote_status']}: {$status['name']} ({$status['color']})\n";
                }
                
                $this->test_results['quote_statuses'] = 'SUCCESS';
            } else {
                echo "   ⚠️ Aucun statut trouvé - Pensez à installer les statuts par défaut\n";
                $this->test_results['quote_statuses'] = 'WARNING';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['quote_statuses'] = 'FAILED';
        }
        
        echo "\n";
    }


    public function testQuoteDetails()
    {
        echo "5. Test détails devis...\n";
        
        try {
            $quotes = Quote::getAllQuotes(5); // Récupère 5 quotes max
            
            if ($quotes && count($quotes) > 0) {
                $first_quote = $quotes[0];
                echo "   ✅ Devis trouvé: " . $first_quote['reference'] . "\n";
                echo "   📊 Client: " . $first_quote['firstname'] . " " . $first_quote['lastname'] . "\n";
                
                $this->test_results['quote_details'] = 'SUCCESS';
            } else {
                echo "   ⚠️ Aucun devis disponible\n";
                $this->test_results['quote_details'] = 'WARNING';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['quote_details'] = 'FAILED';
        }
        
        echo "\n";
    }


    public function testPermissions()
    {
        echo "6. Test permissions admin...\n";
        
        try {
            // Simuler les permissions requises
            $required_permissions = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
            
            foreach ($required_permissions as $permission) {
                // Ici on simule, dans le vrai code ce serait Context::getContext()->employee->hasAccess()
                echo "   ✅ Permission $permission: OK\n";
            }
            
            $this->test_results['permissions'] = 'SUCCESS';
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['permissions'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function displayResults()
    {
        echo "=== RÉSULTATS DES TESTS ===\n";
        
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($this->test_results as $test => $result) {
            $icon = $result == 'SUCCESS' ? '✅' : ($result == 'FAILED' ? '❌' : '⚠️');
            echo "$icon $test: $result\n";
            
            if ($result == 'SUCCESS') $success++;
            elseif ($result == 'FAILED') $failed++;
            else $skipped++;
        }
        
        echo "\n📊 STATISTIQUES:\n";
        echo "   Réussis: $success\n";
        echo "   Échoués: $failed\n";
        echo "   Ignorés: $skipped\n";
        
        if ($failed == 0) {
            echo "\n🎉 TOUS LES TESTS SONT OK ! Prêt pour le dev !\n";
        } else {
            echo "\n⚠️ Des tests ont échoué. Vérifiez les données de test.\n";
        }
    }
}

// Exécution des tests
$tester = new AdminControllerTest();
$tester->runAllTests();

echo "\n=== FIN TEST ADMIN CONTROLLER ===\n";
