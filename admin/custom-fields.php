<?php
/**
 * صفحة إدارة الحقول المخصصة في لوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// معالجة الإجراءات
$action = isset($_GET['action']) ? clean_input($_GET['action']) : 'list';
$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
$errors = [];
$success = false;

// معالجة حذف الحقل
if ($action === 'delete' && $field_id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM custom_fields WHERE field_id = ?");
        $stmt->execute([$field_id]);
        $_SESSION['success_message'] = "تم حذف الحقل بنجاح.";
        header("Location: custom-fields.php?action=list");
        exit;
    } catch (PDOException $e) {
        $errors[] = "حدث خطأ أثناء حذف الحقل: " . $e->getMessage();
    }
}

// استدعاء ملف الرأس
require_once 'includes/header.php';

// جلب أنواع الحقول
$field_types_query = $db->query("SELECT * FROM field_types WHERE status = 'active' ORDER BY type_name");
$field_types = $field_types_query->fetchAll(PDO::FETCH_ASSOC);

// معالجة إضافة/تعديل حقل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field_name = isset($_POST['field_name']) ? clean_input($_POST['field_name']) : '';
    $field_label = isset($_POST['field_label']) ? clean_input($_POST['field_label']) : '';
    $field_type_id = isset($_POST['field_type_id']) ? intval($_POST['field_type_id']) : 0;
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $default_value = isset($_POST['default_value']) ? clean_input($_POST['default_value']) : '';
    $placeholder = isset($_POST['placeholder']) ? clean_input($_POST['placeholder']) : '';
    $help_text = isset($_POST['help_text']) ? clean_input($_POST['help_text']) : '';
    $validation_rules = isset($_POST['validation_rules']) ? clean_input($_POST['validation_rules']) : '';
    $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
    $status = isset($_POST['status']) ? clean_input($_POST['status']) : 'active';

    // التحقق من البيانات
    if (empty($field_name)) {
        $errors[] = 'يرجى إدخال اسم الحقل';
    }
    if (empty($field_label)) {
        $errors[] = 'يرجى إدخال عنوان الحقل';
    }
    if ($field_type_id <= 0) {
        $errors[] = 'يرجى اختيار نوع الحقل';
    }

    // التحقق من عدم تكرار اسم الحقل
    if (empty($errors)) {
        // جلب اسم الحقل الحالي إذا كنا في وضع التعديل
        $current_field_name = '';
        $current_field_label = '';
        if ($action === 'edit' && $field_id > 0) {
            $current_query = $db->prepare("SELECT field_name, field_label FROM custom_fields WHERE field_id = ?");
            $current_query->execute([$field_id]);
            $current_field = $current_query->fetch(PDO::FETCH_ASSOC);
            if ($current_field) {
                $current_field_name = $current_field['field_name'];
                $current_field_label = $current_field['field_label'];
            }
        }
        // التحقق من تكرار اسم الحقل فقط إذا كان الاسم مختلفاً أو إذا كنا نضيف حقل جديد
        if ($action !== 'edit' || $field_name !== $current_field_name) {
            $check_query = $db->prepare("SELECT field_id, field_label, field_name FROM custom_fields WHERE field_name = ? AND field_id != ?");
            $check_query->execute([$field_name, $field_id]);
            if ($check_query->rowCount() > 0) {
                $existing_field = $check_query->fetch(PDO::FETCH_ASSOC);
                $errors[] = "خطأ في قاعدة البيانات: اسم الحقل '$field_name' موجود بالفعل.";
                $errors[] = "تفاصيل الحقل الموجود:";
                $errors[] = "  - رقم الحقل (ID): " . $existing_field['field_id'];
                $errors[] = "  - اسم الحقل: " . htmlspecialchars($existing_field['field_name']);
                $errors[] = "  - عنوان الحقل: " . htmlspecialchars($existing_field['field_label']);
                if ($action === 'edit' && $field_id > 0) {
                    $errors[] = "الحل: قم بتغيير اسم الحقل أو اتركه كما هو إذا كنت لا تريد تعديله.";
                } else {
                    $errors[] = "الحل: اختر اسماً فريداً للحقل الجديد.";
                }
                $errors[] = "سبب الخطأ: قاعدة البيانات تمنع تكرار أسماء الحقول لضمان التفرد.";
            }
        }
        // التحقق من تكرار عنوان الحقل فقط إذا كان العنوان مختلفاً أو إذا كنا نضيف حقل جديد
        if ($action !== 'edit' || $field_label !== $current_field_label) {
            $check_label_query = $db->prepare("SELECT field_id, field_label, field_name FROM custom_fields WHERE field_label = ? AND field_id != ?");
            $check_label_query->execute([$field_label, $field_id]);
            if ($check_label_query->rowCount() > 0) {
                $existing_field = $check_label_query->fetch(PDO::FETCH_ASSOC);
                $errors[] = "خطأ في قاعدة البيانات: عنوان الحقل '$field_label' موجود بالفعل.";
                $errors[] = "تفاصيل الحقل الموجود:";
                $errors[] = "  - رقم الحقل (ID): " . $existing_field['field_id'];
                $errors[] = "  - اسم الحقل: " . htmlspecialchars($existing_field['field_name']);
                $errors[] = "  - عنوان الحقل: " . htmlspecialchars($existing_field['field_label']);
                if ($action === 'edit' && $field_id > 0) {
                    $errors[] = "الحل: قم بتغيير عنوان الحقل أو اتركه كما هو إذا كنت لا تريد تعديله.";
                } else {
                    $errors[] = "الحل: اختر عنواناً فريداً للحقل الجديد.";
                }
                $errors[] = "سبب الخطأ: قاعدة البيانات تمنع تكرار عناوين الحقول لضمان وضوح النموذج للمستخدم.";
            }
        }
    }

    // معالجة الخيارات للحقول من نوع select
    $field_options = [];
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        foreach ($_POST['options'] as $option) {
            if (!empty($option['value']) && !empty($option['label'])) {
                $field_options[] = [
                    'value' => clean_input($option['value']),
                    'label' => clean_input($option['label']),
                    'is_default' => isset($option['is_default']) ? 1 : 0,
                    'order_num' => intval($option['order_num'])
                ];
            }
        }
    }

    // إذا لم يكن هناك أخطاء، نقوم بحفظ الحقل
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            if ($action === 'edit' && $field_id > 0) {
                // تحديث الحقل الموجود
                $stmt = $db->prepare("
                    UPDATE custom_fields SET 
                        field_name = ?, field_label = ?, field_type_id = ?, 
                        is_required = ?, default_value = ?, placeholder = ?, 
                        help_text = ?, validation_rules = ?, order_num = ?, status = ?
                    WHERE field_id = ?
                ");
                $stmt->execute([
                    $field_name, $field_label, $field_type_id, $is_required,
                    $default_value, $placeholder, $help_text, $validation_rules,
                    $order_num, $status, $field_id
                ]);

                // حذف الخيارات القديمة
                $db->prepare("DELETE FROM field_options WHERE field_id = ?")->execute([$field_id]);

                $success = true;
                if ($action === 'edit' && $field_name === $current_field_name) {
                    $_SESSION['success_message'] = "تم تحديث الحقل '$field_label' بنجاح. (اسم الحقل لم يتغير)";
                } else {
                    $_SESSION['success_message'] = "تم تحديث الحقل '$field_label' بنجاح.";
                }
            } else {
                // إضافة حقل جديد
                $stmt = $db->prepare("
                    INSERT INTO custom_fields (
                        field_name, field_label, field_type_id, is_required,
                        default_value, placeholder, help_text, validation_rules, order_num, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $field_name, $field_label, $field_type_id, $is_required,
                    $default_value, $placeholder, $help_text, $validation_rules,
                    $order_num, $status
                ]);

                $field_id = $db->lastInsertId();
                $success = true;
                $_SESSION['success_message'] = "تم إضافة الحقل '$field_label' بنجاح.";
            }

            // إضافة الخيارات إذا كان الحقل من نوع select
            if (!empty($field_options)) {
                $option_stmt = $db->prepare("
                    INSERT INTO field_options (field_id, option_value, option_label, is_default, order_num)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($field_options as $option) {
                    $option_stmt->execute([
                        $field_id, $option['value'], $option['label'], 
                        $option['is_default'], $option['order_num']
                    ]);
                }
            }

            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = 'حدث خطأ أثناء حفظ الحقل: ' . $e->getMessage();
            error_log('Custom field save error: ' . $e->getMessage());
        }
    }
}

// جلب بيانات الحقل للتعديل
$field_data = null;
$field_options_data = [];
if ($action === 'edit' && $field_id > 0) {
    $field_query = $db->prepare("SELECT * FROM custom_fields WHERE field_id = ?");
    $field_query->execute([$field_id]);
    $field_data = $field_query->fetch(PDO::FETCH_ASSOC);

    if ($field_data) {
        $options_query = $db->prepare("SELECT * FROM field_options WHERE field_id = ? ORDER BY order_num");
        $options_query->execute([$field_id]);
        $field_options_data = $options_query->fetchAll(PDO::FETCH_ASSOC);
    }
}

// جلب قائمة الحقول
$fields_query = $db->query("
    SELECT cf.*, ft.type_name, ft.type_key, ft.has_options,
           (SELECT COUNT(*) FROM field_options WHERE field_id = cf.field_id) as options_count
    FROM custom_fields cf
    LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
    ORDER BY cf.order_num, cf.field_name
");
$fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <?= $action === 'edit' ? 'تعديل حقل مخصص' : ($action === 'add' ? 'إضافة حقل مخصص' : 'إدارة الحقول المخصصة') ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">الحقول المخصصة</li>
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
                    <?= $_SESSION['success_message'] ?? 'تم حفظ الحقل بنجاح.' ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> خطأ في قاعدة البيانات:</h5>
                    <div class="mt-3">
                        <?php foreach ($errors as $index => $error): ?>
                            <?php 
                            $is_db_error = strpos($error, 'خطأ في قاعدة البيانات') !== false;
                            $is_details = strpos($error, 'تفاصيل الحقل الموجود') !== false;
                            $is_detail_item = strpos($error, '  - ') !== false;
                            $is_solution = strpos($error, 'الحل:') !== false;
                            $is_reason = strpos($error, 'سبب الخطأ:') !== false;
                            
                            if ($is_db_error): ?>
                                <div class="alert alert-danger border-left-danger border-left-4 mb-2">
                                    <h6 class="alert-heading"><i class="fas fa-database"></i> <?= htmlspecialchars($error) ?></h6>
                                </div>
                            <?php elseif ($is_details): ?>
                                <div class="alert alert-info border-left-info border-left-4 mb-2">
                                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($error) ?></h6>
                                </div>
                            <?php elseif ($is_detail_item): ?>
                                <div class="alert alert-light border-left-light border-left-4 mb-1 ml-4">
                                    <p class="mb-1"><i class="fas fa-arrow-right"></i> <?= htmlspecialchars($error) ?></p>
                                </div>
                            <?php elseif ($is_solution): ?>
                                <div class="alert alert-success border-left-success border-left-4 mb-2">
                                    <p class="mb-1"><i class="fas fa-lightbulb"></i> <?= htmlspecialchars($error) ?></p>
                                </div>
                            <?php elseif ($is_reason): ?>
                                <div class="alert alert-warning border-left-warning border-left-4 mb-2">
                                    <p class="mb-1"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger border-left-danger border-left-4 mb-2">
                                    <p class="mb-1"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- قائمة الحقول -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">الحقول المخصصة</h3>
                        <div class="card-tools">
                            <a href="?action=add" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> إضافة حقل جديد
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اسم الحقل</th>
                                        <th>العنوان</th>
                                        <th>النوع</th>
                                        <th>مطلوب</th>
                                        <th>الترتيب</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fields as $field): ?>
                                        <tr>
                                            <td><?= $field['field_id'] ?></td>
                                            <td><?= htmlspecialchars($field['field_name']) ?></td>
                                            <td><?= htmlspecialchars($field['field_label']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($field['type_name']) ?>
                                                <?php if ($field['has_options']): ?>
                                                    <span class="badge badge-info"><?= $field['options_count'] ?> خيارات</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($field['is_required']): ?>
                                                    <span class="badge badge-success">نعم</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">لا</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $field['order_num'] ?></td>
                                            <td>
                                                <?php if ($field['status'] === 'active'): ?>
                                                    <span class="badge badge-success">نشط</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">غير نشط</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?action=edit&field_id=<?= $field['field_id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&field_id=<?= $field['field_id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا الحقل؟')">
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
            <?php else: ?>
                <!-- نموذج إضافة/تعديل الحقل -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?= $action === 'edit' ? 'تعديل حقل مخصص' : 'إضافة حقل مخصص' ?>
                        </h3>
                        <div class="card-tools">
                            <a href="?action=list" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> العودة للقائمة
                            </a>
                        </div>
                    </div>
                    <form method="post" id="fieldForm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="field_name">اسم الحقل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="field_name" name="field_name" 
                                               value="<?= htmlspecialchars($field_data['field_name'] ?? '') ?>" required>
                                        <small class="text-muted">اسم فريد للحقل (بدون مسافات أو رموز خاصة)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="field_label">عنوان الحقل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="field_label" name="field_label" 
                                               value="<?= htmlspecialchars($field_data['field_label'] ?? '') ?>" required>
                                        <small class="text-muted">العنوان الذي سيظهر للمستخدم</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="field_type_id">نوع الحقل <span class="text-danger">*</span></label>
                                        <select class="form-control" id="field_type_id" name="field_type_id" required>
                                            <option value="">-- اختر نوع الحقل --</option>
                                            <?php foreach ($field_types as $type): ?>
                                                <option value="<?= $type['type_id'] ?>" 
                                                        <?= (isset($field_data['field_type_id']) && $field_data['field_type_id'] == $type['type_id']) ? 'selected' : '' ?>
                                                        data-has-options="<?= $type['has_options'] ?>">
                                                    <?= htmlspecialchars($type['type_name']) ?> - <?= htmlspecialchars($type['description']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="order_num">ترتيب الحقل</label>
                                        <input type="number" class="form-control" id="order_num" name="order_num" 
                                               value="<?= htmlspecialchars($field_data['order_num'] ?? '0') ?>" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="default_value">القيمة الافتراضية</label>
                                        <input type="text" class="form-control" id="default_value" name="default_value" 
                                               value="<?= htmlspecialchars($field_data['default_value'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="placeholder">نص توضيحي</label>
                                        <input type="text" class="form-control" id="placeholder" name="placeholder" 
                                               value="<?= htmlspecialchars($field_data['placeholder'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="help_text">نص المساعدة</label>
                                <textarea class="form-control" id="help_text" name="help_text" rows="2"><?= htmlspecialchars($field_data['help_text'] ?? '') ?></textarea>
                                <small class="text-muted">نص توضيحي يظهر تحت الحقل</small>
                            </div>

                            <div class="form-group">
                                <label for="validation_rules">قواعد التحقق</label>
                                <input type="text" class="form-control" id="validation_rules" name="validation_rules" 
                                       value="<?= htmlspecialchars($field_data['validation_rules'] ?? '') ?>">
                                <small class="text-muted">قواعد التحقق من صحة البيانات (اختياري)</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="is_required" name="is_required" 
                                                   <?= (isset($field_data['is_required']) && $field_data['is_required']) ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="is_required">حقل مطلوب</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">حالة الحقل</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?= (!isset($field_data['status']) || $field_data['status'] == 'active') ? 'selected' : '' ?>>نشط</option>
                                            <option value="inactive" <?= (isset($field_data['status']) && $field_data['status'] == 'inactive') ? 'selected' : '' ?>>غير نشط</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- قسم الخيارات للحقول من نوع select -->
                            <div id="optionsSection" style="display: none;">
                                <div class="card card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">خيارات الحقل</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="addOption()">
                                                <i class="fas fa-plus"></i> إضافة خيار
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="optionsContainer">
                                            <?php if (!empty($field_options_data)): ?>
                                                <?php foreach ($field_options_data as $index => $option): ?>
                                                    <div class="option-row row mb-2">
                                                        <div class="col-md-3">
                                                            <input type="text" class="form-control" name="options[<?= $index ?>][value]" 
                                                                   placeholder="القيمة" value="<?= htmlspecialchars($option['option_value']) ?>" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="options[<?= $index ?>][label]" 
                                                                   placeholder="العنوان" value="<?= htmlspecialchars($option['option_label']) ?>" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" 
                                                                       name="options[<?= $index ?>][is_default]" id="default_<?= $index ?>"
                                                                       <?= $option['is_default'] ? 'checked' : '' ?>>
                                                                <label class="custom-control-label" for="default_<?= $index ?>">افتراضي</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="number" class="form-control" name="options[<?= $index ?>][order_num]" 
                                                                   placeholder="الترتيب" value="<?= $option['order_num'] ?>" min="0">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ الحقل
                            </button>
                            <a href="?action=list" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let optionIndex = <?= isset($field_options_data) ? count($field_options_data) : 0 ?>;

// إظهار/إخفاء قسم الخيارات حسب نوع الحقل
function toggleOptionsSection() {
    const select = document.getElementById('field_type_id');
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    const hasOptions = selectedOption && selectedOption.getAttribute('data-has-options') === '1';
    const optionsSection = document.getElementById('optionsSection');
    if (hasOptions) {
        optionsSection.style.display = 'block';
    } else {
        optionsSection.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // عند تحميل الصفحة، تحقق من نوع الحقل الحالي وأظهر الخيارات إذا كان من نوع قائمة
    toggleOptionsSection();
    // عند تغيير نوع الحقل
    const select = document.getElementById('field_type_id');
    if (select) {
        select.addEventListener('change', toggleOptionsSection);
    }
});

// إضافة خيار جديد
function addOption() {
    const container = document.getElementById('optionsContainer');
    const optionRow = document.createElement('div');
    optionRow.className = 'option-row row mb-2';
    optionRow.innerHTML = `
        <div class="col-md-3">
            <input type="text" class="form-control" name="options[${optionIndex}][value]" placeholder="القيمة" required>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="options[${optionIndex}][label]" placeholder="العنوان" required>
        </div>
        <div class="col-md-2">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" name="options[${optionIndex}][is_default]" id="default_${optionIndex}">
                <label class="custom-control-label" for="default_${optionIndex}">افتراضي</label>
            </div>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="options[${optionIndex}][order_num]" placeholder="الترتيب" value="${optionIndex}" min="0">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(optionRow);
    optionIndex++;
}

// حذف خيار
function removeOption(button) {
    button.closest('.option-row').remove();
}

// التحقق من صحة النموذج
document.getElementById('fieldForm').addEventListener('submit', function(e) {
    const fieldName = document.getElementById('field_name').value;
    const fieldType = document.getElementById('field_type_id').value;
    
    if (!fieldName || !fieldType) {
        e.preventDefault();
        alert('يرجى ملء جميع الحقول المطلوبة');
        return;
    }
    
    // التحقق من صحة اسم الحقل
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(fieldName)) {
        e.preventDefault();
        alert('اسم الحقل يجب أن يبدأ بحرف أو _ ويحتوي على أحرف وأرقام و _ فقط');
        return;
    }
});
</script> 