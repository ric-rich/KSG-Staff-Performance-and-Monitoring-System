<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Verify admin access
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->connect();

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'list_all_users':
            // Get all users with their tasks and uploads
            $query = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.department,
                     COUNT(DISTINCT t.task_id) as total_tasks,
                     COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.task_id END) as completed_tasks,
                     COUNT(DISTINCT up.upload_id) as total_uploads
                     FROM users u
                     LEFT JOIN user_tasks t ON u.user_id = t.user_id
                     LEFT JOIN task_uploads up ON t.task_id = up.task_id
                     GROUP BY u.user_id
                     ORDER BY u.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'users' => $users
            ]);
            break;

        case 'get_user_details':
            if (!isset($_GET['user_id'])) {
                throw new Exception('User ID is required');
            }

            $userId = $_GET['user_id'];

            // Get user details with tasks and uploads
            $query = "SELECT t.*, 
                     GROUP_CONCAT(DISTINCT tu.upload_id) as upload_ids,
                     GROUP_CONCAT(DISTINCT tu.file_name) as file_names
                     FROM users u
                     LEFT JOIN user_tasks t ON u.user_id = t.user_id
                     LEFT JOIN task_uploads tu ON t.task_id = tu.task_id
                     WHERE u.user_id = :user_id
                     GROUP BY t.task_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'tasks' => $tasks
            ]);
            break;

        case 'download_file':
            if (!isset($_GET['upload_id'])) {
                throw new Exception('Upload ID is required');
            }

            $uploadId = $_GET['upload_id'];
            
            $query = "SELECT * FROM task_uploads WHERE upload_id = :upload_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':upload_id', $uploadId);
            $stmt->execute();
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                throw new Exception('File not found');
            }

            header('Content-Type: ' . $file['file_type']);
            header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
            header('Content-Length: ' . $file['file_size']);
            echo $file['file_data'];
            exit();
            break;

        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    error_log('Admin users API error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    error_log('Admin users API error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
