// Crée un fichier temporaire test/test_crud.php
<?php
require_once '../classes/Quote.php';
require_once '../classes/QuoteStatus.php';

// Test de création basique
$quote = new Quote();
$quote->id_customer = 1;
$quote->id_currency = 1;
$quote->id_lang = 1;
$quote->reference = Quote::generateReference();

echo "Référence générée : " . $quote->reference . "\n";
echo "Test de validation : " . ($quote->validateFields() ? "OK" : "KO") . "\n";
