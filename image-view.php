<?php
/**
 * صفحة عرض الصورة المكبرة
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// التحقق من وجود معرف الصورة
if (!isset($_GET['image']) || empty($_GET['image'])) {
    header('Location: portfolio.php');
    exit;
}

$image_path = 'uploads/portfolio/' . basename($_GET['image']);
$image_title = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : '';

// التحقق من وجود الصورة
if (!file_exists($image_path)) {
    header('Location: portfolio.php');
    exit;
}
?>
<section>
    <div class="container">
        <h1 class="page-title">ㅤ ㅤ</h1>
        <div class="breadcrumb">
            <a href="index.php">ㅤ</a>ㅤ
        </div>
    </div>
</section>
<section class="image-view-section section">
    <div class="container">
        <div class="image-view-container">
            <div class="image-view-header">
                <a href="portfolio.php" class="back-button">
                    <i class="fas fa-arrow-right"></i>
                    العودة إلى معرض الأعمال
                </a>
                <?php if ($image_title): ?>
                    <h1 class="image-title"><?php echo $image_title; ?></h1>
                <?php endif; ?>
            </div>
            <div class="image-view-content">
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo $image_title; ?>" class="enlarged-image">
            </div>
        </div>
    </div>
</section>

<style>
.image-view-section {
    padding: var(--spacing-xl) 0;
    background-color: var(--color-light-gray);
    min-height: calc(100vh - 200px);
}

.image-view-container {
    background-color: var(--color-white);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.image-view-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.back-button {
    display: inline-flex;
    align-items: center;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition-fast);
}

.back-button i {
    margin-right: var(--spacing-xs);
}

.back-button:hover {
    color: var(--color-gold);
}

.image-title {
    font-size: 1.8rem;
    color: var(--color-dark);
    margin: 0;
}

.image-view-content {
    padding: var(--spacing-lg);
    text-align: center;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.enlarged-image {
    max-width: 100%;
    max-height: 80vh;
    height: auto;
    border-radius: 8px;
    box-shadow: var(--shadow-md);
    object-fit: contain;
}

@media (max-width: 768px) {
    .image-view-header {
        flex-direction: column;
        gap: var(--spacing-md);
        text-align: center;
    }
    
    .image-title {
        font-size: 1.5rem;
    }
    
    .image-view-content {
        min-height: 300px;
    }
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?> 