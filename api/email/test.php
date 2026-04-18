<?php
/**
 * ADHD Dashboard - Email Service Test Endpoint
 * POST /api/email/test
 * FOR DEVELOPMENT ONLY - Tests email sending functionality
 * 
 * Usage:
 * POST /api/email/test
 * email: test@example.com
 * 
 * Returns: {'success': true, 'messageId': '...'}
 */

require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../../private/lib/email-service.php';

header('Content-Type: application/json');

// Only allow in development (check for localhost/xampp)
if (!Config::isDevelopment() && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', 'localhost', '::1'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Test endpoint only available in development']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $email = $_POST['email'] ?? $_POST['to'] ?? 'test@example.com';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid email required']);
        exit;
    }

    $emailService = new EmailService();
    
    $result = $emailService->send(
        $email,
        'Test Email from ADHD Dashboard',
        '<h1>Test Email</h1><p>This is a test email to verify the email service is working correctly.</p><p>If you received this, the SMTP configuration is correct!</p>',
        'Test Email\n\nThis is a test email to verify the email service is working correctly.\n\nIf you received this, the SMTP configuration is correct!'
    );

    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Email service error: ' . $e->getMessage()
    ]);
}