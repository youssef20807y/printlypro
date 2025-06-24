<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/points_functions.php';

// الاتصال بقاعدة البيانات
$db = db_connect();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_points = get_user_points($_SESSION['user_id']);
$purchase_options = get_points_purchase_options();

// معالجة شراء النقاط
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_option = (int)$_POST['points_option'];
    $payment_method = $_POST['payment_method'];
    
    // البحث عن الحزمة المختارة
    $selected_package = null;
    foreach ($purchase_options as $option) {
        if ($option['points'] == $selected_option) {
            $selected_package = $option;
            break;
        }
    }
    
    if ($selected_package) {
        $total_points = $selected_package['points'] + $selected_package['bonus'];
        $purchase_id = create_points_purchase($_SESSION['user_id'], $total_points, 
                                            $selected_package['price'], $payment_method);
        
        if ($purchase_id) {
            // إعادة التوجيه إلى صفحة الدفع
            header("Location: payment.php?type=points&purchase_id=$purchase_id");
            exit();
        }
    }
}

define('PRINTLY', true);
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شراء النقاط - مطبعة برنتلي</title>
    
    <style>
        :root {
            --primary-color: #00adef;
            --primary-hover-color: #00adef;
            --secondary-color: #343a40;
            --light-gray-color: #f8f9fa;
            --text-color: #333;
            --border-color: #dee2e6;
            --border-radius: 0.75rem;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f9f9f9;
            margin-top: 120px;
            font-family: 'Cairo', sans-serif;
        }

        .points-purchase-section {
            padding: 2rem 0;
            min-height: calc(100vh - 120px);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            background-color: #fff;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .current-points {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .current-points::before {
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

        .points-display {
            font-size: 3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .points-label {
            color: #666;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .points-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .points-option {
            position: relative;
        }

        .points-option input[type="radio"] {
            display: none;
        }

        .points-option label {
            display: block;
            padding: 2rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            text-align: center;
        }

        .points-option input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #f8f9ff, #e3f2fd);
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 173, 239, 0.15);
        }

        .option-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .option-header h5 {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .bonus-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .option-price {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .option-total {
            color: #666;
            font-size: 1rem;
        }

        .payment-method {
            margin-bottom: 2rem;
        }

        .payment-method h4 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }

        .purchase-summary {
            background: var(--light-gray-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .purchase-summary h4 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
            border-top: 2px solid var(--primary-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .btn-purchase {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover-color));
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 173, 239, 0.3);
        }

        .points-info-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            position: sticky;
            top: 100px;
        }

        .points-info-card h4 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .points-info-card ul {
            padding-left: 1.5rem;
        }

        .points-info-card li {
            margin-bottom: 0.75rem;
            color: #666;
        }

        @media (max-width: 768px) {
            body {
                margin-top: 80px;
            }

            .points-purchase-section {
                padding: 1rem 0;
            }

            .points-display {
                font-size: 2rem;
            }

            .points-options {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .points-option label {
                padding: 1.5rem;
            }

            .option-header h5 {
                font-size: 1.2rem;
            }

            .option-price {
                font-size: 1.5rem;
            }

            .points-info-card {
                position: static;
                margin-top: 2rem;
            }
        }
    </style>
</head>

<body>
    <section class="points-purchase-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-star me-2"></i>
                                شراء النقاط
                            </h2>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">اشترِ نقاطاً واحصل على خصومات على طلباتك المستقبلية!</p>
                            
                            <div class="current-points">
                                <h4>رصيدك الحالي</h4>
                                <div class="points-display">
                                    <?= number_format($user_points['balance']) ?> نقطة
                                </div>
                                <div class="points-label">متاح للاستخدام</div>
                            </div>
                            
                            <form method="POST" class="points-purchase-form">
                                <div class="points-options">
                                    <?php foreach ($purchase_options as $option): ?>
                                    <div class="points-option">
                                        <input type="radio" name="points_option" value="<?= $option['points'] ?>" 
                                               id="option_<?= $option['points'] ?>" required>
                                        <label for="option_<?= $option['points'] ?>">
                                            <div class="option-header">
                                                <h5><?= number_format($option['points']) ?> نقطة</h5>
                                                <?php if ($option['bonus'] > 0): ?>
                                                <span class="bonus-badge">+<?= number_format($option['bonus']) ?> مكافأة</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="option-price">
                                                <?= number_format($option['price'], 2) ?> جنيه
                                            </div>
                                            <div class="option-total">
                                                المجموع: <?= number_format($option['points'] + $option['bonus']) ?> نقطة
                                            </div>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="payment-method">
                                    <h4>طريقة الدفع</h4>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="">اختر طريقة الدفع</option>
                                        <option value="cash">الدفع عند الاستلام</option>
                                        <option value="bank">تحويل بنكي</option>
                                        <option value="online">دفع إلكتروني</option>
                                    </select>
                                </div>
                                
                                <div class="purchase-summary">
                                    <h4>ملخص الشراء</h4>
                                    <div id="summary-content">
                                        <p class="text-muted">اختر حزمة نقاط لرؤية الملخص</p>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-purchase">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    شراء النقاط
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="points-info-card">
                        <h4>كيف تعمل النقاط؟</h4>
                        <ul>
                            <li>1 نقطة = 0.01 جنيه خصم</li>
                            <li>احصل على 5 نقاط لكل 100 جنيه تنفقها</li>
                            <li>خصم أقصى 10% من قيمة الطلب</li>
                            <li>النقاط لا تنتهي صلاحيتها</li>
                        </ul>
                        
                        <h4>مزايا النقاط</h4>
                        <ul>
                            <li>وفر المال على كل طلب</li>
                            <li>نقاط مكافأة على المشتريات الكبيرة</li>
                            <li>مزايا حصرية للأعضاء</li>
                            <li>تتبع مدخراتك</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const options = document.querySelectorAll('input[name="points_option"]');
            const summary = document.getElementById('summary-content');
            
            function updateSummary() {
                const selectedOption = document.querySelector('input[name="points_option"]:checked');
                
                if (selectedOption) {
                    const points = parseInt(selectedOption.value);
                    const price = parseFloat(selectedOption.closest('.points-option').querySelector('.option-price').textContent);
                    const bonus = selectedOption.closest('.points-option').querySelector('.bonus-badge');
                    const bonusPoints = bonus ? parseInt(bonus.textContent.replace('+', '').replace(' مكافأة', '')) : 0;
                    const totalPoints = points + bonusPoints;
                    
                    summary.innerHTML = `
                        <div class="summary-row">
                            <span>حزمة النقاط:</span>
                            <span>${points.toLocaleString()} نقطة</span>
                        </div>
                        ${bonusPoints > 0 ? `
                        <div class="summary-row">
                            <span>نقاط المكافأة:</span>
                            <span>+${bonusPoints.toLocaleString()} نقطة</span>
                        </div>
                        ` : ''}
                        <div class="summary-row">
                            <span>إجمالي النقاط:</span>
                            <span>${totalPoints.toLocaleString()} نقطة</span>
                        </div>
                        <div class="summary-row">
                            <span>السعر:</span>
                            <span>${price.toFixed(2)} جنيه</span>
                        </div>
                        <div class="summary-row total">
                            <span>القيمة:</span>
                            <span>${(totalPoints * 0.01).toFixed(2)} جنيه في الخصومات</span>
                        </div>
                    `;
                } else {
                    summary.innerHTML = '<p class="text-muted">اختر حزمة نقاط لرؤية الملخص</p>';
                }
            }
            
            options.forEach(option => {
                option.addEventListener('change', updateSummary);
            });
        });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?> 