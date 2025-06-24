<?php
/**
 * صفحة إدارة المدونة للوحة تحكم مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

// التحقق من تسجيل دخول المدير
require_once 'auth.php';

// معالجة حذف المقال
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $blog_id = intval($_GET['id']);
    
    try {
        // الحصول على معلومات المقال قبل الحذف
        $stmt = $db->prepare("SELECT image FROM blog WHERE blog_id = ?");
        $stmt->execute([$blog_id]);
        $blog = $stmt->fetch();
        
        // حذف صورة المقال إذا كانت موجودة
        if ($blog && !empty($blog['image'])) {
            $image_path = '../uploads/blog/' . $blog['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // حذف المقال من قاعدة البيانات
        $stmt = $db->prepare("DELETE FROM blog WHERE blog_id = ?");
        $stmt->execute([$blog_id]);
        
        $success_message = 'تم حذف المقال بنجاح';
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء حذف المقال: ' . $e->getMessage();
    }
}

// الحصول على قائمة المقالات
try {
    $stmt = $db->query("
        SELECT b.*, u.username as author_name 
        FROM blog b 
        LEFT JOIN users u ON b.author_id = u.user_id 
        ORDER BY b.created_at DESC
    ");
    $blogs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع قائمة المقالات: ' . $e->getMessage();
    $blogs = [];
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
                    <h1 class="m-0">إدارة المدونة</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-left">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">المدونة</li>
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
                    <h3 class="card-title">قائمة المقالات</h3>
                    <div class="card-tools">
                        <a href="blog-add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> إضافة مقال جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="blog-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>العنوان</th>
                                <th>الكاتب</th>
                                <th>التصنيف</th>
                                <th>المشاهدات</th>
                                <th>الحالة</th>
                                <th>تاريخ النشر</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blogs as $index => $blog): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php if (!empty($blog['image'])): ?>
                                            <img src="../uploads/blog/<?php echo $blog['image']; ?>" alt="<?php echo $blog['title']; ?>" style="max-height: 50px;">
                                        <?php else: ?>
                                            <img src="../assets/images/default-blog.jpg" alt="Default Image" style="max-height: 50px;">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($blog['title']); ?></td>
                                    <td><?php echo htmlspecialchars($blog['author_name'] ?? 'غير معروف'); ?></td>
                                    <td><?php echo htmlspecialchars($blog['category'] ?? 'بدون تصنيف'); ?></td>
                                    <td><?php echo $blog['views']; ?></td>
                                    <td>
                                        <?php if ($blog['status'] == 'published'): ?>
                                            <span class="badge badge-success">منشور</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">مسودة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($blog['created_at'])); ?></td>
                                    <td>
                                        <a href="../blog-details.php?slug=<?php echo $blog['slug']; ?>" target="_blank" class="btn btn-info btn-sm" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="blog-edit.php?id=<?php echo $blog['blog_id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-danger btn-sm delete-blog" data-id="<?php echo $blog['blog_id']; ?>" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

<!-- نافذة تأكيد الحذف -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">تأكيد الحذف</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من رغبتك في حذف هذا المقال؟</p>
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
    $('#blog-table').DataTable({
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
    
    // معالجة نقر زر الحذف
    $('.delete-blog').click(function(e) {
        e.preventDefault();
        var blog_id = $(this).data('id');
        $('#confirm-delete').attr('href', 'blog.php?action=delete&id=' + blog_id);
        $('#delete-modal').modal('show');
    });
});
</script>

