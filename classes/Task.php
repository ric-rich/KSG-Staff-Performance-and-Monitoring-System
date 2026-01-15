<?php
require_once '../config/database.php';

class Task {
    private $conn;
    private $table = 'user_tasks';

    // Task properties
    public $task_id;
    public $user_id;
    public $template_id;
    public $title;
    public $description;
    public $status;
    public $start_date;
    public $due_date;
    public $completion_date;
    public $category;
    public $priority;
    public $assigned_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create task (using stored procedure with template)
    public function create() {
        // If we have category, title, etc., use direct insert instead of stored procedure
        if(!empty($this->category) && !empty($this->title)) {
            return $this->createDirect();
        }
        
        // Otherwise use the stored procedure
        $query = 'CALL assign_task(:user_id, :template_id, :start_date, :due_date)';

        $stmt = $this->conn->prepare($query);

        // Bind data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':template_id', $this->template_id);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':due_date', $this->due_date);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Create task directly without template
    public function createDirect() {
        $query = 'INSERT INTO ' . $this->table . '
            (user_id, category, title, description, status, due_date, priority, assigned_by, start_date)
            VALUES (:user_id, :category, :title, :description, :status, :due_date, :priority, :assigned_by, NOW())';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));

        // Bind data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':priority', $this->priority);
        $stmt->bindParam(':assigned_by', $this->assigned_by);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get user's tasks
    public function getUserTasks($user_id) {
        // Select from user_tasks and join templates/categories to provide full task info
        $query = 'SELECT 
            ut.task_id AS id,
            ut.title,
            ut.description,
            ut.status,
            ut.start_date,
            ut.due_date,
            ut.completion_date,
            ut.priority,
            ut.assigned_by,
            ut.user_id,
            tt.template_id,
            tt.title AS template_title,
            tc.category_id,
            tc.name AS category_name,
            u.first_name,
            u.last_name
            FROM user_tasks ut
            LEFT JOIN task_templates tt ON ut.template_id = tt.template_id
            LEFT JOIN task_categories tc ON tt.category_id = tc.category_id
            LEFT JOIN users u ON ut.user_id = u.user_id
            WHERE ut.user_id = :user_id
            ORDER BY ut.start_date DESC';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Update task status
    public function updateStatus() {
        $query = 'UPDATE ' . $this->table . '
            SET status = :status,
                completion_date = :completion_date
            WHERE task_id = :task_id';

        $stmt = $this->conn->prepare($query);

        // Bind data
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':completion_date', $this->completion_date);
        $stmt->bindParam(':task_id', $this->task_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Upload task file
    public function uploadFile($file_name, $file_data, $file_type, $file_size) {
        $query = 'INSERT INTO task_uploads 
            (task_id, file_name, file_data, file_type, file_size) 
            VALUES (:task_id, :file_name, :file_data, :file_type, :file_size)';

        $stmt = $this->conn->prepare($query);

        // Bind data
        $stmt->bindParam(':task_id', $this->task_id);
        $stmt->bindParam(':file_name', $file_name);
        $stmt->bindParam(':file_data', $file_data);
        $stmt->bindParam(':file_type', $file_type);
        $stmt->bindParam(':file_size', $file_size);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get task templates
    public function getTemplates() {
        $query = 'SELECT tt.*, tc.name as category_name 
            FROM task_templates tt
            JOIN task_categories tc ON tt.category_id = tc.category_id
            ORDER BY tc.name, tt.title';
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Create or find category, create template if needed and assign to user
    public function assignToUser($user_id, $category_name, $title, $description = null, $start_date = null, $due_date = null, $assigned_by = null, $priority = 'medium', $instructions = null) {
        try {
            $this->conn->beginTransaction();

            // Validate inputs
            if (empty($user_id) || empty($title) || empty($due_date)) {
                throw new Exception('Missing required fields');
            }

            // Ensure category exists
            $catQuery = 'SELECT category_id FROM task_categories WHERE name = :name LIMIT 1';
            $stmt = $this->conn->prepare($catQuery);
            $stmt->bindParam(':name', $category_name);
            $stmt->execute();

            $category_id = null;
            if ($row = $stmt->fetch()) {
                $category_id = $row['category_id'];
            } else {
                // Create category if it doesn't exist
                $insCat = $this->conn->prepare('INSERT INTO task_categories (name, description, created_at) VALUES (:name, :desc, NOW())');
                $insCat->bindParam(':name', $category_name);
                $insCat->bindParam(':desc', $description);
                $insCat->execute();
                $category_id = $this->conn->lastInsertId();
            }

            // Insert the task directly
            $query = 'INSERT INTO user_tasks (
                user_id, title, description, instructions, start_date, due_date, 
                priority, assigned_by, created_at, status
            ) VALUES (
                :user_id, :title, :description, :instructions, :start_date, :due_date,
                :priority, :assigned_by, NOW(), "pending"
            )';
            
            $stmt = $this->conn->prepare($query);
            
            // Bind all parameters
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':instructions', $instructions);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':assigned_by', $assigned_by);

            if ($stmt->execute()) {
                $task_id = $this->conn->lastInsertId();
                $this->conn->commit();
                return $task_id;
            }

            throw new Exception('Failed to insert task');

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Task assignment error: " . $e->getMessage());
            throw $e;
        }
    }

    // Add this new method to get all users for task assignment
    public function getAllUsers() {
        $query = "SELECT user_id, email, first_name, last_name, department,
                 (SELECT COUNT(*) FROM user_tasks WHERE user_id = users.user_id) as total_tasks,
                 (SELECT COUNT(*) FROM user_tasks WHERE user_id = users.user_id AND status = 'pending') as pending_tasks
                 FROM users 
                 ORDER BY last_name, first_name";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting users: " . $e->getMessage());
            return false;
        }
    }
}

// Sample fetch request (to be used in JavaScript client-side code)
// fetch('/api/user.php?action=create_task', {
//    method: 'POST',
//    headers: {'Content-Type': 'application/json'},
 //   body: JSON.stringify({
 //       title: 'Complete project',
 //       description: 'Finish the dashboard implementation',
 //       due_date: '2025-10-15 17:00:00',
 //       priority: 'high'
 //   })
 //});