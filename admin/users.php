<?php
// Admin User Management - View all users with their tasks and uploads
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
    <title>User Management - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .back-btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .users-container {
            display: grid;
            gap: 20px;
        }
        .user-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .user-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        .user-details h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .user-details p {
            opacity: 0.9;
            font-size: 14px;
        }
        .user-stats {
            display: flex;
            gap: 20px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-item .value {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-item .label {
            font-size: 12px;
            opacity: 0.9;
        }
        .user-content {
            padding: 20px;
            display: none;
        }
        .user-content.active {
            display: block;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .task-item, .upload-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        .task-item h4, .upload-item h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .task-item p, .upload-item p {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-in_progress {
            background: #cfe2ff;
            color: #084298;
        }
        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .priority-low {
            background: #e7f3ff;
            color: #0066cc;
        }
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        .priority-high {
            background: #f8d7da;
            color: #842029;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .expand-icon {
            transition: transform 0.3s;
        }
        .expand-icon.rotated {
            transform: rotate(180deg);
        }
        .file-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .file-link:hover {
            text-decoration: underline;
        }
        .date-info {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-btn">← Back to Admin Panel</a>
        <h1>👥 User Management</h1>
        <p>View and manage all users with their assigned tasks and uploads</p>
    </div>

    <div class="stats-container" id="statsContainer">
        <div class="stat-card">
            <div class="number" id="totalUsers">-</div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="number" id="totalTasks">-</div>
            <div class="label">Total Tasks</div>
        </div>
        <div class="stat-card">
            <div class="number" id="completedTasks">-</div>
            <div class="label">Completed Tasks</div>
        </div>
        <div class="stat-card">
            <div class="number" id="totalUploads">-</div>
            <div class="label">Total Uploads</div>
        </div>
    </div>

    <div class="search-container">
        <input type="text" class="search-input" id="searchInput" placeholder="🔍 Search users by name, email, or department...">
    </div>

    <div id="loadingContainer" class="loading">
        <div class="spinner"></div>
        <p>Loading users...</p>
    </div>

    <div class="users-container" id="usersContainer" style="display: none;">
        <!-- Users will be loaded here dynamically -->
    </div>

    <script>
        let allUsers = [];

        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterUsers(searchTerm);
        });

        async function loadUsers() {
            try {
                const response = await fetch('../api/admin-users.php?action=get_users_with_details');
                const data = await response.json();

                if (data.status === 'success') {
                    allUsers = data.users;
                    displayUsers(allUsers);
                    updateStats(allUsers);
                    document.getElementById('loadingContainer').style.display = 'none';
                    document.getElementById('usersContainer').style.display = 'block';
                } else {
                    throw new Error(data.message || 'Failed to load users');
                }
            } catch (error) {
                console.error('Error loading users:', error);
                document.getElementById('loadingContainer').innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">⚠️</div>
                        <p>Error loading users: ${error.message}</p>
                    </div>
                `;
            }
        }

        function displayUsers(users) {
            const container = document.getElementById('usersContainer');
            
            if (users.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">👤</div>
                        <p>No users found</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = users.map(user => `
                <div class="user-card" data-user-id="${user.user_id}">
                    <div class="user-header" onclick="toggleUserDetails(${user.user_id})">
                        <div class="user-info">
                            <div class="user-avatar">
                                ${getInitials(user.first_name, user.last_name)}
                            </div>
                            <div class="user-details">
                                <h3>${user.first_name} ${user.last_name}</h3>
                                <p>${user.email} ${user.department ? '• ' + user.department : ''}</p>
                            </div>
                        </div>
                        <div class="user-stats">
                            <div class="stat-item">
                                <div class="value">${user.total_tasks}</div>
                                <div class="label">Tasks</div>
                            </div>
                            <div class="stat-item">
                                <div class="value">${user.completed_tasks}</div>
                                <div class="label">Completed</div>
                            </div>
                            <div class="stat-item">
                                <div class="value">${user.total_uploads}</div>
                                <div class="label">Uploads</div>
                            </div>
                            <span class="expand-icon" id="expand-${user.user_id}">▼</span>
                        </div>
                    </div>
                    <div class="user-content" id="content-${user.user_id}">
                        <div class="tabs">
                            <button class="tab active" onclick="switchTab(${user.user_id}, 'tasks')">
                                Tasks (${user.total_tasks})
                            </button>
                            <button class="tab" onclick="switchTab(${user.user_id}, 'uploads')">
                                Uploads (${user.total_uploads})
                            </button>
                            <button class="tab" onclick="switchTab(${user.user_id}, 'info')">
                                User Info
                            </button>
                        </div>
                        <div class="tab-content active" id="tasks-${user.user_id}">
                            ${displayTasks(user.recent_tasks)}
                        </div>
                        <div class="tab-content" id="uploads-${user.user_id}">
                            ${displayUploads(user.recent_uploads)}
                        </div>
                        <div class="tab-content" id="info-${user.user_id}">
                            ${displayUserInfo(user)}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function displayTasks(tasks) {
            if (!tasks || tasks.length === 0) {
                return `
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <p>No tasks assigned yet</p>
                    </div>
                `;
            }

            return tasks.map(task => `
                <div class="task-item">
                    <h4>${task.title}</h4>
                    <p>
                        <span class="status-badge status-${task.status}">${task.status.replace('_', ' ')}</span>
                        ${task.priority ? `<span class="priority-badge priority-${task.priority}">${task.priority}</span>` : ''}
                    </p>
                    <p><strong>Due Date:</strong> ${formatDate(task.due_date)}</p>
                    ${task.completion_date ? `<p><strong>Completed:</strong> ${formatDate(task.completion_date)}</p>` : ''}
                </div>
            `).join('');
        }

        function displayUploads(uploads) {
            if (!uploads || uploads.length === 0) {
                return `
                    <div class="empty-state">
                        <div class="empty-state-icon">📁</div>
                        <p>No uploads yet</p>
                    </div>
                `;
            }

            return uploads.map(upload => `
                <div class="upload-item">
                    <h4>📎 ${upload.file_name}</h4>
                    <p><strong>Task:</strong> ${upload.task_title}</p>
                    <p class="date-info">Uploaded: ${formatDate(upload.uploaded_at)}</p>
                </div>
            `).join('');
        }

        function displayUserInfo(user) {
            return `
                <div style="padding: 10px;">
                    <p style="margin-bottom: 10px;"><strong>Email:</strong> ${user.email}</p>
                    <p style="margin-bottom: 10px;"><strong>Name:</strong> ${user.first_name} ${user.last_name}</p>
                    ${user.department ? `<p style="margin-bottom: 10px;"><strong>Department:</strong> ${user.department}</p>` : ''}
                    <p style="margin-bottom: 10px;"><strong>Member Since:</strong> ${formatDate(user.created_at)}</p>
                    <p style="margin-bottom: 10px;"><strong>User ID:</strong> ${user.user_id}</p>
                </div>
            `;
        }

        function toggleUserDetails(userId) {
            const content = document.getElementById(`content-${userId}`);
            const icon = document.getElementById(`expand-${userId}`);
            
            if (content.classList.contains('active')) {
                content.classList.remove('active');
                icon.classList.remove('rotated');
            } else {
                content.classList.add('active');
                icon.classList.add('rotated');
            }
        }

        function switchTab(userId, tabName) {
            // Update tab buttons
            const card = document.querySelector(`[data-user-id="${userId}"]`);
            const tabs = card.querySelectorAll('.tab');
            const tabContents = card.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(`${tabName}-${userId}`).classList.add('active');
        }

        function filterUsers(searchTerm) {
            const filtered = allUsers.filter(user => {
                const fullName = `${user.first_name} ${user.last_name}`.toLowerCase();
                const email = user.email.toLowerCase();
                const department = (user.department || '').toLowerCase();
                
                return fullName.includes(searchTerm) || 
                       email.includes(searchTerm) || 
                       department.includes(searchTerm);
            });

            displayUsers(filtered);
        }

        function updateStats(users) {
            const totalUsers = users.length;
            const totalTasks = users.reduce((sum, user) => sum + parseInt(user.total_tasks), 0);
            const completedTasks = users.reduce((sum, user) => sum + parseInt(user.completed_tasks), 0);
            const totalUploads = users.reduce((sum, user) => sum + parseInt(user.total_uploads), 0);

            document.getElementById('totalUsers').textContent = totalUsers;
            document.getElementById('totalTasks').textContent = totalTasks;
            document.getElementById('completedTasks').textContent = completedTasks;
            document.getElementById('totalUploads').textContent = totalUploads;
        }

        function getInitials(firstName, lastName) {
            return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
    </script>
</body>
</html>
