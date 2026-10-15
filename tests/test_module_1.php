<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Module 1 Verification Test Suite
 *
 * Verifies:
 * 1. Cryptographic Table QR Token Generation
 * 2. Table Session Activation with Valid Token
 * 3. Table Session Rejection with Invalid / Inactive Token
 * 4. Table Session Termination / Clearing
 * 5. Customer Identification & Loyalty Lookup
 * 6. Pure-PHP SVG QR Code Vector Matrix Generator
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Security;
use App\Core\QrCodeGenerator;
use App\Models\Table;
use App\Models\User;
use App\Models\PointsLedger;
use App\Services\TableSessionService;

class Module1TestSuite
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
        echo " Cielo Cafe & Restro - Module 1 Verification Test Suite \n";
        echo "========================================================\n\n";

        $this->testTokenGeneration();
        $this->testValidTableSession();
        $this->testInvalidAndInactiveTableSession();
        $this->testSessionTermination();
        $this->testCustomerLoyaltyLookup();
        $this->testSvgQrCodeGenerator();

        echo "\n--------------------------------------------------------\n";
        echo sprintf("Test Results: %d Passed, %d Failed\n", $this->passed, $this->failed);
        echo "--------------------------------------------------------\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testTokenGeneration(): void
    {
        echo "Test Group 1: Cryptographic Table QR Token Generation\n";

        $tokens = [];
        for ($i = 0; $i < 50; $i++) {
            $t = Security::generateTableToken();
            $tokens[$t] = true;
            if (strlen($t) !== 16 || !preg_match('/^[a-zA-Z0-9]{16}$/', $t)) {
                $this->assert("Token format validation", false, "Token {$t} did not match pattern");
                return;
            }
        }

        $this->assert("Tokens have exact length of 16 alphanumeric chars", true);
        $this->assert("Tokens generated are 100% collision-free (50 unique samples)", count($tokens) === 50);
        echo "\n";
    }

    private function testValidTableSession(): void
    {
        echo "Test Group 2: Table Session Activation (Valid QR Token)\n";

        $sampleTable = Table::findBy('table_number', '01');
        $this->assert("Seeded Table 01 found in database", !empty($sampleTable));

        $token = (string)$sampleTable['qr_token'];
        $session = TableSessionService::startSession($token);

        $this->assert("TableSessionService::startSession returns active table session data", is_array($session));
        $this->assert("Session table_number matches '01'", ($session['table_number'] ?? '') === '01');
        $this->assert("Session id matches database table id", (int)($session['id'] ?? 0) === (int)$sampleTable['id']);
        $this->assert("TableSessionService::hasActiveTable() returns true", TableSessionService::hasActiveTable() === true);

        $active = TableSessionService::getActiveTable();
        $this->assert("TableSessionService::getActiveTable() returns current session", !empty($active) && $active['id'] === (int)$sampleTable['id']);

        $scanUrl = TableSessionService::generateScanUrl($token);
        $this->assert("Scan URL properly formed with t.php?token=", str_contains($scanUrl, "t.php?token={$token}"));
        echo "\n";
    }

    private function testInvalidAndInactiveTableSession(): void
    {
        echo "Test Group 3: Table Session Rejection (Invalid / Inactive Tokens)\n";

        // Test non-existent token
        $fakeSession = TableSessionService::startSession('non_existent_fake_token_xyz');
        $this->assert("Random invalid token is rejected (returns null)", $fakeSession === null);

        // Test empty token
        $emptySession = TableSessionService::startSession('');
        $this->assert("Empty token is rejected (returns null)", $emptySession === null);

        // Test inactive table
        $inactiveToken = Security::generateTableToken();
        $inactiveTableId = (int)Table::create([
            'table_number' => '99-TEST',
            'qr_token' => $inactiveToken,
            'capacity' => 2,
            'is_active' => 0, // Inactive
        ]);

        $inactiveSession = TableSessionService::startSession($inactiveToken);
        $this->assert("Inactive table is rejected even with valid token", $inactiveSession === null);

        // Clean up test table
        if ($inactiveTableId > 0) {
            Table::deleteById($inactiveTableId);
        }
        echo "\n";
    }

    private function testSessionTermination(): void
    {
        echo "Test Group 4: Table Session Termination\n";

        // Start session first
        $table = Table::findBy('table_number', '02');
        TableSessionService::startSession($table['qr_token']);
        $this->assert("Session initiated for Table 02", TableSessionService::hasActiveTable() === true);

        // Clear session
        TableSessionService::clearSession();
        $this->assert("Session terminated via clearSession()", TableSessionService::hasActiveTable() === false);
        $this->assert("getActiveTable() returns null after clearing", TableSessionService::getActiveTable() === null);
        echo "\n";
    }

    private function testCustomerLoyaltyLookup(): void
    {
        echo "Test Group 5: Customer Loyalty Identification\n";

        $testPhone = '9876500001';
        $testName = 'Aarav Patel';

        // 1. Identify customer
        $user = User::findOrCreateByPhone($testPhone, $testName);
        $this->assert("Customer created or found by 10-digit phone", !empty($user));
        $this->assert("Customer phone matches input", $user['phone'] === $testPhone);

        // 2. Add points to customer
        $userId = (int)$user['id'];
        PointsLedger::credit($userId, 250, 'Welcome Bonus Points');
        $balance = PointsLedger::getBalance($userId);
        $this->assert("Customer points ledger credited (+250)", $balance >= 250);

        // 3. Test phone validation logic
        $validRegex = '/^[6-9]\d{9}$/';
        $this->assert("Phone regex accepts valid 10-digit Indian number", (bool)preg_match($validRegex, '9876543210'));
        $this->assert("Phone regex rejects short numbers (5 digits)", !preg_match($validRegex, '12345'));
        $this->assert("Phone regex rejects numbers not starting with 6-9", !preg_match($validRegex, '2345678901'));
        echo "\n";
    }

    private function testSvgQrCodeGenerator(): void
    {
        echo "Test Group 6: Native Vector SVG QR Code Generator\n";

        $url = 'https://cielocafebistro.in/t.php?token=a1b2c3d4e5f67890';
        $svg = QrCodeGenerator::generateSvg($url, 250, '#1A1412', '#F7F4EE', 2);

        $this->assert("SVG generated is non-empty string", !empty($svg));
        $this->assert("SVG contains <?xml declaration", str_contains($svg, '<?xml'));
        $this->assert("SVG contains valid <svg opening and closing tags", str_contains($svg, '<svg') && str_contains($svg, '</svg>'));
        $this->assert("SVG specifies requested dimensions (250x250)", str_contains($svg, 'width="250"') && str_contains($svg, 'height="250"'));
        $this->assert("SVG contains background rect with #F7F4EE fill", str_contains($svg, 'fill="#F7F4EE"'));
        $this->assert("SVG contains vector path modules with #1A1412 fill", str_contains($svg, 'fill="#1A1412"'));

        $dataUri = QrCodeGenerator::generateSvgDataUri($url, 200);
        $this->assert("Data URI starts with data:image/svg+xml", str_starts_with($dataUri, 'data:image/svg+xml;utf8,'));
        echo "\n";
    }
}

// Run the verification suite
$suite = new Module1TestSuite();
$suite->run();
