<?php
// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once '../auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }

    // حفظ معلومات الاشتراك في قاعدة البيانات
    $stmt = $db->prepare("
        INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, created_at) 
        VALUES (:user_id, :endpoint, :p256dh_key, :auth_key, NOW())
        ON DUPLICATE KEY UPDATE 
        p256dh_key = :p256dh_key,
        auth_key = :auth_key,
        updated_at = NOW()
    ");

    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'endpoint' => $input['endpoint'],
        'p256dh_key' => $input['keys']['p256dh'],
        'auth_key' => $input['keys']['auth']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
