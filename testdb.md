How You Can Test It Yourself
You can test the database anytime right from your terminal or command line:

1. Run the Full Test Suite
Open your terminal in the project root (/Users/apple/Desktop/Dhrumil/Prototype/Cielo cafe website) and run:

bash
php tests/test_module_0.php
You will see all 49 assertions execute with live pass/fail marks.

2. Inspect the Live SQLite Database Directly
By default, your 

.env
 uses DB_CONNECTION=sqlite (database/cielo.sqlite). You can query it directly in terminal:

Check all 20 tables:

bash
sqlite3 database/cielo.sqlite ".tables"
See the seeded cafe tables & QR tokens:

bash
sqlite3 -column -header database/cielo.sqlite "SELECT id, table_number, floor, qr_token FROM tables;"
See the seeded categories & dishes:

bash
sqlite3 -column -header database/cielo.sqlite "SELECT id, name, price_paise / 100.0 AS price_inr, is_veg FROM menu_items LIMIT 5;"
See dynamic CMS & GST settings:

bash
sqlite3 -column -header database/cielo.sqlite "SELECT key, value, group_name FROM settings WHERE group_name IN ('tax', 'loyalty');"
3. How to Test on MySQL (cPanel / XAMPP / MAMP)
When you are ready to test against a MySQL server:

Update your 

.env
:

ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cielo_cafe
DB_USERNAME=root
DB_PASSWORD=your_password
Run the migration to create all 20 tables in MySQL:

bash
php database/migrate.php
Seed the sample menu, tables, and settings:

bash
php database/seed.php
Run the test suite on MySQL:

bash
php tests/test_module_0.php
14:33
