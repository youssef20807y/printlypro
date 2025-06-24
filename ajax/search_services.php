<?php
/**
 * معالجة طلبات البحث المباشر
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الإعدادات
require_once '../includes/config.php';

// استدعاء اتصال قاعدة البيانات
$db = db_connect();

// التحقق من وجود كلمة البحث
if (!isset($_GET['q']) || empty($_GET['q'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'كلمة البحث فارغة',
        'query' => '',
        'count' => 0,
        'results' => [],
        'error_details' => null
    ]);
    exit;
}

$query = trim($_GET['q']);

try {
    // البحث في قاعدة البيانات
    $searchTerm = "%{$query}%";
    
    // استخدام استعلام بسيط للبحث
    $sql = "SELECT 
                s.service_id,
                s.name,
                s.description,
                s.price_start,
                s.image,
                c.name as category_name
            FROM services s
            LEFT JOIN categories c ON s.category_id = c.category_id
            WHERE s.status = 'active'
            AND (
                s.name LIKE ? 
                OR s.description LIKE ? 
                OR c.name LIKE ?
            )
            ORDER BY s.name ASC
            LIMIT 5";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تنظيف النتائج
    foreach ($results as &$result) {
        $result['name'] = htmlspecialchars($result['name']);
        $result['description'] = strip_tags($result['description'], '<p><strong><b><em><i><br>');
        $result['price_start'] = number_format($result['price_start'], 2);
        $result['category_name'] = htmlspecialchars($result['category_name'] ?? 'بدون تصنيف');
    }
    
    // إرسال النتائج بتنسيق JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => count($results) > 0 ? 'تم العثور على نتائج' : 'عذراً، لم نتمكن من العثور على نتائج تطابق بحثك. يرجى تجربة كلمات بحث أخرى.',
        'query' => $query,
        'count' => count($results),
        'results' => $results,
        'error_details' => null
    ]);
    
} catch (PDOException $e) {
    // تسجيل الخطأ في ملف السجل
    error_log("Search Error - Query: " . $query . "\nError: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . "\nTrace: " . $e->getTraceAsString());
    
    // إرسال رسالة خطأ بتنسيق JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'حدث خطأ أثناء البحث',
        'query' => $query,
        'count' => 0,
        'results' => [],
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    exit;
} 