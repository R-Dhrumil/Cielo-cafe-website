<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\UserReward;

class CustomerAuthService
{
    /**
     * Clean and validate Indian 10-digit mobile number
     * Accepts: "9876543210", "+919876543210", "919876543210", "09876543210"
     */
    public static function validateIndianPhone(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    /**
     * Send OTP to customer with strict rate limiting & hashing
     */
    public static function sendOtp(string $phone, string $ip = '127.0.0.1'): array
    {
        $cleanPhone = self::validateIndianPhone($phone);
        if ($cleanPhone === null) {
            return [
                'success' => false,
                'error' => 'Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).',
            ];
        }

        $db = db();

        // 1. Rate Limiting: Max 3 OTP requests per 10 minutes per IP/Phone
        $windowStart = date('Y-m-d H:i:s', time() - 600); // 10 minutes ago
        $rateSql = "SELECT COUNT(*) FROM otp_verifications 
                    WHERE (phone = ? OR ip_address = ?) 
                      AND created_at >= ?";
        $recentCount = (int)$db->fetchColumn($rateSql, [$cleanPhone, $ip, $windowStart]);

        if ($recentCount >= 3) {
            return [
                'success' => false,
                'rate_limited' => true,
                'error' => 'Too many OTP requests. Please wait 10 minutes before requesting another code.',
            ];
        }

        // 2. Generate cryptographically strong 6-digit OTP
        $otp = (string)random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT);
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes validity

        // 3. Persist OTP in database
        $db->insert('otp_verifications', [
            'phone' => $cleanPhone,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'is_verified' => 0,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 4. Log OTP dispatch
        if (function_exists('cielo_log')) {
            cielo_log("OTP generated for +91 {$cleanPhone} (IP: {$ip})", 'info');
        }

        $provider = getenv('OTP_PROVIDER') ?: 'mock';
        $isDev = (getenv('APP_ENV') ?: 'development') === 'development';

        $response = [
            'success' => true,
            'message' => "Verification code sent to +91 {$cleanPhone}.",
            'phone' => $cleanPhone,
            'expires_in' => 300,
        ];

        // In dev / test / mock mode: provide mock_otp for automated testing and UI pre-fill
        if ($isDev || $provider === 'mock') {
            $response['mock_otp'] = $otp;
            $response['mode'] = 'dev_mock';
        }

        return $response;
    }

    /**
     * Verify OTP, manage attempt lockouts, find/create customer, and merge guest orders
     */
    public static function verifyOtp(string $phone, string $otp, string $ip = '127.0.0.1', ?string $name = null): array
    {
        $cleanPhone = self::validateIndianPhone($phone);
        if ($cleanPhone === null) {
            return [
                'success' => false,
                'error' => 'Invalid mobile number format.',
            ];
        }

        $cleanOtp = trim($otp);
        if (strlen($cleanOtp) !== 6 || !ctype_digit($cleanOtp)) {
            return [
                'success' => false,
                'error' => 'Please enter a valid 6-digit verification code.',
            ];
        }

        $db = db();

        // 1. Fetch latest active OTP request for this phone
        $record = $db->fetch(
            "SELECT * FROM otp_verifications 
             WHERE phone = ? AND is_verified = 0 
             ORDER BY id DESC LIMIT 1",
            [$cleanPhone]
        );

        if (!$record) {
            return [
                'success' => false,
                'error' => 'No pending OTP verification found. Please request a new code.',
            ];
        }

        // 2. Check 3-attempt lockout
        if ((int)$record['attempts'] >= 3) {
            return [
                'success' => false,
                'error' => 'Maximum verification attempts exceeded. Please request a fresh OTP.',
            ];
        }

        // 3. Check 5-minute expiration
        if (strtotime($record['expires_at']) < time()) {
            return [
                'success' => false,
                'error' => 'This verification code has expired. Please request a new one.',
            ];
        }

        // 4. Verify OTP hash
        if (!password_verify($cleanOtp, $record['otp_hash'])) {
            $newAttempts = (int)$record['attempts'] + 1;
            $db->query("UPDATE otp_verifications SET attempts = ? WHERE id = ?", [$newAttempts, $record['id']]);
            $remaining = max(0, 3 - $newAttempts);

            return [
                'success' => false,
                'error' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."
                    : 'Maximum verification attempts exceeded. Please request a fresh OTP.',
                'remaining_attempts' => $remaining,
            ];
        }

        // 5. Mark as verified
        $db->query("UPDATE otp_verifications SET is_verified = 1 WHERE id = ?", [$record['id']]);

        // 6. Find or automatically register customer in users table
        $user = User::findOrCreateByPhone($cleanPhone, $name);

        // 7. Auto-Merge Guest Orders: Link past unassigned orders to this verified user
        self::mergeGuestOrders($user['id'], $cleanPhone);

        // 8. Establish Customer Session
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('startCieloSession')) {
                startCieloSession();
            } else {
                @session_start();
            }
        }

        $_SESSION['cielo_customer_id'] = (int)$user['id'];
        $_SESSION['cielo_customer_phone'] = $cleanPhone;
        $_SESSION['cielo_customer_name'] = $user['name'] ?: 'Patron';

        return [
            'success' => true,
            'message' => 'Authentication successful! Welcome to Cielo Cafe & Restro.',
            'user' => $user,
        ];
    }

    /**
     * Merge guest orders into authenticated customer account
     */
    public static function mergeGuestOrders(int $userId, string $cleanPhone): int
    {
        $db = db();

        // Check if there are any orders where user_id is NULL or 0
        // and phone number is in metadata_json or notes
        $sql = "UPDATE orders 
                SET user_id = ? 
                WHERE (user_id IS NULL OR user_id = 0)
                  AND id IN (
                      SELECT order_id FROM payments 
                      WHERE metadata_json LIKE ?
                  )";
        $stmt = $db->query($sql, [$userId, "%{$cleanPhone}%"]);
        return $stmt->rowCount();
    }

    public static function user(): ?array
    {
        return self::getCurrentUser();
    }

    /**
     * Retrieve currently authenticated customer
     */
    public static function getCurrentUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('startCieloSession')) {
                startCieloSession();
            } else {
                @session_start();
            }
        }

        $userId = $_SESSION['cielo_customer_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $user = User::find((int)$userId);
        if (!$user) {
            unset($_SESSION['cielo_customer_id'], $_SESSION['cielo_customer_phone'], $_SESSION['cielo_customer_name']);
            return null;
        }

        return $user;
    }

    /**
     * Log out customer
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset(
            $_SESSION['cielo_customer_id'],
            $_SESSION['cielo_customer_phone'],
            $_SESSION['cielo_customer_name']
        );
    }

    /**
     * Update customer profile details (name, email, dob)
     */
    public static function updateProfile(int $userId, array $data): array
    {
        $allowed = [];
        if (isset($data['name'])) {
            $allowed['name'] = trim((string)$data['name']);
        }
        if (isset($data['email'])) {
            $allowed['email'] = trim((string)$data['email']);
        }
        if (isset($data['dob'])) {
            $allowed['dob'] = trim((string)$data['dob']);
        }

        if (empty($allowed)) {
            return ['success' => false, 'error' => 'No valid profile data provided.'];
        }

        $allowed['updated_at'] = date('Y-m-d H:i:s');
        User::updateById($userId, $allowed);

        if (isset($allowed['name']) && !empty($allowed['name'])) {
            $_SESSION['cielo_customer_name'] = $allowed['name'];
        }

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => User::find($userId),
        ];
    }

    /**
     * Calculate comprehensive loyalty and profile metrics for customer hub
     */
    public static function getProfileMetrics(int $userId): array
    {
        $db = db();
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        // 1. Authoritative Points Ledger Balance
        $pointsSql = "SELECT COALESCE(SUM(points), 0) FROM points_ledger WHERE user_id = ?";
        $pointsBalance = (int)$db->fetchColumn($pointsSql, [$userId]);

        // 2. Visits & Milestone Wheel
        $visitCountSql = "SELECT COUNT(*) FROM visits WHERE user_id = ? AND is_counted = 1";
        $lifetimeVisits = (int)$db->fetchColumn($visitCountSql, [$userId]);

        // 5-visit milestone cycle
        $cycleTarget = 5;
        $currentCycleProgress = $lifetimeVisits % $cycleTarget;
        $completedCycles = (int)floor($lifetimeVisits / $cycleTarget);

        // 3. Tier Status
        $tier = match (true) {
            $lifetimeVisits >= 15 || $pointsBalance >= 1500 => ['name' => 'Gold Connoisseur', 'badge' => '★ Gold', 'color' => '#D4AF37'],
            $lifetimeVisits >= 5 || $pointsBalance >= 500 => ['name' => 'Silver Regular', 'badge' => '◈ Silver', 'color' => '#8C92AC'],
            default => ['name' => 'Club Member', 'badge' => '✦ Member', 'color' => '#6B9AC4'],
        };

        // 4. Personal Coupon Wallet
        $today = date('Y-m-d');
        $coupons = $db->fetchAll(
            "SELECT c.* FROM coupons c 
             WHERE c.is_active = 1 
               AND c.start_date <= ? 
               AND c.end_date >= ?
             ORDER BY c.value DESC",
            [$today, $today]
        );

        $wallet = [];
        foreach ($coupons as $cpn) {
            // Check user usage cap
            $usedCount = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?",
                [$cpn['id'], $userId]
            );

            if ($usedCount < (int)$cpn['usage_limit_per_user']) {
                $wallet[] = [
                    'id' => (int)$cpn['id'],
                    'code' => $cpn['code'],
                    'title' => $cpn['title'],
                    'type' => $cpn['type'],
                    'value' => (float)$cpn['value'],
                    'discount_text' => $cpn['type'] === 'percentage' ? ((int)$cpn['value'] . '% OFF') : ('₹' . ((int)($cpn['value'] / 100)) . ' OFF'),
                    'min_order_paise' => (int)$cpn['min_order_paise'],
                    'min_order_rupees' => (int)($cpn['min_order_paise'] / 100),
                    'end_date' => $cpn['end_date'],
                    'remaining_uses' => (int)$cpn['usage_limit_per_user'] - $usedCount,
                ];
            }
        }

        // 5. Itemized Order History
        $orders = $db->fetchAll(
            "SELECT o.*, t.table_number 
             FROM orders o 
             LEFT JOIN tables t ON o.table_id = t.id 
             WHERE o.user_id = ? 
             ORDER BY o.id DESC 
             LIMIT 15",
            [$userId]
        );

        $orderHistory = [];
        foreach ($orders as $ord) {
            $items = $db->fetchAll(
                "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC",
                [$ord['id']]
            );
            $review = $db->fetch(
                "SELECT id, rating, review_text, photo_url, status, created_at FROM reviews WHERE order_id = ?",
                [$ord['id']]
            );
            $canReview = ($ord['status'] === 'completed' && empty($review));

            $orderHistory[] = [
                'id' => (int)$ord['id'],
                'order_number' => $ord['order_number'],
                'table_number' => $ord['table_number'] ?? 'Counter',
                'status' => $ord['status'],
                'payment_status' => $ord['payment_status'],
                'payment_method' => $ord['payment_method'],
                'total_paise' => (int)$ord['total_paise'],
                'total_formatted' => '₹' . number_format((int)$ord['total_paise'] / 100, 2),
                'created_at' => $ord['created_at'],
                'items_count' => count($items),
                'can_review' => $canReview,
                'review' => $review ? [
                    'id' => (int)$review['id'],
                    'rating' => (int)$review['rating'],
                    'review_text' => $review['review_text'],
                    'photo_url' => $review['photo_url'],
                    'status' => $review['status'],
                    'created_at' => $review['created_at'],
                ] : null,
                'items' => array_map(function ($it) {
                    return [
                        'item_name' => $it['item_name'],
                        'quantity' => (int)$it['quantity'],
                        'total_price_paise' => (int)$it['total_price_paise'],
                        'total_price_formatted' => '₹' . number_format((int)$it['total_price_paise'] / 100, 2),
                    ];
                }, $items),
            ];
        }

        return [
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'] ?: 'Patron',
                'phone' => $user['phone'],
                'email' => $user['email'] ?? '',
                'dob' => $user['dob'] ?? '',
                'member_since' => date('M Y', strtotime($user['created_at'])),
            ],
            'points' => [
                'balance' => $pointsBalance,
                'rupee_equivalent' => (int)($pointsBalance / 10), // 10 pts = ₹1
                'tier' => $tier,
            ],
            'visits' => [
                'lifetime' => $lifetimeVisits,
                'cycle_target' => $cycleTarget,
                'cycle_progress' => $currentCycleProgress,
                'remaining_for_milestone' => $cycleTarget - $currentCycleProgress,
                'completed_milestones' => $completedCycles,
                'next_milestone_reward' => 'Complimentary Artisanal Beverage & 100 Bonus Points',
            ],
            'rewards' => array_map(function ($rw) {
                $v = !empty($rw['reward_value_json']) ? json_decode($rw['reward_value_json'], true) : [];
                return [
                    'id' => (int)$rw['id'],
                    'type' => $rw['reward_type'],
                    'title' => $v['title'] ?? 'Milestone Reward Voucher',
                    'item_name' => $v['item_name'] ?? 'Complimentary Dish',
                    'discount_paise' => (int)($v['discount_paise'] ?? 24000),
                    'discount_rupees' => (float)(($v['discount_paise'] ?? 24000) / 100),
                    'status' => $rw['status'],
                    'expires_at' => $rw['expires_at'],
                    'created_at' => $rw['created_at'],
                ];
            }, UserReward::getActiveRewards($userId)),
            'coupons' => $wallet,
            'orders' => $orderHistory,
        ];
    }
}
