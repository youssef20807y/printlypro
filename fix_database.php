<?php
/**
 * سكريبت إصلاح مشكلة قيود المفاتيح الأجنبية في الطلبات
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إصلاح قاعدة البيانات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .step { margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>إصلاح قاعدة البيانات</h1>";

try {
    // بدء المعاملة
    $db->beginTransaction();
    
    echo "<div class='step'>
        <h3>الخطوة 1: حذف قيود المفاتيح الأجنبية مؤقتاً</h3>";
    
    // حذف قيود المفاتيح الأجنبية
    $db->exec("ALTER TABLE order_item_files DROP FOREIGN KEY order_item_files_ibfk_1");
    $db->exec("ALTER TABLE order_items DROP FOREIGN KEY order_items_ibfk_1");
    $db->exec("ALTER TABLE order_items DROP FOREIGN KEY order_items_ibfk_2");
    
    echo "<p class='success'>✓ تم حذف قيود المفاتيح الأجنبية بنجاح</p>
    </div>";
    
    echo "<div class='step'>
        <h3>الخطوة 2: حذف عناصر الطلبات المفقودة</h3>";
    
    // حذف عناصر الطلبات التي تشير إلى طلبات غير موجودة
    $stmt = $db->prepare("
        DELETE oi FROM order_items oi 
        LEFT JOIN orders o ON oi.order_id = o.order_id 
        WHERE o.order_id IS NULL
    ");
    $stmt->execute();
    $deleted_items = $stmt->rowCount();
    
    echo "<p class='info'>تم حذف " . $deleted_items . " عنصر طلب بدون طلب</p>
    </div>";
    
    echo "<div class='step'>
        <h3>الخطوة 3: حذف ملفات عناصر الطلبات المفقودة</h3>";
    
    // حذف ملفات عناصر الطلبات التي تشير إلى عناصر غير موجودة
    $stmt = $db->prepare("
        DELETE oif FROM order_item_files oif 
        LEFT JOIN order_items oi ON oif.item_id = oi.item_id 
        WHERE oi.item_id IS NULL
    ");
    $stmt->execute();
    $deleted_files = $stmt->rowCount();
    
    echo "<p class='info'>تم حذف " . $deleted_files . " ملف بدون عنصر طلب</p>
    </div>";
    
    echo "<div class='step'>
        <h3>الخطوة 4: إعادة إنشاء قيود المفاتيح الأجنبية</h3>";
    
    // إعادة إنشاء قيود المفاتيح الأجنبية
    $db->exec("
        ALTER TABLE order_items 
        ADD CONSTRAINT order_items_ibfk_1 
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
    ");
    
    $db->exec("
        ALTER TABLE order_items 
        ADD CONSTRAINT order_items_ibfk_2 
        FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
    ");
    
    $db->exec("
        ALTER TABLE order_item_files 
        ADD CONSTRAINT order_item_files_ibfk_1 
        FOREIGN KEY (item_id) REFERENCES order_items(item_id) ON DELETE CASCADE
    ");
    
    echo "<p class='success'>✓ تم إعادة إنشاء قيود المفاتيح الأجنبية بنجاح</p>
    </div>";
    
    echo "<div class='step'>
        <h3>الخطوة 5: التحقق من صحة البيانات</h3>";
    
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
    
    echo "<p class='info'>عدد الطلبات: " . $orders_count . "</p>";
    echo "<p class='info'>عدد عناصر الطلبات: " . $order_items_count . "</p>";
    echo "<p class='info'>عدد ملفات عناصر الطلبات: " . $order_item_files_count . "</p>";
    
    // التحقق من عدم وجود عناصر طلبات بدون طلبات
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM order_items oi 
        LEFT JOIN orders o ON oi.order_id = o.order_id 
        WHERE o.order_id IS NULL
    ");
    $stmt->execute();
    $orphaned_items = $stmt->fetchColumn();
    
    if ($orphaned_items == 0) {
        echo "<p class='success'>✓ لا توجد عناصر طلبات بدون طلبات</p>";
    } else {
        echo "<p class='error'>✗ يوجد " . $orphaned_items . " عنصر طلب بدون طلب</p>";
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
        echo "<p class='success'>✓ لا توجد ملفات بدون عناصر طلبات</p>";
    } else {
        echo "<p class='error'>✗ يوجد " . $orphaned_files . " ملف بدون عنصر طلب</p>";
    }
    
    echo "</div>";
    
    // تأكيد المعاملة
    $db->commit();
    
    echo "<div class='step'>
        <h2 class='success'>تم إصلاح قاعدة البيانات بنجاح!</h2>
        <p>يمكنك الآن إنشاء طلبات جديدة بدون مشاكل.</p>
        <p><a href='index.php'>العودة للصفحة الرئيسية</a></p>
    </div>";
    
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<div class='step'>
        <h2 class='error'>حدث خطأ أثناء إصلاح قاعدة البيانات</h2>
        <p class='error'>الخطأ: " . $e->getMessage() . "</p>
        <p>يرجى مراجعة السجلات للحصول على مزيد من التفاصيل.</p>
        <p><a href='index.php'>العودة للصفحة الرئيسية</a></p>
    </div>";
}

echo "</div>
</body>
</html>";
?> 