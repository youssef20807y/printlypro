<?php
/**
 * صفحة إدارة الأسئلة الشائعة للوحة تحكم مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

// التحقق من تسجيل دخول المدير
require_once 'auth.php';

// معالجة حذف السؤال
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $faq_id = intval($_GET['id']);
    
    try {
        $stmt = $db->prepare("DELETE FROM faq WHERE faq_id = ?");
        $stmt->execute([$faq_id]);
        
        $success_message = 'تم حذف السؤال بنجاح';
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء حذف السؤال: ' . $e->getMessage();
    }
}

// معالجة تغيير حالة السؤال
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
    $faq_id = intval($_GET['id']);
    
    try {
        $stmt = $db->prepare("SELECT status FROM faq WHERE faq_id = ?");
        $stmt->execute([$faq_id]);
        $faq = $stmt->fetch();
        
        if ($faq) {
            $new_status = ($faq['status'] == 'active') ? 'inactive' : 'active';
            
            $stmt = $db->prepare("UPDATE faq SET status = ? WHERE faq_id = ?");
            $stmt->execute([$new_status, $faq_id]);
            
            $success_message = 'تم تغيير حالة السؤال بنجاح';
        }
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء تغيير حالة السؤال: ' . $e->getMessage();
    }
}

// الحصول على قائمة الأسئلة الشائعة
try {
    $stmt = $db->query("SELECT * FROM faq ORDER BY category, order_num ASC");
    $faqs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع قائمة الأسئلة الشائعة: ' . $e->getMessage();
    $faqs = [];
}

// استدعاء ملف الرأس
require_once 'includes/header.php';
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <!-- رأس الصفحة -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إدارة الأسئلة الشائعة</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-left">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">الأسئلة الشائعة</li>
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
                    <h3 class="card-title">قائمة الأسئلة الشائعة</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#add-faq-modal">
                            <i class="fas fa-plus"></i> إضافة سؤال جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="faq-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>السؤال</th>
                                <th>التصنيف</th>
                                <th>الترتيب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faqs as $index => $faq): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($faq['question']); ?></td>
                                    <td><?php echo htmlspecialchars($faq['category'] ?? 'عام'); ?></td>
                                    <td><?php echo $faq['order_num']; ?></td>
                                    <td>
                                        <?php if ($faq['status'] == 'active'): ?>
                                            <span class="badge badge-success">نشط</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">غير نشط</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-faq" data-id="<?php echo $faq['faq_id']; ?>" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm edit-faq" data-id="<?php echo $faq['faq_id']; ?>" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="faq.php?action=toggle_status&id=<?php echo $faq['faq_id']; ?>" class="btn btn-warning btn-sm" title="<?php echo ($faq['status'] == 'active') ? 'إلغاء التفعيل' : 'تفعيل'; ?>">
                                            <i class="fas <?php echo ($faq['status'] == 'active') ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm delete-faq" data-id="<?php echo $faq['faq_id']; ?>" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة إضافة سؤال جديد -->
<div class="modal fade" id="add-faq-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">إضافة سؤال جديد</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="add-faq-form" action="faq_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="question">السؤال <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="question" name="question" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="answer">الإجابة <span class="text-danger">*</span></label>
                        <textarea class="form-control summernote" id="answer" name="answer" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">التصنيف</label>
                        <select class="form-control select2" id="category" name="category">
                            <option value="عام">عام</option>
                            <option value="خدمات الطباعة">خدمات الطباعة</option>
                            <option value="التصميم">التصميم</option>
                            <option value="الطلبات والدفع">الطلبات والدفع</option>
                            <option value="التوصيل">التوصيل</option>
                            <option value="new">إضافة تصنيف جديد...</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="new-category-group" style="display: none;">
                        <label for="new-category">تصنيف جديد</label>
                        <input type="text" class="form-control" id="new-category">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="order_num">الترتيب</label>
                                <input type="number" class="form-control" id="order_num" name="order_num" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">الحالة</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة عرض السؤال -->
<div class="modal fade" id="view-faq-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">عرض السؤال</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="view-faq-content">
                    <h5 id="view-question"></h5>
                    <div class="mt-3">
                        <strong>الإجابة:</strong>
                        <div id="view-answer" class="mt-2"></div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <strong>التصنيف:</strong>
                            <p id="view-category"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>الحالة:</strong>
                            <p id="view-status"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تعديل السؤال -->
<div class="modal fade" id="edit-faq-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">تعديل السؤال</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="edit-faq-form" action="faq_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="faq_id" id="edit_faq_id">
                    
                    <div class="form-group">
                        <label for="edit_question">السؤال <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_question" name="question" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_answer">الإجابة <span class="text-danger">*</span></label>
                        <textarea class="form-control summernote" id="edit_answer" name="answer" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category">التصنيف</label>
                        <select class="form-control select2" id="edit_category" name="category">
                            <option value="عام">عام</option>
                            <option value="خدمات الطباعة">خدمات الطباعة</option>
                            <option value="التصميم">التصميم</option>
                            <option value="الطلبات والدفع">الطلبات والدفع</option>
                            <option value="التوصيل">التوصيل</option>
                            <option value="new">إضافة تصنيف جديد...</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="edit-new-category-group" style="display: none;">
                        <label for="edit_new_category">تصنيف جديد</label>
                        <input type="text" class="form-control" id="edit_new_category">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_order_num">الترتيب</label>
                                <input type="number" class="form-control" id="edit_order_num" name="order_num" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">الحالة</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تأكيد الحذف -->
<div class="modal fade" id="delete-faq-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">تأكيد الحذف</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من رغبتك في حذف هذا السؤال؟</p>
                <p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                <a href="#" id="confirm-delete" class="btn btn-danger">حذف</a>
            </div>
        </div>
    </div>
</div>

<!-- استدعاء ملف التذييل -->
<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // تهيئة جدول البيانات
    $('#faq-table').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        }
    });
    
    // تهيئة محرر النصوص
    $('.summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
    
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
    
    // معالجة إضافة تصنيف جديد في نموذج التعديل
    $('#edit_category').change(function() {
        if ($(this).val() === 'new') {
            $('#edit-new-category-group').show();
        } else {
            $('#edit-new-category-group').hide();
        }
    });
    
    $('#edit_new_category').change(function() {
        var newCategory = $(this).val();
        if (newCategory) {
            // إضافة التصنيف الجديد إلى القائمة
            var newOption = new Option(newCategory, newCategory, true, true);
            $('#edit_category').append(newOption).trigger('change');
        }
    });
    
    // معالجة نقر زر العرض
    $('.view-faq').click(function() {
        var faq_id = $(this).data('id');
        
        // استرجاع بيانات السؤال باستخدام AJAX
        $.ajax({
            url: 'faq_actions.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get',
                faq_id: faq_id
            },
            success: function(response) {
                if (response.success) {
                    var faq = response.data;
                    
                    // ملء النموذج بالبيانات
                    $('#view-question').text(faq.question);
                    $('#view-answer').html(faq.answer);
                    $('#view-category').text(faq.category || 'عام');
                    $('#view-status').html(faq.status === 'active' ? 
                        '<span class="badge badge-success">نشط</span>' : 
                        '<span class="badge badge-secondary">غير نشط</span>');
                    
                    // فتح النافذة المنبثقة
                    $('#view-faq-modal').modal('show');
                } else {
                    alert('حدث خطأ أثناء استرجاع بيانات السؤال');
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال بالخادم');
            }
        });
    });
    
    // معالجة نقر زر التعديل
    $('.edit-faq').click(function() {
        var faq_id = $(this).data('id');
        
        // استرجاع بيانات السؤال باستخدام AJAX
        $.ajax({
            url: 'faq_actions.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get',
                faq_id: faq_id
            },
            success: function(response) {
                if (response.success) {
                    var faq = response.data;
                    
                    // ملء النموذج بالبيانات
                    $('#edit_faq_id').val(faq.faq_id);
                    $('#edit_question').val(faq.question);
                    $('#edit_answer').summernote('code', faq.answer);
                    
                    // التحقق من وجود التصنيف في القائمة
                    var categoryExists = false;
                    $('#edit_category option').each(function() {
                        if ($(this).val() === faq.category) {
                            categoryExists = true;
                            return false;
                        }
                    });
                    
                    // إضافة التصنيف إذا لم يكن موجودًا
                    if (!categoryExists && faq.category) {
                        var newOption = new Option(faq.category, faq.category, true, true);
                        $('#edit_category').append(newOption);
                    }
                    
                    $('#edit_category').val(faq.category || 'عام').trigger('change');
                    $('#edit_order_num').val(faq.order_num);
                    $('#edit_status').val(faq.status);
                    
                    // فتح النافذة المنبثقة
                    $('#edit-faq-modal').modal('show');
                } else {
                    alert('حدث خطأ أثناء استرجاع بيانات السؤال');
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال بالخادم');
            }
        });
    });
    
    // معالجة نقر زر الحذف
    $('.delete-faq').click(function() {
        var faq_id = $(this).data('id');
        $('#confirm-delete').attr('href', 'faq.php?action=delete&id=' + faq_id);
        $('#delete-faq-modal').modal('show');
    });
});
</script>

