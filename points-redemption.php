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
$min_redemption = get_min_points_redemption();

// معالجة استبدال النقاط
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $points_to_redeem = (int)$_POST['points_to_redeem'];
    $redemption_type = $_POST['redemption_type'];
    
    // التحقق من صحة البيانات
    if ($points_to_redeem >= $min_redemption && $points_to_redeem <= $user_points['balance']) {
        $redemption_id = create_points_redemption($_SESSION['user_id'], $points_to_redeem, $redemption_type);
        
        if ($redemption_id) {
            // خصم النقاط من رصيد المستخدم
            if (deduct_user_points($_SESSION['user_id'], $points_to_redeem, 'redemption', null, 'استبدال نقاط')) {
                $_SESSION['success_message'] = "تم استبدال النقاط بنجاح! سيتم مراجعة طلبك قريباً.";
                header('Location: account.php');
                exit();
            }
        }
    } else {
        $_SESSION['error_message'] = "عدد النقاط غير صحيح أو غير كافي.";
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
    <title>استبدال النقاط - مطبعة برنتلي</title>
    
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

        .points-redemption-section {
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
            background: linear-gradient(135deg, #28a745, #20c997);
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
            color: white;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .points-label {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .redemption-form {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 173, 239, 0.25);
            outline: none;
        }

        .redemption-summary {
            background: var(--light-gray-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .redemption-summary h4 {
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

        .btn-redeem {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-redeem:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .btn-redeem:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            body {
                margin-top: 80px;
            }

            .points-redemption-section {
                padding: 1rem 0;
            }

            .points-display {
                font-size: 2rem;
            }

            .redemption-form {
                padding: 1.5rem;
            }

            .points-info-card {
                position: static;
                margin-top: 2rem;
            }
        }
    </style>
</head>

<body>
    <section class="points-redemption-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-exchange-alt me-2"></i>
                                استبدال النقاط
                            </h2>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">استبدل نقاطك بخصومات على طلباتك المستقبلية!</p>
                            
                            <div class="current-points">
                                <h4>رصيدك الحالي</h4>
                                <div class="points-display">
                                    <?= number_format($user_points['balance']) ?> نقطة
                                </div>
                                <div class="points-label">متاح للاستبدال</div>
                            </div>
                            
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="error-message">
                                    <?= $_SESSION['error_message'] ?>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>
                            
                            <form method="POST" class="redemption-form">
                                <div class="form-group">
                                    <label class="form-label">عدد النقاط المراد استبدالها</label>
                                    <input type="number" name="points_to_redeem" class="form-control" 
                                           min="<?= $min_redemption ?>" max="<?= $user_points['balance'] ?>" 
                                           value="<?= $min_redemption ?>" required>
                                    <small class="text-muted">
                                        الحد الأدنى: <?= number_format($min_redemption) ?> نقطة
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">نوع الاستبدال</label>
                                    <select name="redemption_type" class="form-control" required>
                                        <option value="discount">خصم على الطلبات المستقبلية</option>
                                        <option value="cash">استرداد نقدي</option>
                                    </select>
                                </div>
                                
                                <div class="redemption-summary">
                                    <h4>ملخص الاستبدال</h4>
                                    <div id="summary-content">
                                        <p class="text-muted">أدخل عدد النقاط لرؤية الملخص</p>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-redeem" id="redeem-btn" 
                                        <?= $user_points['balance'] < $min_redemption ? 'disabled' : '' ?>>
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    استبدال النقاط
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="points-info-card">
                        <h4>كيف يعمل الاستبدال؟</h4>
                        <ul>
                            <li>1 نقطة = 0.01 جنيه خصم</li>
                            <li>الحد الأدنى للاستبدال: <?= number_format($min_redemption) ?> نقطة</li>
                            <li>يمكن استبدال النقاط بخصومات على الطلبات</li>
                            <li>أو استردادها نقدياً (يخضع للمراجعة)</li>
                        </ul>
                        
                        <h4>شروط الاستبدال</h4>
                        <ul>
                            <li>يجب أن يكون لديك رصيد كافي</li>
                            <li>الحد الأدنى: <?= number_format($min_redemption) ?> نقطة</li>
                            <li>الحد الأقصى: رصيدك الحالي</li>
                            <li>يتم مراجعة طلبات الاسترداد النقدي</li>
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
            const pointsInput = document.querySelector('input[name="points_to_redeem"]');
            const summary = document.getElementById('summary-content');
            const redeemBtn = document.getElementById('redeem-btn');
            const maxPoints = <?= $user_points['balance'] ?>;
            const minPoints = <?= $min_redemption ?>;
            
            function updateSummary() {
                const points = parseInt(pointsInput.value) || 0;
                
                if (points >= minPoints && points <= maxPoints) {
                    const value = (points * 0.01).toFixed(2);
                    
                    summary.innerHTML = `
                        <div class="summary-row">
                            <span>النقاط المراد استبدالها:</span>
                            <span>${points.toLocaleString()} نقطة</span>
                        </div>
                        <div class="summary-row">
                            <span>قيمة الاستبدال:</span>
                            <span>${value} جنيه</span>
                        </div>
                        <div class="summary-row total">
                            <span>الرصيد المتبقي:</span>
                            <span>${(maxPoints - points).toLocaleString()} نقطة</span>
                        </div>
                    `;
                    
                    redeemBtn.disabled = false;
                } else {
                    summary.innerHTML = '<p class="text-muted">أدخل عدد نقاط صحيح لرؤية الملخص</p>';
                    redeemBtn.disabled = true;
                }
            }
            
            pointsInput.addEventListener('input', updateSummary);
            updateSummary();
        });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?> 