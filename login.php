<?php
/**
 * صفحة تسجيل الدخول للمستخدمين
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الإعدادات
require_once 'includes/config.php';

// دالة إنشاء توكن عشوائي آمن
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// التحقق من وجود جلسة مسجلة مسبقاً
if (is_logged_in()) {
    redirect('account.php');
}

// التحقق من وجود كوكيز "تذكرني"
if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $stmt = $db->prepare("
            SELECT u.user_id, u.username, u.role 
            FROM users u 
            JOIN user_tokens t ON u.user_id = t.user_id 
            WHERE t.token = ? AND t.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // تحديث وقت آخر تسجيل دخول
            $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->execute([$user['user_id']]);
            
            // تخزين بيانات المستخدم في الجلسة
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // إعادة توجيه المستخدم
            redirect('account.php');
        } else {
            // حذف الكوكيز غير الصالح
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    } catch (PDOException $e) {
        // حذف الكوكيز في حالة حدوث خطأ
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

// معالجة نموذج تسجيل الدخول
$error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        try {
            // التحقق من بيانات المستخدم
            $stmt = $db->prepare("SELECT user_id, username, password, role FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // تحديث وقت آخر تسجيل دخول
                $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->execute([$user['user_id']]);
                
                // تخزين بيانات المستخدم في الجلسة
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // إنشاء كوكيز تلقائياً
                $token = generate_token();
                $expires = time() + (30 * 24 * 60 * 60); // 30 يوم
                
                // حفظ التوكن في قاعدة البيانات
                $token_stmt = $db->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
                $token_stmt->execute([$user['user_id'], $token, $expires]);
                
                // إنشاء الكوكيز
                setcookie('remember_token', $token, $expires, '/', '', true, true);
                
                $login_success = true;
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'account.php';
                header("Location: $redirect");
                exit();
            } else {
                $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'حدث خطأ أثناء تسجيل الدخول: ' . $e->getMessage() . '<br>';
            $error .= 'رمز الخطأ: ' . $e->getCode() . '<br>';
            $error .= 'في الملف: ' . $e->getFile() . '<br>';
            $error .= 'في السطر: ' . $e->getLine();
        } catch (Exception $e) {
            error_log("General error: " . $e->getMessage());
            $error = 'حدث خطأ غير متوقع: ' . $e->getMessage() . '<br>';
            $error .= 'رمز الخطأ: ' . $e->getCode() . '<br>';
            $error .= 'في الملف: ' . $e->getFile() . '<br>';
            $error .= 'في السطر: ' . $e->getLine();
        }
    }
}

// استدعاء ملف الرأس
require_once 'includes/header.php';
?>

<!-- رأس الصفحة -->
<section class="page-header" style="background-image: url('assets/images/login-header.jpg');">
    <div class="container">
        <h1 class="page-title">تسجيل الدخول</h1>
</section>

<!-- قسم تسجيل الدخول -->
<section class="login-section section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-form-wrapper">
                <h2 class="section-title">تسجيل الدخول</h2>
                
                <?php if (!$login_success && !empty($error)): ?>
                    <div class="error-container">
                        <?php echo nl2br(htmlspecialchars($error)); ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني <span class="required">*</span></label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">كلمة المرور <span class="required">*</span></label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    
                    <div class="form-group">
                        <a href="forgot-password.php" class="forgot-link">نسيت كلمة المرور؟</a>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-gold">تسجيل الدخول</button>
                    </div>
                </form>
                
                <div class="auth-links">
                    <p>ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a></p>
                </div>
            </div>
            
            <div class="auth-info">
                <h3>مزايا إنشاء حساب</h3>
                <ul class="benefits-list">
                    <li>
                        <i class="fas fa-history"></i>
                        <div>
                            <h4>متابعة الطلبات</h4>
                            <p>تتبع حالة طلباتك ومعرفة مراحل التنفيذ</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-shopping-cart"></i>
                        <div>
                            <h4>سلة مشتريات دائمة</h4>
                            <p>احتفظ بالمنتجات في سلة المشتريات لوقت لاحق</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <h4>سجل الطلبات</h4>
                            <p>الوصول إلى سجل كامل لطلباتك السابقة</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-percent"></i>
                        <div>
                            <h4>عروض خاصة</h4>
                            <p>احصل على عروض وخصومات حصرية للأعضاء</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<style>
/* أنماط صفحة تسجيل الدخول */
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

.remember-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.checkbox {
    display: flex;
    align-items: center;
}

.checkbox input {
    margin-left: var(--spacing-xs);
}

.forgot-link {
    color: var(--color-dark-gray);
    text-decoration: none;
    transition: var(--transition-fast);
}

.forgot-link:hover {
    color: var(--color-gold);
}

.form-actions {
    margin-top: var(--spacing-lg);
}

.auth-links {
    margin-top: var(--spacing-lg);
    text-align: center;
}

.auth-links a {
    color: var(--color-gold);
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
    color: var(--color-gold);
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

/* التصميم المتجاوب */
@media (max-width: 768px) {
    .remember-group {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .forgot-link {
        margin-top: var(--spacing-xs);
    }
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
