# KSG SMI Performance Monitoring and Evaluation System


## Features

### User Features
- **User Authentication**: Secure login and registration system
- **Task Management**: View assigned tasks, upload work files, track progress
- **Profile Management**: Update personal information, change passwords, upload profile pictures
- **Reports**: Generate and download performance reports (weekly, time tracking, project status)
- **Settings**: Customize display preferences, language, notifications, and privacy settings
- **File Uploads**: Upload work files for tasks with support for multiple file types

### Admin Features
- **User Management**: Create, edit, delete users, reset passwords, manage departments
- **Task Assignment**: Assign tasks from predefined templates to users
- **Security Settings**: Configure password policies, 2FA, session timeouts, view access logs
- **Backup & Restore**: Create system backups, schedule automatic backups, restore from backups
- **System Analytics**: View detailed system insights, user activity trends, export reports
- **Profile Management**: Admin profile picture upload and management

## Technology Stack

- **Frontend**: HTML5, CSS3 (Tailwind CSS), JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Authentication**: Session-based with secure password hashing
- **File Storage**: Database BLOB storage for uploaded files

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.3 or higher
- PDO MySQL extension enabled

### Step 1: Download and Extract
1. Download the system files
2. Extract to your web server directory (e.g., `htdocs/FINAL/`)

### Step 2: Database Setup
1. Navigate to `http://your-domain/FINAL/install/setup.php`
2. Enter your database configuration:
   - **Database Host**: localhost (or your MySQL server)
   - **Database Name**: ksg_smi_performance (or your preferred name)
   - **Database Username**: Your MySQL username
   - **Database Password**: Your MySQL password
3. Click "Install Database"
4. Wait for the installation to complete

### Step 3: Access the System
1. Navigate to `http://your-domain/FINAL/INDEX.HTML`
2. Use the default login credentials:

**Admin Access:**
- Email: `admin@ksg.ac.ke`
- Password: `admin123`
- Index Code: `Richmond@524`

**User Access:**
- Email: `john.doe@ksg.ac.ke`
- Password: `user123`

### Step 4: Configuration (Optional)
Edit `config/database.php` to customize:
- Session timeout settings
- File upload limits
- Email configuration
- Security policies

## File Structure

```
FINAL/
├── api/                    # API endpoints
│   ├── auth.php           # Authentication API
│   ├── admin.php          # Admin API
│   └── user.php           # User API
├── classes/               # PHP classes
│   ├── User.php           # User management
│   ├── Admin.php          # Admin management
│   └── Task.php           # Task management
├── config/                # Configuration files
│   └── database.php       # Database configuration
├── database/              # Database schema
│   └── ksg_smi_performance.sql
├── install/               # Installation files
│   └── setup.php          # Database setup script
├── INDEX.HTML             # Main application file
├── styles.css             # Custom styles
├── app.js                 # Application JavaScript
└── README.md              # This file
```

## Database Schema

### Main Tables
- **users**: User accounts and profiles
- **admins**: Administrator accounts
- **user_tasks**: Task assignments and tracking
- **task_uploads**: File uploads for tasks
- **task_categories**: Task categorization
- **task_templates**: Predefined task templates
- **security_settings**: System security configuration
- **access_logs**: User activity logging
- **system_backups**: System backup storage

### Key Features
- **Stored Procedures**: For complex operations
- **Views**: For common queries
- **Triggers**: For automatic updates
- **Indexes**: For optimal performance

## API Endpoints

### Authentication (`api/auth.php`)
- `POST /auth.php?action=user_login` - User login
- `POST /auth.php?action=user_register` - User registration
- `POST /auth.php?action=admin_login` - Admin login
- `POST /auth.php?action=admin_register` - Admin registration
- `POST /auth.php?action=logout` - Logout
- `GET /auth.php?action=check_session` - Check session status

### User API (`api/user.php`)
- `GET /user.php?action=get_tasks` - Get user tasks
- `GET /user.php?action=get_profile` - Get user profile
- `PUT /user.php?action=update_profile` - Update profile
- `POST /user.php?action=upload_task_file` - Upload task file
- `GET /user.php?action=export_user_report` - Export reports

### Admin API (`api/admin.php`)
- `GET /admin.php?action=get_users` - Get all users
- `POST /admin.php?action=create_user` - Create new user
- `DELETE /admin.php?action=delete_user` - Delete user
- `GET /admin.php?action=get_analytics` - Get system analytics
- `POST /admin.php?action=create_backup` - Create system backup

## Security Features

- **Password Hashing**: Using PHP's `password_hash()` with bcrypt
- **Session Management**: Secure session handling with timeout
- **SQL Injection Protection**: Prepared statements throughout
- **File Upload Security**: Type and size validation
- **Access Control**: Role-based permissions
- **Activity Logging**: Comprehensive audit trail

## Default Credentials

**Admin Account:**
- Email: `admin@ksg.ac.ke`
- Password: `admin123`
- Index Code: `Richmond@524`

**Test User Account:**
- Email: `john.doe@ksg.ac.ke`
- Password: `user123`

**Important**: Change these default passwords after installation!

## Task Categories

The system comes with predefined task categories:

1. **Financial Stewardship and Discipline**
   - Revenue management
   - Debt management
   - Pending bills
   - Zero fault audits

2. **Service Delivery**
   - Citizens' service delivery charter
   - Public complaints resolution

3. **Core Mandate**
   - Training programs review and development
   - Consultancy and research activities
   - Symposia and conferences
   - Productivity improvement
   - Customer experience management

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check MySQL service is running
   - Verify database credentials
   - Ensure PDO MySQL extension is enabled

2. **File Upload Issues**
   - Check PHP `upload_max_filesize` setting
   - Verify `post_max_size` configuration
   - Ensure proper file permissions

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

### Error Logs
- PHP errors: Check your web server error logs
- Application errors: Check browser console for JavaScript errors

## Support

For technical support or questions about the KSG SMI Performance System:

1. Check the troubleshooting section above
2. Review the database logs for error messages
3. Ensure all system requirements are met
4. Verify file and directory permissions

## License

This system is developed for the Kenya School of Government Security Management Institute. All rights reserved.

## Version History

- **v1.0.0** - Initial release with full functionality
  - User and admin authentication
  - Task management system
  - File upload capabilities
  - Reporting and analytics
  - Security and backup features