<?php
/**
 * صفحة من نحن لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على معلومات الفريق
try {
    $team_query = $db->query("
        SELECT * FROM team 
        WHERE status = 'active' 
        ORDER BY order_num ASC
    ");
    $team_members = $team_query->fetchAll();
} catch (PDOException $e) {
    $team_members = [];
}
?>

<!-- رأس الصفحة -->

<section >
    <div class="container">
        <h1 class="page-title">المدونة</h1>
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a>
        </div>
    </div>
</section>
<!-- قسم من نحن -->
<section class="about-section section about-page">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="about-content">
                    <h2 class="section-title">مطبعة برنتلي </h2>
                    <p>
                        تأسست مطبعة برنتلي لتكون واحدة من أبرز المطابع في مصر، حيث نقدم خدمات طباعة متكاملة بأعلى معايير الجودة وبأسعار منافسة.
                    </p>
                    <p>
                        نمتلك فريقاً من المحترفين ذوي الخبرة في مجال التصميم والطباعة، ونستخدم أحدث التقنيات والمعدات لضمان تقديم منتجات طباعية متميزة تلبي احتياجات عملائنا.
                    </p>
                    <p>
                        نؤمن في مطبعة برنتلي بأن الطباعة هي فن وليست مجرد خدمة، لذلك نحرص على الاهتمام بأدق التفاصيل في كل مشروع نقوم به، بدءاً من التصميم وحتى التسليم النهائي.
                    </p>
                    <div class="about-features">
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>جودة عالية</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>أسعار منافسة</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>تسليم سريع</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>خدمة عملاء متميزة</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about-image">
                    <img src="assets/images/logo.png" alt="مطبعة برنتلي">

                </div>
            </div>
        </div>
    </div>
</section>



<!-- قسم قيمنا -->
<section class="values-section section about-page">
    <div class="container">
        <div class="section-intro text-center">
            <h2 class="section-title">قيمنا</h2>
            <p class="section-description">
                نلتزم في مطبعة برنتلي بمجموعة من القيم التي توجه عملنا وتحدد علاقتنا مع عملائنا وشركائنا.
            </p>
        </div>
        
        <div class="values-grid">
            <div class="value-item">
                <div class="value-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <h3>الجودة</h3>
                <p>نلتزم بتقديم منتجات وخدمات بأعلى معايير الجودة، مع الاهتمام بأدق التفاصيل.</p>
            </div>
            
            <div class="value-item">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>النزاهة</h3>
                <p>نتعامل بشفافية ومصداقية مع عملائنا وشركائنا، ونلتزم بالوعود التي نقطعها.</p>
            </div>
            
            <div class="value-item">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>الابتكار</h3>
                <p>نسعى دائماً للتطوير وتبني أحدث التقنيات والأفكار الإبداعية في مجال الطباعة والتصميم.</p>
            </div>
            
            <div class="value-item">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>العمل الجماعي</h3>
                <p>نؤمن بأهمية العمل الجماعي وتضافر الجهود لتحقيق أهدافنا وتقديم أفضل الخدمات لعملائنا.</p>
            </div>
            
            <div class="value-item">
                <div class="value-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>الالتزام بالمواعيد</h3>
                <p>نحترم الوقت ونلتزم بتسليم المشاريع في المواعيد المحددة دون تأخير.</p>
            </div>
            
            <div class="value-item">
                <div class="value-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>المسؤولية البيئية</h3>
                <p>نهتم بالبيئة ونسعى لاستخدام مواد صديقة للبيئة وتقليل النفايات في عملياتنا.</p>
            </div>
        </div>
    </div>
</section>

<!-- قسم فريق العمل -->
<?php if (count($team_members) > 0): ?>
<section class="team-section section bg-light about-page">
    <div class="container">
        <div class="section-intro text-center">
            <h2 class="section-title">فريق العمل</h2>
            <p class="section-description">
                يضم فريقنا نخبة من المحترفين ذوي الخبرة والكفاءة في مجالات الطباعة والتصميم وخدمة العملاء.
            </p>
        </div>
        
        <div class="team-grid">
            <?php foreach ($team_members as $member): ?>
                <div class="team-member">
                    <div class="member-image">
                        <?php if (!empty($member['image'])): ?>
                            <img src="uploads/team/<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                        <?php else: ?>
                            <img src="assets/images/default-avatar.jpg" alt="<?php echo $member['name']; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="member-info">
                        <h3 class="member-name"><?php echo $member['name']; ?></h3>
                        <p class="member-position"><?php echo $member['position']; ?></p>
                        <?php if (!empty($member['bio'])): ?>
                            <p class="member-bio"><?php echo $member['bio']; ?></p>
                        <?php endif; ?>
                        
                        <div class="member-social">
                            <?php if (!empty($member['social_facebook'])): ?>
                                <a href="<?php echo $member['social_facebook']; ?>" target="_blank" class="facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($member['social_twitter'])): ?>
                                <a href="<?php echo $member['social_twitter']; ?>" target="_blank" class="twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($member['social_instagram'])): ?>
                                <a href="<?php echo $member['social_instagram']; ?>" target="_blank" class="instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($member['social_linkedin'])): ?>
                                <a href="<?php echo $member['social_linkedin']; ?>" target="_blank" class="linkedin">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- قسم الإحصائيات -->


<!-- قسم الشركاء -->


<!-- قسم الدعوة للعمل -->
<section class="cta-section section about-page">
    <div class="container">
        <div class="cta-box">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2>هل أنت مستعد لبدء مشروعك الطباعي؟ㅤ</h2>
                    <p>تواصل معنا الآن للحصول على استشارة مجانية وعرض سعر مناسب لمشروعك.</p>
                </div>
                <div class="col-lg-4 text-lg-left text-center mt-4 mt-lg-0">
                    <a href="contact.php" class="btn btn-white">تواصل معنا</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* أنماط صفحة من نحن - النسخة المحسنة */
.about-page {
    direction: rtl;
    text-align: right;
}

/* إضافة متغيرات CSS العامة */
:root {
    --color-gold: #00adef;
    --color-light-gold: rgb(76, 204, 255);
    --color-black: #333;
    --color-white: #fff;
    --color-text-secondary: #666;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 2rem;
    --spacing-xl: 3rem;
    --border-radius: 12px;
    --border-radius-lg: 16px;
    --border-radius-xl: 20px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.about-page .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.about-page .section {
    padding: 80px 0;
    position: relative;
}

.about-page .section-title {
    text-align: center;
    margin-bottom: 40px;
    position: relative;
    padding-bottom: 20px;
    color: var(--color-black);
    font-size: 2.2rem;
    font-weight: 700;
}

.about-page .section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, var(--color-gold), var(--color-light-gold));
}

.about-page .section-description {
    text-align: center;
    max-width: 800px;
    margin: 0 auto 60px;
    color: var(--color-text-secondary);
    line-height: 1.8;
    font-size: 1.1rem;
}

.about-page .about-content {
    margin-bottom: var(--spacing-xl);
    max-width: 800px;
}

.about-page .about-content p {
    margin-bottom: var(--spacing-lg);
    line-height: 1.8;
    font-size: 1.1rem;
    color: var(--color-text-secondary);
}

.about-page .about-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
    margin-top: var(--spacing-xl);
}

.about-page .feature {
    display: flex;
    align-items: center;
    padding: var(--spacing-md);
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: var(--border-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: var(--transition);
}

.about-page .feature:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.about-page .feature i {
    color: var(--color-gold);
    margin-left: var(--spacing-md);
    font-size: 1.5rem;
    min-width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.about-page .about-image {
    border-radius: var(--border-radius-lg);
    overflow: visible;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    position: relative;
    touch-action: none;
    cursor: pointer;
    width: 100%;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 30px;
    min-height: 350px;
}

.about-page .about-image img {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
    display: block;
    transition: transform 0.3s ease;
    will-change: transform;
    transform-style: preserve-3d;
    transform-origin: center center;
    backface-visibility: hidden;
}

.about-page .about-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(212, 175, 55, 0.1), transparent);
    z-index: 1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.about-page .about-image:hover::before,
.about-page .about-image:active::before {
    opacity: 1;
}

.about-page .about-image:hover img {
    transform: scale(1.02);
}

/* تحسين مظهر قسم الرؤية والرسالة */
.about-page .vision-box,
.about-page .mission-box {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-xl);
    height: 100%;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(212, 175, 55, 0.1);
    position: relative;
    overflow: hidden;
    margin-bottom: var(--spacing-lg);
    width: 100%;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.about-page .vision-box::before,
.about-page .mission-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-gold), var(--color-light-gold));
}

.about-page .vision-box:hover,
.about-page .mission-box:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: var(--color-gold);
}

.about-page .vision-box .icon,
.about-page .mission-box .icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-gold), var(--color-light-gold));
    color: var(--color-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: var(--spacing-lg);
    box-shadow: 0 8px 20px rgba(0, 173, 239, 0.3);
    transition: var(--transition);
}

.about-page .vision-box:hover .icon,
.about-page .mission-box:hover .icon {
    transform: scale(1.1) rotate(5deg);
}

.about-page .vision-box h3,
.about-page .mission-box h3 {
    margin-bottom: var(--spacing-md);
    font-size: 1.4rem;
    color: var(--color-black);
}

.about-page .vision-box p,
.about-page .mission-box p {
    line-height: 1.8;
    color: var(--color-text-secondary);
    font-size: 1.05rem;
}

.about-page .vision-mission-section .row {
    display: flex !important;
    justify-content: center !important;
    gap: 100px !important;
}

/* إضافة مسافة إضافية للعناصر داخل الصف */
.about-page .vision-mission-section .col-lg-5 {
    margin: 0 30px !important;
}

@media (max-width: 991px) {
    .about-page .vision-box,
    .about-page .mission-box {
        max-width: 100%;
    }
    
    .about-page .vision-mission-section .row {
        flex-direction: column !important;
        gap: 60px !important;
    }
    
    .about-page .vision-mission-section .col-lg-5 {
        margin: 0 !important;
    }
}

/* قيمنا */
.about-page .values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-xl);
    margin-top: var(--spacing-xl);
}

.about-page .value-item {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-xl);
    text-align: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(212, 175, 55, 0.1);
    position: relative;
    overflow: hidden;
}

.about-page .value-item::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.03) 0%, transparent 70%);
    transition: var(--transition);
    transform: scale(0);
}

.about-page .value-item:hover::before {
    transform: scale(1);
}

.about-page .value-item:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-hover);
    border-color: var(--color-gold);
}

.about-page .value-icon {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-light-gold), rgba(0, 173, 239, 0.8));
    color: var(--color-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto var(--spacing-lg);
    box-shadow: 0 6px 16px rgba(0, 173, 239, 0.2);
    transition: var(--transition);
    position: relative;
    z-index: 1;
}

.about-page .value-item:hover .value-icon {
    transform: scale(1.15) rotate(-5deg);
    box-shadow: 0 10px 24px rgba(0, 173, 239, 0.4);
}

.about-page .value-item h3 {
    margin-bottom: var(--spacing-md);
    font-size: 1.3rem;
    color: var(--color-black);
    position: relative;
    z-index: 1;
}

.about-page .value-item p {
    line-height: 1.7;
    color: var(--color-text-secondary);
    font-size: 1.05rem;
    position: relative;
    z-index: 1;
}

/* فريق العمل */
.about-page .team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-xl);
    margin-top: var(--spacing-xl);
}

.about-page .team-member {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.about-page .team-member:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-hover);
    border-color: var(--color-gold);
}

.about-page .member-image {
    height: 280px;
    overflow: hidden;
    position: relative;
}

.about-page .member-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(212, 175, 55, 0.1), transparent);
    z-index: 1;
    opacity: 0;
    transition: var(--transition);
}

.about-page .team-member:hover .member-image::before {
    opacity: 1;
}

.about-page .member-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.about-page .team-member:hover .member-image img {
    transform: scale(1.08);
}

.about-page .member-info {
    padding: var(--spacing-lg);
    text-align: center;
}

.about-page .member-name {
    margin-bottom: 8px;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--color-black);
}

.about-page .member-position {
    color: var(--color-gold);
    margin-bottom: var(--spacing-md);
    font-weight: 600;
    font-size: 1.05rem;
}

.about-page .member-bio {
    margin-bottom: var(--spacing-md);
    font-size: 0.95rem;
    line-height: 1.7;
    color: var(--color-text-secondary);
}

.about-page .member-social {
    display: flex;
    justify-content: center;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.about-page .member-social a {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-white);
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.about-page .member-social a:hover {
    transform: translateY(-4px) scale(1.1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}

.about-page .member-social .facebook {
    background: linear-gradient(135deg, #3b5998, #4c70ba);
}

.about-page .member-social .twitter {
    background: linear-gradient(135deg, #1da1f2, #40b3f4);
}

.about-page .member-social .instagram {
    background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
}

.about-page .member-social .linkedin {
    background: linear-gradient(135deg, #0077b5, #00a0dc);
}

/* الإحصائيات */
.about-page .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--spacing-xl);
}

.about-page .stat-item {
    text-align: center;
    padding: var(--spacing-lg);
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: var(--border-radius-lg);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.about-page .stat-item:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: var(--color-gold);
}

.about-page .stat-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-light-gold), rgba(0, 173, 239, 0.8));
    color: var(--color-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto var(--spacing-lg);
    box-shadow: 0 8px 20px rgba(0, 173, 239, 0.3);
    transition: var(--transition);
}

.about-page .stat-item:hover .stat-icon {
    transform: scale(1.15) rotate(-10deg);
    box-shadow: 0 12px 28px rgba(0, 173, 239, 0.4);
}

.about-page .stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--color-black);
    margin-bottom: 8px;
    line-height: 1;
}

.about-page .stat-title {
    color: var(--color-text-secondary);
    font-weight: 600;
    font-size: 1.1rem;
}

/* الشركاء */
.about-page .partners-slider {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--spacing-lg);
    margin-top: var(--spacing-xl);
}

.about-page .partner-item {
    width: 180px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: var(--border-radius);
    padding: var(--spacing-md);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.about-page .partner-item:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    border-color: var(--color-gold);
}

.about-page .partner-item img {
    max-width: 100%;
    max-height: 100%;
    filter: grayscale(100%) opacity(0.7);
    transition: var(--transition);
}

.about-page .partner-item:hover img {
    filter: grayscale(0%) opacity(1);
    transform: scale(1.05);
}

/* الدعوة للعمل */
.about-page .cta-box {
    background: linear-gradient(135deg, var(--color-gold), var(--color-light-gold));
    border-radius: var(--border-radius-xl);
    padding: var(--spacing-xl) var(--spacing-lg);
    color: var(--color-white);
    box-shadow: 0 16px 40px rgba(0, 173, 239, 0.3);
    position: relative;
    overflow: hidden;
}

.about-page .cta-box::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: shine 3s ease-in-out infinite;
}

@keyframes shine {
    0%, 100% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1); opacity: 1; }
}

.about-page .cta-box h2 {
    margin-bottom: var(--spacing-md);
    position: relative;
    z-index: 1;
}

.about-page .btn-white {
    background-color: var(--color-white);
    color: var(--color-gold);
    border: 2px solid var(--color-white);
    font-weight: 600;
    padding: 16px 36px;
    border-radius: var(--border-radius);
    transition: var(--transition);
    position: relative;
    z-index: 1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.about-page .btn-white:hover {
    background-color: transparent;
    color: var(--color-white);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* تجاوب محسن */
@media (max-width: 1200px) {
    .about-page .values-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
    
    .about-page .team-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 991px) {
    .about-page .about-image {
        margin-top: var(--spacing-lg);
    }
    
    .about-page .about-features {
        grid-template-columns: 1fr;
    }
    
    .about-page .values-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--spacing-lg);
    }
}

@media (max-width: 767px) {
    .about-page .values-grid,
    .about-page .team-grid,
    .about-page .stats-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-lg);
    }
    
    .about-page .partner-item {
        width: 140px;
        height: 100px;
    }
    
    .about-page .stat-number {
        font-size: 2.5rem;
    }
    
    .about-page .stat-icon {
        width: 75px;
        height: 75px;
        font-size: 2rem;
    }
    
    .about-page .vision-box .icon,
    .about-page .mission-box .icon {
        width: 70px;
        height: 70px;
        font-size: 1.8rem;
    }
    
    .about-page .value-icon {
        width: 65px;
        height: 65px;
        font-size: 1.6rem;
    }
    
    .about-page .member-image {
        height: 250px;
    }
    
    .about-page .cta-box {
        padding: var(--spacing-lg);
    }
}

@media (max-width: 480px) {
    .about-page .about-features {
        grid-template-columns: 1fr;
    }
    
    .about-page .feature {
        flex-direction: column;
        text-align: center;
        padding: var(--spacing-lg);
    }
    
    .about-page .feature i {
        margin-left: 0;
        margin-bottom: var(--spacing-sm);
    }
    
    .about-page .partner-item {
        width: 120px;
        height: 90px;
    }
}
</style>

<script>
// تحريك الأرقام في قسم الإحصائيات - النسخة المحسنة
document.addEventListener('DOMContentLoaded', function() {
    const stats = document.querySelectorAll('.stat-number');
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -50px 0px'
    };
    
    // استخدام Intersection Observer للأداء الأفضل
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                animateNumber(entry.target);
            }
        });
    }, observerOptions);
    
    // مراقبة جميع عناصر الإحصائيات
    stats.forEach(stat => {
        observer.observe(stat);
    });
    
    // دالة تحريك الأرقام المحسنة
    function animateNumber(element) {
        const countTo = parseInt(element.getAttribute('data-count'));
        const duration = 2500;
        const startTime = performance.now();
        
        function updateCount(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // استخدام easing function للحصول على تأثير أكثر سلاسة
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentCount = Math.floor(easeOutQuart * countTo);
            
            element.textContent = currentCount;
            
            if (progress < 1) {
                requestAnimationFrame(updateCount);
            } else {
                element.textContent = countTo;
                element.classList.add('animated');
            }
        }
        
        requestAnimationFrame(updateCount);
    }
    
    // تأثيرات تفاعلية إضافية
    const teamMembers = document.querySelectorAll('.team-member');
    const valueItems = document.querySelectorAll('.value-item');
    
    // تأثير parallax وتوجيه الجهاز للصور
    const aboutImage = document.querySelector('.about-image img');
    let lastGamma = 0;
    let lastBeta = 0;
    
    // التحقق من دعم توجيه الجهاز
    if (window.DeviceOrientationEvent) {
        window.addEventListener('deviceorientation', handleOrientation, true);
    }
    
    function handleOrientation(event) {
        if (!event.gamma || !event.beta) return;
        
        // تنعيم الحركة باستخدام متوسط القيم السابقة
        const gamma = event.gamma * 0.1 + lastGamma * 0.9; // الميل يميناً ويساراً
        const beta = event.beta * 0.1 + lastBeta * 0.9;   // الميل للأمام وللخلف
        
        // حدود الحركة
        const maxTilt = 15;
        const tiltX = Math.max(-maxTilt, Math.min(maxTilt, gamma));
        const tiltY = Math.max(-maxTilt, Math.min(maxTilt, beta));
        
        if (aboutImage) {
            aboutImage.style.transform = `
                perspective(1000px)
                rotateX(${-tiltY * 0.5}deg)
                rotateY(${tiltX * 0.5}deg)
                scale(1.1)
            `;
        }
        
        lastGamma = gamma;
        lastBeta = beta;
    }
    
    // للأجهزة التي تستخدم الماوس
    window.addEventListener('mousemove', (e) => {
        const { clientX, clientY } = e;
        const { innerWidth, innerHeight } = window;
        
        const tiltX = (clientX / innerWidth - 0.5) * 30;
        const tiltY = (clientY / innerHeight - 0.5) * 30;
        
        if (aboutImage) {
            aboutImage.style.transform = `
                perspective(1000px)
                rotateX(${-tiltY * 0.5}deg)
                rotateY(${tiltX * 0.5}deg)
                scale(1.1)
            `;
        }
    });        // تأثير hover وtouch متقدم للبطاقات
    [...teamMembers, ...valueItems].forEach(card => {
        // معالجة أحداث الماوس
        card.addEventListener('mouseenter', handleInteraction);
        
        // معالجة أحداث اللمس
        card.addEventListener('touchstart', handleInteraction, { passive: true });
        card.addEventListener('touchmove', handleInteraction, { passive: true });
        
        function handleInteraction(e) {
            const rect = card.getBoundingClientRect();
            let x, y;
            
            if (e.type.startsWith('touch')) {
                // للأجهزة التي تعمل باللمس
                const touch = e.touches[0];
                x = touch.clientX - rect.left;
                y = touch.clientY - rect.top;
            } else {
                // للأجهزة التي تعمل بالماوس
                x = e.clientX - rect.left;
                y = e.clientY - rect.top;
            }
            
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        }
    });
});

// تحسين الأداء باستخدام throttling للأحداث
function throttle(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
