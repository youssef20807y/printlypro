<?php
/**
 * ملف التذييل المشترك لجميع صفحات الموقع
 */

// منع الوصول المباشر للملف
if (!defined('PRINTLY')) {
    die('الوصول المباشر لهذا الملف غير مسموح!');
}
?>

    <!-- قسم الاشتراك في النشرة البريدية -->


    <!-- التذييل -->
    <footer class="footer">
        <div class="container">
            <div class="footer-container">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                    </div>
                    <p><?php echo SITE_DESCRIPTION; ?></p>
                    <p>نقدم خدمات طباعة متكاملة بأعلى جودة وأفضل الأسعار، مع التزامنا بالمواعيد والدقة في التنفيذ.</p>
                    
                    <div class="social-links">
                        <a href="<?php echo get_setting('social_facebook', '#'); ?>" class="social-link" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo get_setting('social_instagram', '#'); ?>" class="social-link" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/<?php echo get_setting('social_whatsapp', ''); ?>" class="social-link" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul>
                        <li class="footer-link"><a href="index.php">الرئيسية</a></li>
                        <li class="footer-link"><a href="services.php">خدماتنا</a></li>
                        <li class="footer-link"><a href="portfolio.php">معرض الأعمال</a></li>
                        <li class="footer-link"><a href="order.php">طلب خدمة</a></li>
                        <li class="footer-link"><a href="about.php">من نحن</a></li>
                        <li class="footer-link"><a href="contact.php">تواصل معنا</a></li>
                        <li class="footer-link"><a href="faq.php">الأسئلة الشائعة</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h3 class="footer-title">خدماتنا</h3>
                    <ul>
                        <?php
                        // عرض أهم 6 خدمات
                        $stmt = $db->query("SELECT service_id, name FROM services WHERE status = 'active' ORDER BY is_featured DESC LIMIT 6");
                        while ($service = $stmt->fetch()) {
                            echo '<li class="footer-link"><a href="order.php?service_id=' . $service['service_id'] . '">' . $service['name'] . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h3 class="footer-title">تواصل معنا</h3>
                    <ul class="contact-info">
                        <li class="contact-item">
                            <i class="fas fa-map-marker-alt contact-icon contact-info-footer"></i>
                            <span><?php echo get_setting('site_address', 'الرياض، المملكة العربية السعودية'); ?></span>
                        </li>
                        <li class="contact-item">
                            <i class="fas fa-phone-alt contact-icon contact-info-footer"></i>
                            <span><?php echo get_setting('site_phone', '+966500000000'); ?></span>
                        </li>
                        <li class="contact-item">
                            <i class="fas fa-envelope contact-icon contact-info-footer"></i>
                            <span><?php echo get_setting('site_email', 'info@printly.com'); ?></span>
                        </li>
                        <li class="contact-item">
                            <i class="fas fa-clock contact-icon contact-info-footer"></i>
                            <span>ايام العمل : من السبت  إلي الخميس</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
    .contact-info-footer {
        vertical-align: middle;
        position: relative;
        top: 5px;
        margin-left: 6px;
        margin-right: 0;
        font-size: 1.1em;
        color: #00adef;
    }
    @media (max-width: 767px) {
        .footer-contact {
            display: none !important;
        }
    }
    </style>
</body>
</html>
