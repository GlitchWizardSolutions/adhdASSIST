<?php
/**
 * ADHD Dashboard - Public Configuration Wrapper
 * Location: /public_html/lib/config.php
 * 
 * This file loads the secure configuration from /private/lib/config.php
 * and provides helper methods for URLs, paths, and settings.
 * 
 * NO database credentials or sensitive data in this file
 * ALL credentials stored in /private/.env (outside web root)
 * 
 * Usage: require_once __DIR__ . '/config.php';
 *        $url = Config::url('api');
 *        $path = Config::path('uploads');
 */

// Load secure configuration from private directory
require_once __DIR__ . '/../../../private/lib/config.php';

class Config {
    // Application subdirectory
    const APP_SUBDIR = APP_SUBDIR;

    /**
     * Get web-accessible URL for resources
     * URLs automatically include APP_SUBDIR for subdirectory deployments
     * 
     * @param string $type URL type: 'base', 'api', 'css', 'js', 'uploads', 'views'
     * @return string Full URL path
     */
    public static function url($type) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $basePath = self::APP_SUBDIR . '/';
        $baseUrl = $protocol . '://' . $host . $basePath;

        $urls = [
            'base' => $baseUrl,
            'api' => $baseUrl . 'api/',
            'uploads' => $baseUrl . 'uploads/',
            'views' => $baseUrl . 'views/',
            'css' => $baseUrl . 'css/',
            'js' => $baseUrl . 'js/',
        ];

        return $urls[$type] ?? $baseUrl;
    }

    /**
     * Get filesystem path for resources
     * 
     * @param string $type Path type: 'uploads', 'private_uploads', 'logs', 'private', 'public', 'project'
     * @return string Full filesystem path
     */
    public static function path($type) {
        $paths = [
            'root' => project_path,
            'project' => project_path,
            'public' => public_path,
            'public_html' => public_path,
            'private' => private_path,
            'uploads' => public_path . 'uploads/',
            'private_uploads' => uploads_path,
            'logs' => logs_path,
        ];

        return $paths[$type] ?? null;
    }

    /**
     * Get environment-aware redirect URL
     * Works in both XAMPP (/public_html/...) and production (/)
     * Automatically includes APP_SUBDIR for subdirectory deployments
     * 
     * @param string $path The path like /views/dashboard.php
     * @return string Full environment-aware URL
     */
    public static function redirectUrl($path) {
        return self::APP_SUBDIR . $path;
    }

    /**
     * Check if running in development environment
     * @return bool
     */
    public static function isDevelopment() {
        return ENVIRONMENT === 'development';
    }

    /**
     * Check if running in production environment
     * @return bool
     */
    public static function isProduction() {
        return ENVIRONMENT === 'production';
    }

    /**
     * Get configuration value from environment
     * 
     * @param string $constant Configuration constant name
     * @param mixed $default Default value if not defined
     * @return mixed Configuration value
     */
    public static function get($constant, $default = null) {
        return defined($constant) ? constant($constant) : $default;
    }
}

// Confirm configuration loaded
if (!defined('ENVIRONMENT')) {
    throw new Exception('Configuration not loaded properly - private/lib/config.php failed to initialize');
}
