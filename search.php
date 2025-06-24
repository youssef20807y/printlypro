<?php
/**
 * صفحة البحث عن الخدمات
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على كلمة البحث
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// تحليل البحث
$search_analysis = [
    'query_length' => mb_strlen($search_query),
    'query_words' => explode(' ', $search_query),
    'is_valid' => true,
    'error_type' => null,
    'error_details' => null
];

// البحث في قاعدة البيانات
$services = [];
if (!empty($search_query)) {
    try {
        // التحقق من طول كلمة البحث
        if ($search_analysis['query_length'] < 2) {
            $search_analysis['is_valid'] = false;
            $search_analysis['error_type'] = 'query_too_short';
        }
        
        // التحقق من عدد الكلمات
        if (count($search_analysis['query_words']) > 5) {
            $search_analysis['is_valid'] = false;
            $search_analysis['error_type'] = 'too_many_words';
        }

        if ($search_analysis['is_valid']) {
            $stmt = $db->prepare("
                SELECT s.*, c.name as category_name 
                FROM services s 
                LEFT JOIN categories c ON s.category_id = c.category_id 
                WHERE (s.name LIKE :query1 OR s.description LIKE :query2 OR c.name LIKE :query3)
                AND s.status = 'active'
                ORDER BY s.name ASC
            ");
            $stmt->execute([
                'query1' => "%{$search_query}%",
                'query2' => "%{$search_query}%",
                'query3' => "%{$search_query}%"
            ]);
            $services = $stmt->fetchAll();
            
            // تحليل النتائج
            $search_analysis['total_results'] = count($services);
            $search_analysis['search_fields'] = ['name', 'description', 'category_name'];
        }
    } catch (PDOException $e) {
        $search_analysis['is_valid'] = false;
        $search_analysis['error_type'] = 'database_error';
        $search_analysis['error_details'] = [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'sql_state' => $e->errorInfo[0] ?? null,
            'driver_code' => $e->errorInfo[1] ?? null,
            'driver_message' => $e->errorInfo[2] ?? null
        ];
    }
}
?>

<!-- رأس الصفحة -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">نتائج البحث</h1>
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a> / نتائج البحث
        </div>
    </div>
</section>

<!-- قسم نتائج البحث -->
<section class="search-results section">
    <div class="container">
        <?php if (!empty($search_query)): ?>
            <h2 class="section-title">نتائج البحث عن "<?= htmlspecialchars($search_query) ?>"</h2>
            
            <?php if (count($services) > 0): ?>
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-image">
                                <a href="service-details.php?id=<?= $service['service_id'] ?>">
                                <?php if (!empty($service['image']) && file_exists('uploads/services/' . $service['image'])): ?>
                                    <img src="uploads/services/<?= htmlspecialchars($service['image']) ?>" alt="<?= htmlspecialchars($service['name']) ?>">
                                <?php else: ?>
                                    <img src="assets/images/service-placeholder.jpg" alt="<?= htmlspecialchars($service['name']) ?>">
                                <?php endif; ?>
                                </a>
                            </div>
                            <div class="service-content">
                                <h3 class="service-title">
                                    <a href="service-details.php?id=<?= $service['service_id'] ?>">
                                        <?= htmlspecialchars($service['name']) ?>
                                    </a>
                                </h3>
                                <div class="service-description">
                                    <?= strip_tags($service['description'], '<p><strong><b><em><i><br>') ?>
                                </div>
                                <div class="service-price">
                                    <?php if ($service['price_start'] > 0): ?>
                                        <span class="price">تبدأ من <?= htmlspecialchars($service['price_start']) ?> جنيه</span>
                                    <?php else: ?>
                                        <span class="price text-muted">السعر يحدد لاحقاً</span>
                                    <?php endif; ?>
                                </div>
                                <div class="service-actions" style="display: flex; justify-content: center;">
                                    <a href="order.php?service_id=<?= $service['service_id'] ?>" class="btn btn-gold btn-order-center">اطلب الآن</a>
                                    <!-- <a href="service-details.php?id=<?= $service['service_id'] ?>" class="btn btn-secondary">التفاصيل</a> -->
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results text-center">
                    <p class="lead">عذراً، لم يتم العثور على أي نتائج تطابق بحثك "<?= htmlspecialchars($search_query) ?>".</p>
                    
                    <?php if (!$search_analysis['is_valid']): ?>
                        <div class="search-error">
                            <p>سبب عدم وجود نتائج:</p>
                            <?php
                            switch($search_analysis['error_type']) {
                                case 'query_too_short':
                                    echo '<p class="error-message">كلمة البحث قصيرة جداً. يجب أن تكون كلمة البحث على الأقل حرفين.</p>';
                                    break;
                                case 'too_many_words':
                                    echo '<p class="error-message">عدد كلمات البحث كبير جداً. يرجى استخدام 5 كلمات كحد أقصى.</p>';
                                    break;
                                case 'database_error':
                                    echo '<div class="error-details">';
                                    echo '<p class="error-message">تفاصيل خطأ قاعدة البيانات:</p>';
                                    echo '<ul class="error-list">';
                                    echo '<li>رمز الخطأ: ' . htmlspecialchars($search_analysis['error_details']['code']) . '</li>';
                                    echo '<li>حالة SQL: ' . htmlspecialchars($search_analysis['error_details']['sql_state']) . '</li>';
                                    echo '<li>رمز الخطأ في قاعدة البيانات: ' . htmlspecialchars($search_analysis['error_details']['driver_code']) . '</li>';
                                    echo '<li>رسالة الخطأ: ' . htmlspecialchars($search_analysis['error_details']['driver_message']) . '</li>';
                                    echo '</ul>';
                                    echo '<p class="error-solution">الحلول المقترحة:</p>';
                                    echo '<ul class="solution-list">';
                                    echo '<li>تأكد من صحة كلمة البحث</li>';
                                    echo '<li>حاول البحث بكلمات مختلفة</li>';
                                    echo '<li>إذا استمرت المشكلة، يرجى التواصل مع الدعم الفني</li>';
                                    echo '</ul>';
                                    echo '</div>';
                                    break;
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="search-analysis">
                            <p>تحليل البحث:</p>
                            <ul class="search-details">
                                <li>طول كلمة البحث: <?= $search_analysis['query_length'] ?> حرف</li>
                                <li>عدد كلمات البحث: <?= count($search_analysis['query_words']) ?> كلمة</li>
                                <li>تم البحث في: <?= implode('، ', $search_analysis['search_fields']) ?></li>
                                <li>عدد النتائج: <?= $search_analysis['total_results'] ?> نتيجة</li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <p>اقتراحات للبحث:</p>
                    <ul class="search-suggestions">
                        <li>تأكد من صحة كتابة كلمة البحث</li>
                        <li>جرب استخدام كلمات بحث مختلفة</li>
                        <li>استخدم كلمات بحث أقصر أو أكثر عمومية</li>
                        <li>يمكنك تصفح جميع خدماتنا المتاحة</li>
                    </ul>
                    <a href="services.php" class="btn btn-gold mt-4">تصفح جميع الخدمات</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results text-center">
                <p class="lead">يرجى إدخال كلمة بحث للبدء.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* أنماط نتائج البحث */
.search-results {
    padding: var(--spacing-xl) 0;
}

.no-results {
    padding: var(--spacing-xl) 0;
    text-align: center;
}

.no-results .lead {
    font-size: 1.2rem;
    color: var(--color-dark-gray);
    margin-bottom: var(--spacing-md);
}

.search-details,
.search-suggestions {
    list-style: none;
    padding: 0;
    margin: var(--spacing-md) 0;
    text-align: right;
}

.search-details li,
.search-suggestions li {
    margin-bottom: var(--spacing-sm);
    color: var(--color-gray);
    font-size: 0.95rem;
}

.search-details li:before {
    content: "•";
    color: var(--color-gold);
    margin-left: var(--spacing-sm);
}

.search-suggestions li:before {
    content: "→";
    color: var(--color-gold);
    margin-left: var(--spacing-sm);
}

.mt-4 {
    margin-top: var(--spacing-lg);
}

.search-error {
    background-color: #fff3f3;
    border: 1px solid #ffcdd2;
    border-radius: 4px;
    padding: var(--spacing-md);
    margin: var(--spacing-md) 0;
}

.error-message {
    color: #d32f2f;
    font-weight: bold;
    margin-bottom: var(--spacing-sm);
}

.error-details {
    background-color: #fff;
    border: 1px solid #ffcdd2;
    border-radius: 4px;
    padding: var(--spacing-md);
    margin-top: var(--spacing-sm);
}

.error-list {
    list-style: none;
    padding: 0;
    margin: var(--spacing-sm) 0;
}

.error-list li {
    color: #d32f2f;
    margin-bottom: var(--spacing-xs);
    font-size: 0.9rem;
}

.error-solution {
    color: #1976d2;
    font-weight: bold;
    margin-top: var(--spacing-md);
}

.solution-list {
    list-style: none;
    padding: 0;
    margin: var(--spacing-sm) 0;
}

.solution-list li {
    color: #1976d2;
    margin-bottom: var(--spacing-xs);
    font-size: 0.9rem;
}

.solution-list li:before {
    content: "→";
    margin-left: var(--spacing-sm);
    color: #1976d2;
}

.search-analysis {
    background-color: #f5f5f5;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: var(--spacing-md);
    margin: var(--spacing-md) 0;
}

.btn-order-center {
    display: block;
    width: 100%;
    max-width: 300px;
    margin: 20px auto 0 auto;
    font-weight: bold;
    font-size: 1.15rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 173, 239, 0.15);
}
</style>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?> 