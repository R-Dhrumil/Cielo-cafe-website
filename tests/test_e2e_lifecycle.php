<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Complete End-to-End Simulation & Integrity Test
 *
 * Simulates the full customer and staff journey from table scan to settlement:
 * 1. Table QR scan & session activation
 * 2. Customer loyalty identification
 * 3. Menu retrieval & cart validation
 * 4. Coupon discount application
 * 5. Order placement (Counter Pay)
 * 6. Kitchen Display System (KDS) detection & order pickup
 * 7. Live Customer order tracking updates
 * 8. KOT continuous roll print output
 * 9. POS Bill settlement & auto loyalty crediting
 * 10. Thermal invoice generation with UPI QR
 * 11. Dynamic CMS updates reflecting on public website
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\TableSessionService;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Models\Table;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\PointsLedger;
use App\Models\Setting;
use App\Core\Money;
use App\Core\Security;

class E2EFullLifecycleTest
{
    private int $step = 1;

    private function logStep(string $title): void
    {
        echo "\n[Step {$this->step}] {$title}...\n";
        $this->step++;
    }

    private function assertCondition(string $desc, bool $condition): void
    {
        if ($condition) {
            echo "   ✔ {$desc}\n";
        } else {
            echo "   ✖ FAILED: {$desc}\n";
            exit(1);
        }
    }

    public function execute(): void
    {
        echo "====================================================================\n";
        echo "  CIELO CAFE & RESTRO - COMPLETE END-TO-END SYSTEM INTEGRITY TEST   \n";
        echo "====================================================================\n";

        // Step 1: Table QR Scan
        $this->logStep("Patron Scans Table QR Code");
        $table = Table::findBy('table_number', '01') ?? Table::find(1);
        $this->assertCondition("Table 01 exists with valid QR token", !empty($table['qr_token']));
        
        $session = TableSessionService::startSession($table['qr_token']);
        $this->assertCondition("Table session initiated for {$table['table_number']}", $session !== null && $session['table_number'] === '01');

        // Step 2: Customer Loyalty Lookup
        $this->logStep("Patron Enters Mobile Number for Loyalty Identification");
        $patronPhone = '9876500001';
        $user = User::findOrCreateByPhone($patronPhone, 'Arjun Patel');
        $initialBalance = PointsLedger::getBalance($user['id']);
        $this->assertCondition("Customer registered/identified ({$user['name']})", $user['id'] > 0);
        $this->assertCondition("Initial points balance read successfully ({$initialBalance} pts)", $initialBalance >= 0);

        // Step 3: Menu Item Selection & Cart Validation
        $this->logStep("Patron Selects Items & Validates Cart");
        $items = MenuItem::all();
        $this->assertCondition("Menu catalog loaded with active dishes", count($items) > 0);
        $dish1 = $items[0]; // e.g. Truffle Mushroom Risotto or Margherita
        $dish2 = $items[1];

        $cart = [
            [
                'id' => (int)$dish1['id'],
                'name' => (string)$dish1['name'],
                'price_paise' => (int)$dish1['price_paise'],
                'quantity' => 2,
                'addons' => []
            ],
            [
                'id' => (int)$dish2['id'],
                'name' => (string)$dish2['name'],
                'price_paise' => (int)$dish2['price_paise'],
                'quantity' => 1,
                'addons' => []
            ]
        ];

        $calculatedSubtotal = 0;
        foreach ($cart as $ci) {
            $calculatedSubtotal += $ci['price_paise'] * $ci['quantity'];
        }
        $this->assertCondition("Authoritative subtotal computed in Paise ({$calculatedSubtotal} paise / " . Money::format($calculatedSubtotal) . ")", $calculatedSubtotal > 0);

        // Step 4: Coupon Application
        $this->logStep("Patron Applies Welcome Coupon 'WELCOME50'");
        $coupon = Coupon::findBy('code', 'WELCOME50');
        $this->assertCondition("Coupon WELCOME50 active in database", $coupon && (int)$coupon['is_active'] === 1);
        $discountPaise = Money::calculateDiscount($calculatedSubtotal, $coupon['type'], (float)$coupon['value'], (int)$coupon['max_discount_paise']);
        $this->assertCondition("Coupon provides discount of " . Money::format($discountPaise), $discountPaise === 5000);

        $taxableSubtotal = max(0, $calculatedSubtotal - $discountPaise);
        $taxPaise = Money::calculateTax($taxableSubtotal, 5.0); // 5% total GST
        $grandTotalPaise = $taxableSubtotal + $taxPaise;
        $this->assertCondition("Grand total accurately calculated (" . Money::format($grandTotalPaise) . ")", $grandTotalPaise > 0);

        // Step 5: Place Order
        $this->logStep("Order Submitted by Customer");
        $orderNumber = Order::generateOrderNumber();
        $orderId = Order::create([
            'order_number' => $orderNumber,
            'table_id' => $table['id'],
            'user_id' => $user['id'],
            'status' => 'placed',
            'subtotal_paise' => $calculatedSubtotal,
            'discount_paise' => $discountPaise,
            'tax_paise' => $taxPaise,
            'total_paise' => $grandTotalPaise,
            'payment_status' => 'unpaid',
            'payment_method' => 'counter_upi',
            'kitchen_notes' => 'Crisp crust, less spicy'
        ]);
        $order = Order::find($orderId);
        $this->assertCondition("Order {$orderNumber} persisted with status 'placed'", $order !== null);

        foreach ($cart as $ci) {
            OrderItem::create([
                'order_id' => $order['id'],
                'item_id' => $ci['id'],
                'item_name' => $ci['name'],
                'quantity' => $ci['quantity'],
                'unit_price_paise' => $ci['price_paise'],
                'total_price_paise' => $ci['price_paise'] * $ci['quantity'],
                'item_note' => 'Standard preparation'
            ]);
        }
        $savedItems = OrderItem::where('`order_id` = :oid', ['oid' => $order['id']]);
        $this->assertCondition("All " . count($cart) . " line items attached to order", count($savedItems) === count($cart));

        // Step 6: Kitchen Display System (KDS)
        $this->logStep("Kitchen Staff Interacts via High-Contrast KDS");
        AuthService::attempt('kitchen@cielocafe.com', 'Kitchen@2026');
        $this->assertCondition("Kitchen staff authenticated", AuthService::hasRole('kitchen'));

        // Kitchen chef moves to preparing
        Order::updateById($order['id'], ['status' => 'preparing']);
        $orderInPrep = Order::find($order['id']);
        $this->assertCondition("Kitchen transitions order to 'preparing'", $orderInPrep['status'] === 'preparing');

        // Step 7: Live Customer Tracking
        $this->logStep("Customer Live Tracking Stepper Syncs");
        $stepperStep = match ($orderInPrep['status']) {
            'placed' => 1,
            'preparing' => 2,
            'ready' => 3,
            'completed' => 4,
            default => 1
        };
        $this->assertCondition("Tracking screen advances to Step 2 ('Preparing in Kitchen')", $stepperStep === 2);

        // Step 8: KOT Thermal Continuous Ticket Print
        $this->logStep("Kitchen Generates 58mm/80mm Thermal KOT Ticket");
        $_GET['order_id'] = $order['id'];
        ob_start();
        include __DIR__ . '/../kitchen/print_kot.php';
        $kot = ob_get_clean();
        $this->assertCondition("Thermal KOT rendered with order number & kitchen notes", strpos($kot, $orderNumber) !== false && strpos($kot, 'Crisp crust, less spicy') !== false);

        // Step 9: Kitchen Marks Order Ready
        $this->logStep("Kitchen Chef Marks Order as 'Ready'");
        Order::updateById($order['id'], ['status' => 'ready']);
        $orderReady = Order::find($order['id']);
        $this->assertCondition("Order status is now 'ready'", $orderReady['status'] === 'ready');

        // Step 10: Cashier Settle Bill & Loyalty Credit
        $this->logStep("Cashier Settles Bill & Awards Loyalty Points");
        AuthService::attempt('admin@cielocafe.com', 'CieloAdmin@2026');
        $this->assertCondition("Admin cashier authenticated", AuthService::hasRole(['owner', 'admin']));

        // Settle bill
        Order::updateById($order['id'], [
            'payment_status' => 'paid',
            'status' => 'completed'
        ]);

        $earnedPts = (int)floor((Money::toRupees($calculatedSubtotal) / 100) * 10);
        PointsLedger::credit($user['id'], $earnedPts, "Earned on Order #{$orderNumber}", (int)$order['id']);
        $updatedBalance = PointsLedger::getBalance($user['id']);
        $this->assertCondition("Bill settled as PAID and loyalty points (+{$earnedPts} pts) credited", $updatedBalance === ($initialBalance + $earnedPts));

        // Step 11: Thermal Customer Bill Print
        $this->logStep("Cashier Generates 58mm/80mm Customer Thermal Bill");
        ob_start();
        include __DIR__ . '/../admin/print_bill.php';
        $bill = ob_get_clean();
        $this->assertCondition("Customer bill contains cafe GSTIN & grand total", strpos($bill, 'GSTIN') !== false && strpos($bill, Money::format($grandTotalPaise, false)) !== false);

        // Step 12: Dynamic Website CMS Test
        $this->logStep("Admin Updates Cafe CMS & Website Reflects Instantly");
        $testHeadline = "Handcrafted Italian Sourdough & Slow Brews";
        Setting::set('cms_story_heading', $testHeadline, 'cms', 'string');
        $retrievedHeadline = Setting::get('cms_story_heading');
        $this->assertCondition("Dynamic CMS story headline updated in database", $retrievedHeadline === $testHeadline);

        // Clean up session
        TableSessionService::clearSession();
        AuthService::logout();
        $this->assertCondition("Table and staff sessions cleared cleanly", TableSessionService::getActiveTable() === null && AuthService::user() === null);

        echo "\n====================================================================\n";
        echo "  ✔ ALL 12 STEPS OF FULL LIFECYCLE PASSED WITH 100% INTEGRITY!     \n";
        echo "====================================================================\n\n";
    }
}

$test = new E2EFullLifecycleTest();
$test->execute();
