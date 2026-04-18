<?php
/**
 * ADHD Dashboard - PDO Singleton Database Connection
 * Single connection instance for entire application
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;

    /**
     * Private constructor - prevents direct instantiation
     */
    private function __construct() {}

    /**
     * Get singleton instance
     * @return PDO The database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->connect();
        }
        return self::$instance->pdo;
    }

    /**
     * Initialize PDO connection
     */
    private function connect() {
        try {
            // Database credentials from private .env file
            $host = Config::get('DB_HOST', 'localhost');
            $dbname = Config::get('DB_NAME', 'adhd_dashboard');
            $user = Config::get('DB_USER', 'root');
            $password = Config::get('DB_PASSWORD', '');

            // DSN
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

            // Create PDO connection with error handling
            $this->pdo = new PDO(
                $dsn,
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            // Set timezone
            $this->pdo->exec("SET time_zone='+00:00'");

        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error' => 'Database connection failed',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function for easier access
function db() {
    return Database::getInstance();
}
?>
