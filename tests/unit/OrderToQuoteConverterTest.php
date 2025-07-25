<?php
// tests/Unit/OrderToQuoteConverterTest.php

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../src/Entity/Quote.php';
require_once dirname(__FILE__) . '/../src/Entity/QuoteProduct.php';
require_once dirname(__FILE__) . '/../src/Service/OrderToQuoteConverter.php';

class OrderToQuoteConverterTest 
{
    private $test_results = [];
    
    public function runAllTests()
    {
        echo "🧪 === TEST SERVICE CONVERSION === 🧪\n\n";
        
        $this->testServiceInstantiation();
        $this->testConvertValidOrder();
        $this->testConvertInvalidOrder();
        $this->testProductsCopied();
        
        $this->displayResults();
    }
    
    public function testServiceInstantiation()
    {
        echo "1. Test d'instanciation du service...\n";
        
        try {
            $converter = new OrderToQuoteConverter();
            echo "   ✅ Service OrderToQuoteConverter instancié\n";
            echo "   ✅ Méthode convert() disponible: " . (method_exists($converter, 'convert') ? 'Oui' : 'Non') . "\n";
            
            $this->test_results['service_instantiation'] = 'SUCCESS';
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['service_instantiation'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function testConvertValidOrder()
    {
        echo "2. Test conversion commande valide...\n";
        
        try {
            // Récupérer une vraie commande
            $orders = Db::getInstance()->executeS('
                SELECT id_order 
                FROM ' . _DB_PREFIX_ . 'orders 
                ORDER BY date_add DESC 
                LIMIT 1
            ');
            
            if (empty($orders)) {
                echo "   ⚠️ Aucune commande trouvée pour le test\n";
                $this->test_results['convert_valid'] = 'SKIPPED';
                return;
            }
            
            $order = new Order($orders[0]['id_order']);
            echo "   📦 Commande test: #{$order->reference}\n";
            
            $converter = new OrderToQuoteConverter();
            $quote = $converter->convert($order);
            
            if ($quote && $quote->id_quote) {
                echo "   ✅ Conversion réussie ! Devis #{$quote->reference}\n";
                echo "   📊 Client: {$quote->id_customer}\n";
                echo "   💰 Total: {$quote->total_paid}€\n";
                
                // Nettoyer le test
                $quote->delete();
                
                $this->test_results['convert_valid'] = 'SUCCESS';
            } else {
                echo "   ❌ Conversion échouée\n";
                $this->test_results['convert_valid'] = 'FAILED';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['convert_valid'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function testConvertInvalidOrder()
    {
        echo "3. Test conversion commande invalide...\n";
        
        try {
            $converter = new OrderToQuoteConverter();
            
            // Test avec null
            $result1 = $converter->convert(null);
            $test1 = ($result1 === false) ? '✅' : '❌';
            echo "   $test1 Conversion null: " . ($result1 === false ? 'Refusée correctement' : 'Acceptée incorrectement') . "\n";
            
            // Test avec commande vide
            $fake_order = new Order();
            $result2 = $converter->convert($fake_order);
            $test2 = ($result2 === false) ? '✅' : '❌';
            echo "   $test2 Conversion commande vide: " . ($result2 === false ? 'Refusée correctement' : 'Acceptée incorrectement') . "\n";
            
            $this->test_results['convert_invalid'] = ($result1 === false && $result2 === false) ? 'SUCCESS' : 'FAILED';
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['convert_invalid'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function testProductsCopied()
    {
        echo "4. Test copie des produits...\n";
        
        try {
            // Récupérer une commande avec des produits
            $sql = 'SELECT DISTINCT o.id_order 
                    FROM ' . _DB_PREFIX_ . 'orders o
                    INNER JOIN ' . _DB_PREFIX_ . 'order_detail od ON o.id_order = od.id_order
                    ORDER BY o.date_add DESC 
                    LIMIT 1';
                    
            $orders = Db::getInstance()->executeS($sql);
            
            if (empty($orders)) {
                echo "   ⚠️ Aucune commande avec produits trouvée\n";
                $this->test_results['products_copied'] = 'SKIPPED';
                return;
            }
            
            $order = new Order($orders[0]['id_order']);
            $order_products = $order->getOrderDetailList();
            
            echo "   📦 Commande avec " . count($order_products) . " produits\n";
            
            $converter = new OrderToQuoteConverter();
            $quote = $converter->convert($order);
            
            if ($quote) {
                $quote_products = QuoteProduct::getQuoteProducts($quote->id_quote);
                
                $success = (count($quote_products) == count($order_products));
                $icon = $success ? '✅' : '❌';
                
                echo "   $icon Produits copiés: " . count($quote_products) . "/" . count($order_products) . "\n";
                
                // Nettoyer
                $quote->delete();
                
                $this->test_results['products_copied'] = $success ? 'SUCCESS' : 'FAILED';
            } else {
                echo "   ❌ Conversion échouée\n";
                $this->test_results['products_copied'] = 'FAILED';
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $this->test_results['products_copied'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    public function displayResults()
    {
        echo "=== RÉSULTATS DES TESTS SERVICE ===\n";
        
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
            echo "\n🎉 SERVICE PRÊT POUR LA PRODUCTION ! 🎉\n";
        } else {
            echo "\n⚠️ Des améliorations sont nécessaires.\n";
        }
    }
}

// Exécution
$test = new OrderToQuoteConverterTest();
$test->runAllTests();
?>
