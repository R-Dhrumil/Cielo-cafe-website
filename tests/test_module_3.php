<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Module 3 Verification Test Suite
 *
 * Verifies:
 * 1. Order Creation & Sequential Numbering
 * 2. Itemized Snapshot Persistence & Add-ons
 * 3. Coupon Usage Tracking & Per-User Limits
 * 4. Idempotent Daily Visit Integration
 * 5. Payment Methods & Kitchen Status Transitions
 * 6. Live Order Status Payload & Stepper Mapping
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Money;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Table;
use App\Models\MenuItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Visit;

class Module3TestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    private function assert(string $label, bool $condition, string $details = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  ✔ [PASS] {$label}\n";
        } else {
            $this->failed++;
            echo "  ✖ [FAIL] {$label}" . ($details !== '' ? " ({$details})" : "") . "\n";
        }
    }

    public function run(): void
    {
        echo "\n========================================================\n";
        echo " Cielo Cafe & Restro - Module 3 Verification Test Suite \n";
        echo "========================================================\n\n";

        $this->testOrderCreationAndNumbering();
        $this->testItemizedSnapshotPersistence();
        $this->testCouponUsageTracking();
        $this->testDailyVisitIntegration();
        $this->testPaymentMethodsAndTransitions();
        $this->testLiveOrderStatusPayload();

        echo "\n--------------------------------------------------------\n";
        echo sprintf("Test Results: %d Passed, %d Failed\n", $this->passed, $this->failed);
        echo "--------------------------------------------------------\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testOrderCreationAndNumbering(): void
    {
        echo "Test Group 1: Order Creation & Numbering\n";

        $num1 = Order::generateOrderNumber();
        $num2 = Order::generateOrderNumber();

        $this->assert("Order number starts with 'CIELO-'", str_starts_with($num1, 'CIELO-'));
        $this->assert("Order number has format CIELO-YYMMDD-XXXX", (bool)preg_match('/^CIELO-\d{6}-[A-Z0-9]{4}$/', $num1));
        $this->assert("Consecutive order numbers are unique", $num1 !== $num2);

        $testUser = User::findOrCreateByPhone('9876500301', 'Order Number Tester');
        $orderId = (int)Order::create([
            'order_number' => $num1,
            'user_id' => $testUser['id'],
            'table_id' => 1,
            'status' => 'placed',
            'subtotal_paise' => 50000,
            'discount_paise' => 5000,
            'tax_paise' => 2250,
            'total_paise' => 47250,
            'payment_status' => 'pending',
            'payment_method' => 'counter_cash',
        ]);

        $this->assert("Order created with database ID > 0", $orderId > 0);
        $fetched = Order::find($orderId);
        $this->assert("Fetched order matches created order_number", $fetched['order_number'] === $num1);
        $this->assert("Order status initialized to 'placed'", $fetched['status'] === 'placed');
        echo "\n";
    }

    private function testItemizedSnapshotPersistence(): void
    {
        echo "Test Group 2: Itemized Snapshot Persistence\n";

        $order = Order::findBy('status', 'placed');
        $orderId = (int)$order['id'];

        $lineId = OrderItem::create([
            'order_id' => $orderId,
            'item_id' => 1,
            'variant_id' => 2,
            'item_name' => 'Cielo Citrus Cold Brew (Large Grande)',
            'unit_price_paise' => 28000,
            'addons_json' => json_encode([['name' => 'Extra Espresso Shot', 'price_paise' => 5000]]),
            'quantity' => 2,
            'total_price_paise' => 66000,
            'item_note' => 'Extra cold please',
        ]);

        $this->assert("Line item created with ID > 0", (int)$lineId > 0);

        $lineItem = OrderItem::find((int)$lineId);
        $this->assert("Line item snapshots dish and variant name", str_contains($lineItem['item_name'], 'Cold Brew'));
        $this->assert("Line item preserves addons JSON", str_contains((string)$lineItem['addons_json'], 'Extra Espresso Shot'));
        $this->assert("Line item preserves kitchen note", $lineItem['item_note'] === 'Extra cold please');
        echo "\n";
    }

    private function testCouponUsageTracking(): void
    {
        echo "Test Group 3: Coupon Usage Tracking & Limits\n";

        $coupon = Coupon::findValidCoupon('WELCOME50');
        $this->assert("Coupon WELCOME50 found for usage test", !empty($coupon));

        $testUser = User::findOrCreateByPhone('9876500302', 'Coupon Usage Tester');
        $userId = (int)$testUser['id'];

        // Clean previous usages
        db()->delete('coupon_usage', '`user_id` = :uid', ['uid' => $userId]);

        $orderId = (int)Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $userId,
            'table_id' => 1,
            'status' => 'placed',
            'subtotal_paise' => 30000,
            'discount_paise' => 5000,
            'tax_paise' => 1250,
            'total_paise' => 26250,
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon['id'],
            'order_id' => $orderId,
            'user_id' => $userId,
            'discount_paise' => 5000,
            'used_at' => date('Y-m-d H:i:s'),
        ]);

        $usageCount = CouponUsage::getUserUsageCount((int)$coupon['id'], $userId);
        $this->assert("Coupon usage successfully recorded for user", $usageCount === 1);
        $this->assert("User usage count matches max allowed (1 use)", $usageCount >= (int)$coupon['max_uses_per_user']);
        echo "\n";
    }

    private function testDailyVisitIntegration(): void
    {
        echo "Test Group 4: Daily Visit Integration & Anti-Misuse\n";

        $testUser = User::findOrCreateByPhone('9876500303', 'Visit Integration Tester');
        $userId = (int)$testUser['id'];

        db()->delete('visits', '`user_id` = :uid', ['uid' => $userId]);

        $order1 = (int)Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $userId,
            'table_id' => 1,
            'status' => 'completed',
            'total_paise' => 20000,
        ]);

        $firstVisit = Visit::logVisit($userId, $order1, true);
        $this->assert("First order of the day qualifies for visit point", $firstVisit === true);

        $order2 = (int)Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $userId,
            'table_id' => 1,
            'status' => 'completed',
            'total_paise' => 15000,
        ]);

        $secondVisit = Visit::logVisit($userId, $order2, true);
        $this->assert("Subsequent order on same calendar day does not double count visit", $secondVisit === false);
        echo "\n";
    }

    private function testPaymentMethodsAndTransitions(): void
    {
        echo "Test Group 5: Payment Methods & Status Transitions\n";

        $testUser = User::findOrCreateByPhone('9876500304', 'Payment Tester');
        $orderId = (int)Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $testUser['id'],
            'table_id' => 2,
            'status' => 'placed',
            'payment_status' => 'pending',
            'payment_method' => 'counter_upi',
            'total_paise' => 35000,
        ]);

        $order = Order::find($orderId);
        $this->assert("Payment method stored as 'counter_upi'", $order['payment_method'] === 'counter_upi');
        $this->assert("Payment status initialized as 'pending'", $order['payment_status'] === 'pending');

        // Transition through kitchen lifecycle
        Order::updateById($orderId, ['status' => 'preparing']);
        $this->assert("Transition to 'preparing' succeeded", Order::find($orderId)['status'] === 'preparing');

        Order::updateById($orderId, ['status' => 'ready']);
        $this->assert("Transition to 'ready' succeeded", Order::find($orderId)['status'] === 'ready');

        Order::updateById($orderId, ['status' => 'completed', 'payment_status' => 'paid']);
        $final = Order::find($orderId);
        $this->assert("Transition to 'completed' & 'paid' succeeded", $final['status'] === 'completed' && $final['payment_status'] === 'paid');
        echo "\n";
    }

    private function testLiveOrderStatusPayload(): void
    {
        echo "Test Group 6: Live Order Status Stepper Mapping\n";

        $orderNumber = Order::generateOrderNumber();
        $testUser = User::findOrCreateByPhone('9876500305', 'Stepper Tester');

        $orderId = (int)Order::create([
            'order_number' => $orderNumber,
            'user_id' => $testUser['id'],
            'table_id' => 1,
            'status' => 'preparing',
            'total_paise' => 45000,
        ]);

        $fetched = Order::findBy('order_number', $orderNumber);
        $this->assert("Order retrieved by order_number", !empty($fetched));

        $statusMap = [
            'placed' => 1,
            'preparing' => 2,
            'ready' => 3,
            'completed' => 4,
        ];

        $stepIndex = $statusMap[$fetched['status']] ?? 1;
        $this->assert("Status 'preparing' correctly maps to Step 2 (Kitchen Preparing)", $stepIndex === 2);
        echo "\n";
    }
}

// Execute Module 3 Test Suite
$suite = new Module3TestSuite();
$suite->run();
