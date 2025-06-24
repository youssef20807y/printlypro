<?php
/**
 * صفحة معرض الأعمال لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على فئات الأعمال الفريدة
$categories_query = $db->query("
    SELECT DISTINCT c.category_id, c.name 
    FROM portfolio p 
    JOIN categories c ON p.category_id = c.category_id 
    ORDER BY c.name ASC
");
$categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);

// الحصول على الأعمال
$portfolio_query = $db->query("
    SELECT p.*, s.name as service_name, c.name as category_name 
    FROM portfolio p 
    LEFT JOIN services s ON p.service_id = s.service_id 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.created_at DESC
");
$portfolio_items = $portfolio_query->fetchAll();
?>
<section>
    <div class="container">
        <h1 class="page-title">ㅤ ㅤ</h1>
        <div class="breadcrumb">
            <a href="index.php">ㅤ</a>ㅤ
        </div>
    </div>
</section>
<!-- رأس الصفحة -->


<!-- قسم معرض الأعمال -->
<section class="portfolio-section section">
    <div class="container">
        <h2 class="section-title">أعمالنا المميزة</h2>
        <p class="section-description text-center">
            نفخر بتقديم مجموعة من أفضل أعمالنا التي تعكس جودة خدماتنا والتزامنا بالتميز في كل تفاصيل الطباعة.
            استعرض أعمالنا واكتشف إمكانياتنا في تحويل أفكارك إلى واقع ملموس.
        </p>
        
        <!-- فلتر معرض الأعمال -->
        <div class="portfolio-filter">
            <ul class="filter-list">
                <li><button class="portfolio-filter-btn active" data-filter="all">جميع الأعمال</button></li>
                <?php foreach ($categories as $category): ?>
                    <li><button class="portfolio-filter-btn" data-filter="<?php echo strtolower(str_replace(' ', '-', $category['name'])); ?>"><?php echo $category['name']; ?></button></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- عرض معرض الأعمال -->
        <div class="portfolio-grid">
            <?php if (count($portfolio_items) > 0): ?>
                <?php foreach ($portfolio_items as $item): ?>
                    <div class="portfolio-item <?php echo strtolower(str_replace(' ', '-', $item['category_name'])); ?>">
                        <div class="portfolio-image">
                            <img src="uploads/portfolio/<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>" class="zoomable-image">
                            <div class="portfolio-overlay">
                                <div class="portfolio-info">
                                    <h3><?php echo $item['title']; ?></h3>
                                    <p>
                                        <a href="search.php?q=<?php echo htmlspecialchars(urlencode($item['service_name'])); ?>" class="service-link" onclick="window.location.href='search.php?q=<?php echo htmlspecialchars(urlencode($item['service_name'])); ?>'; return false;">
                                            <?php echo htmlspecialchars($item['service_name']); ?>
                                        </a>
                                    </p>
                                    <a href="#" class="portfolio-zoom">
                                        <i class="fas fa-search-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- أعمال افتراضية في حالة عدم وجود أعمال في قاعدة البيانات -->
                <div class="portfolio-item business-cards">
                    <div class="portfolio-image">
                        <img src="assets/images/portfolio/business-card-1.jpg" alt="كرت شخصي احترافي">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h3>كرت شخصي احترافي</h3>
                                <p>كروت شخصية</p>
                                <a href="#" class="portfolio-zoom">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="portfolio-item brochures">
                    <div class="portfolio-image">
                        <img src="assets/images/portfolio/brochure-1.jpg" alt="بروشور شركة">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h3>بروشور شركة</h3>
                                <p>بروشورات</p>
                                <a href="#" class="portfolio-zoom">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="portfolio-item rollups">
                    <div class="portfolio-image">
                        <img src="assets/images/portfolio/rollup-1.jpg" alt="رول أب معرض">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h3>رول أب معرض</h3>
                                <p>رول أب</p>
                                <a href="#" class="portfolio-zoom">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="portfolio-item business-cards">
                    <div class="portfolio-image">
                        <img src="assets/images/portfolio/business-card-2.jpg" alt="كرت شخصي مميز">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h3>كرت شخصي مميز</h3>
                                <p>كروت شخصية</p>
                                <a href="#" class="portfolio-zoom">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="portfolio-item stickers">
                    <div class="portfolio-image">
                        <img src="assets/images/portfolio/sticker-1.jpg" alt="استيكر شعار">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h3>استيكر شعار</h3>
                                <p>استيكرات</p>
                                <a href="#" class="portfolio-zoom">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="portfolio-item notebooks">
                    <div class="portfolio-image">
                        <img src="assets/images/portfolio/notebook-1.jpg" alt="دفتر ملاحظات">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h3>دفتر ملاحظات</h3>
                                <p>دفاتر</p>
                                <a href="#" class="portfolio-zoom">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- قسم الخدمات المرتبطة -->
<section class="related-services-section section" style="background-color: var(--color-light-gray);">
    <div class="container">
        <h2 class="section-title">خدماتنا المتميزة</h2>
        <p class="text-center">استكشف خدماتنا المتنوعة واختر ما يناسب احتياجاتك</p>
        
        <div class="services-grid">
            <?php
            // الحصول على الخدمات المميزة
            $featured_services_query = $db->query("SELECT * FROM services WHERE status = 'active' AND is_featured = 1 ORDER BY service_id ASC LIMIT 3");
            $featured_services = $featured_services_query->fetchAll();
            
            if (count($featured_services) > 0):
                foreach ($featured_services as $service):
            ?>
                <div class="service-card">
                    <div class="service-image">
                        <?php if (!empty($service['image'])): ?>
                            <img src="uploads/services/<?php echo $service['image']; ?>" alt="<?php echo $service['name']; ?>">
                        <?php else: ?>
                            <img src="assets/images/service-placeholder.jpg" alt="<?php echo $service['name']; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="service-content">
                        <h3 class="service-title"><?php echo $service['name']; ?></h3>
                        <p class="service-description"><?php echo substr($service['description'], 0, 100) . '...'; ?></p>
                        <a href="service-details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-secondary">تفاصيل الخدمة</a>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center" style="margin-top: var(--spacing-lg);">
            <a href="services.php" class="btn btn-primary">عرض جميع الخدمات</a>
        </div>
    </div>
</section>

<!-- قسم الدعوة للعمل -->
<section class="cta-section section">
    <div class="container text-center">
        <h2 class="section-title">هل أعجبتك أعمالنا؟</h2>
        <p>دعنا نساعدك في تحويل أفكارك إلى تصاميم مطبوعة مميزة</p>
        <div style="margin-top: var(--spacing-lg);">
            <a href="order.php" class="btn btn-gold" style="margin-left: var(--spacing-md);">طلب خدمة الآن</a>
            <a href="contact.php" class="btn btn-secondary">تواصل معنا</a>
        </div>
    </div>
</section>

<style>
/* أنماط صفحة معرض الأعمال */
.portfolio-filter {
    margin-bottom: var(--spacing-lg);
}

.portfolio-filter .filter-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    list-style: none;
    padding: 0;
}

.portfolio-filter-btn {
    background: none;
    border: none;
    padding: var(--spacing-sm) var(--spacing-md);
    margin: 0 var(--spacing-xs);
    cursor: pointer;
    font-family: var(--font-heading);
    font-weight: 600;
    position: relative;
    transition: var(--transition-fast);
}

.portfolio-filter-btn:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--color-gold);
    transform: scaleX(0);
    transition: var(--transition-fast);
}

.portfolio-filter-btn:hover:after,
.portfolio-filter-btn.active:after {
    transform: scaleX(1);
}

.portfolio-filter-btn.active {
    color: var(--color-gold);
}

.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.portfolio-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-fast);
}

.portfolio-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.portfolio-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.portfolio-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition-medium);
}

.portfolio-item:hover .portfolio-image img {
    transform: scale(1.05);
}

.portfolio-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: var(--transition-fast);
}

.portfolio-item:hover .portfolio-overlay {
    opacity: 1;
}

.portfolio-info {
    text-align: center;
    color: var(--color-white);
    padding: var(--spacing-md);
    transform: translateY(20px);
    transition: var(--transition-fast);
}

.portfolio-item:hover .portfolio-info {
    transform: translateY(0);
}

.portfolio-info h3 {
    font-size: 1.5rem;
    margin-bottom: var(--spacing-xs);
    color: var(--color-white);
    font-weight: 700;
}

.portfolio-info p {
    font-size: 1.2rem;
    margin-bottom: var(--spacing-md);
    color: var(--color-gold);
}

.service-link {
    color: var(--color-gold);
    text-decoration: none;
    transition: var(--transition-fast);
    font-weight: 700;
}

.service-link:hover {
    color: var(--color-white);
    text-decoration: underline;
}

.portfolio-zoom {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: var(--color-gold);
    color: var(--color-black);
    border-radius: 50%;
    transition: var(--transition-fast);
    border: none;
    cursor: pointer;
    padding: 0;
    text-decoration: none;
}

.portfolio-zoom:hover {
    background-color: var(--color-white);
    transform: scale(1.1);
}

/* تحسين التجاوب */
@media (max-width: 992px) {
    .portfolio-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .portfolio-filter .filter-list {
        flex-direction: column;
        align-items: center;
    }
    
    .portfolio-filter-btn {
        margin: var(--spacing-xs) 0;
    }
}

@media (max-width: 576px) {
    .portfolio-grid {
        grid-template-columns: 1fr;
    }
}

/* أنماط نافذة تكبير الصورة */
.lightbox {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.lightbox.active {
    display: flex;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90vh;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
}

.lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    color: white;
    font-size: 30px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 5px;
}

.lightbox-close:hover {
    color: var(--color-gold);
}
</style>

<!-- إضافة عنصر نافذة تكبير الصورة -->
<div class="lightbox" id="imageLightbox">
    <div class="lightbox-content">
        <button class="lightbox-close">&times;</button>
        <img src="" alt="صورة مكبرة">
    </div>
</div>

<script>
// إعادة تحميل الصفحة عند استخدام زر الرجوع
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة فلتر معرض الأعمال
    const filterButtons = document.querySelectorAll('.portfolio-filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    
    // تهيئة نافذة تكبير الصورة
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImg = lightbox.querySelector('img');
    const lightboxClose = lightbox.querySelector('.lightbox-close');
    
    // إضافة مستمعي الأحداث لأزرار التكبير
    document.querySelectorAll('.portfolio-zoom').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const portfolioItem = this.closest('.portfolio-item');
            const imgSrc = portfolioItem.querySelector('img').src;
            const imgAlt = portfolioItem.querySelector('img').alt;
            
            lightboxImg.src = imgSrc;
            lightboxImg.alt = imgAlt;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    // إغلاق نافذة التكبير
    lightboxClose.addEventListener('click', () => {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // إغلاق نافذة التكبير عند النقر خارج الصورة
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // إغلاق نافذة التكبير باستخدام مفتاح ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // إزالة الفئة النشطة من جميع الأزرار
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // إضافة الفئة النشطة للزر الحالي
            this.classList.add('active');
            
            // الحصول على فئة التصفية
            const filterValue = this.getAttribute('data-filter');
            
            // تصفية العناصر
            portfolioItems.forEach(item => {
                if (filterValue === 'all' || item.classList.contains(filterValue)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
