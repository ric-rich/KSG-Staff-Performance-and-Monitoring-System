# User Management Guide

## Overview
The User Management feature allows administrators to view a comprehensive list of all users in the system along with detailed information about their assigned tasks and uploads.

## Accessing User Management

1. **Login as Admin**: Navigate to the admin login page and authenticate
2. **Admin Panel**: From the admin control panel (`admin/index.php`), click on the "User Management" card
3. **Direct Access**: Navigate directly to `admin/users.php`

## Features

### 1. Dashboard Statistics
At the top of the page, you'll see four key metrics:
- **Total Users**: Number of registered users in the system
- **Total Tasks**: Cumulative count of all tasks assigned to users
- **Completed Tasks**: Number of tasks marked as completed
- **Total Uploads**: Total number of files uploaded by all users

### 2. Search Functionality
- **Real-time Search**: Type in the search box to filter users instantly
- **Search Criteria**: Search by:
  - First name
  - Last name
  - Email address
  - Department

### 3. User Cards
Each user is displayed in an expandable card showing:

#### Header Information (Always Visible)
- User avatar with initials
- Full name
- Email address
- Department (if available)
- Quick stats:
  - Total tasks assigned
  - Completed tasks
  - Total uploads

#### Expandable Details (Click to View)
Click on any user card to expand and view three tabs:

##### Tasks Tab
- Lists all tasks assigned to the user
- Shows for each task:
  - Task title
  - Status badge (pending, in progress, completed)
  - Priority badge (low, medium, high)
  - Due date
  - Completion date (if completed)
- Displays up to 5 most recent tasks

##### Uploads Tab
- Lists all files uploaded by the user
- Shows for each upload:
  - File name with icon
  - Associated task title
  - Upload date and time
- Displays up to 5 most recent uploads

##### User Info Tab
- Complete user profile information:
  - Email address
  - Full name
  - Department
  - Member since date
  - User ID

## API Endpoints

### Get All Users with Details
```
GET /api/admin-users.php?action=get_users_with_details
```

**Response:**
```json
{
  "status": "success",
  "timestamp": "2024-01-15 10:30:00",
  "users": [
    {
      "user_id": 1,
      "email": "john.doe@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "department": "Training",
      "created_at": "2024-01-01 00:00:00",
      "total_tasks": 5,
      "completed_tasks": 3,
      "total_uploads": 8,
      "recent_tasks": [...],
      "recent_uploads": [...]
    }
  ]
}
```

### Get Individual User Details
```
GET /api/admin-users.php?action=get_user_details&user_id=1
```

**Response:**
```json
{
  "status": "success",
  "user": {
    "user_id": 1,
    "email": "john.doe@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "department": "Training",
    "tasks": [...],
    "uploads": [...]
  }
}
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    job_title VARCHAR(100),
    profile_picture MEDIUMBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### User Tasks Table
```sql
CREATE TABLE user_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### Task Uploads Table
```sql
CREATE TABLE task_uploads (
    upload_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_data LONGBLOB,
    file_type VARCHAR(100),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES user_tasks(task_id)
);
```

## Security Features

1. **Admin Authentication**: Only authenticated admin users can access the user management page
2. **Session Validation**: Each request validates the admin session
3. **SQL Injection Protection**: All database queries use prepared statements
4. **XSS Prevention**: All user data is properly escaped when displayed

## User Interface Features

### Visual Design
- **Gradient Headers**: Eye-catching purple gradient for user cards
- **Color-Coded Badges**: 
  - Status badges (yellow for pending, blue for in progress, green for completed)
  - Priority badges (blue for low, yellow for medium, red for high)
- **Responsive Layout**: Adapts to different screen sizes
- **Smooth Animations**: Hover effects and transitions for better UX

### Interactive Elements
- **Expandable Cards**: Click to expand/collapse user details
- **Tab Navigation**: Switch between tasks, uploads, and user info
- **Real-time Search**: Instant filtering as you type
- **Loading States**: Spinner animation while data loads

## Best Practices

### For Administrators
1. **Regular Monitoring**: Check user activity regularly to ensure tasks are being completed
2. **Search Efficiently**: Use the search feature to quickly find specific users
3. **Review Uploads**: Monitor file uploads to ensure compliance with policies
4. **Track Progress**: Use the statistics to gauge overall system usage

### For Developers
1. **API Optimization**: The API fetches only recent tasks/uploads (5 each) for performance
2. **Lazy Loading**: User details are loaded on demand when cards are expanded
3. **Error Handling**: Comprehensive error handling with user-friendly messages
4. **Code Maintainability**: Well-structured code with clear separation of concerns

## Troubleshooting

### Users Not Loading
- Check database connection in `config/database.php`
- Verify admin session is active
- Check browser console for JavaScript errors
- Ensure API endpoint is accessible

### Search Not Working
- Clear browser cache
- Check JavaScript console for errors
- Verify search input is properly bound to the filter function

### Statistics Showing Incorrect Numbers
- Verify database queries in `api/admin-users.php`
- Check for orphaned records in the database
- Ensure foreign key relationships are intact

## Future Enhancements

Potential features for future development:
1. **Export Functionality**: Export user data to CSV/Excel
2. **Bulk Actions**: Select multiple users for batch operations
3. **Advanced Filters**: Filter by task status, date range, etc.
4. **User Activity Timeline**: Visual timeline of user actions
5. **Email Notifications**: Send emails directly to users from the interface
6. **User Editing**: Edit user details directly from the management page
7. **Task Assignment**: Assign new tasks to users from this interface
8. **Performance Metrics**: Show completion rates and average task duration

## Support

For issues or questions:
1. Check the error logs in the browser console
2. Review the PHP error log for server-side issues
3. Verify database connectivity and permissions
4. Ensure all required files are present and properly configured

## File Structure

```
admin/
├── index.php          # Admin control panel
├── users.php          # User management page (NEW)
└── messages.php       # Contact messages management

api/
├── admin-users.php    # User management API endpoints
├── admin.php          # General admin API
└── auth.php           # Authentication API

inc/
├── auth.php           # Authentication functions
└── db.php             # Database connection

config/
└── database.php       # Database configuration
```

## Changelog

### Version 1.0 (Current)
- Initial release of user management feature
- View all users with statistics
- Expandable user cards with tabs
- Real-time search functionality
- Task and upload details display
- Responsive design with smooth animations
