-- Create the database
CREATE DATABASE IF NOT EXISTS ksg_smi_performance;
USE ksg_smi_performance;

-- Drop existing views and triggers
DROP VIEW IF EXISTS task_overview;
DROP TRIGGER IF EXISTS update_task_status;
DROP PROCEDURE IF EXISTS assign_task;

-- Drop tables in correct order (dependencies first)
DROP TABLE IF EXISTS site_metric_files;
DROP TABLE IF EXISTS task_uploads;      -- Drop uploads first as it depends on user_tasks
DROP TABLE IF EXISTS user_tasks;        -- Now safe to drop user_tasks
DROP TABLE IF EXISTS task_templates;    -- Rest of the tables
DROP TABLE IF EXISTS task_categories;
DROP TABLE IF EXISTS access_logs;
DROP TABLE IF EXISTS system_backups;
DROP TABLE IF EXISTS security_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS contact_messages;


-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    job_title VARCHAR(100),    
    is_team_member TINYINT(1) DEFAULT 0,
    profile_picture MEDIUMBLOB,    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    settings JSON,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    notification_preferences JSON,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    index_code VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    job_title VARCHAR(100),
    profile_picture MEDIUMBLOB,
    notification_preferences JSON,
    settings JSON,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Task categories table
CREATE TABLE task_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Task templates table (estimated_duration is in hours)
CREATE TABLE task_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    estimated_duration INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES task_categories(category_id)
);

-- User tasks table
CREATE TABLE IF NOT EXISTS user_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions TEXT,
    start_date DATETIME,
    due_date DATETIME NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    assigned_by INT,
    assigner_type ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completion_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Task uploads table
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

-- Security settings table
CREATE TABLE security_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Access logs table
CREATE TABLE access_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- System backups table
CREATE TABLE system_backups (
    backup_id INT PRIMARY KEY AUTO_INCREMENT,
    backup_name VARCHAR(255) NOT NULL,
    backup_data LONGBLOB,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(admin_id)
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255),
  subject VARCHAR(255),
  message TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Site Metrics table for landing page
CREATE TABLE IF NOT EXISTS site_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_key VARCHAR(50) NOT NULL UNIQUE,
    metric_value VARCHAR(255) NOT NULL,
    metric_label VARCHAR(255) NOT NULL,    
    description VARCHAR(255),
    file_path VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Files for Site Metrics
CREATE TABLE IF NOT EXISTS site_metric_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    metric_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (metric_id) REFERENCES site_metrics(id) ON DELETE CASCADE
);

-- Repository files table
CREATE TABLE IF NOT EXISTS repository_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    file_size INT,
    file_type VARCHAR(100),
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES admins(admin_id) ON DELETE SET NULL
);

-- Default site metrics
INSERT INTO site_metrics (metric_key, metric_value, metric_label, description) VALUES
('participants_trained_q1', '10', 'Participants Trained (Q1)', 'Number of participants trained in Quarter 1.'),
('participants_trained_q2', '12', 'Participants Trained (Q2)', 'Number of participants trained in Quarter 2.'),
('participants_trained_q3', '13', 'Participants Trained (Q3)', 'Number of participants trained in Quarter 3.'),
('participants_trained_q4', '10', 'Participants Trained (Q4)', 'Number of participants trained in Quarter 4.'),
('participants_trained_total', '45', 'Total Participants Trained', 'Total number of participants trained in the year.'),
('revenue_generated_q1', '10M', 'Revenue Generated (Q1)', 'Revenue generated in Quarter 1.'),
('revenue_generated_q2', '12M', 'Revenue Generated (Q2)', 'Revenue generated in Quarter 2.'),
('revenue_generated_q3', '10M', 'Revenue Generated (Q3)', 'Revenue generated in Quarter 3.'),
('revenue_generated_q4', '12M', 'Revenue Generated (Q4)', 'Revenue generated in Quarter 4.'),
('revenue_generated_total', '44M', 'Total Revenue Generated', 'Total revenue generated in the year.'),
('programs_launched', '4', 'New Programs Launched', 'Number of new programs introduced this period.'),
('participant_satisfaction', '92%', 'Participant Satisfaction', 'Overall satisfaction score from participant feedback.')
ON DUPLICATE KEY UPDATE metric_key=metric_key;

-- Insert default task categories
INSERT INTO task_categories (name, description) VALUES
('Financial Stewardship and Discipline', 'Tasks related to revenue management, debt management, pending bills, and zero fault audits'),
('Service Delivery', 'Tasks related to citizens'' service delivery charter and public complaints resolution'),
('Core Mandate', 'Tasks related to training programs, consultancy, research, symposia, and customer experience');

-- Insert default security settings
INSERT INTO security_settings (setting_name, setting_value) VALUES
('password_expiry_days', '90'),
('min_password_length', '8'),
('session_timeout_minutes', '30'),
('max_login_attempts', '5'),
('require_2fa', 'false');

-- Create default admin account with password: admin123
INSERT INTO admins (email, password_hash, index_code, first_name, last_name) VALUES
('admin@ksg.ac.ke', '$2y$10$T0Nk.ZxmPko/CVFL5Mduxe9qxecOv0JLtP7sYxZ/Xh3FVBI97cQ.2', 'Richmond@524', 'System', 'Administrator');

-- Create test user account with password: user123
INSERT INTO users (email, password_hash, first_name, last_name, department) VALUES
('john.doe@ksg.ac.ke', '$2y$10$uRffQoNLRzDG1eEesBmvye5fxrpg0vJnqluzAjKeC03NEjDdAwNAm', 'John', 'Doe', 'Training');

-- Create stored procedure for adding a new user
CREATE PROCEDURE create_user(
    IN p_email VARCHAR(255),
    IN p_password_hash VARCHAR(255),
    IN p_first_name VARCHAR(100),
    IN p_last_name VARCHAR(100),
    IN p_department VARCHAR(100),
    IN p_phone VARCHAR(20),
    IN p_job_title VARCHAR(100)
)
BEGIN
    INSERT INTO users (email, password_hash, first_name, last_name, department, phone, job_title)
    VALUES (p_email, p_password_hash, p_first_name, p_last_name, p_department, p_phone, p_job_title);
END;

-- Create stored procedure for task assignment
CREATE PROCEDURE assign_task(IN p_user_id INT, IN p_template_id INT, IN p_start_date DATE, IN p_due_date DATE)
BEGIN
    DECLARE v_title VARCHAR(255);
    DECLARE v_description TEXT;
    
    -- Get template details
    SELECT title, description INTO v_title, v_description
    FROM task_templates WHERE template_id = p_template_id;
    
    -- Create new task
    INSERT INTO user_tasks (user_id, template_id, title, description, start_date, due_date)
    SELECT p_user_id, p_template_id, title, description, p_start_date, p_due_date
    FROM task_templates WHERE template_id = p_template_id;
END;

-- Create view for task overview
CREATE VIEW task_overview AS
SELECT 
    ut.task_id,
    ut.user_id,
    ut.title,
    ut.status,
    ut.start_date,
    ut.due_date,
    ut.completion_date,
    u.first_name,
    u.last_name,
    tc.name as category_name
FROM user_tasks ut
JOIN users u ON ut.user_id = u.user_id
JOIN task_templates tt ON ut.template_id = tt.template_id
JOIN task_categories tc ON tt.category_id = tc.category_id;

-- Create trigger for task status update
CREATE TRIGGER update_task_status BEFORE UPDATE ON user_tasks
FOR EACH ROW
SET NEW.status = CASE
    WHEN NEW.completion_date IS NOT NULL THEN 'completed'
    WHEN CURRENT_DATE > NEW.due_date AND NEW.status != 'completed' THEN 'overdue'
    ELSE NEW.status
END;

-- Create indexes for better performance
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_admin_email ON admins(email);
CREATE INDEX idx_task_status ON user_tasks(status);
CREATE INDEX idx_task_dates ON user_tasks(start_date, due_date);
CREATE INDEX idx_uploads_task ON task_uploads(task_id);
CREATE INDEX idx_access_logs_date ON access_logs(created_at);