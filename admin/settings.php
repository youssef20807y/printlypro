<?php
/**
 * صفحة الإعدادات للوحة تحكم مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'includes/db.php';

// التحقق من تسجيل دخول المدير
require_once 'auth.php';

// استدعاء ملفات المساعدة
require_once 'includes/functions/service_helpers.php';

// معالجة تحديث الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        // تحديث الإعدادات العامة
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        // معالجة تحميل الشعار إذا تم تحديده
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_file($_FILES['site_logo'], '../assets/images/');
            if ($upload_result['success']) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'");
                $stmt->execute([$upload_result['file_name']]);
            }
        }
        
        // معالجة تحميل الأيقونة إذا تم تحديدها
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_file($_FILES['site_favicon'], '../assets/images/');
            if ($upload_result['success']) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_favicon'");
                $stmt->execute([$upload_result['file_name']]);
            }
        }
        
        $success_message = 'تم تحديث الإعدادات بنجاح';
    } catch (PDOException $e) {
        $error_message = 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage();
    }
}

// الحصول على الإعدادات الحالية
try {
    $stmt = $db->query("SELECT * FROM settings ORDER BY setting_group, setting_id");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row;
    }
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع الإعدادات: ' . $e->getMessage();
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
                    <h1 class="m-0">إعدادات الموقع</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-left">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">الإعدادات</li>
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
                    <h3 class="card-title">إعدادات الموقع</h3>
                </div>
                <div class="card-body">
                    <form action="settings.php" method="post" enctype="multipart/form-data">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs" id="settings-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general-tab" data-toggle="pill" href="#general" role="tab" aria-controls="general" aria-selected="true">إعدادات عامة</a>
                                </li>

                            </ul>
                            
                            <div class="tab-content mt-3" id="settings-content">
                                <!-- الإعدادات العامة -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                    <div class="form-group">
                                        <label for="site_name">اسم الموقع</label>
                                        <input type="text" class="form-control" id="site_name" name="settings[site_name]" value="<?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']['setting_value']) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="site_description">وصف الموقع</label>
                                        <textarea class="form-control" id="site_description" name="settings[site_description]" rows="3"><?php echo isset($settings['site_description']) ? htmlspecialchars($settings['site_description']['setting_value']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="site_logo">شعار الموقع</label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="site_logo" name="site_logo">
                                                <label class="custom-file-label" for="site_logo">اختر ملفًا</label>
                                            </div>
                                        </div>
                                        <?php if (isset($settings['site_logo']) && !empty($settings['site_logo']['setting_value'])): ?>
                                            <div class="mt-2">
                                                <img src="../assets/images/<?php echo $settings['site_logo']['setting_value']; ?>" alt="الشعار الحالي" style="max-height: 50px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="site_favicon">أيقونة الموقع</label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="site_favicon" name="site_favicon">
                                                <label class="custom-file-label" for="site_favicon">اختر ملفًا</label>
                                            </div>
                                        </div>
                                        <?php if (isset($settings['site_favicon']) && !empty($settings['site_favicon']['setting_value'])): ?>
                                            <div class="mt-2">
                                                <img src="../assets/images/<?php echo $settings['site_favicon']['setting_value']; ?>" alt="الأيقونة الحالية" style="max-height: 32px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                

                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" name="update_settings" class="btn btn-primary">حفظ الإعدادات</button>
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
    // تهيئة مربعات اختيار الملفات
    bsCustomFileInput.init();
});
</script>

