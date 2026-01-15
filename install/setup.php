<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if installation is already complete
if (file_exists('install_complete.txt')) {
    unlink('install_complete.txt'); // Remove the file to allow reinstallation
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database configuration from POST data
    $host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'ksg_smi_performance';
    $username = $_POST['db_username'] ?? 'root';
    $password = $_POST['db_password'] ?? '';

    try {
        // Test database connection
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Drop existing database if it exists
        $conn->exec("DROP DATABASE IF EXISTS `$db_name`");
        
        // Create fresh database
        $conn->exec("CREATE DATABASE `$db_name`");
        $conn->exec("USE `$db_name`");

        // Read SQL file
        $sql = file_get_contents('../database/ksg_smi_performance.sql');
        
        // Execute create and use database statements first
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $conn->exec("USE `$db_name`");
        
        // First, execute all table creation and basic statements
        $statements = [];
        $currentStatement = '';
        $inProcedure = false;
        $procedureStatement = '';
        
        // Split the SQL file into statements
        $lines = explode("\n", $sql);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || substr($line, 0, 2) == '--') {
                continue;
            }
            
            // Check if this is the start of a stored procedure
            if (stripos($line, 'CREATE PROCEDURE') === 0) {
                $inProcedure = true;
                $procedureStatement = $line;
                continue;
            }
            
            if ($inProcedure) {
                $procedureStatement .= "\n" . $line;
                // Check if this is the end of the procedure
                if (trim($line) === 'END;') {
                    $statements[] = $procedureStatement;
                    $inProcedure = false;
                    $procedureStatement = '';
                }
            } else {
                $currentStatement .= ' ' . $line;
                
                // If the line ends with a semicolon, it's the end of a statement
                if (substr(trim($line), -1) === ';') {
                    $statements[] = trim($currentStatement);
                    $currentStatement = '';
                }
            }
        }
        
        // Execute each statement
        foreach ($statements as $statement) {
            try {
                if (empty(trim($statement))) {
                    continue;
                }
                
                // Execute the statement
                $conn->exec($statement);
                
            } catch (PDOException $e) {
                // Log the failing statement for debugging
                error_log("Failed SQL: " . $statement);
                // Skip "already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw new PDOException($e->getMessage() . "\nFailed Query: " . $statement);
                }
            }
        }

        // Update database configuration
        $config_content = "<?php
class Database {
    private \$host = '$host';
    private \$db_name = '$db_name';
    private \$username = '$username';
    private \$password = '$password';
    private \$conn;

    public function connect() {
        \$this->conn = null;

        try {
            \$this->conn = new PDO(
                'mysql:host=' . \$this->host . ';dbname=' . \$this->db_name,
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException \$e) {
            echo 'Connection Error: ' . \$e->getMessage();
        }

        return \$this->conn;
    }

    public static function getConfig() {
        return [
            'host' => '$host',
            'db_name' => '$db_name',
            'username' => '$username',
            'password' => '$password'
        ];
    }
}
?>";
        file_put_contents('../config/database.php', $config_content);

        // Create a flag file to indicate successful installation
        file_put_contents('install_complete.txt', date('Y-m-d H:i:s'));

        $response = [
            'status' => 'success',
            'message' => 'Database installed successfully'
        ];
    } catch(PDOException $e) {
        // Log the full error for debugging
        error_log("Database Error: " . $e->getMessage());
        
        $response = [
            'status' => 'error',
            'message' => 'Database Error: ' . $e->getMessage()
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Check if already installed
if (file_exists('install_complete.txt')) {
    header('Location: ../INDEX.HTML');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSG SMI Performance System Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Database Setup</h1>
            
            <?php if (isset($response) && $response['status'] === 'error'): ?>
            <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-md">
                <?php echo htmlspecialchars($response['message']); ?>
            </div>
            <?php endif; ?>
            
            <form id="setupForm" method="POST" action="setup.php" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Database Host</label>
                    <input type="text" name="db_host" value="localhost" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Database Name</label>
                    <input type="text" name="db_name" value="ksg_smi_performance" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Database Username</label>
                    <input type="text" name="db_username" value="root" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Database Password</label>
                    <input type="password" name="db_password" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Install Database
                    </button>
                </div>
            </form>

            <div id="message" class="mt-4 p-4 rounded-md hidden"></div>
        </div>
    </div>

    <script>
        document.getElementById('setupForm').addEventListener('submit', function(e) {
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Installing...';
            
            // Form will submit normally - no need to prevent default
            return true;
        });
    </script>
</body>
</html>