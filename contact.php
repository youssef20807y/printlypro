<?php
/**
 * صفحة تواصل معنا لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// معالجة النموذج عند الإرسال
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من البيانات المدخلة
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
    if (empty($name)) {
        $errors[] = 'يرجى إدخال الاسم';
    }
    
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    if (empty($email)) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صحيح';
    }
    
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    
    $subject = isset($_POST['subject']) ? clean_input($_POST['subject']) : '';
    if (empty($subject)) {
        $errors[] = 'يرجى إدخال الموضوع';
    }
    
    $message = isset($_POST['message']) ? clean_input($_POST['message']) : '';
    if (empty($message)) {
        $errors[] = 'يرجى إدخال الرسالة';
    }
    
    // إذا لم يكن هناك أخطاء، نقوم بإضافة الرسالة
    if (empty($errors)) {
        try {
            // إضافة الرسالة إلى قاعدة البيانات
            $stmt = $db->prepare("
                INSERT INTO messages (name, email, phone, subject, message, status)
                VALUES (?, ?, ?, ?, ?, 'new')
            ");
            
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            // إرسال بريد إلكتروني بالرسالة
            $to = ADMIN_EMAIL;
            $mail_subject = "رسالة جديدة من موقع برنتلي: " . $subject;
            
            $mail_message = "
                <html>
                <head>
                    <title>رسالة جديدة</title>
                </head>
                <body>
                    <h2>رسالة جديدة من موقع برنتلي</h2>
                    <p><strong>الاسم:</strong> {$name}</p>
                    <p><strong>البريد الإلكتروني:</strong> {$email}</p>
                    <p><strong>رقم الهاتف:</strong> {$phone}</p>
                    <p><strong>الموضوع:</strong> {$subject}</p>
                    <p><strong>الرسالة:</strong></p>
                    <p>{$message}</p>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: موقع مطبعة برنتلي <' . $email . '>' . "\r\n";
            
            // إرسال البريد (معطل في بيئة التطوير)
            // mail($to, $mail_subject, $mail_message, $headers);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة مرة أخرى.';
        }
    }
}

// الحصول على بيانات التواصل من الإعدادات
$contact_phone = get_setting('site_phone', '+20 100 288 9688');
$contact_email = get_setting('site_email', 'printly@gmail.com');
$contact_address = get_setting('site_address', 'دمياط : شارع وزير - بجوار مسجد تقي الدين');
?>

<!-- رأس الصفحة -->
<section>
    <div class="container">
        <h1 class="page-title">ㅤ ㅤ</h1>
        <div class="breadcrumb">
            <a href="index.php">ㅤ</a>ㅤ
        </div>
    </div>
</section>

<!-- قسم معلومات التواصل -->
<section class="contact-info-section section py-5 mt-4">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title mb-3 text-center">معلومات التواصل</h2>
            <p class="section-subtitle mx-auto text-center" style="max-width: 600px; margin: 0 auto; display: block;">تواصل معنا عبر أي من القنوات التالية</p>
        </div>
        
        <div class="contact-info-grid">
            <div class="contact-info-item" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-icon-wrapper">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                </div>
                <div class="contact-info-content">
                    <h3 class="info-title">العنوان</h3>
                    <p class="info-text"><?php echo $contact_address; ?></p>
                </div>
            </div>
            
            <div class="contact-info-item" data-aos="fade-up" data-aos-delay="200">
                <div class="contact-icon-wrapper">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                </div>
                <div class="contact-info-content">
                    <h3 class="info-title">رقم الهاتف</h3>
                    <p class="info-text">
                        <a href="tel:<?php echo $contact_phone; ?>" class="contact-link"><?php echo $contact_phone; ?></a>
                    </p>
                </div>
            </div>
            
            <div class="contact-info-item" data-aos="fade-up" data-aos-delay="300">
                <div class="contact-icon-wrapper">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="contact-info-content">
                    <h3 class="info-title">البريد الإلكتروني</h3>
                    <p class="info-text">
                        <a href="mailto:<?php echo $contact_email; ?>" class="contact-link"><?php echo $contact_email; ?></a>
                    </p>
                </div>
            </div>
            
            <div class="contact-info-item" data-aos="fade-up" data-aos-delay="400">
                <div class="contact-icon-wrapper">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="contact-info-content">
                    <h3 class="info-title">ساعات العمل</h3>
                    <div class="working-hours">
                        <p class="info-text text-center" style="text-align: center !important; display: block; width: 100%;">السبت -الخميس</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم نموذج التواصل والخريطة -->
<section class="contact-form-section section py-5">
    <div class="container">
        <div class="contact-form-wrapper">
                    <div class="form-header text-center mb-4">
                        <h2 class="section-title">أرسل لنا رسالة</h2>
                        <p class="form-subtitle">يسعدنا تواصلكم معنا في أي وقت</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="success-message text-center">
                            <div class="success-icon mb-3">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                            <h3 class="mb-3">تم إرسال رسالتك بنجاح!</h3>
                            <p class="text-muted">شكراً لتواصلك معنا. سنقوم بالرد عليك في أقرب وقت ممكن.</p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="contact.php" method="post" id="contact-form" class="contact-form text-center">
                            <div class="form-fields d-flex flex-column align-items-center">
                                <div class="form-field w-100" style="max-width: 500px;">
                                    <div class="form-floating">
                                        <input type="text" name="name" id="name" class="form-control custom-input" placeholder=" " required>
                                        <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    </div>
                                </div>

                                <div class="form-field w-100" style="max-width: 500px;">
                                    <div class="form-floating">
                                        <input type="email" name="email" id="email" class="form-control custom-input" placeholder=" " required>
                                        <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    </div>
                                </div>

                                <div class="form-field w-100" style="max-width: 500px;">
                                    <div class="form-floating">
                                        <input type="tel" name="phone" id="phone" class="form-control custom-input" placeholder=" ">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                    </div>
                                </div>

                                <div class="form-field w-100" style="max-width: 500px;">
                                    <div class="form-floating">
                                        <input type="text" name="subject" id="subject" class="form-control custom-input" placeholder=" " required>
                                        <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                                    </div>
                                </div>

                                <div class="form-field w-100" style="max-width: 500px;">
                                    <div class="form-floating">
                                        <textarea name="message" id="message" class="form-control custom-input message-input" 
                                                placeholder=" " required></textarea>
                                        <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions text-center">
                                <button type="submit" class="btn btn-gold btn-lg px-5">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    إرسال الرسالة
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم الأسئلة الشائعة -->
<section class="faq-section py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">الأسئلة الشائعة</h2>
            <p class="section-subtitle">إجابات على الأسئلة الأكثر شيوعاً من عملائنا</p>
        </div>
        
        <div class="faq-container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>ما هي مواعيد عمل المطبعة؟</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>نستقبل عملائنا من السبت إلى الخميس، من الساعة 9 صباحاً حتى 3 صباحاً. نغلق أبوابنا يوم الجمعة للصيانة والراحة الأسبوعية. نرجو تنسيق مواعيد الطلبات العاجلة مسبقاً.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>كيف يمكنني مناقشة تفاصيل مشروع الطباعة؟</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>نوفر عدة خيارات للتواصل: يمكنك زيارة مقر المطبعة خلال ساعات العمل للقاء فريقنا الفني، أو حجز موعد استشارة عبر الهاتف، أو إرسال تفاصيل مشروعك عبر البريد الإلكتروني. سيقوم مستشارونا بدراسة متطلباتك وتقديم أفضل الحلول المناسبة.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>كم يستغرق الرد على الاستفسارات والطلبات؟</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>نلتزم بالرد على جميع الاستفسارات خلال ساعتين كحد أقصى خلال أوقات العمل. للطلبات العاجلة، يمكنك الاتصال مباشرة برقم خدمة العملاء. نؤكد حرصنا على تقديم خدمة سريعة ودقيقة لجميع عملائنا.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>ما هي وسائل الدفع المتاحة لديكم؟</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>نوفر مجموعة متنوعة من طرق الدفع لراحة عملائنا: الدفع النقدي عند الاستلام، التحويل البنكي المباشر، بطاقات الائتمان والدفع الإلكتروني في المقر. للمشاريع الكبيرة، نقدم خيار الدفع على دفعات بعد الاتفاق المسبق.</p>
                        </div>
                    </div>
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
            <a href="services.php" class="btn btn-secondary">استكشف خدماتنا</a>
        </div>
    </div>
</section>

<style>
.faq-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 3rem 0;
}

.faq-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    margin-bottom: 12px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #fff;
    position: relative;
}

.faq-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, var(--color-gold), #00adef);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.faq-item.active::before {
    transform: scaleY(1);
}

.faq-question {
    padding: 16px 20px;
    background: #fff;
    color: var(--color-black);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
    position: relative;
}

.faq-question:hover {
    background: #f8f9fa;
}

.faq-question h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-black);
    line-height: 1.4;
    padding-right: 15px;
    flex: 1;
}

.faq-toggle {
    background: linear-gradient(135deg, var(--color-gold), #00adef);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(218, 165, 32, 0.2);
}

.faq-toggle i {
    color: white;
    font-size: 0.8rem;
    transition: transform 0.3s ease;
    font-weight: bold;
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: #fafbfc;
    padding: 0 20px;
    border-top: 1px solid transparent;
}

.faq-item.active {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.faq-item.active .faq-question {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom-color: #e9ecef;
}

.faq-item.active .faq-toggle {
    background: linear-gradient(135deg, #00adef, var(--color-gold));
    transform: rotate(180deg);
    box-shadow: 0 4px 12px rgba(0, 173, 239, 0.3);
}

.faq-item.active .faq-answer {
    max-height: 300px;
    padding: 16px 20px;
    border-top-color: #e9ecef;
}

.faq-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.faq-question:hover .faq-toggle {
    transform: scale(1.05);
    box-shadow: 0 3px 8px rgba(218, 165, 32, 0.3);
}

.faq-answer p {
    margin: 0;
    color: #6c757d;
    font-size: 0.95rem;
    line-height: 1.6;
    text-align: justify;
}

/* تحسينات للأجهزة المحمولة */
@media (max-width: 768px) {
    .faq-section {
        padding: 2rem 0;
    }
    
    .faq-container {
        max-width: 100%;
        padding: 0 1rem;
    }
    
    .faq-item {
        margin-bottom: 10px;
        border-radius: 10px;
    }
    
    .faq-question {
        padding: 14px 16px;
    }
    
    .faq-question h3 {
        font-size: 0.95rem;
        padding-right: 12px;
    }
    
    .faq-toggle {
        width: 26px;
        height: 26px;
    }
    
    .faq-toggle i {
        font-size: 0.75rem;
    }
    
    .faq-item.active .faq-answer {
        padding: 14px 16px;
        max-height: 400px;
    }
    
    .faq-answer p {
        font-size: 0.9rem;
        line-height: 1.5;
    }
}

@media (max-width: 480px) {
    .faq-question {
        padding: 12px 14px;
    }
    
    .faq-question h3 {
        font-size: 0.9rem;
        padding-right: 10px;
    }
    
    .faq-toggle {
        width: 24px;
        height: 24px;
    }
    
    .faq-toggle i {
        font-size: 0.7rem;
    }
    
    .faq-item.active .faq-answer {
        padding: 12px 14px;
    }
    
    .faq-answer p {
        font-size: 0.85rem;
    }
}

/* تأثيرات إضافية */
@keyframes slideDown {
    from { 
        opacity: 0; 
        transform: translateY(-8px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.faq-item.active .faq-answer {
    animation: slideDown 0.3s ease forwards;
}

/* تأثير النبض للأيقونة */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.faq-question:hover .faq-toggle {
    animation: pulse 1s ease-in-out;
}

/* تحسين مظهر العنوان */
.faq-section .section-title {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--color-black);
}

.faq-section .section-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 2.5rem;
}

@media (max-width: 768px) {
    .faq-section .section-title {
        font-size: 1.6rem;
    }
    
    .faq-section .section-subtitle {
        font-size: 1rem;
        margin-bottom: 2rem;
    }
}

.contact-info-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 1.5rem;
    padding: 2rem 0;
    max-width: 1200px;
    margin: 0 auto;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
    scroll-snap-type: x proximity;
    will-change: scroll-position;
}

.contact-info-grid::-webkit-scrollbar {
    display: none; /* Chrome, Safari and Opera */
}

.contact-info-item {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 2rem 1.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    min-width: 250px;
    flex-shrink: 0;
    scroll-snap-align: center;
    will-change: transform;
}

.contact-info-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-gold), #00adef);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.contact-info-item:hover::before {
    transform: scaleX(1);
}

.contact-info-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--color-gold);
}

.contact-icon-wrapper {
    margin-bottom: 1.5rem;
    position: relative;
}

.contact-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    position: relative;
    background: linear-gradient(135deg, var(--color-gold), #00adef);
    box-shadow: 0 8px 20px rgba(218, 165, 32, 0.3);
    transition: all 0.3s ease;
}

.contact-icon i {
    font-size: 2rem;
    color: white;
    transition: all 0.3s ease;
}

.contact-info-item:hover .contact-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 12px 30px rgba(218, 165, 32, 0.4);
}

.contact-info-item:hover .contact-icon i {
    transform: scale(1.1);
}

.contact-info-content {
    position: relative;
    z-index: 2;
}

.info-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--color-black);
    margin-bottom: 1rem;
    position: relative;
}

.info-title::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 2px;
    background: linear-gradient(90deg, var(--color-gold), #00adef);
    transition: width 0.3s ease;
}

.contact-info-item:hover .info-title::after {
    width: 60px;
}

.info-text {
    color: #6c757d;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
    transition: color 0.3s ease;
}

.contact-link {
    color: var(--color-gold);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
}

.contact-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--color-gold);
    transition: width 0.3s ease;
}

.contact-link:hover {
    color: #00adef;
}

.contact-link:hover::after {
    width: 100%;
}

.working-hours {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.working-hours .info-text {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.working-hours .info-text:last-child {
    border-bottom: none;
}

.working-hours .info-text span {
    font-weight: 600;
    color: var(--color-gold);
}

/* تحسينات للأجهزة المحمولة */
@media (max-width: 768px) {
    .contact-info-grid {
        gap: 0.8rem;
        padding: 1rem 0.5rem;
        scroll-snap-type: x mandatory;
        margin: 0 -0.5rem;
        scroll-padding: 0 1rem;
    }
    
    .contact-info-item {
        padding: 1.2rem 0.8rem;
        min-width: 180px;
        max-width: 200px;
        scroll-snap-align: center;
        border-radius: 15px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .contact-info-item:active {
        transform: scale(0.98);
    }
    
    .contact-icon-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
    }
    
    .contact-icon {
        margin: 0 auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .contact-icon i {
        font-size: 1.3rem;
    }
    
    .contact-info-content {
        text-align: center;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .info-title {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
        text-align: center;
    }
    
    .info-text {
        font-size: 0.8rem;
        line-height: 1.4;
        text-align: center;
    }
    
    .contact-link {
        font-size: 0.8rem;
        text-align: center;
    }
    
    .working-hours {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .working-hours .info-text {
        font-size: 0.75rem;
        padding: 0.3rem 0;
        text-align: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .working-hours .info-text span {
        font-size: 0.75rem;
    }
    
    /* تحسين الأداء على الأجهزة المحمولة */
    .contact-info-item:hover {
        transform: translateY(-5px);
    }
    
    .contact-info-item:hover .contact-icon {
        transform: scale(1.05);
    }
}

/* تحسينات إضافية للشاشات الصغيرة جداً */
@media (max-width: 480px) {
    .contact-info-grid {
        gap: 0.6rem;
        padding: 0.8rem 0.3rem;
    }
    
    .contact-info-item {
        padding: 1rem 0.6rem;
        min-width: 160px;
        max-width: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .contact-icon-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
    }
    
    .contact-icon {
        margin: 0 auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .contact-icon i {
        font-size: 1.2rem;
    }
    
    .contact-info-content {
        text-align: center;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .info-title {
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
        text-align: center;
    }
    
    .info-text {
        font-size: 0.75rem;
        text-align: center;
    }
    
    .contact-link {
        font-size: 0.75rem;
        text-align: center;
    }
    
    .working-hours {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .working-hours .info-text {
        font-size: 0.7rem;
        text-align: center;
        justify-content: center;
        gap: 0.4rem;
    }
    
    .working-hours .info-text span {
        font-size: 0.7rem;
    }
}
@media (max-width: 768px) {
  .contact-icon {
    width: 50px !important;
    height: 50px !important;
  }
}
@media (max-width: 480px) {
  .contact-icon {
    width: 45px !important;
    height: 45px !important;
  }
}
/* تأثيرات إضافية */
.contact-info-item:nth-child(1) .contact-icon {
    background: linear-gradient(135deg, #00adef, #0097e6);
}

.contact-info-item:nth-child(2) .contact-icon {
    background: linear-gradient(135deg, #00adef, #0097e6);
}

.contact-info-item:nth-child(3) .contact-icon {
    background: linear-gradient(135deg, #00adef, #0097e6);
}

.contact-info-item:nth-child(4) .contact-icon {
    background: linear-gradient(135deg, #00adef, #0097e6);
}

/* تأثير النبض للأيقونات */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.contact-info-item:hover .contact-icon {
    animation: pulse 2s infinite;
}

/* تأثير الظل المتحرك */
@keyframes shadowMove {
    0% { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
    50% { box-shadow: 0 12px 40px rgba(218, 165, 32, 0.2); }
    100% { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
}

.contact-info-item:hover {
    animation: shadowMove 2s ease-in-out infinite;
}

/* تحسين تأثيرات النص */
.info-text {
    position: relative;
    z-index: 3;
}

.contact-link {
    position: relative;
    z-index: 3;
}

/* تأثير الإضاءة الخلفية */
.contact-icon::before {
    content: '';
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    background: inherit;
    border-radius: 50%;
    opacity: 0;
    filter: blur(10px);
    transition: all 0.3s ease;
}

.contact-info-item:hover .contact-icon::before {
    opacity: 0;
    filter: blur(15px);
}

.contact-form-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.contact-form-wrapper {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.form-header {
    margin-bottom: 40px;
}

.form-subtitle {
    color: #6c757d;
}

/* تحسينات نموذج الاتصال */
.form-fields {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
    width: 100%;
}

.form-field {
    position: relative;
    transition: all 0.3s ease;
}

.form-field:last-child {
    grid-column: 1 / -1;
}

.form-floating {
    display: flex;
    flex-direction: column;
}

.form-floating .form-label {
    order: -1;
    margin-bottom: 0.5rem;
    color: #495057;
    font-weight: 500;
}

.custom-input {
    height: 58px;
    width: 100%;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    transition: all 0.3s ease;
    background-color: #fff;
}

.message-input {
    height: 150px;
    resize: none;
}

.form-label {
    padding: 0.5rem;
    margin-right: 0.5rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.form-floating > .custom-input:focus,
.form-floating > .custom-input:not(:placeholder-shown) {
    border-color: var(--color-gold);
    box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.1);
}

.form-floating > .custom-input:focus ~ label {
    color: var(--color-gold);
}

.form-floating > .custom-input:focus {
    border-color: var(--color-gold);
    box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.1);
}

.form-actions {
    margin-top: 2rem;
}

.form-actions .btn-gold {
    min-width: 200px;
    padding: 1rem 2rem;
}

@media (max-width: 768px) {
    .form-fields {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .custom-input {
        height: 52px;
    }
    
    .message-input {
        height: 120px;
    }
    
    .form-field:last-child {
        grid-column: 1;
    }
}

/* تحسين مظهر الحقول عند التركيز */
/* تنسيق رسائل الخطأ */
.alert-danger {
    background-color: #fff5f5;
    border-color: #fed7d7;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
}

.alert-danger ul {
    padding-right: 1.5rem;
}

/* تأكيد توسيط نص ساعات العمل */
.working-hours .info-text.text-center {
    text-align: center !important;
    display: block !important;
    width: 100% !important;
    margin: 0 auto !important;
}
</style>

<script>
// تفعيل وظائف الأسئلة الشائعة
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const toggle = item.querySelector('.faq-toggle i');
        
        question.addEventListener('click', () => {
            // إغلاق جميع الإجابات الأخرى
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.faq-toggle i').classList.remove('fa-minus');
                    otherItem.querySelector('.faq-toggle i').classList.add('fa-plus');
                }
            });
            
            // تبديل حالة السؤال الحالي
            item.classList.toggle('active');
            if (item.classList.contains('active')) {
                toggle.classList.remove('fa-plus');
                toggle.classList.add('fa-minus');
            } else {
                toggle.classList.remove('fa-minus');
                toggle.classList.add('fa-plus');
            }
        });
    });
    
    // إضافة تأثيرات إضافية لقسم معلومات التواصل
    const contactItems = document.querySelectorAll('.contact-info-item');
    
    contactItems.forEach((item, index) => {
        // إضافة تأخير للظهور
        item.style.animationDelay = `${index * 0.1}s`;
        
        // إضافة تأثير النقر
        item.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
});
</script>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
