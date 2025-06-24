<?php
/**
 * صفحة استعادة كلمة المرور
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

// إضافة الأعمدة المطلوبة إذا لم تكن موجودة
try {
    // التحقق من وجود عمود reset_code
    $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'reset_code'");
    if ($check_column->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN reset_code VARCHAR(255) NULL");
    }
    
    // التحقق من وجود عمود reset_expires
    $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'reset_expires'");
    if ($check_column->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
    }
} catch (PDOException $e) {
    // يمكنك تسجيل الخطأ هنا إذا كنت تريد
    // error_log($e->getMessage());
}

// معالجة نموذج استعادة كلمة المرور
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    
    // التحقق من البريد الإلكتروني
    if (empty($email)) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح';
    } else {
        // التحقق من وجود البريد الإلكتروني في قاعدة البيانات
        $check_stmt = $db->prepare("SELECT user_id, username FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        
        if ($check_stmt->rowCount() > 0) {
            $user = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // إنشاء رمز إعادة تعيين كلمة المرور
            $reset_code = md5(uniqid(rand(), true));
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // تحديث رمز إعادة التعيين في قاعدة البيانات
            $update_stmt = $db->prepare("
                UPDATE users 
                SET reset_code = ?, reset_expires = ? 
                WHERE user_id = ?
            ");
            
            try {
                $update_stmt->execute([$reset_code, $reset_expires, $user['user_id']]);
                
                // إرسال بريد إعادة تعيين كلمة المرور
                if (send_reset_email($email, $user['username'], $reset_code)) {
                    $success = true;
                } else {
                    $errors[] = 'حدث خطأ أثناء إرسال البريد الإلكتروني. يرجى التحقق من:';
                    $errors[] = '- صحة البريد الإلكتروني المدخل';
                    $errors[] = '- اتصال الإنترنت لديك';
                    $errors[] = '- عدم وجود مشكلة في خادم البريد';
                    $errors[] = 'يمكنك المحاولة مرة أخرى بعد قليل أو التواصل مع الدعم الفني';
                    
                    // إعادة تعيين رمز إعادة التعيين في حالة فشل الإرسال
                    $reset_stmt = $db->prepare("UPDATE users SET reset_code = NULL, reset_expires = NULL WHERE user_id = ?");
                    $reset_stmt->execute([$user['user_id']]);
                }
            } catch (PDOException $e) {
                $errors[] = 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage();
                error_log("خطأ في قاعدة البيانات: " . $e->getMessage());
            }
        } else {
            // إظهار نفس رسالة النجاح حتى لو لم يكن البريد موجوداً (لأسباب أمنية)
            $success = true;
        }
    }
}

// استدعاء ملف الرأس
require_once 'includes/header.php';
?>

<!-- رأس الصفحة -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">استعادة كلمة المرور</h1>
    </div>
</section>

<!-- قسم استعادة كلمة المرور -->
<section class="forgot-password-section section">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>تم إرسال رابط إعادة تعيين كلمة المرور</h2>
                <p>إذا كان البريد الإلكتروني مسجلاً في نظامنا، سيتم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.</p>
                <div class="success-actions">
                    <a href="login.php" class="btn btn-gold">العودة إلى تسجيل الدخول</a>
                </div>
            </div>
        <?php else: ?>
            <div class="auth-container">
                <div class="auth-form-wrapper">
                    <h2 class="section-title">استعادة كلمة المرور</h2>
                    
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
                            <label for="email">البريد الإلكتروني <span class="required">*</span></label>
                            <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-gold">إرسال رابط إعادة التعيين</button>
                        </div>
                    </form>
                    
                    <div class="auth-links">
                        <p>تذكرت كلمة المرور؟ <a href="login.php">تسجيل الدخول</a></p>
                    </div>
                </div>
                
                <div class="auth-info">
                    <h3>تعليمات استعادة كلمة المرور</h3>
                    <ul class="benefits-list">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>أدخل بريدك الإلكتروني</h4>
                                <p>أدخل البريد الإلكتروني المستخدم في حسابك</p>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-link"></i>
                            <div>
                                <h4>استلام الرابط</h4>
                                <p>سيتم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني</p>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-key"></i>
                            <div>
                                <h4>تعيين كلمة مرور جديدة</h4>
                                <p>انقر على الرابط في البريد الإلكتروني لتعيين كلمة مرور جديدة</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* أنماط صفحة استعادة كلمة المرور */
.auth-container {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
    margin-top: var(--spacing-lg);
}

.auth-form-wrapper {
    flex: 1;
    min-width: 300px;
    background-color: var(--color-white);
    border-radius: 8px;
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
}

.auth-info {
    flex: 1;
    min-width: 300px;
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

.form-actions {
    margin-top: var(--spacing-lg);
}

.auth-links {
    margin-top: var(--spacing-lg);
    text-align: center;
}

.auth-links a {
    color: #00adef;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition-fast);
}

.auth-links a:hover {
    text-decoration: underline;
}

.benefits-list {
    list-style: none;
    padding: 0;
    margin-top: var(--spacing-md);
}

.benefits-list li {
    display: flex;
    align-items: flex-start;
    margin-bottom: var(--spacing-md);
}

.benefits-list i {
    font-size: 1.5rem;
    color: #00adef;
    margin-left: var(--spacing-md);
    margin-top: var(--spacing-xs);
}

.benefits-list h4 {
    margin: 0 0 var(--spacing-xs) 0;
    font-size: 1.1rem;
}

.benefits-list p {
    margin: 0;
    color: var(--color-dark-gray);
}

.error-container {
    background-color: #fce4e4;
    border-right: 4px solid #e74c3c;
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    border-radius: 4px;
    color: #e74c3c;
}

.error-container ul {
    margin: 0;
    padding-right: var(--spacing-md);
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
    .success-actions .btn {
        width: 100%;
    }
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?> 