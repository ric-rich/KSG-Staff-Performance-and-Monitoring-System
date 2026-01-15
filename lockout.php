<?php
// lockout.php - Place this in your root directory
require_once 'inc/maintenance_check.php';

// --- CONFIGURATION ---
// CHANGE THIS PASSWORD TO SOMETHING SECURE
$access_password = 'admin_secret_lock';
// ---------------------

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($password === $access_password) {
        if ($action === 'lock') {
            // Create the lock file
            file_put_contents(LOCK_FILE, 'LOCKED on ' . date('Y-m-d H:i:s'));
            $message = 'System has been LOCKED.';
            $messageType = 'error';
        } elseif ($action === 'unlock') {
            // Delete the lock file
            if (file_exists(LOCK_FILE)) {
                unlink(LOCK_FILE);
            }
            $message = 'System has been UNLOCKED.';
            $messageType = 'success';
        }
    } else {
        $message = 'Invalid authorization password.';
        $messageType = 'error';
    }
}

$is_locked = is_system_locked();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Lockout Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="bg-gray-100 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4 shadow-inner">
                <?php if ($is_locked): ?>
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                <?php else: ?>
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z">
                        </path>
                    </svg>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">System Access Control</h1>

            <div
                class="mt-4 inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold <?php echo $is_locked ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                <span
                    class="w-2 h-2 rounded-full mr-2 <?php echo $is_locked ? 'bg-red-600 animate-pulse' : 'bg-green-600'; ?>"></span>
                Current Status: <?php echo $is_locked ? 'LOCKED' : 'ACTIVE'; ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div
                class="mb-6 p-4 rounded-lg flex items-center <?php echo $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'; ?>">
                <?php if ($messageType === 'error'): ?>
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                <?php else: ?>
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Authorization Password</label>
                <input type="password" name="password" required placeholder="Enter secure password"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm">
            </div>

            <?php if ($is_locked): ?>
                <input type="hidden" name="action" value="unlock">
                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform active:scale-95 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z">
                        </path>
                    </svg>
                    RESTORE SYSTEM
                </button>
            <?php else: ?>
                <input type="hidden" name="action" value="lock">
                <button type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform active:scale-95 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    LOCK SYSTEM DOWN
                </button>
            <?php endif; ?>
        </form>
    </div>
</body>

</html>