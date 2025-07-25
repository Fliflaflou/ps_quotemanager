<?php
// test/bootstrap.php - Configuration Docker

// === CHEMINS DOCKER CORRIGÉS ===
// Depuis modules/myquotemanager/test/ vers data/
$prestashop_root = dirname(__FILE__) . '/../../../data/';

echo "=== INITIALISATION TESTS DOCKER ===\n";
echo "Répertoire tests : " . dirname(__FILE__) . "\n";
echo "Chemin calculé PrestaShop : " . $prestashop_root . "\n";
echo "Chemin réel PrestaShop : " . realpath($prestashop_root) . "\n";

// === VÉRIFICATIONS ===
$config_file = $prestashop_root . 'config/config.inc.php';
echo "Recherche config : " . $config_file . "\n";

if (!file_exists($config_file)) {
    die("❌ Config PrestaShop introuvable !\nChemin testé : " . $config_file . "\n");
}

echo "✅ Configuration PrestaShop trouvée\n";

// === CHARGEMENT PRESTASHOP ===
require_once($config_file);

// === CHARGEMENT NOS CLASSES ===
$module_root = dirname(__FILE__) . '/../';
echo "Chemin module : " . realpath($module_root) . "\n";

require_once $module_root . 'classes/Quote.php';
require_once $module_root . 'classes/QuoteProduct.php';
require_once $module_root . 'classes/QuoteStatus.php';

echo "✅ Classes module chargées\n";
echo "========================\n\n";

// Variables globales disponibles pour tous les tests
global $prestashop_root, $module_root;
?>