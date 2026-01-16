<?php
// add_user.php

// It's good practice to have a centralized bootstrap or config file
// that handles session start, configuration, and autoloading.
// require_once '../bootstrap.php';
session_start();

// Check if the admin is logged in, otherwise redirect to the login page.
// This is a crucial security step.
if (!isset($_SESSION['admin_id'])) {
    // Redirect to admin login page
    header('Location: ../index.php');
    exit();
}

// Include your database connection file.
// The path '../includes/db_connection.php' is assumed. Adjust if necessary.
require_once '../includes/db_connection.php';

$errors = [];
$success_message = ''; // This will be handled by session flash messages.

// Retrieve flash messages from session if implementing PRG pattern
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']);
}

// We only process the form if it was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and validate input from the form
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    // Sanitize strings that might be displayed back to the user.
    $first_name = htmlspecialchars(trim($_POST['first_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars(trim($_POST['last_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $department = htmlspecialchars(trim($_POST['department'] ?? ''), ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $job_title = htmlspecialchars(trim($_POST['job_title'] ?? ''), ENT_QUOTES, 'UTF-8');

    // 2. Perform basic validation checks
    if (!$email) {
        $errors[] = 'A valid email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }

    // 3. If validation passes, proceed with database operations
    if (empty($errors)) {
        try {
            // First, check if a user with this email already exists to avoid errors
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn()) {
                $errors[] = 'A user with this email address already exists.';
            } else {
                // 4. Securely hash the password using BCRYPT
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // 5. Call the 'create_user' stored procedure
                $stmt = $pdo->prepare("CALL create_user(?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $email,
                    $password_hash,
                    $first_name,
                    $last_name,
                    $department,
                    $phone,
                    $job_title
                ]);

                $_SESSION['success_message'] = "User '{$first_name} {$last_name}' was created successfully!";
                // Optional: Log this administrative action
                // log_admin_action($_SESSION['admin_id'], 'Created new user: ' . $email);

                // Redirect to prevent form resubmission (PRG Pattern)
                header('Location: manage_users.php'); // Adjust to your user list page
                exit();
            }
        } catch (PDOException $e) {
            // In a production environment, you would log this error to a file
            // error_log("User creation failed: " . $e->getMessage());
            $errors[] = "Database error: Could not create the user. Please contact support.";
        }

        // If there were validation errors, store them in the session and redirect back
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            // Redirect back to the form page to display errors
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// The rest of your file would be the HTML form to add a user.
// You would display the $errors and $success_message variables here.
// Example:
/*
 <?php if (!empty($errors)): ?>
   <div class="errors">...</div>
 <?php endif; ?>
 <form method="POST">...</form>
*/
?>