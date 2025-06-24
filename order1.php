<?php
/**
 * صفحة طلب خدمة لموقع مطبعة برنتلي
 */

define('PRINTLY', true);
define('DESIGNS_PATH', __DIR__ . '/uploads/designs/');

// إنشاء مجلد التصميمات إذا لم يكن موجوداً
if (!file_exists(DESIGNS_PATH)) {
    mkdir(DESIGNS_PATH, 0777, true);
}

// التحقق من وجود معرف الخدمة في الرابط
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$selected_service = null;

// إذا لم يكن هناك معرف خدمة، أو الخدمة غير موجودة/نشطة، أعد التوجيه
if ($service_id <= 0) {
    require_once 'includes/config.php';
    redirect('services.php');
}

// جلب بيانات الخدمة ومتطلباتها
require_once 'includes/config.php';
$stmt = $db->prepare("SELECT * FROM services WHERE service_id = ? AND status = 'active'");
$stmt->execute([$service_id]);
$selected_service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$selected_service) {
    // الخدمة غير موجودة أو غير نشطة
    redirect('services.php');
}

// تحويل قيم المتطلبات إلى boolean لتسهيل الاستخدام
$selected_service['require_quantity'] = !empty($selected_service['require_quantity']);
$selected_service['require_paper_type'] = !empty($selected_service['require_paper_type']);
$selected_service['require_size'] = !empty($selected_service['require_size']);
$selected_service['require_colors'] = !empty($selected_service['require_colors']);
$selected_service['require_design_file'] = !empty($selected_service['require_design_file']);
$selected_service['require_notes'] = !empty($selected_service['require_notes']);

// الحصول على جميع الخدمات النشطة
$services_query = $db->query("SELECT service_id, name FROM services WHERE status = 'active' ORDER BY name ASC");
$services = $services_query->fetchAll();

// الحصول على أنواع الورق والمقاسات المتاحة
$paper_types = ['عادي', 'مقوى', 'لامع', 'مطفي', 'كوشيه', 'كرافت', 'استيكر'];
$sizes = ['A3', 'A4', 'A5', 'A6', 'مخصص'];

// معالجة النموذج عند الإرسال
$errors = [];
$success = false;
$order_number = null;
$price = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استعادة بيانات النموذج
    $form_data = $_POST;

    // التحقق من البيانات المدخلة بناءً على متطلبات الخدمة
    $quantity = isset($form_data['quantity']) ? intval($form_data['quantity']) : 0;
    if ($quantity <= 0) {
        $errors[] = 'يرجى إدخال كمية صحيحة';
    }

    $paper_type = isset($form_data['paper_type']) ? clean_input($form_data['paper_type']) : '';
    $size = isset($form_data['size']) ? clean_input($form_data['size']) : '';
    $colors = isset($form_data['colors']) ? intval($form_data['colors']) : 0;
    $notes = isset($form_data['notes']) ? clean_input($form_data['notes']) : '';

    // معالجة ملفات التصميم
    $design_file = '';
    if (isset($_FILES['design_file']) && is_array($_FILES['design_file']['name'])) {
        $allowed_types = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'application/postscript',
            'application/vnd.adobe.illustrator',
            'application/vnd.adobe.photoshop',
            'application/vnd.adobe.indesign',
            'application/vnd.corel-draw',
            'application/x-photoshop',
            'application/x-illustrator',
            'application/x-indesign',
            'application/x-coreldraw',
            'application/x-quarkxpress',
            'application/x-pagemaker',
            'application/x-freehand',
            'application/x-eps',
            'application/x-ai',
            'application/x-psd',
            'application/x-cdr',
            'application/x-qxd',
            'application/x-pmd',
            'application/x-fh',
            'application/x-tiff',
            'image/tiff',
            'image/bmp',
            'image/x-bmp',
            'image/x-ms-bmp',
            'image/x-windows-bmp',
            'image/x-portable-pixmap',
            'image/x-portable-graymap',
            'image/x-portable-bitmap',
            'image/x-portable-anymap',
            'image/x-xbitmap',
            'image/x-xpixmap',
            'image/x-xwindowdump',
            'image/x-rgb',
            'image/x-xbm',
            'image/x-xpm',
            'image/x-pcx',
            'image/x-pict',
            'image/x-quicktime',
            'image/x-sgi',
            'image/x-tga',
            'image/x-icns',
            'image/x-portable-anyformat',
            'image/x-portable-floatmap',
            'image/x-portable-greymap',
            'image/x-portable-pixmap',
            'image/x-portable-bitmap',
            'image/x-portable-anymap',
            'image/x-portable-floatmap',
            'image/x-portable-greymap',
            'image/x-portable-pixmap',
            'image/x-portable-bitmap',
            'image/x-portable-anymap'
        ];
        $max_size = 1536 * 1024 * 1024; // 1.5 جيجابايت

        $design_files = [];
        $design_file_uploaded = false;

        foreach ($_FILES['design_file']['name'] as $index => $file_name) {
            if (empty($file_name)) continue;
            
            $file_type = $_FILES['design_file']['type'][$index];
            $file_size = $_FILES['design_file']['size'][$index];
            $file_tmp = $_FILES['design_file']['tmp_name'][$index];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // التحقق من نوع الملف
            $is_valid_type = false;
            
            // التحقق من نوع MIME
            if (in_array($file_type, $allowed_types)) {
                $is_valid_type = true;
            }
            
            // التحقق من امتداد الملف
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ai', 'psd', 'eps', 'cdr', 'qxd', 'pmd', 'fh', 'tiff', 'bmp', 'pict', 'pcx', 'tga'];
            if (in_array($file_extension, $allowed_extensions)) {
                $is_valid_type = true;
            }

            // إذا كان نوع الملف غير معروف، نتحقق من محتوى الملف
            if (!$is_valid_type) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file_tmp);
                finfo_close($finfo);
                
                if (in_array($mime_type, $allowed_types)) {
                    $is_valid_type = true;
                }
            }

            if ($is_valid_type && $file_size <= $max_size) {
                $new_file_name = uniqid() . '_' . basename($file_name);
                $destination = DESIGNS_PATH . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $design_files[] = $new_file_name;
                    $design_file_uploaded = true;
                    
                    // تسجيل معلومات الملف للتصحيح
                    error_log("تم رفع الملف بنجاح: " . $file_name . " (نوع MIME: " . $file_type . ", امتداد: " . $file_extension . ")");
                } else {
                    error_log("فشل في رفع الملف: " . $file_name . " (نوع MIME: " . $file_type . ", امتداد: " . $file_extension . ")");
                    $errors[] = 'فشل في رفع الملف: ' . $file_name;
                }
            } else {
                error_log("نوع ملف غير مدعوم: " . $file_name . " (نوع MIME: " . $file_type . ", امتداد: " . $file_extension . ")");
                $errors[] = 'نوع ملف غير مدعوم: ' . $file_name;
            }
        }

        if ($selected_service['require_design_file'] && !$design_file_uploaded) {
            $errors[] = 'يرجى رفع ملف التصميم';
        }

        // حفظ أسماء الملفات كسلسلة نصية مفصولة بفواصل
        $design_file = implode(',', $design_files);
    }

    // إضافة الطلب إلى السلة
    if (empty($errors)) {
        // حساب السعر الأساسي للخدمة
        $base_price = $selected_service['price_start'] ?? 0;
        $calculated_price = $base_price * $quantity;

        // إنشاء معرف جلسة للمستخدمين الضيوف
        $session_id = session_id();
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;

        try {
            // إدراج العنصر في جدول cart
            $stmt = $db->prepare("INSERT INTO cart (user_id, session_id, service_id, quantity, paper_type, size, colors, design_file, notes, price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $user_id,
                $session_id,
                $service_id,
                $quantity,
                $paper_type,
                $size,
                $colors,
                $design_file,
                $notes,
                $calculated_price
            ]);

            // إعادة التوجيه إلى صفحة السلة
            redirect('cart.php');
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء إضافة المنتج إلى السلة';
        }
    }
}

require_once 'includes/header.php';

?>

<section class="order-section section">
    <div class="container" style="min-height: 100vh; margin-top: 120px; padding: 30px 40px; background: white; border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.05);">
        <?php if (!isset($_GET['payment_success']) && !$success): ?>
            <div class="row justify-content-center pt-4 mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title mb-3">طلب خدمة "<?php echo htmlspecialchars($selected_service['name']); ?>"</h2>
                    <p class="section-description text-muted">
                        يرجى ملء الحقول المطلوبة أدناه لإكمال طلبك. الحقول المعلمة بـ <span class="required">*</span> إلزامية لهذه الخدمة.
                    </p>
                </div>
            </div>
        <?php endif; ?>
        <div style="display: flex; align-items: center; justify-content: center; flex: 1;">
        <?php if (isset($_GET['payment_success'])): ?>
            <div class="success-message text-center card shadow-lg p-5 border-0 mx-auto" style="max-width: 600px;">
                <div class="success-icon mb-4" style="font-size: 5rem; color: var(--color-success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="mb-3 text-success">تم استلام طلبك وإثبات الدفع بنجاح!</h2>
                <p class="lead text-muted">شكراً لطلبك من مطبعة برنتلي. سنقوم بمراجعة الدفع والبدء في تنفيذ طلبك قريباً.</p>
                <div class="order-details my-4 p-4 bg-light rounded-3 border">
                    <p class="mb-2"><strong>حالة الطلب:</strong> <span class="badge bg-info">قيد المراجعة</span></p>
                    <p class="mb-2"><strong>حالة الدفع:</strong> <span class="badge bg-success">تم استلام إثبات الدفع</span></p>
                </div>
                <p>يمكنك متابعة حالة طلبك من خلال <a href="account.php" class="text-decoration-none">حسابك الشخصي</a>.</p>
                <div class="actions mt-4">
                    <a href="index.php" class="btn btn-outline-secondary me-2 px-4">العودة للرئيسية</a>
                    <a href="services.php" class="btn btn-gold px-4">استكشاف خدمات أخرى</a>
                </div>
            </div>
        <?php elseif ($success): ?>
            <div class="success-message text-center card shadow-lg p-5 border-0">
                <div class="success-icon mb-4" style="font-size: 5rem; color: var(--color-success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="mb-3 text-success">تم استلام طلبك بنجاح!</h2>
                <p class="lead text-muted">شكراً لطلبك من مطبعة برنتلي. سيتم معالجة طلبك قريباً.</p>
                <div class="order-details my-4 p-4 bg-light rounded-3 border">
                    <p class="mb-2"><strong>رقم الطلب:</strong> <span class="badge bg-primary fs-6"><?php echo $order_number; ?></span></p>
                    <p class="mb-0"><strong>إجمالي المبلغ المبدئي:</strong> <span class="fw-bold text-success"><?php echo number_format($price, 2); ?> جنيه</span></p>
                    <small class="d-block text-muted mt-2">(قد يتغير السعر النهائي بعد مراجعة التفاصيل)</small>
                </div>
                <p>يمكنك متابعة حالة طلبك من خلال <a href="account.php" class="text-decoration-none">حسابك الشخصي</a>.</p>
                <div class="actions mt-4">
                    <a href="index.php" class="btn btn-outline-secondary me-2 px-4">العودة للرئيسية</a>
                    <a href="services.php" class="btn btn-gold px-4">استكشاف خدمات أخرى</a>
                </div>
            </div>
        <?php else: ?>
            <!-- تم نقل العنوان والوصف إلى أعلى الصفحة -->

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <p class="mb-2"><i class="fas fa-exclamation-circle me-2"></i>يرجى ملء الحقول المطلوبة أدناه لإكمال طلبك. الحقول المعلمة بـ * إلزامية لهذه الخدمة.</p>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="order-form-container row g-5">
                <div class="col-lg-8">
                    <form action="order.php?service_id=<?php echo $service_id; ?>" method="post" enctype="multipart/form-data" class="order-form">
                        <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">

                        <!-- قسم مواصفات الخدمة -->
                        <div class="form-section service-specs">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h3 class="section-title">مواصفات الخدمة</h3>
                            </div>

<div class="form-grid">
                                <!-- الكمية -->
                                <div class="form-group">
                                    <label for="quantity" class="form-label">
                                        <i class="fas fa-calculator me-2 text-primary"></i>
                                        الكمية <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input type="number" 
                                               name="quantity" 
                                               id="quantity" 
                                               min="1" 
                                               max="50000" 
                                               required 
                                               class="form-control"
                                               placeholder="أدخل الكمية المطلوبة"
                                               value="<?php echo isset($form_data['quantity']) ? htmlspecialchars($form_data['quantity']) : '1'; ?>"
                                               oninput="if (this.value > 50000) this.value = 50000;">
                                    </div>
                                </div>




                                <!-- نوع الورق -->
                                <?php if ($selected_service['require_paper_type']): ?>
                                <div class="form-group">
                                    <label for="paper_type" class="form-label">
                                        <i class="fas fa-file-alt me-2 text-primary"></i>
                                        نوع الورق <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <select name="paper_type" id="paper_type" required class="form-select">
                                            <option value="">اختر نوع الورق المناسب</option>
                                            <?php foreach ($paper_types as $type): ?>
                                                <option value="<?php echo $type; ?>" 
                                                        <?php echo (isset($form_data['paper_type']) && $form_data['paper_type'] == $type) ? 'selected' : ''; ?>>
                                                    <?php echo $type; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- المقاس -->
                                <?php if ($selected_service['require_size']): ?>
                                <div class="form-group">
                                    <label for="size" class="form-label">
                                        <i class="fas fa-expand-arrows-alt me-2 text-primary"></i>
                                        المقاس <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <select name="size" id="size" required class="form-select">
                                            <option value="">اختر المقاس المطلوب</option>
                                            <?php foreach ($sizes as $s): ?>
                                                <option value="<?php echo $s; ?>" 
                                                        <?php echo (isset($form_data['size']) && $form_data['size'] == $s) ? 'selected' : ''; ?>>
                                                    <?php echo $s; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- عدد الألوان -->
                                <?php if ($selected_service['require_colors']): ?>
                                <div class="form-group">
                                    <label for="colors" class="form-label">
                                        <i class="fas fa-palette me-2 text-primary"></i>
                                        عدد الألوان <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input type="number" 
                                               name="colors" 
                                               id="colors" 
                                               min="1" 
                                               max="4" 
                                               required 
                                               class="form-control"
                                               placeholder="عدد الألوان المطلوبة"
                                               value="<?php echo isset($form_data['colors']) ? htmlspecialchars($form_data['colors']) : ''; ?>">
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- الملاحظات -->
                                <div class="form-group full-width">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-sticky-note me-2 text-primary"></i>
                                        الملاحظات
                                    </label>
                                    <div class="input-wrapper">
                                        <textarea name="notes" id="notes" class="form-control" placeholder="أدخل أي ملاحظات إضافية هنا"><?php echo isset($form_data['notes']) ? htmlspecialchars($form_data['notes']) : ''; ?></textarea>
                                    </div>
                                </div>

                                <!-- إرفاق ملف التصميم -->
                                <div class="form-group full-width">
                                    <label for="design_file" class="form-label">
                                        <i class="fas fa-upload me-2 text-primary"></i>
                                        ملف التصميم <span class="required">*</span>
                                    </label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" 
                                               name="design_file[]" 
                                               id="design_file" 
                                               multiple 
                                               required 
                                               class="form-control file-input">
                                        <small class="file-upload-info text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            الصيغ المدعومة: PDF, JPG, PNG, SVG, AI, PSD, EPS, TIFF, CDR, QXD, FH, PMD, BMP, PICT, PCX, TGA | الحد الأقصى لكل ملف: 1.5 جيجابايت
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>
                                إضافة إلى السلة
                            </button>
                            <button type="reset" class="btn btn-outline-secondary btn-reset">
                                <i class="fas fa-redo me-2"></i>
                                إعادة تعيين
                            </button>
                        </div>

                        <!-- قسم تعليمات الطلب -->
                        <div class="form-group full-width" style="text-align: left; margin-top: 20px;">
                            <div class="instructions-section" style="background: #f8f9fa; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <h4 class="instructions-title" style="font-size: 1.2rem; font-weight: bold; color: #333; margin-bottom: 10px; text-align: center;">تعليمات الطلب</h4>
                                <ul class="instructions-list" style="list-style: none; padding: 0; margin: 0;">
                                    <li style="margin-bottom: 8px; display: flex; align-items: center;">
                                        <i class="fas fa-info-circle text-primary me-2" style="font-size: 1.1rem; color: #007bff; margin-right: 8px;"></i>
                                        <span style="font-size: 0.95rem; color: #555;">يرجى التأكد من إدخال الكمية المطلوبة بدقة.</span>
                                    </li>
                                    <li style="margin-bottom: 8px; display: flex; align-items: center;">
                                        <i class="fas fa-info-circle text-primary me-2" style="font-size: 1.1rem; color: #007bff; margin-right: 8px;"></i>
                                        <span style="font-size: 0.95rem; color: #555;">اختر نوع الورق والمقاس المناسبين لاحتياجاتك.</span>
                                    </li>
                                    <li style="margin-bottom: 8px; display: flex; align-items: center;">
                                        <i class="fas fa-info-circle text-primary me-2" style="font-size: 1.1rem; color: #007bff; margin-right: 8px;"></i>
                                        <span style="font-size: 0.95rem; color: #555;">يمكنك إرفاق ملفات التصميم بصيغ مدعومة مثل PDF وJPG.</span>
                                    </li>
                                    <li style="margin-bottom: 8px; display: flex; align-items: center;">
                                        <i class="fas fa-info-circle text-primary me-2" style="font-size: 1.1rem; color: #007bff; margin-right: 8px;"></i>
                                        <span style="font-size: 0.95rem; color: #555;">إذا كانت لديك أي ملاحظات إضافية، يرجى كتابتها في الحقل المخصص.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- الشريط الجانبي -->
                <div class="col-lg-4">
                    <div class="order-sidebar sticky-top">
                        <!-- معلومات الطلب -->
                        <div class="sidebar-card info-card">
                            <div class="card-header">
                                <i class="fas fa-info-circle"></i>
                                <h4>معلومات الطلب</h4>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">سيتم مراجعة طلبك والتواصل معك خلال 24 ساعة لتأكيد التفاصيل والسعر النهائي.</p>
                                <div class="price-info">
                                    <span class="label">السعر المبدئي  تقريبا:</span>
                                    <span class="price"><?php echo number_format($selected_service['price_start'] ?? 0, 2); ?> جنيه</span>
                                </div>
                            </div>
                        </div>

                        <!-- طرق الدفع -->
                        <div class="sidebar-card payment-card">
                            <div class="card-header">
                                <i class="fas fa-credit-card"></i>
                                <h4>طرق الدفع المتاحة</h4>
                            </div>
                            <div class="card-body">
                                <ul class="payment-methods">
                                    <li>
                                        <i class="fas fa-money-bill-wave text-success"></i>
                                        <span>الدفع عند الاستلام</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-university text-info"></i>
                                        <span>تحويل بنكي</span>
                                        <small>(سيتم تزويدك بالبيانات لاحقاً)</small>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- خدمات أخرى -->
                        <div class="sidebar-card services-card">
                            <div class="card-header">
                                <i class="fas fa-plus-circle"></i>
                                <h4>خدمات أخرى قد تهمك</h4>
                            </div>
                            <div class="card-body">
                                <div class="related-services">
                                    <?php 
                                    $related_services = array_filter($services, function($service) use ($service_id) {
                                        return $service['service_id'] != $service_id;
                                    });
                                    $related_services = array_slice($related_services, 0, 3);
                                    foreach ($related_services as $service): ?>
                                        <div class="service-item">
                                            <a href="order.php?service_id=<?php echo $service['service_id']; ?>" class="service-link">
                                                <i class="fas fa-print text-primary"></i>
                                                <span><?php echo htmlspecialchars($service['name']); ?></span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($related_services)): ?>
                                        <p class="text-muted text-center">لا توجد خدمات أخرى متاحة حالياً</p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="services.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>
                                        عرض جميع الخدمات
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات التواصل -->
                        <div class="sidebar-card contact-card">
                            <div class="card-header">
                                <i class="fas fa-headset"></i>
                                <h4>هل تحتاج مساعدة؟</h4>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted mb-3">فريق الدعم الفني متاح لمساعدتك</p>
                                <div class="contact-methods">
                                    <a href="tel:+201002889688" class="contact-method">
                                        <i class="fas fa-phone text-success"></i>
                                        <span>اتصل بنا</span>
                                    </a>
                                    <a href="https://wa.me/201002889688" class="contact-method" target="_blank">
                                        <i class="fab fa-whatsapp text-success"></i>
                                        <span>واتساب</span>
                                    </a>
                                    <a href="mailto:support@printly.com" class="contact-method">
                                        <i class="fas fa-envelope text-info"></i>
                                        <span>إيميل</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- سكريبت التفاعل مع النموذج -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديث السعر المبدئي عند تغيير الكمية
    const quantityInput = document.getElementById('quantity');
    const priceElement = document.querySelector('.price');
    const basePrice = <?php echo $selected_service['price_start'] ?? 0; ?>;
    
    if (quantityInput && priceElement) {
        quantityInput.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            const totalPrice = basePrice * quantity;
            priceElement.textContent = totalPrice.toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + 'جنيه';
        });
    }

// تحسين رفع الملفات (يدعم ملفات متعددة)
const fileInput = document.getElementById('design_file');

if (fileInput) {
    fileInput.addEventListener('change', function () {
        const files = this.files;
        const fileInfo = document.querySelector('.file-upload-info small');

        if (files.length > 0) {
            let valid = true;
            let fileDetails = [];

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // بالميجابايت
                const fileName = file.name;

                if (fileSize > 1536) { // 1.5 جيجابايت = 1536 ميجابايت
                    alert(`الملف "${fileName}" حجمه كبير جداً (${fileSize} ميجابايت). الحد الأقصى 1.5 جيجابايت.`);
                    this.value = ''; // إعادة تعيين الإدخال
                    fileInfo.innerHTML = `
                        <i class="fas fa-info-circle me-1"></i>
                        الصيغ المدعومة: PDF, JPG, PNG, SVG, AI, PSD, EPS, TIFF, CDR, QXD, FH, PMD, BMP, PICT, PCX, TGA | الحد الأقصى لكل ملف: 1.5 جيجابايت
                    `;
                    fileInfo.classList.remove('text-success');
                    fileInfo.classList.add('text-muted');
                    valid = false;
                    break;
                }

                fileDetails.push(`${fileName} (${fileSize} ميجابايت)`);
            }

            if (valid) {
                fileInfo.innerHTML = `
                    <i class="fas fa-check-circle text-success me-1"></i>
                    تم اختيار ${files.length} ملف(ات):<br>${fileDetails.join('<br>')}
                `;
                fileInfo.classList.remove('text-muted');
                fileInfo.classList.add('text-success');
            }
        } else {
            fileInfo.innerHTML = `
                <i class="fas fa-info-circle me-1"></i>
                الصيغ المدعومة: PDF, JPG, PNG, SVG, AI, PSD, EPS, TIFF, CDR, QXD, FH, PMD, BMP, PICT, PCX, TGA | الحد الأقصى لكل ملف: 1.5 جيجابايت
            `;
            fileInfo.classList.remove('text-success');
            fileInfo.classList.add('text-muted');
        }
    });
}


    // تحسين تجربة المستخدم في النموذج
    const formInputs = document.querySelectorAll('.form-control, .form-select');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-group').classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.closest('.form-group').classList.remove('focused');
            if (this.value.trim() !== '') {
                this.closest('.form-group').classList.add('filled');
            } else {
                this.closest('.form-group').classList.remove('filled');
            }
        });

        // تحقق من الحقول المملوءة عند تحميل الصفحة
        if (input.value.trim() !== '') {
            input.closest('.form-group').classList.add('filled');
        }
    });

    // تحسين زر إعادة التعيين
    const resetButton = document.querySelector('.btn-reset');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من رغبتك في إعادة تعيين جميع الحقول؟')) {
                e.preventDefault();
            } else {
                // إعادة تعيين معلومات الملف
                const fileInfo = document.querySelector('.file-upload-info small');
                if (fileInfo) {
                    fileInfo.innerHTML = `
                        <i class="fas fa-info-circle me-1"></i>
                        الصيغ المدعومة: PDF, JPG, PNG, SVG, AI, PSD, EPS, TIFF, CDR, QXD, FH, PMD, BMP, PICT, PCX, TGA | الحد الأقصى: 10 ميجابايت
                    `;
                    fileInfo.classList.remove('text-success');
                    fileInfo.classList.add('text-muted');
                }
                
                // إزالة الكلاسات المضافة
                formInputs.forEach(input => {
                    input.closest('.form-group').classList.remove('focused', 'filled');
                });
            }
        });
    }

    // التحقق من صحة النموذج قبل الإرسال
    const orderForm = document.querySelector('.order-form');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            // التحقق من الاسم الثلاثي
            const nameField = document.getElementById('name');
            if (nameField && nameField.value) {
                const nameParts = nameField.value.trim().split(/\s+/);
                if (nameParts.length < 3) {
                    isValid = false;
                    nameField.classList.add('is-invalid');
                    nameField.closest('.form-group').classList.add('error');
                    alert('يرجى إدخال الاسم الثلاثي كاملاً');
                } else {
                    nameField.classList.remove('is-invalid');
                    nameField.closest('.form-group').classList.remove('error');
                }
            }
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    field.closest('.form-group').classList.add('error');
                } else {
                    field.classList.remove('is-invalid');
                    field.closest('.form-group').classList.remove('error');
                }
            });

            // التحقق من صحة البريد الإلكتروني
            const emailField = document.getElementById('email');
            if (emailField && emailField.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value)) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                    emailField.closest('.form-group').classList.add('error');
                }
            }

// التحقق من رقم الهاتف المصري
const phoneField = document.getElementById('phone');
if (phoneField && phoneField.value) {
    const phoneRegex = /^(010|011|012|015)[0-9]{8}$/;
    if (!phoneRegex.test(phoneField.value)) {
        isValid = false;
        phoneField.classList.add('is-invalid');
        phoneField.closest('.form-group').classList.add('error');
        alert('يرجى إدخال رقم هاتف مصري صحيح يبدأ بـ 010 أو 011 أو 012 أو 015 ويتكون من 11 رقمًا');
    }
}


            if (!isValid) {
                e.preventDefault();
                // التمرير إلى أول حقل خاطئ
                const firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    firstError.focus();
                }
            }
        });
    }

    // تأثيرات بصرية للشريط الجانبي
    const sidebarCards = document.querySelectorAll('.sidebar-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });

    sidebarCards.forEach(card => {
        observer.observe(card);
    });
});

// دالة لتنسيق الأرقام
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// دالة للتحقق من نوع الملف
function validateFileType(file) {
    const allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/svg+xml',
        'application/postscript',
        'application/illustrator',
        'application/vnd.adobe.photoshop',
        'application/vnd.adobe.indesign',
        'application/vnd.corel-draw',
        'application/x-photoshop',
        'application/x-illustrator',
        'application/x-indesign',
        'application/x-coreldraw',
        'application/x-quarkxpress',
        'application/x-pagemaker',
        'application/x-freehand',
        'application/x-eps',
        'application/x-ai',
        'application/x-psd',
        'application/x-cdr',
        'application/x-qxd',
        'application/x-pmd',
        'application/x-fh',
        'application/x-tiff',
        'image/tiff',
        'image/bmp',
        'image/x-bmp',
        'image/x-ms-bmp',
        'image/x-windows-bmp',
        'image/x-portable-pixmap',
        'image/x-portable-graymap',
        'image/x-portable-bitmap',
        'image/x-portable-anymap',
        'image/x-xbitmap',
        'image/x-xpixmap',
        'image/x-xwindowdump',
        'image/x-rgb',
        'image/x-xbm',
        'image/x-xpm',
        'image/x-pcx',
        'image/x-pict',
        'image/x-quicktime',
        'image/x-sgi',
        'image/x-tga',
        'image/x-icns',
        'image/x-portable-anyformat',
        'image/x-portable-floatmap',
        'image/x-portable-greymap',
        'image/x-portable-pixmap',
        'image/x-portable-bitmap',
        'image/x-portable-anymap',
        'image/x-portable-floatmap',
        'image/x-portable-greymap',
        'image/x-portable-pixmap',
        'image/x-portable-bitmap',
        'image/x-portable-anymap'
    ];
    
    return allowedTypes.includes(file.type);
}
</script>

<style>
:root {
  --color-primary: #00adef;
  --color-secondary: #00adef;
  --color-primary-rgb: 78, 115, 223;
  --color-dark: #2c3e50;
  --color-light: #f8f9fa;
}

* {
  box-sizing: border-box;
}

.order-section {
  padding-top: 80px;
  padding: 2rem 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  min-height: 100vh;
}

.container {
  margin-top: 0 !important;
  padding: 30px 40px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.order-form-container {
  background: white;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  overflow: hidden;
  max-width: 1200px;
  margin: 0 auto;
}

.form-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  border: 1px solid #e9ecef;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f3f5;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.section-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
  flex-shrink: 0;
}

.section-title {
  margin: 0;
  color: var(--color-dark);
  font-weight: 600;
  font-size: 1.25rem;
}

/* تنسيقات الشبكة */
.form-grid,
.customer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
  gap: 1.25rem;
  align-items: start;
}

@media (min-width: 576px) {
  .form-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }
  .customer-grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  }
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group {
  position: relative;
  margin-bottom: 1rem;
}

.form-label {
  font-weight: 500;
  color: var(--color-dark);
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

.required {
  color: #dc3545;
  margin-right: 0.25rem;
}

.optional-badge {
  background: #6c757d;
  color: white;
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
  border-radius: 8px;
  margin-right: 0.5rem;
}

.input-wrapper {
  position: relative;
}

.form-control, .form-select {
  border: 1px solid #e0e3e6;
  border-radius: 8px;
  padding: 0.65rem 0.9rem;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  background: #fafafa;
  width: 100%;
}

.form-control:focus, .form-select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 0.15rem rgba(var(--color-primary-rgb), 0.2);
  background: white;
  outline: none;
}

.form-group.focused .form-control,
.form-group.focused .form-select {
  border-color: var(--color-primary);
  background: white;
}

.form-group.error .form-control,
.form-group.error .form-select {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.2);
}

/* رفع الملفات */
.file-upload-wrapper {
  position: relative;
}

.file-input {
  padding: 0.8rem;
  border: 2px dashed #dee2e6;
  background: #f8f9fa;
  cursor: pointer;
  border-radius: 8px;
  text-align: center;
}

.file-input:hover {
  border-color: var(--color-primary);
  background: #e3f2fd;
}

.file-upload-info {
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #6c757d;
}

/* أزرار */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f1f3f5;
  flex-wrap: wrap;
}

.btn-submit {
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 8px;
  min-width: 180px;
  transition: all 0.2s ease;
  color: white;
  cursor: pointer;
  font-size: 1rem;
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}

.btn-reset {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 8px;
  min-width: 120px;
  border: 1px solid #6c757d;
  transition: all 0.2s ease;
  background: white;
  color: #6c757d;
  cursor: pointer;
  font-size: 1rem;
}

.btn-reset:hover {
  background: #6c757d;
  color: white;
}

/* الشريط الجانبي */
.order-sidebar {
  padding: 1rem;
  padding-bottom: 2rem;
}

.sidebar-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  margin-bottom: 1.25rem;
  overflow: hidden;
  border: 1px solid #e9ecef;
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 0.8rem 1.2rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.card-header i {
  color: var(--color-primary);
  font-size: 1rem;
}

.card-header h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-dark);
}

.card-body {
  padding: 1.2rem;
}

/* معلومات السعر */
.price-info {
  background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  padding: 0.8rem;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #90caf9;
}

.price-info .label {
  font-size: 0.8rem;
  color: #555;
  margin-bottom: 0.4rem;
}

.price-info .price {
  font-size: 1.3rem;
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: 0.2rem;
}

.price-info .note {
  color: #666;
  font-size: 0.75rem;
}

/* طرق الدفع */
.payment-methods {
  list-style: none;
  padding: 0;
  margin: 0;
}

.payment-methods li {
  display: flex;
  align-items: flex-start;
  padding: 0.6rem 0;
  border-bottom: 1px solid #f1f3f5;
  gap: 0.6rem;
}

.payment-methods li:last-child {
  border-bottom: none;
}

.payment-methods i {
  margin-top: 0.1rem;
  font-size: 1rem;
}

.payment-methods span {
  font-weight: 500;
  color: var(--color-dark);
  font-size: 0.9rem;
}

.payment-methods small {
  display: block;
  color: #6c757d;
  font-size: 0.75rem;
  margin-top: 0.2rem;
}

/* الخدمات ذات الصلة */
.related-services {
  margin-bottom: 0.8rem;
}

.service-item {
  margin-bottom: 0.6rem;
}

.service-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem;
  border-radius: 6px;
  text-decoration: none;
  color: var(--color-dark);
  transition: all 0.2s ease;
  border: 1px solid #e9ecef;
  font-size: 0.9rem;
}

.service-link:hover {
  background: #f8f9fa;
  border-color: var(--color-primary);
  color: var(--color-primary);
}

/* طرق التواصل */
.contact-methods {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.contact-method {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.6rem;
  border-radius: 6px;
  text-decoration: none;
  color: var(--color-dark);
  border: 1px solid #e9ecef;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.contact-method:hover {
  background: #f8f9fa;
  border-color: var(--color-primary);
  color: var(--color-primary);
}

/* رسالة النجاح */
.success-message {
  background: white;
  border-radius: 15px;
  animation: successSlideIn 0.5s ease-out;
  margin-top: 100px; /* عدّل الرقم حسب المسافة المطلوبة */
}

@keyframes successSlideIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.success-icon {
  animation: successPulse 1.5s infinite;
}

@keyframes successPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.03); }
}

.order-details {
  animation: fadeInUp 0.6s ease-out 0.2s both;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1, transform: translateY(0); }
}

/* استجابة للشاشات المختلفة */
@media (min-width: 768px) {
  .order-section {
    padding: 3rem 1.5rem;
  }

  .order-form-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
  }

  .form-section {
    padding: 2rem;
  }

  .order-sidebar {
    padding-top: 0;
    position: sticky;
    top: 1rem;
  }
}

@media (max-width: 767px) {
  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-submit,
  .btn-reset {
    width: 100%;
  }

  .contact-methods {
    flex-direction: row;
    flex-wrap: wrap;
  }

  .contact-method {
    flex: 1 1 45%;
  }

  .order-section {
    padding-top: 60px;
  }

  .container {
    padding: 15px !important;
    margin-top: 0 !important;
  }
}

@media (max-width: 480px) {
  .form-grid,
  .customer-grid {
    grid-template-columns: 1fr;
  }

  .contact-methods {
    flex-direction: column;
  }

  .contact-method {
    width: 100%;
  }

  .form-control,
  .form-select {
    padding: 0.55rem 0.75rem;
    font-size: 0.85rem;
  }

  .btn-submit,
  .btn-reset {
    font-size: 0.95rem;
    padding: 0.6rem 1rem;
  }
}

/* تحسينات للأجهزة المحمولة */
@media (max-width: 767px) {
    .order-section {
        padding-top: 60px;
    }

    .container {
        padding: 15px !important;
        margin-top: 0 !important;
    }

    .form-section {
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .section-header {
        margin-bottom: 1rem;
    }

    .form-grid {
        gap: 0.8rem;
    }

    .form-group {
        margin-bottom: 0.8rem;
    }

    .form-label {
        font-size: 0.85rem;
    }

    .form-control, .form-select {
        padding: 0.5rem 0.7rem;
        font-size: 0.9rem;
    }

    .file-upload-info {
        font-size: 0.75rem;
    }

    .form-actions {
        margin-top: 1rem;
        padding-top: 1rem;
        gap: 0.8rem;
    }

    .btn-submit, .btn-reset {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        min-width: auto;
        width: 100%;
    }

    .order-sidebar {
        padding: 0.8rem;
    }

    .sidebar-card {
        margin-bottom: 1rem;
    }

    .card-header {
        padding: 0.6rem 1rem;
    }

    .card-body {
        padding: 1rem;
    }

    .price-info {
        padding: 0.6rem;
    }

    .price-info .price {
        font-size: 1.1rem;
    }

    .payment-methods li {
        padding: 0.5rem 0;
    }

    .service-link {
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .contact-method {
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .success-message {
        margin-top: 60px;
        padding: 1.5rem !important;
    }

    .success-icon {
        font-size: 4rem !important;
    }

    .order-details {
        padding: 0.8rem !important;
    }
}

/* تحسينات إضافية للشاشات الصغيرة جداً */
@media (max-width: 480px) {
    .container {
        padding: 10px !important;
    }

    .form-section {
        padding: 0.8rem;
    }

    .section-title {
        font-size: 1.1rem;
    }

    .form-label {
        font-size: 0.8rem;
    }

    .form-control, .form-select {
        padding: 0.45rem 0.6rem;
        font-size: 0.85rem;
    }

    .file-upload-info {
        font-size: 0.7rem;
    }

    .card-header h4 {
        font-size: 0.85rem;
    }

    .price-info .label {
        font-size: 0.75rem;
    }

    .price-info .price {
        font-size: 1rem;
    }

    .payment-methods span {
        font-size: 0.8rem;
    }

    .payment-methods small {
        font-size: 0.7rem;
    }

    .service-link {
        font-size: 0.8rem;
    }

    .contact-method {
        font-size: 0.8rem;
    }
}

/* تحسينات للشاشات المتوسطة */
@media (min-width: 768px) and (max-width: 991px) {
    .container {
        padding: 20px !important;
    }

    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }
}

/* تحسينات للشاشات الكبيرة */
@media (min-width: 992px) {
    .order-form-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .order-sidebar {
        position: sticky;
        top: 1rem;
    }
}

/* تحسينات عامة للتجاوب */
@media (max-width: 767px) {
    .row {
        margin-left: 0;
        margin-right: 0;
    }

    .col-12, .col-lg-8, .col-lg-4 {
        padding-left: 0;
        padding-right: 0;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .input-wrapper {
        margin-bottom: 0.5rem;
    }

    .alert {
        padding: 0.75rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .alert ul {
        padding-right: 1.5rem;
    }

    .alert li {
        font-size: 0.85rem;
    }
}

/* تحسينات للصور والأيقونات */
@media (max-width: 767px) {
    .section-icon {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }

    .card-header i {
        font-size: 0.9rem;
    }

    .payment-methods i {
        font-size: 0.9rem;
    }

    .service-link i {
        font-size: 0.9rem;
    }

    .contact-method i {
        font-size: 0.9rem;
    }
}

/* تحسينات للتباعد والهوامش */
@media (max-width: 767px) {
    .mb-5 {
        margin-bottom: 1.5rem !important;
    }

    .mt-4 {
        margin-top: 1rem !important;
    }

    .p-5 {
        padding: 1rem !important;
    }

    .p-4 {
        padding: 0.8rem !important;
    }

    .me-2 {
        margin-right: 0.5rem !important;
    }

    .ms-2 {
        margin-left: 0.5rem !important;
    }
}

/* تحسينات للخطوط */
@media (max-width: 767px) {
    h2 {
        font-size: 1.5rem;
    }

    h3 {
        font-size: 1.2rem;
    }

    h4 {
        font-size: 1.1rem;
    }

    .lead {
        font-size: 1rem;
    }

    .text-muted {
        font-size: 0.85rem;
    }
}
</style>


