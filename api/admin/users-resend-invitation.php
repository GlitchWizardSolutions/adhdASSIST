<?php
session_start();
/**
 * Admin API - Resend User Invitation
 * Resends invitation to user email with new token
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

    if (empty($data['invitation_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invitation ID required']);
        exit;
    }

    // Get the invitation
    $stmt = $db->prepare("SELECT id, email FROM invitations WHERE id = ? AND accepted_at IS NULL");
    $stmt->execute([$data['invitation_id']]);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invitation) {
        http_response_code(404);
        echo json_encode(['error' => 'Invitation not found']);
        exit;
    }

    // Regenerate token
    $adminDb = new AdminDB($db);
    $token = $adminDb->createInvitation($invitation['email'], null, $userId);

    if (!$token) {
        throw new Exception('Failed to create new invitation token');
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
            padding: 12px 30px !important; 
            border-radius: 5px !important; 
            text-decoration: none !important; 
            font-weight: bold !important;
            margin: 20px 0 !important;
        }
        .footer { background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #6b7280; border-radius: 0 0 5px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to ADHD Dashboard Family</h1>
        </div>
        <div class="content">
            <p>You've been invited to join ADHD Dashboard! Click the link below to set up your account and join your family.</p>
            <p style="text-align: center;">
                <a href="{$inviteUrl}" class="button">Accept Invitation</a>
            </p>
            <p style="color: #6b7280; font-size: 13px;">Or copy this link in your browser:</p>
            <p style="word-break: break-all; background: #f3f4f6; padding: 10px; border-radius: 5px; font-size: 12px;">
                {$inviteUrl}
            </p>
            <p>This link expires in 7 days.</p>
        </div>
        <div class="footer">
            <p>ADHD Dashboard • Focus on what matters • Zero judgment</p>
        </div>
    </div>
</body>
</html>
EMAIL;

    $result = $emailService->send(
        $invitation['email'],
        $emailSubject,
        $emailBody
    );

    if ($result['success']) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to send email: ' . $result['message']
        ]);
    }

} catch (PDOException $e) {
    error_log("Admin API - Resend Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Admin API - Resend Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
