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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار أزرار النقاط</title>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .test-title {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .points-info {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .points-balance {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        
        .buttons-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 30px 0;
        }
        
        .test-btn {
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-block;
            text-align: center;
            min-width: 150px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .test-btn.primary {
            background: #00adef;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 173, 239, 0.3);
        }
        
        .test-btn.secondary {
            background: #fff;
            color: #00adef;
            border: 2px solid #00adef;
            box-shadow: 0 4px 15px rgba(0, 173, 239, 0.1);
        }
        
        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .test-btn.primary:hover {
            background: #0098d4;
            box-shadow: 0 8px 25px rgba(0, 173, 239, 0.4);
        }
        
        .test-btn.secondary:hover {
            background: #00adef;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 173, 239, 0.3);
        }
        
        .test-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .test-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 173, 239, 0.3);
        }
        
        .debug-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .debug-info h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .debug-item {
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #00adef;
        }
        
        .debug-label {
            font-weight: bold;
            color: #666;
        }
        
        .debug-value {
            color: #333;
            margin-right: 10px;
        }
        
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="test-title">اختبار أزرار النقاط</h1>
        
        <div class="points-info">
            <h3>رصيد النقاط الحالي</h3>
            <div class="points-balance">
                <?= number_format($user_points['balance']) ?> نقطة
            </div>
            <p>متاح للاستخدام والاستبدال</p>
        </div>
        
        <div class="buttons-container">
            <a href="points-purchase.php" class="test-btn primary" id="purchase-btn">
                <i class="fas fa-plus"></i> شراء نقاط
            </a>
            <a href="points-redemption.php" class="test-btn secondary" id="redemption-btn">
                <i class="fas fa-exchange-alt"></i> استبدال النقاط
            </a>
        </div>
        
        <div class="debug-info">
            <h3>معلومات التصحيح</h3>
            
            <div class="debug-item">
                <span class="debug-label">معرف المستخدم:</span>
                <span class="debug-value"><?= $_SESSION['user_id'] ?></span>
            </div>
            
            <div class="debug-item">
                <span class="debug-label">رصيد النقاط:</span>
                <span class="debug-value"><?= number_format($user_points['balance']) ?> نقطة</span>
            </div>
            
            <div class="debug-item">
                <span class="debug-label">إجمالي النقاط المكتسبة:</span>
                <span class="debug-value"><?= number_format($user_points['total_earned']) ?> نقطة</span>
            </div>
            
            <div class="debug-item">
                <span class="debug-label">إجمالي النقاط المستهلكة:</span>
                <span class="debug-value"><?= number_format($user_points['total_spent']) ?> نقطة</span>
            </div>
            
            <div class="debug-item">
                <span class="debug-label">عدد خيارات الشراء:</span>
                <span class="debug-value"><?= count($purchase_options) ?> خيار</span>
            </div>
            
            <div class="debug-item">
                <span class="debug-label">الحد الأدنى للاستبدال:</span>
                <span class="debug-value"><?= get_min_points_redemption() ?> نقطة</span>
            </div>
            
            <?php if ($user_points['balance'] >= get_min_points_redemption()): ?>
                <div class="success">
                    ✅ يمكن استبدال النقاط - الرصيد كافي
                </div>
            <?php else: ?>
                <div class="error">
                    ❌ لا يمكن استبدال النقاط - الرصيد غير كافي
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('تم تحميل صفحة اختبار أزرار النقاط');
            
            const purchaseBtn = document.getElementById('purchase-btn');
            const redemptionBtn = document.getElementById('redemption-btn');
            
            // اختبار زر شراء النقاط
            purchaseBtn.addEventListener('click', function(e) {
                console.log('تم النقر على زر شراء النقاط');
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
            
            // اختبار زر استبدال النقاط
            redemptionBtn.addEventListener('click', function(e) {
                console.log('تم النقر على زر استبدال النقاط');
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
            
            // اختبار إمكانية الوصول
            [purchaseBtn, redemptionBtn].forEach(btn => {
                btn.setAttribute('tabindex', '0');
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        console.log('تم تفعيل الزر باستخدام لوحة المفاتيح');
                        this.click();
                    }
                });
            });
            
            console.log('أزرار النقاط جاهزة للاختبار');
        });
    </script>
</body>
</html> 