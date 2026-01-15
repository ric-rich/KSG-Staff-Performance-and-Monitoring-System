<?php
require_once '../api/fpdf.php';
require_once '../config/database.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

session_start();
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

// Initialize database
$database = new Database();
$db = $database->connect();

function generatePerformanceReport($db, $user_id, $type = 'all') {
    // Get user info
    $userQuery = "SELECT first_name, last_name, email, department FROM users WHERE user_id = :user_id";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':user_id', $user_id);
    $userStmt->execute();
    $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userInfo) {
        throw new Exception('User not found');
    }

    // Determine date range and report title
    $dateClause = '';
    switch($type) {
        case 'weekly':
            $dateClause = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
            $reportTitle = 'Weekly Performance Report';
            break;
        case 'monthly':
            $dateClause = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            $reportTitle = 'Monthly Performance Report';
            break;
        default:
            $reportTitle = 'All-Time Performance Report';
    }

    // Get tasks
    $tasksQuery = "SELECT title, status, due_date, completion_date, created_at 
                   FROM user_tasks 
                   WHERE user_id = :user_id $dateClause 
                   ORDER BY created_at DESC";
    $tasksStmt = $db->prepare($tasksQuery);
    $tasksStmt->bindParam(':user_id', $user_id);
    $tasksStmt->execute();
    $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Create PDF with FPDF
    class PDF extends FPDF {
        function Header() {
            global $reportTitle;
            $this->SetFont('Helvetica', 'B', 16);
            $this->Cell(0, 10, $reportTitle, 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // User Info
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'User Information', 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, 'Name: ' . $userInfo['first_name'] . ' ' . $userInfo['last_name'], 0, 1);
    $pdf->Cell(0, 6, 'Department: ' . ($userInfo['department'] ?? 'N/A'), 0, 1);
    $pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(5);

    // Tasks Table
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Task Details', 0, 1);
    
    // Table headers
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(80, 7, 'Task', 1);
    $pdf->Cell(30, 7, 'Status', 1);
    $pdf->Cell(35, 7, 'Due Date', 1);
    $pdf->Cell(35, 7, 'Completed', 1);
    $pdf->Ln();

    // Table content
    $pdf->SetFont('Helvetica', '', 9);
    foreach($tasks as $task) {
        $pdf->Cell(80, 6, substr($task['title'], 0, 45), 1);
        $pdf->Cell(30, 6, ucfirst($task['status']), 1);
        $pdf->Cell(35, 6, date('Y-m-d', strtotime($task['due_date'])), 1);
        $pdf->Cell(35, 6, $task['completion_date'] ? date('Y-m-d', strtotime($task['completion_date'])) : '-', 1);
        $pdf->Ln();
    }

    return $pdf;
}

try {
    $type = $_GET['type'] ?? 'all';
    $pdf = generatePerformanceReport($db, $_SESSION['user_id'], $type);
    
    // Output PDF
    $filename = 'performance_report_' . date('Y-m-d') . '.pdf';
    $pdf->Output('D', $filename);
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
}
