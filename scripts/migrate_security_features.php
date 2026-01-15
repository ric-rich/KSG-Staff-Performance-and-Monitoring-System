<?php
// scripts/migrate_security_features.php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    echo "Starting security migration...\n";

    // 1. Update Users Table
    $columns = [
        "ADD COLUMN failed_login_attempts INT DEFAULT 0",
        "ADD COLUMN locked_until DATETIME DEFAULT NULL",
        "ADD COLUMN password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($columns as $col) {
        try {
            $conn->exec("ALTER TABLE users $col");
            echo "Added column to users: $col\n";
        } catch (PDOException $e) {
            // Column likely exists
            echo "Skipping users column (might exist): $col\n";
        }
    }

    // 2. Update Admins Table
    foreach ($columns as $col) {
        try {
            $conn->exec("ALTER TABLE admins $col");
            echo "Added column to admins: $col\n";
        } catch (PDOException $e) {
            // Column likely exists
            echo "Skipping admins column (might exist): $col\n";
        }
    }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>