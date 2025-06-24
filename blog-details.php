<?php
/**
 * صفحة تفاصيل المقال لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// التحقق من وجود معرف المقال
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: blog.php');
    exit;
}

$slug = $_GET['slug'];

// الحصول على تفاصيل المقال
try {
    $post_query = $db->prepare("
        SELECT b.*, u.username as author_name 
        FROM blog b 
        LEFT JOIN users u ON b.author_id = u.user_id 
        WHERE b.slug = ? AND b.status = 'published'
    ");
    $post_query->execute([$slug]);
    $post = $post_query->fetch();
    
    if (!$post) {
        header('Location: blog.php');
        exit;
    }
    
    // زيادة عدد المشاهدات
    $update_views = $db->prepare("UPDATE blog SET views = views + 1 WHERE blog_id = ?");
    $update_views->execute([$post['blog_id']]);
    
    // الحصول على المقالات ذات الصلة
    $related_query = $db->prepare("
        SELECT blog_id, title, slug, image, created_at 
        FROM blog 
        WHERE status = 'published' 
        AND blog_id != ? 
        AND (category = ? OR category IS NULL)
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $related_query->execute([$post['blog_id'], $post['category']]);
    $related_posts = $related_query->fetchAll();
    
    // الحصول على أحدث المقالات للشريط الجانبي
    $recent_posts_query = $db->query("
        SELECT blog_id, title, slug, created_at 
        FROM blog 
        WHERE status = 'published' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_posts = $recent_posts_query->fetchAll();
    
    // الحصول على التصنيفات
    $categories_query = $db->query("
        SELECT category, COUNT(*) as count 
        FROM blog 
        WHERE status = 'published' AND category IS NOT NULL AND category != ''
        GROUP BY category 
        ORDER BY count DESC
    ");
    $categories = $categories_query->fetchAll();
} catch (PDOException $e) {
    // في حالة حدوث خطأ، إعادة التوجيه إلى صفحة المدونة
    header('Location: blog.php');
    exit;
}
?>

<!-- رأس الصفحة -->
<section class="page-header" style="background-image: url('assets/images/blog-header.jpg');">
    <div class="container">
        <h1 class="page-title">المدونة</h1>
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a> / <a href="blog.php">المدونة</a> / <?php echo $post['title']; ?>
        </div>
    </div>
</section>

<!-- قسم تفاصيل المقال -->
<section class="blog-details-section section">
    <div class="container">
        <div class="row">
            <!-- محتوى المقال -->
            <div class="col-lg-8">
                <div class="blog-details">
                    <div class="blog-header">
                        <h1 class="blog-title"><?php echo $post['title']; ?></h1>
                        <div class="blog-meta">
                            <span class="blog-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                            </span>
                            <?php if (!empty($post['author_name'])): ?>
                                <span class="blog-author">
                                    <i class="far fa-user"></i>
                                    <?php echo $post['author_name']; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($post['category'])): ?>
                                <span class="blog-category">
                                    <i class="far fa-folder"></i>
                                    <?php echo $post['category']; ?>
                                </span>
                            <?php endif; ?>
                            <span class="blog-views">
                                <i class="far fa-eye"></i>
                                <?php echo $post['views']; ?> مشاهدة
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($post['image'])): ?>
                        <div class="blog-featured-image">
                            <img src="uploads/blog/<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="blog-content">
                        <?php echo $post['content']; ?>
                    </div>
                    
                    <?php if (!empty($post['tags'])): ?>
                        <div class="blog-tags">
                            <h4>الوسوم:</h4>
                            <div class="tags">
                                <?php 
                                $tags = explode(',', $post['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <a href="blog.php?tag=<?php echo urlencode($tag); ?>" class="tag"><?php echo $tag; ?></a>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- مشاركة المقال -->
                    <div class="blog-share">
                        <h4>مشاركة المقال:</h4>
                        <div class="social-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" class="twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . ' - https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($post['title']); ?>" target="_blank" class="linkedin">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- المقالات ذات الصلة -->
                    <?php if (count($related_posts) > 0): ?>
                        <div class="related-posts">
                            <h3>مقالات ذات صلة</h3>
                            <div class="related-posts-grid">
                                <?php foreach ($related_posts as $related): ?>
                                    <div class="related-post-card">
                                        <div class="related-post-image">
                                            <?php if (!empty($related['image'])): ?>
                                                <img src="uploads/blog/<?php echo $related['image']; ?>" alt="<?php echo $related['title']; ?>">
                                            <?php else: ?>
                                                <img src="assets/images/default-blog.jpg" alt="<?php echo $related['title']; ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-post-content">
                                            <h4 class="related-post-title">
                                                <a href="blog-details.php?slug=<?php echo $related['slug']; ?>">
                                                    <?php echo $related['title']; ?>
                                                </a>
                                            </h4>
                                            <div class="related-post-date">
                                                <i class="far fa-calendar-alt"></i>
                                                <?php echo date('d/m/Y', strtotime($related['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- الشريط الجانبي -->
            <div class="col-lg-4">
                <div class="sidebar">
                    <!-- البحث -->
                    <div class="sidebar-widget search-widget">
                        <h4 class="widget-title">بحث</h4>
                        <form action="blog.php" method="get" class="search-form">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="ابحث هنا..." class="form-control">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- أحدث المقالات -->
                    <div class="sidebar-widget recent-posts-widget">
                        <h4 class="widget-title">أحدث المقالات</h4>
                        <div class="recent-posts">
                            <?php if (count($recent_posts) > 0): ?>
                                <?php foreach ($recent_posts as $recent): ?>
                                    <div class="recent-post-item">
                                        <h5 class="recent-post-title">
                                            <a href="blog-details.php?slug=<?php echo $recent['slug']; ?>">
                                                <?php echo $recent['title']; ?>
                                            </a>
                                        </h5>
                                        <div class="recent-post-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($recent['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>لا توجد مقالات حديثة</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- التصنيفات -->
                    <div class="sidebar-widget categories-widget">
                        <h4 class="widget-title">التصنيفات</h4>
                        <ul class="categories-list">
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $category): ?>
                                    <li>
                                        <a href="blog.php?category=<?php echo urlencode($category['category']); ?>">
                                            <?php echo $category['category']; ?>
                                            <span class="count">(<?php echo $category['count']; ?>)</span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>لا توجد تصنيفات</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* أنماط صفحة تفاصيل المقال */
.blog-details {
    background-color: var(--color-white);
    border-radius: 8px;
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: var(--spacing-xl);
}

.blog-header {
    margin-bottom: var(--spacing-lg);
}

.blog-title {
    font-size: 2rem;
    margin-bottom: var(--spacing-md);
}

.blog-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
    color: var(--color-dark-gray);
}

.blog-meta span {
    display: inline-flex;
    align-items: center;
}

.blog-meta i {
    margin-left: 5px;
}

.blog-featured-image {
    margin-bottom: var(--spacing-lg);
    border-radius: 8px;
    overflow: hidden;
}

.blog-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.blog-content {
    line-height: 1.8;
    margin-bottom: var(--spacing-lg);
}

.blog-content p {
    margin-bottom: var(--spacing-md);
}

.blog-content h2,
.blog-content h3,
.blog-content h4 {
    margin-top: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
}

.blog-content img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: var(--spacing-md) 0;
}

.blog-content ul,
.blog-content ol {
    margin-bottom: var(--spacing-md);
    padding-right: var(--spacing-lg);
}

.blog-content li {
    margin-bottom: var(--spacing-sm);
}

.blog-content blockquote {
    border-right: 4px solid var(--color-gold);
    padding: var(--spacing-md);
    background-color: var(--color-light-gray);
    margin: var(--spacing-lg) 0;
    font-style: italic;
}

/* الوسوم */
.blog-tags {
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-light-gray);
}

.blog-tags h4 {
    margin-bottom: var(--spacing-sm);
}

.tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.tag {
    display: inline-block;
    padding: 5px 15px;
    background-color: var(--color-light-gray);
    border-radius: 20px;
    color: var(--color-dark-gray);
    text-decoration: none;
    transition: all 0.3s ease;
}

.tag:hover {
    background-color: var(--color-gold);
    color: var(--color-white);
}

/* مشاركة المقال */
.blog-share {
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-light-gray);
}

.blog-share h4 {
    margin-bottom: var(--spacing-sm);
}

.social-share {
    display: flex;
    gap: var(--spacing-sm);
}

.social-share a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: var(--color-white);
    text-decoration: none;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.social-share a:hover {
    transform: translateY(-3px);
    opacity: 0.9;
}

.social-share .facebook {
    background-color: #3b5998;
}

.social-share .twitter {
    background-color: #1da1f2;
}

.social-share .whatsapp {
    background-color: #25d366;
}

.social-share .linkedin {
    background-color: #0077b5;
}

/* المقالات ذات الصلة */
.related-posts {
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--color-light-gray);
}

.related-posts h3 {
    margin-bottom: var(--spacing-lg);
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-md);
}

.related-post-card {
    background-color: var(--color-light-gray);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.related-post-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.related-post-image {
    height: 150px;
    overflow: hidden;
}

.related-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.related-post-card:hover .related-post-image img {
    transform: scale(1.05);
}

.related-post-content {
    padding: var(--spacing-sm);
}

.related-post-title {
    font-size: 1rem;
    margin-bottom: 5px;
}

.related-post-title a {
    color: var(--color-black);
    text-decoration: none;
    transition: color 0.3s ease;
}

.related-post-title a:hover {
    color: var(--color-gold);
}

.related-post-date {
    font-size: 0.85rem;
    color: var(--color-dark-gray);
}

/* الشريط الجانبي */
.sidebar {
    position: sticky;
    top: 20px;
}

.sidebar-widget {
    background-color: var(--color-white);
    border-radius: 8px;
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
}

.widget-title {
    font-size: 1.25rem;
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-gold);
}

/* البحث */
.search-form {
    position: relative;
}

.search-form .form-control {
    padding-left: 40px;
    height: 50px;
    border-radius: 4px;
}

.search-btn {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 40px;
    background: none;
    border: none;
    color: var(--color-dark-gray);
    cursor: pointer;
    transition: color 0.3s ease;
}

.search-btn:hover {
    color: var(--color-gold);
}

/* أحدث المقالات */
.recent-post-item {
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--color-light-gray);
}

.recent-post-item:last-child {
    border-bottom: none;
}

.recent-post-title {
    font-size: 1rem;
    margin-bottom: 5px;
}

.recent-post-title a {
    color: var(--color-black);
    text-decoration: none;
    transition: color 0.3s ease;
}

.recent-post-title a:hover {
    color: var(--color-gold);
}

.recent-post-date {
    font-size: 0.85rem;
    color: var(--color-dark-gray);
}

/* التصنيفات */
.categories-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.categories-list li {
    padding: 8px 0;
    border-bottom: 1px solid var(--color-light-gray);
}

.categories-list li:last-child {
    border-bottom: none;
}

.categories-list a {
    display: flex;
    justify-content: space-between;
    color: var(--color-black);
    text-decoration: none;
    transition: color 0.3s ease;
}

.categories-list a:hover {
    color: var(--color-gold);
}

.categories-list .count {
    color: var(--color-dark-gray);
}

/* تجاوب */
@media (max-width: 991px) {
    .sidebar {
        margin-top: var(--spacing-xl);
    }
}

@media (max-width: 767px) {
    .related-posts-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-lg);
    }
    
    .blog-title {
        font-size: 1.5rem;
    }
    
    .blog-meta {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>

