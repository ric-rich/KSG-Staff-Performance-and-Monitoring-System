<?php
// scripts/deploy.php

/**
 * Simple Deployment Automation Script
 * 
 * Usage:
 * 1. CLI: php scripts/deploy.php
 * 2. Webhook: https://your-site.com/scripts/deploy.php?key=YOUR_SECRET_KEY
 */

// Configuration
define('DEPLOY_KEY', getenv('DEPLOY_KEY') ?: 'change_this_to_a_random_secure_string');
define('LOG_FILE', __DIR__ . '/deploy.log');
define('REPO_PATH', realpath(__DIR__ . '/../'));

// Function to log messages
function logMsg($message)
{
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $formatted, FILE_APPEND);
    echo $formatted; // Echo to output for CLI/Web response
}

// 1. Security Check
$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? null;

if (!$isCli && $providedKey !== DEPLOY_KEY) {
    http_response_code(403);
    die('Access Denied: Invalid deployment key.');
}

logMsg("Deployment started...");

// 2. Change to Project Root
if (!chdir(REPO_PATH)) {
    logMsg("Error: Could not change directory to " . REPO_PATH);
    exit(1);
}

// 3. Git Pull
logMsg("Pulling latest changes from git...");
$output = [];
$returnVar = 0;
// Note: 2>&1 redirects stderr to stdout
exec("git pull origin main 2>&1", $output, $returnVar);

foreach ($output as $line) {
    logMsg("GIT: $line");
}

if ($returnVar !== 0) {
    logMsg("Error: Git pull failed.");
    exit(1);
}

// 3.5 Auto-create .htaccess if missing
$htaccessPath = REPO_PATH . '/.htaccess';
$htaccessSample = REPO_PATH . '/.htaccess.sample';

if (!file_exists($htaccessPath) && file_exists($htaccessSample)) {
    logMsg("Creating .htaccess from sample...");
    if (copy($htaccessSample, $htaccessPath)) {
        logMsg("SUCCESS: .htaccess created using default template.");
        logMsg("WARNING: You must manually edit .htaccess to set DB passwords!");
    } else {
        logMsg("ERROR: Failed to create .htaccess.");
    }
}

// 4. Updates & Maintenance
// Reload DB config to check connectivity
require_once REPO_PATH . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    if ($conn) {
        logMsg("Database connection verification: SUCCESS");
    } else {
        logMsg("Database connection verification: FAILED");
    }
} catch (Exception $e) {
    logMsg("Database check error: " . $e->getMessage());
}

// 5. Cleanup
// Example: Clear any temp files if you had a cache folder
// exec("rm -rf " . REPO_PATH . "/cache/*");

logMsg("Deployment completed successfully.");
?>