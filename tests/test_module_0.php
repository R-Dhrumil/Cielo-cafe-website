<?php
/**
 * Cielo Cafe and Restro - Module 0 Automated Test Suite
 * Verifies Architecture, Database, Models, Security, Money, and Loyalty Rules
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Money;
use App\Core\Security;
use App\Models\Setting;
use App\Models\StaffUser;
use App\Models\User;
use App\Models\Table;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\PointsLedger;
use App\Models\Visit;
use App\Models\Coupon;
use App\Services\SettingsService;

class Module0TestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function assert(string $description, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            echo "  ✔ [PASS] {$description}\n";
            $this->passed++;
        } else {
            echo "  ✖ [FAIL] {$description} " . ($detail ? "({$detail})" : "") . "\n";
            $this->failed++;
        }
    }

    public function run(): void
    {
        echo "========================================================\n";
        echo " Cielo Cafe & Restro - Module 0 Verification Test Suite\n";
        echo "========================================================\n\n";

        $this->testDatabaseAndSchema();
        $this->testMoneyCalculations();
        $this->testSecurityAndTokens();
        $this->testSettingsAndDynamicCms();
        $this->testPointsLedgerImmutability();
        $this->testDailyVisitConstraint();
        $this->testTransactionAtomicity();

        echo "\n--------------------------------------------------------\n";
        echo "Test Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "--------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testDatabaseAndSchema(): void
    {
        echo "Test Group 1: Database Connection & Schema Tables\n";

        $db = Database::getInstance();
        $this->assert("Database connection established", $db->getPdo() instanceof PDO);

        $tables = [
            'settings', 'staff_users', 'users', 'tables', 'menu_categories',
            'menu_items', 'item_variants', 'item_addons', 'orders', 'order_items',
            'payments', 'coupons', 'coupon_usage', 'offers', 'reward_rules',
            'points_ledger', 'visits', 'user_rewards', 'reviews', 'audit_log'
        ];

        foreach ($tables as $t) {
            if ($db->isSqlite()) {
                $count = (int)$db->fetchColumn("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name = :t", ['t' => $t]);
            } else {
                $count = (int)$db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t", ['t' => $t]);
            }
            $this->assert("Table '{$t}' exists in database", $count === 1);
        }
        echo "\n";
    }

    private function testMoneyCalculations(): void
    {
        echo "Test Group 2: Money & Paise Integer Calculations\n";

        $this->assert("Rupees to Paise conversion (240 -> 24000)", Money::toPaise(240) === 24000);
        $this->assert("Decimal Rupees to Paise (240.50 -> 24050)", Money::toPaise(240.50) === 24050);
        $this->assert("Paise to Rupees conversion (24050 -> 240.50)", Money::toRupees(24050) === 240.50);
        $this->assert("Money format integer (24000 -> ₹ 240)", Money::format(24000) === '₹ 240');
        $this->assert("Money format float (24050 -> ₹ 240.50)", Money::format(24050) === '₹ 240.50');

        // Tax calculation in Paise (5% of ₹500 = ₹25 -> 2500 paise)
        $tax = Money::calculateTax(50000, 5.0);
        $this->assert("Tax calculation (5% of 50000 paise = 2500 paise)", $tax === 2500);

        // Percentage Discount calculation (10% of 50000 paise = 5000 paise)
        $disc = Money::calculateDiscount(50000, 'percentage', 10.0, 10000);
        $this->assert("Discount calculation (10% of 50000 = 5000 paise)", $disc === 5000);

        // Cap enforcement (10% of 150000 = 15000, capped at 10000 paise)
        $cappedDisc = Money::calculateDiscount(150000, 'percentage', 10.0, 10000);
        $this->assert("Discount max cap enforcement (15000 capped at 10000 paise)", $cappedDisc === 10000);
        echo "\n";
    }

    private function testSecurityAndTokens(): void
    {
        echo "Test Group 3: Security, CSRF, and QR Tokens\n";

        $token1 = Security::generateTableToken(16);
        $token2 = Security::generateTableToken(16);
        $this->assert("Table QR token length is 16", strlen($token1) === 16);
        $this->assert("Tokens are cryptographically unique", $token1 !== $token2);

        $csrf = Security::csrfToken();
        $this->assert("CSRF token generated", !empty($csrf) && strlen($csrf) === 64);
        $this->assert("CSRF token verification succeeds with valid token", Security::verifyCsrfToken($csrf) === true);
        $this->assert("CSRF token verification fails with invalid token", Security::verifyCsrfToken('invalid_token') === false);

        $password = "CieloSecret2026!";
        $hash = Security::hashPassword($password);
        $this->assert("Password hashed with BCRYPT", str_starts_with($hash, '$2y$'));
        $this->assert("Password verify succeeds with valid password", Security::verifyPassword($password, $hash) === true);
        $this->assert("Password verify fails with wrong password", Security::verifyPassword("WrongPassword", $hash) === false);
        echo "\n";
    }

    private function testSettingsAndDynamicCms(): void
    {
        echo "Test Group 4: Dynamic Settings & CMS Engine\n";

        Setting::set('test_string_key', 'Hello Cielo', 'test', 'string');
        $this->assert("Setting::set and get string", Setting::get('test_string_key') === 'Hello Cielo');

        Setting::set('test_int_key', 42, 'test', 'int');
        $this->assert("Setting type casting int", Setting::get('test_int_key') === 42);

        Setting::set('test_json_key', ['tag' => 'luxury', 'rating' => 5], 'test', 'json');
        $jsonVal = Setting::get('test_json_key');
        $this->assert("Setting type casting json/array", is_array($jsonVal) && $jsonVal['tag'] === 'luxury');

        $gst = SettingsService::getGstRates();
        $this->assert("SettingsService GST rates configured (CGST = 2.5%, SGST = 2.5%)", $gst['cgst'] === 2.5 && $gst['sgst'] === 2.5);

        $loyalty = SettingsService::getLoyaltyConfig();
        $this->assert("SettingsService Loyalty rules configured (10 points per ₹100)", $loyalty['points_per_100_spent'] === 10);
        echo "\n";
    }

    private function testPointsLedgerImmutability(): void
    {
        echo "Test Group 5: Immutable Points Ledger & Balance Logic\n";

        $testUser = User::findOrCreateByPhone('9999900001', 'Test Patron');
        $userId = (int)$testUser['id'];

        $initialBalance = PointsLedger::getBalance($userId);

        PointsLedger::credit($userId, 100, 'Order #101 reward');
        $afterCredit = PointsLedger::getBalance($userId);
        $this->assert("Points credit adds to ledger balance (+100)", $afterCredit === $initialBalance + 100);

        PointsLedger::debit($userId, 40, 'Redeemed ₹40 on bill');
        $afterDebit = PointsLedger::getBalance($userId);
        $this->assert("Points debit subtracts from ledger balance (-40)", $afterDebit === $initialBalance + 60);

        // Verify ledger immutability
        $immutableExceptionThrown = false;
        try {
            PointsLedger::updateById(1, ['points' => 999]);
        } catch (RuntimeException $e) {
            $immutableExceptionThrown = true;
        }
        $this->assert("Ledger throws exception on update attempt (immutable rule)", $immutableExceptionThrown);
        echo "\n";
    }

    private function testDailyVisitConstraint(): void
    {
        echo "Test Group 6: Anti-Misuse 1 Visit Per Calendar Day\n";

        $testUser = User::findOrCreateByPhone('9999900002', 'Visit Tester');
        $userId = (int)$testUser['id'];

        // Clean previous test visits and test orders for this user
        db()->delete('visits', '`user_id` = :uid', ['uid' => $userId]);
        db()->delete('orders', '`user_id` = :uid', ['uid' => $userId]);

        $orderId = (int)Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $userId,
            'table_id' => 1,
            'status' => 'completed',
            'subtotal_paise' => 20000,
            'discount_paise' => 0,
            'tax_paise' => 1000,
            'total_paise' => 21000,
        ]);

        $loggedFirst = Visit::logVisit($userId, $orderId, true);
        $this->assert("First visit of day successfully logged", $loggedFirst === true);

        // Attempt second visit on the same calendar day
        $loggedSecond = Visit::logVisit($userId, $orderId, true);
        $this->assert("Second visit on same calendar day rejected (1 visit/day rule)", $loggedSecond === false);

        $windowCount = Visit::countVisitsInWindow($userId, 30);
        $this->assert("Qualified visits in 30-day window equals 1", $windowCount === 1);
        echo "\n";
    }

    private function testTransactionAtomicity(): void
    {
        echo "Test Group 7: Database Transaction Atomicity\n";

        $db = Database::getInstance();
        $testUser = User::findOrCreateByPhone('9999900003', 'Transaction Tester');
        $userId = (int)$testUser['id'];
        $balanceBefore = PointsLedger::getBalance($userId);

        // Transaction that intentionally fails midway
        $failedCaught = false;
        try {
            $db->transaction(function() use ($userId) {
                PointsLedger::credit($userId, 50, 'Temporary credit');
                // Simulate unexpected failure
                throw new Exception("Intentional failure midway through transaction");
            });
        } catch (Exception $e) {
            $failedCaught = true;
        }

        $balanceAfter = PointsLedger::getBalance($userId);
        $this->assert("Transaction rolled back on error, points remain unchanged", $failedCaught && ($balanceAfter === $balanceBefore));
        echo "\n";
    }
}

// Execute the test suite
$suite = new Module0TestSuite();
$suite->run();
