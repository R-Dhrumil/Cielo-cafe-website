-- ==============================================================================
-- CIELO CAFE AND RESTRO - PRODUCTION MYSQL 8.0+ SCHEMA (InnoDB / utf8mb4)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Global Dynamic Settings (CMS, GST, Points, Cafe Info)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` MEDIUMTEXT NULL,
  `group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `type` VARCHAR(20) NOT NULL DEFAULT 'string',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Staff & Admin Users
CREATE TABLE IF NOT EXISTS `staff_users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'admin', 'staff', 'kitchen') NOT NULL DEFAULT 'staff',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Customer Patrons
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phone` VARCHAR(15) NOT NULL UNIQUE,
  `name` VARCHAR(100) NULL,
  `email` VARCHAR(150) NULL,
  `dob` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Cafe Tables & Secure QR Tokens
CREATE TABLE IF NOT EXISTS `tables` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `table_number` VARCHAR(60) NOT NULL UNIQUE,
  `qr_token` VARCHAR(64) NULL UNIQUE,
  `floor` VARCHAR(50) NULL DEFAULT 'Ground Floor',
  `capacity` INT UNSIGNED NOT NULL DEFAULT 4,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tables_token` (`qr_token`),
  INDEX `idx_tables_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Menu Categories
CREATE TABLE IF NOT EXISTS `menu_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(80) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `time_slot` VARCHAR(50) NULL DEFAULT 'all_day',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cat_sort` (`sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Menu Items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `price_paise` INT NOT NULL DEFAULT 0,
  `is_veg` TINYINT(1) NOT NULL DEFAULT 1,
  `tag` VARCHAR(50) NULL,
  `image_url` VARCHAR(255) NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_item_cat` (`category_id`, `is_available`),
  INDEX `idx_item_available` (`is_available`),
  FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Item Size & Style Variants
CREATE TABLE IF NOT EXISTS `item_variants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `price_paise` INT NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_variant_item` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Item Customization Add-ons
CREATE TABLE IF NOT EXISTS `item_addons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `extra_price_paise` INT NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_addon_item` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Customer Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(40) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NULL,
  `table_id` INT UNSIGNED NOT NULL,
  `status` ENUM('placed', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled') NOT NULL DEFAULT 'placed',
  `subtotal_paise` INT NOT NULL DEFAULT 0,
  `discount_paise` INT NOT NULL DEFAULT 0,
  `tax_paise` INT NOT NULL DEFAULT 0,
  `total_paise` INT NOT NULL DEFAULT 0,
  `payment_status` ENUM('pending', 'paid', 'refunded', 'failed') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('counter_cash', 'counter_upi', 'online_razorpay', 'points') NOT NULL DEFAULT 'counter_cash',
  `kitchen_notes` VARCHAR(255) NULL,
  `cancelled_reason` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_order_table` (`table_id`),
  INDEX `idx_order_user` (`user_id`),
  INDEX `idx_order_status` (`status`, `created_at`),
  FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Order Line Items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price_paise` INT NOT NULL DEFAULT 0,
  `total_price_paise` INT NOT NULL DEFAULT 0,
  `addons_json` JSON NULL,
  `item_note` VARCHAR(150) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_items_order` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Transaction Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `gateway` VARCHAR(50) NOT NULL DEFAULT 'counter',
  `gateway_order_id` VARCHAR(100) NULL,
  `gateway_payment_id` VARCHAR(100) NULL,
  `amount_paise` INT NOT NULL,
  `status` ENUM('pending', 'paid', 'refunded', 'failed') NOT NULL DEFAULT 'pending',
  `metadata_json` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_payments_order` (`order_id`),
  INDEX `idx_payments_gateway_id` (`gateway_payment_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Promotional Coupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(40) NOT NULL UNIQUE,
  `title` VARCHAR(100) NOT NULL,
  `type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'percentage',
  `value` DECIMAL(10, 2) NOT NULL,
  `min_order_paise` INT NOT NULL DEFAULT 0,
  `max_discount_paise` INT NOT NULL DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `usage_limit_total` INT NOT NULL DEFAULT 0,
  `usage_limit_per_user` INT NOT NULL DEFAULT 1,
  `audience` ENUM('all', 'first_time', 'loyal') NOT NULL DEFAULT 'all',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_coupons_code` (`code`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Coupon Redemptions (UNIQUE per coupon + order)
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `coupon_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `discount_paise` INT NOT NULL,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_coupon_order` (`coupon_id`, `order_id`),
  INDEX `idx_coupon_user` (`coupon_id`, `user_id`),
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Promotional Offers (Happy Hour, Combos, Banners)
CREATE TABLE IF NOT EXISTS `offers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `type` ENUM('bogo', 'combo', 'happy_hour', 'banner') NOT NULL,
  `rules_json` JSON NOT NULL,
  `schedule_json` JSON NULL,
  `banner_url` VARCHAR(255) NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_offers_active` (`is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Loyalty Reward Rules (Ticket Types)
CREATE TABLE IF NOT EXISTS `reward_rules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `trigger_type` ENUM('visit_milestone', 'order_spend', 'review_submit', 'birthday') NOT NULL,
  `config_json` JSON NOT NULL,
  `reward_json` JSON NOT NULL,
  `validity_days` INT NOT NULL DEFAULT 30,
  `is_repeatable` TINYINT(1) NOT NULL DEFAULT 1,
  `cooldown_days` INT NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_reward_trigger` (`trigger_type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Immutable Points Ledger (Insert-Only Ledger)
CREATE TABLE IF NOT EXISTS `points_ledger` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `points` INT NOT NULL,
  `type` ENUM('credit', 'debit') NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `order_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ledger_user` (`user_id`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Qualified Visits (Anti-Misuse: Max 1 Visit per User per Calendar Day)
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `visit_date` DATE NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `is_counted` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_user_visit_day` (`user_id`, `visit_date`),
  INDEX `idx_visits_lookup` (`user_id`, `is_counted`, `visit_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Awarded User Vouchers & Gifts
CREATE TABLE IF NOT EXISTS `user_rewards` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `rule_id` INT UNSIGNED NULL,
  `reward_type` VARCHAR(50) NOT NULL,
  `reward_value_json` JSON NOT NULL,
  `status` ENUM('active', 'used', 'expired', 'revoked') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NULL,
  `redeemed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_rewards_status` (`user_id`, `status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Verified Customer Reviews (1 Review per Completed Order)
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `review_text` TEXT NULL,
  `photo_url` VARCHAR(255) NULL,
  `status` ENUM('pending', 'approved', 'hidden') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_reviews_status` (`status`, `rating`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Staff & Admin Audit Log
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` INT UNSIGNED NULL,
  `details_json` JSON NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_staff` (`staff_id`, `created_at`),
  FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Customer OTP Verifications & Security Rate-Limiting
CREATE TABLE IF NOT EXISTS `otp_verifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phone` VARCHAR(15) NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_otp_phone` (`phone`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

