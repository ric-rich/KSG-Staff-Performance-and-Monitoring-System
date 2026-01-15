<?php
require_once __DIR__ . '/../inc/maintenance_check.php';
// Admin Dashboard Index - Quick Access to Admin Features
require_once __DIR__ . '/../inc/auth.php';

if (!is_admin()) {
    header('Location: ../INDEX.HTML');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quick Access</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .card-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .card-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        .icon-messages {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .icon-users {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .icon-tasks {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .icon-settings {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .icon-analytics {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .icon-dashboard {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            color: #667eea;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body>
    <a href="../INDEX.HTML" class="logout-btn">← Back to Main</a>

    <div class="container">
        <div class="header">
            <h1>🛡️ Admin Control Panel</h1>
            <p>Quick access to all administrative functions</p>
        </div>

        <div class="grid">
            <a href="messages.php" class="card">
                <div class="card-icon icon-messages">
                    📧
                </div>
                <div class="card-title">
                    Contact Messages
                    <span class="badge" id="messageBadge">New</span>
                </div>
                <div class="card-description">
                    View and manage all contact form submissions from visitors
                </div>
            </a>

            <a href="../INDEX.HTML" class="card">
                <div class="card-icon icon-dashboard">
                    📊
                </div>
                <div class="card-title">Main Dashboard</div>
                <div class="card-description">
                    Access the main admin dashboard with full system overview
                </div>
            </a>

            <a href="users.php" class="card">
                <div class="card-icon icon-users">
                    👥
                </div>
                <div class="card-title">User Management</div>
                <div class="card-description">
                    View all users with their assigned tasks and uploads
                </div>
            </a>

            <a href="#" onclick="alert('Feature coming soon!'); return false;" class="card">
                <div class="card-icon icon-tasks">
                    ✅
                </div>
                <div class="card-title">Task Management</div>
                <div class="card-description">
                    Assign, track, and manage tasks across the organization
                </div>
            </a>

            <a href="#" onclick="alert('Feature coming soon!'); return false;" class="card">
                <div class="card-icon icon-analytics">
                    📈
                </div>
                <div class="card-title">Analytics & Reports</div>
                <div class="card-description">
                    View detailed analytics and generate comprehensive reports
                </div>
            </a>

            <a href="#" onclick="alert('Feature coming soon!'); return false;" class="card">
                <div class="card-icon icon-settings">
                    ⚙️
                </div>
                <div class="card-title">System Settings</div>
                <div class="card-description">
                    Configure system settings, security, and preferences
                </div>
            </a>
        </div>
    </div>

    <script>
        // Load unread message count
        fetch('../api/admin.php?action=get_unread_messages_count')
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    document.getElementById('messageBadge').textContent = data.count;
                } else {
                    document.getElementById('messageBadge').style.display = 'none';
                }
            })
            .catch(() => {
                document.getElementById('messageBadge').style.display = 'none';
            });
    </script>
</body>

</html>