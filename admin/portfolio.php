<?php
/**
 * صفحة إدارة معرض الأعمال في لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// معالجة حذف العمل
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $portfolio_id = intval($_GET['id']);
    
    try {
        // التحقق من وجود العمل
        $check_stmt = $db->prepare("SELECT image FROM portfolio WHERE portfolio_id = ?");
        $check_stmt->execute([$portfolio_id]);
        $portfolio_item = $check_stmt->fetch();
        
        if ($portfolio_item) {
            // حذف صورة العمل إذا كانت موجودة
            if (!empty($portfolio_item['image'])) {
                $image_path = '../uploads/portfolio/' . $portfolio_item['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // حذف العمل من قاعدة البيانات
            $delete_stmt = $db->prepare("DELETE FROM portfolio WHERE portfolio_id = ?");
            $delete_stmt->execute([$portfolio_id]);
            
            $success_message = 'تم حذف العمل بنجاح';
        } else {
            $error_message = 'العمل غير موجود';
        }
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء حذف العمل: ' . $e->getMessage();
    }
}

// الحصول على قائمة الأعمال
try {
    $portfolio_query = $db->query("
        SELECT p.*, s.name as service_name 
        FROM portfolio p 
        LEFT JOIN services s ON p.service_id = s.service_id 
        ORDER BY p.created_at DESC
    ");
    $portfolio_items = $portfolio_query->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع الأعمال: ' . $e->getMessage();
    $portfolio_items = [];
}
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إدارة معرض الأعمال</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">معرض الأعمال</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> نجاح!</h5>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> خطأ!</h5>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">قائمة الأعمال</h3>
                    
                    <div class="card-tools">
                        <a href="portfolio-add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة عمل جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>العنوان</th>
                                <th>الخدمة</th>
                                <th>التصنيف</th>
                                <th>تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($portfolio_items)): ?>
                                <?php foreach ($portfolio_items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="../uploads/portfolio/<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>" width="50">
                                            <?php else: ?>
                                                <img src="../assets/images/portfolio-placeholder.jpg" alt="صورة افتراضية" width="50">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $item['title']; ?></td>
                                        <td><?php echo $item['service_name'] ?: 'غير محدد'; ?></td>
                                        <td><?php echo $item['category'] ?? 'غير محدد'; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="portfolio-edit.php?id=<?php echo $item['portfolio_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </a>
                                                <a href="portfolio.php?action=delete&id=<?php echo $item['portfolio_id']; ?>" class="btn btn-sm btn-danger btn-delete">
                                                    <i class="fas fa-trash"></i> حذف
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد أعمال متاحة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
