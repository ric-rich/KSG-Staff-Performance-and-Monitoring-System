<?php
require_once '../config/session.php';

// Prevent any output before headers
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1); // Temporarily enable display_errors for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Add this before processing any request
$raw = file_get_contents('php://input');
error_log("Raw input: " . $raw);

// Set headers for CORS
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost'));
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    require_once '../classes/User.php';
    require_once '../classes/Admin.php';
    require_once '../config/database.php';
    // Initialize database connection
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    error_log("Configuration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'System configuration error'
    ]);
    exit();
}

// Catch any JSON parsing errors
$rawData = file_get_contents("php://input");
if (!empty($rawData)) {
    $data = json_decode($rawData);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid JSON data provided'
        ]);
        exit();
    }
} else {
    $data = null;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

function logAccess($db, $user_id = null, $admin_id = null, $action, $ip_address)
{
    $query = 'INSERT INTO access_logs (user_id, admin_id, action, ip_address, user_agent) 
              VALUES (:user_id, :admin_id, :action, :ip_address, :user_agent)';

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':ip_address', $ip_address);
    $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    $stmt->execute();
}

try {
    switch ($action) {
        case 'user_login':
            try {
                // Ensure we have input data
                if (empty($data)) {
                    throw new Exception('No data provided');
                }

                // Validate required fields
                if (empty($data->email) || empty($data->password)) {
                    throw new Exception('Email and password are required');
                }

                $user = new User($db);
                $result = $user->login($data->email, $data->password);

                if (!$result) {
                    http_response_code(401);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid credentials'
                    ]);
                    exit(); // Important: exit after sending response
                }

                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user_type'] = 'user';

                // Log successful login
                logAccess($db, $result['user_id'], null, 'user_login', $_SERVER['REMOTE_ADDR']);

                // Send success response
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'data' => [
                        'user_id' => $result['user_id'],
                        'email' => $result['email'],
                        'name' => trim($result['first_name'] . ' ' . $result['last_name']),
                        'is_admin' => $result['is_admin'] ?? 0
                    ]
                ]);
                exit(); // Important: exit after sending response

            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
                exit(); // Important: exit after sending response
            }


        case 'admin_login':
            try {
                if (empty($data)) {
                    throw new Exception('No data provided');
                }

                if (empty($data->email) || empty($data->password) || empty($data->index_code)) {
                    throw new Exception('Email, password, and index code are required');
                }

                $admin = new Admin($db);
                $result = $admin->login($data->email, $data->password, $data->index_code);

                if (!$result) {
                    http_response_code(401);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid admin credentials'
                    ]);
                    break;
                }

                $_SESSION['admin_id'] = $result['admin_id'];
                $_SESSION['user_type'] = 'admin';

                // Log access
                logAccess($db, null, $result['admin_id'], 'admin_login', $_SERVER['REMOTE_ADDR']);

                // Add combined name field for frontend
                $result['name'] = trim($result['first_name'] . ' ' . $result['last_name']);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Admin login successful',
                    'data' => array_diff_key($result, ['password_hash' => 0])
                ]);
                exit(); // Exit after sending response

            } catch (Exception $e) {
                error_log("Admin login error: " . $e->getMessage());
                http_response_code($e instanceof PDOException ? 500 : 400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'user_register':
            try {
                // Check for required data
                if (empty($data)) {
                    throw new Exception('No registration data provided');
                }

                if (
                    !empty($data->email) && !empty($data->password) &&
                    !empty($data->first_name) && !empty($data->last_name)
                ) {

                    // Email format validation
                    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Invalid email format');
                    }

                    // Check if email already exists
                    $checkQuery = "SELECT COUNT(*) as count FROM users WHERE email = :email";
                    $stmt = $db->prepare($checkQuery);
                    $stmt->bindParam(':email', $data->email);
                    $stmt->execute();
                    $result = $stmt->fetch();

                    if ($result['count'] > 0) {
                        http_response_code(409); // Conflict status code
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'This email address is already registered'
                        ]);
                        break;
                    }

                    // Password strength validation
                    if (strlen($data->password) < 8) {
                        throw new Exception('Password must be at least 8 characters long');
                    }
                    if (!preg_match('/[0-9]/', $data->password)) {
                        throw new Exception('Password must contain at least one number');
                    }
                    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data->password)) {
                        throw new Exception('Password must contain at least one special character');
                    }

                    $user = new User($db);
                    $user->email = $data->email;
                    $user->password_hash = password_hash($data->password, PASSWORD_DEFAULT);
                    $user->first_name = strip_tags($data->first_name);
                    $user->last_name = strip_tags($data->last_name);
                    $user->department = strip_tags($data->department ?? '');

                    // Set default user settings
                    $defaultSettings = json_encode([
                        'theme' => 'light',
                        'language' => 'en',
                        'notifications' => true
                    ]);

                    if ($user->create($defaultSettings)) {
                        http_response_code(201); // Created status code
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Registration successful. You can now login.'
                        ]);
                    } else {
                        throw new Exception('Failed to create user account');
                    }
                } else {
                    throw new Exception('Missing required fields');
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'admin_register':
            try {
                if (empty($data)) {
                    throw new Exception('No data provided');
                }

                if (empty($data->email) || empty($data->password) || empty($data->name) || empty($data->index_code)) {
                    throw new Exception('Name, email, password, and index code are required');
                }

                // Check if email already exists
                $checkQuery = "SELECT COUNT(*) as count FROM admins WHERE email = :email";
                $stmt = $db->prepare($checkQuery);
                $stmt->bindParam(':email', $data->email);
                $stmt->execute();
                $result = $stmt->fetch();

                if ($result['count'] > 0) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'An admin with this email already exists'
                    ]);
                    break;
                }

                // Password strength validation
                if (strlen($data->password) < 8) {
                    throw new Exception('Password must be at least 8 characters long');
                }
                if (!preg_match('/[0-9]/', $data->password)) {
                    throw new Exception('Password must contain at least one number');
                }
                if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data->password)) {
                    throw new Exception('Password must contain at least one special character');
                }

                $admin = new Admin($db);
                $admin->email = $data->email;
                $admin->password_hash = password_hash($data->password, PASSWORD_DEFAULT);
                $admin->index_code = $data->index_code;

                // Split name into first and last
                $nameParts = explode(' ', $data->name, 2);
                $admin->first_name = $nameParts[0];
                $admin->last_name = isset($nameParts[1]) ? $nameParts[1] : '';

                if ($admin->create()) {
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Admin registered successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to create admin account'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Admin registration error: " . $e->getMessage());
                http_response_code($e instanceof PDOException ? 500 : 400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'logout':
            // Clear session data
            session_unset();

            // Clear session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            // Destroy the session
            session_destroy();

            echo json_encode([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ]);
            break;

        case 'check_session':
            if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'user') {
                // Fetch user details
                $query = "SELECT user_id, email, first_name, last_name, settings FROM users WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    echo json_encode([
                        'status' => 'success',
                        'logged_in' => true,
                        'user_type' => 'user',
                        'user' => $user
                    ]);
                } else {
                    // User in session but not in DB, destroy session
                    session_destroy();
                    echo json_encode(['status' => 'success', 'logged_in' => false]);
                }
            } elseif (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
                // Fetch admin details
                $query = "SELECT admin_id, email, first_name, last_name FROM admins WHERE admin_id = :admin_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                $stmt->execute();
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'logged_in' => true,
                    'user_type' => 'admin',
                    'user' => $admin // Keep 'user' key for frontend consistency
                ]);

            } else {
                echo json_encode([
                    'status' => 'success',
                    'logged_in' => false
                ]);
            }
            break;

        case 'list_users':
            // Verify admin access
            if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Admin access required'
                ]);
                exit();
            }

            try {
                $query = "SELECT u.user_id, u.email, u.first_name, u.last_name, u.department, u.created_at,
                     COUNT(DISTINCT t.task_id) as total_tasks,
                     COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.task_id END) as completed_tasks
                     FROM users u
                     LEFT JOIN user_tasks t ON u.user_id = t.user_id
                     GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.department, u.created_at
                     ORDER BY u.created_at DESC";

                $stmt = $db->prepare($query);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'users' => array_map(function ($user) {
                        return [
                            'user_id' => $user['user_id'],
                            'email' => $user['email'],
                            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                            'department' => $user['department'],
                            'created_at' => $user['created_at'],
                            'total_tasks' => $user['total_tasks'],
                            'completed_tasks' => $user['completed_tasks']
                        ];
                    }, $users)
                ]);
                exit();
            } catch (Exception $e) {
                error_log("List users error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to retrieve users list'
                ]);
                exit();
            }


        case 'get_users_for_assignment':
            // Verify admin access
            if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Admin access required'
                ]);
                exit();
            }

            try {
                $query = "SELECT u.user_id, 
                     u.email, 
                     u.first_name, 
                     u.last_name, 
                     u.department,
                     COUNT(t.task_id) as active_tasks
                     FROM users u
                     LEFT JOIN user_tasks t ON u.user_id = t.user_id 
                        AND t.status = 'pending'
                     GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.department
                     ORDER BY u.last_name, u.first_name";

                $stmt = $db->prepare($query);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($users === false) {
                    throw new Exception("Failed to fetch users");
                }

                echo json_encode([
                    'status' => 'success',
                    'users' => array_map(function ($user) {
                        return [
                            'id' => $user['user_id'],
                            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                            'email' => $user['email'],
                            'department' => $user['department'],
                            'active_tasks' => (int) $user['active_tasks']
                        ];
                    }, $users)
                ]);
                exit();
            } catch (Exception $e) {
                error_log("Get users for assignment error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to retrieve users list',
                    'debug' => $e->getMessage()
                ]);
                exit();
            }


        case 'change_password':
            try {
                $userId = $_SESSION['user_id'] ?? null;
                $adminId = $_SESSION['admin_id'] ?? null;
                $userType = $_SESSION['user_type'] ?? null;

                if (!$userId && !$adminId) {
                    throw new Exception('User not authenticated');
                }

                if (!$data || !isset($data->current_password) || !isset($data->new_password) || !isset($data->confirm_password)) {
                    throw new Exception('Missing required fields');
                }

                if ($data->new_password !== $data->confirm_password) {
                    throw new Exception('New passwords do not match');
                }

                if (strlen($data->new_password) < 8) {
                    throw new Exception('New password must be at least 8 characters long');
                }
                if (!preg_match('/[0-9]/', $data->new_password)) {
                    throw new Exception('New password must contain at least one number');
                }
                if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data->new_password)) {
                    throw new Exception('New password must contain at least one special character');
                }

                // Determine table and ID based on user type
                if ($userType === 'admin' && $adminId) {
                    $table = 'admins';
                    $idColumn = 'admin_id';
                    $id = $adminId;
                } else {
                    $table = 'users';
                    $idColumn = 'user_id';
                    $id = $userId;
                }

                // Get current password hash
                $query = "SELECT password_hash FROM $table WHERE $idColumn = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $account = $stmt->fetch();

                if (!$account || !password_verify($data->current_password, $account['password_hash'])) {
                    throw new Exception('Current password is incorrect');
                }

                // Update password and timestamp
                $new_hash = password_hash($data->new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE $table SET password_hash = :password_hash, password_changed_at = NOW() WHERE $idColumn = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':password_hash', $new_hash);
                $update_stmt->bindParam(':id', $id);

                if ($update_stmt->execute()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Password updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update password');
                }

            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'request_password_reset':
            try {
                if (!$data || !isset($data->email)) {
                    throw new Exception('Email address is required');
                }

                // Check if email exists
                $query = "SELECT user_id, email, first_name FROM users WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $data->email);
                $stmt->execute();
                $user = $stmt->fetch();

                if (!$user) {
                    // For security, don't reveal if email exists
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'If your email exists in our system, you will receive reset instructions.'
                    ]);
                    exit();
                }

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store reset token
                $updateQuery = "UPDATE users SET 
                           reset_token = :token,
                           reset_token_expires = :expires 
                           WHERE user_id = :user_id";

                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':token', $token);
                $updateStmt->bindParam(':expires', $expires);
                $updateStmt->bindParam(':user_id', $user['user_id']);

                if ($updateStmt->execute()) {
                    // TODO: Send actual email with reset link
                    // For development, just return token
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Password reset instructions sent',
                        'debug' => [
                            'token' => $token,
                            'expires' => $expires
                        ]
                    ]);
                } else {
                    throw new Exception('Failed to initiate password reset');
                }

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'request_reset':
            try {
                if (!$data || !isset($data->email)) {
                    throw new Exception('Email is required');
                }

                // Generate unique reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token to database
                $query = "UPDATE users 
                     SET reset_token = :token,
                         reset_token_expires = :expires
                     WHERE email = :email";

                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':token' => $token,
                    ':expires' => $expires,
                    ':email' => $data->email
                ]);

                if ($stmt->rowCount() > 0) {
                    // For development return token directly
                    // In production would send via email
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Password reset instructions sent',
                        'debug' => [
                            'token' => $token,
                            'expires' => $expires
                        ]
                    ]);
                } else {
                    throw new Exception('Email not found');
                }

            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'verify_reset_token':
            try {
                if (!$data || !isset($data->token)) {
                    throw new Exception('Reset token is required');
                }

                $query = "SELECT user_id FROM users 
                     WHERE reset_token = :token 
                     AND reset_token_expires > NOW()";

                $stmt = $db->prepare($query);
                $stmt->execute([':token' => $data->token]);

                if ($stmt->fetch()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Valid reset token'
                    ]);
                } else {
                    throw new Exception('Invalid or expired reset token');
                }

            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'forgot_password':
            try {
                if (!$data || !isset($data->email) || !isset($data->user_type)) {
                    throw new Exception('Email and user type are required');
                }

                $email = $data->email;
                $user_type = $data->user_type;
                $table = '';
                $id_column = '';

                if ($user_type === 'user') {
                    $table = 'users';
                    $id_column = 'user_id';
                } elseif ($user_type === 'admin') {
                    $table = 'admins';
                    $id_column = 'admin_id';
                } else {
                    throw new Exception('Invalid user type specified');
                }

                // Check if email exists
                $query = "SELECT $id_column, email, first_name FROM $table WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $account = $stmt->fetch();

                if (!$account) {
                    // For security, don't reveal if email exists.
                    echo json_encode(['status' => 'success', 'message' => 'If an account with that email exists, a password reset link has been sent.']);
                    exit();
                }

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store reset token
                $updateQuery = "UPDATE $table SET reset_token = :token, reset_token_expires = :expires WHERE $id_column = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':token', $token);
                $updateStmt->bindParam(':expires', $expires);
                $updateStmt->bindParam(':id', $account[$id_column]);

                if ($updateStmt->execute()) {
                    // In a production environment, you would send an email here.
                    // For now, we'll just confirm success.
                    error_log("Password reset for $email. Token: $token");
                    echo json_encode(['status' => 'success', 'message' => 'Password reset link sent! Please check your email.']);
                } else {
                    throw new Exception('Failed to generate password reset link.');
                }

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'reset_password':
            try {
                if (!$data || !isset($data->token) || !isset($data->password)) {
                    throw new Exception('Token and new password are required');
                }

                // Verify token and get user
                $query = "SELECT user_id FROM users 
                     WHERE reset_token = :token 
                     AND reset_token_expires > NOW()";

                $stmt = $db->prepare($query);
                $stmt->execute([':token' => $data->token]);
                $user = $stmt->fetch();

                if (!$user) {
                    throw new Exception('Invalid or expired reset token');
                }

                // Update password and clear token
                $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users 
                          SET password_hash = :password_hash,
                              reset_token = NULL,
                              reset_token_expires = NULL
                          WHERE user_id = :user_id";

                $stmt = $db->prepare($updateQuery);
                $stmt->execute([
                    ':password_hash' => $password_hash,
                    ':user_id' => $user['user_id']
                ]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Password has been reset successfully'
                ]);

            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action specified'
            ]);
    }
} catch (Exception $e) {
    error_log('api/auth.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
?>