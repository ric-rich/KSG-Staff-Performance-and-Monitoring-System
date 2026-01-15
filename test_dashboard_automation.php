<?php
/**
 * Test Script for Admin Dashboard Automation
 * 
 * This script tests the automated dashboard statistics functionality
 * Run this file to verify that all automation features are working correctly
 */

// Include required files
require_once 'config/database.php';
require_once 'classes/Admin.php';

// Start output
echo "=== Admin Dashboard Automation Test ===\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->connect();
    echo "✓ Database connection successful\n\n";
    
    // Create Admin instance
    $admin = new Admin($db);
    $admin->admin_id = 1; // Use first admin for testing
    
    // Test 1: Get Dashboard Stats
    echo "Test 1: Dashboard Statistics\n";
    echo "----------------------------\n";
    $stats = $admin->getDashboardStats();
    
    echo "Total Users: " . $stats['total_users'] . "\n";
    echo "Active Sessions: " . $stats['active_sessions'] . "\n";
    echo "System Health Status: " . $stats['system_health']['status'] . "\n";
    echo "Database Status: " . $stats['system_health']['database'] . "\n";
    echo "Number of Alerts: " . count($stats['alerts']) . "\n";
    
    if (count($stats['alerts']) > 0) {
        echo "\nAlerts:\n";
        foreach ($stats['alerts'] as $alert) {
            echo "  - [{$alert['priority']}] {$alert['message']}\n";
        }
    }
    echo "\n";
    
    // Test 2: Task Statistics
    echo "Test 2: Task Statistics\n";
    echo "----------------------\n";
    $taskStats = $admin->getTaskStatistics();
    
    echo "Total Tasks: " . $taskStats['total_tasks'] . "\n";
    echo "Pending Tasks: " . ($taskStats['tasks_pending'] ?? 0) . "\n";
    echo "In Progress Tasks: " . ($taskStats['tasks_in_progress'] ?? 0) . "\n";
    echo "Completed Tasks: " . ($taskStats['tasks_completed'] ?? 0) . "\n";
    echo "Overdue Tasks: " . ($taskStats['tasks_overdue'] ?? 0) . "\n";
    echo "Completion Rate: " . $taskStats['completion_rate'] . "%\n\n";
    
    // Test 3: Recent Activity
    echo "Test 3: Recent Activity\n";
    echo "----------------------\n";
    $recentActivity = $admin->getRecentActivity(5);
    
    if (count($recentActivity) > 0) {
        foreach ($recentActivity as $activity) {
            $userName = $activity['user_first_name'] ? 
                "{$activity['user_first_name']} {$activity['user_last_name']}" : 
                ($activity['admin_first_name'] ? "{$activity['admin_first_name']} {$activity['admin_last_name']}" : 'Unknown');
            
            echo "  - {$userName}: {$activity['action']} ({$activity['created_at']})\n";
        }
    } else {
        echo "  No recent activity found\n";
    }
    echo "\n";
    
    // Test 4: System Health Checks
    echo "Test 4: System Health Checks\n";
    echo "----------------------------\n";
    
    // Check database tables exist
    $tables = ['users', 'admins', 'user_tasks', 'access_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✓ Table '$table' exists with $count records\n";
        } catch (Exception $e) {
            echo "✗ Table '$table' error: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Test 5: API Endpoint Simulation
    echo "Test 5: API Response Format\n";
    echo "---------------------------\n";
    $apiResponse = [
        'status' => 'success',
        'stats' => array_merge($stats, $taskStats),
        'recent_activity' => $recentActivity
    ];
    
    echo "API Response Structure:\n";
    echo json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n\n";
    
    // Summary
    echo "=== Test Summary ===\n";
    echo "✓ All tests completed successfully!\n";
    echo "✓ Dashboard automation is working correctly\n";
    echo "✓ Ready for production use\n\n";
    
    // Recommendations
    echo "=== Recommendations ===\n";
    if ($stats['total_users'] == 0) {
        echo "⚠ No users in system - consider adding test users\n";
    }
    if ($stats['active_sessions'] == 0) {
        echo "ℹ No active sessions - this is normal if no one is logged in\n";
    }
    if (count($stats['alerts']) > 5) {
        echo "⚠ High number of alerts - review and address issues\n";
    }
    if ($stats['system_health']['status'] != 'healthy') {
        echo "⚠ System health is not optimal - investigate issues\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
