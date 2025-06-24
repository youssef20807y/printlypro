<?php
/**
 * الصفحة الرئيسية لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// استعلام للحصول على الشرائح النشطة
$slider_query = $db->query("SELECT * FROM sliders WHERE status = 'active' ORDER BY order_num ASC");
$sliders = $slider_query->fetchAll();

// استعلام للحصول على الخدمات المميزة
$services_query = $db->query("SELECT * FROM services WHERE status = 'active' AND is_featured = 1 ORDER BY service_id ASC LIMIT 6");
$featured_services = $services_query->fetchAll();

// استعلام للحصول على العملاء المميزين
$clients_query = $db->query("SELECT * FROM clients WHERE is_featured = 1 ORDER BY order_num ASC LIMIT 8");
$featured_clients = $clients_query->fetchAll();

// استعلام للحصول على التقييمات المعتمدة
$testimonials_query = $db->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.status = 'approved' ORDER BY r.created_at DESC LIMIT 5");
$testimonials = $testimonials_query->fetchAll();
?>

<style>
/* Responsive Styles */
@media screen and (max-width: 768px) {
    .welcome-content {
        flex-direction: column;
    }
    
    .welcome-text {
        width: 100%;
        margin-bottom: 20px;
    }
    
    .welcome-image {
        width: 100%;
    }
    
    .welcome-image img {
        width: 100%;
        height: auto;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .service-card {
        flex-direction: column;
    }
    
    .service-image {
        width: 100%;
    }
    
    .service-content {
        width: 100%;
        padding: 15px;
    }
    
    .slide-content {
        padding: 20px;
    }
    
    .slide-title {
        font-size: 24px;
    }
    
    .slide-subtitle {
        font-size: 16px;
    }
    
    .testimonial-item {
        padding: 15px;
    }
    
    .cta-section .btn {
        display: block;
        width: 100%;
        margin: 10px 0;
    }
}

@media screen and (max-width: 480px) {
    .slide-title {
        font-size: 20px;
    }
    
    .slide-subtitle {
        font-size: 14px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .service-title {
        font-size: 18px;
    }
    
    .testimonial-content {
        font-size: 14px;
    }
}

.service-order-btn {
    display: block;
    margin: 20px auto 0 auto;
    text-align: center;
    width: fit-content;
}
</style>

<!-- الشريط الدوار -->
<section class="slider">
    <?php if (count($sliders) > 0): ?>
        <?php foreach ($sliders as $index => $slide): ?>
            <div class="slide <?php echo ($index === 0) ? 'active' : ''; ?>" style="background-image: url('uploads/sliders/<?php echo $slide['image']; ?>');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h1 class="slide-title"><?php echo $slide['title']; ?></h1>
                    <p class="slide-subtitle"><?php echo $slide['subtitle']; ?></p>
                    <?php if (!empty($slide['button_text']) && !empty($slide['button_link'])): ?>
                        <a href="<?php echo $slide['button_link']; ?>" class="btn btn-gold"><?php echo $slide['button_text']; ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- أسهم التنقل -->
        <div class="slider-arrow slider-arrow-prev">
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="slider-arrow slider-arrow-next">
            <i class="fas fa-chevron-left"></i>
        </div>
        
        <!-- نقاط التنقل -->
        <div class="slider-controls">
            <?php foreach ($sliders as $index => $slide): ?>
                <div class="slider-dot <?php echo ($index === 0) ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- شريحة افتراضية في حالة عدم وجود شرائح في قاعدة البيانات -->
        <div class="slide active" style="background-image: url('assets/images/default-slide.jpg');">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1 class="slide-title">مطبعة برنتلي</h1>
                <p class="slide-subtitle">نحو طباعة متقنة، تليق بأفكارك</p>
                <a href="services.php" class="btn btn-gold">استكشف خدماتنا</a>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- قسم الترحيب -->
<section class="welcome-section section">
    <div class="container">
        <div class="welcome-content">
            <div class="welcome-text">
                <h2 class="section-title">مرحباً بكم في <span class="gold-text">مطبعة برنتلي</span></h2>
                <p>نحن مطبعة متخصصة في تقديم خدمات الطباعة المتكاملة بأعلى جودة وأفضل الأسعار. نمتلك أحدث المعدات والتقنيات في مجال الطباعة، ونضم فريقاً من المحترفين ذوي الخبرة الطويلة.</p>
                <p>منذ تأسيسنا، حرصنا على تقديم خدمات متميزة تلبي احتياجات عملائنا من الأفراد والشركات، مع الالتزام بالمواعيد والدقة في التنفيذ.</p>
                <p>نؤمن بأن الطباعة ليست مجرد حبر على ورق، بل هي انعكاس لهويتك وصورة عملك. لذلك نحرص على أن تكون مطبوعاتك بأعلى جودة وأفضل تصميم.</p>
                <a href="about.php" class="btn btn-primary">تعرف علينا أكثر</a>
            </div>
            <div class="welcome-image">
                <img src="assets/images/printing-shop.jpg" alt="مطبعة برنتلي">
            </div>
        </div>
    </div>
</section>
<section class="cta-section section" style="background-color: var(--color-light-gray);">
    <div class="container text-center">
        <h2 class="section-title">جاهز لطلب خدمة طباعة؟</h2>
        <p>نحن هنا لمساعدتك في تحويل أفكارك إلى مطبوعات عالية الجودة</p>
        <div style="margin-top: var(--spacing-lg);">
            <a href="order.php" class="btn btn-gold" style="margin-left: var(--spacing-md);">طلب خدمة الآن</a>
            <a href="contact.php" class="btn btn-secondary">تواصل معنا</a>
        </div>
    </div>
</section>
<!-- قسم الخدمات -->
<section class="services-section section">
    <div class="container">
        <h2 class="section-title">خدماتنا المميزة</h2>
        <div class="services-grid">
            <?php if (count($featured_services) > 0): ?>
                <?php foreach ($featured_services as $service): ?>
                    <div class="service-card">
                        <div class="service-image">
                            <img src="uploads/services/<?php echo $service['image']; ?>" alt="<?php echo $service['name']; ?>">
                        </div>
                        <div class="service-content">
                            <h3 class="service-title"><?php echo $service['name']; ?></h3>
                            <p class="service-description"><?php echo substr($service['description'], 0, 100) . '...'; ?></p>
                            <a href="order.php?service_id=<?php echo $service['service_id']; ?>" class="btn btn-gold service-order-btn">طلب الخدمة</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- خدمات افتراضية في حالة عدم وجود خدمات في قاعدة البيانات -->
                <div class="service-card">
                    <div class="service-image">
                        <img src="assets/images/business-cards.jpg" alt="كروت شخصية">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title">كروت شخصية</h3>
                        <p class="service-description">تصميم وطباعة كروت شخصية بأعلى جودة وبمختلف الخامات والمقاسات.</p>
                        <a href="services.php" class="btn btn-secondary">تفاصيل الخدمة</a>
                        <a href="order.php" class="btn btn-gold">طلب الخدمة</a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="assets/images/brochures.jpg" alt="بروشورات">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title">بروشورات</h3>
                        <p class="service-description">تصميم وطباعة بروشورات دعائية بمختلف المقاسات والخامات.</p>
                        <a href="services.php" class="btn btn-secondary">تفاصيل الخدمة</a>
                        <a href="order.php" class="btn btn-gold">طلب الخدمة</a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="assets/images/rollup.jpg" alt="رول أب">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title">رول أب</h3>
                        <p class="service-description">تصميم وطباعة رول أب بأحجام مختلفة وبأعلى جودة.</p>
                        <a href="services.php" class="btn btn-secondary">تفاصيل الخدمة</a>
                        <a href="order.php" class="btn btn-gold">طلب الخدمة</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center" style="margin-top: var(--spacing-lg);">
            <a href="services.php" class="btn btn-primary">عرض جميع الخدمات</a>
        </div>
    </div>
</section>

<!-- قسم العملاء -->



<!-- قسم الشهادات -->
<?php if (count($testimonials) > 0): ?>
<section class="testimonials-section section">
    <div class="container">
        <h2 class="section-title">آراء عملائنا</h2>
        <div class="testimonials-slider">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-item">
                    <div class="testimonial-content">
                        <?php echo $testimonial['comment']; ?>
                    </div>
                    <div class="testimonial-author"><?php echo $testimonial['username']; ?></div>
                    <div class="testimonial-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo ($i <= $testimonial['rating']) ? 'gold-text' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- قسم الإحصائيات -->


<!-- قسم الدعوة للعمل -->

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
