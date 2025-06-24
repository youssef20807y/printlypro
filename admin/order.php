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

// جلب الحقول المخصصة للخدمة
$custom_fields_stmt = $db->prepare("
    SELECT cf.*, ft.type_name, ft.type_key, ft.has_options, sf.is_required as service_required
    FROM service_fields sf
    INNER JOIN custom_fields cf ON sf.field_id = cf.field_id
    LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
    WHERE cf.status = 'active' AND sf.status = 'active' AND sf.service_id = ?
    ORDER BY sf.order_num ASC, cf.order_num ASC
");
$custom_fields_stmt->execute([$service_id]);
$custom_fields = $custom_fields_stmt->fetchAll(PDO::FETCH_ASSOC);

// فلتر الحقول المخصصة لتجنب التكرار مع الحقول الأساسية
$filtered_custom_fields = [];
$basic_field_labels = ['نوع الورق', 'المقاس', 'عدد الألوان', 'الملاحظات', 'ملف التصميم'];

foreach ($custom_fields as $field) {
    // تجاهل الحقول المخصصة التي لها نفس اسم الحقول الأساسية
    if (!in_array($field['field_label'], $basic_field_labels)) {
        $filtered_custom_fields[] = $field;
    }
}

// جلب خيارات الحقول المخصصة
foreach ($filtered_custom_fields as &$field) {
    if ($field['has_options']) {
        $options_stmt = $db->prepare("
            SELECT option_value, option_label, is_default, order_num
            FROM field_options
            WHERE field_id = ?
            ORDER BY order_num ASC, option_label ASC
        ");
        $options_stmt->execute([$field['field_id']]);
        $field['options'] = $options_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $field['options'] = [];
    }
}

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
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.body.classList.add('order-page');
});
</script>
<!-- نافذة تكبير الصورة -->
<div id="imageZoomModal" class="image-zoom-modal" tabindex="-1" style="display:none;">
    <span class="image-zoom-close" id="imageZoomClose">&times;</span>
    <img class="image-zoom-modal-content" id="imageZoomedImg" alt="صورة مكبرة">
</div>

<section class="order-section section">
  <div class="order-container new-order-layout">
    <div class="order-grid order-grid-3cols">
      <!-- تفاصيل المنتج على اليمين -->
      <aside class="product-details-aside">
        <div class="product-image-big-container">
          <?php if (!empty($selected_service['image'])): ?>
            <img src="uploads/services/<?php echo htmlspecialchars($selected_service['image']); ?>" 
                 alt="<?php echo htmlspecialchars($selected_service['name']); ?>" 
                 class="product-image-big zoomable" tabindex="0" id="productZoomImg">
          <?php else: ?>
            <div class="no-image-placeholder">
              <i class="fas fa-image"></i>
              <span>لا توجد صورة</span>
            </div>
          <?php endif; ?>
        </div>
        <div class="product-details-box">
          <h2 class="product-name-main"><?php echo htmlspecialchars($selected_service['name']); ?></h2>
          <?php if (!empty($selected_service['description'])): ?>
            <div class="product-description-main">
              <?php echo $selected_service['description']; ?>
            </div>
          <?php endif; ?>
          <?php if ($selected_service['price_end']): ?>
            <div class="product-price-range-main">
              <span class="price-label">نطاق السعر:</span>
              <span class="price-value">
                <?php echo number_format($selected_service['price_start'] ?? 0, 2); ?> - 
                <?php echo number_format($selected_service['price_end'], 2); ?> جنيه
              </span>
            </div>
          <?php else: ?>
            <div class="product-price-main">
              <span class="price-label">السعر المبدئي:</span>
              <span class="price-value" id="dynamic-price"><?php echo number_format($selected_service['price_start'] ?? 0, 2); ?> جنيه</span>
            </div>
          <?php endif; ?>
        </div>
      </aside>
      <!-- النموذج على اليسار -->
      <main class="order-form-main">
        <?php if (!isset($_GET['payment_success']) && !$success): ?>
          <h2 class="section-title mb-3" style="text-align:right;">طلب خدمة "<?php echo htmlspecialchars($selected_service['name']); ?>"</h2>
          <p class="section-description text-muted" style="text-align:right;">
            يرجى ملء الحقول المطلوبة أدناه لإكمال طلبك. الحقول المعلمة بـ <span class="required">*</span> إلزامية لهذه الخدمة.
          </p>
        <?php endif; ?>
        <?php if (isset($_GET['payment_success'])): ?>
          <!-- ... success message ... -->
          <?php /* لا تغييرات هنا */ ?>
        <?php elseif ($success): ?>
          <!-- ... success message ... -->
          <?php /* لا تغييرات هنا */ ?>
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
          <form action="order.php?service_id=<?php echo $service_id; ?>" method="post" enctype="multipart/form-data" class="order-form new-order-form">
            <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
            <div class="form-grid new-form-grid">
              <!-- الكمية -->
              <div class="form-group">
                <label for="quantity" class="form-label">
                  <i class="fas fa-calculator me-2 text-primary"></i>
                  الكمية <span class="required">*</span>
                </label>
                <input type="number" name="quantity" id="quantity" min="1" max="50000" required class="form-control" placeholder="أدخل الكمية المطلوبة" value="<?php echo isset($form_data['quantity']) ? htmlspecialchars($form_data['quantity']) : '1'; ?>" oninput="if (this.value > 50000) this.value = 50000;">
              </div>
              <!-- نوع الورق -->
              <?php if ($selected_service['require_paper_type']): ?>
              <div class="form-group">
                <label for="paper_type" class="form-label">
                  <i class="fas fa-file-alt me-2 text-primary"></i>
                  نوع الورق <span class="required">*</span>
                </label>
                <select name="paper_type" id="paper_type" required class="form-select">
                  <option value="">اختر نوع الورق المناسب</option>
                  <?php foreach ($paper_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo (isset($form_data['paper_type']) && $form_data['paper_type'] == $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <!-- المقاس -->
              <?php if ($selected_service['require_size']): ?>
              <div class="form-group">
                <label for="size" class="form-label">
                  <i class="fas fa-expand-arrows-alt me-2 text-primary"></i>
                  المقاس <span class="required">*</span>
                </label>
                <select name="size" id="size" required class="form-select">
                  <option value="">اختر المقاس المطلوب</option>
                  <?php foreach ($sizes as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo (isset($form_data['size']) && $form_data['size'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <!-- عدد الألوان -->
              <?php if ($selected_service['require_colors']): ?>
              <div class="form-group">
                <label for="colors" class="form-label">
                  <i class="fas fa-palette me-2 text-primary"></i>
                  عدد الألوان <span class="required">*</span>
                </label>
                <input type="number" name="colors" id="colors" min="1" max="4" required class="form-control" placeholder="عدد الألوان المطلوبة" value="<?php echo isset($form_data['colors']) ? htmlspecialchars($form_data['colors']) : ''; ?>">
              </div>
              <?php endif; ?>
              
              <!-- الحقول المخصصة -->
              <?php foreach ($filtered_custom_fields as $field): ?>
              <div class="form-group <?php echo $field['type_key'] === 'textarea' ? 'full-width' : ''; ?>">
                <label for="custom_field_<?php echo $field['field_id']; ?>" class="form-label">
                  <i class="fas fa-cog me-2 text-primary"></i>
                  <?php echo htmlspecialchars($field['field_label']); ?>
                  <?php if ($field['service_required']): ?><span class="required">*</span><?php endif; ?>
                </label>
                <?php if ($field['type_key'] === 'text'): ?>
                  <input type="text" name="custom_field_<?php echo $field['field_id']; ?>" id="custom_field_<?php echo $field['field_id']; ?>" 
                         <?php echo $field['service_required'] ? 'required' : ''; ?> class="form-control" 
                         placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                         value="<?php echo isset($form_data['custom_field_' . $field['field_id']]) ? htmlspecialchars($form_data['custom_field_' . $field['field_id']]) : htmlspecialchars($field['default_value'] ?? ''); ?>">
                <?php elseif ($field['type_key'] === 'number'): ?>
                  <input type="number" name="custom_field_<?php echo $field['field_id']; ?>" id="custom_field_<?php echo $field['field_id']; ?>" 
                         <?php echo $field['service_required'] ? 'required' : ''; ?> class="form-control" 
                         placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                         value="<?php echo isset($form_data['custom_field_' . $field['field_id']]) ? htmlspecialchars($form_data['custom_field_' . $field['field_id']]) : htmlspecialchars($field['default_value'] ?? ''); ?>">
                <?php elseif ($field['type_key'] === 'textarea'): ?>
                  <textarea name="custom_field_<?php echo $field['field_id']; ?>" id="custom_field_<?php echo $field['field_id']; ?>" 
                            <?php echo $field['service_required'] ? 'required' : ''; ?> class="form-control" 
                            placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"><?php echo isset($form_data['custom_field_' . $field['field_id']]) ? htmlspecialchars($form_data['custom_field_' . $field['field_id']]) : htmlspecialchars($field['default_value'] ?? ''); ?></textarea>
                <?php elseif ($field['type_key'] === 'select' && !empty($field['options'])): ?>
                  <select name="custom_field_<?php echo $field['field_id']; ?>" id="custom_field_<?php echo $field['field_id']; ?>" 
                          <?php echo $field['service_required'] ? 'required' : ''; ?> class="form-select">
                    <option value="">اختر...</option>
                    <?php foreach ($field['options'] as $option): ?>
                      <option value="<?php echo htmlspecialchars($option['option_value']); ?>" 
                              <?php echo (isset($form_data['custom_field_' . $field['field_id']]) && $form_data['custom_field_' . $field['field_id']] == $option['option_value']) ? 'selected' : ($option['is_default'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($option['option_label']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>
                <?php if (!empty($field['help_text'])): ?>
                  <small class="text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              
              <!-- الملاحظات -->
              <div class="form-group full-width">
                <label for="notes" class="form-label">
                  <i class="fas fa-sticky-note me-2 text-primary"></i>
                  الملاحظات
                </label>
                <textarea name="notes" id="notes" class="form-control" placeholder="أدخل أي ملاحظات إضافية هنا"><?php echo isset($form_data['notes']) ? htmlspecialchars($form_data['notes']) : ''; ?></textarea>
              </div>
              <!-- إرفاق ملف التصميم -->
              <div class="form-group full-width">
                <label for="design_file" class="form-label">
                  <i class="fas fa-upload me-2 text-primary"></i>
                  ملف التصميم <span class="required">*</span>
                </label>
                <input type="file" name="design_file[]" id="design_file" multiple required class="form-control file-input">
                <small class="file-upload-info text-muted">
                  <i class="fas fa-info-circle me-1"></i>
                  الصيغ المدعومة: PDF, JPG, PNG, SVG, AI, PSD, EPS, TIFF, CDR, QXD, FH, PMD, BMP, PICT, PCX, TGA | الحد الأقصى لكل ملف: 1.5 جيجابايت
                </small>
              </div>
            </div>
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
            <div class="form-group full-width" style="text-align: left; margin-top: 20px;">
              <div class="instructions-section">
                <h4 class="instructions-title">تعليمات الطلب</h4>
                <ul class="instructions-list">
                  <li><i class="fas fa-info-circle text-primary me-2"></i>يرجى التأكد من إدخال الكمية المطلوبة بدقة.</li>
                  <li><i class="fas fa-info-circle text-primary me-2"></i>اختر نوع الورق والمقاس المناسبين لاحتياجاتك.</li>
                  <li><i class="fas fa-info-circle text-primary me-2"></i>يمكنك إرفاق ملفات التصميم بصيغ مدعومة مثل PDF وJPG.</li>
                  <li><i class="fas fa-info-circle text-primary me-2"></i>إذا كانت لديك أي ملاحظات إضافية، يرجى كتابتها في الحقل المخصص.</li>
                </ul>
              </div>
            </div>
          </form>
        <?php endif; ?>
      </main>
      <!-- ملخص الطلب على اليسار -->
      <aside class="order-summary-aside">
                  <div class="order-summary-box">
            <h3 class="order-summary-title"><i class="fas fa-receipt me-2"></i>ملخص الطلب</h3>
            <ul class="order-summary-list">
              <li>
                <span>الخدمة:</span> 
                <span id="summary-service-name"><?php echo htmlspecialchars($selected_service['name']); ?></span>
              </li>
              <li>
                <span>الكمية:</span> 
                <span id="summary-quantity">1</span>
              </li>
              <li>
                <span>السعر الإجمالي:</span> 
                <span id="summary-total-price"><?php echo number_format($selected_service['price_start'], 2); ?> جنيه</span>
              </li>
              <?php if ($selected_service['require_paper_type']): ?>
              <li>
                <span>نوع الورق:</span> 
                <span id="summary-paper-type">-</span>
              </li>
              <?php endif; ?>
              <?php if ($selected_service['require_size']): ?>
              <li>
                <span>المقاس:</span> 
                <span id="summary-size">-</span>
              </li>
              <?php endif; ?>
              <?php if ($selected_service['require_colors']): ?>
              <li>
                <span>عدد الألوان:</span> 
                <span id="summary-colors">-</span>
              </li>
              <?php endif; ?>
              <li>
                <span>الملاحظات:</span> 
                <span id="summary-notes">-</span>
              </li>
            </ul>
          </div>
      </aside>
    </div>
  </div>
</section>

<!-- سكريبت التفاعل مع النموذج -->
<script>
// كود تحديث الكمية والسعر
document.addEventListener('DOMContentLoaded', function() {
    var qtyInput = document.getElementById('quantity');
    var qtyDisplay = document.getElementById('summary-quantity');
    var priceDisplay = document.getElementById('summary-total-price');
    var basePrice = <?php echo (float)$selected_service['price_start']; ?>;

    function updateQuantityAndPrice() {
        // تحديث الكمية
        var qty = Math.max(1, parseInt(qtyInput.value) || 1);
        
        // التأكد من أن الكمية لا تزيد عن 50000
        if (qty > 50000) {
            qty = 50000;
        }
        
        qtyInput.value = qty;
        qtyDisplay.textContent = qty;

        // تحديث السعر
        var total = qty * basePrice;
        var formattedPrice = total.toLocaleString('ar-EG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        priceDisplay.textContent = formattedPrice + ' جنيه';
        
        // تحديث السعر الديناميكي أيضاً
        var dynamicPriceElement = document.getElementById('dynamic-price');
        if (dynamicPriceElement) {
            dynamicPriceElement.textContent = formattedPrice + ' جنيه';
        }
        
        console.log('تم تحديث السعر:', {
            quantity: qty,
            basePrice: basePrice,
            totalPrice: total,
            formattedPrice: formattedPrice
        });
    }

    if (qtyInput) {
        // تحديث فوري عند أي تغيير
        ['input', 'change', 'keyup', 'mouseup', 'paste'].forEach(function(event) {
            qtyInput.addEventListener(event, function() {
                setTimeout(updateQuantityAndPrice, 10);
            });
        });

        // منع القيم السالبة
        qtyInput.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 1;
                updateQuantityAndPrice();
            }
        });

        // تحديث القيم الأولية
        updateQuantityAndPrice();

        // إضافة التحقق من التحديث كل ثانية
        setInterval(updateQuantityAndPrice, 1000);
    }

    // إضافة أزرار + و - للكمية
    var plusBtn = document.createElement('button');
    plusBtn.type = 'button';
    plusBtn.className = 'qty-btn plus';
    plusBtn.textContent = '+';
    
    var minusBtn = document.createElement('button');
    minusBtn.type = 'button';
    minusBtn.className = 'qty-btn minus';
    minusBtn.textContent = '-';

    if (qtyInput && qtyInput.parentNode) {
        qtyInput.parentNode.insertBefore(minusBtn, qtyInput);
        qtyInput.parentNode.insertBefore(plusBtn, qtyInput.nextSibling);

        plusBtn.addEventListener('click', function() {
            qtyInput.value = parseInt(qtyInput.value || 1) + 1;
            updateQuantityAndPrice();
        });

        minusBtn.addEventListener('click', function() {
            var currentVal = parseInt(qtyInput.value || 2);
            if (currentVal > 1) {
                qtyInput.value = currentVal - 1;
                updateQuantityAndPrice();
            }
        });
    }
});

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
                } else {
                    phoneField.classList.remove('is-invalid');
                    phoneField.closest('.form-group').classList.remove('error');
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

    // تحديث ملخص الطلب تلقائياً
    const summaryQuantity = document.getElementById('summary-quantity');
    const summaryTotalPrice = document.getElementById('summary-total-price');
    const summaryPaperType = document.getElementById('summary-paper-type');
    const summarySize = document.getElementById('summary-size');
    const summaryColors = document.getElementById('summary-colors');
    const summaryNotes = document.getElementById('summary-notes');
    const quantityInput = document.getElementById('quantity');
    const paperTypeInput = document.getElementById('paper_type');
    const sizeInput = document.getElementById('size');
    const colorsInput = document.getElementById('colors');
    const notesInput = document.getElementById('notes');
    const basePrice = <?php echo $selected_service['price_start'] ?? 0; ?>;

    function updateSummary() {
        // تحديث الكمية
        if (quantityInput && summaryQuantity) {
            const quantity = parseInt(quantityInput.value) || 1;
            summaryQuantity.textContent = quantity;
        }
        
        // تحديث السعر الإجمالي
        if (summaryTotalPrice && quantityInput) {
            const quantity = parseInt(quantityInput.value) || 1;
            const totalPrice = basePrice * quantity;
            const formattedPrice = totalPrice.toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            summaryTotalPrice.textContent = formattedPrice + ' جنيه';
            
            // تحديث السعر الديناميكي أيضاً
            if (dynamicPriceElement) {
                dynamicPriceElement.textContent = formattedPrice + ' جنيه';
            }
        }
        
        // تحديث باقي التفاصيل
        if (paperTypeInput && summaryPaperType) {
            summaryPaperType.textContent = paperTypeInput.value || '-';
        }
        if (sizeInput && summarySize) {
            summarySize.textContent = sizeInput.value || '-';
        }
        if (colorsInput && summaryColors) {
            summaryColors.textContent = colorsInput.value || '-';
        }
        if (notesInput && summaryNotes) {
            summaryNotes.textContent = notesInput.value.trim() ? notesInput.value : '-';
        }
    }
    if (quantityInput) quantityInput.addEventListener('input', updateSummary);
    if (paperTypeInput) paperTypeInput.addEventListener('change', updateSummary);
    if (sizeInput) sizeInput.addEventListener('change', updateSummary);
    if (colorsInput) colorsInput.addEventListener('input', updateSummary);
    if (notesInput) notesInput.addEventListener('input', updateSummary);
    // تحديث أولي
    updateSummary();
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

.new-order-layout {
  padding: 0 !important;
  box-shadow: 0 0 20px rgba(0,0,0,0.07);
  border-radius: 18px;
  background: #fff;
  margin-top: 60px !important;
}
.order-grid {
  display: grid;
  grid-template-columns: 420px 1fr;
  gap: 0;
  min-height: 600px;
  direction: rtl;
}
@media (max-width: 1100px) {
  .order-grid {
    grid-template-columns: 340px 1fr;
  }
}
@media (max-width: 900px) {
  .order-grid {
    grid-template-columns: 1fr;
  }
  .product-details-aside {
    border-left: none;
    border-bottom: 1px solid #e9ecef;
    border-radius: 0 0 18px 18px;
    margin-bottom: 2rem;
  }
}
.product-details-aside {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-left: 1px solid #e9ecef;
  border-radius: 18px 0 0 18px;
  padding: 2.5rem 2rem 2rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 320px;
  max-width: 480px;
  box-shadow: 0 0 0 rgba(0,0,0,0);
}
.product-image-big-container {
  width: 100%;
  max-width: 340px;
  height: 340px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,173,239,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 2rem;
  border: 1.5px solid #e3f2fd;
}
.product-image-big {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,173,239,0.07);
  background: #f8f9fa;
}
.product-details-box {
  width: 100%;
  text-align: right;
}
.product-name-main {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 0.7rem;
}
.product-description-main {
  font-size: 1.05rem;
  color: #666;
  margin-bottom: 1.2rem;
  line-height: 1.7;
  max-height: 120px;
  overflow-y: auto;
}
.product-price-main, .product-price-range-main {
  background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  padding: 1rem 1.2rem;
  border-radius: 10px;
  border: 1px solid #90caf9;
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--color-primary);
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}
.price-label {
  color: #555;
  font-size: 1rem;
  font-weight: 500;
}
.price-value {
  font-size: 1.2rem;
  font-weight: bold;
  color: var(--color-primary);
}
.order-form-main {
  padding: 2.5rem 2.5rem 2.5rem 2rem;
  background: #fff;
  border-radius: 0 18px 18px 0;
  min-width: 320px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}
@media (max-width: 900px) {
  .order-form-main {
    border-radius: 0 0 18px 18px;
    padding: 2rem 1rem;
  }
}
.new-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.7rem 1.2rem;
  margin-bottom: 1.2rem;
}
@media (max-width: 700px) {
  .new-form-grid {
    grid-template-columns: 1fr;
    gap: 0.7rem 0;
  }
}
.form-group {
  margin-bottom: 0.3rem !important;
}
.form-group.full-width {
  grid-column: 1 / -1;
}
.form-label {
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 0.2rem;
  font-size: 1rem;
}
.form-control, .form-select {
  border: 1.5px solid #e0e3e6;
  border-radius: 8px;
  padding: 0.55rem 0.9rem;
  font-size: 1rem;
  background: #fafafa;
  width: 100%;
  transition: border 0.2s;
}
.form-control:focus, .form-select:focus {
  border-color: var(--color-primary);
  background: white;
  outline: none;
}
.file-input {
  padding: 0.7rem;
  border: 2px dashed #dee2e6;
  background: #f8f9fa;
  border-radius: 8px;
  text-align: center;
}
.file-upload-info {
  margin-top: 0.3rem;
  font-size: 0.85rem;
  color: #6c757d;
}
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-start;
  margin-top: 1.2rem;
  padding-top: 1.2rem;
  border-top: 1px solid #f1f3f5;
  flex-wrap: wrap;
}
.btn-submit {
  background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
  border: none;
  padding: 0.7rem 1.5rem;
  font-weight: 600;
  border-radius: 8px;
  min-width: 150px;
  color: white;
  font-size: 1rem;
  transition: all 0.2s;
}
.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.18);
}
.btn-reset {
  padding: 0.7rem 1.5rem;
  font-weight: 600;
  border-radius: 8px;
  min-width: 120px;
  border: 1px solid #6c757d;
  background: white;
  color: #6c757d;
  font-size: 1rem;
  transition: all 0.2s;
}
.btn-reset:hover {
  background: #6c757d;
  color: white;
}
.instructions-section {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.07);
  margin-top: 0.5rem;
}
.instructions-title {
  font-size: 1.1rem;
  font-weight: bold;
  color: #333;
  margin-bottom: 8px;
  text-align: center;
}
.instructions-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.instructions-list li {
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  font-size: 0.95rem;
  color: #555;
}
@media (max-width: 700px) {
  .order-container.new-order-layout {
    padding: 0 !important;
  }
  .order-grid {
    grid-template-columns: 1fr;
  }
  .product-details-aside, .order-form-main {
    border-radius: 0 !important;
    padding: 1.2rem 0.7rem !important;
  }
  .product-image-big-container {
    max-width: 100vw;
    height: 220px;
  }
}
.order-grid-3cols {
  grid-template-columns: 420px 1fr 320px;
}
@media (max-width: 1100px) {
  .order-grid-3cols {
    grid-template-columns: 340px 1fr 240px;
  }
}
@media (max-width: 900px) {
  .order-grid-3cols {
    grid-template-columns: 1fr;
  }
  .order-summary-aside {
    border-right: none;
    border-top: 1px solid #e9ecef;
    border-radius: 0 0 18px 18px;
    margin-top: 2rem;
  }
}
.order-summary-aside {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-right: 1px solid #e9ecef;
  border-radius: 0 18px 18px 0;
  padding: 2.5rem 2rem 2rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 220px;
  max-width: 340px;
  box-shadow: 0 0 0 rgba(0,0,0,0);
}
.order-summary-box {
  width: 100%;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,173,239,0.07);
  padding: 1.5rem 1.2rem;
  border: 1.5px solid #e3f2fd;
}
.order-summary-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 1rem;
  text-align: right;
}
.order-summary-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 1rem;
  color: #333;
}
.order-summary-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.7rem;
  border-bottom: 1px dashed #e3f2fd;
  padding-bottom: 0.4rem;
}
.order-summary-list li:last-child {
  border-bottom: none;
  margin-bottom: 0;
}
.order-summary-list span {
  display: inline-block;
  min-width: 80px;
}
</style>
<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>

