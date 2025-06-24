<?php
/**
 * صفحة تفاصيل الخدمة لموقع مطبعة برنتلي
 * 
 * @package Printly
 * @version 1.0
 */

// تعريف ثوابت النظام
define('PRINTLY', true);

// استدعاء الملفات المطلوبة
require_once 'includes/header.php';

/**
 * دالة لتقصير النص مع إضافة علامة الحذف
 * 
 * @param string $text النص المراد تقصيره
 * @param int $max_chars الحد الأقصى لعدد الأحرف
 * @return string النص المقصوص
 */
function truncate_text($text, $max_chars = 100) {
    if (strlen($text) <= $max_chars) {
        return $text;
    }
    return substr($text, 0, $max_chars) . '...';
}

// الحصول على معرف الخدمة من URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// تهيئة المتغيرات
$service = null;
$related_services = [];
$portfolio_items = [];

// جلب بيانات الخدمة
if ($service_id > 0) {
    // جلب بيانات الخدمة الرئيسية
    $stmt = $db->prepare("
        SELECT s.*, c.name as category_name 
        FROM services s 
        LEFT JOIN categories c ON s.category_id = c.category_id 
        WHERE s.service_id = ? AND s.status = 'active'
    ");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($service) {
        // تحويل قيم المتطلبات إلى boolean
        $service['require_paper_type'] = !empty($service['require_paper_type']);
        $service['require_size'] = !empty($service['require_size']);
        $service['require_colors'] = !empty($service['require_colors']);
        $service['require_design_file'] = !empty($service['require_design_file']);
        $service['require_notes'] = !empty($service['require_notes']);

        // جلب خدمات ذات صلة
        $stmt = $db->prepare("
            SELECT s.*, c.name as category_name 
            FROM services s 
            LEFT JOIN categories c ON s.category_id = c.category_id 
            WHERE s.category_id = ? AND s.service_id != ? AND s.status = 'active' 
            LIMIT 4
        ");
        $stmt->execute([$service['category_id'], $service_id]);
        $related_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        redirect('services.php');
    }
} else {
    redirect('services.php');
}

// قوائم الخيارات الثابتة
$paper_types = ['عادي', 'مقوى', 'لامع', 'مطفي', 'كوشيه', 'كرافت', 'استيكر'];
$sizes = ['A3', 'A4', 'A5', 'A6', 'مخصص'];
?>

<!-- قسم تفاصيل الخدمة -->
<section class="service-details">
    <div class="service-details-container">
        <!-- شريط التنقل -->


        <div class="service-content">
            <!-- صورة الخدمة -->
            <div class="service-image" onclick="openImageModal('<?php echo $image_url; ?>', '<?php echo htmlspecialchars($service['name']); ?>')">
                <?php
                $image_url = 'assets/images/default-slide.jpg';
                if (!empty($service['image']) && file_exists('uploads/services/' . $service['image'])) {
                    $image_url = 'uploads/services/' . htmlspecialchars($service['image']);
                }
                ?>
                <img src="<?php echo $image_url; ?>" 
                     alt="<?php echo htmlspecialchars($service['name']); ?>" 
                     class="service-img">
                <div class="image-overlay">
                    <i class="fas fa-search-plus"></i>
                    <span>انقر لعرض الصورة كاملة</span>
                </div>
            </div>

            <!-- Modal لعرض الصورة كاملة -->
            <div id="imageModal" class="image-modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeImageModal()">&times;</span>
                    <img id="modalImage" src="" alt="">
                    <div class="modal-caption" id="modalCaption"></div>
                </div>
            </div>

            <!-- معلومات الخدمة -->
            <div class="service-info">
                <div class="service-header">
                    <h1 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h1>
                    
                    <div class="service-badges">
                        <?php if ($service['is_featured']): ?>
                        <span class="badge featured">
                            <i class="fas fa-star"></i> مميزة
                        </span>
                        <?php endif; ?>
                        <span class="badge category">
                            <i class="fas fa-folder"></i>
                            <?php echo htmlspecialchars($service['category_name'] ?? 'بدون تصنيف'); ?>
                        </span>
                    </div>
                </div>

                <div class="service-description">
                    <?php echo nl2br(html_entity_decode($service['description'])); ?>
                </div>

                <!-- مواصفات الخدمة -->
                <div class="service-specs">
                    <h3>مواصفات الخدمة</h3>
                    <div class="specs-grid">
                        <?php if ($service['require_paper_type']): ?>
                        <div class="spec-item">
                            <i class="fas fa-scroll"></i>
                            <span>نوع الورق</span>
                            <small>مطلوب تحديده</small>
                        </div>
                        <?php endif; ?>

                        <?php if ($service['require_size']): ?>
                        <div class="spec-item">
                            <i class="fas fa-ruler-combined"></i>
                            <span>المقاس</span>
                            <small>مطلوب تحديده</small>
                        </div>
                        <?php endif; ?>

                        <?php if ($service['require_colors']): ?>
                        <div class="spec-item">
                            <i class="fas fa-palette"></i>
                            <span>عدد الألوان</span>
                            <small>مطلوب تحديده</small>
                        </div>
                        <?php endif; ?>

                        <div class="spec-item">
                            <i class="fas fa-file-upload"></i>
                            <span>ملف التصميم</span>
                            <small class="<?php echo $service['require_design_file'] ? 'required' : 'optional'; ?>">
                                <?php echo $service['require_design_file'] ? 'إلزامي' : 'اختياري'; ?>
                            </small>
                        </div>

                        <div class="spec-item">
                            <i class="fas fa-sticky-note"></i>
                            <span>ملاحظات</span>
                            <small class="<?php echo $service['require_notes'] ? 'required' : 'optional'; ?>">
                                <?php echo $service['require_notes'] ? 'إلزامي' : 'اختياري'; ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- زر الطلب -->
                <div class="service-action">
                    <a href="order.php?service_id=<?php echo $service_id; ?>" class="btn-order">
                        <i class="fas fa-shopping-cart"></i>
                        اطلب هذه الخدمة
                    </a>
                    <p class="action-note">سيتم نقلك إلى صفحة الطلب لتحديد الكمية والمواصفات</p>
                    
                    <!-- زر تجريبي لاختبار Modal -->

                </div>
            </div>
        </div>

        <!-- الخدمات ذات الصلة -->
        <?php if (!empty($related_services)): ?>
        <div class="related-services">
            <h2>خدمات أخرى قد تهمك</h2>
            <div class="services-grid">
                <?php foreach ($related_services as $related_service): ?>
                <div class="service-card">
                    <?php
                    $related_image_url = 'assets/images/default-slide.jpg';
                    if (!empty($related_service['image']) && file_exists('uploads/services/' . $related_service['image'])) {
                        $related_image_url = 'uploads/services/' . htmlspecialchars($related_service['image']);
                    }
                    ?>
                    <div class="card-image">
                        <img src="<?php echo $related_image_url; ?>" 
                             alt="<?php echo htmlspecialchars($related_service['name']); ?>">
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($related_service['name']); ?></h3>
                        <p><?php echo truncate_text(strip_tags($related_service['description']), 80); ?></p>
                        <a href="service-details.php?id=<?php echo $related_service['service_id']; ?>" class="card-link">
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script>
// دوال التحكم في Modal الصورة
function openImageModal(imageSrc, imageAlt) {
    console.log('Opening modal with:', imageSrc, imageAlt); // للتأكد من عمل الدالة
    
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    
    if (!modal || !modalImg || !modalCaption) {
        console.error('Modal elements not found');
        alert('حدث خطأ في تحميل النافذة المنبثقة');
        return;
    }
    
    modalImg.src = imageSrc;
    modalImg.alt = imageAlt;
    modalCaption.textContent = imageAlt;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// انتظار تحميل الصفحة بالكامل
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up modal events');
    
    const modal = document.getElementById('imageModal');
    if (modal) {
        // إغلاق Modal عند النقر خارجه
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
    }

    // إغلاق Modal بمفتاح ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
    
    // إضافة event listener للصورة مباشرة كبديل
    const serviceImage = document.querySelector('.service-image');
    if (serviceImage) {
        console.log('Service image found, adding click listener');
        serviceImage.addEventListener('click', function(e) {
            console.log('Service image clicked');
            // منع النقر المزدوج
            e.preventDefault();
            e.stopPropagation();
            
            const img = this.querySelector('.service-img');
            if (img) {
                console.log('Image found, opening modal');
                openImageModal(img.src, img.alt);
            } else {
                console.error('Image not found in service image container');
            }
        });
    } else {
        console.error('Service image container not found');
    }
    
    // إضافة event listener للصورة نفسها أيضاً
    const serviceImg = document.querySelector('.service-img');
    if (serviceImg) {
        console.log('Service img found, adding click listener');
        serviceImg.addEventListener('click', function(e) {
            console.log('Service img clicked');
            e.preventDefault();
            e.stopPropagation();
            openImageModal(this.src, this.alt);
        });
    }
});

// للتأكد من أن الدوال متاحة عالمياً
window.openImageModal = openImageModal;
window.closeImageModal = closeImageModal;

// دالة اختبار Modal
function testModal() {
    console.log('Test modal function called');
    const testImage = 'assets/images/default-slide.jpg';
    const testAlt = 'صورة تجريبية';
    openImageModal(testImage, testAlt);
}

// إضافة console.log للتأكد من تحميل السكريبت
console.log('Image modal script loaded successfully');
</script>

<style>
/* ===== Service Details Styles ===== */
/* تحديد نطاق الأنماط لصفحة تفاصيل الخدمة فقط */
.service-details {
    padding: 1.5rem 0;
    margin-top: 70px;
    background: #f8f9fa;
    min-height: 100vh;
}

.service-details-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Breadcrumb */
.service-details .breadcrumb-nav {
    margin-bottom: 1.5rem;
}

.service-details .breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.service-details .breadcrumb-item {
    color: #6c757d;
}

.service-details .breadcrumb-item a {
    color: #007bff;
    text-decoration: none;
    transition: color 0.2s ease;
}

.service-details .breadcrumb-item a:hover {
    color: #0056b3;
}

.service-details .breadcrumb-item.active {
    color: #495057;
}

/* Service Content */
.service-details .service-content {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

/* Service Image */
.service-details .service-image {
    width: 100%;
    height: 350px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    transition: all 0.3s ease;
}

.service-details .service-image:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.service-details .service-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
    cursor: pointer;
}

.service-details .service-image:hover .service-img {
    transform: scale(1.02);
}

.service-details .image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    color: white;
    text-align: center;
}

.service-details .service-image:hover .image-overlay {
    opacity: 1;
}

.service-details .image-overlay i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: #fff;
}

.service-details .image-overlay span {
    font-size: 0.9rem;
    font-weight: 500;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

/* Image Modal */
.service-details .image-modal {
    display: none;
    position: fixed;
    z-index: 99999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(5px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.service-details .modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    text-align: center;
}

.service-details .close-modal {
    position: absolute;
    top: -40px;
    right: 0;
    color: #f1f1f1;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
    z-index: 100000;
    transition: color 0.3s ease;
}

.service-details .close-modal:hover {
    color: #bbb;
}

.service-details #modalImage {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.service-details .modal-caption {
    color: white;
    font-size: 1.1rem;
    margin-top: 1rem;
    text-align: center;
    font-weight: 500;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
}

/* Service Info */
.service-details .service-info {
    padding: 1.5rem;
}

.service-details .service-header {
    margin-bottom: 1rem;
}

.service-details .service-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 0.75rem 0;
    line-height: 1.3;
}

.service-details .service-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.service-details .badge {
    padding: 0.4rem 0.8rem;
    border-radius: 16px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.service-details .badge.featured {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.service-details .badge.category {
    background: #e9ecef;
    color: #495057;
    border: 1px solid #dee2e6;
}

.service-details .service-description {
    font-size: 1rem;
    line-height: 1.6;
    color: #495057;
    margin-bottom: 1.5rem;
}

/* Service Specifications */
.service-details .service-specs {
    margin-bottom: 1.5rem;
}

.service-details .service-specs h3 {
    font-size: 1.2rem;
    color: #2c3e50;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
}

.service-details .specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.service-details .spec-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #007bff;
}

.service-details .spec-item i {
    color: #007bff;
    font-size: 1rem;
    width: 16px;
    text-align: center;
}

.service-details .spec-item span {
    font-weight: 500;
    color: #2c3e50;
    font-size: 0.9rem;
}

.service-details .spec-item small {
    margin-right: auto;
    font-size: 0.75rem;
    padding: 0.15rem 0.4rem;
    border-radius: 10px;
}

.service-details .spec-item small.required {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.service-details .spec-item small.optional {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Service Action */
.service-details .service-action {
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-top: 1.5rem;
}

.service-details .btn-order {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #007bff;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.2s ease;
    border: none;
}

.service-details .btn-order:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

.service-details .action-note {
    margin-top: 0.75rem;
    color: #6c757d;
    font-size: 0.85rem;
}

/* Related Services */
.service-details .related-services {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.service-details .related-services h2 {
    text-align: center;
    font-size: 1.5rem;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
}

.service-details .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.service-details .service-card {
    background: white;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
    border: 1px solid #e9ecef;
}

.service-details .service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.service-details .card-image {
    height: 160px;
    overflow: hidden;
}

.service-details .card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s ease;
}

.service-details .service-card:hover .card-image img {
    transform: scale(1.05);
}

.service-details .card-content {
    padding: 1rem;
}

.service-details .card-content h3 {
    font-size: 1.1rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.service-details .card-content p {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 0.75rem;
}

.service-details .card-link {
    display: inline-block;
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: color 0.2s ease;
}

.service-details .card-link:hover {
    color: #0056b3;
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .service-details {
        padding: 1rem 0;
        margin-top: 60px;
    }

    .service-details-container {
        padding: 0 0.75rem;
    }

    .service-details .service-image {
        height: 250px;
    }

    .service-details .service-info {
        padding: 1rem;
    }

    .service-details .service-title {
        font-size: 1.4rem;
    }

    .service-details .specs-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .service-details .spec-item {
        padding: 0.5rem;
    }

    .service-details .services-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .service-details .related-services {
        padding: 1rem;
    }

    .service-details .related-services h2 {
        font-size: 1.3rem;
    }

    .service-details .card-image {
        height: 140px;
    }

    /* Image Modal Responsive */
    .service-details .image-modal {
        padding: 0.5rem;
    }

    .service-details .modal-content {
        max-width: 95%;
    }

    .service-details .close-modal {
        top: -35px;
        font-size: 30px;
    }

    .service-details #modalImage {
        max-height: 70vh;
    }

    .service-details .modal-caption {
        font-size: 1rem;
        margin-top: 0.75rem;
    }
}

@media (max-width: 480px) {
    .service-details {
        padding: 0.5rem 0;
        margin-top: 50px;
    }

    .service-details-container {
        padding: 0 0.5rem;
    }

    .service-details .service-image {
        height: 200px;
    }

    .service-details .service-info {
        padding: 0.75rem;
    }

    .service-details .service-title {
        font-size: 1.2rem;
    }

    .service-details .service-badges {
        flex-direction: column;
        align-items: flex-start;
    }

    .service-details .badge {
        width: fit-content;
    }

    .service-details .btn-order {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        width: 100%;
        justify-content: center;
    }

    .service-details .related-services {
        padding: 0.75rem;
    }

    .service-details .related-services h2 {
        font-size: 1.2rem;
    }

    .service-details .card-content {
        padding: 0.75rem;
    }

    .service-details .card-image {
        height: 120px;
    }

    /* Image Modal Responsive for Small Screens */
    .service-details .image-modal {
        padding: 0.25rem;
    }

    .service-details .modal-content {
        max-width: 98%;
    }

    .service-details .close-modal {
        top: -30px;
        font-size: 25px;
    }

    .service-details #modalImage {
        max-height: 60vh;
    }

    .service-details .modal-caption {
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .service-details .image-overlay i {
        font-size: 1.5rem;
    }

    .service-details .image-overlay span {
        font-size: 0.8rem;
    }
}

@media (max-width: 360px) {
    .service-details .service-image {
        height: 180px;
    }

    .service-details .service-title {
        font-size: 1.1rem;
    }

    .service-details .service-description {
        font-size: 0.9rem;
    }

    .service-details .spec-item {
        flex-direction: column;
        text-align: center;
        gap: 0.25rem;
    }

    .service-details .spec-item small {
        margin-right: 0;
    }

    .service-details .card-image {
        height: 100px;
    }

    /* Image Modal Responsive for Very Small Screens */
    .service-details .image-modal {
        padding: 0.1rem;
    }

    .service-details .modal-content {
        max-width: 99%;
    }

    .service-details .close-modal {
        top: -25px;
        font-size: 20px;
    }

    .service-details #modalImage {
        max-height: 50vh;
    }

    .service-details .modal-caption {
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .service-details .image-overlay i {
        font-size: 1.2rem;
    }

    .service-details .image-overlay span {
        font-size: 0.7rem;
    }
}

/* Print Styles */
@media print {
    .service-details {
        margin-top: 0;
        background: white;
    }

    .service-details .service-content,
    .service-details .related-services {
        box-shadow: none;
        border: 1px solid #ddd;
    }

    .service-details .btn-order,
    .service-details .card-link {
        display: none;
    }
}
</style>

