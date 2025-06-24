<?php
/**
 * صفحة عرض تفاصيل الرسالة في لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// التحقق من وجود معرف الرسالة
if (!isset($_GET['id'])) {
    header('Location: messages.php');
    exit;
}

$message_id = intval($_GET['id']);

// جلب تفاصيل الرسالة
try {
    $stmt = $db->prepare("SELECT * FROM messages WHERE message_id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        header('Location: messages.php');
        exit;
    }
    
    // تحديث حالة الرسالة إلى مقروءة إذا كانت جديدة
    if ($message['status'] === 'new') {
        $update_stmt = $db->prepare("UPDATE messages SET status = 'read' WHERE message_id = ?");
        $update_stmt->execute([$message_id]);
        $message['status'] = 'read';
    }
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع تفاصيل الرسالة: ' . $e->getMessage();
}
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">تفاصيل الرسالة</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="messages.php">الرسائل</a></li>
                        <li class="breadcrumb-item active">تفاصيل الرسالة</li>
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
                    <h3 class="card-title">معلومات الرسالة</h3>
                    
                    <div class="card-tools">
                        <a href="messages.php" class="btn btn-default">
                            <i class="fas fa-arrow-right"></i> عودة للرسائل
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>المرسل:</label>
                                <p class="form-control-static"><?php echo $message['name']; ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>البريد الإلكتروني:</label>
                                <p class="form-control-static"><?php echo $message['email']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الموضوع:</label>
                                <p class="form-control-static"><?php echo $message['subject']; ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>تاريخ الإرسال:</label>
                                <p class="form-control-static"><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>نص الرسالة:</label>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br($message['message']); ?>
                        </div>
                    </div>
  
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>
