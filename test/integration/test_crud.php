<?php
// test/test_crud.php

// Depuis le fichier test/, on remonte d'un niveau vers le module, puis 2 niveaux vers PrestaShop
$prestashop_root = dirname(__FILE__) . '/../../../';

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

echo "✅ Configuration trouvée !\n";

// Initialisation PrestaShop
require_once($prestashop_root . 'config/config.inc.php');

// Chargement de nos classes
require_once dirname(__FILE__) . '/../classes/Quote.php';
require_once dirname(__FILE__) . '/../classes/QuoteStatus.php';
require_once dirname(__FILE__) . '/../classes/QuoteProduct.php';

echo "\n=== TEST CRUD MODULE QUOTE ===\n";

try {
    // Test 1: Vérifier que les statuts existent
    echo "1. Test des statuts...\n";
    $statuses = QuoteStatus::getQuoteStatuses();
    echo "Nombre de statuts : " . count($statuses) . "\n";
    
    if (empty($statuses)) {
        echo "⚠️ Aucun statut trouvé. Installation des statuts par défaut...\n";
        if (QuoteStatus::installDefaultStatuses()) {
            echo "✅ Statuts par défaut installés !\n";
            $statuses = QuoteStatus::getQuoteStatuses();
            echo "Nombre de statuts après installation : " . count($statuses) . "\n";
        } else {
            echo "❌ Échec installation statuts par défaut\n";
        }
    } else {
        echo "✅ Statuts trouvés :\n";
        foreach ($statuses as $status) {
            echo "  - ID: {$status['id_quote_status']}, Nom: {$status['name']}, Couleur: {$status['color']}\n";
        }
    }
    
    // Test 2: Générer une référence
    echo "\n2. Test génération référence...\n";
    $reference = Quote::generateReference();
    echo "✅ Référence générée : " . $reference . "\n";
    
    // Test 3: Créer un devis
    echo "\n3. Test création devis...\n";
    $quote = new Quote();
    $quote->id_customer = 1;
    $quote->id_currency = 1;
    $quote->id_lang = 1;
    
    // Utilise le premier statut disponible
    $first_status = reset($statuses);
    if ($first_status) {
        $quote->id_quote_status = $first_status['id_quote_status'];
        echo "Utilisation du statut ID: " . $quote->id_quote_status . "\n";
    } else {
        $quote->id_quote_status = 1;
        echo "Utilisation du statut par défaut (ID: 1)\n";
    }
    
    // La référence est auto-générée dans le constructeur
    echo "Référence du devis : " . $quote->reference . "\n";
    
    if ($quote->save()) {
        echo "✅ Devis créé avec l'ID : " . $quote->id . "\n";
        
        // Test 4: Récupérer le devis
        $quote_loaded = new Quote($quote->id);
        echo "✅ Devis rechargé - Référence : " . $quote_loaded->reference . "\n";
        
        // Test 5: Ajouter un produit au devis
        echo "\n4. Test ajout produit...\n";
        
        // Vérifier qu'un produit existe
        $product_exists = Db::getInstance()->getValue('
            SELECT p.id_product 
            FROM ' . _DB_PREFIX_ . 'product p 
            WHERE p.active = 1 
        ');
        if (!$product_exists) {
            echo "❌ Aucun produit actif trouvé en base\n";
        } else {
            echo "✅ Produit trouvé (ID: $product_exists)\n";
            
            // Utiliser la méthode addProductToQuote avec des prix explicites
            $quote_product_result = QuoteProduct::addProductToQuote(
                $quote->id,     // id_quote
                $product_exists, // id_product
                0,              // id_product_attribute
                2,              // quantity
                10.00,          // unit_price_tax_excl
                12.00           // unit_price_tax_incl
            );

                        // DEBUG : Vérifie en base directement
            // echo "=== DEBUG BASE DE DONNÉES ===\n";
            // $result = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'quote_product WHERE id_quote = ' . (int)$quote->id);
            // echo "Produits en base : " . count($result) . "\n";
            // if ($result) {
            //     foreach ($result as $product) {
            //         echo "- ID: {$product['id_quote_product']}, Produit: {$product['id_product']}, Qty: {$product['quantity']}\n";
            //     }
            // }

            // // DEBUG : Teste la méthode getByQuote
            // echo "=== DEBUG METHODE getByQuote ===\n";
            // $products_method = QuoteProduct::getByQuote($quote->id);
            // echo "Produits via getByQuote : " . count($products_method) . "\n";
            // var_dump($products_method);

            // // DEBUG : Teste la méthode getProducts du Quote
            // echo "=== DEBUG METHODE getProducts ===\n";
            // $products_quote = $quote->getProducts();
            // echo "Produits via Quote->getProducts : " . count($products_quote) . "\n";
            // var_dump($products_quote);
            
            if ($quote_product_result !== false) {
                echo "✅ Produit ajouté au devis\n";
                
                // Test 6: Calculer les totaux
                echo "\n5. Test calcul totaux...\n";
                if ($quote->calculateTotals()) {
                    $quote->update();
                    echo "✅ Totaux calculés - Total HT: " . $quote->total_products . "€\n";
                    echo "✅ Totaux calculés - Total TTC: " . $quote->total_products_wt . "€\n";
                } else {
                    echo "❌ Erreur calcul totaux\n";
                }
                
                // Test 7: Récupérer les produits du devis
                echo "\n6. Test récupération produits...\n";
                $products = QuoteProduct::getByQuote($quote->id);
                echo "Nombre de produits : " . count($products) . "\n";
                foreach ($products as $product) {
                    echo "  - Produit ID: {$product['id_product']}, Qty: {$product['quantity']}, Prix HT: {$product['unit_price_tax_excl']}€\n";
                }
                
            } else {
                echo "❌ Échec ajout produit\n";
            }
        }
        
    } else {
        echo "❌ Échec de création du devis\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " .  $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";
