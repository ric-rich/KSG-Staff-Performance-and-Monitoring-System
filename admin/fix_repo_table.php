<?php
// admin/fix_repo_table.php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();

    // 1. Check if 'repository_files' table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'repository_files'")->rowCount() > 0;

    if (!$tableExists) {
        echo "Table 'repository_files' does not exist. Creating it...<br>";
        $sql = "CREATE TABLE IF NOT EXISTS repository_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            description TEXT,
            file_size INT,
            file_type VARCHAR(100),
            uploaded_by INT,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        echo "Table created.<br>";
    }

    // 2. Check and Add 'task_id' column
    $colCheck = $db->query("SHOW COLUMNS FROM repository_files LIKE 'task_id'");
    if ($colCheck->rowCount() == 0) {
        $db->exec("ALTER TABLE repository_files ADD COLUMN task_id INT DEFAULT NULL");
        echo "Added column 'task_id'.<br>";
    } else {
        echo "Column 'task_id' already exists.<br>";
    }

    // 3. Check and Add 'user_id' column
    $colCheck = $db->query("SHOW COLUMNS FROM repository_files LIKE 'user_id'");
    if ($colCheck->rowCount() == 0) {
        $db->exec("ALTER TABLE repository_files ADD COLUMN user_id INT DEFAULT NULL");
        echo "Added column 'user_id'.<br>";
    } else {
        echo "Column 'user_id' already exists.<br>";
    }

    // 4. Check and add foreign keys if needed (optional but good practice)
    // We'll skip strict FK constraints for now to avoid issues if referenced data is missing,
    // but having the columns is the critical part for the query to work.

    echo "Database fix completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>