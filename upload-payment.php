<?php
/**
 * صفحة رفع إثبات الدفع للطلبات الموجودة
 * تسمح للمستخدمين برفع صورة إثبات الدفع للطلبات التي لم يتم دفعها بعد
 */

define('PRINTLY', true);

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تفعيل مخزن الإخراج
ob_start();

require_once 'includes/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = null;
$success = null;
$order = null;

// التحقق من وجود معرف الطلب في URL
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: account.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// الاتصال بقاعدة البيانات
$db = db_connect();

// جلب تفاصيل الطلب مع التحقق من ملكية المستخدم للطلب
$stmt = $db->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: account.php");
    exit();
}

// التحقق من أن الطلب في حالة انتظار الدفع
if ($order['payment_status'] !== 'pending') {
    $error = 'هذا الطلب تم الدفع له مسبقاً أو لا يحتاج إلى دفع.';
}

// تحديد مسار حفظ إثبات الدفع
if (!defined('DESIGNS_PATH')) {
    define('DESIGNS_PATH', __DIR__ . '/uploads/designs/');
}

// إنشاء مجلد التصميمات إذا لم يكن موجوداً
if (!file_exists(DESIGNS_PATH)) {
    mkdir(DESIGNS_PATH, 0777, true);
}

// إذا تم إرسال نموذج الدفع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
    $max_size = 15 * 1024 * 1024; // 15 MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_type = $file['type'];
        $file_size = $file['size'];

        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= $max_size) {
                $new_file_name = 'payment_' . $order_id . '_' . uniqid() . '_' . basename($file['name']);
                $destination = DESIGNS_PATH . $new_file_name;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    try {
                        // تحديث حالة الدفع
                        $stmt = $db->prepare("
                            UPDATE orders 
                            SET 
                                payment_status = 'pending',
                                payment_date = NOW(),
                                payment_proof = ?,
                                notes = CONCAT(COALESCE(notes, ''), '\nتم رفع إثبات الدفع')
                            WHERE order_id = ? AND user_id = ?
                        ");
                        
                        $stmt->execute([$new_file_name, $order_id, $user_id]);
                        
                        $success = 'تم رفع إثبات الدفع بنجاح! سيتم مراجعة الدفع من قبل الإدارة قريباً.';
                        
                        // إعادة تحميل بيانات الطلب
                        $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
                        $stmt->execute([$order_id, $user_id]);
                        $order = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                    } catch (PDOException $e) {
                        $error = 'حدث خطأ أثناء تحديث حالة الدفع: ' . $e->getMessage();
                    }
                } else {
                    $error = 'حدث خطأ أثناء رفع الملف. يرجى المحاولة مرة أخرى.';
                }
            } else {
                $error = 'حجم الملف كبير جداً. الحد الأقصى المسموح به هو 15 ميجابايت.';
            }
        } else {
            $error = 'نوع الملف غير مدعوم. الأنواع المدعومة هي: JPG, PNG, GIF, PDF.';
        }
    } else {
        $upload_errors = array(
            UPLOAD_ERR_INI_SIZE => 'حجم الملف يتجاوز الحد المسموح به في إعدادات PHP',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف يتجاوز الحد المسموح به في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد التخزين المؤقت غير موجود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
            UPLOAD_ERR_EXTENSION => 'تم إيقاف رفع الملف بواسطة إضافة PHP'
        );
        $error = isset($upload_errors[$file['error']]) ? $upload_errors[$file['error']] : 'حدث خطأ غير معروف أثناء رفع الملف';
    }
}

require_once 'includes/header.php';
?>

<style>
.upload-payment-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
}

.upload-section {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

.upload-section h2 {
    color: #000;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.8rem;
}

.order-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
}

.info-item {
    text-align: center;
}

.info-item h4 {
    color: #00adef;
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.info-item p {
    margin: 0;
    color: #000;
    font-size: 1rem;
    font-weight: 600;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.payment-method {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid #dee2e6;
}

.method-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.method-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #00adef;
    border-radius: 50%;
    margin-left: 1rem;
}

.method-icon i {
    font-size: 1.25rem;
    color: white;
}

.method-title {
    color: #00adef;
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.phone-display {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
    gap: 0.5rem;
    border: 1px solid #dee2e6;
    justify-content: space-between;
    direction: rtl;
    flex-direction: row;
}

.phone-input {
    border: none;
    background: transparent;
    flex: 1;
    padding: 0.5rem;
    font-size: 1.1rem;
    color: #333;
    font-weight: 600;
    font-family: monospace;
    text-align: right;
}

.copy-btn {
    background: #00adef;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
    white-space: nowrap;
    margin-right: -90px;
    flex-shrink: 0;
}

.copy-btn:hover {
    background: #c4a130;
    transform: translateY(-2px);
}

.instapay-btn {
    display: inline-flex;
    align-items: center;
    background: #00adef;
    color: white;
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    margin-top: 1rem;
    transition: all 0.3s ease;
    font-weight: 600;
    gap: 0.5rem;
    width: 100%;
    justify-content: center;
}

.instapay-btn:hover {
    background: #c4a130;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

.form-group {
    margin-bottom: 2rem;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #333;
    font-size: 1.1rem;
}

.form-control {
    border-radius: 10px;
    border: 2px dashed #00adef;
    padding: 1.25rem;
    background: #f8f9fa;
    font-size: 1.1rem;
    width: 100%;
}

.form-control:focus {
    border-color: #00adef;
    box-shadow: 0 0 0 0.2rem rgba(0, 173, 239, 0.25);
    outline: none;
}

.form-help {
    color: #666;
    margin-top: 0.75rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #00adef;
    border: none;
    padding: 1rem 2.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    text-decoration: none;
    cursor: pointer;
}

.btn-primary:hover {
    background: #c4a130;
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.alert {
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 2rem;
    font-size: 1.1rem;
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

.back-button {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.back-button:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

.existing-proof {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: center;
}

.existing-proof h4 {
    color: #155724;
    margin-bottom: 1rem;
}

.existing-proof img {
    max-width: 300px;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin: 1rem auto;
    display: block;
    cursor: pointer;
}

@media (max-width: 768px) {
    .upload-payment-container {
        padding: 1rem;
        margin: 1rem;
    }
    
    .upload-section {
        padding: 1rem;
    }
    
    .payment-methods {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .order-info {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .phone-display {
        flex-direction: row;
        gap: 0.5rem;
        justify-content: space-between;
    }
    
    .phone-input {
        text-align: right;
        width: auto;
        flex: 1;
    }
    
    .copy-btn {
        width: auto;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="upload-payment-container">
    <!-- معلومات الطلب -->
    <div class="upload-section">
        <h2>رفع إثبات الدفع - الطلب #<?= htmlspecialchars($order['order_number']) ?></h2>
        
        <div class="order-info">
            <div class="info-item">
                <h4>رقم الطلب</h4>
                <p><?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            <div class="info-item">
                <h4>إجمالي المبلغ</h4>
                <p><?= number_format($order['total_amount'], 2) ?> جنيه</p>
            </div>
            <div class="info-item">
                <h4>تاريخ الطلب</h4>
                <p><?= date("Y/m/d", strtotime($order['created_at'])) ?></p>
            </div>
            <div class="info-item">
                <h4>حالة الدفع</h4>
                <p style="color: #dc3545; font-weight: bold;">في انتظار الدفع</p>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- إثبات الدفع الموجود -->
    <?php if (!empty($order['payment_proof'])): ?>
        <div class="existing-proof">
            <h4><i class="fas fa-check-circle me-2"></i>تم رفع إثبات الدفع مسبقاً</h4>
            <?php 
            $correct_filename = str_replace('payment_', 'designspayment_', $order['payment_proof']);
            ?>
            <img src="uploads/<?= htmlspecialchars($correct_filename) ?>" 
                 alt="إثبات الدفع" 
                 onclick="window.open(this.src, '_blank')"
                 title="انقر لعرض الصورة بالحجم الكامل">
            <p>تم رفع إثبات الدفع في: <?= date("Y/m/d H:i", strtotime($order['payment_date'])) ?></p>
        </div>
    <?php endif; ?>

    <!-- طرق الدفع -->
    <div class="upload-section">
        <h3>طرق الدفع المتاحة</h3>
        
        <div class="payment-methods">
            <!-- فودافون كاش -->
            <div class="payment-method">
                <div class="method-header">
                    <div class="method-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4 class="method-title">فودافون كاش</h4>
                </div>
                <div class="method-body">
                    <p>رقم المحفظة:</p>
                    <div class="phone-display">
                        <input type="text" value="01002889688" class="phone-input" readonly>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('01002889688')">
                            <i class="fas fa-copy"></i>
                            نسخ
                        </button>
                    </div>
                </div>
            </div>

            <!-- انستا باي -->
            <div class="payment-method">
                <div class="method-header">
                    <div class="method-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h4 class="method-title">InstaPay</h4>
                </div>
                <div class="method-body">
                    <p>الدفع المباشر عبر الرابط:</p>
                    <a href="https://ipn.eg/S/zeyadkamelali/instapay/7XaLOl" class="instapay-btn" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        الدفع عبر InstaPay
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- نموذج رفع إثبات الدفع -->
    <?php if (empty($order['payment_proof'])): ?>
        <div class="upload-section">
            <h3>رفع إثبات الدفع</h3>
            <form action="upload-payment.php?order_id=<?= $order_id ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="payment_proof" class="form-label">
                        صورة إثبات الدفع <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="file" 
                           name="payment_proof" 
                           id="payment_proof" 
                           class="form-control" 
                           accept=".jpg,.jpeg,.png,.gif,.pdf"
                           required>
                    <div class="form-help">
                        <i class="fas fa-info-circle"></i>
                        يرجى رفع صورة لإيصال التحويل (JPG, PNG, PDF). الحد الأقصى: 15 ميجابايت
                    </div>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-upload"></i>
                        رفع إثبات الدفع
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- زر العودة -->
    <div style="text-align: center;">
        <a href="order_details.php?id=<?= $order_id ?>" class="back-button">
            <i class="fas fa-arrow-right"></i>
            العودة لتفاصيل الطلب
        </a>
        <a href="account.php" class="back-button" style="margin-right: 1rem;">
            <i class="fas fa-user"></i>
            العودة للحساب
        </a>
    </div>
</div>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            alert('تم نسخ الرقم بنجاح!');
        }).catch(err => {
            console.error('فشل في نسخ النص: ', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('تم نسخ الرقم بنجاح!');
        } else {
            alert('فشل في نسخ الرقم');
        }
    } catch (err) {
        console.error('فشل في نسخ النص: ', err);
        alert('فشل في نسخ الرقم');
    }
    
    document.body.removeChild(textArea);
}

// إضافة التحقق من حجم الملف
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const fileInput = document.getElementById('payment_proof');
    
    if (form && fileInput) {
        const maxSize = 15 * 1024 * 1024; // 15 MB

        form.addEventListener('submit', function(e) {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('حجم الملف كبير جداً. الحد الأقصى المسموح به هو 15 ميجابايت');
                    fileInput.value = ''; // مسح الملف المحدد
                }
            }
        });

        // التحقق عند اختيار الملف
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > maxSize) {
                    alert('حجم الملف كبير جداً. الحد الأقصى المسموح به هو 15 ميجابايت');
                    this.value = ''; // مسح الملف المحدد
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 