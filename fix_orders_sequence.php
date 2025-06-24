<?php
/**
 * سكريبت إصلاح تسلسل معرفات الطلبات في قاعدة البيانات
 * يحل مشكلة انتهاك قيود المفاتيح الأجنبية
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

echo "<h2>بدء إصلاح تسلسل معرفات الطلبات</h2>";

try {
    // بدء المعاملة
    $db->beginTransaction();
    
    echo "<p>1. فحص الطلبات الموجودة...</p>";
    
    // جلب جميع الطلبات مرتبة حسب المعرف
    $stmt = $db->prepare("SELECT order_id, order_number FROM orders ORDER BY order_id ASC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>تم العثور على " . count($orders) . " طلب</p>";
    
    // جلب جميع عناصر الطلبات
    $stmt = $db->prepare("SELECT DISTINCT order_id FROM order_items ORDER BY order_id ASC");
    $stmt->execute();
    $order_items_orders = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>تم العثور على عناصر طلبات لـ " . count($order_items_orders) . " طلب مختلف</p>";
    
    // العثور على الطلبات المفقودة (التي لها عناصر ولكن لا توجد في جدول الطلبات)
    $existing_order_ids = array_column($orders, 'order_id');
    $missing_order_ids = array_diff($order_items_orders, $existing_order_ids);
    
    if (!empty($missing_order_ids)) {
        echo "<p style='color: red;'>2. تم العثور على طلبات مفقودة: " . implode(', ', $missing_order_ids) . "</p>";
        
        // حذف عناصر الطلبات المفقودة
        foreach ($missing_order_ids as $missing_order_id) {
            echo "<p>حذف عناصر الطلب المفقود: " . $missing_order_id . "</p>";
            
            // حذف ملفات عناصر الطلبات أولاً
            $stmt = $db->prepare("
                DELETE oif FROM order_item_files oif 
                INNER JOIN order_items oi ON oif.item_id = oi.item_id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$missing_order_id]);
            
            // حذف عناصر الطلبات
            $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$missing_order_id]);
        }
        
        echo "<p style='color: green;'>تم حذف جميع عناصر الطلبات المفقودة</p>";
    } else {
        echo "<p style='color: green;'>لا توجد طلبات مفقودة</p>";
    }
    
    // إعادة ترتيب معرفات الطلبات
    echo "<p>3. إعادة ترتيب معرفات الطلبات...</p>";
    
    // إنشاء جدول مؤقت للطلبات
    $db->exec("CREATE TEMPORARY TABLE temp_orders AS SELECT * FROM orders ORDER BY order_id ASC");
    
    // حذف جميع الطلبات من الجدول الأصلي
    $db->exec("DELETE FROM orders");
    
    // إعادة تعيين AUTO_INCREMENT
    $db->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
    
    // إعادة إدراج الطلبات بترتيب جديد
    $stmt = $db->prepare("
        INSERT INTO orders (
            user_id, order_number, total_amount, status, payment_status, payment_method,
            delivery_method_id, delivery_type, shipping_name, pickup_name, pickup_phone,
            shipping_phone, shipping_email, shipping_address, shipping_city, shipping_country,
            shipping_cost, notes, created_at, updated_at, payment_proof, payment_date,
            points_used, points_discount, points_earned
        ) SELECT 
            user_id, order_number, total_amount, status, payment_status, payment_method,
            delivery_method_id, delivery_type, shipping_name, pickup_name, pickup_phone,
            shipping_phone, shipping_email, shipping_address, shipping_city, shipping_country,
            shipping_cost, notes, created_at, updated_at, payment_proof, payment_date,
            points_used, points_discount, points_earned
        FROM temp_orders
    ");
    $stmt->execute();
    
    // حذف الجدول المؤقت
    $db->exec("DROP TEMPORARY TABLE temp_orders");
    
    echo "<p style='color: green;'>تم إعادة ترتيب معرفات الطلبات بنجاح</p>";
    
    // تحديث معرفات الطلبات في عناصر الطلبات
    echo "<p>4. تحديث معرفات الطلبات في عناصر الطلبات...</p>";
    
    // جلب الطلبات الجديدة مع معرفاتها الجديدة
    $stmt = $db->prepare("SELECT order_id, order_number FROM orders ORDER BY order_id ASC");
    $stmt->execute();
    $new_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إنشاء جدول مؤقت لعناصر الطلبات
    $db->exec("CREATE TEMPORARY TABLE temp_order_items AS SELECT * FROM order_items ORDER BY order_id ASC, item_id ASC");
    
    // حذف جميع عناصر الطلبات
    $db->exec("DELETE FROM order_items");
    
    // إعادة تعيين AUTO_INCREMENT
    $db->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
    
    // إعادة إدراج عناصر الطلبات مع معرفات الطلبات الجديدة
    foreach ($new_orders as $index => $order) {
        $old_order_id = $index + 1; // معرف الطلب القديم (ترتيبي)
        
        $stmt = $db->prepare("
            INSERT INTO order_items (
                order_id, service_id, quantity, paper_type, size, colors, notes, price
            ) SELECT 
                ?, service_id, quantity, paper_type, size, colors, notes, price
            FROM temp_order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$order['order_id'], $old_order_id]);
    }
    
    // حذف الجدول المؤقت
    $db->exec("DROP TEMPORARY TABLE temp_order_items");
    
    echo "<p style='color: green;'>تم تحديث معرفات الطلبات في عناصر الطلبات بنجاح</p>";
    
    // تحديث معرفات عناصر الطلبات في ملفات عناصر الطلبات
    echo "<p>5. تحديث معرفات عناصر الطلبات في الملفات...</p>";
    
    // إنشاء جدول مؤقت لملفات عناصر الطلبات
    $db->exec("CREATE TEMPORARY TABLE temp_order_item_files AS SELECT * FROM order_item_files ORDER BY item_id ASC");
    
    // حذف جميع ملفات عناصر الطلبات
    $db->exec("DELETE FROM order_item_files");
    
    // إعادة تعيين AUTO_INCREMENT
    $db->exec("ALTER TABLE order_item_files AUTO_INCREMENT = 1");
    
    // إعادة إدراج ملفات عناصر الطلبات مع معرفات العناصر الجديدة
    $stmt = $db->prepare("SELECT item_id FROM order_items ORDER BY item_id ASC");
    $stmt->execute();
    $new_item_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($new_item_ids as $index => $new_item_id) {
        $old_item_id = $index + 1; // معرف العنصر القديم (ترتيبي)
        
        $stmt = $db->prepare("
            INSERT INTO order_item_files (
                item_id, file_name, uploaded_at, is_payment_proof
            ) SELECT 
                ?, file_name, uploaded_at, is_payment_proof
            FROM temp_order_item_files 
            WHERE item_id = ?
        ");
        $stmt->execute([$new_item_id, $old_item_id]);
    }
    
    // حذف الجدول المؤقت
    $db->exec("DROP TEMPORARY TABLE temp_order_item_files");
    
    echo "<p style='color: green;'>تم تحديث معرفات عناصر الطلبات في الملفات بنجاح</p>";
    
    // التحقق من صحة البيانات
    echo "<p>6. التحقق من صحة البيانات...</p>";
    
    // التحقق من عدد الطلبات
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders");
    $stmt->execute();
    $orders_count = $stmt->fetchColumn();
    
    // التحقق من عدد عناصر الطلبات
    $stmt = $db->prepare("SELECT COUNT(*) FROM order_items");
    $stmt->execute();
    $order_items_count = $stmt->fetchColumn();
    
    // التحقق من عدد ملفات عناصر الطلبات
    $stmt = $db->prepare("SELECT COUNT(*) FROM order_item_files");
    $stmt->execute();
    $order_item_files_count = $stmt->fetchColumn();
    
    echo "<p>عدد الطلبات: " . $orders_count . "</p>";
    echo "<p>عدد عناصر الطلبات: " . $order_items_count . "</p>";
    echo "<p>عدد ملفات عناصر الطلبات: " . $order_item_files_count . "</p>";
    
    // التحقق من عدم وجود عناصر طلبات بدون طلبات
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM order_items oi 
        LEFT JOIN orders o ON oi.order_id = o.order_id 
        WHERE o.order_id IS NULL
    ");
    $stmt->execute();
    $orphaned_items = $stmt->fetchColumn();
    
    if ($orphaned_items == 0) {
        echo "<p style='color: green;'>✓ لا توجد عناصر طلبات بدون طلبات</p>";
    } else {
        echo "<p style='color: red;'>✗ يوجد " . $orphaned_items . " عنصر طلب بدون طلب</p>";
    }
    
    // التحقق من عدم وجود ملفات بدون عناصر طلبات
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM order_item_files oif 
        LEFT JOIN order_items oi ON oif.item_id = oi.item_id 
        WHERE oi.item_id IS NULL
    ");
    $stmt->execute();
    $orphaned_files = $stmt->fetchColumn();
    
    if ($orphaned_files == 0) {
        echo "<p style='color: green;'>✓ لا توجد ملفات بدون عناصر طلبات</p>";
    } else {
        echo "<p style='color: red;'>✗ يوجد " . $orphaned_files . " ملف بدون عنصر طلب</p>";
    }
    
    // تأكيد المعاملة
    $db->commit();
    
    echo "<h2 style='color: green;'>تم إصلاح قاعدة البيانات بنجاح!</h2>";
    echo "<p>يمكنك الآن إنشاء طلبات جديدة بدون مشاكل.</p>";
    
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<h2 style='color: red;'>حدث خطأ أثناء إصلاح قاعدة البيانات</h2>";
    echo "<p>الخطأ: " . $e->getMessage() . "</p>";
    echo "<p>يرجى مراجعة السجلات للحصول على مزيد من التفاصيل.</p>";
}

echo "<p><a href='index.php'>العودة للصفحة الرئيسية</a></p>";
?> 