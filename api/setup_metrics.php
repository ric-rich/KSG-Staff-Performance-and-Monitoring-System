<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();

    // Create site_metrics table
    $sql = "CREATE TABLE IF NOT EXISTS site_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_key VARCHAR(50) NOT NULL UNIQUE,
        metric_value TEXT,
        metric_label VARCHAR(100),
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "site_metrics table created or exists.\n";

    // Create site_metric_files table
    $sql = "CREATE TABLE IF NOT EXISTS site_metric_files (
        file_id INT AUTO_INCREMENT PRIMARY KEY,
        metric_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (metric_id) REFERENCES site_metrics(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "site_metric_files table created or exists.\n";

    // Seed default metrics if empty
    $stmt = $db->query("SELECT COUNT(*) FROM site_metrics");
    if ($stmt->fetchColumn() == 0) {
        $defaults = [
            ['logo', '', 'Company Logo', 'Upload the main company logo.'],
            ['banner', '', 'Homepage Banner', 'Upload the banner image for the homepage.'],
            ['footer_text', '© 2024 Company Name', 'Footer Text', 'Text to display in the footer.'],
            ['contact_email', 'info@example.com', 'Contact Email', 'Main contact email address.']
        ];

        $insert = $db->prepare("INSERT INTO site_metrics (metric_key, metric_value, metric_label, description) VALUES (?, ?, ?, ?)");
        foreach ($defaults as $row) {
            $insert->execute($row);
        }
        echo "Default metrics seeded.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>