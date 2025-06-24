<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/db.php';

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معرف الطلب في URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: account.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// جلب تفاصيل الطلب مع التحقق من ملكية المستخدم للطلب
$stmt = $conn->prepare("
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

// جلب عناصر الطلب
$stmt_items = $conn->prepare("
    SELECT oi.*, s.name AS service_name, GROUP_CONCAT(oif.file_name) as files
    FROM order_items oi
    JOIN services s ON oi.service_id = s.service_id
    LEFT JOIN order_item_files oif ON oi.item_id = oif.item_id
    WHERE oi.order_id = ?
    GROUP BY oi.item_id
");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// جلب الحقول المخصصة لكل خدمة في الطلب
$service_custom_fields = [];
$service_ids = array_unique(array_column($order_items, 'service_id'));
if (!empty($service_ids)) {
    $in = str_repeat('?,', count($service_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT 
            sf.service_id,
            cf.field_id,
            cf.field_label,
            cf.field_type_id,
            ft.type_key,
            sf.is_required
        FROM service_fields sf
        JOIN custom_fields cf ON sf.field_id = cf.field_id
        LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
        WHERE sf.service_id IN ($in) 
        AND sf.status = 'active' 
        AND cf.status = 'active'
        ORDER BY sf.service_id, sf.order_num, cf.order_num
    ");
    $stmt->execute($service_ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $service_custom_fields[$row['service_id']][] = $row;
    }
}

// فلتر الحقول المخصصة لتجنب التكرار مع الحقول الأساسية
$basic_field_labels = ['نوع الورق', 'المقاس', 'عدد الألوان', 'الملاحظات', 'ملف التصميم'];
foreach ($service_custom_fields as $service_id => &$fields) {
    $filtered_fields = [];
    foreach ($fields as $field) {
        if (!in_array($field['field_label'], $basic_field_labels)) {
            $filtered_fields[] = $field;
        }
    }
    $service_custom_fields[$service_id] = $filtered_fields;
}
?>

<style>
.order-details-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 1rem;
    margin-top: 120px;
}

@media (max-width: 768px) {
    .order-details-container {
        margin: 1rem auto;
        padding: 0.5rem;
        margin-top: 100px;
    }
}

.order-section {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .order-section {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: 10px;
    }
}

.order-section h2 {
    color: #000;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.8rem;
}

@media (max-width: 768px) {
    .order-section h2 {
        font-size: 1.4rem;
        margin-bottom: 1.5rem;
    }
}

.order-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .order-info {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .order-info {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.2rem;
    }
}

.info-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .info-item {
        padding: 0.8rem;
        border-radius: 8px;
    }
}

.info-item h4 {
    color: #00adef;
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .info-item h4 {
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
    }
}

.info-item p {
    margin: 0;
    color: #000;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .info-item p {
        font-size: 0.95rem;
        line-height: 1.4;
    }
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .status-badge {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 15px;
    }
}

.files-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    padding: 0;
    list-style: none;
}

@media (max-width: 768px) {
    .files-list {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
    }
}

@media (max-width: 480px) {
    .files-list {
        grid-template-columns: 1fr;
    }
}

.files-list li {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}

@media (max-width: 768px) {
    .files-list li {
        padding: 0.8rem;
        border-radius: 8px;
    }
}

.files-list a {
    color: #00adef;
    text-decoration: none;
    display: block;
}

.files-list i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .files-list i {
        font-size: 1.5rem;
        margin-bottom: 0.3rem;
    }
}

.back-button {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: #00adef;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .back-button {
        padding: 0.7rem 1.5rem;
        font-size: 0.9rem;
        width: 100%;
        text-align: center;
        margin-bottom: 0.5rem;
    }
}

.back-button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .back-button:hover {
        transform: none;
    }
}

.payment-proof-button {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin: 1rem 0;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .payment-proof-button {
        padding: 0.7rem 1.5rem;
        font-size: 0.9rem;
        width: 100%;
        text-align: center;
        margin: 0.5rem 0;
    }
}

.payment-proof-button:hover {
    background: #c82333;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .payment-proof-button:hover {
        transform: none;
    }
}

.payment-proof-section {
    text-align: center;
    margin: 2rem 0;
    padding: 2rem;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .payment-proof-section {
        margin: 1.5rem 0;
        padding: 1.5rem;
        border-radius: 10px;
    }
}

.payment-proof-section h3 {
    color: #dc3545;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .payment-proof-section h3 {
        font-size: 1.2rem;
        margin-bottom: 0.8rem;
    }
}

.payment-proof-image {
    max-width: 300px;
    max-height: 300px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin: 1rem auto;
    display: block;
}

@media (max-width: 768px) {
    .payment-proof-image {
        max-width: 100%;
        max-height: 250px;
        border-radius: 8px;
        margin: 0.8rem auto;
    }
}

/* تحسينات إضافية للهواتف */
@media (max-width: 768px) {
    /* تحسين المسافات بين العناصر */
    .order-details-container > div {
        margin-bottom: 1rem;
    }
    
    /* تحسين عرض النصوص الطويلة */
    .info-item p {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    /* تحسين أزرار التفاعل */
    .back-button, .payment-proof-button {
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
    }
    
    /* تحسين عرض الملفات */
    .files-list a {
        padding: 0.5rem;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    /* تحسين عرض الصور */
    img {
        max-width: 100%;
        height: auto;
    }
}

/* تحسينات للشاشات الصغيرة جداً */
@media (max-width: 480px) {
    .order-details-container {
        padding: 0.3rem;
        margin-top: 80px;
    }
    
    .order-section {
        padding: 1rem;
    }
    
    .order-section h2 {
        font-size: 1.2rem;
    }
    
    .info-item {
        padding: 0.6rem;
    }
    
    .info-item h4 {
        font-size: 0.8rem;
    }
    
    .info-item p {
        font-size: 0.9rem;
    }
}
</style>

<div class="order-details-container">
    <!-- معلومات الطلب الأساسية -->
    <div class="order-section">
        <h2>تفاصيل الطلب #<?= htmlspecialchars($order['order_number']) ?></h2>
        <div class="order-info">
            <div class="info-item">
                <h4>حالة الطلب</h4>
                <p class="status-badge" style="
                    <?php
                    switch($order['status']) {
                        case 'pending':
                            echo 'background: #fff3cd; color: #856404;';
                            break;
                        case 'processing':
                            echo 'background: #cce5ff; color: #004085;';
                            break;
                        case 'completed':
                            echo 'background: #d4edda; color: #155724;';
                            break;
                        case 'ready':
                            echo 'background: #17a2b8; color: #ffffff;';
                            break;
                        case 'delivered':
                            echo 'background: #28a745; color: #ffffff;';
                            break;
                        case 'cancelled':
                            echo 'background: #f8d7da; color: #721c24;';
                            break;
                        default:
                            echo 'background: #e2e3e5; color: #383d41;';
                    }
                    ?>">
                    <?php
                    switch($order['status']) {
                        case 'pending':
                            echo 'قيد الانتظار';
                            break;
                        case 'processing':
                            echo 'قيد التنفيذ';
                            break;
                        case 'completed':
                            echo 'مكتمل';
                            break;
                        case 'ready':
                            echo 'جاهز';
                            break;
                        case 'delivered':
                            echo 'تم التسليم';
                            break;
                        case 'cancelled':
                            echo 'ملغي';
                            break;
                        case 'new':
                            echo 'في المراجعة';
                            break;
                        default:
                            echo 'في المراجعة';
                    }
                    ?>
                </p>
            </div>
            <div class="info-item">
                <h4>تاريخ الطلب</h4>
                <p><?= date("Y/m/d", strtotime($order['created_at'])) ?></p>
            </div>
            <div class="info-item">
                <h4>إجمالي المبلغ</h4>
                <p><?= number_format($order['total_amount'], 2) ?> جنيه</p>
            </div>
            <div class="info-item">
                <h4>طريقة الدفع</h4>
                <p><?= $order['payment_method'] ? htmlspecialchars($order['payment_method']) : 'لم يتم التحديد' ?></p>
            </div>
        </div>
    </div>

    <!-- قسم إثبات الدفع -->
    <div class="payment-proof-section">
        <h3>إثبات الدفع</h3>
        <?php if (!empty($order['payment_proof'])): ?>
            <p style="color: #28a745; font-weight: bold;">✓ تم إرسال إثبات الدفع</p>
            <?php 
            // استبدال 'payment_' بـ 'designspayment_' في اسم الملف
            $correct_filename = str_replace('payment_', 'designspayment_', $order['payment_proof']);
            ?>
            <img src="uploads/<?= htmlspecialchars($correct_filename) ?>" 
                 alt="إثبات الدفع" 
                 class="payment-proof-image"
                 onclick="window.open(this.src, '_blank')"
                 style="cursor: pointer;">
            <br>
            <a href="uploads/<?= htmlspecialchars($correct_filename) ?>" 
               target="_blank" 
               class="back-button">
                <i class="fas fa-eye"></i>
                عرض إثبات الدفع
            </a>
        <?php else: ?>
            <p style="color: #dc3545; font-weight: bold;">✗ لم يتم إرسال إثبات الدفع</p>
            <a href="upload-payment.php?order_id=<?= $order_id ?>" class="payment-proof-button">
                <i class="fas fa-exclamation-triangle"></i>
                إرسال صورة الدفع
            </a>
        <?php endif; ?>
    </div>

    <!-- تفاصيل المنتج -->
    <div class="order-section">
        <h2>تفاصيل المنتجات</h2>
        <?php foreach ($order_items as $item): ?>
        <div class="order-info">
            <div class="info-item">
                <h4>اسم الخدمة</h4>
                <p><?= htmlspecialchars($item['service_name']) ?></p>
            </div>
            <div class="info-item">
                <h4>الكمية</h4>
                <p><?= htmlspecialchars($item['quantity']) ?></p>
            </div>
            <?php if($item['paper_type']): ?>
            <div class="info-item">
                <h4>نوع الورق</h4>
                <p><?= htmlspecialchars($item['paper_type']) ?></p>
            </div>
            <?php endif; ?>
            <?php if($item['size']): ?>
            <div class="info-item">
                <h4>المقاس</h4>
                <p><?= htmlspecialchars($item['size']) ?></p>
            </div>
            <?php endif; ?>
            <?php if($item['colors']): ?>
            <div class="info-item">
                <h4>الألوان</h4>
                <p><?= htmlspecialchars($item['colors']) ?></p>
            </div>
            <?php endif; ?>
            <?php if($item['notes']): ?>
            <div class="info-item">
                <h4>ملاحظات</h4>
                <p><?= nl2br(htmlspecialchars($item['notes'])) ?></p>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <h4>السعر</h4>
                <p><?= number_format($item['price'], 2) ?> جنيه</p>
            </div>
        </div>
        
        <!-- الملفات المرفقة لهذا العنصر -->
        <?php if(!empty($item['files'])): ?>
        <div style="margin-top: 1rem;">
            <h4>الملفات المرفقة:</h4>
            <ul class="files-list">
                <?php 
                $files = explode(',', $item['files']);
                foreach($files as $file): 
                ?>
                <li>
                    <a href="uploads/designs/<?= htmlspecialchars(trim($file)) ?>" target="_blank">
                        <i class="fas fa-file"></i>
                        <span>عرض الملف</span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- جلب الحقول المخصصة لكل خدمة في الطلب -->
        <?php if (!empty($service_custom_fields[$item['service_id']])): ?>
            <?php foreach ($service_custom_fields[$item['service_id']] as $field): ?>
                <?php 
                $field_name = 'custom_field_' . $field['field_id'];
                if (!empty($item[$field_name])): ?>
                    <div class="info-item">
                        <h4><?php echo htmlspecialchars($field['field_label']); ?></h4>
                        <p><?php echo htmlspecialchars($item[$field_name]); ?></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <hr style="margin: 2rem 0;">
        <?php endforeach; ?>
    </div>

    <!-- معلومات الشحن -->
    <div class="order-section">
        <h2>معلومات <?= $order['delivery_type'] === 'pickup' ? 'الاستلام من المطبعة' : 'التوصيل' ?></h2>
        <div class="order-info">
            <?php if ($order['delivery_type'] === 'pickup'): ?>
            <div class="info-item">
                <h4>الاسم</h4>
                <p><?= htmlspecialchars($order['pickup_name']) ?></p>
            </div>
            <div class="info-item">
                <h4>رقم الهاتف</h4>
                <p><?= htmlspecialchars($order['pickup_phone']) ?></p>
            </div>
            <div class="info-item">
                <h4>عنوان المطبعة</h4>
                <p>دمياط، شارع وزير، بجوار مسجد تقي الدين</p>
            </div>
            <div class="info-item">
                <h4>هاتف المطبعة</h4>
                <p>201002889688+</p>
            </div>
            <div class="info-item">
                <h4>مواعيد العمل</h4>
                <p>من السبت للخميس</p>
            </div>
            <?php else: ?>
            <div class="info-item">
                <h4>الاسم</h4>
                <p><?= htmlspecialchars($order['shipping_name']) ?></p>
            </div>
            <div class="info-item">
                <h4>رقم الهاتف</h4>
                <p><?= htmlspecialchars($order['shipping_phone']) ?></p>
            </div>
            <div class="info-item">
                <h4>البريد الإلكتروني</h4>
                <p><?= htmlspecialchars($order['shipping_email']) ?></p>
            </div>
            <div class="info-item">
                <h4>العنوان</h4>
                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
            </div>
            <div class="info-item">
                <h4>المدينة</h4>
                <p><?= htmlspecialchars($order['shipping_city']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- الملفات المرفقة -->
    <?php if(!empty($order_items)): ?>
    <!-- تم عرض الملفات مع كل عنصر أعلاه -->
    <?php endif; ?>

    <!-- زر العودة -->
    <div style="text-align: center; margin-top: 2rem;">
        <a href="account.php" class="back-button">
            <i class="fas fa-arrow-right ml-2"></i>
            العودة للحساب
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
