<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Module 4 Verification Test Suite
 *
 * Verifies:
 * 1. Staff Authentication & Role-Based Access Control
 * 2. Kitchen Display System (KDS) Lifecycle & Status Transitions
 * 3. POS Order Billing, Settlement & Auto Loyalty Crediting
 * 4. Thermal Printing Templates (58mm/80mm KOT and Customer Bill)
 * 5. Dynamic CMS & Live Settings Engine
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\SettingsService;
use App\Models\User;
use App\Models\StaffUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Setting;
use App\Models\PointsLedger;
use App\Core\Money;

class Module4TestSuite
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
        echo " Cielo Cafe & Restro - Module 4 Verification Test Suite \n";
        echo "========================================================\n\n";

        $this->testStaffAuthenticationAndRoles();
        $this->testKitchenDisplayLifecycle();
        $this->testPosBillingAndLoyaltySettlement();
        $this->testThermalPrintingTemplates();
        $this->testDynamicCmsEngine();

        echo "\n--------------------------------------------------------\n";
        echo sprintf("Test Results: %d Passed, %d Failed\n", $this->passed, $this->failed);
        echo "--------------------------------------------------------\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testStaffAuthenticationAndRoles(): void
    {
        echo "● 1. Staff Authentication & Role-Based Access Control:\n";

        // Successful Admin Authentication (Owner role)
        $adminLogin = AuthService::attempt('admin@cielocafe.com', 'CieloAdmin@2026');
        $this->assert("Admin login succeeds with seeded credentials", $adminLogin !== null);
        $this->assert("Admin role is recognized as 'owner'", ($adminLogin['role'] ?? '') === 'owner');

        // Role verification (Owner possesses all administrative permissions)
        $this->assert("Owner satisfies single role check ['owner']", AuthService::hasRole('owner'));
        $this->assert("Owner satisfies multi-role check ['owner', 'admin']", AuthService::hasRole(['owner', 'admin']));
        $this->assert("Owner satisfies privilege access for management", AuthService::hasRole('admin'));

        // Kitchen Staff Authentication
        $kitchenLogin = AuthService::attempt('kitchen@cielocafe.com', 'Kitchen@2026');
        $this->assert("Kitchen staff login succeeds with seeded credentials", $kitchenLogin !== null);
        $this->assert("Kitchen role is recognized as 'kitchen'", ($kitchenLogin['role'] ?? '') === 'kitchen');
        $this->assert("Kitchen satisfies 'kitchen' role check", AuthService::hasRole('kitchen'));
        $this->assert("Kitchen fails owner/admin privilege check", !AuthService::hasRole(['owner', 'admin']));

        // Invalid Password Rejection
        $badLogin = AuthService::attempt('admin@cielocafe.com', 'WrongPassword123');
        $this->assert("Authentication fails for incorrect password", $badLogin === null);

        // Non-existent Staff User Rejection
        $ghostLogin = AuthService::attempt('ghost@cielocafe.com', 'SomePassword');
        $this->assert("Authentication fails for non-existent staff user", $ghostLogin === null);

        // Logout
        AuthService::logout();
        $this->assert("Staff session cleared on logout", AuthService::user() === null);
    }

    private function testKitchenDisplayLifecycle(): void
    {
        echo "\n● 2. Kitchen Display System (KDS) Lifecycle & Transitions:\n";

        // Create test customer & table
        $user = User::findOrCreateByPhone('+919777788888', 'KDS Test User');
        $table = Table::findBy('table_number', 'T-04') ?? Table::find(1);

        // Create a new order placed by customer
        $orderNum = Order::generateOrderNumber();
        $orderId = Order::create([
            'order_number' => $orderNum,
            'table_id' => $table['id'],
            'user_id' => $user['id'],
            'status' => 'placed',
            'payment_status' => 'unpaid',
            'payment_method' => 'counter_cash',
            'subtotal_paise' => 45000,
            'tax_paise' => 2250,
            'discount_paise' => 0,
            'total_paise' => 47250,
            'kitchen_notes' => 'Customer requested less spice on pasta',
        ]);
        $order = Order::find($orderId);

        OrderItem::create([
            'order_id' => $order['id'],
            'item_id' => 1,
            'item_name' => 'Truffle Mushroom Risotto',
            'quantity' => 2,
            'unit_price_paise' => 22500,
            'total_price_paise' => 45000,
            'item_note' => 'Extra creamy, al dente'
        ]);

        $this->assert("Test order created with status 'placed'", $order['status'] === 'placed');

        // Transition: placed -> preparing
        $prepSuccess = Order::updateById($order['id'], ['status' => 'preparing']);
        $orderFresh = Order::find($order['id']);
        $this->assert("KDS transition to 'preparing' succeeds", $prepSuccess && $orderFresh['status'] === 'preparing');

        // Transition: preparing -> ready
        $readySuccess = Order::updateById($order['id'], ['status' => 'ready']);
        $orderFresh = Order::find($order['id']);
        $this->assert("KDS transition to 'ready' succeeds", $readySuccess && $orderFresh['status'] === 'ready');

        // Transition: ready -> completed
        $doneSuccess = Order::updateById($order['id'], ['status' => 'completed']);
        $orderFresh = Order::find($order['id']);
        $this->assert("KDS transition to 'completed' succeeds", $doneSuccess && $orderFresh['status'] === 'completed');
    }

    private function testPosBillingAndLoyaltySettlement(): void
    {
        echo "\n● 3. POS Order Billing, Settlement & Auto Loyalty Crediting:\n";

        // Create a customer with initial points
        $userPhone = '+919999988888';
        $user = User::findOrCreateByPhone($userPhone, 'POS Settlement User');
        $initialPoints = PointsLedger::getBalance($user['id']);

        $orderNum = Order::generateOrderNumber();
        $subtotal = 60000; // ₹600.00
        $cgst = Money::calculateTax($subtotal, 2.5); // ₹15.00
        $sgst = Money::calculateTax($subtotal, 2.5); // ₹15.00
        $total = $subtotal + $cgst + $sgst;          // ₹630.00

        $orderId = Order::create([
            'order_number' => $orderNum,
            'table_id' => 1,
            'user_id' => $user['id'],
            'status' => 'ready',
            'payment_status' => 'unpaid',
            'payment_method' => 'counter_upi',
            'subtotal_paise' => $subtotal,
            'tax_paise' => $cgst + $sgst,
            'discount_paise' => 0,
            'total_paise' => $total
        ]);
        $order = Order::find($orderId);

        $this->assert("Unsettled POS order created", $order['payment_status'] === 'unpaid');

        // Simulate Cash/UPI Settlement in admin/orders.php
        $loyaltyConfig = SettingsService::getLoyaltyConfig();
        $pointsEarned = 0;
        if (!empty($loyaltyConfig['is_enabled']) && $subtotal >= 10000) {
            $pointsPer100 = (int)($loyaltyConfig['points_per_100_spent'] ?? 10);
            $pointsEarned = (int)floor(($subtotal / 10000) * $pointsPer100);
        }

        $this->assert("Earned loyalty points computed correctly (₹600 spent = 60 points)", $pointsEarned === 60);

        // Perform settlement update
        Order::updateById($order['id'], [
            'payment_status' => 'paid',
            'status' => 'completed'
        ]);

        if ($pointsEarned > 0 && !empty($user['id'])) {
            PointsLedger::credit(
                (int)$user['id'],
                $pointsEarned,
                "Earned on Order #{$orderNum}",
                (int)$order['id']
            );
        }

        $newPoints = PointsLedger::getBalance($user['id']);
        $this->assert("Customer points balance credited by 60 points in immutable ledger", $newPoints === ($initialPoints + 60));

        $orderAfter = Order::find($order['id']);
        $this->assert("Order marked as paid and completed", $orderAfter['payment_status'] === 'paid' && $orderAfter['status'] === 'completed');

        // Verify ledger record
        $ledgerEntries = PointsLedger::where('`user_id` = :uid', ['uid' => $user['id']]);
        $hasLedger = false;
        foreach ($ledgerEntries as $entry) {
            if ((int)$entry['order_id'] === (int)$order['id'] && (int)$entry['points'] === 60) {
                $hasLedger = true;
                break;
            }
        }
        $this->assert("Points ledger entry recorded for order", $hasLedger);
    }

    private function testThermalPrintingTemplates(): void
    {
        echo "\n● 4. Thermal Printing Templates (58mm/80mm KOT & Bill):\n";

        // Create sample order with item
        $orderNum = Order::generateOrderNumber();
        $orderId = Order::create([
            'order_number' => $orderNum,
            'table_id' => 1,
            'status' => 'placed',
            'payment_status' => 'unpaid',
            'payment_method' => 'counter_cash',
            'subtotal_paise' => 38000,
            'tax_paise' => 1900,
            'discount_paise' => 0,
            'total_paise' => 39900,
            'kitchen_notes' => 'No onions please'
        ]);
        $order = Order::find($orderId);

        OrderItem::create([
            'order_id' => $order['id'],
            'item_id' => 1,
            'item_name' => 'Classic Margherita Pizza (12 inch)',
            'quantity' => 1,
            'unit_price_paise' => 38000,
            'total_price_paise' => 38000,
            'item_note' => 'Extra crisp crust'
        ]);

        // 1. Test KOT template rendering via output buffering
        $_GET['order_id'] = $order['id'];
        
        ob_start();
        include __DIR__ . '/../kitchen/print_kot.php';
        $kotOutput = ob_get_clean();

        $this->assert("KOT thermal template renders HTML", strpos($kotOutput, 'KITCHEN TICKET') !== false);
        $this->assert("KOT includes order number", strpos($kotOutput, $orderNum) !== false);
        $this->assert("KOT includes item name", strpos($kotOutput, 'Classic Margherita Pizza') !== false);
        $this->assert("KOT includes item special instructions", strpos($kotOutput, 'Extra crisp crust') !== false);
        $this->assert("KOT includes customer notes", strpos($kotOutput, 'No onions please') !== false);

        // 2. Test Customer Bill template rendering via output buffering
        ob_start();
        include __DIR__ . '/../admin/print_bill.php';
        $billOutput = ob_get_clean();

        $this->assert("Customer Bill thermal template renders HTML", strpos($billOutput, 'TAX INVOICE') !== false || strpos($billOutput, 'CIELO') !== false);
        $this->assert("Bill contains cafe GSTIN", strpos($billOutput, 'GSTIN') !== false);
        $this->assert("Bill contains itemized subtotal & tax", strpos($billOutput, '399.00') !== false);
        $this->assert("Bill contains UPI QR code section", strpos($billOutput, 'SCAN & PAY VIA UPI') !== false || strpos($billOutput, 'UPI ID') !== false);
    }

    private function testDynamicCmsEngine(): void
    {
        echo "\n● 5. Dynamic CMS & Live Settings Engine:\n";

        // Save original settings so we can cleanly restore them
        $origHeroName = Setting::get('cms_hero_name', 'Cielo');
        $origCgst = Setting::get('gst_cgst_percent', 2.5);
        $origLoyaltyPoints = Setting::get('loyalty_points_per_100', 10);

        // Update settings dynamically
        Setting::set('cms_hero_name', 'Cielo Artisanal Cafe', 'system', 'string');
        Setting::set('gst_cgst_percent', 3.5, 'system', 'float');
        Setting::set('loyalty_points_per_100', 15, 'system', 'int');

        // Verify retrieval via Setting model
        $this->assert("Dynamic hero name saved and retrieved", Setting::get('cms_hero_name') === 'Cielo Artisanal Cafe');
        $this->assert("Dynamic CGST percent saved as float", (float)Setting::get('gst_cgst_percent') === 3.5);
        $this->assert("Dynamic loyalty rate saved as int", (int)Setting::get('loyalty_points_per_100') === 15);

        // Verify retrieval via SettingsService facade
        $gstRates = SettingsService::getGstRates();
        $this->assert("SettingsService reflects updated CGST (3.5%)", (float)$gstRates['cgst'] === 3.5);

        $loyaltyConfig = SettingsService::getLoyaltyConfig();
        $this->assert("SettingsService reflects updated loyalty rate (15 pts / ₹100)", (int)$loyaltyConfig['points_per_100_spent'] === 15);

        // Restore originals
        Setting::set('cms_hero_name', $origHeroName, 'system', 'string');
        Setting::set('gst_cgst_percent', $origCgst, 'system', 'float');
        Setting::set('loyalty_points_per_100', $origLoyaltyPoints, 'system', 'int');

        $this->assert("Original CMS and financial settings restored cleanly", (float)SettingsService::getGstRates()['cgst'] === (float)$origCgst);
    }
}

// Run test suite
$suite = new Module4TestSuite();
$suite->run();
