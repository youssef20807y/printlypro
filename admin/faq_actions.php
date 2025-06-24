<?php
/**
 * معالجة إجراءات الأسئلة الشائعة للوحة تحكم مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

// التحقق من تسجيل دخول المدير
require_once 'auth.php';

// التحقق من وجود إجراء
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'لم يتم تحديد الإجراء المطلوب']);
    exit;
}

$action = $_POST['action'];

// إضافة سؤال جديد
if ($action === 'add') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['question']) || empty($_POST['answer'])) {
            throw new Exception('يرجى إدخال السؤال والإجابة');
        }
        
        // إعداد البيانات
        $question = $_POST['question'];
        $answer = $_POST['answer'];
        $category = $_POST['category'] ?? 'عام';
        $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
        $status = $_POST['status'] ?? 'active';
        
        // إدخال البيانات في قاعدة البيانات
        $stmt = $db->prepare("
            INSERT INTO faq (question, answer, category, order_num, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$question, $answer, $category, $order_num, $status]);
        
        // إعادة التوجيه إلى صفحة الأسئلة الشائعة
        header('Location: faq.php?success=1');
        exit;
    } catch (Exception $e) {
        header('Location: faq.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// تعديل سؤال
elseif ($action === 'edit') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['faq_id']) || empty($_POST['question']) || empty($_POST['answer'])) {
            throw new Exception('يرجى إدخال جميع البيانات المطلوبة');
        }
        
        // إعداد البيانات
        $faq_id = intval($_POST['faq_id']);
        $question = $_POST['question'];
        $answer = $_POST['answer'];
        $category = $_POST['category'] ?? 'عام';
        $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
        $status = $_POST['status'] ?? 'active';
        
        // تحديث البيانات في قاعدة البيانات
        $stmt = $db->prepare("
            UPDATE faq 
            SET question = ?, answer = ?, category = ?, order_num = ?, status = ?
            WHERE faq_id = ?
        ");
        
        $stmt->execute([$question, $answer, $category, $order_num, $status, $faq_id]);
        
        // إعادة التوجيه إلى صفحة الأسئلة الشائعة
        header('Location: faq.php?success=2');
        exit;
    } catch (Exception $e) {
        header('Location: faq.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// الحصول على بيانات سؤال
elseif ($action === 'get') {
    try {
        // التحقق من وجود معرف السؤال
        if (empty($_POST['faq_id'])) {
            throw new Exception('لم يتم تحديد معرف السؤال');
        }
        
        $faq_id = intval($_POST['faq_id']);
        
        // استرجاع بيانات السؤال
        $stmt = $db->prepare("SELECT * FROM faq WHERE faq_id = ?");
        $stmt->execute([$faq_id]);
        $faq = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$faq) {
            throw new Exception('السؤال غير موجود');
        }
        
        // إرجاع البيانات بتنسيق JSON
        echo json_encode(['success' => true, 'data' => $faq]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// إجراء غير معروف
else {
    echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
    exit;
}

