<?php
// Prevent any PHP output before JSON
ob_start();

// Start session handling at the very beginning
require_once '../config/session.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../classes/User.php';
    require_once '../classes/Task.php';
    require_once '../config/database.php';

    function sendError($message, $statusCode = 500)
    {
        http_response_code($statusCode);
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit();
    }

    function sendSuccess($message, $data = [], $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }

    /**
     * Generates a bar chart image and saves it to a temporary file.
     *
     * @param array $data Associative array of data (e.g., ['label' => value]).
     * @param string $title The title of the chart.
     * @param int $width The width of the image.
     * @param int $height The height of the image.
     * @return string The file path of the generated chart image.
     */
    function generateBarChart($data, $title, $width = 480, $height = 250)
    {
        // Create image canvas
        $image = imagecreatetruecolor($width, $height);

        // Define colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 192, 192, 192);
        $colors = [
            imagecolorallocate($image, 75, 192, 192),  // Teal
            imagecolorallocate($image, 255, 205, 86), // Yellow
            imagecolorallocate($image, 255, 99, 132),  // Red
            imagecolorallocate($image, 54, 162, 235),  // Blue
        ];

        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        // Chart properties
        $barWidth = 50;
        $spacing = 35;
        $margin = 40;
        $chartAreaHeight = $height - ($margin * 2);
        $maxValue = !empty($data) ? max(array_values($data)) : 0;
        $scale = $maxValue > 0 ? $chartAreaHeight / $maxValue : 0;

        // Draw chart title
        imagestring($image, 4, ($width - strlen($title) * 8) / 2, 5, $title, $black);

        // Draw bars and labels
        $i = 0;
        $startX = $margin + 30;
        foreach ($data as $label => $value) {
            $barHeight = $scale * $value;
            $x1 = $startX + ($i * ($barWidth + $spacing));
            $y1 = $height - $margin - $barHeight;
            $x2 = $x1 + $barWidth;
            $y2 = $height - $margin - 1;

            // Draw bar
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $colors[$i % count($colors)]);

            // Draw label below bar
            imagestring($image, 2, $x1 + ($barWidth / 2) - (strlen($label) * 4) / 2, $y2 + 5, $label, $black);

            // Draw value on top of bar
            if ($value > 0) {
                imagestring($image, 3, $x1 + ($barWidth / 2) - (strlen($value) * 4) / 2, $y1 - 15, $value, $black);
            }
            $i++;
        }

        // Save image to a temporary file
        $filePath = tempnam(sys_get_temp_dir(), 'chart') . '.png';
        imagepng($image, $filePath);
        imagedestroy($image);

        return $filePath;
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    }

    // Initialize database
    $database = new Database();
    $db = $database->connect();

    // Get action from request
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Get posted data (JSON or fallback to POST)
    $rawInput = file_get_contents('php://input');
    $data = null;
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput);
        if (json_last_error() === JSON_ERROR_NONE)
            $data = $decoded;
    }
    if ($data === null && !empty($_POST)) {
        if (isset($_POST['data'])) {
            $maybe = json_decode($_POST['data']);
            if (json_last_error() === JSON_ERROR_NONE)
                $data = $maybe;
        }
        if ($data === null)
            $data = (object) $_POST;
    }

    $user = new User($db);
    $user->user_id = $_SESSION['user_id'];

    switch ($action) {
        case 'get_tasks':
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

            $query = 'SELECT ut.task_id, ut.user_id, ut.title, ut.description, ut.due_date, ut.status, ut.created_at, ut.completion_date,
                      CASE 
                        WHEN ut.status = "pending" AND ut.due_date < NOW() THEN "overdue"
                        ELSE ut.status
                      END as effective_status
                      FROM user_tasks ut
                      WHERE ut.user_id = :user_id';

            $params = [':user_id' => $user->user_id];

            // Add filter conditions
            switch ($filter) {
                case 'pending':
                    $query .= ' AND ut.status = "pending" AND ut.due_date >= NOW()';
                    break;
                case 'completed':
                    $query .= ' AND ut.status = "completed"';
                    break;
                case 'overdue':
                    $query .= ' AND ut.status = "pending" AND ut.due_date < NOW()';
                    break;
                // 'all' shows everything by default
            }

            // Add search term condition
            if (!empty($searchTerm)) {
                $query .= ' AND ut.title LIKE :search';
                $params[':search'] = '%' . $searchTerm . '%';
            }

            $query .= ' ORDER BY 
                        CASE 
                            WHEN ut.status = "pending" AND ut.due_date < NOW() THEN 1
                            WHEN ut.status = "pending" THEN 2
                            WHEN ut.status = "in_progress" THEN 3
                            WHEN ut.status = "completed" THEN 4
                            ELSE 5
                        END,
                        ut.due_date ASC';

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add counts for the filter buttons
            $countQuery = 'SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" AND due_date >= NOW() THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "pending" AND due_date < NOW() THEN 1 ELSE 0 END) as overdue
            FROM user_tasks 
            WHERE user_id = :user_id';

            $countStmt = $db->prepare($countQuery);
            $countStmt->bindParam(':user_id', $user->user_id);
            $countStmt->execute();
            $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'tasks' => $tasks,
                'counts' => $counts
            ]);
            break;

        case 'get_profile':
            try {
                error_log("DEBUG: Fetching profile data for user_id: " . $_SESSION['user_id']);
                $query = 'SELECT user_id, email, first_name, last_name, department, phone, job_title, is_team_member, profile_picture, created_at, settings
                         FROM users WHERE user_id = :user_id LIMIT 1';

                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);

                if (!$stmt->execute()) {
                    error_log("DEBUG: Query execution failed: " . json_encode($stmt->errorInfo()));
                    throw new Exception('Failed to fetch profile data');
                }

                $profile = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$profile) {
                    error_log("DEBUG: No profile found for user_id: " . $_SESSION['user_id']);
                    http_response_code(404);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Profile not found'
                    ]);
                    exit();
                }

                error_log("DEBUG: Profile data retrieved successfully");

                // Handle profile picture if exists
                if (!empty($profile['profile_picture'])) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $finfo->buffer($profile['profile_picture']);
                    $profile['profile_picture'] = 'data:' . $mime_type . ';base64,' . base64_encode($profile['profile_picture']);
                }

                // Add additional user information
                $profile['name'] = trim($profile['first_name'] . ' ' . $profile['last_name']);
                // Decode settings JSON
                $profile['settings'] = json_decode($profile['settings'] ?? '{"theme":"light","language":"en"}', true);

                echo json_encode([
                    'status' => 'success',
                    'data' => $profile
                ]);
                exit();

            } catch (Exception $e) {
                error_log("Profile fetch error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error fetching profile data',
                    'debug' => $e->getMessage()
                ]);
                exit();
            }
        // break; // Unreachable

        case 'update_profile':
            if (!empty($data->name)) {
                // Split name into first and last
                $nameParts = explode(' ', trim($data->name), 2);
                $user->first_name = $nameParts[0];
                $user->last_name = $nameParts[1] ?? '';

                // Assign other properties from the request data
                $user->department = $data->department ?? null;
                $user->phone = $data->phone ?? null;
                $user->job_title = $data->job_title ?? null;
                $user->is_team_member = $data->is_team_member ?? null;

                // Attempt to update
                if ($user->update()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Profile updated successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error updating profile'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Name is a required field.'
                ]);
            }
            break;

        case 'save_notification_preferences':
            if ($data) {
                try {
                    $preferences_json = json_encode($data);

                    $query = 'UPDATE users SET notification_preferences = :preferences WHERE user_id = :user_id';
                    $stmt = $db->prepare($query);

                    $stmt->bindParam(':preferences', $preferences_json);
                    $stmt->bindParam(':user_id', $user->user_id);

                    if ($stmt->execute()) {
                        sendSuccess('Notification preferences updated successfully.');
                    } else {
                        sendError('Failed to update notification preferences.');
                    }
                } catch (Exception $e) {
                    sendError('An error occurred while saving preferences.');
                }
            } else {
                sendError('No preferences data provided.', 400);
            }
            break;

        case 'upload_task_file':
            if (isset($_FILES['file'])) {
                $task = new Task($db);
                $task->task_id = $_POST['task_id'] ?? null;

                if ($task->task_id) {
                    $file = $_FILES['file'];
                    $file_data = file_get_contents($file['tmp_name']);

                    if ($task->uploadFile($file['name'], $file_data, $file['type'], $file['size'])) {
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'File uploaded successfully'
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Error uploading file'
                        ]);
                    }
                }
            }
            break;

        case 'update_task_status':
            if (isset($_GET['task_id']) && !empty($data->status)) {
                $task_id = $_GET['task_id'];
                $status = $data->status;

                // Verify task belongs to this user
                $checkQuery = 'SELECT user_id FROM user_tasks WHERE task_id = :task_id';
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':task_id', $task_id);
                $checkStmt->execute();
                $taskOwner = $checkStmt->fetch();

                if (!$taskOwner || $taskOwner['user_id'] != $user->user_id) {
                    http_response_code(403);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Unauthorized to update this task'
                    ]);
                    break;
                }

                // Update task status
                $updateQuery = 'UPDATE user_tasks 
                               SET status = :status,
                                   completion_date = CASE WHEN :status = "completed" THEN NOW() ELSE completion_date END
                               WHERE task_id = :task_id';

                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':status', $status);
                $updateStmt->bindParam(':task_id', $task_id);

                if ($updateStmt->execute()) {
                    // Check if completed and send notifications
                    if ($status === 'completed') {
                        try {
                            $tStmt = $db->prepare("SELECT title FROM user_tasks WHERE task_id = ?");
                            $tStmt->execute([$task_id]);
                            $tData = $tStmt->fetch();

                            $uStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
                            $uStmt->execute([$user->user_id]);
                            $uData = $uStmt->fetch();
                            $userName = $uData['first_name'] . ' ' . $uData['last_name'];

                            $admins = $db->query("SELECT email, notification_preferences FROM admins")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($admins as $adm) {
                                $prefs = isset($adm['notification_preferences']) ? json_decode($adm['notification_preferences'], true) : null;
                                if ($prefs && isset($prefs['receive_task_emails']) && $prefs['receive_task_emails']) {
                                    $to = $adm['email'];
                                    $subject = "Task Completed Notification";
                                    $msg = "User $userName has completed the task: " . $tData['title'];
                                    $headers = "From: no-reply@perf-system.local\r\n";
                                    // Use @ to suppress errors if mail server not configured
                                    @mail($to, $subject, $msg, $headers);
                                }
                            }
                        } catch (Exception $e) {
                            // Log error but don't fail the request
                            error_log("Notification error: " . $e->getMessage());
                        }
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Task status updated successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to update task status'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Task ID and status are required'
                ]);
            }
            break;

        case 'get_dashboard_stats':
            // Get task statistics for the user
            $statsQuery = 'SELECT 
                            COUNT(*) as total_tasks,
                            SUM(CASE WHEN status = "pending" AND due_date >= NOW() THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                            SUM(CASE WHEN status = "pending" AND due_date < NOW() THEN 1 ELSE 0 END) as overdue_count,
                            SUM(CASE WHEN status = "completed" AND completion_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as completed_this_week
                           FROM user_tasks
                           WHERE user_id = :user_id';

            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->bindParam(':user_id', $user->user_id);
            $statsStmt->execute();

            $stats = $statsStmt->fetch();

            echo json_encode([
                'status' => 'success',
                'stats' => $stats
            ]);
            break;

        case 'get_task_details':
            if (isset($_GET['task_id'])) {
                $task_id = filter_var($_GET['task_id'], FILTER_VALIDATE_INT);

                if ($task_id === false) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid task ID format'
                    ]);
                    break;
                }

                // Verify task belongs to this user and get full details
                $query = 'SELECT t.*, COUNT(tu.upload_id) as file_count 
                         FROM user_tasks t 
                         LEFT JOIN task_uploads tu ON t.task_id = tu.task_id
                         WHERE t.task_id = :task_id AND t.user_id = :user_id
                         GROUP BY t.task_id';

                $stmt = $db->prepare($query);
                $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user->user_id, PDO::PARAM_INT);

                if (!$stmt->execute()) {
                    error_log("Task details query failed: " . json_encode($stmt->errorInfo()));
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to fetch task details'
                    ]);
                    break;
                }

                $task = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($task) {
                    // Format dates for consistent output
                    $task['due_date'] = date('Y-m-d H:i:s', strtotime($task['due_date']));
                    if ($task['completion_date']) {
                        $task['completion_date'] = date('Y-m-d H:i:s', strtotime($task['completion_date']));
                    }

                    echo json_encode([
                        'status' => 'success',
                        'task' => $task
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Task not found or access denied'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Task ID is required'
                ]);
            }
            break;

        // Backwards-compatible alias expected by frontend
        case 'get_task':
            if (isset($_GET['task_id'])) {
                // reuse logic from get_task_details
                $task_id = $_GET['task_id'];
                $query = 'SELECT * FROM user_tasks WHERE task_id = :task_id AND user_id = :user_id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':task_id', $task_id);
                $stmt->bindParam(':user_id', $user->user_id);
                $stmt->execute();
                $task = $stmt->fetch();
                if ($task) {
                    echo json_encode(['status' => 'success', 'task' => $task]);
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Task not found or access denied']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Task ID is required']);
            }
            break;

        case 'get_task_files':
            if (isset($_GET['task_id'])) {
                $task_id = $_GET['task_id'];

                // Verify task belongs to this user
                $checkQuery = 'SELECT user_id FROM user_tasks WHERE task_id = :task_id';
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':task_id', $task_id);
                $checkStmt->execute();
                $taskOwner = $checkStmt->fetch();

                if (!$taskOwner || $taskOwner['user_id'] != $user->user_id) {
                    http_response_code(403);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Access denied'
                    ]);
                    break;
                }

                // Get uploaded files
                $query = 'SELECT upload_id, task_id, file_name, file_type, file_size, uploaded_at 
                         FROM task_uploads 
                         WHERE task_id = :task_id 
                         ORDER BY uploaded_at DESC';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':task_id', $task_id);
                $stmt->execute();

                $files = $stmt->fetchAll();

                echo json_encode([
                    'status' => 'success',
                    'files' => $files
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Task ID is required'
                ]);
            }
            break;

        // Backwards-compatible alias expected by frontend
        case 'get_task_uploads':
            if (isset($_GET['task_id'])) {
                $task_id = $_GET['task_id'];

                // Verify task belongs to this user
                $checkQuery = 'SELECT user_id FROM user_tasks WHERE task_id = :task_id';
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':task_id', $task_id);
                $checkStmt->execute();
                $taskOwner = $checkStmt->fetch();

                if (!$taskOwner || $taskOwner['user_id'] != $user->user_id) {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                    break;
                }

                // Get uploaded files and normalize id field
                $query = 'SELECT upload_id, task_id, file_name, file_type, file_size, uploaded_at FROM task_uploads WHERE task_id = :task_id ORDER BY uploaded_at DESC';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':task_id', $task_id);
                $stmt->execute();
                $rows = $stmt->fetchAll();
                $uploads = [];
                foreach ($rows as $r) {
                    $r['id'] = $r['upload_id'];
                    $uploads[] = $r;
                }

                echo json_encode(['status' => 'success', 'uploads' => $uploads]);
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Task ID is required']);
            }
            break;

        case 'download_task_file':
            if (isset($_GET['upload_id'])) {
                $upload_id = $_GET['upload_id'];

                // Get file and verify access
                $query = 'SELECT tu.*, ut.user_id 
                         FROM task_uploads tu
                         JOIN user_tasks ut ON tu.task_id = ut.task_id
                         WHERE tu.upload_id = :upload_id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':upload_id', $upload_id);
                $stmt->execute();

                $file = $stmt->fetch();

                if (!$file || $file['user_id'] != $user->user_id) {
                    http_response_code(403);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'File not found or access denied'
                    ]);
                    break;
                }

                // Send file for download
                header('Content-Type: ' . $file['file_type']);
                header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
                header('Content-Length: ' . $file['file_size']);
                echo $file['file_data'];
                exit();
            }
            break;

        case 'export_user_report':
            ob_end_clean();
            header('Content-Type: application/pdf');
            require_once __DIR__ . '/../api/fpdf.php';

            // Get report type and user
            $type = isset($_GET['type']) ? $_GET['type'] : 'all';
            $targetUserId = $_SESSION['user_id'];
            if (isset($_GET['target_user_id'])) {
                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
                    $targetUserId = filter_var($_GET['target_user_id'], FILTER_VALIDATE_INT);
                } else {
                    sendError('Permission denied', 403);
                }
            }

            // Fetch User Info
            $userStmt = $db->prepare("SELECT first_name, last_name, email, department, job_title, phone FROM users WHERE user_id = :user_id");
            $userStmt->execute([':user_id' => $targetUserId]);
            $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$userInfo) {
                http_response_code(404);
                die('User not found');
            }

            // Date Range & Title Logic
            $dateClause = '';
            $reportTitle = '';
            switch ($type) {
                case 'weekly':
                    $dateClause = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                    $reportTitle = 'Weekly Status Report';
                    break;
                case 'monthly':
                    $dateClause = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                    $reportTitle = 'Monthly Status Report';
                    break;
                default:
                    $reportTitle = 'Project Status Report';
                    break;
            }

            // Fetch Tasks
            $tasksStmt = $db->prepare("SELECT title, description, status, priority, due_date, completion_date, created_at FROM user_tasks WHERE user_id = :user_id $dateClause ORDER BY created_at DESC");
            $tasksStmt->execute([':user_id' => $targetUserId]);
            $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Stats
            $statsStmt = $db->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' AND due_date < NOW() THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN status = 'pending' AND due_date >= NOW() THEN 1 ELSE 0 END) as pending
                FROM user_tasks WHERE user_id = :user_id $dateClause");
            $statsStmt->execute([':user_id' => $targetUserId]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // Define cleanForPdf if not exists
            if (!function_exists('cleanForPdf')) {
                function cleanForPdf($str)
                {
                    return iconv('UTF-8', 'windows-1252//TRANSLIT', $str);
                }
            }

            // Extended PDF Class for Professional Design
            if (!class_exists('ReportPDF')) {
                class ReportPDF extends FPDF
                {
                    public $reportTitle;
                    public $reportSubtitle;

                    function Header()
                    {
                        // Teal Background Header
                        $this->SetFillColor(0, 150, 136); // Teal 500
                        $this->Rect(0, 0, 210, 35, 'F');

                        // Title
                        $this->SetXY(10, 10);
                        $this->SetTextColor(255, 255, 255);
                        $this->SetFont('Arial', 'B', 22);
                        $this->Cell(0, 10, cleanForPdf("PERFORMANCE REPORT"), 0, 1, 'L');

                        // Subtitle & Date
                        $this->SetFont('Arial', '', 11);
                        $this->Cell(0, 6, cleanForPdf($this->reportSubtitle), 0, 1, 'L');
                        $this->SetX(10); // Reset X
                        $this->Cell(0, 6, date('F j, Y'), 0, 1, 'L');

                        $this->SetY(45); // Start body content
                    }

                    function Footer()
                    {
                        $this->SetY(-15);
                        $this->SetTextColor(150, 150, 150);
                        $this->SetFont('Arial', '', 8);
                        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb} - Generated by Wellness System', 0, 0, 'C');
                    }

                    function KPI($label, $value, $x, $y, $w, $h, $color, $borderColor = null)
                    {
                        $this->SetXY($x, $y);
                        // Optional border
                        if ($borderColor) {
                            $this->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
                            $this->SetLineWidth(0.5);
                        } else {
                            $this->SetDrawColor(255, 255, 255);
                        }

                        $this->SetFillColor($color[0], $color[1], $color[2]);
                        $this->Rect($x, $y, $w, $h, 'FD');

                        // Label
                        $this->SetXY($x, $y + 5);
                        $this->SetTextColor(255, 255, 255);
                        $this->SetFont('Arial', '', 10);
                        $this->Cell($w, 5, cleanForPdf($label), 0, 1, 'C');

                        // Value
                        $this->SetXY($x, $y + 15);
                        $this->SetFont('Arial', 'B', 16);
                        $this->Cell($w, 10, $value, 0, 1, 'C');
                    }
                }
            }

            // Generate Chart
            $chartPath = null;
            if (function_exists('imagecreatetruecolor')) {
                $chartPath = generateBarChart([
                    'Done' => (int) $stats['completed'],
                    'Pending' => (int) $stats['pending'],
                    'Overdue' => (int) $stats['overdue']
                ], 'Task Status Distribution', 600, 300);
            }

            // Initialize PDF
            $pdf = new ReportPDF();
            $pdf->reportSubtitle = $reportTitle;
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 15);

            // 1. User Profile Section (Top Card)
            $pdf->SetFillColor(248, 249, 250); // Very light grey
            $pdf->SetDrawColor(230, 230, 230);
            $pdf->Rect(10, 40, 190, 25, 'FD');

            $pdf->SetTextColor(50, 50, 50);
            $pdf->SetXY(15, 45);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(90, 8, cleanForPdf($userInfo['first_name'] . ' ' . $userInfo['last_name']), 0, 1);
            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(90, 6, cleanForPdf($userInfo['job_title'] ?? 'Team Member') . ' | ' . cleanForPdf($userInfo['department'] ?? 'General'), 0, 1);

            // Right side of profile card
            $pdf->SetXY(110, 45);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->Cell(80, 8, 'Contact:', 0, 1, 'R');
            $pdf->SetXY(110, 51);
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(80, 6, cleanForPdf($userInfo['email']), 0, 1, 'R');

            $pdf->Ln(15);

            // 2. KPI Cards Section
            $yKPI = 72;
            $cardWidth = 45;
            $gap = 3.3; // Distribute across 190mm

            // Total (Grey/Blue)
            $pdf->KPI('Total Tasks', $stats['total'], 10, $yKPI, $cardWidth, 30, [96, 125, 139]);
            // Completed (Teal)
            $pdf->KPI('Completed', $stats['completed'], 10 + $cardWidth + $gap, $yKPI, $cardWidth, 30, [0, 150, 136]);
            // Pending (Orange) 
            $pdf->KPI('Pending', $stats['pending'], 10 + ($cardWidth + $gap) * 2, $yKPI, $cardWidth, 30, [255, 152, 0]);
            // Overdue (Red)
            $pdf->KPI('Overdue', $stats['overdue'], 10 + ($cardWidth + $gap) * 3, $yKPI, $cardWidth, 30, [244, 67, 54]);

            $pdf->Ln(38);

            // 3. Chart Section
            if ($chartPath && file_exists($chartPath)) {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(0, 150, 136);
                $pdf->Cell(0, 10, 'Visual Analytics', 0, 1);
                $pdf->Image($chartPath, 10, $pdf->GetY(), 190, 60);
                $pdf->Ln(65);
            } else {
                $pdf->Ln(10);
            }

            // 4. Project Task Tracker (Table)
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(0, 150, 136);
            $pdf->Cell(0, 10, 'Project Task Tracker', 0, 1);

            // Headers
            $pdf->SetFillColor(0, 150, 136);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(85, 8, 'Task Name', 0, 0, 'L', true);
            $pdf->Cell(25, 8, 'Priority', 0, 0, 'C', true);
            $pdf->Cell(25, 8, 'Status', 0, 0, 'C', true);
            $pdf->Cell(28, 8, 'Due', 0, 0, 'C', true);
            $pdf->Cell(27, 8, 'Completed', 0, 1, 'C', true);

            // Rows
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->SetFillColor(245, 248, 250); // Alternating stripe color

            $fill = false;
            if (empty($tasks)) {
                $pdf->Cell(190, 10, 'No tasks recorded for this period.', 1, 1, 'C');
            } else {
                foreach ($tasks as $task) {
                    $pdf->Cell(85, 8, cleanForPdf(substr($task['title'], 0, 60)), 1, 0, 'L', $fill);

                    // Priority
                    $pdf->Cell(25, 8, ucfirst($task['priority']), 1, 0, 'C', $fill);

                    // Status with Color
                    $status = ucfirst($task['status']);
                    if ($task['status'] == 'completed')
                        $pdf->SetTextColor(0, 128, 0);
                    elseif ($task['status'] == 'overdue')
                        $pdf->SetTextColor(200, 0, 0);
                    else
                        $pdf->SetTextColor(50, 50, 50);

                    $pdf->Cell(25, 8, $status, 1, 0, 'C', $fill);
                    $pdf->SetTextColor(50, 50, 50); // Reset

                    // Dates
                    $due = date('M j', strtotime($task['due_date']));
                    $comp = $task['completion_date'] ? date('M j', strtotime($task['completion_date'])) : '-';

                    $pdf->Cell(28, 8, $due, 1, 0, 'C', $fill);
                    $pdf->Cell(27, 8, $comp, 1, 1, 'C', $fill);

                    $fill = !$fill;
                }
            }

            // Output
            $safeName = preg_replace('/[^a-z0-9]/i', '_', $userInfo['first_name'] . '_' . $userInfo['last_name']);
            $pdf->Output('D', 'Performance_Report_' . $safeName . '.pdf');

            // Cleanup
            if ($chartPath && file_exists($chartPath))
                unlink($chartPath);
            exit;
            break;

        case 'upload_profile_picture':
            if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                sendError('No file uploaded or an upload error occurred.', 400);
            }

            $file = $_FILES['profile_picture'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($file['type'], $allowedTypes, true)) {
                sendError('Invalid file type. Only JPG, PNG and GIF are allowed.');
            }

            if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
                sendError('File is too large. Maximum size is 2MB.');
            }

            // Read file data into a variable
            $imageData = file_get_contents($file['tmp_name']);
            if ($imageData === false) {
                sendError('Could not read uploaded file.', 500);
            }

            try {
                // Update user's profile picture blob in the database
                $userId = $_SESSION['user_id'];
                $query = "UPDATE users SET profile_picture = :picture_data WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':picture_data', $imageData, PDO::PARAM_LOB);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    // Create a data URI to send back to the frontend for immediate display
                    $pictureUrl = 'data:' . $file['type'] . ';base64,' . base64_encode($imageData);
                    sendSuccess('Profile picture updated successfully!', ['picture_url' => $pictureUrl]);
                } else {
                    sendError('Failed to update profile picture in database');
                }
            } catch (PDOException $e) {
                error_log("DB Error on profile pic upload: " . $e->getMessage());
                sendError('Database error during profile picture update.', 500);
            }
            break;

        case 'save_settings':
            try {
                if (!$data) {
                    throw new Exception('No settings data provided');
                }

                // Check if 'settings' column exists before trying to update it
                $checkColumnQuery = "SHOW COLUMNS FROM `users` LIKE 'settings'";
                $checkStmt = $db->query($checkColumnQuery);
                $columnExists = $checkStmt->fetch();

                if ($columnExists) {
                    $settings = [
                        'theme' => $data->theme ?? 'light',
                        'language' => $data->language ?? 'en'
                    ];

                    $query = "UPDATE users 
                             SET settings = :settings 
                             WHERE user_id = :user_id";

                    $stmt = $db->prepare($query);
                    $settingsJson = json_encode($settings);
                    $stmt->bindParam(':settings', $settingsJson);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);

                    if ($stmt->execute()) {
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Settings updated successfully',
                            'settings' => $settings
                        ]);
                    } else {
                        throw new Exception('Failed to update settings');
                    }
                } else {
                    echo json_encode([
                        'status' => 'info',
                        'message' => 'Settings feature not available (column missing).'
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'create_task':
            if (!empty($data->title) && !empty($data->due_date)) {
                $query = 'INSERT INTO user_tasks (
                    user_id, 
                    title, 
                    description, 
                    due_date, 
                    status,
                    priority,
                    created_at
                ) VALUES (
                    :user_id,
                    :title,
                    :description,
                    :due_date,
                    "pending",
                    :priority,
                    NOW()
                )';

                $stmt = $db->prepare($query);

                // bindParam requires variables, not expressions.
                $description = $data->description ?? '';
                $priority = $data->priority ?? 'medium';

                $stmt->bindParam(':user_id', $user->user_id);
                $stmt->bindParam(':title', $data->title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':due_date', $data->due_date);
                $stmt->bindParam(':priority', $priority);

                if ($stmt->execute()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Task created successfully',
                        'task_id' => $db->lastInsertId()
                    ]);
                    exit(); // Ensure script terminates after success response
                } else {
                    sendError('Failed to create task');
                }
            } else {
                sendError('Title and due date are required', 400);
            }
        // break; // Unreachable

        case 'delete_task_upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                sendError('This action requires a DELETE request.', 405);
            }

            if (!isset($_GET['upload_id'])) {
                sendError('Upload ID is required.', 400);
            }

            $upload_id = filter_var($_GET['upload_id'], FILTER_VALIDATE_INT);
            if ($upload_id === false) {
                sendError('Invalid Upload ID specified.', 400);
            }

            try {
                // Start transaction
                $db->beginTransaction();

                // Verify that the upload belongs to a task owned by the current user
                $verifyQuery = 'SELECT tu.upload_id, tu.file_name 
                              FROM task_uploads tu
                              JOIN user_tasks ut ON tu.task_id = ut.task_id
                              WHERE tu.upload_id = :upload_id AND ut.user_id = :user_id';
                $verifyStmt = $db->prepare($verifyQuery);
                $verifyStmt->bindParam(':upload_id', $upload_id, PDO::PARAM_INT);
                $verifyStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $verifyStmt->execute();

                $fileInfo = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                if (!$fileInfo) {
                    throw new Exception('You do not have permission to delete this file.', 403);
                }

                // Proceed with deletion
                $deleteStmt = $db->prepare('DELETE FROM task_uploads WHERE upload_id = :upload_id');
                $deleteStmt->bindParam(':upload_id', $upload_id, PDO::PARAM_INT);

                if (!$deleteStmt->execute()) {
                    throw new Exception('Failed to delete file from database.');
                }

                $db->commit();
                echo json_encode([
                    'status' => 'success',
                    'message' => 'File "' . htmlspecialchars($fileInfo['file_name']) . '" was successfully deleted.',
                    'upload_id' => $upload_id
                ]);

            } catch (Exception $e) {
                $db->rollBack();
                http_response_code($e->getCode() === 403 ? 403 : 500);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action specified']);
    }

} catch (Exception $e) {
    error_log('api/user.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}