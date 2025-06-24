<?php
/**
 * ملف AJAX لجلب الحقول المخصصة
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once '../auth.php';

// إعداد الاستجابة JSON
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
    
    $stmt = $db->query($query);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إضافة خيارات الحقول من نوع select
    foreach ($fields as &$field) {
        if ($field['has_options']) {
            $options_query = $db->prepare("
                SELECT option_value, option_label, is_default, order_num
                FROM field_options
                WHERE field_id = ?
                ORDER BY order_num, option_label
            ");
            $options_query->execute([$field['field_id']]);
            $field['options'] = $options_query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $field['options'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'fields' => $fields,
        'count' => count($fields)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ عام: ' . $e->getMessage()
    ]);
}
?>

