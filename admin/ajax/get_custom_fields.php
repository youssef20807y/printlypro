<?php
/**
 * ملف AJAX لجلب الحقول المخصصة
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';

// تعيين نوع المحتوى كـ JSON
header('Content-Type: application/json');

try {
    // جلب الحقول المخصصة النشطة
    $query = "
        SELECT cf.*, ft.type_name, ft.type_key, ft.has_options
        FROM custom_fields cf
        LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
        WHERE cf.status = 'active'
        ORDER BY cf.order_num, cf.field_name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب الخيارات لكل حقل
    foreach ($fields as &$field) {
        if ($field['has_options']) {
            $options_query = $db->prepare("
                SELECT option_value, option_label, is_default, order_num
                FROM field_options 
                WHERE field_id = ? AND status = 'active'
                ORDER BY order_num
            ");
            $options_query->execute([$field['field_id']]);
            $field['options'] = $options_query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $field['options'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (PDOException $e) {
    error_log('Error fetching custom fields: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب الحقول المخصصة'
    ]);
}
?> 