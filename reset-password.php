<?php
/**
 * صفحة إعادة تعيين كلمة المرور
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الإعدادات
require_once 'includes/config.php';
require_once 'includes/functions.php';
$db = db_connect();

// التحقق من وجود جلسة مسجلة مسبقاً
if (is_logged_in()) {
    redirect('account.php');
}

$errors = [];
$success = false;
$valid_code = false;
$user_id = null;

// التحقق من وجود رمز إعادة التعيين
if (isset($_GET['code'])) {
    $reset_code = clean_input($_GET['code']);
    
    // التحقق من صلاحية الرمز
    $check_stmt = $db->prepare("
        SELECT user_id, email 
        FROM users 
        WHERE reset_code = ? 
        AND reset_expires > NOW() 
        AND status = 'active'
    ");
    $check_stmt->execute([$reset_code]);
    
    if ($check_stmt->rowCount() > 0) {
        $valid_code = true;
        $user_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user_data['user_id'];
        $user_email = $user_data['email'];
    } else {
        $errors[] = 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية';
    }
} else {
    $errors[] = 'رمز إعادة التعيين غير موجود';
}

// معالجة نموذج إعادة تعيين كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_code) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // التحقق من كلمة المرور
    if (empty($password)) {
        $errors[] = 'يرجى إدخال كلمة المرور الجديدة';
    } elseif (strlen($password) < 6) {
        $errors[] = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمات المرور غير متطابقة';
    }
    
    // إذا لم يكن هناك أخطاء، نقوم بتحديث كلمة المرور
    if (empty($errors)) {
        try {
            // تشفير كلمة المرور الجديدة بنفس الطريقة المستخدمة في تسجيل الدخول
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            
            // التحقق من وجود المستخدم قبل التحديث
            $verify_user = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
            $verify_user->execute([$user_id]);
            
            if ($verify_user->rowCount() > 0) {
                // تحديث كلمة المرور وإزالة رمز إعادة التعيين
                $update_stmt = $db->prepare("
                    UPDATE users 
                    SET password = ?, 
                        reset_code = NULL, 
                        reset_expires = NULL,
                        reset_token = NULL,
                        reset_token_expiry = NULL
                    WHERE user_id = ?
                ");
                
                $update_stmt->execute([$hashed_password, $user_id]);
                
                if ($update_stmt->rowCount() > 0) {
                    // التحقق من أن كلمة المرور تم تحديثها بشكل صحيح
                    $verify_password = $db->prepare("SELECT password FROM users WHERE user_id = ?");
                    $verify_password->execute([$user_id]);
                    $stored_hash = $verify_password->fetchColumn();
                    
                    // التحقق من كلمة المرور بنفس طريقة تسجيل الدخول
                    if (password_verify($password, $stored_hash)) {
                        $success = true;
                    } else {
                        $errors[] = 'حدث خطأ في التحقق من كلمة المرور';
                    }
                } else {
                    $errors[] = 'حدث خطأ أثناء تحديث كلمة المرور';
                }
            } else {
                $errors[] = 'لم يتم العثور على المستخدم أو الحساب غير نشط';
            }
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء تحديث كلمة المرور: ' . $e->getMessage();
        }
    }
}

// استدعاء ملف الرأس
require_once 'includes/header.php';
?>

<!-- رأس الصفحة -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">إعادة تعيين كلمة المرور</h1>
    </div>
</section>

<!-- قسم إعادة تعيين كلمة المرور -->
<section class="reset-password-section section">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>تم إعادة تعيين كلمة المرور بنجاح!</h2>
                <p>تم تحديث كلمة المرور الخاصة بك بنجاح. يمكنك الآن تسجيل الدخول باستخدام كلمة المرور الجديدة.</p>
                <div class="success-actions">
                    <a href="login.php" class="btn btn-gold">تسجيل الدخول</a>
                </div>
            </div>
        <?php elseif ($valid_code): ?>
            <div class="auth-container">
                <div class="auth-form-wrapper">
                    <h2 class="section-title">إعادة تعيين كلمة المرور</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="error-container">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" class="auth-form">
                        <div class="form-group">
                            <label for="password">كلمة المرور الجديدة <span class="required">*</span></label>
                            <input type="password" name="password" id="password" required>
                            <small>يجب أن تكون 6 أحرف على الأقل</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">تأكيد كلمة المرور <span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-gold">تحديث كلمة المرور</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="error-container">
                <h2>رمز غير صالح</h2>
                <p>رمز إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية.</p>
                <div class="error-actions">
                    <a href="forgot-password.php" class="btn btn-gold">طلب رمز جديد</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* أنماط صفحة إعادة تعيين كلمة المرور */
.auth-container {
    max-width: 500px;
    margin: var(--spacing-lg) auto;
}

.auth-form-wrapper {
    background-color: var(--color-white);
    border-radius: 8px;
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
}

.auth-form {
    margin-top: var(--spacing-lg);
}

.form-group {
    margin-bottom: var(--spacing-md);
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: var(--color-dark-gray);
    font-size: 0.85rem;
}

.form-actions {
    margin-top: var(--spacing-lg);
}

.error-container {
    text-align: center;
    padding: var(--spacing-xl) 0;
}

.error-actions {
    margin-top: var(--spacing-lg);
}

.success-container {
    text-align: center;
    padding: var(--spacing-xl) 0;
}

.success-icon {
    font-size: 5rem;
    color: #2ecc71;
    margin-bottom: var(--spacing-md);
}

.success-actions {
    margin-top: var(--spacing-lg);
}

/* التصميم المتجاوب */
@media (max-width: 768px) {
    .auth-container {
        margin: var(--spacing-md);
    }
    
    .success-actions .btn,
    .error-actions .btn {
        width: 100%;
    }
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?> 