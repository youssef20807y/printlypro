<?php
/**
 * صفحة الخدمات لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على فئات الخدمات الفريدة
$categories_query = $db->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);

// الحصول على الخدمات
$services_query = $db->query("
    SELECT s.*, c.name as category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.category_id 
    WHERE s.status = 'active' 
    ORDER BY c.name ASC, s.name ASC
");
$services = $services_query->fetchAll();

// تنظيم الخدمات حسب الفئة
$services_by_category = [];
foreach ($services as $service) {
    $category = $service['category_name'] ?: 'أخرى'; // Use 'أخرى' for uncategorized
    if (!isset($services_by_category[$category])) {
        $services_by_category[$category] = [];
    }
    $services_by_category[$category][] = $service;
}

// الوسوم المسموح بها في الوصف المختصر
$allowed_tags_short = '<p><strong><b><em><i><br>';

?>

<!-- رأس الصفحة -->
<section class="page-header" style="background-image: url('assets/images/services-header.jpg');">
    <div class="container">
        <h1 class="page-title">خدماتنا</h1>
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

        <!-- فلتر الخدمات -->
        <div class="services-filter">
            <ul class="filter-list">
                <li><button class="filter-btn active" data-filter="all">جميع الخدمات</button></li>
                <?php foreach ($categories as $category): ?>
                    <?php $filter_cat = strtolower(str_replace(' ', '-', htmlspecialchars($category['name']))); ?>
                    <li><button class="filter-btn" data-filter="<?= $filter_cat ?>"><?= htmlspecialchars($category['name']) ?></button></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- عرض الخدمات -->
        <div class="services-grid">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <?php $card_cat = strtolower(str_replace(' ', '-', htmlspecialchars($service['category_name'] ?: 'أخرى'))); ?>
                    <div class="service-card" data-category="<?= $card_cat ?>">
                        <div class="service-image">
                            <a href="order.php?service_id=<?= $service['service_id'] ?>">
                            <?php if (!empty($service['image']) && file_exists('uploads/services/' . $service['image'])): ?>
                                <img src="uploads/services/<?= htmlspecialchars($service['image']) ?>" alt="<?= htmlspecialchars($service['name']) ?>">
                            <?php else: ?>
                                <img src="assets/images/service-placeholder.jpg" alt="<?= htmlspecialchars($service['name']) ?>">
                            <?php endif; ?>
                            </a>
                        </div>
                        <div class="service-content">
                            <h3 class="service-title"><a href="order.php?service_id=<?= $service['service_id'] ?>"><?= htmlspecialchars($service['name']) ?></a></h3>
                            <div class="service-description">
                                <?php
                                // عرض الوصف مع السماح ببعض وسوم HTML الأساسية وإزالة الباقي
                                // يمكنك تعديل قائمة الوسوم المسموح بها حسب الحاجة
                                $description_html = $service['description'];
                                // إزالة وسوم الفقرات الفارغة التي قد يضيفها المحرر
                                $description_html = preg_replace('/<p>\s*(&nbsp;)*\s*<\/p>/i', '', $description_html);
                                // عرض الوصف بعد التنقية والسماح بالوسوم المحددة
                                echo strip_tags($description_html, $allowed_tags_short);
                                ?>
                            </div>
                            <div class="service-actions">
                                <a href="order.php?service_id=<?= $service['service_id'] ?>" class="btn btn-gold">اطلب الآن</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-services text-center" style="grid-column: 1 / -1;">
                    <p class="lead">لا توجد خدمات متاحة حالياً في هذا التصنيف أو بشكل عام.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- قسم طلب خدمة مخصصة -->
<section class="custom-service-section section">
    <div class="container">
        <div class="custom-service-wrapper">
            <div class="custom-service-content">
                <h2 class="section-title">هل تحتاج إلى خدمة طباعة مخصصة؟</h2>
                <p>إذا كنت تبحث عن خدمة طباعة غير مدرجة في قائمة خدماتنا، أو تحتاج إلى مواصفات خاصة، فنحن هنا لمساعدتك.</p>
                <p>فريقنا من المتخصصين جاهز لتلبية احتياجاتك الفريدة وتقديم حلول مخصصة تناسب متطلباتك.</p>
                <a href="contact.php" class="btn btn-gold mt-4">تواصل معنا الآن</a>
            </div>
            <div class="custom-service-image">
                <img src="assets/images/custom-printing.jpg" alt="خدمة طباعة مخصصة" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- قسم الأسئلة الشائعة -->
<section class="faq-section section">
    <div class="container">
        <h2 class="section-title">الأسئلة الشائعة حول خدماتنا</h2>

        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">
                    <h3>ما هي مدة تنفيذ الطلب؟</h3>
                    <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>تختلف مدة تنفيذ الطلب حسب نوع الخدمة والكمية المطلوبة. عادةً ما تتراوح المدة بين 1-5 أيام عمل. يمكنك معرفة المدة التقديرية عند تقديم الطلب.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>هل يمكنني الحصول على عينة قبل طباعة الكمية الكاملة؟</h3>
                    <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>نعم، يمكننا توفير عينة للتأكد من جودة الطباعة ومطابقتها لتوقعاتك قبل طباعة الكمية الكاملة. قد تكون هناك رسوم إضافية للعينات.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>هل توفرون خدمة التوصيل؟</h3>
                    <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>نعم، نوفر خدمة التوصيل داخل المدينة وإلى جميع مناطق المملكة. تختلف رسوم التوصيل حسب الموقع والوزن.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>هل يمكنني تعديل التصميم بعد تقديم الطلب؟</h3>
                    <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>يمكن إجراء تعديلات طفيفة على التصميم قبل بدء عملية الطباعة. بعد بدء الطباعة، قد لا يكون من الممكن إجراء تعديلات دون رسوم إضافية.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>ما هي صيغ الملفات المقبولة للطباعة؟</h3>
                    <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>نقبل معظم صيغ الملفات الشائعة مثل PDF، AI، PSD، JPEG، PNG. للحصول على أفضل نتائج، نوصي باستخدام ملفات PDF عالية الدقة.</p>
                </div>
            </div>
        </div>


    </div>
</section>

<!-- قسم الدعوة للعمل -->
<section class="cta-section section" style="background-color: var(--color-black); color: var(--color-white);">
    <div class="container text-center">
        <h2 class="section-title" style="color: var(--color-white);">جاهز لطلب خدمة طباعة؟</h2>
        <p>نحن هنا لمساعدتك في تحويل أفكارك إلى مطبوعات عالية الجودة</p>
        <div style="margin-top: var(--spacing-lg);">
            <a href="order.php" class="btn btn-gold" style="margin-left: var(--spacing-md);">طلب خدمة الآن</a>
            <a href="contact.php" class="btn btn-secondary">تواصل معنا</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة فلتر الخدمات
    const filterButtons = document.querySelectorAll('.filter-btn');
    const serviceCards = document.querySelectorAll('.service-card');
    const noServicesMessage = document.querySelector('.no-services');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // إزالة الفئة النشطة من جميع الأزرار
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // إضافة الفئة النشطة للزر الحالي
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');
            let visibleCount = 0;

            // تصفية الخدمات
            serviceCards.forEach(card => {
                if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // إظهار/إخفاء رسالة عدم وجود خدمات
            if (noServicesMessage) {
                if (visibleCount === 0 && serviceCards.length > 0) {
                    noServicesMessage.style.display = 'block';
                } else {
                    noServicesMessage.style.display = 'none';
                }
            }
        });
    });

    // تهيئة الأسئلة الشائعة
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        const toggle = item.querySelector('.faq-toggle i');

        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');

            // إغلاق جميع الإجابات الأخرى
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.faq-toggle i').className = 'fas fa-plus';
                }
            });

            // تبديل حالة العنصر الحالي
            item.classList.toggle('active');
            toggle.className = item.classList.contains('active') ? 'fas fa-plus' : 'fas fa-plus';
        });
    });
});
</script>

<style>
/* أنماط صفحة الخدمات */
.page-header {
    background-size: cover;
    background-position: center;
    background-color: rgba(0, 0, 0, 0.7);
    background-blend-mode: overlay;
    padding: 100px 0 50px;
    color: var(--color-white);
    text-align: center;
    margin-top: 80px; /* Adjust based on header height */
}

.page-title {
    font-size: 3rem;
    margin-bottom: var(--spacing-sm);
    color: var(--color-white);
}

.breadcrumb {
    font-size: 1rem;
}

.breadcrumb a {
    color: var(--color-gold);
    text-decoration: none;
}
.breadcrumb a:hover {
    text-decoration: underline;
}

.section-description {
    max-width: 800px;
    margin: 0 auto var(--spacing-lg);
}

/* فلتر الخدمات */
.services-filter {
    margin-bottom: var(--spacing-lg);
}

.filter-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    list-style: none;
    padding: 0;
    gap: var(--spacing-sm);
}

.filter-btn {
    background: none;
    border: 1px solid var(--color-light-gray);
    border-radius: 20px;
    padding: var(--spacing-xs) var(--spacing-md);
    cursor: pointer;
    font-family: var(--font-heading);
    font-weight: 500;
    transition: var(--transition-fast);
    color: var(--color-dark-gray);
}

.filter-btn:hover {
    background-color: var(--color-light-gray);
    border-color: var(--color-gray);
}

.filter-btn.active {
    background-color: var(--color-gold);
    border-color: var(--color-gold);
    color: var(--color-white);
}

/* عرض الخدمات */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.service-card {
    background-color: var(--color-white);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-fast);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.service-image {
    height: 200px;
    overflow: hidden;
    position: relative;
}
.service-image a {
    display: block;
    height: 100%;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition-medium);
}

.service-card:hover .service-image img {
    transform: scale(1.05);
}

.service-content {
    padding: var(--spacing-md);
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.service-title {
    font-size: 1.25rem;
    margin-bottom: var(--spacing-sm);
}
.service-title a {
    color: var(--color-black);
    text-decoration: none;
    transition: color var(--transition-fast);
}
.service-title a:hover {
    color: var(--color-gold);
}

.service-description {
    margin-bottom: var(--spacing-md);
    color: var(--color-dark-gray);
    flex-grow: 1;
    font-size: 0.95rem;
    line-height: 1.6;
}
/* Limit description height and add fade effect if needed */
.service-description {
    max-height: 100px; /* Adjust as needed */
    overflow: hidden;
    position: relative;
}
.service-description::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 30px;
    background: linear-gradient(to bottom, transparent, white);
    pointer-events: none; /* Allows clicking through the gradient */
}

.service-actions {
    display: flex;
    justify-content: space-between;
    gap: var(--spacing-sm);
    margin-top: auto; /* Push actions to the bottom */
}

.service-actions .btn {
    flex: 1;
    text-align: center;
    padding: var(--spacing-sm);
}

.no-services {
    grid-column: 1 / -1; /* Span across all columns */
    text-align: center;
    padding: var(--spacing-xl) 0;
    color: var(--color-dark-gray);
}

/* خدمة مخصصة */
.custom-service-section {
    background-color: var(--color-light-gray);
    padding: var(--spacing-xl) 0;
}

.custom-service-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-xl);
    align-items: center;
}

.custom-service-content {
    padding-right: var(--spacing-lg);
}

.custom-service-content h2 {
    margin-bottom: var(--spacing-md);
    font-size: 2.2rem;
}

.custom-service-content p {
    margin-bottom: var(--spacing-md);
    line-height: 1.8;
    color: var(--color-dark-gray);
}

.custom-service-image {
    position: relative;
    height: 100%;
    min-height: 400px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.custom-service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.custom-service-image:hover img {
    transform: scale(1.05);
}

/* تحسين التجاوب للشاشات الصغيرة */
@media (max-width: 991px) {
    .custom-service-wrapper {
        grid-template-columns: 1fr;
        gap: var(--spacing-lg);
    }
    
    .custom-service-content {
        padding-right: 0;
        text-align: center;
    }
    
    .custom-service-image {
        min-height: 300px;
    }
}

/* الأسئلة الشائعة */
.faq-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    margin-bottom: var(--spacing-md);
    border: 1px solid var(--color-light-gray);
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow var(--transition-fast);
}
.faq-item:hover {
    box-shadow: var(--shadow-sm);
}

.faq-question {
    padding: var(--spacing-md);
    background-color: var(--color-white);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background-color var(--transition-fast);
}
.faq-item.active .faq-question {
    background-color: var(--color-lighter-gray);
}

.faq-question h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.faq-toggle {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-gold);
    transition: transform 0.4s ease;
}
.faq-item.active .faq-toggle {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 var(--spacing-md);
    height: 0;
    overflow: hidden;
    opacity: 0;
    transition: all 0.4s ease-in-out;
}
.faq-item.active .faq-answer {
    padding: var(--spacing-md);
    height: auto;
    opacity: 1;
}

.faq-toggle i {
    transition: transform 0.4s ease;
}

.faq-item.active .faq-toggle i {
    transform: rotate(45deg);
}

/* قسم الدعوة للعمل */
.cta-section {
    padding: var(--spacing-xl) 0;
}

</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>

