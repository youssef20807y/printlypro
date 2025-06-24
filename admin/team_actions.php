<?php
/**
 * معالجة إجراءات الفريق للوحة تحكم مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

// التحقق من تسجيل دخول المدير
require_once 'auth.php';

// التحقق من وجود إجراء
if (!isset($_POST['action'])) {
    redirect_with_error('settings.php', 'لم يتم تحديد الإجراء المطلوب');
}

$action = $_POST['action'];

// إضافة عضو فريق جديد
if ($action === 'add') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['name']) || empty($_POST['position'])) {
            redirect_with_error('settings.php', 'يرجى ملء جميع الحقول المطلوبة');
        }
        
        // معالجة تحميل الصورة
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = upload_team_image($_FILES['image']);
            if (!$image_name) {
                redirect_with_error('settings.php', 'حدث خطأ أثناء تحميل الصورة');
            }
        }
        
        // إعداد البيانات للإدخال
        $name = $_POST['name'];
        $position = $_POST['position'];
        $bio = isset($_POST['bio']) ? $_POST['bio'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $social_facebook = isset($_POST['social_facebook']) ? $_POST['social_facebook'] : '';
        $social_twitter = isset($_POST['social_twitter']) ? $_POST['social_twitter'] : '';
        $social_instagram = isset($_POST['social_instagram']) ? $_POST['social_instagram'] : '';
        $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        // إدخال البيانات في قاعدة البيانات
        $stmt = $db->prepare("
            INSERT INTO team (name, position, bio, image, email, phone, social_facebook, social_twitter, social_instagram, order_num, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name, $position, $bio, $image_name, $email, $phone, $social_facebook, $social_twitter, $social_instagram, $order_num, $status
        ]);
        
        // إعادة التوجيه مع رسالة نجاح
        redirect_with_success('settings.php', 'تمت إضافة عضو الفريق بنجاح');
    } catch (PDOException $e) {
        redirect_with_error('settings.php', 'حدث خطأ أثناء إضافة عضو الفريق: ' . $e->getMessage());
    }
}

// تعديل عضو فريق
elseif ($action === 'edit') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['team_id']) || empty($_POST['name']) || empty($_POST['position'])) {
            redirect_with_error('settings.php', 'يرجى ملء جميع الحقول المطلوبة');
        }
        
        $team_id = intval($_POST['team_id']);
        
        // الحصول على بيانات العضو الحالية
        $stmt = $db->prepare("SELECT image FROM team WHERE team_id = ?");
        $stmt->execute([$team_id]);
        $current_member = $stmt->fetch();
        
        if (!$current_member) {
            redirect_with_error('settings.php', 'عضو الفريق غير موجود');
        }
        
        // معالجة تحميل الصورة الجديدة
        $image_name = $current_member['image']; // الاحتفاظ بالصورة الحالية كقيمة افتراضية
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = upload_team_image($_FILES['image']);
            if ($new_image) {
                // حذف الصورة القديمة إذا كانت موجودة
                if (!empty($current_member['image'])) {
                    $old_image_path = '../uploads/team/' . $current_member['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image_name = $new_image;
            }
        }
        
        // إعداد البيانات للتحديث
        $name = $_POST['name'];
        $position = $_POST['position'];
        $bio = isset($_POST['bio']) ? $_POST['bio'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $social_facebook = isset($_POST['social_facebook']) ? $_POST['social_facebook'] : '';
        $social_twitter = isset($_POST['social_twitter']) ? $_POST['social_twitter'] : '';
        $social_instagram = isset($_POST['social_instagram']) ? $_POST['social_instagram'] : '';
        $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        // تحديث البيانات في قاعدة البيانات
        $stmt = $db->prepare("
            UPDATE team 
            SET name = ?, position = ?, bio = ?, image = ?, email = ?, phone = ?, 
                social_facebook = ?, social_twitter = ?, social_instagram = ?, order_num = ?, status = ?
            WHERE team_id = ?
        ");
        
        $stmt->execute([
            $name, $position, $bio, $image_name, $email, $phone, $social_facebook, $social_twitter, $social_instagram, $order_num, $status, $team_id
        ]);
        
        // إعادة التوجيه مع رسالة نجاح
        redirect_with_success('settings.php', 'تم تحديث بيانات عضو الفريق بنجاح');
    } catch (PDOException $e) {
        redirect_with_error('settings.php', 'حدث خطأ أثناء تحديث بيانات عضو الفريق: ' . $e->getMessage());
    }
}

// حذف عضو فريق
elseif ($action === 'delete') {
    try {
        // التحقق من وجود معرف العضو
        if (empty($_POST['team_id'])) {
            redirect_with_error('settings.php', 'لم يتم تحديد عضو الفريق المراد حذفه');
        }
        
        $team_id = intval($_POST['team_id']);
        
        // الحصول على بيانات العضو قبل الحذف
        $stmt = $db->prepare("SELECT image FROM team WHERE team_id = ?");
        $stmt->execute([$team_id]);
        $member = $stmt->fetch();
        
        if (!$member) {
            redirect_with_error('settings.php', 'عضو الفريق غير موجود');
        }
        
        // حذف الصورة إذا كانت موجودة
        if (!empty($member['image'])) {
            $image_path = '../uploads/team/' . $member['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // حذف العضو من قاعدة البيانات
        $stmt = $db->prepare("DELETE FROM team WHERE team_id = ?");
        $stmt->execute([$team_id]);
        
        // إعادة التوجيه مع رسالة نجاح
        redirect_with_success('settings.php', 'تم حذف عضو الفريق بنجاح');
    } catch (PDOException $e) {
        redirect_with_error('settings.php', 'حدث خطأ أثناء حذف عضو الفريق: ' . $e->getMessage());
    }
}

// الحصول على بيانات عضو فريق
elseif ($action === 'get') {
    try {
        // التحقق من وجود معرف العضو
        if (empty($_POST['team_id'])) {
            echo json_encode(['success' => false, 'message' => 'لم يتم تحديد عضو الفريق']);
            exit;
        }
        
        $team_id = intval($_POST['team_id']);
        
        // الحصول على بيانات العضو
        $stmt = $db->prepare("SELECT * FROM team WHERE team_id = ?");
        $stmt->execute([$team_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            echo json_encode(['success' => false, 'message' => 'عضو الفريق غير موجود']);
            exit;
        }
        
        // إرجاع البيانات كـ JSON
        echo json_encode(['success' => true, 'data' => $member]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع بيانات عضو الفريق: ' . $e->getMessage()]);
        exit;
    }
}

// إجراء غير معروف
else {
    redirect_with_error('settings.php', 'إجراء غير معروف');
}

/**
 * دالة لتحميل صورة عضو الفريق
 * 
 * @param array $file بيانات الملف المرفوع
 * @return string|false اسم الملف بعد التحميل أو false في حالة الفشل
 */
function upload_team_image($file) {
    // التحقق من وجود المجلد وإنشائه إذا لم يكن موجودًا
    $target_dir = '../uploads/team/';
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

/**
 * دالة لإعادة التوجيه مع رسالة خطأ
 * 
 * @param string $url عنوان URL للتوجيه إليه
 * @param string $message رسالة الخطأ
 */
function redirect_with_error($url, $message) {
    $_SESSION['error_message'] = $message;
    header('Location: ' . $url);
    exit;
}

/**
 * دالة لإعادة التوجيه مع رسالة نجاح
 * 
 * @param string $url عنوان URL للتوجيه إليه
 * @param string $message رسالة النجاح
 */
function redirect_with_success($url, $message) {
    $_SESSION['success_message'] = $message;
    header('Location: ' . $url);
    exit;
}

