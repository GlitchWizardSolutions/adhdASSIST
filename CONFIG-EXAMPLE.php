<?php
/**
 * ADHD Dashboard Configuration Example
 * Demonstrates how environment-aware configuration works
 * 
 * This file is for reference only - not used in production
 * It shows how lib/config.php handles dev vs production automatically
 */

// This is handled automatically by lib/config.php
// No need to include this file - just reference for understanding

echo "=== ADHD Dashboard Configuration Example ===\n\n";

// When you include lib/config.php in any PHP file:
// require_once __DIR__ . '/../lib/config.php';

// You get automatic environment detection:

$exampleOutput = [
    'development' => [
        'detected_by' => 'localhost / 127.0.0.1 / XAMPP htdocs',
        'database' => [
            'DB_HOST' => '127.0.0.1:3307',
            'DB_NAME' => 'adhd_dashboard',
            'DB_USER' => 'root',
            'DB_PASSWORD' => '',
        ],
        'url_paths' => [
            'base' => 'http://localhost:8000/public_html/',
            'api' => 'http://localhost:8000/public_html/api/',
            'uploads' => 'http://localhost:8000/public_html/uploads/',
        ],
        'filesystem_paths' => [
            'root' => 'C:\xampp\htdocs\adhd-dashboard',
            'public_html' => 'C:\xampp\htdocs\adhd-dashboard\public_html',
            'private' => 'C:\xampp\htdocs\adhd-dashboard\private',
            'config' => 'C:\xampp\htdocs\adhd-dashboard\private\.env',
        ],
    ],
    'production' => [
        'detected_by' => 'Any domain except localhost',
        'database' => [
            'DB_HOST' => '127.0.0.1:3307',  // Same in .env
            'DB_NAME' => 'adhd_dashboard',   // Same in .env
            'DB_USER' => 'root',              // Same in .env
            'DB_PASSWORD' => '',              // Same in .env
        ],
        'url_paths' => [
            'base' => 'https://mydomain.com/',
            'api' => 'https://mydomain.com/api/',
            'uploads' => 'https://mydomain.com/uploads/',
        ],
        'filesystem_paths' => [
            'root' => '/home/user/public_html',
            'public_html' => '/home/user/public_html/adhd-dashboard/public_html',
            'private' => '/home/user/private/adhd-dashboard',
            'config' => '/home/user/private/adhd-dashboard/.env',
        ],
    ],
];

echo "Notice: The .env file is IDENTICAL in both environments!\n";
echo "The Config class automatically adjusts paths and URLs.\n\n";

echo "To use Configuration in your code:\n\n";
echo "<?php\n";
echo "require_once __DIR__ . '/../lib/config.php';\n\n";
echo "// Get config values\n";
echo "\$host = Config::get('DB_HOST');              // From .env\n";
echo "\$database = Config::get('DB_NAME');          // From .env\n";
echo "\$user = Config::get('DB_USER');              // From .env\n\n";

echo "// Get paths (filesystem)\n";
echo "\$rootPath = Config::path('root');            // Full project root\n";
echo "\$uploadsPath = Config::path('uploads');      // Full uploads directory\n";
echo "\$privatePath = Config::path('private');      // Full private directory\n\n";

echo "// Get URLs (web-accessible, environment-aware)\n";
echo "\$baseUrl = Config::url('base');              // Base URL\n";
echo "\$apiUrl = Config::url('api');                // API base URL\n";
echo "\$uploadsUrl = Config::url('uploads');        // Uploads base URL\n\n";

echo "// Environment checks\n";
echo "if (Config::isDevelopment()) {\n";
echo "    // Development-specific code\n";
echo "    error_reporting(E_ALL);\n";
echo "    ini_set('display_errors', 1);\n";
echo "}\n\n";

echo "if (Config::isProduction()) {\n";
echo "    // Production-specific code\n";
echo "    error_reporting(0);\n";
echo "    ini_set('display_errors', 0);\n";
echo "}\n";
echo "?>\n";
?>
