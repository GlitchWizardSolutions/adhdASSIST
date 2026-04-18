<?php
/**
 * SMS Delivery Receipt Callback - EasySendSMS HDR Webhook
 * 
 * Handset Delivery Receipts (HDR / dlr=2):
 * - Confirms message was delivered to recipient's device (not just carrier)
 * - More accurate than basic delivery receipts (DLR / dlr=1)
 * - Provides 'DELIVRD' status when phone received the SMS
 * - Provides 'UNDELIV' status if delivery failed or message expired
 * 
 * API Documentation: https://www.easysendsms.com/dlr-api
 * Webhook logs: /private/logs/webhook-delivery-receipt.log
 */

// Setup logging first - use dynamic path
$logDir = dirname(dirname(dirname(dirname(__DIR__)))) . '/private/logs';
$logFile = $logDir . '/webhook-delivery-receipt.log';

// Create log directory if needed
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Log all incoming requests immediately
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s.u'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'headers' => [
        'Content-Type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
        'Authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'N/A',
        'X-API-Key' => $_SERVER['HTTP_X_API_KEY'] ?? 'N/A',
    ],
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
    'get' => $_GET,
    'post' => $_POST,
];

@file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);

// Return HTTP 200 IMMEDIATELY
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

// Simple success response
$response = ['success' => true, 'message' => 'Receipt recorded'];

// Try to process actual callback data on POST with parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    if (!empty($rawBody)) {
        parse_str($rawBody, $data);
        
        // Extract DLR fields
        $smsId = $data['sms_id'] ?? null;
        $status = $data['response'] ?? null;
        
        // Log actual callback
        if ($smsId || $status) {
            $callbackLog = [
                'timestamp' => date('Y-m-d H:i:s'),
                'sms_id' => $smsId,
                'status' => $status,
                'data' => $data
            ];
            @file_put_contents($logFile, json_encode($callbackLog) . "\n", FILE_APPEND);
            
            // Update database if we have sms_id
            if ($smsId) {
                try {
                    require_once __DIR__ . '/../../lib/config.php';
                    require_once __DIR__ . '/../../lib/database.php';
                    $pdo = db();
                    
                    // Map status
                    $mappedStatus = $status;
                    if (in_array($status, ['DELIVRD', 'delivered', 'OK'])) {
                        $mappedStatus = 'delivered';
                    } elseif (in_array($status, ['UNDELIV', 'failed'])) {
                        $mappedStatus = 'failed';
                    } elseif (in_array($status, ['EXPIRED'])) {
                        $mappedStatus = 'expired';
                    }
                    
                    $stmt = $pdo->prepare('
                        UPDATE sms_logs 
                        SET status = ?, updated_at = NOW()
                        WHERE message_id = ? AND status != "delivered"
                    ');
                    $stmt->execute([$mappedStatus, $smsId]);
                } catch (Exception $e) {
                    @file_put_contents($logFile, "DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }
    }
}

// Always output response
echo json_encode($response, JSON_UNESCAPED_SLASHES);
exit(0);

// For GET and HEAD validation requests, return immediately
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'HEAD') {
    echo json_encode(['success' => true]);
    exit(0);
}

// For POST requests, process the raw content
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Suppress errors to keep response clean
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_start();
    
    try {
        // Read raw POST body (EasySendSMS sends URL-encoded data)
        $rawBody = file_get_contents('php://input');
        parse_str($rawBody, $data);
        
        // Extract callback parameters
        $source = $data['source'] ?? null;
        $msisdn = $data['msisdn'] ?? null;
        $response = $data['response'] ?? null;
        $sentDate = $data['sent_date'] ?? null;
        $smsId = $data['sms_id'] ?? null;
        
        // Log the callback
        $logData = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => $source,
            'msisdn' => $msisdn,
            'response' => $response,
            'sent_date' => $sentDate,
            'sms_id' => $smsId,
            'raw_body' => $rawBody
        ]);
        
        // Try to log to file - use dynamic path only
        $logPaths = [
            dirname(dirname(dirname(dirname(__DIR__)))) . '/private/logs/sms-callbacks.log'
        ];
        
        foreach ($logPaths as $logPath) {
            if (is_writable(dirname($logPath))) {
                @file_put_contents($logPath, $logData . "\n\n", FILE_APPEND);
                break;
            }
        }
        
        // Update database if we have message ID
        if ($smsId) {
            require_once __DIR__ . '/../../lib/config.php';
            require_once __DIR__ . '/../../lib/database.php';
            
            try {
                $pdo = db();
                
                // Map EasySendSMS status to our status
                $mappedStatus = $response;
                if (in_array($response, ['DELIVRD', 'delivered', 'OK'])) {
                    $mappedStatus = 'delivered';
                } elseif (in_array($response, ['UNDELIV', 'failed', 'Failed'])) {
                    $mappedStatus = 'failed';
                } elseif (in_array($response, ['EXPIRED', 'expired'])) {
                    $mappedStatus = 'expired';
                }
                
                $stmt = $pdo->prepare('
                    UPDATE sms_logs 
                    SET status = ?, 
                        delivered_at = ?,
                        updated_at = NOW()
                    WHERE message_id = ? AND status != "delivered"
                ');
                
                $stmt->execute([
                    $mappedStatus,
                    !empty($sentDate) ? date('Y-m-d H:i:s', strtotime($sentDate)) : date('Y-m-d H:i:s'),
                    $smsId
                ]);
            } catch (Exception $e) {
                // Log error but don't fail
                @error_log('[SMS CALLBACK ERROR] ' . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        @error_log('[SMS WEBHOOK ERROR] ' . $e->getMessage());
    }
    
    ob_end_clean();
    echo json_encode(['success' => true]);
    exit(0);
}

// Default response for any other method
echo json_encode(['success' => true]);
exit(0);
