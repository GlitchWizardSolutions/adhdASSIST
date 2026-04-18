<?php
/**
 * Database Check - Verify tables exist
 */

require_once __DIR__ . '/../lib/config.php';

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    
    echo "✓ Connected to database: " . DB_NAME . "\n\n";
    
    // Check critical tables
    $tables = ['users', 'sessions', 'user_settings', 'invitations', 'user_crud_permissions'];
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✓ Table exists: $table\n";
        } else {
            echo "✗ Table MISSING: $table\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "If any tables are MISSING, run:\n";
    echo "Get-Content 'c:\\xampp\\htdocs\\adhd-dashboard\\database\\adhd_dashboard.sql' | mysql -u root adhd_dashboard\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nCheck your .env configuration in /private/.env\n";
}
