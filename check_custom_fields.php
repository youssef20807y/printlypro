<?php
/**
 * ملف مؤقت لفحص الحقول المخصصة الموجودة في قاعدة البيانات
 */

define('PRINTLY', true);
require_once 'includes/config.php';

echo "<h2>الحقول المخصصة الموجودة في قاعدة البيانات:</h2>";

try {
    // جلب جميع الحقول المخصصة
    $query = "
        SELECT cf.*, ft.type_name, ft.type_key, ft.has_options
        FROM custom_fields cf
        LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
        ORDER BY cf.field_id
    ";
    
    $stmt = $db->query($query);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fields)) {
        echo "<p>لا توجد حقول مخصصة في قاعدة البيانات.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th>";
        echo "<th>اسم الحقل</th>";
        echo "<th>عنوان الحقل</th>";
        echo "<th>نوع الحقل</th>";
        echo "<th>مطلوب</th>";
        echo "<th>الحالة</th>";
        echo "</tr>";
        
        foreach ($fields as $field) {
            echo "<tr>";
            echo "<td>" . $field['field_id'] . "</td>";
            echo "<td>" . htmlspecialchars($field['field_name']) . "</td>";
            echo "<td>" . htmlspecialchars($field['field_label']) . "</td>";
            echo "<td>" . htmlspecialchars($field['type_name']) . "</td>";
            echo "<td>" . ($field['is_required'] ? 'نعم' : 'لا') . "</td>";
            echo "<td>" . htmlspecialchars($field['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // فحص الحقول المخصصة المرتبطة بالخدمات
    echo "<h3>الحقول المخصصة المرتبطة بالخدمات:</h3>";
    
    $service_fields_query = "
        SELECT sf.service_id, s.name as service_name, cf.field_id, cf.field_label, cf.field_name
        FROM service_fields sf
        JOIN services s ON sf.service_id = s.service_id
        JOIN custom_fields cf ON sf.field_id = cf.field_id
        WHERE sf.status = 'active' AND cf.status = 'active'
        ORDER BY sf.service_id, sf.order_num
    ";
    
    $stmt = $db->query($service_fields_query);
    $service_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($service_fields)) {
        echo "<p>لا توجد حقول مخصصة مرتبطة بأي خدمة.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID الخدمة</th>";
        echo "<th>اسم الخدمة</th>";
        echo "<th>ID الحقل</th>";
        echo "<th>عنوان الحقل</th>";
        echo "<th>اسم الحقل</th>";
        echo "</tr>";
        
        foreach ($service_fields as $sf) {
            echo "<tr>";
            echo "<td>" . $sf['service_id'] . "</td>";
            echo "<td>" . htmlspecialchars($sf['service_name']) . "</td>";
            echo "<td>" . $sf['field_id'] . "</td>";
            echo "<td>" . htmlspecialchars($sf['field_label']) . "</td>";
            echo "<td>" . htmlspecialchars($sf['field_name']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
}
?> 