<?php
/**
 * صفحة الأسئلة الشائعة لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على الأسئلة الشائعة
try {
    $faq_query = $db->query("
        SELECT * FROM faq 
        WHERE status = 'active' 
        ORDER BY category, order_num ASC
    ");
    $faqs = $faq_query->fetchAll();
    
    // تنظيم الأسئلة حسب التصنيف
    $faq_categories = [];
    foreach ($faqs as $faq) {
        $category = !empty($faq['category']) ? $faq['category'] : 'عام';
        if (!isset($faq_categories[$category])) {
            $faq_categories[$category] = [];
        }
        $faq_categories[$category][] = $faq;
    }
} catch (PDOException $e) {
    $faq_categories = [];
}
?>

<!-- رأس الصفحة -->
<section class="page-header" style="background-image: url('assets/images/faq-header.jpg');">
    <div class="container">
        <h1 class="page-title">الأسئلة الشائعة</h1>
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a> / الأسئلة الشائعة
        </div>
    </div>
</section>

<!-- قسم الأسئلة الشائعة -->
<section class="faq-section section">
    <div class="container">
        <div class="section-intro text-center">
            <h2 class="section-title">الأسئلة الشائعة</h2>
            <p class="section-description">
                نقدم لكم إجابات على الأسئلة الأكثر شيوعاً حول خدماتنا ومنتجاتنا. إذا لم تجد إجابة لسؤالك، يرجى التواصل معنا مباشرة.
            </p>
        </div>
        
        <?php if (count($faq_categories) > 0): ?>
            <!-- تبويبات التصنيفات -->
            <div class="faq-tabs">
                <ul class="nav nav-tabs" id="faqTabs" role="tablist">
                    <?php $first_tab = true; ?>
                    <?php foreach ($faq_categories as $category => $items): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $first_tab ? 'active' : ''; ?>" 
                               id="<?php echo sanitize_id($category); ?>-tab" 
                               data-toggle="tab" 
                               href="#<?php echo sanitize_id($category); ?>" 
                               role="tab" 
                               aria-controls="<?php echo sanitize_id($category); ?>" 
                               aria-selected="<?php echo $first_tab ? 'true' : 'false'; ?>">
                                <?php echo $category; ?>
                            </a>
                        </li>
                        <?php $first_tab = false; ?>
                    <?php endforeach; ?>
                </ul>
                
                <div class="tab-content" id="faqTabsContent">
                    <?php $first_tab = true; ?>
                    <?php foreach ($faq_categories as $category => $items): ?>
                        <div class="tab-pane fade <?php echo $first_tab ? 'show active' : ''; ?>" 
                             id="<?php echo sanitize_id($category); ?>" 
                             role="tabpanel" 
                             aria-labelledby="<?php echo sanitize_id($category); ?>-tab">
                            
                            <div class="accordion" id="accordion-<?php echo sanitize_id($category); ?>">
                                <?php foreach ($items as $index => $faq): ?>
                                    <div class="accordion-item">
                                        <h3 class="accordion-header" id="heading-<?php echo $faq['faq_id']; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                                    type="button" 
                                                    data-toggle="collapse" 
                                                    data-target="#collapse-<?php echo $faq['faq_id']; ?>" 
                                                    aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                                    aria-controls="collapse-<?php echo $faq['faq_id']; ?>">
                                                <?php echo $faq['question']; ?>
                                            </button>
                                        </h3>
                                        <div id="collapse-<?php echo $faq['faq_id']; ?>" 
                                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                             aria-labelledby="heading-<?php echo $faq['faq_id']; ?>" 
                                             data-parent="#accordion-<?php echo sanitize_id($category); ?>">
                                            <div class="accordion-body">
                                                <?php echo $faq['answer']; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $first_tab = false; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- أسئلة افتراضية في حالة عدم وجود أسئلة في قاعدة البيانات -->
            <div class="accordion" id="accordion-default">
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading-1">
                        <button class="accordion-button" type="button" data-toggle="collapse" data-target="#collapse-1" aria-expanded="true" aria-controls="collapse-1">
                            ما هي خدمات الطباعة التي تقدمونها؟
                        </button>
                    </h3>
                    <div id="collapse-1" class="accordion-collapse collapse show" aria-labelledby="heading-1" data-parent="#accordion-default">
                        <div class="accordion-body">
                            <p>نقدم مجموعة واسعة من خدمات الطباعة تشمل طباعة الكروت الشخصية، البروشورات، الفلايرات، الرول أب، اللوحات الإعلانية، الأختام، الملصقات، المغلفات، الأكياس الورقية، والمزيد من المطبوعات التجارية والشخصية.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading-2">
                        <button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse-2" aria-expanded="false" aria-controls="collapse-2">
                            كم تستغرق عملية الطباعة من الوقت؟
                        </button>
                    </h3>
                    <div id="collapse-2" class="accordion-collapse collapse" aria-labelledby="heading-2" data-parent="#accordion-default">
                        <div class="accordion-body">
                            <p>تختلف مدة الطباعة حسب نوع المنتج وكميته. بشكل عام، تستغرق الطباعة من 1-3 أيام عمل للطلبات العادية، ونوفر خدمة الطباعة السريعة خلال 24 ساعة لبعض المنتجات مقابل رسوم إضافية.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading-3">
                        <button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse-3" aria-expanded="false" aria-controls="collapse-3">
                            هل تقدمون خدمات التصميم؟
                        </button>
                    </h3>
                    <div id="collapse-3" class="accordion-collapse collapse" aria-labelledby="heading-3" data-parent="#accordion-default">
                        <div class="accordion-body">
                            <p>نعم، نقدم خدمات تصميم احترافية لجميع أنواع المطبوعات. يمكنك التواصل مع فريق التصميم لدينا لمناقشة أفكارك واحتياجاتك، وسنقوم بتصميم مطبوعات تناسب هويتك التجارية وتحقق أهدافك.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading-4">
                        <button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse-4" aria-expanded="false" aria-controls="collapse-4">
                            ما هي صيغ الملفات المقبولة للطباعة؟
                        </button>
                    </h3>
                    <div id="collapse-4" class="accordion-collapse collapse" aria-labelledby="heading-4" data-parent="#accordion-default">
                        <div class="accordion-body">
                            <p>نقبل معظم صيغ الملفات الشائعة مثل PDF و AI و PSD و JPEG و PNG. للحصول على أفضل جودة طباعة، نوصي بتقديم ملفات بصيغة PDF عالية الدقة (300 DPI على الأقل) مع هوامش آمنة وألوان CMYK.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading-5">
                        <button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse-5" aria-expanded="false" aria-controls="collapse-5">
                            هل توفرون خدمة التوصيل؟
                        </button>
                    </h3>
                    <div id="collapse-5" class="accordion-collapse collapse" aria-labelledby="heading-5" data-parent="#accordion-default">
                        <div class="accordion-body">
                            <p>نعم، نوفر خدمة توصيل لجميع أنحاء مصر. تختلف رسوم التوصيل حسب الموقع ووزن الشحنة. كما يمكنك استلام طلبك مباشرة من مقر المطبعة.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h3 class="accordion-header" id="heading-6">
                        <button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse-6" aria-expanded="false" aria-controls="collapse-6">
                            ما هي طرق الدفع المتاحة؟
                        </button>
                    </h3>
                    <div id="collapse-6" class="accordion-collapse collapse" aria-labelledby="heading-6" data-parent="#accordion-default">
                        <div class="accordion-body">
                            <p>نقبل الدفع نقداً عند الاستلام، والتحويل البنكي، وبطاقات الائتمان (فيزا وماستركارد)، ومدى، وآبل باي. للطلبات الكبيرة، نطلب دفعة مقدمة بنسبة 50% من قيمة الطلب.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- قسم لم تجد إجابة؟ -->
        <div class="faq-cta">
            <h3>لم تجد إجابة لسؤالك؟</h3>
            <p>يمكنك التواصل معنا مباشرة وسنقوم بالرد على استفسارك في أقرب وقت ممكن.</p>
            <a href="contact.php" class="btn btn-gold">تواصل معنا</a>
        </div>
    </div>
</section>

<style>
/* أنماط صفحة الأسئلة الشائعة */
.section-intro {
    margin-bottom: var(--spacing-xl);
}

.section-description {
    max-width: 600px;
    margin: 0 auto;
}

/* تبويبات التصنيفات */
.faq-tabs {
    margin-bottom: var(--spacing-xl);
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.nav-tabs {
    border-bottom: 2px solid var(--color-light-gray);
    margin-bottom: var(--spacing-lg);
    display: flex;
    flex-wrap: wrap;
}

.nav-tabs .nav-item {
    margin-bottom: -2px;
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--color-dark-gray);
    font-weight: 600;
    padding: var(--spacing-sm) var(--spacing-md);
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    color: var(--color-gold);
}

.nav-tabs .nav-link.active {
    color: var(--color-gold);
    border-bottom: 2px solid var(--color-gold);
}

/* الأكورديون */
.accordion {
    margin-bottom: var(--spacing-lg);
}

.accordion-item {
    border: 1px solid var(--color-light-gray);
    border-radius: 8px;
    margin-bottom: var(--spacing-md);
    overflow: hidden;
}

.accordion-header {
    margin: 0;
}

.accordion-button {
    background-color: var(--color-white);
    color: var(--color-black);
    font-weight: 600;
    font-size: 1.1rem;
    padding: var(--spacing-md);
    width: 100%;
    text-align: right;
    border: none;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.accordion-button:hover {
    background-color: var(--color-light-gray);
}

.accordion-button:focus {
    outline: none;
}

.accordion-button::after {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    left: var(--spacing-md);
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
}

.accordion-button:not(.collapsed)::after {
    transform: translateY(-50%) rotate(180deg);
}

.accordion-body {
    padding: var(--spacing-md);
    background-color: var(--color-white);
    border-top: 1px solid var(--color-light-gray);
    line-height: 1.6;
}

/* قسم CTA */
.faq-cta {
    background-color: var(--color-light-gray);
    border-radius: 8px;
    padding: var(--spacing-lg);
    text-align: center;
    margin-top: var(--spacing-xl);
}

.faq-cta h3 {
    margin-bottom: var(--spacing-sm);
}

.faq-cta p {
    margin-bottom: var(--spacing-md);
}

/* تجاوب */
@media (max-width: 767px) {
    .nav-tabs {
        flex-direction: column;
    }
    
    .nav-tabs .nav-item {
        margin-bottom: 5px;
    }
    
    .nav-tabs .nav-link {
        border: 1px solid var(--color-light-gray);
        border-radius: 4px;
    }
    
    .nav-tabs .nav-link.active {
        background-color: var(--color-gold);
        color: var(--color-white);
        border-color: var(--color-gold);
    }
}
</style>

<?php
/**
 * دالة لتنظيف النص وجعله صالحًا للاستخدام كمعرف
 * 
 * @param string $text النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
function sanitize_id($text) {
    // استبدال الحروف العربية بحروف إنجليزية
    $arabic_map = [
        'أ' => 'a', 'إ' => 'a', 'آ' => 'a', 'ا' => 'a',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th',
        'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
        'د' => 'd', 'ذ' => 'th',
        'ر' => 'r', 'ز' => 'z',
        'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd',
        'ط' => 't', 'ظ' => 'z',
        'ع' => 'a', 'غ' => 'gh',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k',
        'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a', 'ئ' => 'e',
        'ء' => 'a', 'ؤ' => 'o'
    ];
    
    foreach ($arabic_map as $ar => $en) {
        $text = str_replace($ar, $en, $text);
    }
    
    // استبدال المسافات والأحرف الخاصة بشرطات
    $text = preg_replace('/[^a-z0-9]/', '-', strtolower($text));
    
    // إزالة الشرطات المتكررة
    $text = preg_replace('/-+/', '-', $text);
    
    // إزالة الشرطات من البداية والنهاية
    $text = trim($text, '-');
    
    return $text;
}

// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>

