<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->connect();

try {
    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM admins LIKE 'notification_preferences'");
    if ($check->rowCount() == 0) {
        // Add column
        $db->exec("ALTER TABLE admins ADD COLUMN notification_preferences JSON DEFAULT NULL AFTER profile_picture");
        echo "Column 'notification_preferences' added to 'admins' table.\n";
    } else {
        echo "Column 'notification_preferences' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>