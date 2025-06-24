<?php
/**
 * صفحة إدارة الخدمات في لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// معالجة حذف الخدمة
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $service_id = intval($_GET['id']);
    
    try {
        // التحقق من وجود الخدمة
        $check_stmt = $db->prepare("SELECT image FROM services WHERE service_id = ?");
        $check_stmt->execute([$service_id]);
        $service = $check_stmt->fetch();
        
        if ($service) {
            // حذف صورة الخدمة إذا كانت موجودة
            if (!empty($service['image'])) {
                $image_path = '../uploads/services/' . $service['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // حذف الخدمة من قاعدة البيانات
            $delete_stmt = $db->prepare("DELETE FROM services WHERE service_id = ?");
            $delete_stmt->execute([$service_id]);
            
            $success_message = 'تم حذف الخدمة بنجاح';
        } else {
            $error_message = 'الخدمة غير موجودة';
        }
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء حذف الخدمة: ' . $e->getMessage();
    }
}

// جلب التصنيفات
$categories_query = $db->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);

// جلب الخدمات مع معلومات التصنيف
$services_query = $db->query("
    SELECT s.*, c.name as category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.category_id 
    ORDER BY s.name ASC
");
$services = $services_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إدارة الخدمات</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">إدارة الخدمات</li>
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
                    <h3 class="card-title">قائمة الخدمات</h3>
                    
                    <div class="card-tools">
                        <a href="service-add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة خدمة جديدة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>اسم الخدمة</th>
                                <th>التصنيف</th>
                                <th>السعر</th>
                                <th>الحالة</th>
                                <th>مميزة</th>
                                <th>تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($services)): ?>
                                <?php foreach ($services as $index => $service): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($service['image'])): ?>
                                                <img src="../uploads/services/<?php echo $service['image']; ?>" alt="<?php echo $service['name']; ?>" class="service-image">
                                            <?php else: ?>
                                                <div class="service-image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($service['name']) ?></td>
                                        <td><?= htmlspecialchars($service['category_name'] ?? 'بدون تصنيف') ?></td>
                                        <td><?= number_format($service['price_start'], 2) ?> جنيه</td>
                                        <td>
                                            <?php if ($service['status'] == 'active'): ?>
                                                <span class="badge badge-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($service['is_featured']): ?>
                                                <span class="badge badge-primary">نعم</span>
                                            <?php else: ?>
                                                <span class="badge badge-light">لا</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($service['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="service-edit.php?id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </a>
                                                <a href="services.php?action=delete&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-danger btn-delete">
                                                    <i class="fas fa-trash"></i> حذف
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد خدمات متاحة</td>
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
