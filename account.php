<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/db.php'; // تأكد من وجود هذا الملف لاتصال قاعدة البيانات
require_once 'includes/points_functions.php'; // إضافة دوال النقاط

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم من قاعدة البيانات
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone, city, country, address, role, status, registration_date, points_balance, total_points_earned, total_points_spent, unverified_points_balance FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// جلب طلبات المستخدم
$orders_stmt = $conn->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب معاملات النقاط
$points_transactions = get_user_points_transactions($user_id, 10);
?>

<style>
/* إضافة مساحة للـ header الثابت */
body {
    padding-top: 120px !important;
}

/* التنسيق الأساسي للكمبيوتر */
.auth-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 2rem;
    margin-top: 50px; /* تقليل المساحة لأننا أضفنا padding للـ body */
    padding-top: 3rem; /* إضافة padding إضافي من أعلى */
}

.auth-info {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
}

.auth-info h2 {
    color:rgb(0, 0, 0);
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.8rem;
}

.benefits-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    padding: 0;
    list-style: none;
}

.benefits-list li {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.2rem;
    transition: transform 0.3s ease;
    display: flex;
    align-items: start;
}

.benefits-list li:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.benefits-list i {
    font-size: 1.5rem;
    color: #00adef;
    margin-left: 1rem;
    margin-top: 0.5rem;
}

.benefits-list div {
    flex: 1;
}

.benefits-list h4 {
    color: #00adef;
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.benefits-list p {
    color: #000000;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 500;
}

/* تنسيق قسم النقاط */
.points-section {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    margin-top: 0.5rem; /* تقليل المسافة من أعلى */
    text-align: center;
    position: relative;
    overflow: hidden;
}

.points-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.points-balance {
    font-size: 3rem;
    font-weight: bold;
    color: #333;
    margin: 1rem 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.points-label {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 1rem;
}

.points-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.points-btn {
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-block;
    text-align: center;
    min-width: 120px;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.points-btn.primary {
    background: #00adef;
    color: white;
    box-shadow: 0 4px 15px rgba(0, 173, 239, 0.3);
}

.points-btn.secondary {
    background: #fff;
    color: #00adef;
    border: 2px solid #00adef;
    box-shadow: 0 4px 15px rgba(0, 173, 239, 0.1);
}

.points-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.points-btn.primary:hover {
    background: #0098d4;
    box-shadow: 0 8px 25px rgba(0, 173, 239, 0.4);
}

.points-btn.secondary:hover {
    background: #00adef;
    color: white;
    box-shadow: 0 8px 25px rgba(0, 173, 239, 0.3);
}

.points-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.points-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 173, 239, 0.3);
}

.points-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.points-stat {
    background: rgba(255,255,255,0.8);
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}

.points-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 0.5rem;
}

.points-stat-label {
    font-size: 0.9rem;
    color: #666;
}

/* تنسيق معاملات النقاط */
.points-transactions {
    background: #fff;
    border-radius: 15px;
    padding: 2rem;
    margin-top: 2rem;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-info {
    flex: 1;
}

.transaction-amount {
    font-weight: bold;
    font-size: 1.1rem;
}

.transaction-amount.positive {
    color: #28a745;
}

.transaction-amount.negative {
    color: #dc3545;
}

.transaction-date {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
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
}

.form-actions .btn:first-child {
    background: #00adef;
    color: white;
}

.form-actions .btn:last-child {
    background: #fff;
    border: 2px solid #00adef;
    color: #00adef;
}

.form-actions .btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

/* تنسيق الهواتف المحمولة */
@media only screen and (max-width: 768px) {
    .auth-container {
        padding: 10px;
        margin: 10px auto;
    }

    .auth-info {
        padding: 15px;
        border-radius: 10px;
    }

    .auth-info h2 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .benefits-list {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .benefits-list li {
        padding: 15px;
    }

    .benefits-list i {
        font-size: 1.2rem;
        margin-left: 10px;
    }

    .benefits-list h4 {
        font-size: 0.85rem;
    }

    .benefits-list p {
        font-size: 1rem;
    }

    .points-section {
        padding: 1.5rem;
    }

    .points-balance {
        font-size: 2.5rem;
    }

    .points-actions {
        flex-direction: column;
        align-items: center;
    }

    .points-btn {
        width: 100%;
        max-width: 200px;
    }

    .points-stats {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
        gap: 10px;
    }

    .form-actions .btn {
        width: 100%;
        text-align: center;
        padding: 12px;
    }

    /* تنسيق جدول الطلبات للهواتف */
    .orders-list {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 -15px;
        padding: 0 15px;
    }

    .orders-list table {
        min-width: 600px;
    }

    .orders-list th,
    .orders-list td {
        padding: 10px;
        font-size: 0.9rem;
    }

    /* تثبيت العمود الأول والأخير */
    .orders-list th:first-child,
    .orders-list td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 1;
    }

    .orders-list td:last-child {
        position: sticky;
        right: 0;
        background: #fff;
        z-index: 1;
    }
}

/* تنسيق الهواتف الصغيرة */
@media only screen and (max-width: 480px) {
    .auth-container {
        padding: 5px;
    }

    .auth-info {
        padding: 10px;
    }

    .auth-info h2 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }

    .benefits-list li {
        padding: 10px;
    }

    .benefits-list i {
        font-size: 1rem;
        margin-left: 8px;
    }

    .benefits-list h4 {
        font-size: 0.8rem;
    }

    .benefits-list p {
        font-size: 0.9rem;
    }

    .points-section {
        padding: 1rem;
    }

    .points-balance {
        font-size: 2rem;
    }

    .form-actions .btn {
        padding: 10px;
        font-size: 0.9rem;
    }

    /* تنسيق حالة الطلب */
    .orders-list td span {
        padding: 3px 8px;
        font-size: 0.8rem;
    }
}

/* تحسينات للشاشات الكبيرة */
@media screen and (min-width: 1200px) {
    .auth-container {
        max-width: 1100px;
    }

    .benefits-list {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>

<div class="auth-container">
    <div class="auth-info">
        <h2>معلومات الحساب</h2>
        <ul class="benefits-list">
            <li>
                <i class="fas fa-user"></i>
                <div>
                    <h4>اسم المستخدم</h4>
                    <p><?= htmlspecialchars($user['username']) ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>البريد الإلكتروني</h4>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-phone"></i>
                <div>
                    <h4>رقم الجوال</h4>
                    <p><?= htmlspecialchars($user['phone']) ?: 'غير مضاف' ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>العنوان</h4>
                    <p><?= htmlspecialchars($user['address']) ?: 'غير مضاف' ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-globe"></i>
                <div>
                    <h4>المدينة / الدولة</h4>
                    <p><?= htmlspecialchars($user['city']) ?: 'غير محددة' ?> / <?= htmlspecialchars($user['country']) ?: 'غير محددة' ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-user-shield"></i>
                <div>
                    <h4>الصلاحية</h4>
                    <p><?= ($user['role'] === 'admin') ? 'مدير' : 'عميل' ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-calendar-check"></i>
                <div>
                    <h4>تاريخ التسجيل</h4>
                    <p><?= date("Y-m-d", strtotime($user['registration_date'])) ?></p>
                </div>
            </li>
            <li>
                <i class="fas fa-toggle-on"></i>
                <div>
                    <h4>الحالة</h4>
                    <p><?= ($user['status'] === 'active') ? 'نشط' : 'غير نشط' ?></p>
                </div>
            </li>
        </ul>
        <div class="form-actions" style="text-align:center;">
            <a href="edit_account.php" class="btn">تعديل المعلومات</a>
            <a href="logout.php" class="btn" style="color:red;">تسجيل الخروج</a>
        </div>
    </div>

    <!-- قسم الطلبات -->
    <div class="auth-info" style="margin-top: 0.5rem;">
        <h2>طلباتي</h2>
        <?php if (!empty($orders)): ?>
            <div class="orders-list">
                <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; background: #f8f9fa; border-bottom: 2px solid #00adef; text-align: right;">رقم الطلب</th>
                            <th style="padding: 1rem; background: #f8f9fa; border-bottom: 2px solid #00adef; text-align: right;">تاريخ الطلب</th>
                            <th style="padding: 1rem; background: #f8f9fa; border-bottom: 2px solid #00adef; text-align: right;">المجموع</th>
                            <th style="padding: 1rem; background: #f8f9fa; border-bottom: 2px solid #00adef; text-align: right;">النقاط</th>
                            <th style="padding: 1rem; background: #f8f9fa; border-bottom: 2px solid #00adef; text-align: right;">حالة الطلب</th>
                            <th style="padding: 1rem; background: #f8f9fa; border-bottom: 2px solid #00adef; text-align: right;">تفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid #eee;">#<?= htmlspecialchars($order['order_number']) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid #eee;"><?= date("Y/m/d", strtotime($order['created_at'])) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid #eee;"><?= number_format($order['total_amount'], 2) ?> جنيه</td>
                                <td style="padding: 1rem; border-bottom: 1px solid #eee;">
                                    <?php if ($order['points_earned'] > 0): ?>
                                        <span style="color: #28a745; font-weight: bold;">+<?= number_format($order['points_earned']) ?> نقطة</span>
                                        <br><small style="color: #666;">مكتسبة</small>
                                    <?php elseif ($order['points_used'] > 0): ?>
                                        <span style="color: #dc3545; font-weight: bold;">-<?= number_format($order['points_used']) ?> نقطة</span>
                                        <br><small style="color: #666;">مستخدمة</small>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid #eee;">
                                    <span style="padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.9rem; 
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
                                                break;
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
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid #eee;">
                                    <a href="order_details.php?id=<?= $order['order_id'] ?>" 
                                       style="color: #00adef; text-decoration: none;">
                                        <i class="fas fa-eye"></i>
                                        عرض التفاصيل
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin: 2rem 0;">لا توجد طلبات حتى الآن</p>
        <?php endif; ?>
    </div>

    <!-- قسم النقاط -->
    <div class="points-section">
        <h2><i class="fas fa-star"></i> نظام النقاط</h2>
        <div class="points-label">رصيد النقاط الحالي</div>
        <div class="points-balance"><?= number_format($user["points_balance"]) ?></div>
        <div class="points-label" style="color: orange;">نقاط غير مؤكدة: <?= number_format($user["unverified_points_balance"]) ?></div>
        
        <div class="points-stats">
            <div class="points-stat">
                <div class="points-stat-value"><?= number_format($user['total_points_earned']) ?></div>
                <div class="points-stat-label">إجمالي النقاط المكتسبة</div>
            </div>
            <div class="points-stat">
                <div class="points-stat-value"><?= number_format($user['total_points_spent']) ?></div>
                <div class="points-stat-label">إجمالي النقاط المستهلكة</div>
            </div>
        </div>
    </div>

    <!-- معاملات النقاط -->
    <?php if (!empty($points_transactions)): ?>
        <div class="points-transactions">
            <h3><i class="fas fa-history"></i> آخر معاملات النقاط</h3>
            <?php foreach ($points_transactions as $transaction): ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-amount <?= $transaction['points_amount'] > 0 ? 'positive' : 'negative' ?>">
                            <?= $transaction['points_amount'] > 0 ? '+' : '' ?><?= number_format($transaction['points_amount']) ?> نقطة
                        </div>
                        <div class="transaction-date">
                            <?= date('Y/m/d H:i', strtotime($transaction['created_at'])) ?>
                            <?php if (!empty($transaction['order_number'])): ?>
                                - طلب #<?= $transaction['order_number'] ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($transaction['description'])): ?>
                            <div class="transaction-description" style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                                <?= htmlspecialchars($transaction['description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="transaction-type">
                        <?php
                        switch($transaction['transaction_type']) {
                            case 'earn':
                                if ($transaction['status'] === 'pending') {
                                    echo '<span style="color: #ffc107;"><i class="fas fa-clock"></i> في الانتظار</span>';
                                } else {
                                    echo '<span style="color: #28a745;"><i class="fas fa-plus-circle"></i> اكتساب</span>';
                                }
                                break;
                            case 'spend':
                                echo '<span style="color: #dc3545;"><i class="fas fa-minus-circle"></i> استهلاك</span>';
                                break;
                            case 'purchase':
                                echo '<span style="color: #007bff;"><i class="fas fa-shopping-cart"></i> شراء</span>';
                                break;
                            case 'redemption':
                                echo '<span style="color: #6f42c1;"><i class="fas fa-exchange-alt"></i> استبدال</span>';
                                break;
                            default:
                                echo '<span style="color: #6c757d;"><i class="fas fa-exchange-alt"></i> معاملة</span>';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- رابط لعرض جميع المعاملات -->
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="points-transactions.php" class="btn" style="background: #00adef; color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none;">
                    <i class="fas fa-list"></i> عرض جميع المعاملات
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحسين تجربة المستخدم مع أزرار النقاط
    const pointsButtons = document.querySelectorAll('.points-btn');
    
    pointsButtons.forEach(button => {
        // إضافة تأثير النقر
        button.addEventListener('click', function(e) {
            // إضافة تأثير النقر
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
        
        // إضافة تأثير التحميل
        button.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحميل...';
            this.style.pointerEvents = 'none';
            
            // إعادة النص الأصلي بعد ثانيتين (في حالة عدم التوجيه)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            }, 2000);
        });
        
        // تحسين إمكانية الوصول
        button.setAttribute('tabindex', '0');
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // إضافة رسائل تأكيد
    console.log('تم تحميل صفحة الحساب بنجاح');
    console.log('أزرار النقاط جاهزة للاستخدام');
});
</script>

<?php require_once 'includes/footer.php'; ?>
