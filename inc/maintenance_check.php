<?php
// inc/maintenance_check.php

// Define the path to the lock file
define('LOCK_FILE', __DIR__ . '/../config/system.lock');

// Function to check if system is locked
function is_system_locked()
{
    return file_exists(LOCK_FILE);
}

// Perform the check
if (is_system_locked()) {
    // If the script accessing this is NOT the lockout management script
    if (basename($_SERVER['PHP_SELF']) !== 'lockout.php') {
        http_response_code(503); // Service Unavailable
        die('<!DOCTYPE html>
        <html>
        <head>
            <title>System Unavailable</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
             <style>
                body {
                    background: #111827;
                    color: #fff;
                    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    margin: 0;
                }
                .container {
                    padding: 2rem;
                    max-width: 600px;
                }
                h1 {
                    font-size: 3rem;
                    color: #e11d48;
                    margin-bottom: 1rem;
                    font-weight: 800;
                }
                p {
                    font-size: 1.25rem;
                    color: #9ca3af;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>System Locked</h1>
                <p>This platform is currently unavailable due to suspended access.<br>Please contact the system administrator.</p>
            </div>
        </body>
        </html>');
    }
}
?>