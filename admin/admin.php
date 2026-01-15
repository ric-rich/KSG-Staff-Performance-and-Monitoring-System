<?php
/**
 * admin.php
 *
 * API endpoint for all administrative AJAX requests.
 * This centralizes API logic and improves security and maintenance.
 */

// Set content type to JSON for all responses
header('Content-Type: application/json');

// Start session to verify admin authentication
session_start();

// --- Security Check ---
// Immediately exit if the user is not a logged-in admin.
if (!isset($_SESSION['admin_id'])) {
    // Return a 401 Unauthorized error
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

// --- Includes ---
// Include necessary files like database connection and class definitions.
require_once '../includes/db_connection.php';
require_once '../classes/Admin.php'; // Assuming an Admin class exists

// --- Request Routing ---
// Determine the requested action from the query string.
$action = $_GET['action'] ?? '';

try {
    // Initialize the Admin class with the database connection
    $admin = new Admin($pdo);

    switch ($action) {
        case 'get_dashboard_stats':
            // This action is for the main dashboard auto-refresh
            $stats = $admin->getDashboardStats();
            echo json_encode(['status' => 'success', 'stats' => $stats]);
            break;

        case 'get_task_details':
            // --- This is the new logic to fetch task details ---
            $taskId = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);

            if (!$taskId || $taskId <= 0) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => 'A valid Task ID is required.']);
                break;
            }

            // Assuming you have a method in your Admin class to get task details
            $taskDetails = $admin->getTaskDetailsById($taskId);

            if (!$taskDetails) {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            } else {
                echo json_encode(['status' => 'success', 'task' => $taskDetails]);
            }
            break;

        // Add other admin actions here (e.g., get_user_activity)

        default:
            // Handle unknown actions
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Invalid API action specified.']);
            break;
    }
} catch (PDOException $e) {
    // Log the detailed database error for debugging
    error_log("API Database Error: " . $e->getMessage());
    // Return a generic server error to the client
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'A server error occurred. Please try again later.']);
}

exit();