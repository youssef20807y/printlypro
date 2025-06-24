<?php
/**
 * معالجة طلبات جلب خدمات التصنيف
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once '../auth.php';

// تعيين نوع المحتوى إلى JSON
header('Content-Type: application/json; charset=utf-8');

// التأكد من عدم وجود أي مخرجات قبل JSON
ob_clean();

$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

if ($category_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'معرف التصنيف غير صالح',
        'details' => 'يجب أن يكون معرف التصنيف رقماً موجباً'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // التحقق من وجود التصنيف
    $check_stmt = $db->prepare("SELECT category_id FROM categories WHERE category_id = ?");
    $check_stmt->execute([$category_id]);
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'التصنيف غير موجود',
            'details' => 'لم يتم العثور على تصنيف بهذا المعرف'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // جلب الخدمات المرتبطة بالتصنيف
    $stmt = $db->prepare("SELECT service_id FROM services WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $services = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'services' => $services,
        'count' => count($services)
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("Error in get_category_services.php: " . $e->getMessage());
    
    $error_details = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];
    
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ أثناء جلب الخدمات',
        'details' => $error_details
    ], JSON_UNESCAPED_UNICODE);
} 