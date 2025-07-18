<?php
/**
 * Script de nettoyage des données de test
 */

// Déterminer le chemin vers PrestaShop
$ds = DIRECTORY_SEPARATOR;
$ps_root_dir = dirname(__FILE__) . $ds . '..' . $ds . '..' . $ds . '..' . $ds;

// Inclure la configuration PrestaShop
if (file_exists($ps_root_dir . 'config' . $ds . 'config.inc.php')) {
    require_once($ps_root_dir . 'config' . $ds . 'config.inc.php');
    echo "✅ Configuration trouvée !\n";
} else {
    die("❌ Configuration PrestaShop non trouvée dans : " . $ps_root_dir . "\n");
}

// Inclure les classes du module
require_once(dirname(__FILE__) . '/../classes/Quote.php');
require_once(dirname(__FILE__) . '/../classes/QuoteProduct.php');
require_once(dirname(__FILE__) . '/../classes/QuoteStatus.php');

echo "=== NETTOYAGE DES DONNÉES DE TEST ===\n\n";

try {
    // 1. Nettoyer les produits de devis de test
    echo "1. Nettoyage des produits de devis...\n";
    $sql_products = "DELETE FROM " . _DB_PREFIX_ . "quote_product 
                     WHERE id_quote IN (
                         SELECT id_quote FROM " . _DB_PREFIX_ . "quote 
                         WHERE reference LIKE 'Q%' OR id_customer = 1
                     )";
    
    $result = Db::getInstance()->execute($sql_products);
    if ($result) {
        $affected = Db::getInstance()->Affected_Rows();
        echo "✅ {$affected} produits de devis supprimés\n";
    } else {
        echo "⚠️  Aucun produit de devis à supprimer\n";
    }

    // 2. Nettoyer les devis de test
    echo "\n2. Nettoyage des devis...\n";
    $sql_quotes = "DELETE FROM " . _DB_PREFIX_ . "quote 
                   WHERE reference LIKE 'Q%' OR id_customer = 1";
    
    $result = Db::getInstance()->execute($sql_quotes);
    if ($result) {
        $affected = Db::getInstance()->Affected_Rows();
        echo "✅ {$affected} devis supprimés\n";
    } else {
        echo "⚠️  Aucun devis à supprimer\n";
    }

    // 3. Remettre à zéro le compteur de références (optionnel)
    echo "\n3. Remise à zéro du compteur de références...\n";
    $sql_counter = "DELETE FROM " . _DB_PREFIX_ . "configuration 
                    WHERE name = 'PS_QUOTE_REFERENCE_COUNTER'";
    
    $result = Db::getInstance()->execute($sql_counter);
    if ($result) {
        echo "✅ Compteur de références remis à zéro\n";
    } else {
        echo "⚠️  Compteur déjà à zéro\n";
    }

    // 4. Vérification
    echo "\n4. Vérification après nettoyage...\n";
    
    $count_quotes = Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "quote");
    $count_products = Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "quote_product");
    
    echo "📊 Devis restants : {$count_quotes}\n";
    echo "📊 Produits de devis restants : {$count_products}\n";

    echo "\n✅ Nettoyage terminé avec succès !\n";

} catch (Exception $e) {
    echo "❌ Erreur lors du nettoyage : " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== FIN NETTOYAGE ===\n";
?>
