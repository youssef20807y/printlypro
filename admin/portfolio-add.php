<?php
/**
 * صفحة إضافة عمل جديد في معرض الأعمال
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على قائمة الخدمات
try {
    $services_query = $db->query("SELECT service_id, name FROM services WHERE status = 'active' ORDER BY name ASC");
    $services = $services_query->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع الخدمات: ' . $e->getMessage();
    $services = [];
}

// الحصول على قائمة التصنيفات
try {
    $categories_query = $db->query("SELECT category_id, name FROM categories ORDER BY name ASC");
    $categories = $categories_query->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع التصنيفات: ' . $e->getMessage();
    $categories = [];
}

// معالجة إضافة عمل جديد
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من البيانات المدخلة
    $title = isset($_POST['title']) ? clean_input($_POST['title']) : '';
    if (empty($title)) {
        $errors[] = 'يرجى إدخال عنوان العمل';
    }
    
    $description = isset($_POST['description']) ? clean_input($_POST['description']) : '';
    if (empty($description)) {
        $errors[] = 'يرجى إدخال وصف العمل';
    }
    
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if ($category_id <= 0) {
        $errors[] = 'يرجى اختيار تصنيف العمل';
    }
    
    // معالجة الصورة
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5 ميجابايت
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'نوع الملف غير مسموح به. يرجى رفع صورة بصيغة JPG أو PNG أو GIF';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'حجم الصورة كبير جداً. الحد الأقصى هو 5 ميجابايت';
        } else {
            // إنشاء اسم فريد للصورة
            $image = uniqid() . '_' . basename($_FILES['image']['name']);
            $upload_path = '../uploads/portfolio/' . $image;
            
            // التأكد من وجود المجلد
            if (!is_dir('../uploads/portfolio/')) {
                mkdir('../uploads/portfolio/', 0755, true);
            }
            
            // رفع الصورة
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = 'فشل في رفع الصورة. يرجى المحاولة مرة أخرى';
                $image = '';
            }
        }
    } else {
        $errors[] = 'يرجى اختيار صورة للعمل';
    }
    
    // إذا لم يكن هناك أخطاء، نقوم بإضافة العمل
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO portfolio (
                    title, description, service_id, category_id, image
                ) VALUES (
                    ?, ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $title, $description, $service_id, $category_id, $image
            ]);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء إضافة العمل: ' . $e->getMessage();
            
            // حذف الصورة في حالة فشل إضافة العمل
            if (!empty($image)) {
                $image_path = '../uploads/portfolio/' . $image;
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
                    <h1 class="m-0">إضافة عمل جديد</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="portfolio.php">معرض الأعمال</a></li>
                        <li class="breadcrumb-item active">إضافة عمل</li>
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
                    تمت إضافة العمل بنجاح.
                    <br>
                    <a href="portfolio.php" class="btn btn-sm btn-success mt-2">العودة إلى معرض الأعمال</a>
                    <a href="portfolio-add.php" class="btn btn-sm btn-primary mt-2">إضافة عمل آخر</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> خطأ!</h5>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">بيانات العمل</h3>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">عنوان العمل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? $_POST['title'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">وصف العمل <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="service_id">الخدمة المرتبطة</label>
                                        <select class="form-control select2" id="service_id" name="service_id">
                                            <option value="0">-- اختر الخدمة --</option>
                                            <?php foreach ($services as $service): ?>
                                                <option value="<?php echo $service['service_id']; ?>" <?php echo (isset($_POST['service_id']) && $_POST['service_id'] == $service['service_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $service['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id">تصنيف العمل <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="category_id" name="category_id" required>
                                            <option value="">-- اختر التصنيف --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
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
                                <label for="image">صورة العمل <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image" name="image" accept="image/*" required>
                                        <label class="custom-file-label" for="image">اختر صورة</label>
                                    </div>
                                </div>
                                <small class="text-muted">الحد الأقصى للحجم: 5 ميجابايت. الصيغ المسموحة: JPG, PNG, GIF</small>
                                <div class="mt-2" style="display: none;">
                                    <img src="" class="img-preview" style="max-width: 200px; max-height: 200px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">إضافة العمل</button>
                            <a href="portfolio.php" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
