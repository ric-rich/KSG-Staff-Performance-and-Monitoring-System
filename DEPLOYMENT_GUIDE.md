# Deployment Automation Guide

This guide explains how to set up automated deployment for your KSG SMI Performance System.

## Overview

We have implemented a **Git-based automation workflow**.

1. **GitHub Actions**: Automatically triggers a deployment when you push to the `main` branch.
2. **Deployment Script**: A PHP script (`scripts/deploy.php`) on the server pulls the latest code.
3. **Environment Security**: Database credentials are now managed via environment variables.

## 1. Server Configuration

On your live server (e.g., CPanel, VPS, Apache/Nginx), you need to set the following environment variables. This prevents storing sensitive passwords in the code.

### Option A: Apache (.htaccess)

Create or edit `.htaccess` in your project root:

```apache
SetEnv APP_ENV production
SetEnv DB_HOST 127.0.0.1
SetEnv DB_NAME your_live_db_name
SetEnv DB_USER your_live_db_user
SetEnv DB_PASS your_live_db_password
SetEnv DEPLOY_KEY your_secret_deployment_key_here
```

### Option B: VPS / System Environment

Add to `/etc/environment` or your web server config.

## 2. GitHub Setup

To enable the automated workflow:

1. Go to your GitHub Repository -> **Settings** -> **Secrets and variables** -> **Actions**.
2. Add the following **Repository Secrets**:
   - `SSH_HOST`: Your server IP address.
   - `SSH_USERNAME`: Your server SSH username (e.g., `root` or `ubuntu`).
   - `SSH_PRIVATE_KEY`: Your SSH private key content.
   - `SSH_PORT`: Your SSH port (default is 22).
   - `DEPLOY_PATH`: The absolute path to your project on the server (e.g., `/var/www/html`).

## 3. Initial Server Setup

Before automation works, you must clone the repository on the server once manually:

```bash
# SSH into your server
ssh user@your-server.com

# Go to your web directory
cd /var/www/html

# Clone the repository
git clone https://github.com/yourusername/your-repo.git .

# Install dependencies (if any)
# composer install
```

## 4. How It Works

- **Automatic**: When you push to `main` on GitHub, the "Deploy to Live Server" action runs. It logs into your server via SSH and runs `php scripts/deploy.php`.
- **Manual (Web)**: You can also visit `https://yoursite.com/scripts/deploy.php?key=YOUR_SECRET_KEY` to trigger an update manually.
- **Manual (CLI)**: SSH into your server and run `php scripts/deploy.php`.

## 5. Deployment Script Logic (`scripts/deploy.php`)

- Checks for usage of the correct Secret Key.
- Runs `git pull origin main`.
- Verifies database connection using the environment variables.
- Logs output to `deploy.log`.

## 6. Troubleshooting

### IDE Warnings: "Context access might be invalid"

If you see warnings in your editor like `Context access might be invalid: SSH_HOST` in the `.github/workflows/deploy.yml` file, **you can safely ignore them**.

- These are **False Positives**.
- Your local editor (VS Code, etc.) does not have access to your GitHub Repository's private Settings, so it cannot verify that `SSH_HOST` actually exists.
- As long as you have added them in GitHub Settings (Step 2), the deployment will work correctly.

### Database Connection Fails in CLI

If the deployment runs but says "Database connection verification: FAILED", it's likely because the CLI user doesn't see the environment variables set in `.htaccess`.
**Fix:**
Add the environment variables to your user's shell profile (e.g., `~/.bashrc` or `~/.profile`) on the server:

```bash
export DB_HOST="127.0.0.1"
export DB_NAME="your_live_db"
export DB_USER="your_user"
export DB_PASS="your_pass"
```
