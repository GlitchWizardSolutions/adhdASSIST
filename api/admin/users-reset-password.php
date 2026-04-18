<?php
session_start();
/**
 * Admin API - Reset User Password
 * Generate password reset token and send reset email to user
 */

require_once dirname(__DIR__, 4) . '/private/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';
require_once dirname(__DIR__, 4) . '/private/lib/email-service.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $adminId = $_SESSION['user_id'];
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify admin/developer role
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current || !in_array($current['role'], ['admin', 'developer'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }

    // Get user details
    $adminDb = new AdminDB($db);
    $user = $adminDb->getUserById($data['id']);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Create reset token
    $resetToken = $adminDb->createPasswordReset($data['id']);

    if (!$resetToken) {
        throw new Exception('Failed to create password reset token');
    }

    // Build password reset link
    $resetUrl = Config::url('base') . 'reset-password.php?token=' . urlencode($resetToken);

    // Send password reset email
    $emailService = new EmailService();
    $emailSubject = 'Password Reset Request - ADHD Dashboard';
    $emailBody = <<<EMAIL
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .button { 
            display: inline-block; 
            background: #3b82f6 !important; 
            color: white !important; 
            padding: 12px 30px; 
            text-decoration: none !important; 
            border-radius: 5px; 
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover { background: #2563eb !important; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
        .footer { font-size: 12px; color: #666; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Password Reset Request</h2>
        </div>
        <div class="content">
            <p>Hello {$user['first_name']},</p>
            <p>We received a password reset request for your ADHD Dashboard account.</p>
            <p>Click the button below to reset your password. This link will expire in 24 hours.</p>
            <center><a href="{$resetUrl}" class="button">Reset Password</a></center>
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> If you did not request this password reset, you can safely ignore this email. Your account remains secure.
            </div>
            <p><strong>What's next?</strong></p>
            <ol>
                <li>Click the Reset Password button above</li>
                <li>Enter a new secure password</li>
                <li>Log in with your new password</li>
            </ol>
            <p style="color: #666; font-size: 0.9rem;"><strong>Reset Link:</strong> {$resetUrl}</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 ADHD Dashboard. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
EMAIL;

    // Send via EmailService
    $result = $emailService->send(
        to: $user['email'],
        subject: $emailSubject,
        htmlBody: $emailBody
    );

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reset email sent successfully'
        ]);
    } else {
        throw new Exception('Failed to send password reset email');
    }
} catch (Exception $e) {
    error_log("Admin API - Reset Password Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send password reset email']);
}
