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

// معالجة تحديث المعلومات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $address = $_POST['address'];

    // التحقق من عدم وجود بريد إلكتروني مكرر
    $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $email_result = $check_email->get_result();    if ($email_result->num_rows > 0) {
        $message = '<div class="alert alert-danger">البريد الإلكتروني مستخدم بالفعل</div>';
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, city = ?, country = ?, address = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $username, $email, $phone, $city, $country, $address, $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم تحديث المعلومات بنجاح</div>';
        } else {
            $message = '<div class="alert alert-danger">حدث خطأ أثناء تحديث المعلومات</div>';
        }    }
}  // إغلاق if ($_SERVER['REQUEST_METHOD'] === 'POST')

// جلب بيانات المستخدم الحالية
$stmt = $conn->prepare("SELECT username, email, phone, city, country, address FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<style>
.auth-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 2rem;
}

.auth-info {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
}

.auth-info h2 {
    color: rgb(0, 0, 0);
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.8rem;
}

.edit-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    padding: 0;
}

.form-group {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.2rem;
    transition: transform 0.3s ease;
}

.form-group:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.form-group label {
    display: block;
    color: #00adef;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-group input {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    color: #000000;
}

.form-group input:focus {
    outline: none;
    border-color: #00adef;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    text-align: center;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.form-actions .btn {
    padding: 0.8rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.form-actions .btn-save {
    background: #00adef;
    color: white;
}

.form-actions .btn-cancel {
    background: #fff;
    border: 2px solid #00adef;
    color: #00adef;
}

.form-actions .btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.readonly-field {
    background-color: #e9ecef;
    cursor: not-allowed;
}
</style>

<div class="auth-container">
    <div class="auth-info">
        <h2>تعديل معلومات الحساب</h2>
        <?= $message ?>
        <form method="POST" class="edit-form">            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required readonly class="readonly-field">
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
              <div class="form-actions" style="grid-column: 1 / -1;">
                <?php if (!$message): ?>
                    <button type="submit" class="btn btn-save">حفظ التغييرات</button>
                    <a href="account.php" class="btn btn-cancel">إلغاء</a>
                <?php else: ?>
                    <a href="account.php" class="btn btn-save">رجوع</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
