<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();

    echo "<h3>Database Schema Update</h3>";
    echo "<pre>";

    // Fix Users Table
    echo "Updating 'users' table...\n";
    $users_columns = [
        "ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0",
        "ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($users_columns as $col) {
        try {
            // "IF NOT EXISTS" syntax for ADD COLUMN works in MariaDB 10.2.1+ and MySQL 8.0.29+
            // If running older versions, we might need a different approach, but let's try this first
            // or use a check.
            // A safer cross-version way is to try-catch the ADD COLUMN without IF NOT EXISTS, 
            // but let's assume a relatively modern XAMPP.

            // Actually, XAMPP usually has MariaDB which supports IF NOT EXISTS.
            $sql = "ALTER TABLE users " . $col;
            $db->exec($sql);
            echo "Executed: $sql\n";
        } catch (PDOException $e) {
            // Ignore "Duplicate column name" errors (Code 42S21)
            if ($e->getCode() == '42S21') {
                echo "Column already exists (skipped): $col\n";
            } else {
                // If syntax error (maybe older version), try without IF NOT EXISTS
                if (strpos($col, 'IF NOT EXISTS') !== false) {
                    $col_stripped = str_replace('IF NOT EXISTS ', '', $col);
                    try {
                        $sql = "ALTER TABLE users " . $col_stripped;
                        $db->exec($sql);
                        echo "Executed (retry): $sql\n";
                    } catch (PDOException $e2) {
                        if ($e2->getCode() == '42S21') {
                            echo "Column already exists (skipped): $col_stripped\n";
                        } else {
                            echo "Error adding column to users: " . $e2->getMessage() . "\n";
                        }
                    }
                } else {
                    echo "Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    // Fix Admins Table
    echo "Updating 'admins' table...\n";
    $admins_columns = [
        "ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0",
        "ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($admins_columns as $col) {
        try {
            $sql = "ALTER TABLE admins " . $col;
            $db->exec($sql);
            echo "Executed: $sql\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') {
                echo "Column already exists (skipped): $col\n";
            } else {
                if (strpos($col, 'IF NOT EXISTS') !== false) {
                    $col_stripped = str_replace('IF NOT EXISTS ', '', $col);
                    try {
                        $sql = "ALTER TABLE admins " . $col_stripped;
                        $db->exec($sql);
                        echo "Executed (retry): $sql\n";
                    } catch (PDOException $e2) {
                        if ($e2->getCode() == '42S21') {
                            echo "Column already exists (skipped): $col_stripped\n";
                        } else {
                            echo "Error adding column to admins: " . $e2->getMessage() . "\n";
                        }
                    }
                } else {
                    echo "Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    echo "\nDatabase schema update completed successfully.\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<h3>Critical Error</h3><pre>" . $e->getMessage() . "</pre>";
}
