<?php
/**
 * ملف AJAX لجلب خيارات الحقول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';

// تعيين نوع المحتوى كـ JSON
header('Content-Type: application/json');

try {
    $field_name = isset($_GET['field_name']) ? clean_input($_GET['field_name']) : '';
    
    if (empty($field_name)) {
        throw new Exception('اسم الحقل مطلوب');
    }
    
    // جلب خيارات الحقل
    $query = "
        SELECT fo.option_value, fo.option_label, fo.is_default, fo.order_num
        FROM field_options fo
        JOIN custom_fields cf ON fo.field_id = cf.field_id
        WHERE cf.field_name = ? AND fo.status = 'active'
        ORDER BY fo.order_num
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$field_name]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'field_name' => $field_name,
        'options' => $options
    ]);
    
} catch (Exception $e) {
    error_log('Error fetching field options: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 