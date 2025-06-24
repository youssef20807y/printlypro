<?php
/**
 * صفحة إضافة خدمة جديدة في لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// معالجة إضافة خدمة جديدة
$errors = [];
$success = false;

// جلب التصنيفات الموجودة
$categories_query = $db->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);

// جلب الحقول المخصصة النشطة
$custom_fields_query = $db->query("
    SELECT cf.*, ft.type_name 
    FROM custom_fields cf
    LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
    WHERE cf.status = 'active'
    ORDER BY cf.order_num, cf.field_name
");
$custom_fields_db = $custom_fields_query->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من البيانات المدخلة
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
    if (empty($name)) {
        $errors[] = 'يرجى إدخال اسم الخدمة';
    }

    $description = isset($_POST['description']) ? $_POST['description'] : ''; // لا تستخدم clean_input هنا إذا كان summernote يرسل HTML
    // يمكنك استخدام مكتبة لتنقية HTML إذا لزم الأمر، مثل HTML Purifier
    // $purifier_config = HTMLPurifier_Config::createDefault();
    // $purifier = new HTMLPurifier($purifier_config);
    // $description = $purifier->purify($description);
    if (empty($description)) {
        $errors[] = 'يرجى إدخال وصف الخدمة';
    }

    $price_start = isset($_POST['price_start']) ? floatval($_POST['price_start']) : 0;
    if ($price_start <= 0) {
        $errors[] = 'يرجى إدخال سعر صحيح للخدمة';
    }

    // الحصول على التصنيف
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if ($category_id <= 0) {
        $errors[] = 'يرجى اختيار تصنيف الخدمة';
    }

    $status = isset($_POST['status']) ? clean_input($_POST['status']) : 'inactive';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // الحقول الجديدة
    $require_paper_type = isset($_POST['require_paper_type']) ? 1 : 0;
    $require_size = isset($_POST['require_size']) ? 1 : 0;
    $require_colors = isset($_POST['require_colors']) ? 1 : 0;
    $require_design_file = isset($_POST['require_design_file']) ? 1 : 0;
    $require_notes = isset($_POST['require_notes']) ? 1 : 0;

    // الحقول المخصصة
    $selected_custom_fields = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : [];

    // تحقق: إذا كان هناك حقول مخصصة نشطة في قاعدة البيانات ولم يتم اختيار أي منها، أضف رسالة خطأ
    if (count($selected_custom_fields) === 0 && count($custom_fields_db) > 0) {
        $errors[] = 'يرجى اختيار حقل مخصص واحد على الأقل ليظهر في صفحة الطلب.';
    }

    // معالجة الصورة
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2 ميجابايت

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'نوع الملف غير مسموح به. يرجى رفع صورة بصيغة JPG أو PNG أو GIF';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'حجم الصورة كبير جداً. الحد الأقصى هو 2 ميجابايت';
        } else {
            // إنشاء اسم فريد للصورة
            $image_name = uniqid('service_') . '_' . preg_replace('/[^A-Za-z0-9.\-_]/', '', basename($_FILES['image']['name']));
            $upload_path = '../uploads/services/' . $image_name;

            // التأكد من وجود المجلد
            if (!is_dir('../uploads/services/')) {
                mkdir('../uploads/services/', 0755, true);
            }

            // رفع الصورة
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                 $image = $image_name; // تم الرفع بنجاح
            } else {
                $errors[] = 'فشل في رفع الصورة. يرجى المحاولة مرة أخرى';
                $image = '';
            }
        }
    }

    // إذا لم يكن هناك أخطاء، نقوم بإضافة الخدمة
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO services (
                    name, description, price_start, category_id,
                    image, status, is_featured,
                    require_paper_type, require_size, require_colors,
                    require_design_file, require_notes
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?
                )
            ");

            $stmt->execute([
                $name, $description, $price_start, $category_id,
                $image, $status, $is_featured,
                $require_paper_type, $require_size, $require_colors,
                $require_design_file, $require_notes
            ]);

            $service_id = $db->lastInsertId();

            // إضافة الحقول المخصصة للخدمة
            if (!empty($selected_custom_fields)) {
                $field_stmt = $db->prepare("
                    INSERT INTO service_fields (service_id, field_id, is_required, order_num, status)
                    VALUES (?, ?, 1, ?, 'active')
                ");
                
                foreach ($selected_custom_fields as $index => $field_id) {
                    $field_stmt->execute([$service_id, $field_id, $index + 1]);
                }
            }

            $success = true;
            $_SESSION['success_message'] = "تمت إضافة الخدمة '$name' بنجاح.";

        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء إضافة الخدمة: ' . $e->getMessage();
            error_log('Service add error: ' . $e->getMessage());

            // حذف الصورة في حالة فشل إضافة الخدمة
            if (!empty($image)) {
                $image_path = '../uploads/services/' . $image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }
    }
}
?>
<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إضافة خدمة جديدة</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="services.php">إدارة الخدمات</a></li>
                        <li class="breadcrumb-item active">إضافة خدمة</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> نجاح!</h5>
                    <?= $_SESSION['success_message'] ?? 'تمت إضافة الخدمة بنجاح.' ?>
                    <?php unset($_SESSION['success_message']); ?>
                    <br>
                    <a href="services.php" class="btn btn-sm btn-success mt-2">العودة إلى قائمة الخدمات</a>
                    <a href="service-add.php" class="btn btn-sm btn-primary mt-2">إضافة خدمة أخرى</a>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> خطأ!</h5>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card-primary card-outline">
                 <div class="card-header">
                    <h3 class="card-title">بيانات الخدمة</h3>
                </div>
                <form method="post" enctype="multipart/form-data" id="addServiceForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">اسم الخدمة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">وصف الخدمة <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" id="description" name="description" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <small class="text-muted">سيتم عرض هذا الوصف في صفحة تفاصيل الخدمة وصفحة قائمة الخدمات (مع تجاهل التنسيق المتقدم في القائمة).</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_start">السعر (يبدأ من) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="price_start" name="price_start" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['price_start'] ?? '') ?>" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">جنيه</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id">تصنيف الخدمة <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="category_id" name="category_id" required>
                                        <option value="">-- اختر التصنيف --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['category_id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        <a href="categories.php" target="_blank">إدارة التصنيفات</a>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="image_upload">صورة الخدمة (اختياري)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="image_upload" name="image" accept="image/jpeg, image/png, image/gif">
                                <label class="custom-file-label" for="image_upload">اختر صورة...</label>
                            </div>
                            <small class="text-muted">الحد الأقصى للحجم: 2 ميجابايت. الصيغ المسموحة: JPG, PNG, GIF</small>
                            <div id="image-preview-container" class="mt-2" style="display: none;">
                                <img id="image-preview" src="#" alt="معاينة الصورة" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">حالة الخدمة</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" <?= (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : '' ?>>نشط</option>
                                        <option value="inactive" <?= (!isset($_POST['status']) || $_POST['status'] == 'inactive') ? 'selected' : '' ?>>غير نشط</option> <!-- Default to inactive -->
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                     <label>خيارات إضافية</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="is_featured">خدمة مميزة (تظهر في الصفحة الرئيسية)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- قسم الخصائص الإضافية المطلوبة -->
                        <div class="card card-secondary mt-4">
                            <div class="card-header">
                                <h3 class="card-title">تحديد الحقول المطلوبة في صفحة الطلب</h3>
                                <div class="card-tools">
                                    <a href="custom-fields.php" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-cog"></i> إدارة الحقول المخصصة
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">حدد الخصائص التي يجب على العميل إدخالها عند طلب هذه الخدمة:</p>
                                
                                <!-- الحقول الأساسية -->
                                <div class="row">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_paper_type" name="require_paper_type" <?= isset($_POST['require_paper_type']) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="require_paper_type">نوع الورق مطلوب</label>
                                            </div>
                                            <small class="text-muted">يمكن تخصيص الخيارات من إدارة الحقول</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_size" name="require_size" <?= isset($_POST['require_size']) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="require_size">المقاس مطلوب</label>
                                            </div>
                                            <small class="text-muted">يمكن تخصيص الخيارات من إدارة الحقول</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_colors" name="require_colors" <?= isset($_POST['require_colors']) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="require_colors">عدد الألوان مطلوب</label>
                                            </div>
                                            <small class="text-muted">يمكن تخصيص الخيارات من إدارة الحقول</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_design_file" name="require_design_file" <?= isset($_POST['require_design_file']) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="require_design_file">رفع ملف التصميم مطلوب</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_notes" name="require_notes" <?= isset($_POST['require_notes']) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="require_notes">ملاحظات إضافية مطلوبة</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- الحقول المخصصة -->
                                <div class="mt-4">
                                    <h5>الحقول المخصصة الإضافية</h5>
                                    <div id="customFieldsContainer">
                                        <?php if (empty($custom_fields_db)): ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                لا توجد حقول مخصصة. 
                                                <a href="custom-fields.php" target="_blank" class="alert-link">إنشاء حقول مخصصة</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($custom_fields_db as $field): 
                                                    $checked = (isset($selected_custom_fields) && in_array($field['field_id'], $selected_custom_fields)) ? 'checked' : '';
                                                ?>
                                                <div class="col-md-6 col-sm-12 mb-3">
                                                    <div class="form-group">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" 
                                                                   id="custom_field_<?= $field['field_id'] ?>" 
                                                                   name="custom_fields[]" 
                                                                   value="<?= $field['field_id'] ?>"
                                                                   <?= $checked ?> >
                                                            <label class="custom-control-label" for="custom_field_<?= $field['field_id'] ?>">
                                                                <?= htmlspecialchars($field['field_label']) ?>
                                                                <span class="badge badge-secondary"><?= htmlspecialchars($field['type_name']) ?></span>
                                                            </label>
                                                        </div>
                                                        <small class="text-muted"><?= htmlspecialchars($field['help_text'] ?? '') ?></small>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                    </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle mr-2"></i> إضافة الخدمة</button>
                        <a href="services.php" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function () {
  // Initialize bsCustomFileInput
  bsCustomFileInput.init();

  // Initialize Summernote
  if ($.fn.summernote) {
      $('.summernote').summernote({
          height: 150,
          placeholder: 'اكتب وصفاً تفصيلياً للخدمة هنا...',
          toolbar: [
              ['style', ['style']],
              ['font', ['bold', 'italic', 'underline', 'clear']],
              ['para', ['ul', 'ol', 'paragraph']],
              ['insert', ['link']], // Removed image and video buttons
              ['view', ['fullscreen', 'codeview', 'help']]
          ]
      });
  }

  // Image preview for file input
  $('#image_upload').on('change', function(event) {
    var reader = new FileReader();
    reader.onload = function(){
      var output = document.getElementById('image-preview');
      var container = document.getElementById('image-preview-container');
      output.src = reader.result;
      container.style.display = 'block';
    };
    if(event.target.files[0]){
        reader.readAsDataURL(event.target.files[0]);
    } else {
        // Hide preview if no file selected or selection is cancelled
        var container = document.getElementById('image-preview-container');
        container.style.display = 'none';
    }
  });
});
</script>
'''
