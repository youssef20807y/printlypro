<?php
/**
 * صفحة الخدمات لموقع مطبعة برنتلي - نسخة مبسطة للاختبار
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// بيانات وهمية للخدمات للاختبار
$services = [
    [
        'service_id' => 1,
        'name' => 'طباعة البروشورات',
        'description' => 'طباعة بروشورات عالية الجودة بألوان زاهية',
        'price_start' => 50,
        'category' => 'طباعة تجارية',
        'image' => 'brochures.jpg'
    ],
    [
        'service_id' => 2,
        'name' => 'طباعة البطاقات الشخصية',
        'description' => 'بطاقات شخصية أنيقة ومميزة',
        'price_start' => 25,
        'category' => 'طباعة شخصية',
        'image' => 'business-cards.jpg'
    ],
    [
        'service_id' => 3,
        'name' => 'طباعة الرول أب',
        'description' => 'رول أب للمعارض والفعاليات',
        'price_start' => 150,
        'category' => 'طباعة إعلانية',
        'image' => 'rollup.jpg'
    ]
];

$categories = ['طباعة تجارية', 'طباعة شخصية', 'طباعة إعلانية'];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خدماتنا - مطبعة برنتلي</title>
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- أنماط CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .service-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .service-content {
            padding: 20px;
        }
        
        .service-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .service-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .service-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .service-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-gold {
            background-color: #00adef;
            color: #333;
        }
        
        .btn-gold:hover {
            background-color: #00adef;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
    <!-- أيقونة سلة المشتريات -->
    <div class="cart-icon-container">
        <a href="#" id="cart-icon" class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-count">0</span>
        </a>
    </div>

    <!-- سلة المشتريات الجانبية -->
    <div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h2>سلة المشتريات</h2>
            <button id="close-cart" class="close-btn">&times;</button>
        </div>
        <div class="cart-items" id="cart-items">
            <!-- Cart items will be loaded here by JavaScript -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>الإجمالي:</span>
                <span id="cart-total-price">0.00 جنيه</span>
            </div>
            <button class="checkout-btn">إتمام الشراء</button>
        </div>
    </div>

    <!-- رأس الصفحة -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">خدماتنا</h1>
            <div class="breadcrumb">
                <a href="index.php">الرئيسية</a> / خدماتنا
            </div>
        </div>
    </section>

    <!-- قسم الخدمات -->
    <section class="services-page section">
        <div class="container">
            <h2 class="section-title">خدمات الطباعة المتكاملة</h2>
            <p class="section-description text-center">
                نقدم مجموعة متكاملة من خدمات الطباعة عالية الجودة لتلبية جميع احتياجاتك، سواء كنت فرداً أو شركة.
                نستخدم أحدث التقنيات والمعدات لضمان أفضل النتائج بأسعار تنافسية.
            </p>

            <!-- عرض الخدمات -->
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card" data-service-id="<?= $service['service_id'] ?>">
                        <div class="service-image">
                            <img src="uploads/services/<?= htmlspecialchars($service['image']) ?>" alt="<?= htmlspecialchars($service['name']) ?>">
                        </div>
                        <div class="service-content">
                            <h3 class="service-title"><?= htmlspecialchars($service['name']) ?></h3>
                            <div class="service-description">
                                <?= htmlspecialchars($service['description']) ?>
                            </div>
                            <div class="service-price">
                                <span class="price">تبدأ من <?= htmlspecialchars($service['price_start']) ?> جنيه</span>
                            </div>
                            <div class="service-actions">
                                <button class="btn btn-gold add-to-cart-btn" data-service-id="<?= $service['service_id'] ?>">أضف إلى السلة</button>
                                <a href="service-details.php?id=<?= $service['service_id'] ?>" class="btn btn-secondary">التفاصيل</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- JavaScript -->
    <script src="assets/js/cart.js"></script>
</body>
</html>

