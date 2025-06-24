<?php
/**
 * صفحة تسجيل الدخول للوحة التحكم
 */

// تعريف ثابت لمنع الوصول المباشر للملفات


// استدعاء ملف الإعدادات
require_once '../includes/config.php';

// توليد دالة توليد رمز CSRF إذا لم تكن موجودة
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من وجود جلسة مسجلة مسبقاً
if (isset($_SESSION['admin_id'])) {
    redirect('index.php');
}

// معالجة نموذج تسجيل الدخول
$error = '';

// توليد رمز CSRF وتخزينه في الجلسة عند عرض النموذج
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['csrf_token'] = generate_token();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'رمز الحماية غير صالح. يرجى إعادة تحميل الصفحة والمحاولة مرة أخرى.';
    } else {
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
                    // التحقق من صلاحيات المسؤول
                    if ($user['role'] === 'admin') {
                        // تحديث وقت آخر تسجيل دخول
                        $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $update_stmt->execute([$user['user_id']]);
                        
                        // تخزين بيانات المستخدم في الجلسة
                        $_SESSION['admin_id'] = $user['user_id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_role'] = $user['role'];
                        
                        // إعادة التوجيه إلى لوحة التحكم
                        redirect('index.php');
                    } else {
                        $error = 'ليس لديك صلاحية الوصول إلى لوحة التحكم';
                    }
                } else {
                    $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة تحكم مطبعة برنتلي</title>
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --color-black: #000000;
            --color-gold: #D4AF37;
            --color-dark-gray: #333333;
            --color-light-gray: #F5F5F5;
            --color-white: #FFFFFF;
            --color-danger: #e74c3c;
            
            --font-heading: 'Cairo', sans-serif;
            --font-body: 'Tajawal', sans-serif;
            
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
            
            --transition-fast: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-body);
            background-color: var(--color-light-gray);
            color: var(--color-dark-gray);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: var(--spacing-lg);
            background-color: var(--color-white);
            border-radius: 8px;
            box-shadow: var(--shadow-md);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .login-logo img {
            max-width: 150px;
            height: auto;
        }
        
        .login-title {
            font-family: var(--font-heading);
            font-weight: 700;
            text-align: center;
            margin-bottom: var(--spacing-lg);
            color: var(--color-black);
        }
        
        .login-form {
            margin-bottom: var(--spacing-md);
        }
        
        .form-group {
            margin-bottom: var(--spacing-md);
        }
        
        label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 600;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--color-light-gray);
            border-radius: 4px;
            font-family: var(--font-body);
            font-size: 1rem;
        }
        
        .btn {
            display: inline-block;
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            background-color: var(--color-black);
            color: var(--color-white);
            border: 2px solid var(--color-gold);
            border-radius: 4px;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition-fast);
        }
        
        .btn:hover {
            background-color: var(--color-gold);
            color: var(--color-black);
        }
        
        .error-message {
            background-color: #fce4e4;
            border-right: 4px solid var(--color-danger);
            padding: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            border-radius: 4px;
            color: var(--color-danger);
        }
        
        .back-link {
            text-align: center;
            margin-top: var(--spacing-md);
        }
        
        .back-link a {
            color: var(--color-dark-gray);
            text-decoration: none;
            transition: var(--transition-fast);
        }
        
        .back-link a:hover {
            color: var(--color-gold);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="../assets/images/logo.png" alt="مطبعة برنتلي">
        </div>
        
        <h1 class="login-title">تسجيل الدخول للوحة التحكم</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="post" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <button type="submit" class="btn">تسجيل الدخول</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">العودة إلى الموقع الرئيسي</a>
        </div>
    </div>
</body>
</html>
