<?php
/**
 * معالجة الاشتراك في النشرة البريدية لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الإعدادات
require_once 'includes/config.php';

// التحقق من وجود طلب POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من وجود البريد الإلكتروني
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = clean_input($_POST['email']);
        
        // التحقق من صحة البريد الإلكتروني
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                // التحقق من وجود البريد الإلكتروني مسبقاً
                $check_stmt = $db->prepare("SELECT subscriber_id FROM subscribers WHERE email = ?");
                $check_stmt->execute([$email]);
                
                if ($check_stmt->rowCount() > 0) {
                    // البريد الإلكتروني مسجل مسبقاً
                    $_SESSION['newsletter_message'] = [
                        'type' => 'info',
                        'text' => 'هذا البريد الإلكتروني مشترك بالفعل في النشرة البريدية.'
                    ];
                } else {
                    // إضافة البريد الإلكتروني إلى قائمة المشتركين
                    $insert_stmt = $db->prepare("INSERT INTO subscribers (email, status) VALUES (?, 'active')");
                    $insert_stmt->execute([$email]);
                    
                    // تأكيد الاشتراك
                    $_SESSION['newsletter_message'] = [
                        'type' => 'success',
                        'text' => 'تم الاشتراك في النشرة البريدية بنجاح. شكراً لك!'
                    ];
                    
                    // يمكن إضافة كود هنا لإرسال بريد إلكتروني تأكيدي للمشترك
                    // mail($email, 'تأكيد الاشتراك في النشرة البريدية', 'شكراً لاشتراكك في النشرة البريدية لمطبعة برنتلي...');
                }
            } catch (PDOException $e) {
                // خطأ في قاعدة البيانات
                error_log("Newsletter subscription error: " . $e->getMessage());
                $_SESSION['newsletter_message'] = [
                    'type' => 'error',
                    'text' => 'حدث خطأ أثناء معالجة طلب الاشتراك. يرجى المحاولة مرة أخرى لاحقاً.'
                ];
            }
        } else {
            // البريد الإلكتروني غير صالح
            $_SESSION['newsletter_message'] = [
                'type' => 'error',
                'text' => 'يرجى إدخال بريد إلكتروني صالح.'
            ];
        }
    } else {
        // لم يتم إدخال بريد إلكتروني
        $_SESSION['newsletter_message'] = [
            'type' => 'error',
            'text' => 'يرجى إدخال بريد إلكتروني للاشتراك في النشرة البريدية.'
        ];
    }
}

// إعادة التوجيه إلى الصفحة السابقة أو الصفحة الرئيسية
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
redirect($redirect_url);
