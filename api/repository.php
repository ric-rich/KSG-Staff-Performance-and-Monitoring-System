<?php
// api/repository.php

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../config/session.php';
    require_once '../config/database.php';

    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    }

    $database = new Database();
    $db = $database->connect();

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'get_completed_tasks':
            // Fetch tasks that are completed but NOT yet fully committed?
            // Or just all completed tasks. For simplicity, let's fetch all completed tasks.
            // In a real app, you might want to filter out ones that are "Archived" or already committed if that concept exists.
            $query = "SELECT t.task_id, t.title, t.completion_date, 
                             CONCAT(u.first_name, ' ', u.last_name) as user_name
                      FROM user_tasks t
                      JOIN users u ON t.user_id = u.user_id
                      WHERE t.status = 'completed'
                      AND NOT EXISTS (
                          SELECT 1 FROM repository_files rf WHERE rf.task_id = t.task_id
                      )
                      ORDER BY t.completion_date DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'tasks' => $tasks]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    error_log("Repository API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
ob_end_flush();
?>