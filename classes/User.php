<?php
require_once '../config/database.php';

class User
{
    private $conn;
    private $table = 'users';

    // User properties
    public $user_id;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $department;
    public $profile_picture;
    public $is_team_member;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create new user
    public function create($settings = null)
    {
        try {
            $query = "INSERT INTO users 
                      (email, password_hash, first_name, last_name, department, settings) 
                      VALUES 
                      (:email, :password_hash, :first_name, :last_name, :department, :settings)";

            $stmt = $this->conn->prepare($query);

            // Clean the data
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->department = htmlspecialchars(strip_tags($this->department));

            // Bind the parameters
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password_hash', $this->password_hash);
            $stmt->bindParam(':first_name', $this->first_name);
            $stmt->bindParam(':last_name', $this->last_name);
            $stmt->bindParam(':department', $this->department);
            $stmt->bindParam(':settings', $settings);

            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }

    // Login user
    public function login($email, $password)
    {
        try {
            error_log("Attempting login for email: $email");

            $query = "SELECT user_id, email, password_hash, first_name, last_name, 
                             failed_login_attempts, locked_until, password_changed_at
                     FROM " . $this->table . " 
                     WHERE email = :email";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);

            if (!$stmt->execute()) {
                throw new Exception("Query execution failed");
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                // To prevent timing attacks, we could simulate verification, but for now just return false
                error_log("No user found with email: $email");
                return false;
            }

            // 1. Check if account is locked
            if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
                $wait = ceil((strtotime($row['locked_until']) - time()) / 60);
                throw new Exception("Account locked. Please try again in $wait minutes.");
            }

            // 2. Verify Password
            if (password_verify($password, $row['password_hash'])) {
                // Password valid
                // Reset failed attempts and lock
                $resetQuery = "UPDATE " . $this->table . " 
                               SET failed_login_attempts = 0, locked_until = NULL 
                               WHERE user_id = :user_id";
                $resetStmt = $this->conn->prepare($resetQuery);
                $resetStmt->bindParam(':user_id', $row['user_id']);
                $resetStmt->execute();

                // 3. Check for Password Expiry (90 days)
                $expiryDays = 90;
                $passwordAge = time() - strtotime($row['password_changed_at']);
                if ($passwordAge > ($expiryDays * 24 * 60 * 60)) {
                    // Password expired
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
                                WHERE user_id = :user_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':attempts', $attempts);
                $updateStmt->bindParam(':user_id', $row['user_id']);
                $updateStmt->execute();

                error_log("Password verification failed. Attempts: $attempts");
                throw new Exception($msg);
            }

        } catch (Exception $e) {
            // Re-throw specific exceptions (Lockout/Expiry/Attempts) so auth.php can handle them
            if (
                $e->getMessage() === "PASSWORD_EXPIRED" ||
                strpos($e->getMessage(), "Account locked") !== false ||
                strpos($e->getMessage(), "Invalid credentials") !== false
            ) {
                throw $e;
            }
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    // Update profile
    public function update()
    {
        $fields = [];
        $params = [];

        // Dynamically build the query based on which properties are set
        if (isset($this->first_name)) {
            $fields[] = 'first_name = :first_name';
            $params[':first_name'] = htmlspecialchars(strip_tags($this->first_name));
        }
        if (isset($this->last_name)) {
            $fields[] = 'last_name = :last_name';
            $params[':last_name'] = htmlspecialchars(strip_tags($this->last_name));
        }
        if (isset($this->department)) {
            $fields[] = 'department = :department';
            $params[':department'] = htmlspecialchars(strip_tags($this->department));
        }
        if (isset($this->phone)) {
            $fields[] = 'phone = :phone';
            $params[':phone'] = htmlspecialchars(strip_tags($this->phone));
        }
        if (isset($this->job_title)) {
            $fields[] = 'job_title = :job_title';
            $params[':job_title'] = htmlspecialchars(strip_tags($this->job_title));
        }
        if (isset($this->is_team_member)) {
            $fields[] = 'is_team_member = :is_team_member'; // Added this block
            $params[':is_team_member'] = $this->is_team_member ? 1 : 0;
        }

        if (empty($fields)) {
            // Nothing to update
            return true;
        }

        $query = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id';
        $params[':user_id'] = htmlspecialchars(strip_tags($this->user_id));

        $stmt = $this->conn->prepare($query);

        if ($stmt->execute($params)) {
            // Return true if update was successful or if no rows were affected (data was the same)
            return true;
        }
        return false;
    }

    // Update profile picture
    public function updateProfilePicture()
    {
        $query = 'UPDATE ' . $this->table . '
            SET profile_picture = :profile_picture
            WHERE user_id = :user_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_picture', $this->profile_picture);
        $stmt->bindParam(':user_id', $this->user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}