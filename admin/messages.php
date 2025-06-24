<?php
/**
 * صفحة إدارة الرسائل في لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// معالجة حذف الرسالة
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    
    try {
        // التحقق من وجود الرسالة
        $check_stmt = $db->prepare("SELECT subject FROM messages WHERE message_id = ?");
        $check_stmt->execute([$message_id]);
        $message = $check_stmt->fetch();
        
        if ($message) {
            // حذف الرسالة من قاعدة البيانات
            $delete_stmt = $db->prepare("DELETE FROM messages WHERE message_id = ?");
            $delete_stmt->execute([$message_id]);
            
            $success_message = 'تم حذف الرسالة بنجاح';
        } else {
            $error_message = 'الرسالة غير موجودة';
        }
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء حذف الرسالة: ' . $e->getMessage();
    }
}

// معالجة تحديث حالة الرسالة
if (isset($_GET['action']) && $_GET['action'] == 'mark_read' && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    
    try {
        $update_stmt = $db->prepare("UPDATE messages SET status = 'read' WHERE message_id = ?");
        $update_stmt->execute([$message_id]);
        
        $success_message = 'تم تحديث حالة الرسالة بنجاح';
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء تحديث حالة الرسالة: ' . $e->getMessage();
    }
}

// الحصول على حالة التصفية
$filter_status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// بناء استعلام الرسائل
$query = "SELECT * FROM messages WHERE 1";
$params = [];

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY created_at DESC";

// تنفيذ الاستعلام
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع الرسائل: ' . $e->getMessage();
    $messages = [];
}
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">إدارة الرسائل</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">إدارة الرسائل</li>
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
                    <h3 class="card-title">قائمة الرسائل</h3>
                    
                    <div class="card-tools">
                        <div class="btn-group">
                            <a href="messages.php" class="btn btn-sm btn-default <?php echo empty($filter_status) ? 'active' : ''; ?>">الكل</a>
                            <a href="messages.php?status=new" class="btn btn-sm btn-primary <?php echo $filter_status == 'new' ? 'active' : ''; ?>">جديد</a>
                            <a href="messages.php?status=read" class="btn btn-sm btn-info <?php echo $filter_status == 'read' ? 'active' : ''; ?>">مقروء</a>
                            <a href="messages.php?status=replied" class="btn btn-sm btn-success <?php echo $filter_status == 'replied' ? 'active' : ''; ?>">تم الرد</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المرسل</th>
                                <th>البريد الإلكتروني</th>
                                <th>الموضوع</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $index => $message): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $message['name']; ?></td>
                                        <td><?php echo $message['email']; ?></td>
                                        <td><?php echo $message['subject']; ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($message['status']) {
                                                case 'new':
                                                    $status_class = 'badge-primary';
                                                    $status_text = 'جديد';
                                                    break;
                                                case 'read':
                                                    $status_class = 'badge-info';
                                                    $status_text = 'مقروء';
                                                    break;
                                                case 'replied':
                                                    $status_class = 'badge-success';
                                                    $status_text = 'تم الرد';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="message-details.php?id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> عرض
                                                </a>
                                                <?php if ($message['status'] == 'new'): ?>
                                                    <a href="messages.php?action=mark_read&id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-check"></i> تحديد كمقروء
                                                    </a>
                                                <?php endif; ?>
                                                <a href="messages.php?action=delete&id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-danger btn-delete">
                                                    <i class="fas fa-trash"></i> حذف
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد رسائل متاحة</td>
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
