<?php
// 1. SECURITY: Block direct access to this file
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied.");
}

// 2. HELPER: Native .env parser (No Composer required)
// Looks for .env in the parent directory (root)
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Split name=value
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set to environment variable
            $_ENV[$name] = $value;
        }
    }
} else {
    // Optional: Stop execution if .env is missing in production
    // die("Configuration file missing.");
}

// 3. CONNECTION: Use variables from .env
$host    = $_ENV['DB_HOST'] ?? 'localhost';
$db      = $_ENV['DB_NAME'] ?? 'cexport_db';
$user    = $_ENV['DB_USER'] ?? 'root';
$pass    = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 4. SECURITY FIX: Never echo the raw error to the screen!
    
    // Log the real error to a file (server-side only)
    error_log("Database Connection Error: " . $e->getMessage(), 0);
    
    // Show a generic, safe message to the user
    die("<h3>Service Unavailable</h3><p>We are currently experiencing technical difficulties. Please try again later.</p>");
}
?>