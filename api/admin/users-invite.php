<?php
session_start();
/**
 * Admin API - Send User Invitation
 * Admin generates and sends invitation to new user email
 */

require_once dirname(__DIR__, 2) . '/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';
require_once dirname(__DIR__, 4) . '/private/lib/email-service.php';

header('Content-Type: application/json');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Check authorization
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify admin/developer role
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current || !in_array($current['role'], ['admin', 'developer'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email required']);
        exit;
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    // Check if email already has an active user account (only reject active users)
    $adminDb = new AdminDB($db);
    $existing = $adminDb->getUserByEmail($data['email']);
    if ($existing && $existing['is_active']) {
        http_response_code(409);
        echo json_encode(['error' => 'User with this email already exists']);
        exit;
    }

    // Create invitation (allows resending to same email for fresh link with new token)
    $fullName = $data['full_name'] ?? null;
    $token = $adminDb->createInvitation($data['email'], $fullName, $userId);

    if (!$token) {
        throw new Exception('Failed to create invitation');
    }

    // Build invitation link
    $inviteUrl = Config::url('base') . 'accept-invite.php?token=' . urlencode($token);

    // Send invitation email using EmailService::send()
    $emailService = new EmailService();
    $emailSubject = 'You\'re invited to ADHD Dashboard Family';
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
        .footer { font-size: 12px; color: #666; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>You've been invited!</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>You've been invited to join the ADHD Dashboard family!</p>
            <p>Click the button below to accept your invitation and create your account.</p>
            <center>
                <a href="{$inviteUrl}" class="button">Accept Invitation</a>
            </center>
            <p>Or copy and paste this link in your browser:</p>
            <p><code>{$inviteUrl}</code></p>
            <p>This link expires in 7 days. If you have questions, contact the person who invited you.</p>
            <div class="footer">
                <p>This is an automated message, please do not reply to this email.</p>
                <p>© ADHD Dashboard - All rights reserved</p>
            </div>
        </div>
    </div>
</body>
</html>
EMAIL;

    $emailResult = $emailService->send($data['email'], $emailSubject, $emailBody);

    echo json_encode([
        'success' => $emailResult['success'],
        'message' => $emailResult['success'] ? 'Invitation sent successfully' : 'Invitation created but email failed to send',
        'email' => $data['email'],
        'emailSent' => $emailResult['success'],
    ]);
} catch (Exception $e) {
    error_log("Admin API - Send Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
