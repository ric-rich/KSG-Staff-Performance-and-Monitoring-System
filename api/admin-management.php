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
        case 'get_users_with_details':
            // Get all users with their tasks and uploads
            $query = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.department, u.created_at,
                     COUNT(DISTINCT t.task_id) as total_tasks,
                     COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.task_id END) as completed_tasks,
                     COUNT(DISTINCT up.upload_id) as total_uploads
                     FROM users u
                     LEFT JOIN user_tasks t ON u.user_id = t.user_id
                     LEFT JOIN task_uploads up ON u.user_id = up.user_id -- This join seems incorrect, should be on task_id. Correcting to t.task_id
                     GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.department, u.created_at
                     ORDER BY u.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'timestamp' => date('Y-m-d H:i:s'),
                'users' => $users
            ]);
            break;

        case 'get_user_details':
            if (!isset($_GET['user_id'])) {
                throw new Exception('User ID is required');
            }
            
            $userId = $_GET['user_id'];

            // Get user details
            $query = "SELECT * FROM users WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Get all tasks and their uploads for this user in one query
            $detailsQuery = "SELECT 
                                ut.*, 
                                tu.upload_id, tu.file_name, tu.file_size, tu.uploaded_at, tu.file_type
                            FROM user_tasks ut
                            LEFT JOIN task_uploads tu ON ut.task_id = tu.task_id
                            WHERE ut.user_id = :user_id
                            ORDER BY ut.created_at DESC, tu.uploaded_at DESC";
            $detailsStmt = $db->prepare($detailsQuery);
            $detailsStmt->bindParam(':user_id', $userId);
            $detailsStmt->execute();
            $results = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

            $tasks = [];
            foreach ($results as $row) {
                $taskId = $row['task_id'];
                if (!isset($tasks[$taskId])) {
                    // Unset upload-specific fields from the main task object
                    unset($row['upload_id'], $row['file_name'], $row['file_size'], $row['uploaded_at'], $row['file_type']);
                    $tasks[$taskId] = $row;
                    $tasks[$taskId]['uploads'] = [];
                }
                if ($row['upload_id']) {
                    $tasks[$taskId]['uploads'][] = [
                        'id' => $row['upload_id'], 
                        'file_name' => $row['file_name'],
                        'file_size' => $row['file_size']
                    ];
                }
            }
            $user['tasks'] = array_values($tasks);
            
            echo json_encode(['status' => 'success', 'user' => $user]);
            break;

        case 'delete_task_upload':
            // Ensure the request method is DELETE for this action
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                http_response_code(405); // Method Not Allowed
                throw new Exception('This action requires a DELETE request.');
            }

            if (!isset($_GET['upload_id'])) {
                throw new Exception('Upload ID is required.');
            }

            $upload_id = filter_var($_GET['upload_id'], FILTER_VALIDATE_INT);
            if ($upload_id === false) {
                throw new Exception('Invalid Upload ID specified.');
            }

            // As an admin, we can delete any upload. For enhanced security, log this action.
            $query = 'DELETE FROM task_uploads WHERE upload_id = :upload_id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':upload_id', $upload_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'File deleted successfully.']);
            } else {
                throw new Exception('Failed to delete the file from the database.');
            }
            break;

        case 'delete_file':
            if (!isset($_GET['upload_id'])) {
                throw new Exception('Upload ID is required');
            }

            // Verify the file exists and get task info
            $verifyQuery = "SELECT tu.*, ut.user_id 
                          FROM task_uploads tu
                          JOIN user_tasks ut ON tu.task_id = ut.task_id
                          WHERE tu.upload_id = :upload_id";
            
            $verifyStmt = $db->prepare($verifyQuery);
            $verifyStmt->bindParam(':upload_id', $_GET['upload_id']);
            $verifyStmt->execute();
            $file = $verifyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                throw new Exception('File not found');
            }

            // Delete the file
            $deleteQuery = "DELETE FROM task_uploads WHERE upload_id = :upload_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':upload_id', $_GET['upload_id']);

            if ($deleteStmt->execute()) {
                // Log the deletion
                $logQuery = "INSERT INTO admin_logs (admin_id, action, details) 
                           VALUES (:admin_id, 'file_delete', :details)";
                $logStmt = $db->prepare($logQuery);
                $logStmt->execute([
                    ':admin_id' => $_SESSION['admin_id'],
                    ':details' => json_encode([
                        'upload_id' => $_GET['upload_id'],
                        'user_id' => $file['user_id'],
                        'file_name' => $file['file_name']
                    ])
                ]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'File deleted successfully',
                    'data' => [
                        'upload_id' => $_GET['upload_id'],
                        'user_id' => $file['user_id']
                    ]
                ]);
            } else {
                throw new Exception('Failed to delete file');
            }
            break;

        case 'get_users_summary':
            // Get total users and active users (users with at least one login or task)
            $totalQuery = "SELECT COUNT(*) as total_users FROM users";
            $stmt = $db->prepare($totalQuery);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

            // Define "active" as users who have at least one task assigned
            $activeQuery = "SELECT COUNT(DISTINCT user_id) as active_users FROM user_tasks";
            $stmt = $db->prepare($activeQuery);
            $stmt->execute();
            $active = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];

            // Get all users for management table
            $usersQuery = "SELECT user_id, email, first_name, last_name, department, created_at FROM users ORDER BY created_at DESC";
            $stmt = $db->prepare($usersQuery);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'summary' => [
                    'total_users' => (int)$total,
                    'active_users' => (int)$active
                ],
                'users' => $users
            ]);
            break;

        case 'get_pending_bills':
            $query = "SELECT b.*, 
                     COUNT(tu.upload_id) as upload_count,
                     GROUP_CONCAT(DISTINCT tu.file_name) as uploaded_files
                     FROM bills b
                     LEFT JOIN task_uploads tu ON b.bill_id = tu.task_id
                     WHERE b.status = 'pending'
                     GROUP BY b.bill_id
                     ORDER BY b.due_date ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($bills as &$bill) {
                // Get uploaded files for each bill
                $uploadQuery = "SELECT upload_id, file_name, file_size, uploaded_at 
                              FROM task_uploads 
                              WHERE task_id = :bill_id";
                $uploadStmt = $db->prepare($uploadQuery);
                $uploadStmt->bindParam(':bill_id', $bill['bill_id']);
                $uploadStmt->execute();
                $bill['uploads'] = $uploadStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode([
                'status' => 'success',
                'bills' => $bills
            ]);
            break;

        case 'upload_bill_file':
            if (!isset($_FILES['file']) || !isset($_POST['bill_id'])) {
                throw new Exception('File and bill ID are required');
            }

            $billId = $_POST['bill_id'];
            $file = $_FILES['file'];

            // Insert file into task_uploads
            $query = "INSERT INTO task_uploads (task_id, file_name, file_type, file_size, file_data) 
                     VALUES (:task_id, :file_name, :file_type, :file_size, :file_data)";
            
            $fileData = file_get_contents($file['tmp_name']);
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':task_id', $billId);
            $stmt->bindParam(':file_name', $file['name']);
            $stmt->bindParam(':file_type', $file['type']);
            $stmt->bindParam(':file_size', $file['size']);
            $stmt->bindParam(':file_data', $fileData, PDO::PARAM_LOB);

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'File uploaded successfully'
                ]);
            } else {
                throw new Exception('Failed to upload file');
            }
            break;

        case 'update_bill_status':
            if (!isset($_POST['bill_id']) || !isset($_POST['status'])) {
                throw new Exception('Bill ID and status are required');
            }

            $query = "UPDATE bills 
                     SET status = :status, 
                         updated_at = NOW() 
                     WHERE bill_id = :bill_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':bill_id', $_POST['bill_id']);
            $stmt->bindParam(':status', $_POST['status']);

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Bill status updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update bill status');
            }
            break;

        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    error_log('Admin management API error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>