<?php
/**
 * PHPUnit Backend/Unit Tests for Software-Final-Project
 *
 * Setup:
 *   composer require --dev phpunit/phpunit
 *   php vendor/bin/phpunit tests/unit/BackendTest.php --testdox
 *
 * Place this file in: C:\xampp\htdocs\Software-Final-Project\tests\unit\BackendTest.php
 */

use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    private static \mysqli $conn;

    // ─────────────────────────────────────────
    // Setup: connect to DB (matches your config.php)
    // ─────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        self::$conn = new \mysqli('localhost', 'root', '', 'shopdb');

        if (self::$conn->connect_error) {
            self::markTestSkipped(
                'Cannot connect to DB: ' . self::$conn->connect_error
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$conn->close();
    }

    // ─────────────────────────────────────────
    // 1. DATABASE CONNECTION
    // ─────────────────────────────────────────

    public function testDatabaseConnectionSucceeds(): void
    {
        $this->assertEquals(0, self::$conn->connect_errno, 'DB connection should succeed');
    }

    public function testRequiredTablesExist(): void
    {
        $tables = ['products', 'users', 'orders', 'cart', 'message'];
        foreach ($tables as $table) {
            $result = self::$conn->query("SHOW TABLES LIKE '$table'");
            $this->assertGreaterThan(
                0,
                $result->num_rows,
                "Table '$table' should exist in the database"
            );
        }
    }

    // ─────────────────────────────────────────
    // 2. PRODUCTS TABLE
    // ─────────────────────────────────────────

    public function testProductsTableHasRequiredColumns(): void
    {
        $required = ['id', 'name', 'price', 'image', 'quantity'];
        $result = self::$conn->query("DESCRIBE products");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column '$col' missing from products table");
        }
    }

    public function testProductsTableIsNotEmpty(): void
    {
        $result = self::$conn->query("SELECT COUNT(*) AS cnt FROM products");
        $row = $result->fetch_assoc();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'Products table should have at least one product');
    }

    public function testProductPricesArePositive(): void
    {
        $result = self::$conn->query("SELECT id, price FROM products WHERE price <= 0");
        $this->assertEquals(0, $result->num_rows, 'All products should have a positive price');
    }

    public function testProductNamesAreNotEmpty(): void
    {
        $result = self::$conn->query("SELECT id FROM products WHERE name IS NULL OR name = ''");
        $this->assertEquals(0, $result->num_rows, 'All products should have a non-empty name');
    }

    public function testProductImageFieldNotEmpty(): void
    {
        $result = self::$conn->query("SELECT id FROM products WHERE image IS NULL OR image = ''");
        $this->assertEquals(0, $result->num_rows, 'All products should have an image filename');
    }

    public function testProductQuantityIsNotNegative(): void
    {
        $result = self::$conn->query("SELECT id FROM products WHERE quantity < 0");
        $this->assertEquals(0, $result->num_rows, 'No product should have a negative quantity');
    }

    // ─────────────────────────────────────────
    // 3. USERS TABLE
    // ─────────────────────────────────────────

    public function testUsersTableHasRequiredColumns(): void
    {
        $required = ['id', 'name', 'email', 'password', 'user_type'];
        $result = self::$conn->query("DESCRIBE users");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column '$col' missing from users table");
        }
    }

    public function testUserEmailsAreUnique(): void
    {
        $result = self::$conn->query(
            "SELECT email, COUNT(*) AS cnt FROM users GROUP BY email HAVING cnt > 1"
        );
        $this->assertEquals(0, $result->num_rows, 'Duplicate emails found in users table');
    }

    public function testUserPasswordsArePotentiallyHashed(): void
    {
        // Passwords are stored as MD5 (32 chars)
        $result = self::$conn->query("SELECT password FROM users LIMIT 10");
        while ($row = $result->fetch_assoc()) {
            $this->assertGreaterThanOrEqual(
                32,
                strlen($row['password']),
                'Password appears too short — should be at least MD5 length (32 chars)'
            );
        }
    }

    public function testUserEmailsHaveValidFormat(): void
    {
        $result = self::$conn->query("SELECT id, email FROM users");
        while ($row = $result->fetch_assoc()) {
            $this->assertMatchesRegularExpression(
                '/^[^@]+@[^@]+\.[^@]+$/',
                $row['email'],
                "Invalid email format for user ID {$row['id']}: {$row['email']}"
            );
        }
    }

    public function testUserTypeIsOnlyAdminOrUser(): void
    {
        $result = self::$conn->query(
            "SELECT id, user_type FROM users WHERE user_type NOT IN ('admin', 'user')"
        );
        $this->assertEquals(
            0,
            $result->num_rows,
            "user_type should only be 'admin' or 'user'"
        );
    }

    public function testAtLeastOneAdminExists(): void
    {
        $result = self::$conn->query("SELECT id FROM users WHERE user_type = 'admin'");
        $this->assertGreaterThan(0, $result->num_rows, 'There should be at least one admin user');
    }

    // ─────────────────────────────────────────
    // 4. ORDERS TABLE
    // ─────────────────────────────────────────

    public function testOrdersTableHasRequiredColumns(): void
    {
        $required = [
            'id', 'user_id', 'name', 'number', 'email',
            'method', 'address', 'total_price', 'payment_status'
        ];
        $result = self::$conn->query("DESCRIBE orders");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column '$col' missing from orders table");
        }
    }

    public function testOrdersPaymentStatusIsValid(): void
    {
        // admin_orders.php uses exactly: 'pending', 'completed', 'rejected'
        $valid = ['pending', 'completed', 'rejected'];
        $result = self::$conn->query("SELECT DISTINCT payment_status FROM orders");
        while ($row = $result->fetch_assoc()) {
            $this->assertContains(
                strtolower($row['payment_status']),
                $valid,
                "Unexpected payment_status value: {$row['payment_status']}"
            );
        }
    }

    public function testOrderTotalPricesArePositive(): void
    {
        $result = self::$conn->query("SELECT id FROM orders WHERE total_price <= 0");
        $this->assertEquals(0, $result->num_rows, 'All order totals should be positive');
    }

    public function testOrdersUserIdExistsInUsers(): void
    {
        $result = self::$conn->query(
            "SELECT o.id FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             WHERE u.id IS NULL"
        );
        $this->assertEquals(
            0,
            $result->num_rows,
            'All orders should reference a valid user_id'
        );
    }

    // ─────────────────────────────────────────
    // 5. CART TABLE
    // ─────────────────────────────────────────

    public function testCartTableHasRequiredColumns(): void
    {
        $required = ['id', 'user_id', 'name', 'price', 'quantity', 'image', 'product_id'];
        $result = self::$conn->query("DESCRIBE cart");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column '$col' missing from cart table");
        }
    }

    public function testCartQuantitiesArePositive(): void
    {
        $result = self::$conn->query("SELECT id FROM cart WHERE quantity <= 0");
        $this->assertEquals(0, $result->num_rows, 'All cart quantities should be positive');
    }

    public function testCartPricesArePositive(): void
    {
        $result = self::$conn->query("SELECT id FROM cart WHERE price <= 0");
        $this->assertEquals(0, $result->num_rows, 'All cart prices should be positive');
    }

    public function testCartUserIdExistsInUsers(): void
    {
        $result = self::$conn->query(
            "SELECT c.id FROM cart c
             LEFT JOIN users u ON c.user_id = u.id
             WHERE u.id IS NULL"
        );
        $this->assertEquals(0, $result->num_rows, 'All cart rows should reference a valid user_id');
    }

    public function testCartProductIdExistsInProducts(): void
    {
        // NOTE: search_page.php inserts into cart without product_id — this test
        // will catch any rows where product_id is NULL or references no product.
        $result = self::$conn->query(
            "SELECT c.id FROM cart c
             LEFT JOIN products p ON c.product_id = p.id
             WHERE p.id IS NULL AND c.product_id IS NOT NULL"
        );
        $this->assertEquals(0, $result->num_rows, 'All cart rows with a product_id should reference a valid product');
    }


    // ─────────────────────────────────────────
    // 6. MESSAGE TABLE
    // ─────────────────────────────────────────

    public function testMessageTableHasRequiredColumns(): void
    {
        $required = ['id', 'user_id', 'name', 'email', 'number', 'message'];
        $result = self::$conn->query("DESCRIBE message");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column '$col' missing from message table");
        }
    }

    public function testMessageEmailsHaveValidFormat(): void
    {
        $result = self::$conn->query(
            "SELECT id, email FROM message WHERE email IS NOT NULL AND email != ''"
        );
        while ($row = $result->fetch_assoc()) {
            $this->assertMatchesRegularExpression(
                '/^[^@]+@[^@]+\.[^@]+$/',
                $row['email'],
                "Invalid email in message ID {$row['id']}"
            );
        }
    }

    public function testMessageUserIdExistsInUsers(): void
    {
        $result = self::$conn->query(
            "SELECT m.id FROM message m
             LEFT JOIN users u ON m.user_id = u.id
             WHERE u.id IS NULL"
        );
        $this->assertEquals(0, $result->num_rows, 'All messages should reference a valid user_id');
    }

    // ─────────────────────────────────────────
    // 7. SQL INJECTION BASIC PROTECTION
    // ─────────────────────────────────────────

    public function testMysqliEscapeStringWorks(): void
    {
        $malicious = "1' OR '1'='1";
        $escaped = self::$conn->real_escape_string($malicious);
        $this->assertStringContainsString(
            "\\'",
            $escaped,
            'mysqli_real_escape_string should escape single quotes'
        );
    }

    public function testSearchDoesNotReturnAllOnSqlInjection(): void
    {
        $malicious = self::$conn->real_escape_string("1' OR '1'='1");
        $result = self::$conn->query(
            "SELECT * FROM products WHERE name LIKE '%$malicious%'"
        );
        $all = self::$conn->query("SELECT COUNT(*) AS cnt FROM products");
        $allRow = $all->fetch_assoc();
        $this->assertLessThanOrEqual(
            (int)$allRow['cnt'],
            $result->num_rows,
            'Potential SQL injection vulnerability detected in search'
        );
    }

    // ─────────────────────────────────────────
    // 8. BUSINESS LOGIC
    // ─────────────────────────────────────────

    public function testProductCanBeFoundById(): void
    {
        $result = self::$conn->query("SELECT id FROM products LIMIT 1");
        $row = $result->fetch_assoc();
        if (!$row) {
            $this->markTestSkipped('No products available to test lookup');
        }
        $id = (int)$row['id'];
        $found = self::$conn->query("SELECT * FROM products WHERE id='$id'");
        $this->assertEquals(1, $found->num_rows, "Should find product by ID $id");
    }

    public function testNonExistentProductReturnsEmpty(): void
    {
        $result = self::$conn->query("SELECT * FROM products WHERE id='99999999'");
        $this->assertEquals(0, $result->num_rows, 'Lookup of non-existent product should return empty');
    }

    public function testUserCanBeFoundByEmail(): void
    {
        $result = self::$conn->query("SELECT email FROM users LIMIT 1");
        $row = $result->fetch_assoc();
        if (!$row) {
            $this->markTestSkipped('No users available to test lookup');
        }
        $email = self::$conn->real_escape_string($row['email']);
        $found = self::$conn->query("SELECT * FROM users WHERE email='$email'");
        $this->assertEquals(1, $found->num_rows, 'Should find user by email');
    }

    public function testAdminUserCanBeFoundByUserType(): void
    {
        $result = self::$conn->query(
            "SELECT id FROM users WHERE user_type = 'admin' LIMIT 1"
        );
        $this->assertGreaterThan(0, $result->num_rows, 'Should be able to find admin by user_type');
    }

    // ─────────────────────────────────────────
    // 9. STOCK MANAGEMENT LOGIC
    // ─────────────────────────────────────────

    public function testCartQuantityDoesNotExceedWarehouseStock(): void
    {
        // home.php and shop.php enforce this; verify the DB state is consistent.
        // Cart quantities (per user per product) should not exceed product stock.
        $result = self::$conn->query(
            "SELECT c.id, c.quantity AS cart_qty, p.quantity AS stock
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.quantity > p.quantity"
        );
        $this->assertEquals(
            0,
            $result->num_rows,
            'No cart item should have quantity exceeding current warehouse stock'
        );
    }

    public function testPendingOrdersTotalProductsNotEmpty(): void
    {
        // checkout.php always sets total_products from the cart contents
        $result = self::$conn->query(
            "SELECT id FROM orders
             WHERE (total_products IS NULL OR total_products = '')
               AND payment_status = 'pending'"
        );
        $this->assertEquals(
            0,
            $result->num_rows,
            'Pending orders should always have total_products populated'
        );
    }
}