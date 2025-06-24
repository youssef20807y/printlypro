<?php
/**
 * صفحة تعديل خدمة موجودة في لوحة تحكم المسؤول
 */
define("PRINTLY", true);
require_once "auth.php";
require_once "includes/header.php";

$errors = [];
$success = false;

// الحصول على ID الخدمة
$service_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($service_id <= 0) {
    $_SESSION["error_message"] = "رقم تعريف الخدمة غير صالح.";
    redirect("services.php");
}

// جلب بيانات الخدمة الحالية
$stmt = $db->prepare("SELECT * FROM services WHERE service_id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    $_SESSION["error_message"] = "الخدمة غير موجودة.";
    redirect("services.php");
}

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
$custom_fields = $custom_fields_query->fetchAll(PDO::FETCH_ASSOC);

// جلب الحقول المخصصة المرتبطة بالخدمة
$service_fields_query = $db->prepare("SELECT field_id FROM service_fields WHERE service_id = ?");
$service_fields_query->execute([$service_id]);
$service_field_ids = $service_fields_query->fetchAll(PDO::FETCH_COLUMN);

// التحقق من التعديل
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // التحقق من البيانات الأساسية
    $name = isset($_POST["name"]) ? clean_input($_POST["name"]) : "";
    $description = isset($_POST["description"]) ? $_POST["description"] : ""; // لا تستخدم clean_input هنا إذا كان summernote يرسل HTML
    $price_start = isset($_POST["price_start"]) ? floatval($_POST["price_start"]) : 0;
    $category_id = isset($_POST["category_id"]) ? intval($_POST["category_id"]) : 0;
    $status = isset($_POST["status"]) ? clean_input($_POST["status"]) : "inactive";
    $is_featured = isset($_POST["is_featured"]) ? 1 : 0;

    // التحقق من الخصائص الإضافية
    $require_paper_type = isset($_POST["require_paper_type"]) ? 1 : 0;
    $require_size = isset($_POST["require_size"]) ? 1 : 0;
    $require_colors = isset($_POST["require_colors"]) ? 1 : 0;
    $require_design_file = isset($_POST["require_design_file"]) ? 1 : 0;
    $require_notes = isset($_POST["require_notes"]) ? 1 : 0;

    // الحقول المخصصة
    $custom_fields_selected = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : [];

    // تحقق: إذا كان هناك حقول مخصصة نشطة في قاعدة البيانات ولم يتم اختيار أي منها، أضف رسالة خطأ
    if (count($custom_fields_selected) === 0 && count($custom_fields) > 0) {
        $errors[] = 'يرجى اختيار حقل مخصص واحد على الأقل ليظهر في صفحة الطلب.';
    }

    // التحقق من صحة البيانات
    if (empty($name))
        $errors[] = "يرجى إدخال اسم الخدمة";
    if (empty($description))
        $errors[] = "يرجى إدخال وصف الخدمة";
    if ($price_start <= 0)
        $errors[] = "يرجى إدخال سعر صحيح للخدمة";
    if ($category_id <= 0)
        $errors[] = "يرجى اختيار تصنيف الخدمة";
    if (!in_array($status, ["active", "inactive"]))
        $errors[] = "حالة الخدمة غير صالحة";

    // معالجة الصورة الجديدة (إذا تم رفعها)
    $new_image = $service["image"]; // الاحتفاظ بالصورة القديمة افتراضياً
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $allowed_types = ["image/jpeg", "image/png", "image/gif"];
        $max_size = 2 * 1024 * 1024; // 2 ميجابايت

        if (!in_array($_FILES["image"]["type"], $allowed_types)) {
            $errors[] =
                "نوع الملف غير مسموح به. يرجى رفع صورة بصيغة JPG أو PNG أو GIF";
        } elseif ($_FILES["image"]["size"] > $max_size) {
            $errors[] = "حجم الصورة كبير جداً. الحد الأقصى هو 2 ميجابايت";
        } else {
            // إنشاء اسم فريد للصورة
            $filename = uniqid("service_") . "_" . preg_replace("/[^A-Za-z0-9.\-_]/", "", basename($_FILES["image"]["name"]) );
            $upload_path = "../uploads/services/" . $filename;

            // التأكد من وجود المجلد
            if (!is_dir("../uploads/services/")) {
                mkdir("../uploads/services/", 0755, true);
            }

            // رفع الصورة الجديدة
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $upload_path)) {
                // حذف الصورة القديمة إذا كانت موجودة ومختلفة
                if (
                    !empty($service["image"]) &&
                    $service["image"] != $filename &&
                    file_exists("../uploads/services/" . $service["image"])
                ) {
                    unlink("../uploads/services/" . $service["image"]);
                }
                $new_image = $filename; // تحديث اسم الصورة الجديدة
            } else {
                $errors[] = "فشل في رفع الصورة الجديدة. يرجى المحاولة مرة أخرى";
            }
        }
    }

    // إذا لم يكن هناك أخطاء، نقوم بتحديث الخدمة
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE services
                SET name = ?, description = ?, price_start = ?, category_id = ?,
                    image = ?, status = ?, is_featured = ?,
                    require_paper_type = ?, require_size = ?, require_colors = ?,
                    require_design_file = ?, require_notes = ?
                WHERE service_id = ?
            ");

            $stmt->execute([
                $name,
                $description,
                $price_start,
                $category_id,
                $new_image,
                $status,
                $is_featured,
                $require_paper_type,
                $require_size,
                $require_colors,
                $require_design_file,
                $require_notes,
                $service_id,
            ]);

            // تحديث الحقول المخصصة للخدمة
            // حذف القديم
            $db->prepare("DELETE FROM service_fields WHERE service_id = ?")->execute([$service_id]);
            // إضافة الجديد
            if (!empty($custom_fields_selected)) {
                $field_stmt = $db->prepare("
                    INSERT INTO service_fields (service_id, field_id, is_required, order_num, status)
                    VALUES (?, ?, 1, ?, 'active')
                ");
                foreach ($custom_fields_selected as $index => $field_id) {
                    $field_stmt->execute([$service_id, $field_id, $index + 1]);
                }
            }
            // إعادة جلب الحقول المرتبطة بعد التحديث
            $service_fields_query = $db->prepare("SELECT field_id FROM service_fields WHERE service_id = ?");
            $service_fields_query->execute([$service_id]);
            $service_field_ids = $service_fields_query->fetchAll(PDO::FETCH_COLUMN);

            $success = true;
            $_SESSION["success_message"] = "تم تحديث الخدمة بنجاح.";

            // تحديث البيانات المعروضة في النموذج بعد التحديث الناجح
            $service = array_merge($service, [
                "name" => $name,
                "description" => $description,
                "price_start" => $price_start,
                "category_id" => $category_id,
                "image" => $new_image,
                "status" => $status,
                "is_featured" => $is_featured,
                "require_paper_type" => $require_paper_type,
                "require_size" => $require_size,
                "require_colors" => $require_colors,
                "require_design_file" => $require_design_file,
                "require_notes" => $require_notes,
            ]);

            // إعادة جلب التصنيفات لتضمين التصنيف الجديد إذا كان جديداً
            $categories_query = $db->query("SELECT category_id, name FROM categories ORDER BY name ASC");
            $categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errors[] = "فشل في تحديث الخدمة: " . $e->getMessage();
            error_log("Service update error: " . $e->getMessage());
        }
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">تعديل الخدمة: <?= htmlspecialchars($service["name"]) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="services.php">إدارة الخدمات</a></li>
                        <li class="breadcrumb-item active">تعديل الخدمة</li>
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
                    <?= $_SESSION["success_message"] ?? "تم تحديث الخدمة بنجاح." ?>
                    <?php unset($_SESSION["success_message"]); // Clear message after displaying ?>
                     <br>
                    <a href="services.php" class="btn btn-sm btn-success mt-2">العودة إلى قائمة الخدمات</a>
                    <a href="../service-details.php?id=<?= $service_id ?>" class="btn btn-sm btn-info mt-2" target="_blank">معاينة الخدمة</a>
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
                    <h3 class="card-title">تعديل بيانات الخدمة</h3>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <!-- البيانات الأساسية -->
                        <div class="form-group">
                            <label for="name">اسم الخدمة <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($service["name"]) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">وصف الخدمة <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="form-control summernote" rows="5" required><?= htmlspecialchars($service["description"]) ?></textarea>
                             <small class="text-muted">سيتم عرض هذا الوصف في صفحة تفاصيل الخدمة وصفحة قائمة الخدمات (مع تجاهل التنسيق المتقدم في القائمة).</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_start">السعر (يبدأ من) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" id="price_start" name="price_start" class="form-control" min="0.01" step="0.01" value="<?= htmlspecialchars($service["price_start"]) ?>" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">ريال</span>
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
                                            <option value="<?= $category['category_id'] ?>" <?= ($service['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
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
                            <label>صورة الخدمة الحالية</label>
                            <div class="mb-2">
                            <?php if (!empty($service["image"]) && file_exists("../uploads/services/" . $service["image"])): ?>
                                <img src="../uploads/services/<?= htmlspecialchars($service["image"]) ?>" alt="الصورة الحالية" id="current-image" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                            <?php else: ?>
                                <span class="text-muted">لا توجد صورة حالية.</span>
                            <?php endif; ?>
                            </div>
                             <div id="image-preview-container" class="mt-2 mb-2" style="display: none;">
                                <label>معاينة الصورة الجديدة:</label><br>
                                <img id="image-preview" src="#" alt="معاينة الصورة الجديدة" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                            </div>
                            <label for="image_upload">تغيير الصورة (اختياري)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="image_upload" name="image" accept="image/jpeg, image/png, image/gif">
                                <label class="custom-file-label" for="image_upload">اختر صورة جديدة...</label>
                            </div>
                            <small class="text-muted">الحد الأقصى للحجم: 2 ميجابايت. الصيغ المسموحة: JPG, PNG, GIF</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">حالة الخدمة</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="active" <?= $service["status"] == "active" ? "selected" : "" ?>>نشط</option>
                                        <option value="inactive" <?= $service["status"] == "inactive" ? "selected" : "" ?>>غير نشط</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>خيارات إضافية</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="is_featured" class="custom-control-input" id="is_featured" <?= !empty($service["is_featured"]) ? "checked" : "" ?>>
                                        <label class="custom-control-label" for="is_featured">خدمة مميزة (تظهر في الرئيسية)</label>
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
                                <div class="row">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_paper_type" name="require_paper_type" <?= !empty($service["require_paper_type"]) ? "checked" : "" ?>>
                                                <label class="custom-control-label" for="require_paper_type">نوع الورق مطلوب</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_size" name="require_size" <?= !empty($service["require_size"]) ? "checked" : "" ?>>
                                                <label class="custom-control-label" for="require_size">المقاس مطلوب</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_colors" name="require_colors" <?= !empty($service["require_colors"]) ? "checked" : "" ?>>
                                                <label class="custom-control-label" for="require_colors">عدد الألوان مطلوب</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_design_file" name="require_design_file" <?= !empty($service["require_design_file"]) ? "checked" : "" ?>>
                                                <label class="custom-control-label" for="require_design_file">رفع ملف التصميم مطلوب</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="require_notes" name="require_notes" <?= !empty($service["require_notes"]) ? "checked" : "" ?>>
                                                <label class="custom-control-label" for="require_notes">ملاحظات إضافية مطلوبة</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- الحقول المخصصة -->
                                <div class="mt-4">
                                    <h5>الحقول المخصصة الإضافية</h5>
                                    <div id="customFieldsContainer">
                                        <?php if (empty($custom_fields)): ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                لا توجد حقول مخصصة. 
                                                <a href="custom-fields.php" target="_blank" class="alert-link">إنشاء حقول مخصصة</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($custom_fields as $field): 
                                                    $checked = in_array($field['field_id'], $service_field_ids) ? 'checked' : '';
                                                ?>
                                                <div class="col-md-6 col-sm-12 mb-3">
                                                    <div class="form-group">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" 
                                                                   id="custom_field_<?= $field['field_id'] ?>" 
                                                                   name="custom_fields[]" 
                                                                   value="<?= $field['field_id'] ?>"
                                                                   <?= $checked ?>>
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
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> حفظ التعديلات</button>
                        <a href="services.php" class="btn btn-secondary">إلغاء</a>
                        <a href="../service-details.php?id=<?= $service_id ?>" class="btn btn-info float-right" target="_blank"><i class="fas fa-eye mr-1"></i> معاينة</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>

<script>
// Script to show uploaded file name in custom file input and preview
$(document).ready(function () {
  bsCustomFileInput.init();
  // Initialize Summernote if it\'s not already initialized in footer
  if ($.fn.summernote) {
      $(".summernote").summernote({
          height: 150, // set editor height
          minHeight: null, // set minimum height of editor
          maxHeight: null, // set maximum height of editor
          focus: false, // set focus to editable area after initializing summernote
          placeholder: "اكتب وصفاً تفصيلياً للخدمة هنا...",
          toolbar: [
              ["style", ["style"]],
              ["font", ["bold", "italic", "underline", "clear"]],
              ["para", ["ul", "ol", "paragraph"]],
              ["insert", ["link"]], // Removed image and video buttons
              ["view", ["fullscreen", "codeview", "help"]],
          ],
      });
  }

   // Image preview for file input
  $("#image_upload").on("change", function(event) {
    var reader = new FileReader();
    reader.onload = function(){
      var output = document.getElementById("image-preview");
      var container = document.getElementById("image-preview-container");
      var currentImage = document.getElementById("current-image");
      output.src = reader.result;
      container.style.display = "block";
      if(currentImage) currentImage.style.display = "none"; // Hide current image when previewing new one
    };
    if(event.target.files[0]){
        reader.readAsDataURL(event.target.files[0]);
    } else {
        // Hide preview and show current image if no file selected or selection is cancelled
        var container = document.getElementById("image-preview-container");
        var currentImage = document.getElementById("current-image");
        container.style.display = "none";
         if(currentImage) currentImage.style.display = "block";
    }
  });

});
</script>
'"'"'
