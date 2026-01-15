<?php
// Prevent any output before JSON
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../config/session.php';
    require_once '../config/database.php';
    // The controller includes the necessary classes (Admin, User, Task)
    require_once '../classes/AdminApiController.php';

    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    }

    $database = new Database();
    $db = $database->connect();

    // Get action
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Get input data
    $rawInput = file_get_contents("php://input");
    $data = null;
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        }
    }
    // Fallback
    if ($data === null && !empty($_POST)) {
        if (isset($_POST['templates']) && is_string($_POST['templates'])) {
            $maybe = json_decode($_POST['templates']);
            if (json_last_error() === JSON_ERROR_NONE)
                $data = $maybe;
        }
        if ($data === null)
            $data = (object) $_POST;
    }

    $controller = new AdminApiController($db, $_SESSION['admin_id']);
    $controller->handle($action, $data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>