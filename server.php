<?php
/**
 * BUILT-IN SERVER ROUTER - PHP CLI Development Server
 * Run with: php -S localhost:3000 server.php
 * 
 * This router ensures:
 * 1. Static files (.css, .js, images) are served directly
 * 2. PHP files are executed directly  
 * 3. Everything else routes through index.php for app routing
 */

$requested_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $requested_uri;

// List of extensions that are static files (serve as-is)
$static_extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'json', 'html', 'txt', 'mp3', 'mp4'];

// Get the file extension
$extension = strtolower(pathinfo($requested_uri, PATHINFO_EXTENSION));

// If it's a static file extension and the file exists, let the server serve it
if (in_array($extension, $static_extensions)) {
    if (file_exists($file) && is_file($file)) {
        return false;  // Let the built-in server serve it
    }
}

// If it's a .php file and it exists, execute it
if ($extension === 'php' && file_exists($file) && is_file($file)) {
    require $file;
    return true;
}

// If requesting a directory and index.php exists in it, serve that
if (is_dir($file)) {
    $index = $file . '/index.php';
    if (file_exists($index)) {
        require $index;
        return true;
    }
}

// Everything else routes through index.php (for app routing)
require __DIR__ . '/index.php';
