-- ==============================================================================
-- CIELO CAFE AND RESTRO - SQLITE TEST & LOCAL SCHEMA
-- ==============================================================================

PRAGMA foreign_keys = ON;

-- 1. Global Dynamic Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `key` TEXT NOT NULL UNIQUE,
  `value` TEXT NULL,
  `group` TEXT NOT NULL DEFAULT 'general',
  `type` TEXT NOT NULL DEFAULT 'string',
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS `idx_settings_group` ON `settings` (`group`);

-- 2. Staff Users
CREATE TABLE IF NOT EXISTS `staff_users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `email` TEXT NOT NULL UNIQUE,
  `password_hash` TEXT NOT NULL,
  `role` TEXT NOT NULL DEFAULT 'staff',
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. Customers
CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `phone` TEXT NOT NULL UNIQUE,
  `name` TEXT NULL,
  `email` TEXT NULL,
  `dob` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS `idx_users_phone` ON `users` (`phone`);

-- 4. Tables
CREATE TABLE IF NOT EXISTS `tables` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `table_number` TEXT NOT NULL UNIQUE,
  `qr_token` TEXT NOT NULL UNIQUE,
  `capacity` INTEGER NOT NULL DEFAULT 4,
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS `idx_tables_token` ON `tables` (`qr_token`);

-- 5. Categories
CREATE TABLE IF NOT EXISTS `menu_categories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `slug` TEXT NOT NULL UNIQUE,
  `name` TEXT NOT NULL,
  `description` TEXT NULL,
  `sort_order` INTEGER NOT NULL DEFAULT 0,
  `time_slot` TEXT NULL DEFAULT 'all_day',
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 6. Menu Items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `category_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL,
  `slug` TEXT NOT NULL,
  `description` TEXT NULL,
  `price_paise` INTEGER NOT NULL DEFAULT 0,
  `is_veg` INTEGER NOT NULL DEFAULT 1,
  `tag` TEXT NULL,
  `image_url` TEXT NULL,
  `is_available` INTEGER NOT NULL DEFAULT 1,
  `sort_order` INTEGER NOT NULL DEFAULT 0,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_item_cat` ON `menu_items` (`category_id`, `is_available`);

-- 7. Item Variants
CREATE TABLE IF NOT EXISTS `item_variants` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `item_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL,
  `price_paise` INTEGER NOT NULL DEFAULT 0,
  `sort_order` INTEGER NOT NULL DEFAULT 0,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
);

-- 8. Item Addons
CREATE TABLE IF NOT EXISTS `item_addons` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `item_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL,
  `extra_price_paise` INTEGER NOT NULL DEFAULT 0,
  `is_available` INTEGER NOT NULL DEFAULT 1,
  `sort_order` INTEGER NOT NULL DEFAULT 0,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
);

-- 9. Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_number` TEXT NOT NULL UNIQUE,
  `user_id` INTEGER NULL,
  `table_id` INTEGER NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'placed',
  `subtotal_paise` INTEGER NOT NULL DEFAULT 0,
  `discount_paise` INTEGER NOT NULL DEFAULT 0,
  `tax_paise` INTEGER NOT NULL DEFAULT 0,
  `total_paise` INTEGER NOT NULL DEFAULT 0,
  `payment_status` TEXT NOT NULL DEFAULT 'pending',
  `payment_method` TEXT NOT NULL DEFAULT 'counter_cash',
  `kitchen_notes` TEXT NULL,
  `cancelled_reason` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_order_status` ON `orders` (`status`, `created_at`);

-- 10. Order Items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_id` INTEGER NOT NULL,
  `item_id` INTEGER NOT NULL,
  `variant_id` INTEGER NULL,
  `item_name` TEXT NOT NULL,
  `quantity` INTEGER NOT NULL DEFAULT 1,
  `unit_price_paise` INTEGER NOT NULL DEFAULT 0,
  `total_price_paise` INTEGER NOT NULL DEFAULT 0,
  `addons_json` TEXT NULL,
  `item_note` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
);

-- 11. Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_id` INTEGER NOT NULL,
  `gateway` TEXT NOT NULL DEFAULT 'counter',
  `gateway_order_id` TEXT NULL,
  `gateway_payment_id` TEXT NULL,
  `amount_paise` INTEGER NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'pending',
  `metadata_json` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
);

-- 12. Coupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `code` TEXT NOT NULL UNIQUE,
  `title` TEXT NOT NULL,
  `type` TEXT NOT NULL DEFAULT 'percentage',
  `value` REAL NOT NULL,
  `min_order_paise` INTEGER NOT NULL DEFAULT 0,
  `max_discount_paise` INTEGER NOT NULL DEFAULT 0,
  `start_date` TEXT NOT NULL,
  `end_date` TEXT NOT NULL,
  `usage_limit_total` INTEGER NOT NULL DEFAULT 0,
  `usage_limit_per_user` INTEGER NOT NULL DEFAULT 1,
  `audience` TEXT NOT NULL DEFAULT 'all',
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 13. Coupon Usage
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `coupon_id` INTEGER NOT NULL,
  `order_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `discount_paise` INTEGER NOT NULL,
  `used_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`coupon_id`, `order_id`),
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- 14. Offers
CREATE TABLE IF NOT EXISTS `offers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL,
  `type` TEXT NOT NULL,
  `rules_json` TEXT NOT NULL,
  `schedule_json` TEXT NULL,
  `banner_url` TEXT NULL,
  `priority` INTEGER NOT NULL DEFAULT 0,
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 15. Reward Rules
CREATE TABLE IF NOT EXISTS `reward_rules` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `trigger_type` TEXT NOT NULL,
  `config_json` TEXT NOT NULL,
  `reward_json` TEXT NOT NULL,
  `validity_days` INTEGER NOT NULL DEFAULT 30,
  `is_repeatable` INTEGER NOT NULL DEFAULT 1,
  `cooldown_days` INTEGER NOT NULL DEFAULT 30,
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 16. Points Ledger (Insert Only)
CREATE TABLE IF NOT EXISTS `points_ledger` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `points` INTEGER NOT NULL,
  `type` TEXT NOT NULL,
  `reason` TEXT NOT NULL,
  `order_id` INTEGER NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_ledger_user` ON `points_ledger` (`user_id`, `created_at`);

-- 17. Visits (Max 1 per user per calendar day)
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `visit_date` TEXT NOT NULL,
  `order_id` INTEGER NOT NULL,
  `is_counted` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`user_id`, `visit_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
);

-- 18. User Rewards
CREATE TABLE IF NOT EXISTS `user_rewards` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `rule_id` INTEGER NULL,
  `reward_type` TEXT NOT NULL,
  `reward_value_json` TEXT NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'active',
  `expires_at` TEXT NULL,
  `redeemed_at` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- 19. Reviews (1 per completed order)
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_id` INTEGER NOT NULL UNIQUE,
  `user_id` INTEGER NOT NULL,
  `rating` INTEGER NOT NULL,
  `review_text` TEXT NULL,
  `photo_url` TEXT NULL,
  `status` TEXT NOT NULL DEFAULT 'pending',
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- 20. Audit Log
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `staff_id` INTEGER NULL,
  `action` TEXT NOT NULL,
  `entity_type` TEXT NULL,
  `entity_id` INTEGER NULL,
  `details_json` TEXT NULL,
  `ip_address` TEXT NOT NULL DEFAULT '127.0.0.1',
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL
);

-- 21. Customer OTP Verifications & Security Rate-Limiting
CREATE TABLE IF NOT EXISTS `otp_verifications` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `phone` TEXT NOT NULL,
  `otp_hash` TEXT NOT NULL,
  `expires_at` TEXT NOT NULL,
  `attempts` INTEGER NOT NULL DEFAULT 0,
  `is_verified` INTEGER NOT NULL DEFAULT 0,
  `ip_address` TEXT NOT NULL DEFAULT '127.0.0.1',
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS `idx_otp_phone` ON `otp_verifications` (`phone`, `created_at`);

