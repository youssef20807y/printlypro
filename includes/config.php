<?php
/**
 * ملف الإعدادات الرئيسي لموقع مطبعة برنتلي
 */

// منع الوصول المباشر للملف


// باقي محتوى الملف...

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'printly_db');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
define('SITE_URL', 'http://localhost/printly');
define('SITE_NAME', 'برنتلي');
define('SITE_DESCRIPTION', 'نحو طباعة متقنة، تليق بأفكارك');
define('ADMIN_EMAIL', 'admin@printly.com');

// مسارات المجلدات
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__FILE__)));
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', ROOT_PATH . '/includes');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', ROOT_PATH . '/uploads');
if (!defined('DESIGNS_PATH')) define('DESIGNS_PATH', UPLOADS_PATH . '/designs');
if (!defined('PORTFOLIO_PATH')) define('PORTFOLIO_PATH', UPLOADS_PATH . '/portfolio');
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . '/assets');


// إعدادات الجلسة
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    session_start();
}
require_once 'db_connect.php'; // تأكد من أن المسار صحيح


// إعدادات المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// دالة الاتصال بقاعدة البيانات
function db_connect() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }
}

// دالة تنظيف المدخلات
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// دالة التحقق من تسجيل الدخول
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// دالة التحقق من صلاحيات المسؤول
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// دالة إعادة التوجيه
function redirect($url) {
    header("Location: $url");
    exit;
}

// دالة عرض رسائل النجاح والخطأ
function show_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// دالة إنشاء رقم تتبع فريد للطلب
function generate_order_number() {
    return 'ORD-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// دالة الحصول على إعدادات الموقع من قاعدة البيانات
function get_setting($key, $default = '') {
    static $settings = null;
    
    if ($settings === null) {
        $db = db_connect();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE is_public = 1");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// دالة تحميل الملفات
function upload_file($file, $destination, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    // التحقق من وجود أخطاء
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء رفع الملف'];
    }
    // الاتصال بقاعدة البيانات وتخزينه في متغير $db
$db = db_connect();

    // التحقق من نوع الملف
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به'];
    }
    
    // التحقق من حجم الملف
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
    }
    
    // إنشاء اسم فريد للملف
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_extension;
    $file_path = $destination . '/' . $file_name;
    
    // نقل الملف إلى المجلد المحدد
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => true, 'file_name' => $file_name, 'file_path' => $file_path];
    } else {
        return ['success' => false, 'message' => 'فشل في رفع الملف'];
    }
    // ✅ هنا فقط تضع الاتصال بقاعدة البيانات
}
$db = db_connect();
