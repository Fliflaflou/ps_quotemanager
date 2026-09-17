<?php
// tests/run_all_tests.php

echo "🚀 === SUITE DE TESTS COMPLÈTE === 🚀\n\n";

// INITIALISATION CENTRALISÉE (chemins absolus : indépendant du répertoire courant)
require_once __DIR__ . '/bootstrap.php';

// 1. Tests Admin Controller  
echo "Phase 1: Tests Controller Admin\n";
echo "==============================\n";
require_once __DIR__ . '/admin/AdminQuoteControllerTest.php';

echo "\n\n";

// 2. Tests unitaires réductions produit + devis
echo "Phase 2: Tests réductions (produit + globale)\n";
echo "===============================================\n";
require_once __DIR__ . '/DiscountTest.php';

echo "\n\n";

// 3. Tests du service de conversion devis -> commande
echo "Phase 3: Tests conversion devis -> commande\n";
echo "============================================\n";
require_once __DIR__ . '/unit/QuoteOrderConverterTest.php';

echo "\n\n";

// 4. Tests d'intégration
echo "Phase 4: Tests d'intégration\n";
echo "============================\n";
require_once __DIR__ . '/integration/test_crud.php';

// 5. Test admin controller (si différent)
echo "\nPhase 5: Test Admin Controller 2\n";
echo "================================\n";
require_once __DIR__ . '/admin/test_admin_controller.php';


echo "\n🏁 === TESTS TERMINÉS === 🏁\n";
?>
