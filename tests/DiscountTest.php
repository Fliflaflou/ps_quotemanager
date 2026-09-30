<?php
/**
 * Test suite for discount functionality
 */

namespace MyQuoteManager\Tests;

use Quote;
use QuoteProduct;
use Db;
use Configuration;

require_once dirname(__FILE__) . '/../../../config/config.inc.php';
require_once dirname(__FILE__) . '/../classes/Quote.php';
require_once dirname(__FILE__) . '/../classes/QuoteProduct.php';
require_once dirname(__FILE__) . '/../classes/QuoteStatus.php';

class DiscountTest
{
    private $quote_id = 0;
    private $product_id = 0;
    private $customer_id = 0;
    private $status_id = 0;
    private $product_attribute = 0;
    private $created_quote_ids = [];
    private $pass_count = 0;
    private $fail_count = 0;

    public function run()
    {
        echo "<pre>\n";
        echo "========================================\n";
        echo "Discount Functionality Tests\n";
        echo "========================================\n\n";

        if (!$this->loadFixtures()) {
            echo "❌ Impossible de charger les données de test (client/produit/statut introuvable)\n";
            echo "</pre>\n";
            return;
        }

        $this->testProductLevelReduction();
        $this->testReductionCap();
        $this->testCalculateTotals();
        $this->testGlobalReductionPersistence();
        $this->testCombinedProductAndGlobalReduction();
        $this->testInvalidReductionInputsRejected();

        $this->cleanup();

        echo "========================================\n";
        echo "Tests completed! {$this->pass_count} passed, {$this->fail_count} failed\n";
        echo "========================================\n";
        echo "</pre>\n";
    }

    private function loadFixtures()
    {
        $db = Db::getInstance();
        $this->customer_id = (int) ($db->executeS('SELECT id_customer FROM ' . _DB_PREFIX_ . 'customer ORDER BY id_customer LIMIT 1')[0]['id_customer'] ?? 0);
        // Pick a well-stocked product so cumulative test demand within a single quote never runs out.
        $product_row = $db->executeS(
            'SELECT p.id_product FROM ' . _DB_PREFIX_ . 'product p'
            . ' INNER JOIN ' . _DB_PREFIX_ . 'stock_available sa ON sa.id_product = p.id_product AND sa.id_product_attribute = 0'
            . ' WHERE p.active = 1 AND NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'product_attribute pa WHERE pa.id_product = p.id_product)'
            . ' ORDER BY sa.quantity DESC LIMIT 1'
        );
        $this->product_id = (int) ($product_row[0]['id_product'] ?? 0);
        $this->status_id = (int) ($db->executeS('SELECT id_quote_status FROM ' . _DB_PREFIX_ . 'quote_status ORDER BY id_quote_status LIMIT 1')[0]['id_quote_status'] ?? 0);

        return $this->customer_id > 0 && $this->product_id > 0 && $this->status_id > 0;
    }

    private function assertTrue($condition, $message)
    {
        if ($condition) {
            echo "✓ {$message}\n";
            $this->pass_count++;
        } else {
            echo "✗ {$message}\n";
            $this->fail_count++;
        }
    }

    private function assertAlmostEqual($expected, $actual, $message)
    {
        $this->assertTrue(abs((float) $expected - (float) $actual) < 0.01, $message . " (expected {$expected}, got {$actual})");
    }

    private function newQuote()
    {
        $quote = new Quote();
        $quote->id_customer = $this->customer_id;
        $quote->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT') ?: 1;
        $quote->id_lang = (int) Configuration::get('PS_LANG_DEFAULT') ?: 1;
        $quote->id_quote_status = $this->status_id;
        $quote->valid = 0;
        $quote->add();
        $this->created_quote_ids[] = (int) $quote->id;

        return $quote;
    }

    private function cleanup()
    {
        foreach ($this->created_quote_ids as $id_quote) {
            $quote = new Quote($id_quote);
            if (\Validate::isLoadedObject($quote) && (int) $quote->id_order === 0) {
                $quote->delete();
            }
        }
    }

    /**
     * Test 1: Apply product-level reduction (percent and amount)
     */
    private function testProductLevelReduction()
    {
        echo "[TEST 1] Product-level reduction (% and €)\n";
        echo "---\n";

        $quote = $this->newQuote();
        echo "✓ Quote created: #{$quote->id}\n";
        $this->quote_id = $quote->id;

        $quote_product = QuoteProduct::addProductToQuote($quote->id, $this->product_id, $this->product_attribute, 2, 100.00, 120.00);
        $this->assertTrue((bool) $quote_product, 'Product added: qty=2, price_ht=100, price_ttc=120 (line total HT 200.00)');

        if ($quote_product) {
            $this->assertTrue((bool) QuoteProduct::updateReduction($quote_product->id, 10, 0), 'Applied 10% reduction');
            $quote->calculateTotals();
            $this->assertAlmostEqual(180, $quote->total_products, 'Total products HT after 10% reduction');
            $this->assertAlmostEqual(20, $quote->total_discount, 'Total discount HT after 10% reduction');

            // updateReduction replaces both fields; 0%/20€ overrides the previous 10%
            $this->assertTrue((bool) QuoteProduct::updateReduction($quote_product->id, 0, 20), 'Applied 20€ fixed amount reduction (replaces the 10%)');
            $quote->calculateTotals();
            $this->assertAlmostEqual(180, $quote->total_products, 'Total products HT after 20€ reduction');
            $this->assertAlmostEqual(20, $quote->total_discount, 'Total discount HT after 20€ reduction');
        }
        echo "\n";
    }

    /**
     * Test 2: Reduction cap (max 100%)
     */
    private function testReductionCap()
    {
        echo "[TEST 2] Reduction cap (max 100%)\n";
        echo "---\n";

        if ($this->quote_id <= 0) {
            echo "⚠ Skipping test (no quote from previous test)\n\n";
            return;
        }

        $quote = new Quote($this->quote_id);
        $products = $quote->getProducts();

        if (count($products) > 0) {
            $product = array_shift($products);
            $line_total = $product['price_tax_excl'] * $product['quantity'];
            echo "Line total HT: {$line_total}€\n";

            $this->assertTrue(!QuoteProduct::updateReduction($product['id_quote_product'], 150, 0), 'Correctly rejected 150% reduction');
            $this->assertTrue(!QuoteProduct::updateReduction($product['id_quote_product'], 60, 100), 'Correctly rejected 100€ + 60% (220€ > 200€ line total)');
            $this->assertTrue((bool) QuoteProduct::updateReduction($product['id_quote_product'], 100, 0), 'Applied exact 100% reduction (free product)');

            $quote->calculateTotals();
            $this->assertAlmostEqual(0, $quote->total_products, 'Total products after 100% reduction is zero');
        }
        echo "\n";
    }

    /**
     * Test 3: Calculate totals with multiple products and discounts
     */
    private function testCalculateTotals()
    {
        echo "[TEST 3] Calculate totals with multiple discounts\n";
        echo "---\n";

        $quote = $this->newQuote();
        echo "✓ Quote created: #{$quote->id}\n";

        // Product 1: 100€ HT x 1, 20% reduction
        $p1 = QuoteProduct::addProductToQuote($quote->id, $this->product_id, 0, 1, 100, 120);
        if ($p1) {
            QuoteProduct::updateReduction($p1->id, 20, 0);
            echo "✓ Product 1: 100€ x1 with 20% reduction = 80€\n";
        }

        // Product 2: 50€ HT x 2, 5€ fixed reduction
        $p2 = QuoteProduct::addProductToQuote($quote->id, $this->product_id, 0, 2, 50, 60);
        if ($p2) {
            QuoteProduct::updateReduction($p2->id, 0, 5);
            echo "✓ Product 2: 50€ x2 with 5€ reduction = 95€\n";
        }

        $quote->calculateTotals();
        // (100 - 20) + (100 - 5) = 80 + 95 = 175
        $this->assertAlmostEqual(175, $quote->total_products, 'Total products calculated correctly');
        $this->assertAlmostEqual(25, $quote->total_discount, 'Total discount calculated correctly');
        echo "\n";
    }

    /**
     * Test 4: Global (whole-quote) reduction persists across reloads.
     */
    private function testGlobalReductionPersistence()
    {
        echo "[TEST 4] Global reduction persistence\n";
        echo "---\n";

        $quote = $this->newQuote();
        QuoteProduct::addProductToQuote($quote->id, $this->product_id, 0, 2, 100, 120);

        $this->assertTrue((bool) $quote->applyGlobalDiscount(10, 5), 'Applied 10% + 5€ global discount');

        // Reload from DB to make sure it was actually persisted, not just kept in memory
        $reloaded = new Quote($quote->id);
        $this->assertAlmostEqual(10, $reloaded->global_reduction_percent, 'global_reduction_percent persisted');
        $this->assertAlmostEqual(5, $reloaded->global_reduction_amount, 'global_reduction_amount persisted');
        // base = 200, global = 5 + 10%*200 = 25 -> total_products = 175
        $this->assertAlmostEqual(175, $reloaded->total_products, 'Total products after persisted global discount');
        $this->assertAlmostEqual(25, $reloaded->total_global_discount, 'total_global_discount persisted');

        // Adding another product must recompute the global discount against the new base
        QuoteProduct::addProductToQuote($quote->id, $this->product_id, 0, 1, 50, 60);
        $reloaded->updateTotals();
        // base = 250, global = 5 + 10%*250 = 30 -> total_products = 220
        $this->assertAlmostEqual(220, $reloaded->total_products, 'Global discount recalculated after adding a product');
        echo "\n";
    }

    /**
     * Test 5: Product-level and global reductions combine correctly.
     */
    private function testCombinedProductAndGlobalReduction()
    {
        echo "[TEST 5] Combined product-level + global reduction\n";
        echo "---\n";

        $quote = $this->newQuote();
        $p1 = QuoteProduct::addProductToQuote($quote->id, $this->product_id, 0, 2, 100, 120, '', 10, 0);
        $this->assertTrue((bool) $p1, 'Product added with 10% product-level reduction upfront');

        $quote->applyGlobalDiscount(5, 0);
        $reloaded = new Quote($quote->id);
        // line: 200 - 10% = 180 (product-level discount = 20)
        // global: 5% * 180 = 9 -> total_products = 171
        $this->assertAlmostEqual(20, $reloaded->total_discount, 'Product-level discount unaffected by global discount');
        $this->assertAlmostEqual(9, $reloaded->total_global_discount, 'Global discount computed on post-product-reduction base');
        $this->assertAlmostEqual(171, $reloaded->total_products, 'Final total combines both reductions');
        echo "\n";
    }

    /**
     * Test 6: Invalid reduction inputs must be rejected, not silently clamped.
     */
    private function testInvalidReductionInputsRejected()
    {
        echo "[TEST 6] Invalid inputs rejected\n";
        echo "---\n";

        $quote = $this->newQuote();
        $p1 = QuoteProduct::addProductToQuote($quote->id, $this->product_id, 0, 1, 100, 120);

        $this->assertTrue(!QuoteProduct::updateReduction($p1->id, -10, 0), 'Negative percent rejected');
        $this->assertTrue(!$quote->applyGlobalDiscount(-5, 0), 'Negative global percent rejected');
        $this->assertTrue(!$quote->applyGlobalDiscount(0, -5), 'Negative global amount rejected');

        // Lock the quote as if it had been converted to an order, and verify writes are blocked
        $quote->id_order = 999999;
        $quote->update();
        $this->assertTrue(!QuoteProduct::updateReduction($p1->id, 10, 0), 'Product reduction rejected once quote is linked to an order');
        $this->assertTrue(!$quote->applyGlobalDiscount(10, 0), 'Global reduction rejected once quote is linked to an order');

        // Quote::update() intentionally preserves an existing order link; tests must restore their synthetic link directly.
        Db::getInstance()->update('quote', ['id_order' => 0], '`id_quote` = ' . (int) $quote->id);
        echo "\n";
    }
}

// Run tests
$test = new DiscountTest();
$test->run();

