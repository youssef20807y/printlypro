<?php
/**
 * صفحة تسجيل حساب جديد للمستخدمين
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// تعريف ثابت وضع التصحيح
define('DEBUG_MODE', true);

// استدعاء ملف الإعدادات
require_once 'includes/config.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';
require_once 'includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$db = db_connect();

// التحقق من وجود جلسة مسجلة مسبقاً
if (is_logged_in()) {
    redirect('account.php');
}

// معالجة نموذج التسجيل
$errors = [];
$success = false;
$verification_sent = false;
$verification_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    $verification_code = isset($_POST['verification_code']) ? clean_input($_POST['verification_code']) : '';
    
    // التحقق من البيانات
    if (empty($username)) {
        $errors[] = 'يرجى إدخال اسم المستخدم';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'يجب أن يكون اسم المستخدم بين 3 و 50 حرفاً';
    }
    
    if (empty($email)) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح';
    } else {
        // التحقق من عدم وجود البريد الإلكتروني مسبقاً
        $check_stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->rowCount() > 0) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'يرجى إدخال كلمة المرور';
    } elseif (strlen($password) < 6) {
        $errors[] = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمات المرور غير متطابقة';
    }
    
    if (empty($phone)) {
        $errors[] = 'يرجى إدخال رقم الهاتف';
    }
    
    // إذا لم يكن هناك أخطاء، نقوم بإرسال رمز التحقق
    if (empty($errors)) {
        if (empty($verification_code)) {
            // إنشاء رمز تحقق جديد
            $verification_code = rand(100000, 999999);
            $_SESSION['verification_code'] = $verification_code;
            $_SESSION['verification_email'] = $email;
            $_SESSION['temp_user_data'] = [
                'username' => $username,
                'password' => $password,
                'phone' => $phone
            ];
            
            try {
                $mail = new PHPMailer(true);
                
                // إعدادات الخادم
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'yjmt46999@gmail.com';
                $mail->Password = 'enfgdisiottvcozi';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';
                
                // المرسل والمستلم
                $mail->setFrom(ADMIN_EMAIL, SITE_NAME);
                $mail->addAddress($email, $username);
                $mail->addReplyTo(ADMIN_EMAIL, SITE_NAME);
                
                // محتوى البريد
                $mail->isHTML(true);
                $mail->Subject = 'رمز التحقق - مطبعة برنتلي';
                $mail->Body = "
                    <html dir='rtl'>
                    <head>
                        <title>رمز التحقق</title>
                    </head>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                            <h2 style='color: #00adef; text-align: center;'>مرحباً بك في مطبعة برنتلي</h2>
                            <p>شكراً لتسجيلك في موقعنا. يرجى استخدام الرمز التالي لإكمال عملية التسجيل:</p>
                            <p style='text-align: center; margin: 30px 0; font-size: 24px; font-weight: bold; color: #c9a959;'>{$verification_code}</p>
                            <p>ينتهي هذا الرمز خلال 10 دقائق.</p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #666; text-align: center;'>هذا بريد إلكتروني تلقائي، يرجى عدم الرد عليه.</p>
                        </div>
                    </body>
                    </html>
                ";
                
                // إرسال البريد
                $mail->send();
                $verification_sent = true;
                $success = 'تم إرسال رمز التحقق إلى بريدك الإلكتروني. يرجى إدخال الرمز لإكمال التسجيل.';
                
            } catch (Exception $e) {
                $errors[] = 'حدث خطأ أثناء إرسال رمز التحقق: ' . $mail->ErrorInfo;
            }
        } else {
            // التحقق من صحة الرمز
            if (isset($_SESSION['verification_code']) && 
                isset($_SESSION['verification_email']) && 
                isset($_SESSION['temp_user_data']) && 
                $_SESSION['verification_code'] == $verification_code && 
                $_SESSION['verification_email'] == $email) {
                
                try {
                    // تشفير كلمة المرور
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // إنشاء الحساب
                    $stmt = $db->prepare("
                        INSERT INTO users (
                            username, email, password, phone, 
                            status, role
                        ) VALUES (
                            ?, ?, ?, ?, 
                            'active', 'customer'
                        )
                    ");
                    
                    $stmt->execute([
                        $username, $email, $hashed_password, $phone
                    ]);
                    
                    // تسجيل الدخول تلقائياً
                    $_SESSION['user_id'] = $db->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['user_role'] = 'customer';
                    
                    // حذف بيانات التحقق المؤقتة
                    unset($_SESSION['verification_code']);
                    unset($_SESSION['verification_email']);
                    unset($_SESSION['temp_user_data']);
                    
                    $success = 'تم إنشاء حسابك بنجاح!';
                    redirect('account.php');
                    
                } catch (PDOException $e) {
                    $errors[] = 'حدث خطأ أثناء إنشاء الحساب: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'رمز التحقق غير صحيح';
            }
        }
    }
}

// استدعاء ملف الرأس
require_once 'includes/header.php';
?>

<!-- رأس الصفحة -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">إنشاء حساب جديد</h1>
    </div>
</section>

<!-- قسم التسجيل -->
<section class="register-section section">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h2><?php echo $success; ?></h2>
                <?php if ($verification_sent): ?>
                    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" class="verification-form">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>">
                        <input type="hidden" name="confirm_password" value="<?php echo htmlspecialchars($confirm_password); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                        
                        <div class="form-group">
                            <label for="verification_code">رمز التحقق <span class="required">*</span></label>
                            <input type="text" name="verification_code" id="verification_code" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-gold">تأكيد التسجيل</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="auth-container">
                <div class="auth-form-wrapper">
                    <h2 class="section-title">إنشاء حساب جديد</h2>
                    
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
                            <label for="username">اسم المستخدم <span class="required">*</span></label>
                            <input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني <span class="required">*</span></label>
                            <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="password">كلمة المرور <span class="required">*</span></label>
                                <input type="password" name="password" id="password" required>
                                <small>يجب أن تكون 6 أحرف على الأقل</small>
                            </div>
                            
                            <div class="form-group half">
                                <label for="confirm_password">تأكيد كلمة المرور <span class="required">*</span></label>
                                <input type="password" name="confirm_password" id="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">رقم الهاتف <span class="required">*</span></label>
                            <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-gold">إرسال رمز التحقق</button>
                        </div>
                    </form>
                    
                    <div class="auth-links">
                        <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
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
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.verification-form {
    max-width: 400px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.verification-form .form-group {
    margin-bottom: 20px;
}

.verification-form input[type="text"] {
    width: 100%;
    padding: 10px;
    font-size: 18px;
    text-align: center;
    letter-spacing: 5px;
}
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
