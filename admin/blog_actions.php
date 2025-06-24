<?php
/**
 * معالجة إجراءات المدونة للوحة تحكم مطبعة برنتلي
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

// إنشاء رابط مختصر من العنوان
if ($action === 'create_slug') {
    if (empty($_POST['title'])) {
        echo json_encode(['success' => false, 'message' => 'العنوان مطلوب']);
        exit;
    }
    
    $title = $_POST['title'];
    $slug = create_slug($title);
    
    echo json_encode(['success' => true, 'slug' => $slug]);
    exit;
}

// تحميل صورة للمحرر
elseif ($action === 'upload_image') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'لم يتم تحديد ملف أو حدث خطأ أثناء التحميل']);
        exit;
    }
    
    $file = $_FILES['file'];
    $image_name = upload_blog_image($file);
    
    if ($image_name) {
        $image_url = '../uploads/blog/' . $image_name;
        echo json_encode(['success' => true, 'url' => $image_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل تحميل الصورة']);
    }
    exit;
}

// إجراء غير معروف
else {
    echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
    exit;
}

/**
 * دالة لإنشاء رابط مختصر من العنوان
 * 
 * @param string $title عنوان المقال
 * @return string الرابط المختصر
 */
function create_slug($title) {
    // تحويل الحروف إلى حروف صغيرة
    $slug = mb_strtolower($title, 'UTF-8');
    
    // استبدال الحروف العربية بحروف إنجليزية
    $arabic_map = [
        'أ' => 'a', 'إ' => 'a', 'آ' => 'a', 'ا' => 'a',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th',
        'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
        'د' => 'd', 'ذ' => 'th',
        'ر' => 'r', 'ز' => 'z',
        'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd',
        'ط' => 't', 'ظ' => 'z',
        'ع' => 'a', 'غ' => 'gh',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k',
        'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a', 'ئ' => 'e',
        'ء' => 'a', 'ؤ' => 'o'
    ];
    
    foreach ($arabic_map as $ar => $en) {
        $slug = str_replace($ar, $en, $slug);
    }
    
    // استبدال المسافات والأحرف الخاصة بشرطات
    $slug = preg_replace('/[^a-z0-9]/', '-', $slug);
    
    // إزالة الشرطات المتكررة
    $slug = preg_replace('/-+/', '-', $slug);
    
    // إزالة الشرطات من البداية والنهاية
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * دالة لتحميل صورة المقال
 * 
 * @param array $file بيانات الملف المرفوع
 * @return string|false اسم الملف بعد التحميل أو false في حالة الفشل
 */
function upload_blog_image($file) {
    // التحقق من وجود المجلد وإنشائه إذا لم يكن موجودًا
    $target_dir = '../uploads/blog/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // الحصول على امتداد الملف
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // التحقق من نوع الملف (للصور فقط)
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    // إنشاء اسم فريد للملف
    $new_file_name = uniqid() . '_' . basename($file['name']);
    $target_file = $target_dir . $new_file_name;
    
    // تحميل الملف
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $new_file_name;
    }
    
    return false;
}

