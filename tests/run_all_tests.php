<?php
// tests/run_all_tests.php

echo "🚀 === SUITE DE TESTS COMPLÈTE === 🚀\n\n";

// INITIALISATION CENTRALISÉE
require_once 'bootstrap.php';

// 1. Tests Admin Controller  
echo "Phase 1: Tests Controller Admin\n";
echo "==============================\n";
require_once 'admin/AdminQuoteControllerTest.php';

echo "\n\n";

// 2. Tests Service
echo "Phase 2: Tests Service de Conversion\n";
echo "===================================\n";
require_once 'unit/OrderToQuoteConverterTest.php';

echo "\n\n";

// 3. Tests d'intégration
echo "Phase 3: Tests d'intégration\n";
echo "============================\n";
require_once 'integration/test_crud.php';

// 4. Test admin controller (si différent)
echo "\nPhase 4: Test Admin Controller 2\n";
echo "================================\n";
require_once 'admin/test_admin_controller.php';

echo "\n🏁 === TESTS TERMINÉS === 🏁\n";
?>
