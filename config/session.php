<?php
/**
 * Session Management Configuration
 *
 * This file configures and starts the user session with secure settings.
 * It should be included at the very beginning of any script that needs session access.
 */

// 1. Session Configuration
// ------------------------

// Use a more secure session name than the default 'PHPSESSID'
session_name('KSG_SMI_SESSID');

// Determine if the connection is secure (HTTPS)
$is_secure = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $is_secure = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https-equiv' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
    $is_secure = true;
}

// Set session cookie parameters for security.
// Note: For production, you should set 'domain' to your actual domain.
$cookie_params = [
    'lifetime' => 0, // Expire when browser closes
    'path' => '/',
    'domain' => '', // e.g., '.yourdomain.com'
    'secure' => $is_secure,
    'httponly' => true,
    'samesite' => 'Lax' // Can be 'Strict' or 'Lax'
];
session_set_cookie_params($cookie_params);


// 2. Start the Session
// --------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// 3. Session Security Enhancements
// --------------------------------

// Session Timeout/Expiry (30 minutes)
$session_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_duration)) {
    // Last request was more than 30 minutes ago
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage
    session_start();     // Start a new, clean session
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time stamp


// Session ID Regeneration to prevent session fixation (every 5 minutes)
$regeneration_interval = 300;
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > $regeneration_interval) {
    // Session started more than 5 minutes ago, regenerate ID
    session_regenerate_id(true);    // Pass true to delete old session file
    $_SESSION['CREATED'] = time();  // Update creation time
}

// Basic Session Hijacking Prevention
if (isset($_SESSION['USER_AGENT'])) {
    if ($_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
        // User agent has changed, this could be a sign of hijacking
        session_unset();
        session_destroy();
        // Optionally, you could log this attempt before destroying the session.
    }
} else {
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
}