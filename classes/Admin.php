<?php
require_once '../config/database.php';

class Admin
{
    private $conn;
    private $table = 'admins';

    // Admin properties
    public $admin_id;
    public $email;
    public $password_hash;
    public $index_code;
    public $first_name;
    public $last_name;
    public $profile_picture;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create new admin
    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . '
            (email, password_hash, index_code, first_name, last_name)
            VALUES (:email, :password_hash, :index_code, :first_name, :last_name)';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->index_code = htmlspecialchars(strip_tags($this->index_code));

        // Bind data
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':index_code', $this->index_code);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login admin
    public function login($email, $password, $index_code)
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE email = :email AND index_code = :index_code';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':index_code', $index_code);
        $stmt->execute();

        if ($row = $stmt->fetch()) {
            error_log("Login attempt for admin: " . $email);

            // 1. Check if account is locked
            if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
                $wait = ceil((strtotime($row['locked_until']) - time()) / 60);
                throw new Exception("Account locked. Please try again in $wait minutes.");
            }

            if (password_verify($password, $row['password_hash'])) {
                // Password valid
                // Reset failed attempts and lock
                $resetQuery = "UPDATE " . $this->table . " 
                               SET failed_login_attempts = 0, locked_until = NULL 
                               WHERE admin_id = :admin_id";
                $resetStmt = $this->conn->prepare($resetQuery);
                $resetStmt->bindParam(':admin_id', $row['admin_id']);
                $resetStmt->execute();

                // 3. Check for Password Expiry (90 days)
                $expiryDays = 90;
                $passwordAge = time() - strtotime($row['password_changed_at']);
                if ($passwordAge > ($expiryDays * 24 * 60 * 60)) {
                    throw new Exception("PASSWORD_EXPIRED");
                }

                unset($row['password_hash']);
                unset($row['failed_login_attempts']);
                unset($row['locked_until']);
                return $row;
            } else {
                // Password invalid
                // Increment failed attempts
                $attempts = $row['failed_login_attempts'] + 1;
                $lockSql = "";
                $remaining = 5 - $attempts;

                // If 5 or more attempts, lock for 30 minutes
                if ($attempts >= 5) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    $lockSql = ", locked_until = '$lockUntil'";
                    $msg = "Account locked due to too many failed attempts. Try again in 30 minutes.";
                } else {
                    $msg = "Invalid credentials. You have $remaining attempt(s) remaining.";
                }

                $updateQuery = "UPDATE " . $this->table . " 
                                SET failed_login_attempts = :attempts $lockSql 
                                WHERE admin_id = :admin_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':attempts', $attempts);
                $updateStmt->bindParam(':admin_id', $row['admin_id']);
                $updateStmt->execute();

                error_log("Password verification failed");
                throw new Exception($msg);
            }
        } else {
            error_log("No admin found with email: " . $email . " and matching index code");
        }
        return false;
    }

    // Get all users
    public function getAllUsers()
    {
        $query = 'SELECT * FROM users ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Create system backup
    public function createBackup()
    {
        try {
            $backup_content = "-- KSG SMI Performance System Backup\n";
            $backup_content .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n\n";

            $tables_stmt = $this->conn->query('SHOW TABLES');
            $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Skip the backups table itself to avoid recursive data
                if ($table == 'system_backups') {
                    continue;
                }

                // Get table structure
                $structure_stmt = $this->conn->query("SHOW CREATE TABLE `{$table}`");
                $structure_row = $structure_stmt->fetch(PDO::FETCH_ASSOC);
                $backup_content .= "\n\n--\n-- Table structure for table `{$table}`\n--\n\n";
                $backup_content .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $backup_content .= $structure_row['Create Table'] . ";\n\n";

                // Get table data
                $data_stmt = $this->conn->query("SELECT * FROM `{$table}`");
                $num_fields = $data_stmt->columnCount();

                if ($data_stmt->rowCount() > 0) {
                    $backup_content .= "--\n-- Dumping data for table `{$table}`\n--\n\n";
                    $backup_content .= "LOCK TABLES `{$table}` WRITE;\n";
                    $backup_content .= "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n";

                    while ($row = $data_stmt->fetch(PDO::FETCH_NUM)) {
                        $backup_content .= "INSERT INTO `{$table}` VALUES(";
                        for ($j = 0; $j < $num_fields; $j++) {
                            if (isset($row[$j])) {
                                // Escape special characters
                                $row[$j] = addslashes($row[$j]);
                                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                                $backup_content .= '"' . $row[$j] . '"';
                            } else {
                                $backup_content .= 'NULL';
                            }
                            if ($j < ($num_fields - 1)) {
                                $backup_content .= ',';
                            }
                        }
                        $backup_content .= ");\n";
                    }
                    $backup_content .= "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n";
                    $backup_content .= "UNLOCK TABLES;\n";
                }
            }

            // Save the backup to the database
            $query = 'INSERT INTO system_backups 
                (backup_name, backup_data, created_by) 
                VALUES (:backup_name, :backup_data, :created_by)';

            $stmt = $this->conn->prepare($query);

            $backup_name = 'backup_' . date('Y-m-d_H-i-s');

            $stmt->bindParam(':backup_name', $backup_name);
            $stmt->bindParam(':backup_data', $backup_content, PDO::PARAM_LOB);
            $stmt->bindParam(':created_by', $this->admin_id);

            if ($stmt->execute()) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            return false;
        }
    }

    // Update security settings
    public function updateSecuritySettings($setting_name, $setting_value)
    {
        $query = 'INSERT INTO security_settings (setting_name, setting_value)
            VALUES (:setting_name, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = :setting_value';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':setting_name', $setting_name);
        $stmt->bindParam(':setting_value', $setting_value);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get dashboard statistics
    public function getDashboardStats()
    {
        $stats = [];

        // Total Users
        $query = 'SELECT COUNT(*) as total FROM users';
        $stmt = $this->conn->query($query);
        $stats['total_users'] = $stmt->fetch()['total'];

        // Active Sessions (users who logged in within last 30 minutes)
        $query = 'SELECT COUNT(DISTINCT user_id) as active 
                  FROM access_logs 
                  WHERE action = "login" 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)';
        $stmt = $this->conn->query($query);
        $stats['active_sessions'] = $stmt->fetch()['active'];

        // System Health Metrics
        $stats['system_health'] = $this->getSystemHealth();

        // Alerts
        $stats['alerts'] = $this->getSystemAlerts();

        return $stats;
    }

    // Get system health status
    private function getSystemHealth()
    {
        $health = [
            'status' => 'healthy',
            'database' => 'connected',
            'disk_space' => 'sufficient',
            'response_time' => 'normal'
        ];

        try {
            // Check database connection
            $this->conn->query('SELECT 1');

            // Check for recent errors in access logs
            $query = 'SELECT COUNT(*) as error_count 
                      FROM access_logs 
                      WHERE action LIKE "%error%" 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)';
            $stmt = $this->conn->query($query);
            $errorCount = $stmt->fetch()['error_count'];

            if ($errorCount > 10) {
                $health['status'] = 'warning';
            }

            // Check for overdue tasks
            $query = 'SELECT COUNT(*) as overdue 
                      FROM user_tasks 
                      WHERE status = "overdue"';
            $stmt = $this->conn->query($query);
            $overdueCount = $stmt->fetch()['overdue'];

            if ($overdueCount > 20) {
                $health['status'] = 'warning';
            }

        } catch (Exception $e) {
            $health['status'] = 'critical';
            $health['database'] = 'error';
        }

        return $health;
    }

    // Get system alerts
    private function getSystemAlerts()
    {
        $alerts = [];

        // Check for overdue tasks
        $query = 'SELECT COUNT(*) as count FROM user_tasks WHERE status = "overdue"';
        $stmt = $this->conn->query($query);
        $overdueCount = $stmt->fetch()['count'];

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "$overdueCount overdue tasks require attention",
                'count' => $overdueCount,
                'priority' => 'high'
            ];
        }

        // Check for failed login attempts
        $query = 'SELECT COUNT(*) as count 
                  FROM access_logs 
                  WHERE action = "failed_login" 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
        $stmt = $this->conn->query($query);
        $failedLogins = $stmt->fetch()['count'];

        if ($failedLogins > 10) {
            $alerts[] = [
                'type' => 'security',
                'message' => "$failedLogins failed login attempts in last 24 hours",
                'count' => $failedLogins,
                'priority' => 'medium'
            ];
        }

        // Check for pending tasks
        $query = 'SELECT COUNT(*) as count 
                  FROM user_tasks 
                  WHERE status = "pending" 
                  AND due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)';
        $stmt = $this->conn->query($query);
        $pendingCount = $stmt->fetch()['count'];

        if ($pendingCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "$pendingCount tasks due within 3 days",
                'count' => $pendingCount,
                'priority' => 'low'
            ];
        }

        // Check for users without tasks
        $query = 'SELECT COUNT(*) as count 
                  FROM users u 
                  LEFT JOIN user_tasks ut ON u.user_id = ut.user_id 
                  WHERE ut.task_id IS NULL';
        $stmt = $this->conn->query($query);
        $usersWithoutTasks = $stmt->fetch()['count'];

        if ($usersWithoutTasks > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "$usersWithoutTasks users have no assigned tasks",
                'count' => $usersWithoutTasks,
                'priority' => 'low'
            ];
        }

        return $alerts;
    }

    // Get recent activity
    public function getRecentActivity($limit = 10)
    {
        $query = 'SELECT al.*, 
                  u.first_name as user_first_name, u.last_name as user_last_name,
                  a.first_name as admin_first_name, a.last_name as admin_last_name
                  FROM access_logs al
                  LEFT JOIN users u ON al.user_id = u.user_id
                  LEFT JOIN admins a ON al.admin_id = a.admin_id
                  ORDER BY al.created_at DESC
                  LIMIT :limit';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Get task statistics
    public function getTaskStatistics()
    {
        $stats = [];

        // Total tasks
        $query = 'SELECT COUNT(*) as total FROM user_tasks';
        $stmt = $this->conn->query($query);
        $stats['total_tasks'] = $stmt->fetch()['total'];

        // Tasks by status
        $query = 'SELECT status, COUNT(*) as count 
                  FROM user_tasks 
                  GROUP BY status';
        $stmt = $this->conn->query($query);
        $statusCounts = $stmt->fetchAll();

        foreach ($statusCounts as $row) {
            $stats['tasks_' . $row['status']] = $row['count'];
        }

        // Completion rate
        $completed = $stats['tasks_completed'] ?? 0;
        $total = $stats['total_tasks'];
        $stats['completion_rate'] = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return $stats;
    }
    // Get task details by ID
    public function getTaskDetailsById($taskId)
    {
        $query = 'SELECT ut.*, CONCAT(u.first_name, " ", u.last_name) as user_name 
                  FROM user_tasks ut 
                  LEFT JOIN users u ON ut.user_id = u.user_id 
                  WHERE ut.task_id = :task_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':task_id', $taskId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}