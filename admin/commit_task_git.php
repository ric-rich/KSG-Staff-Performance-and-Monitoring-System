<?php
// admin/commit_task_git.php

// Ensure no output before JSON
ob_start();

session_start();
header('Content-Type: application/json');

// 1. Authentication Check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in as admin.']);
    exit;
}

// 2. Input Handling
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback to POST
    $input = $_POST;
}

$taskId = $input['task_id'] ?? null;
$commitMessage = $input['commit_message'] ?? '';

if (!$taskId || empty($commitMessage)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Task ID and Commit Message are required.']);
    exit;
}

// 3. Environment Setup
$projectRoot = realpath(__DIR__ . '/../');
if (!$projectRoot) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not determine project root.']);
    exit;
}

// Check for .git directory
if (!is_dir($projectRoot . '/.git')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Project root is not a Git repository (missing .git).']);
    exit;
}

// 4. Execute Git Commands
// We change directory to the project root to run git commands
chdir($projectRoot);

// Sanitize inputs for shell execution to prevent injection
// We include the Task ID in the commit message for traceability
$fullMessage = "Task #{$taskId}: " . $commitMessage;
$safeMessage = escapeshellarg($fullMessage);

// Command: Add all changes, then commit
// 2>&1 redirects stderr to stdout so we can capture errors
$command = "git add . 2>&1 && git commit -m $safeMessage 2>&1";

$output = [];
$returnCode = 0;

exec($command, $output, $returnCode);

// 5. Response
if ($returnCode === 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Git commit performed successfully.',
        'details' => $output
    ]);
} else {
    // Check if failure was due to "nothing to commit" which git treats as exit code 1 sometimes
    $outputStr = implode("\n", $output);
    if (strpos($outputStr, 'nothing to commit') !== false) {
        echo json_encode([
            'status' => 'success', // Treat as success or warning
            'message' => 'Nothing to commit (working tree clean).',
            'details' => $output
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Git commit failed.',
            'details' => $output
        ]);
    }
}
ob_end_flush();
?>