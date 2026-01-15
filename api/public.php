<?php
// Public API - No authentication required

header('Content-Type: application/json');

require_once '../config/database.php';

function sendError($message, $statusCode = 500)
{
    http_response_code($statusCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();

    $action = $_GET['action'] ?? '';
    // error_log("Public API Action: " . $action);
    // $action = 'get_team_members';

    switch ($action) {
        case 'get_site_metrics':
            $query = "SELECT * FROM site_metrics";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $metrics_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $metrics = [];
            foreach ($metrics_raw as $m) {
                $metrics[$m['metric_key']] = [
                    'label' => $m['metric_label'],
                    'value' => $m['metric_value']
                ];
            }
            echo json_encode(['status' => 'success', 'metrics' => $metrics]);
            break;



        case 'get_team_members':
            $adminsQuery = "SELECT admin_id as id, CONCAT(first_name, ' ', last_name) as name, job_title, profile_picture, 'Admin' as type, created_at FROM admins";
            $usersQuery = "SELECT user_id as id, CONCAT(first_name, ' ', last_name) as name, job_title, profile_picture, 'Staff' as type, created_at FROM users";

            $admins = [];
            $users = [];

            try {
                $stmt = $db->prepare($adminsQuery);
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Ignore errors (e.g. missing columns) for robustness
            }

            try {
                $stmt = $db->prepare($usersQuery);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Ignore errors
            }

            $team = array_merge($admins, $users);

            // Base64 encode profile pictures
            foreach ($team as &$member) {
                if (!empty($member['profile_picture'])) {
                    $member['profile_picture'] = 'data:image/jpeg;base64,' . base64_encode($member['profile_picture']);
                }
            }

            usort($team, function ($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });

            echo json_encode(['status' => 'success', 'team' => $team]);
            break;

        default:
            sendError('Invalid public action: "' . $action . '"', 400);
            break;
    }
} catch (Exception $e) {
    sendError('A server error occurred: ' . $e->getMessage());
}