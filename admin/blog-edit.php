<?php
/**
 * صفحة تعديل مقال للوحة تحكم مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

// التحقق من تسجيل دخول المدير
require_once 'auth.php';

// التحقق من وجود معرف المقال
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: blog.php');
    exit;
}

$blog_id = intval($_GET['id']);

// الحصول على بيانات المقال
try {
    $stmt = $db->prepare("SELECT * FROM blog WHERE blog_id = ?");
    $stmt->execute([$blog_id]);
    $blog = $stmt->fetch();
    
    if (!$blog) {
        header('Location: blog.php');
        exit;
    }
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع بيانات المقال: ' . $e->getMessage();
}

// معالجة تعديل المقال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من البيانات المطلوبة
        if (empty($_POST['title'])) {
            throw new Exception('يرجى إدخال عنوان المقال');
        }
        
        // إعداد البيانات
        $title = $_POST['title'];
        $slug = $_POST['slug'] ?? create_slug($title);
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? '';
        $tags = $_POST['tags'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        
        // التحقق من تفرد الرابط المختصر
        $check_slug = $db->prepare("SELECT blog_id FROM blog WHERE slug = ? AND blog_id != ?");
        $check_slug->execute([$slug, $blog_id]);
        if ($check_slug->rowCount() > 0) {
            $slug = $slug . '-' . time();
        }
        
        // معالجة تحميل الصورة
        $image_name = $blog['image']; // الاحتفاظ بالصورة الحالية كقيمة افتراضية
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = upload_blog_image($_FILES['image']);
            if ($new_image) {
                // حذف الصورة القديمة إذا كانت موجودة
                if (!empty($blog['image'])) {
                    $old_image_path = '../uploads/blog/' . $blog['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image_name = $new_image;
            }
        }
        
        // تحديث البيانات في قاعدة البيانات
        $stmt = $db->prepare("
            UPDATE blog 
            SET title = ?, slug = ?, content = ?, image = ?, category = ?, tags = ?, status = ?, updated_at = NOW()
            WHERE blog_id = ?
        ");
        
        $stmt->execute([$title, $slug, $content, $image_name, $category, $tags, $status, $blog_id]);
        
        $success_message = 'تم تحديث المقال بنجاح';
        
        // إعادة تحميل بيانات المقال بعد التحديث
        $stmt = $db->prepare("SELECT * FROM blog WHERE blog_id = ?");
        $stmt->execute([$blog_id]);
        $blog = $stmt->fetch();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// الحصول على قائمة التصنيفات
try {
    $categories_query = $db->query("
        SELECT DISTINCT category 
        FROM blog 
        WHERE category IS NOT NULL AND category != '' 
        ORDER BY category
    ");
    $categories = $categories_query->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// استدعاء ملف الرأس
require_once 'includes/header.php';

/**
 * دالة لإنشاء رابط مختصر من العنوان
 * 
 * @param string $title عنوان المقال
 * @return string الرابط المختصر
 */
function create_slug($title) {
    // تحويل الحروف إلى حروف صغيرة
    $slug = mb_strtolower($title, 'UTF-8');
    
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
        $slug = str_replace($ar, $en, $slug);
    }
    
    // استبدال المسافات والأحرف الخاصة بشرطات
    $slug = preg_replace('/[^a-z0-9]/', '-', $slug);
    
    // إزالة الشرطات المتكررة
    $slug = preg_replace('/-+/', '-', $slug);
    
    // إزالة الشرطات من البداية والنهاية
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * دالة لتحميل صورة المقال
 * 
 * @param array $file بيانات الملف المرفوع
 * @return string|false اسم الملف بعد التحميل أو false في حالة الفشل
 */
function upload_blog_image($file) {
    // التحقق من وجود المجلد وإنشائه إذا لم يكن موجودًا
    $target_dir = '../uploads/blog/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // الحصول على امتداد الملف
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // التحقق من نوع الملف (للصور فقط)
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    // إنشاء اسم فريد للملف
    $new_file_name = uniqid() . '_' . basename($file['name']);
    $target_file = $target_dir . $new_file_name;
    
    // تحميل الملف
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $new_file_name;
    }
    
    return false;
}
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <!-- رأس الصفحة -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">تعديل المقال</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-left">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="blog.php">المدونة</a></li>
                        <li class="breadcrumb-item active">تعديل المقال</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- محتوى رئيسي -->
    <div class="content">
        <div class="container-fluid">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> نجاح!</h5>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> خطأ!</h5>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">تعديل المقال</h3>
                </div>
                <div class="card-body">
                    <form action="blog-edit.php?id=<?php echo $blog_id; ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">عنوان المقال <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($blog['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">الرابط المختصر</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($blog['slug']); ?>">
                            <small class="form-text text-muted">الرابط المختصر للمقال. يتم إنشاؤه تلقائيًا من العنوان إذا تركته فارغًا.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">التصنيف</label>
                            <select class="form-control select2" id="category" name="category">
                                <option value="">بدون تصنيف</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($blog['category'] == $category) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new">إضافة تصنيف جديد...</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="new-category-group" style="display: none;">
                            <label for="new-category">تصنيف جديد</label>
                            <input type="text" class="form-control" id="new-category">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">صورة المقال</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                    <label class="custom-file-label" for="image">اختر ملفًا</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">الصورة الرئيسية للمقال. الأبعاد المثالية: 1200×800 بكسل.</small>
                            <?php if (!empty($blog['image'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/blog/<?php echo $blog['image']; ?>" alt="صورة المقال" style="max-height: 200px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">محتوى المقال</label>
                            <textarea class="form-control summernote" id="content" name="content" rows="10"><?php echo htmlspecialchars($blog['content']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">الوسوم</label>
                            <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($blog['tags']); ?>" placeholder="أدخل الوسوم مفصولة بفواصل">
                            <small class="form-text text-muted">أدخل الوسوم مفصولة بفواصل، مثال: طباعة، تصميم، كروت</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">حالة النشر</label>
                            <select class="form-control" id="status" name="status">
                                <option value="published" <?php echo ($blog['status'] == 'published') ? 'selected' : ''; ?>>منشور</option>
                                <option value="draft" <?php echo ($blog['status'] == 'draft') ? 'selected' : ''; ?>>مسودة</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            <a href="blog.php" class="btn btn-default">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- استدعاء ملف التذييل -->
<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // تهيئة محرر النصوص
    $('.summernote').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        callbacks: {
            onImageUpload: function(files) {
                for (let i = 0; i < files.length; i++) {
                    uploadImage(files[i], this);
                }
            }
        }
    });
    
    // تهيئة مربع اختيار الملفات
    bsCustomFileInput.init();
    
    // تهيئة Select2
    $('.select2').select2({
        tags: true
    });
    
    // معالجة إضافة تصنيف جديد
    $('#category').change(function() {
        if ($(this).val() === 'new') {
            $('#new-category-group').show();
        } else {
            $('#new-category-group').hide();
        }
    });
    
    $('#new-category').change(function() {
        var newCategory = $(this).val();
        if (newCategory) {
            // إضافة التصنيف الجديد إلى القائمة
            var newOption = new Option(newCategory, newCategory, true, true);
            $('#category').append(newOption).trigger('change');
        }
    });
    
    // إنشاء الرابط المختصر من العنوان
    $('#title').blur(function() {
        if ($('#slug').val() === '') {
            var title = $(this).val();
            $.ajax({
                url: 'blog_actions.php',
                method: 'POST',
                data: {
                    action: 'create_slug',
                    title: title
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#slug').val(data.slug);
                    }
                }
            });
        }
    });
    
    // دالة لتحميل الصور في المحرر
    function uploadImage(file, editor) {
        var formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'upload_image');
        
        $.ajax({
            url: 'blog_actions.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    $(editor).summernote('insertImage', data.url);
                } else {
                    alert('فشل تحميل الصورة: ' + data.message);
                }
            },
            error: function() {
                alert('حدث خطأ أثناء تحميل الصورة');
            }
        });
    }
});
</script>

