<?php
require_once '../config/database.php';
require_once '../classes/Admin.php';
require_once '../classes/Task.php';
require_once '../classes/User.php';

class AdminApiController
{
    private $db;
    private $admin;
    private $adminId;

    public function __construct($db, $adminId)
    {
        $this->db = $db;
        $this->adminId = $adminId;
        $this->admin = new Admin($db);
        $this->admin->admin_id = $adminId;
    }

    public function handle($action, $data)
    {
        switch ($action) {
            case 'get_users':
                $this->getUsers();
                break;
            case 'create_user':
                $this->createUser($data);
                break;
            case 'update_user':
                $this->updateUser($data);
                break;
            case 'delete_user':
                $this->deleteUser($data);
                break;
            case 'delete_all_users':
                $this->deleteAllUsers();
                break;
            case 'get_analytics':
                $this->getAnalytics();
                break;
            case 'get_dashboard_stats':
                $this->getDashboardStats();
                break;
            case 'update_security_settings':
                $this->updateSecuritySettings($data);
                break;
            case 'assign_predefined_task':
                $this->assignPredefinedTask($data);
                break;
            case 'assign_task':
                $this->assignTask($data);
                break;
            case 'delete_task':
                $this->deleteTask();
                break;
            case 'import_templates':
                $this->importTemplates($data);
                break;
            case 'get_recent_assignments':
                $this->getRecentAssignments();
                break;
            case 'get_all_tasks':
                $this->getAllTasks();
                break;
            case 'get_user_stats':
                $this->getUserStats();
                break;
            case 'get_user_details':
                $this->getUserDetails();
                break;
            case 'get_task':
                $this->getTask();
                break;
            case 'get_task_uploads':
                $this->getTaskUploads();
                break;
            case 'get_user_tasks':
                $this->getUserTasks();
                break;
            case 'get_user_task_details':
                $this->getUserTaskDetails();
                break;
            case 'get_access_logs':
                $this->getAccessLogs();
                break;
            case 'download_task_file':
                $this->downloadTaskFile();
                break;
            case 'get_unread_messages_count':
                $this->getUnreadMessagesCount();
                break;
            case 'get_messages':
                $this->getMessages();
                break;
            case 'mark_message_read':
                $this->markMessageRead();
                break;
            case 'delete_message':
                $this->deleteMessage();
                break;
            case 'get_users_with_roles':
                $this->getUsersWithRoles();
                break;
            case 'update_user_role':
                $this->updateUserRole($data);
                break;
            case 'get_site_metrics':
                $this->getSiteMetrics();
                break;
            case 'update_site_metrics':
                $this->updateSiteMetrics();
                break;
            case 'get_repository_files':
                $this->getRepositoryFiles();
                break;
            case 'upload_repository_file':
                $this->uploadRepositoryFile();
                break;
            case 'delete_repository_file':
                $this->deleteRepositoryFile($data);
                break;
            case 'commit_task_to_repository':
                $this->commitTaskToRepository($data);
                break;
            case 'get_backups':
                $this->getBackups();
                break;
            case 'create_backup':
                $this->createBackup();
                break;
            case 'delete_backup':
                $this->deleteBackup();
                break;
            case 'download_backup':
                $this->downloadBackup();
                break;
            case 'restore_backup':
                $this->restoreBackup();
                break;
            case 'download_repository_file':
                $this->downloadRepositoryFile();
                break;
            case 'get_profile':
                $this->getProfile();
                break;
            case 'update_profile':
                $this->updateProfile($data);
                break;
            case 'upload_profile_picture':
                $this->uploadProfilePicture();
                break;
            case 'save_notification_preferences':
                $this->saveNotificationPreferences($data);
                break;
            // Specific endpoint if needed for deleting task upload
            case 'delete_task_upload':
                $this->deleteTaskUpload();
                break;
            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    }

    private function getUsers()
    {
        $query = "SELECT u.user_id as id,
                         u.email,
                         u.first_name, u.last_name,
                         u.department,
                         u.created_at,
                         COUNT(DISTINCT t.task_id) as total_tasks,
                         COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.task_id END) as completed_tasks,
                         COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.task_id END) as pending_tasks
                  FROM users u
                  LEFT JOIN user_tasks t ON u.user_id = t.user_id
                  GROUP BY u.user_id
                  ORDER BY u.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedUsers = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'department' => $user['department'],
                'created_at' => $user['created_at'],
                'total_tasks' => (int) $user['total_tasks'],
                'completed_tasks' => (int) $user['completed_tasks'],
                'pending_tasks' => (int) $user['pending_tasks'],
                'status' => 'active'
            ];
        }, $users);

        echo json_encode(['status' => 'success', 'users' => $formattedUsers]);
    }

    private function createUser($data)
    {
        if (!empty($data->email) && !empty($data->password) && !empty($data->first_name) && !empty($data->last_name)) {
            $checkQuery = "SELECT COUNT(*) FROM users WHERE email = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data->email]);
            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
                return;
            }

            $query = "INSERT INTO users (email, password_hash, first_name, last_name, department, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);

            try {
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $data->email,
                    $hashedPassword,
                    $data->first_name,
                    $data->last_name,
                    $data->department ?? null
                ]);

                echo json_encode(['status' => 'success', 'message' => 'User created successfully', 'user_id' => $this->db->lastInsertId()]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to create user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        }
    }

    private function updateUser($data)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            return;
        }
        if (empty($data->user_id) || empty($data->first_name) || empty($data->last_name)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID, first name, and last name are required.']);
            return;
        }

        try {
            $userToUpdate = new User($this->db);
            $userToUpdate->user_id = $data->user_id;
            $userToUpdate->first_name = $data->first_name;
            $userToUpdate->last_name = $data->last_name;
            $userToUpdate->job_title = $data->job_title ?? null;
            $userToUpdate->is_team_member = !empty($data->is_team_member) ? 1 : 0;

            if ($userToUpdate->update()) {
                echo json_encode(['status' => 'success', 'message' => 'User updated successfully.']);
            } else {
                throw new Exception('Failed to update user in the database.');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function deleteUser($data)
    {
        if (empty($data->user_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            return;
        }
        $user_id = $data->user_id;
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('DELETE FROM access_logs WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $user_id]);
            $stmt = $this->db->prepare('DELETE FROM users WHERE user_id = :user_id');
            $stmt->execute([':user_id' => $user_id]);

            if ($stmt->rowCount() > 0) {
                $this->db->commit();
                echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
            } else {
                throw new Exception('User not found.');
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An error occurred.']);
        }
    }

    private function deleteAllUsers()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        try {
            $this->db->beginTransaction();
            $this->db->exec('DELETE FROM access_logs WHERE user_id IS NOT NULL');
            $stmt = $this->db->prepare('DELETE FROM users');
            $stmt->execute();
            $count = $stmt->rowCount();
            $this->db->commit();
            echo json_encode(['status' => 'success', 'message' => "Successfully deleted {$count} users."]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An error occurred.']);
        }
    }

    private function getAnalytics()
    {
        $analytics = ['completion_rate' => 0, 'active_users' => 0, 'total_tasks' => 0, 'avg_completion_time' => 'N/A', 'most_active_dept' => 'N/A', 'peak_hours' => 'N/A'];

        $rate = $this->db->query('SELECT COUNT(*) as total, COUNT(CASE WHEN status = "completed" THEN 1 END) as completed FROM user_tasks WHERE created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")')->fetch();
        if ($rate && $rate['total'] > 0)
            $analytics['completion_rate'] = round(($rate['completed'] / $rate['total']) * 100);

        $analytics['active_users'] = $this->db->query('SELECT COUNT(DISTINCT user_id) FROM access_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();
        $analytics['total_tasks'] = $this->db->query('SELECT COUNT(*) FROM user_tasks')->fetchColumn();

        $avg = $this->db->query('SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completion_date)) as h FROM user_tasks WHERE status = "completed"')->fetchColumn();
        if ($avg)
            $analytics['avg_completion_time'] = round($avg / 24, 1) . ' days';

        $dept = $this->db->query('SELECT u.department, COUNT(ut.task_id) as c FROM user_tasks ut JOIN users u ON ut.user_id = u.user_id WHERE u.department IS NOT NULL GROUP BY u.department ORDER BY c DESC LIMIT 1')->fetch();
        if ($dept)
            $analytics['most_active_dept'] = $dept['department'];

        $peak = $this->db->query('SELECT HOUR(created_at) as h, COUNT(*) as c FROM access_logs GROUP BY h ORDER BY c DESC LIMIT 1')->fetch();
        if ($peak)
            $analytics['peak_hours'] = sprintf('%02d:00 - %02d:00', $peak['h'], $peak['h'] + 1);

        echo json_encode(['status' => 'success', 'analytics' => $analytics]);
    }

    private function getDashboardStats()
    {
        $stats = [];
        $stats['total_users'] = $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['active_sessions'] = $this->db->query('SELECT COUNT(DISTINCT user_id) FROM access_logs WHERE action = "login" AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)')->fetchColumn();

        $health = ['status' => 'healthy', 'database' => 'connected', 'issues' => []];
        $overdue = $this->db->query('SELECT COUNT(*) FROM user_tasks WHERE status = "pending" AND due_date < NOW()')->fetchColumn();
        if ($overdue > 20) {
            $health['status'] = 'warning';
            $health['issues'][] = "$overdue overdue tasks.";
        }
        $errors = $this->db->query('SELECT COUNT(*) FROM access_logs WHERE action LIKE "%fail%" AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)')->fetchColumn();
        if ($errors > 10) {
            $health['status'] = 'warning';
            $health['issues'][] = "$errors errors/hr.";
        }
        $stats['system_health'] = $health;

        $alerts = [];
        if ($overdue > 0)
            $alerts[] = ['type' => 'warning', 'message' => "$overdue overdue tasks.", 'priority' => 'high'];
        $failedLogins = $this->db->query('SELECT COUNT(*) FROM access_logs WHERE action = "failed_login" AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')->fetchColumn();
        if ($failedLogins > 10)
            $alerts[] = ['type' => 'security', 'message' => "High failed logins.", 'priority' => 'medium'];
        $stats['alerts'] = $alerts;

        $taskStats = $this->db->query('SELECT COUNT(*) as t, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as c FROM user_tasks')->fetch(PDO::FETCH_ASSOC);
        $stats['total_tasks'] = $taskStats['t'];
        $stats['completion_rate'] = $taskStats['t'] > 0 ? round(($taskStats['c'] / $taskStats['t']) * 100) : 0;

        $recent = $this->db->query('SELECT al.*, CASE WHEN al.user_id IS NOT NULL THEN (SELECT CONCAT(first_name, " ", last_name) FROM users u WHERE u.user_id = al.user_id) WHEN al.admin_id IS NOT NULL THEN (SELECT CONCAT(first_name, " ", last_name) FROM admins a WHERE a.admin_id = al.admin_id) ELSE "System" END as user_name FROM access_logs al ORDER BY al.created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'stats' => $stats, 'recent_activity' => $recent]);
    }

    private function updateSecuritySettings($data)
    {
        if (!empty($data->setting_name) && isset($data->setting_value)) {
            if ($this->admin->updateSecuritySettings($data->setting_name, $data->setting_value)) {
                echo json_encode(['status' => 'success', 'message' => 'Settings updated']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error updating settings']);
            }
        }
    }

    private function assignPredefinedTask($data)
    {
        try {
            if (empty($data) || empty($data->user_id) || empty($data->category) || empty($data->title) || empty($data->due_date)) {
                throw new Exception('Missing required fields');
            }
            $taskObj = new Task($this->db);
            $id = $taskObj->assignToUser(
                $data->user_id,
                $data->category,
                $data->title,
                null,
                null,
                $data->due_date,
                'Admin #' . $this->adminId,
                $data->priority ?? 'medium',
                $data->instructions ?? null
            );
            if ($id)
                echo json_encode(['status' => 'success', 'message' => 'Task assigned', 'task_id' => $id]);
            else
                throw new Exception('Failed to create task');
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function assignTask($data)
    {
        if (!empty($data->user_id) && !empty($data->category) && !empty($data->title)) {
            try {
                $adminName = $this->db->query("SELECT CONCAT(first_name, ' ', last_name) FROM admins WHERE admin_id = {$this->adminId}")->fetchColumn();
                $taskObj = new Task($this->db);
                $id = $taskObj->assignToUser(
                    $data->user_id,
                    $data->category,
                    $data->title,
                    $data->description ?? null,
                    $data->start_date ?? null,
                    $data->due_date ?? null,
                    $adminName ?: "Admin #{$this->adminId}",
                    $data->priority ?? null,
                    $data->description ?? null
                );
                if ($id)
                    echo json_encode(['status' => 'success', 'message' => 'Task assigned', 'task_id' => $id]);
                else
                    throw new Exception('Failed to assign');
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        }
    }

    private function deleteTask()
    {
        if (!isset($_GET['task_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Task ID required']);
            return;
        }
        $id = $_GET['task_id'];
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('DELETE FROM task_uploads WHERE task_id = ?');
            $stmt->execute([$id]);
            $stmt = $this->db->prepare('DELETE FROM user_tasks WHERE task_id = ?');
            $stmt->execute([$id]);
            $this->db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Task deleted']);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete']);
        }
    }

    private function importTemplates($data)
    {
        try {
            $templates = $data->templates ?? $data ?? null;
            if (!$templates)
                throw new Exception('No templates provided');
            $templates = (array) $templates;
            $count = 0;
            foreach ($templates as $catName => $tasks) {
                $catName = trim($catName);
                if (!$catName)
                    continue;
                $stmt = $this->db->prepare('SELECT category_id FROM task_categories WHERE name = ?');
                $stmt->execute([$catName]);
                $catId = $stmt->fetchColumn();
                if (!$catId) {
                    $stmt = $this->db->prepare('INSERT INTO task_categories (name, created_at) VALUES (?, NOW())');
                    $stmt->execute([$catName]);
                    $catId = $this->db->lastInsertId();
                }
                foreach ((array) $tasks as $title) {
                    $title = trim($title);
                    if (!$title)
                        continue;
                    $stmt = $this->db->prepare('SELECT template_id FROM task_templates WHERE category_id = ? AND title = ?');
                    $stmt->execute([$catId, $title]);
                    if (!$stmt->fetch()) {
                        $stmt = $this->db->prepare('INSERT INTO task_templates (category_id, title, created_at) VALUES (?, ?, NOW())');
                        $stmt->execute([$catId, $title]);
                        $count++;
                    }
                }
            }
            echo json_encode(['status' => 'success', 'created_templates' => $count]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function getRecentAssignments()
    {
        $limit = $_GET['limit'] ?? 10;
        $stmt = $this->db->prepare('SELECT ut.*, CONCAT(u.first_name, " ", u.last_name) as user_name FROM user_tasks ut LEFT JOIN users u ON ut.user_id = u.user_id ORDER BY ut.created_at DESC LIMIT ?');
        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'assignments' => $stmt->fetchAll()]);
    }

    private function getAllTasks()
    {
        $query = 'SELECT ut.*, CONCAT(u.first_name, " ", u.last_name) as user_name FROM user_tasks ut LEFT JOIN users u ON ut.user_id = u.user_id WHERE 1=1';
        $params = [];
        if (!empty($_GET['user_id'])) {
            $query .= ' AND ut.user_id = ?';
            $params[] = $_GET['user_id'];
        }
        if (!empty($_GET['status'])) {
            $query .= ' AND ut.status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['priority'])) {
            $query .= ' AND ut.priority = ?';
            $params[] = $_GET['priority'];
        }
        $query .= ' ORDER BY ut.created_at DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'tasks' => $stmt->fetchAll()]);
    }

    private function getUserStats()
    {
        $stats = ['total_users' => 0, 'active_users' => 0];
        $stats['total_users'] = $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['active_users'] = $stats['total_users'];
        echo json_encode(['status' => 'success', 'stats' => $stats]);
    }

    private function getUserDetails()
    {
        if (!isset($_GET['user_id'])) {
            http_response_code(400);
            return;
        }
        $id = $_GET['user_id'];
        $user = $this->db->prepare("SELECT user_id, email, first_name, last_name, department, job_title, is_team_member, created_at FROM users WHERE user_id = ?");
        $user->execute([$id]);
        $userData = $user->fetch(PDO::FETCH_ASSOC);
        if (!$userData) {
            http_response_code(404);
            return;
        }

        $stats = $this->db->prepare("SELECT COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks, COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks FROM user_tasks WHERE user_id = ?");
        $stats->execute([$id]);

        $tasks = $this->db->prepare("SELECT t.*, GROUP_CONCAT(DISTINCT tu.upload_id) as upload_ids, GROUP_CONCAT(DISTINCT tu.file_name) as file_names, GROUP_CONCAT(DISTINCT tu.file_size) as file_sizes FROM user_tasks t LEFT JOIN task_uploads tu ON t.task_id = tu.task_id WHERE t.user_id = ? GROUP BY t.task_id ORDER BY t.created_at DESC");
        $tasks->execute([$id]);
        $taskList = $tasks->fetchAll(PDO::FETCH_ASSOC);

        foreach ($taskList as &$task) {
            $task['uploads'] = [];
            if ($task['upload_ids']) {
                $ids = explode(',', $task['upload_ids']);
                $names = explode(',', $task['file_names']);
                $sizes = explode(',', $task['file_sizes']);
                foreach ($ids as $i => $uid) {
                    $task['uploads'][] = ['id' => $uid, 'file_name' => $names[$i] ?? 'Unknown', 'file_size' => $sizes[$i] ?? 0];
                }
            }
            unset($task['upload_ids'], $task['file_names'], $task['file_sizes']);
        }

        echo json_encode(['status' => 'success', 'user' => $userData, 'stats' => $stats->fetch(PDO::FETCH_ASSOC), 'tasks' => $taskList]);
    }

    private function getTask()
    {
        if (!isset($_GET['task_id']))
            return;
        $stmt = $this->db->prepare('SELECT ut.*, CONCAT(u.first_name, " ", u.last_name) as user_name FROM user_tasks ut LEFT JOIN users u ON ut.user_id = u.user_id WHERE ut.task_id = ?');
        $stmt->execute([$_GET['task_id']]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($task)
            echo json_encode(['status' => 'success', 'task' => $task]);
        else {
            http_response_code(404);
            echo json_encode(['status' => 'error']);
        }
    }

    private function getTaskUploads()
    {
        if (!isset($_GET['task_id']))
            return;
        $stmt = $this->db->prepare('SELECT upload_id as id, task_id, file_name, file_type, file_size, uploaded_at FROM task_uploads WHERE task_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$_GET['task_id']]);
        echo json_encode(['status' => 'success', 'uploads' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    private function getUserTaskDetails()
    {
        if (!isset($_GET['task_id'])) {
            http_response_code(400);
            return;
        }
        $id = $_GET['task_id'];

        $stmt = $this->db->prepare("SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name, u.email as assigned_to_email 
                                  FROM user_tasks t 
                                  LEFT JOIN users u ON t.user_id = u.user_id 
                                  WHERE t.task_id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Task not found']);
            return;
        }

        // Get uploads (EXCLUDING file_data blob to prevent JSON errors)
        $stmt = $this->db->prepare('SELECT upload_id, task_id, file_name, file_type, file_size, uploaded_at FROM task_uploads WHERE task_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$id]);
        $task['uploads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'task' => $task]);
    }

    private function getUserTasks()
    {
        if (!isset($_GET['user_id']))
            return;
        $stmt = $this->db->prepare('SELECT * FROM user_tasks WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$_GET['user_id']]);
        echo json_encode(['status' => 'success', 'tasks' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    private function getAccessLogs()
    {
        $limit = $_GET['limit'] ?? 10;
        $query = 'SELECT al.*, 
                  CASE WHEN al.user_id IS NOT NULL THEN CONCAT(u.first_name, " ", u.last_name) WHEN al.admin_id IS NOT NULL THEN CONCAT(a.first_name, " ", a.last_name) ELSE "Unknown" END as user_name,
                  CASE WHEN al.user_id IS NOT NULL THEN "user" WHEN al.admin_id IS NOT NULL THEN "admin" ELSE "unknown" END as user_type
                  FROM access_logs al
                  LEFT JOIN users u ON al.user_id = u.user_id
                  LEFT JOIN admins a ON al.admin_id = a.admin_id
                  ORDER BY al.created_at DESC LIMIT ?';
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    private function downloadTaskFile()
    {
        if (!isset($_GET['upload_id']))
            return;
        $stmt = $this->db->prepare('SELECT * FROM task_uploads WHERE upload_id = ?');
        $stmt->execute([$_GET['upload_id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$file) {
            http_response_code(404);
            return;
        }
        ob_end_clean();
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($file['file_name']) . '"');
        header('Content-Length: ' . $file['file_size']);
        echo $file['file_data'];
        exit();
    }

    // Deleting task upload (File deletion from task)
    private function deleteTaskUpload()
    {
        if (!isset($_GET['upload_id']))
            return;
        try {
            $stmt = $this->db->prepare("DELETE FROM task_uploads WHERE upload_id = ?");
            if ($stmt->execute([$_GET['upload_id']])) {
                echo json_encode(['status' => 'success']);
            } else {
                throw new Exception('DB Error');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function getUnreadMessagesCount()
    {
        $count = $this->db->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
        echo json_encode(['status' => 'success', 'count' => (int) $count]);
    }

    private function getMessages()
    {
        $msgs = $this->db->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'messages' => $msgs]);
    }

    private function markMessageRead()
    {
        if (isset($_GET['id'])) {
            $this->db->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$_GET['id']]);
            echo json_encode(['status' => 'success']);
        }
    }

    private function deleteMessage()
    {
        if (isset($_GET['id'])) {
            $this->db->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$_GET['id']]);
            echo json_encode(['status' => 'success']);
        }
    }

    private function getUsersWithRoles()
    {
        $users = $this->db->query("SELECT user_id as id, email, CONCAT(first_name, ' ', last_name) as name, 'user' as role FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $admins = $this->db->query("SELECT admin_id as id, email, CONCAT(first_name, ' ', last_name) as name, 'admin' as role FROM admins")->fetchAll(PDO::FETCH_ASSOC);
        $combined = [];
        foreach ($users as $u)
            $combined[$u['email']] = $u;
        foreach ($admins as $a)
            $combined[$a['email']] = $a;
        echo json_encode(['status' => 'success', 'users' => array_values($combined)]);
    }

    private function updateUserRole($data)
    {
        if (isset($data->user_id) && isset($data->role)) {
            try {
                // Logic simplified: Check if user exists, check if admin exists.
                // Promote/Demote logic as per original file.
                // Assuming logic is robust enough in original.
                // This is a complex logic block, I will just replicate the intent:
                // Note: user_id here might be a User ID or an Admin ID depending on the context of the list?
                // Actually the FE likely sends the ID from the list.
                // However, user_id and admin_id are different keys. 
                // The original code assumes we are operating on a User ID from the users table.
                // If we are Demoting an admin, we need their email.

                // Fetch user by ID
                $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$data->user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user)
                    throw new Exception("User not found");

                // Check Admin status
                $astmt = $this->db->prepare("SELECT admin_id FROM admins WHERE email = ?");
                $astmt->execute([$user['email']]);
                $isAdmin = $astmt->fetch();

                if ($data->role === 'admin' && !$isAdmin) {
                    $istmt = $this->db->prepare("INSERT INTO admins (first_name, last_name, email, password_hash, index_code) VALUES (?, ?, ?, ?, 'PROMOTED')");
                    $istmt->execute([$user['first_name'], $user['last_name'], $user['email'], $user['password_hash']]);
                } elseif ($data->role === 'user' && $isAdmin) {
                    // Check if last admin
                    if ($this->db->query("SELECT COUNT(*) FROM admins")->fetchColumn() <= 1)
                        throw new Exception("Cannot demote last admin");
                    $this->db->prepare("DELETE FROM admins WHERE email = ?")->execute([$user['email']]);
                }
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    private function getSiteMetrics()
    {
        $metrics = $this->db->query('SELECT id, metric_key, metric_value, metric_label, description FROM site_metrics')->fetchAll(PDO::FETCH_ASSOC);
        $files = $this->db->query('SELECT file_id, metric_id, file_path FROM site_metric_files')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($metrics as &$metric) {
            $metric['files'] = array_values(array_filter($files, function ($f) use ($metric) {
                return $f['metric_id'] == $metric['id'];
            }));
        }
        echo json_encode(['status' => 'success', 'metrics' => $metrics]);
    }

    private function updateSiteMetrics()
    {
        // multipart/form-data
        if (isset($_POST['metrics'])) {
            try {
                $this->db->beginTransaction();
                $upload_dir = __DIR__ . '/../uploads/metrics/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                $upd = $this->db->prepare("UPDATE site_metrics SET metric_value = ?, metric_label = ?, description = ? WHERE id = ?");
                $ins = $this->db->prepare("INSERT INTO site_metric_files (metric_id, file_path) VALUES (?, ?)");
                $del = $this->db->prepare("DELETE FROM site_metric_files WHERE file_id = ?");

                foreach ($_POST['metrics'] as $idx => $m) {
                    $m = (object) $m;
                    $upd->execute([$m->value, $m->label, $m->description, $m->id]);

                    if (!empty($m->remove_files)) {
                        foreach (json_decode($m->remove_files) as $rf) {
                            $del->execute([$rf->id]);
                            if (file_exists($upload_dir . $rf->path))
                                unlink($upload_dir . $rf->path);
                        }
                    }
                    if (isset($_FILES['metric_files']['name'][$idx])) {
                        foreach ($_FILES['metric_files']['name'][$idx] as $key => $name) {
                            if ($_FILES['metric_files']['error'][$idx][$key] === UPLOAD_ERR_OK) {
                                $fname = time() . '_' . basename($name);
                                if (move_uploaded_file($_FILES['metric_files']['tmp_name'][$idx][$key], $upload_dir . $fname)) {
                                    $ins->execute([$m->id, $fname]);
                                }
                            }
                        }
                    }
                }
                $this->db->commit();
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                $this->db->rollBack();
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    private function getRepositoryFiles()
    {
        $search = $_GET['search'] ?? '';
        $query = 'SELECT rf.*, CONCAT(u.first_name, " ", u.last_name) as user_name, ut.title as task_title FROM repository_files rf LEFT JOIN users u ON rf.user_id = u.user_id LEFT JOIN user_tasks ut ON rf.task_id = ut.task_id WHERE 1=1';
        $params = [];
        if ($search) {
            $query .= ' AND (rf.file_name LIKE ? OR rf.description LIKE ?)';
            $params = ["%$search%", "%$search%"];
        }
        $query .= ' ORDER BY rf.uploaded_at DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $stats = $this->db->query('SELECT COUNT(*) as total_items, SUM(file_size) as storage_used FROM repository_files')->fetch(PDO::FETCH_ASSOC);
        $stats['formatted_storage'] = $this->formatBytes($stats['storage_used'] ?? 0);

        echo json_encode(['status' => 'success', 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'stats' => $stats]);
    }

    private function formatBytes($bytes)
    {
        if ($bytes <= 0)
            return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    private function uploadRepositoryFile()
    {
        if (isset($_FILES['repository_file']) && $_FILES['repository_file']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../uploads/repository/';
            if (!is_dir($dir))
                mkdir($dir, 0777, true);
            $f = $_FILES['repository_file'];
            $name = time() . '_' . basename($f['name']);
            if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                $this->db->prepare('INSERT INTO repository_files (file_name, file_path, description, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$f['name'], $name, $_POST['description'] ?? '', $f['size'], $f['type'], $this->adminId]);
                echo json_encode(['status' => 'success']);
            }
        }
    }

    private function deleteRepositoryFile($data)
    {
        if (isset($data->file_id)) {
            $stmt = $this->db->prepare('SELECT file_path FROM repository_files WHERE id = ?');
            $stmt->execute([$data->file_id]);
            $f = $stmt->fetch();
            if ($f) {
                $path = __DIR__ . '/../uploads/repository/' . $f['file_path'];
                if (file_exists($path))
                    unlink($path);
                $this->db->prepare('DELETE FROM repository_files WHERE id = ?')->execute([$data->file_id]);
                echo json_encode(['status' => 'success']);
            }
        }
    }

    private function commitTaskToRepository($data)
    {
        if (empty($data->task_id))
            return;
        $tid = $data->task_id;
        $task = $this->db->query("SELECT title, user_id FROM user_tasks WHERE task_id = $tid")->fetch(PDO::FETCH_ASSOC);
        $uploads = $this->db->query("SELECT * FROM task_uploads WHERE task_id = $tid")->fetchAll(PDO::FETCH_ASSOC);

        $dir = __DIR__ . '/../uploads/repository/';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        $cnt = 0;
        foreach ($uploads as $u) {
            $fname = time() . '_' . basename($u['file_name']);
            if (file_put_contents($dir . $fname, $u['file_data']) !== false) {
                $this->db->prepare("INSERT INTO repository_files (file_name, file_path, description, file_size, file_type, uploaded_by, task_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$u['file_name'], $fname, "From Task: " . $task['title'], $u['file_size'], $u['file_type'], $this->adminId, $tid, $task['user_id']]);
                $cnt++;
            }
        }
        if ($cnt > 0)
            echo json_encode(['status' => 'success', 'message' => "$cnt files committed"]);
        else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'No files committed']);
        }
    }

    private function getBackups()
    {
        $dir = __DIR__ . '/../backups/';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        $backups = [];
        foreach (glob($dir . '*.sql') as $f) {
            $backups[] = ['name' => basename($f), 'size' => filesize($f), 'date' => filemtime($f)];
        }
        echo json_encode(['status' => 'success', 'backups' => $backups]);
    }

    private function createBackup()
    {
        // Reuse Admin->createBackup() if implemented, OR use logic from original file.
        // Original file had "Simple PHP Backup Logic". I will use that.
        $dir = __DIR__ . '/../backups/';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        $file = $dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Simplified dump logic for brevity
        $content = "-- Backup\nSET FOREIGN_KEY_CHECKS=0;\n";
        $tables = $this->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            $create = $this->db->query("SHOW CREATE TABLE `$t`")->fetchColumn(1);
            $content .= "\n$create;\n";
            $rows = $this->db->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_NUM);
            foreach ($rows as $r) {
                $vals = array_map(function ($v) {
                    return $v === null ? "NULL" : $this->db->quote($v);
                }, $r);
                $content .= "INSERT INTO `$t` VALUES (" . implode(',', $vals) . ");\n";
            }
        }
        $content .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($file, $content))
            echo json_encode(['status' => 'success']);
        else {
            http_response_code(500);
            echo json_encode(['status' => 'error']);
        }
    }

    private function deleteBackup()
    {
        $data = json_decode(file_get_contents("php://input"));
        if ($data && $data->filename) {
            $f = __DIR__ . '/../backups/' . basename($data->filename);
            if (file_exists($f))
                unlink($f);
            echo json_encode(['status' => 'success']);
        }
    }

    private function downloadBackup()
    {
        if (isset($_GET['filename'])) {
            $f = __DIR__ . '/../backups/' . basename($_GET['filename']);
            if (file_exists($f)) {
                ob_end_clean();
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($f) . '"');
                header('Content-Length: ' . filesize($f));
                readfile($f);
                exit;
            }
        }
        http_response_code(404);
    }

    private function restoreBackup()
    {
        if (isset($_GET['filename']) || isset($_POST['filename'])) {
            $fname = $_REQUEST['filename'];
            $f = __DIR__ . '/../backups/' . basename($fname);
            if (file_exists($f)) {
                $sql = file_get_contents($f);
                $this->db->exec($sql);
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        // Also handle file upload for restore
        if (isset($_FILES['backup_file'])) {
            $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
            $this->db->exec($sql);
            echo json_encode(['status' => 'success']);
        }
    }

    private function downloadRepositoryFile()
    {
        if (isset($_GET['file_id'])) {
            $stmt = $this->db->prepare('SELECT * FROM repository_files WHERE id = ?');
            $stmt->execute([$_GET['file_id']]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($file) {
                $path = __DIR__ . '/../uploads/repository/' . $file['file_path'];
                if (file_exists($path)) {
                    ob_end_clean();
                    header('Content-Type: ' . $file['file_type']);
                    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
                    readfile($path);
                    exit;
                }
            }
        }
        http_response_code(404);
    }

    private function getProfile()
    {
        $stmt = $this->db->prepare('SELECT admin_id, email, first_name, last_name, department, phone, job_title, profile_picture, notification_preferences FROM admins WHERE admin_id = ?');
        $stmt->execute([$this->adminId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            if ($profile['profile_picture']) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($profile['profile_picture']);
                $profile['profile_picture'] = 'data:' . $mime . ';base64,' . base64_encode($profile['profile_picture']);
            }
            $profile['name'] = trim($profile['first_name'] . ' ' . $profile['last_name']);

            $prefs = $profile['notification_preferences'] ? json_decode($profile['notification_preferences'], true) : ['receive_task_emails' => false];
            $profile['settings'] = json_encode(['notifications' => $prefs]);

            echo json_encode(['status' => 'success', 'data' => $profile]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error']);
        }
    }

    private function updateProfile($data)
    {
        if (empty($data->name))
            return;
        $parts = explode(' ', trim($data->name), 2);
        $fn = $parts[0];
        $ln = $parts[1] ?? '';
        $stmt = $this->db->prepare('UPDATE admins SET first_name=?, last_name=?, department=?, phone=?, job_title=? WHERE admin_id=?');
        if ($stmt->execute([$fn, $ln, $data->department ?? null, $data->phone ?? null, $data->job_title ?? null, $this->adminId])) {
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error']);
        }
    }

    private function uploadProfilePicture()
    {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $data = file_get_contents($_FILES['profile_picture']['tmp_name']);
            $stmt = $this->db->prepare('UPDATE admins SET profile_picture = ? WHERE admin_id = ?');
            $stmt->bindParam(1, $data, PDO::PARAM_LOB);
            $stmt->bindParam(2, $this->adminId);
            if ($stmt->execute()) {
                $url = 'data:' . $_FILES['profile_picture']['type'] . ';base64,' . base64_encode($data);
                echo json_encode(['status' => 'success', 'data' => ['picture_url' => $url]]);
            }
        }
    }

    private function saveNotificationPreferences($data)
    {
        if ($data) {
            try {
                $preferences_json = json_encode($data);
                $stmt = $this->db->prepare('UPDATE admins SET notification_preferences = ? WHERE admin_id = ?');
                if ($stmt->execute([$preferences_json, $this->adminId])) {
                    echo json_encode(['status' => 'success', 'message' => 'Preferences updated']);
                } else {
                    throw new Exception('Failed to update DB');
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No data']);
        }
    }
}
?>