<?php
/**
 * صفحة إدارة تصنيفات الخدمات
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// معالجة إضافة/تعديل/حذف التصنيف
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = isset($_POST['name']) ? clean_input(trim($_POST['name'])) : '';
                if (empty($name)) {
                    $errors[] = 'يرجى إدخال اسم التصنيف';
                } else {
                    try {
                        // التحقق من وجود التصنيف
                        $check_stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
                        $check_stmt->execute([$name]);
                        if ($check_stmt->fetchColumn() > 0) {
                            $errors[] = 'هذا التصنيف موجود مسبقاً. يرجى استخدام اسم آخر.';
                        } else {
                            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                            $stmt->execute([$name]);
                            $success = true;
                            $_SESSION['success_message'] = "تمت إضافة التصنيف بنجاح.";
                        }
                    } catch (PDOException $e) {
                        $errors[] = 'حدث خطأ أثناء إضافة التصنيف: ' . $e->getMessage();
                    }
                }
                break;

            case 'edit':
                $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
                $name = isset($_POST['name']) ? clean_input(trim($_POST['name'])) : '';
                $services = isset($_POST['services']) ? $_POST['services'] : [];
                
                if ($category_id <= 0) {
                    $errors[] = 'معرف التصنيف غير صالح';
                } elseif (empty($name)) {
                    $errors[] = 'يرجى إدخال اسم التصنيف';
                } else {
                    try {
                        $db->beginTransaction();
                        
                        // تحديث اسم التصنيف
                        $stmt = $db->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
                        $stmt->execute([$name, $category_id]);
                        
                        // تحديث الخدمات المرتبطة
                        if (!empty($services)) {
                            // إزالة جميع الخدمات المرتبطة بالتصنيف
                            $stmt = $db->prepare("UPDATE services SET category_id = NULL WHERE category_id = ?");
                            $stmt->execute([$category_id]);
                            
                            // إضافة الخدمات المحددة للتصنيف
                            $stmt = $db->prepare("UPDATE services SET category_id = ? WHERE service_id = ?");
                            foreach ($services as $service_id) {
                                $stmt->execute([$category_id, $service_id]);
                            }
                        }
                        
                        $db->commit();
                        $success = true;
                        $_SESSION['success_message'] = "تم تحديث التصنيف بنجاح.";
                    } catch (PDOException $e) {
                        $db->rollBack();
                        $errors[] = 'حدث خطأ أثناء تحديث التصنيف: ' . $e->getMessage();
                    }
                }
                break;

            case 'delete':
                $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
                if ($category_id <= 0) {
                    $errors[] = 'معرف التصنيف غير صالح';
                } else {
                    try {
                        $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
                        $stmt->execute([$category_id]);
                        $success = true;
                        $_SESSION['success_message'] = "تم حذف التصنيف بنجاح.";
                    } catch (PDOException $e) {
                        $errors[] = 'حدث خطأ أثناء حذف التصنيف: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// جلب جميع التصنيفات
try {
    $categories_query = $db->query("
        SELECT c.*, COUNT(s.service_id) as service_count 
        FROM categories c 
        LEFT JOIN services s ON c.category_id = s.category_id 
        GROUP BY c.category_id 
        ORDER BY c.name ASC
    ");
    $categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'حدث خطأ أثناء جلب التصنيفات: ' . $e->getMessage();
    $categories = [];
}
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إدارة تصنيفات الخدمات</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">إدارة التصنيفات</li>
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
                    <?= $_SESSION['success_message'] ?? 'تمت العملية بنجاح.' ?>
                    <?php unset($_SESSION['success_message']); ?>
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

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">إضافة تصنيف جديد</h3>
                        </div>
                        <form method="post" id="addCategoryForm">
                            <input type="hidden" name="action" value="add">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">اسم التصنيف</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">إضافة التصنيف</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">قائمة التصنيفات</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>اسم التصنيف</th>
                                            <th>عدد الخدمات</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $index => $category): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                                    <td><?= $category['service_count'] ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger delete-category"
                                                                data-id="<?= $category['category_id'] ?>"
                                                                data-name="<?= htmlspecialchars($category['name']) ?>">
                                                            <i class="fas fa-trash"></i> حذف
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">لا توجد تصنيفات</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تعديل التصنيف -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" id="editCategoryForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="editCategoryModalLabel">
                        <i class="fas fa-edit mr-2"></i>تعديل التصنيف
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_name" class="font-weight-bold">
                                    <i class="fas fa-tag mr-1"></i>اسم التصنيف
                                </label>
                                <input type="text" class="form-control form-control-lg" id="edit_name" name="name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list mr-1"></i>
                                        إدارة الخدمات المرتبطة
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="font-weight-bold">الخدمات المصنفة</label>
                                                <select class="form-control select2" id="edit_services" name="services[]" multiple>
                                                    <?php
                                                    $services_query = $db->query("SELECT service_id, name FROM services ORDER BY name ASC");
                                                    $services = $services_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($services as $service) {
                                                        echo '<option value="' . $service['service_id'] . '">' . htmlspecialchars($service['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="alert alert-info">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-info-circle fa-2x mr-3"></i>
                                                    <div>
                                                        <h6 class="alert-heading mb-1">معلومات هامة</h6>
                                                        <p class="mb-0">
                                                            - يمكنك اختيار عدة خدمات للتصنيف<br>
                                                            - استخدم مربع البحث للعثور على خدمات محددة<br>
                                                            - يمكنك سحب وإفلات الخدمات لترتيبها
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تأكيد الحذف -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" id="deleteCategoryForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" id="delete_category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">تأكيد الحذف</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف التصنيف "<span id="delete_category_name"></span>"؟</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // تهيئة Select2 مع تحسينات
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'اختر الخدمات المرتبطة بالتصنيف',
        allowClear: true,
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        },
        templateResult: formatServiceOption,
        templateSelection: formatServiceOption
    });

    // تنسيق عرض الخدمات في القائمة المنسدلة
    function formatServiceOption(service) {
        if (!service.id) {
            return service.text;
        }
        return $('<span><i class="fas fa-cog mr-2"></i>' + service.text + '</span>');
    }

    // معالجة تعديل التصنيف
    $('.edit-category').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        // جلب الخدمات المرتبطة بالتصنيف
        $.ajax({
            url: 'ajax/get_category_services.php',
            type: 'POST',
            data: { category_id: id },
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function(response) {
                if (response.success) {
                    $('#edit_category_id').val(id);
                    $('#edit_name').val(name);
                    $('#edit_services').val(response.services).trigger('change');
                    $('#editCategoryModal').modal('show');
                } else {
                    showError(response.error, response.details);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'حدث خطأ أثناء جلب بيانات التصنيف';
                var errorDetails = '';
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                    }
                    if (response.details) {
                        if (typeof response.details === 'object') {
                            errorDetails = '<br>تفاصيل الخطأ:<br>' +
                                'الرسالة: ' + response.details.message + '<br>' +
                                'الكود: ' + response.details.code + '<br>' +
                                'الملف: ' + response.details.file + '<br>' +
                                'السطر: ' + response.details.line;
                        } else {
                            errorDetails = '<br>تفاصيل: ' + response.details;
                        }
                    }
                } catch (e) {
                    errorDetails = '<br>محتوى الاستجابة:<br>' + xhr.responseText;
                }
                
                showError(errorMessage, errorDetails);
            }
        });
    });

    // معالجة حذف التصنيف
    $('.delete-category').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete_category_id').val(id);
        $('#delete_category_name').text(name);
        $('#deleteCategoryModal').modal('show');
    });

    // دالة لعرض رسائل الخطأ
    function showError(message, details) {
        var errorHtml = '<div class="alert alert-danger alert-dismissible">' +
            '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
            '<h5><i class="icon fas fa-ban"></i> خطأ!</h5>' +
            '<p>' + message + '</p>' +
            (details ? '<div class="error-details">' + details + '</div>' : '') +
            '</div>';
        
        // إضافة رسالة الخطأ في بداية المحتوى
        $('.content').prepend(errorHtml);
        
        // إزالة رسالة الخطأ بعد 5 ثواني
        setTimeout(function() {
            $('.alert-danger').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script> 