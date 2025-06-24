<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// معالجة تحديث المعلومات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $country = trim($_POST['country']);
    $address = trim($_POST['address']);

    // التحقق من صحة البيانات
    if (empty($username) || empty($email)) {
        $message = '<div class="alert alert-danger">يرجى ملء جميع الحقول المطلوبة</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">يرجى إدخال بريد إلكتروني صحيح</div>';
    } else {
        // التحقق من عدم وجود بريد إلكتروني مكرر
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $email_result = $check_email->get_result();

        if ($email_result->num_rows > 0) {
            $message = '<div class="alert alert-danger">البريد الإلكتروني مستخدم بالفعل</div>';
        } else {
            // تحديث البيانات
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, city = ?, country = ?, address = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $username, $email, $phone, $city, $country, $address, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">تم تحديث المعلومات بنجاح</div>';
                $success = true;
            } else {
                $message = '<div class="alert alert-danger">حدث خطأ أثناء تحديث المعلومات</div>';
            }
        }
    }
}

// جلب بيانات المستخدم الحالية
$stmt = $conn->prepare("SELECT username, email, phone, city, country, address FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<style>
.user-edit-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem;
}

.user-edit-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
}

.user-edit-card h2 {
    color: #333;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.8rem;
    position: relative;
    padding-bottom: 1rem;
}

.user-edit-card h2:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 3px;
    background: #00adef;
}

.edit-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    padding: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #00adef;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0, 173, 239, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e1e5e9;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-save {
    background: #00adef;
    color: white;
}

.btn-save:hover {
    background: #0098d1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 173, 239, 0.3);
}

.btn-cancel {
    background: #6c757d;
    color: white;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 1px solid transparent;
}

.alert-success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

@media (max-width: 768px) {
    .user-edit-container {
        padding: 1rem;
    }
    
    .user-edit-card {
        padding: 1.5rem;
    }
    
    .edit-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="user-edit-container">
    <div class="user-edit-card">
        <h2>تعديل الملف الشخصي</h2>
        <?= $message ?>
        <form method="POST" class="edit-form">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>رقم الجوال</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="أدخل رقم الجوال">
            </div>
            <div class="form-group">
                <label>المدينة</label>
                <input type="text" name="city" value="<?= htmlspecialchars($user['city']) ?>" placeholder="أدخل المدينة">
            </div>
            <div class="form-group">
                <label>الدولة</label>
                <input type="text" name="country" value="<?= htmlspecialchars($user['country']) ?>" placeholder="أدخل الدولة">
            </div>
            <div class="form-group">
                <label>العنوان</label>
                <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>" placeholder="أدخل العنوان">
            </div>
            <div class="form-actions">
                <?php if (!$success): ?>
                    <button type="submit" class="btn btn-save">حفظ التغييرات</button>
                    <a href="account.php" class="btn btn-cancel">إلغاء</a>
                <?php else: ?>
                    <a href="account.php" class="btn btn-save">العودة للملف الشخصي</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 