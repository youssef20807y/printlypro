<?php
/**
 * صفحة تفعيل حساب المستخدم
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الإعدادات
require_once 'includes/config.php';
$db = db_connect();

$success = false;
$error = '';

// التحقق من وجود رمز التفعيل
if (isset($_GET['code'])) {
    $activation_code = clean_input($_GET['code']);
    error_log("تم استلام رمز التفعيل: " . $activation_code);
    
    try {
        // التحقق من صلاحية رمز التفعيل
        $stmt = $db->prepare("
            SELECT user_id, username, email, activation_code 
            FROM users 
            WHERE activation_code = ? 
            AND status = 'inactive'
        ");
        $stmt->execute([$activation_code]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("تم العثور على المستخدم: " . $user['email'] . " برمز التفعيل: " . $user['activation_code']);
            
            // تفعيل الحساب
            $update_stmt = $db->prepare("
                UPDATE users 
                SET status = 'active',
                    activation_code = NULL
                WHERE user_id = ?
            ");
            $update_stmt->execute([$user['user_id']]);
            
            if ($update_stmt->rowCount() > 0) {
                error_log("تم تفعيل الحساب بنجاح للمستخدم: " . $user['email']);
                $success = true;
                
                // تسجيل الدخول تلقائياً
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = 'customer';
            } else {
                error_log("فشل تحديث حالة الحساب للمستخدم: " . $user['email']);
                $error = 'حدث خطأ أثناء تفعيل الحساب';
            }
        } else {
            error_log("لم يتم العثور على مستخدم برمز التفعيل: " . $activation_code);
            $error = 'رمز التفعيل غير صالح أو تم استخدامه مسبقاً';
        }
    } catch (PDOException $e) {
        error_log("خطأ في قاعدة البيانات أثناء التفعيل: " . $e->getMessage());
        $error = 'حدث خطأ أثناء تفعيل الحساب: ' . $e->getMessage();
    }
} else {
    error_log("لم يتم استلام رمز التفعيل في الطلب");
    $error = 'رمز التفعيل غير موجود';
}

// استدعاء ملف الرأس
require_once 'includes/header.php';
?>

<!-- رأس الصفحة -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">تفعيل الحساب</h1>
    </div>
</section>

<!-- قسم تفعيل الحساب -->
<section class="activation-section section">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>تم تفعيل حسابك بنجاح!</h2>
                <p>مرحباً بك في مطبعة برنتلي. تم تسجيل دخولك تلقائياً ويمكنك الآن استخدام جميع ميزات الموقع.</p>
                <div class="success-actions">
                    <a href="account.php" class="btn btn-gold">الذهاب إلى حسابي</a>
                    <a href="services.php" class="btn btn-outline">استعراض الخدمات</a>
                </div>
            </div>
        <?php else: ?>
            <div class="error-container">
                <h2>خطأ في التفعيل</h2>
                <p><?php echo $error; ?></p>
                <div class="error-actions">
                    <a href="login.php" class="btn btn-gold">تسجيل الدخول</a>
                    <a href="register.php" class="btn btn-outline">إنشاء حساب جديد</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.activation-section {
    padding: var(--spacing-xl) 0;
}

.success-container,
.error-container {
    text-align: center;
    padding: var(--spacing-xl) 0;
}

.success-icon {
    font-size: 5rem;
    color: #2ecc71;
    margin-bottom: var(--spacing-md);
}

.error-container h2 {
    color: #e74c3c;
    margin-bottom: var(--spacing-md);
}

.success-actions,
.error-actions {
    margin-top: var(--spacing-lg);
    display: flex;
    justify-content: center;
    gap: var(--spacing-md);
}

@media (max-width: 768px) {
    .success-actions,
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .success-actions .btn,
    .error-actions .btn {
        width: 100%;
        margin-bottom: var(--spacing-sm);
    }
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?> 