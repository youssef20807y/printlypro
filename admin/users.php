<?php
/**
 * صفحة إدارة المستخدمين في لوحة تحكم المسؤول
 */
define('PRINTLY', true);
require_once 'auth.php';
require_once 'includes/header.php';

// حذف المستخدم
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    if ($user_id == $_SESSION['admin_id']) {
        $error_message = 'لا يمكنك حذف حسابك الحالي';
    } else {
        try {
            $check_stmt = $db->prepare("SELECT username FROM users WHERE user_id = ?");
            $check_stmt->execute([$user_id]);
            $user = $check_stmt->fetch();
            if ($user) {
                $delete_stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $delete_stmt->execute([$user_id]);
                $success_message = 'تم حذف المستخدم بنجاح';
            } else {
                $error_message = 'المستخدم غير موجود';
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ أثناء حذف المستخدم: ' . $e->getMessage();
        }
    }
}

// تغيير حالة المستخدم
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    if ($user_id == $_SESSION['admin_id']) {
        $error_message = 'لا يمكنك تغيير حالتك الخاصة';
    } else {
        try {
            $stmt = $db->prepare("SELECT status FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            if ($user) {
                $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
                $update = $db->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $update->execute([$new_status, $user_id]);
                $success_message = "تم تحديث حالة المستخدم إلى: " . (($new_status == 'active') ? 'نشط' : 'غير نشط');
            } else {
                $error_message = 'المستخدم غير موجود';
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage();
        }
    }
}

// جلب المستخدمين
try {
    $users_query = $db->query("SELECT * FROM users ORDER BY registration_date DESC");
    $users = $users_query->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع المستخدمين: ' . $e->getMessage();
    $users = [];
}
?>


<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إدارة المستخدمين</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">إدارة المستخدمين</li>
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
                    <h3 class="card-title">قائمة المستخدمين</h3>
                    
                    <div class="card-tools">
                        <a href="user-add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة مستخدم جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>آخر تسجيل دخول</th>
                                <th>تاريخ التسجيل</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $index => $user): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <?php if ($user['role'] == 'admin'): ?>
                                                <span class="badge badge-primary">مسؤول</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">عميل</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] == 'active'): ?>
                                                <span class="badge badge-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'لم يسجل الدخول بعد'; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($user['registration_date'])); ?></td>
                                        <td>
    <div class="btn-group">
        <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info">
            <i class="fas fa-edit"></i> تعديل
        </a>
        <?php if ($user['user_id'] != $_SESSION['admin_id']): ?>
            <a href="users.php?action=toggle_status&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning">
                <i class="fas fa-sync-alt"></i> تغيير الحالة
            </a>
            <a href="users.php?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger btn-delete" onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا المستخدم؟');">
                <i class="fas fa-trash"></i> حذف
            </a>
        <?php endif; ?>
    </div>
</td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا يوجد مستخدمين متاحين</td>
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
