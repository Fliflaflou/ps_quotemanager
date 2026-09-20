<?php
// tests/unit/QuoteOrderConverterTest.php
//
// Real test of QuoteOrderConverter (Quote -> Order), replacing the previous
// stale test that referenced a nonexistent "OrderToQuoteConverter" service.

require_once dirname(__FILE__) . '/../../../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../../../init.php';
require_once dirname(__FILE__) . '/../../classes/Quote.php';
require_once dirname(__FILE__) . '/../../classes/QuoteProduct.php';
require_once dirname(__FILE__) . '/../../classes/QuoteStatus.php';
require_once dirname(__FILE__) . '/../../classes/StockReservation.php';
require_once dirname(__FILE__) . '/../../classes/QuoteOrderConverter.php';

class QuoteOrderConverterTest
{
    private $test_results = [];
    private $customer_id = 0;
    private $address_id = 0;
    private $status_id = 0;
    private $product_ids = [];
    private $created_quote_ids = [];
    private $created_order_ids = [];

    public function runAllTests()
    {
        echo "🧪 === TEST QuoteOrderConverter (devis -> commande) === 🧪\n\n";

        if (!$this->loadFixtures()) {
            echo "   ⚠️ Fixtures indisponibles (client/adresse/produits/statut) : tests ignorés\n";
            $this->displayResults();
            return;
        }

        try {
            $this->testCannotConvertEmptyQuote();
            $this->testBasicConversionWithoutDiscount();
            $this->testConversionWithProductAndGlobalDiscount();
            $this->testConvertingTwiceReturnsSameOrder();
            $this->testMissingThirdPartyColumnDiagnostic();
        } catch (Throwable $exception) {
            echo "   ❌ Exception de test : {$exception->getMessage()}\n\n";
            $this->test_results['unexpected_exception'] = 'FAILED';
        } finally {
            $this->cleanup();
        }

        $this->displayResults();
    }

    private function loadFixtures()
    {
        $db = Db::getInstance();
        $this->customer_id = (int) ($db->executeS('SELECT id_customer FROM ' . _DB_PREFIX_ . 'customer ORDER BY id_customer LIMIT 1')[0]['id_customer'] ?? 0);
        $this->status_id = (int) ($db->executeS('SELECT id_quote_status FROM ' . _DB_PREFIX_ . 'quote_status ORDER BY id_quote_status LIMIT 1')[0]['id_quote_status'] ?? 0);

        if ($this->customer_id > 0) {
            $this->address_id = (int) Address::getFirstCustomerAddressId($this->customer_id);
        }

        // Two distinct, well-stocked products so the "multiple products" test is meaningful.
        $rows = $db->executeS(
            'SELECT p.id_product FROM ' . _DB_PREFIX_ . 'product p'
            . ' INNER JOIN ' . _DB_PREFIX_ . 'stock_available sa ON sa.id_product = p.id_product AND sa.id_product_attribute = 0'
            . ' WHERE p.active = 1 ORDER BY sa.quantity DESC LIMIT 2'
        );
        $this->product_ids = array_map(function ($row) {
            return (int) $row['id_product'];
        }, $rows);

        return $this->customer_id > 0 && $this->address_id > 0 && $this->status_id > 0 && count($this->product_ids) >= 2;
    }

    private function assertTrue($condition, $message, $key)
    {
        if ($condition) {
            echo "   ✅ {$message}\n";
            $this->test_results[$key] = 'SUCCESS';
        } else {
            echo "   ❌ {$message}\n";
            $this->test_results[$key] = 'FAILED';
        }
    }

    private function newQuote()
    {
        $quote = new Quote();
        $quote->reference = 'QOC' . date('ymdHis') . strtoupper(substr(uniqid(), -6));
        $quote->id_customer = $this->customer_id;
        $quote->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT') ?: 1;
        $quote->id_lang = (int) Configuration::get('PS_LANG_DEFAULT') ?: 1;
        $quote->id_quote_status = $this->status_id;
        $quote->id_address_delivery = $this->address_id;
        $quote->id_address_invoice = $this->address_id;
        $quote->valid = 0;
        if (!$quote->add() || !Validate::isLoadedObject($quote)) {
            throw new RuntimeException('Impossible de créer le devis de test ' . $quote->reference);
        }
        $this->created_quote_ids[] = (int) $quote->id;

        return $quote;
    }

    private function converter()
    {
        $module = Module::getInstanceByName('myquotemanager');

        return new QuoteOrderConverter($module);
    }

    public function testCannotConvertEmptyQuote()
    {
        echo "1. Conversion d'un devis sans produit...\n";

        $quote = $this->newQuote();
        $result = $this->converter()->convert($quote, $this->address_id, $this->address_id);

        $this->assertTrue($result === false, 'Conversion refusée pour un devis sans produit', 'empty_quote_rejected');
        echo "\n";
    }

    public function testBasicConversionWithoutDiscount()
    {
        echo "2. Conversion basique sans réduction...\n";

        $quote = $this->newQuote();
        QuoteProduct::addProductToQuote($quote->id, $this->product_ids[0], 0, 1, 100, 120);
        $quote->calculateTotals();

        $order = $this->converter()->convert($quote, $this->address_id, $this->address_id);
        if (!$order) {
            $this->assertTrue(false, 'Conversion réussie: ' . $this->converter()->getLastError(), 'basic_conversion');
            echo "\n";
            return;
        }
        $this->created_order_ids[] = (int) $order->id;

        $this->assertTrue(
            abs((float) $order->total_paid - (float) $quote->total_paid) < 0.01,
            "Commande #{$order->id} créée avec le bon total (order={$order->total_paid}, quote={$quote->total_paid})",
            'basic_conversion'
        );
        echo "\n";
    }

    public function testConversionWithProductAndGlobalDiscount()
    {
        echo "3. Conversion avec réduction produit + réduction globale (2 produits distincts)...\n";

        $quote = $this->newQuote();
        // Product A: qty 2 @ 100€ HT with 10% product-level reduction -> net 180€
        QuoteProduct::addProductToQuote($quote->id, $this->product_ids[0], 0, 2, 100, 120, '', 10, 0);
        // Product B: qty 1 @ 50€ HT, no product-level reduction -> net 50€
        QuoteProduct::addProductToQuote($quote->id, $this->product_ids[1], 0, 1, 50, 60);
        // Global reduction: 5% + 10€ on top of the 230€ product-net base -> 21.5€
        $quote->applyGlobalDiscount(5, 10);

        $reloaded = new Quote($quote->id);
        $order = $this->converter()->convert($reloaded, $this->address_id, $this->address_id);

        if (!$order) {
            $this->assertTrue(false, 'Conversion réussie: ' . $this->converter()->getLastError(), 'discount_conversion');
            echo "\n";
            return;
        }
        $this->created_order_ids[] = (int) $order->id;

        $order_details = $order->getProducts();
        $sum_excl = 0.0;
        foreach ($order_details as $detail) {
            $sum_excl += (float) $detail['total_price_tax_excl'];
        }

        $this->assertTrue(
            abs($sum_excl - (float) $reloaded->total_products) < 0.02,
            "Somme des lignes de commande ({$sum_excl}€) = total du devis ({$reloaded->total_products}€)",
            'discount_conversion_sum'
        );
        $this->assertTrue(
            abs((float) $order->total_products - (float) $reloaded->total_products) < 0.01,
            "Order->total_products reflète les réductions (order={$order->total_products}, quote={$reloaded->total_products})",
            'discount_conversion_total_products'
        );
        $this->assertTrue(
            abs((float) $order->total_paid - (float) $reloaded->total_paid) < 0.01,
            "Order->total_paid reflète les réductions (order={$order->total_paid}, quote={$reloaded->total_paid})",
            'discount_conversion_total_paid'
        );
        $this->assertTrue(
            count($order_details) === 2,
            'Les deux produits distincts apparaissent bien comme 2 lignes de commande',
            'discount_conversion_line_count'
        );
        echo "\n";
    }

    public function testConvertingTwiceReturnsSameOrder()
    {
        echo "4. Reconversion d'un devis déjà transformé...\n";

        $quote = $this->newQuote();
        QuoteProduct::addProductToQuote($quote->id, $this->product_ids[0], 0, 1, 20, 24);

        $converter = $this->converter();
        $first_order = $converter->convert($quote, $this->address_id, $this->address_id);
        if (!$first_order) {
            $this->assertTrue(false, 'Première conversion réussie: ' . $converter->getLastError(), 'reconversion_idempotent');
            echo "\n";
            return;
        }
        $this->created_order_ids[] = (int) $first_order->id;

        $reloaded = new Quote($quote->id);
        $second_order = $converter->convert($reloaded, $this->address_id, $this->address_id);

        $this->assertTrue(
            $second_order && (int) $second_order->id === (int) $first_order->id,
            'La reconversion renvoie la commande existante au lieu d\'en créer une nouvelle',
            'reconversion_idempotent'
        );
        echo "\n";
    }

    public function testMissingThirdPartyColumnDiagnostic()
    {
        echo "5. Diagnostic d'un hook tiers avec colonne SQL manquante...\n";

        $method = new ReflectionMethod(QuoteOrderConverter::class, 'formatCartCreationError');
        $method->setAccessible(true);
        $message = $method->invoke(
            $this->converter(),
            new RuntimeException("Unknown column 'ets.last_seek_key' in 'field list'")
        );

        $this->assertTrue(
            strpos($message, 'last_seek_key') !== false && strpos($message, 'module tiers') !== false,
            'La panne SQL tierce produit un message BO explicite',
            'third_party_hook_diagnostic'
        );
        echo "\n";
    }

    private function cleanup()
    {
        foreach ($this->created_order_ids as $id_order) {
            $order = new Order($id_order);
            if (Validate::isLoadedObject($order)) {
                $order->delete();
            }
        }
        foreach ($this->created_quote_ids as $id_quote) {
            $quote = new Quote($id_quote);
            if (Validate::isLoadedObject($quote)) {
                $quote->id_order = 0;
                $quote->update();
                $quote->delete();
            }
        }
    }

    public function displayResults()
    {
        echo "=== RÉSULTATS QuoteOrderConverter ===\n";
        $success = 0;
        $failed = 0;
        foreach ($this->test_results as $name => $status) {
            echo ($status === 'SUCCESS' ? '✅' : '❌') . " {$name}: {$status}\n";
            $status === 'SUCCESS' ? $success++ : $failed++;
        }
        echo "\n📊 {$success} réussi(s), {$failed} échoué(s)\n\n";
    }
}

$test = new QuoteOrderConverterTest();
$test->runAllTests();
