<?php
define('PRINTLY', true);
require_once 'auth.php';
require_once '../includes/points_functions.php';

// التحقق من وجود order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die('رقم الطلب غير صالح.');
}

$order_id = intval($_GET['order_id']);

// عند تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    $allowed_statuses = ['new', 'processing', 'ready', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_stmt = $db->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update_stmt->execute([$new_status, $order_id]);
        header("Location: order-details.php?order_id=" . $order_id);
        exit;
    } else {
        $status_error = "حالة غير صالحة.";
    }
}

// جلب بيانات الطلب
$stmt = $db->prepare("
    SELECT o.*, u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('لم يتم العثور على الطلب.');
}

// جلب معاملات النقاط غير المؤكدة لهذا الطلب
$pending_points_stmt = $db->prepare("SELECT * FROM points_transactions WHERE order_id = ? AND status = 'pending'");
$pending_points_stmt->execute([$order_id]);
$pending_points_transactions = $pending_points_stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة قبول أو رفض النقاط
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_points'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $action = $_POST['action_points']; // 'approve' or 'reject'
    $points_amount = intval($_POST['points_amount']);
    $user_id = intval($_POST['user_id']);
    $order_number = htmlspecialchars($order['order_number']);

    try {
        $db->beginTransaction();

        $update_transaction_stmt = $db->prepare("UPDATE points_transactions SET status = ?, admin_notes = ? WHERE transaction_id = ? AND status = 'pending'");
        
        if ($action === 'approve') {
            // نقل النقاط من unverified_points_balance إلى points_balance
            $update_user_points_stmt = $db->prepare("UPDATE users SET points_balance = points_balance + ?, unverified_points_balance = unverified_points_balance - ? WHERE user_id = ?");
            $update_user_points_stmt->execute([$points_amount, $points_amount, $user_id]);
            $update_transaction_stmt->execute(['approved', 'تمت الموافقة على النقاط', $transaction_id]);
            send_user_message($user_id, "تمت الموافقة على نقاطك من الطلب #" . $order_number . ". تم إضافة " . format_points($points_amount) . " إلى رصيدك.", "points_update");
            $_SESSION['success_message'] = 'تمت الموافقة على النقاط بنجاح.';
        } elseif ($action === 'reject') {
            // خصم النقاط من unverified_points_balance فقط
            $update_user_points_stmt = $db->prepare("UPDATE users SET unverified_points_balance = unverified_points_balance - ? WHERE user_id = ?");
            $update_user_points_stmt->execute([$points_amount, $user_id]);
            $update_transaction_stmt->execute(['rejected', 'تم رفض النقاط', $transaction_id]);
            send_user_message($user_id, "تم رفض نقاطك من الطلب #" . $order_number . ". لم يتم إضافة " . format_points($points_amount) . " إلى رصيدك.", "points_update");
            $_SESSION['error_message'] = 'تم رفض النقاط بنجاح.';
        }

        $db->commit();
        header("Location: order-details.php?order_id=" . $order_id);
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error_message'] = 'حدث خطأ أثناء معالجة النقاط: ' . $e->getMessage();
        error_log("Error processing points: " . $e->getMessage());
    }
}

// جلب العناصر المضمنة في الطلب
$stmt_items = $db->prepare("
    SELECT 
        oi.item_id,
        oi.order_id,
        oi.service_id,
        oi.quantity,
        oi.paper_type,
        oi.size,
        oi.colors,
        oi.notes,
        oi.price,
        s.name AS service_name,
        GROUP_CONCAT(DISTINCT oif.file_name) as files
    FROM order_items oi
    INNER JOIN services s ON oi.service_id = s.service_id
    LEFT JOIN order_item_files oif ON oi.item_id = oif.item_id
    WHERE oi.order_id = ?
    GROUP BY 
        oi.item_id,
        oi.order_id,
        oi.service_id,
        oi.quantity,
        oi.paper_type,
        oi.size,
        oi.colors,
        oi.notes,
        oi.price,
        s.name
    ORDER BY oi.item_id ASC
");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// جلب القيم المخصصة لكل عنصر طلب
$order_item_custom_values = [];
if (!empty($order_items)) {
    $item_ids = array_column($order_items, 'item_id');
    $in = str_repeat('?,', count($item_ids) - 1) . '?';
    $stmt = $db->prepare("SELECT * FROM order_field_values WHERE order_item_id IN ($in)");
    $stmt->execute($item_ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $order_item_custom_values[$row['order_item_id']][$row['field_id']] = $row['field_value'];
    }
}

// جلب الحقول المخصصة لكل خدمة في الطلب
$service_custom_fields = [];
$service_ids = array_unique(array_column($order_items, 'service_id'));
if (!empty($service_ids)) {
    $in = str_repeat('?,', count($service_ids) - 1) . '?';
    $stmt = $db->prepare("
        SELECT sf.service_id, cf.field_id, cf.field_label, cf.field_type_id, ft.type_key, ft.has_options, sf.is_required
        FROM service_fields sf
        JOIN custom_fields cf ON sf.field_id = cf.field_id
        LEFT JOIN field_types ft ON cf.field_type_id = ft.type_id
        WHERE sf.service_id IN ($in) AND sf.status = 'active' AND cf.status = 'active'
        ORDER BY sf.service_id, sf.order_num, cf.order_num
    ");
    $stmt->execute($service_ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $service_custom_fields[$row['service_id']][] = $row;
    }
}

// فلتر الحقول المخصصة لتجنب التكرار مع الحقول الأساسية
$basic_field_labels = ['نوع الورق', 'المقاس', 'عدد الألوان', 'الملاحظات', 'ملف التصميم'];
foreach ($service_custom_fields as $service_id => &$fields) {
    $filtered_fields = [];
    foreach ($fields as $field) {
        if (!in_array($field['field_label'], $basic_field_labels)) {
            $filtered_fields[] = $field;
        }
    }
    $service_custom_fields[$service_id] = $filtered_fields;
}

// الآن نقوم بتضمين header.php بعد معالجة جميع العمليات التي تحتاج إلى header redirects
require_once 'includes/header.php';

// إضافة كود للتحقق من عدد العناصر
if (empty($order_items)) {
    echo '<div class="alert alert-warning">لا توجد عناصر في هذا الطلب</div>';
} else {
    echo '<div class="alert alert-info">عدد العناصر: ' . count($order_items) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الطلب - إدارة برنتلي</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            direction: rtl; 
            background: #f0f2f5; 
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        h1 { 
            color: #2c3e50; 
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        h2 { 
            color: #34495e;
            font-size: 1.3em;
            margin-top: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        ul { 
            list-style: none; 
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        li { 
            margin-bottom: 12px;
            padding: 8px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        li strong {
            color: #2c3e50;
            display: inline-block;
            margin-left: 8px;
        }
        table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        th, td { 
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th { 
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }
        .btn-view {
            background: #3498db;
            color: white !important;
            margin: 2px;
        }
        .btn-view:hover {
            background: #2980b9;
        }
        .btn-view:hover {
            background: #2980b9;
        }
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 20px;
        }
        select, button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9em;
        }
        button {
            background: #2ecc71;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #27ae60;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #3498db;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
        }
        .back-link:hover {
            color: #2980b9;
        }
        .file-links {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .download-all-btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        .download-all-btn:hover {
            background: #219a52;
        }
        .payment-proof {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .payment-image {
            margin-bottom: 10px;
            text-align: center;
        }
        .pdf-preview, .file-preview {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .pdf-preview:hover, .file-preview:hover {
            transform: translateY(-2px);
        }
        .pdf-link, .file-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #2c3e50;
        }
        .pdf-link span, .file-link span {
            font-size: 14px;
            font-weight: 500;
        }
        .payment-date {
            text-align: right;
            margin-top: 15px;
            color: #666;
        }
        .no-payment-proof {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #e74c3c;
            font-weight: 500;
        }
        .no-payment-proof i {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-file-invoice"></i> تفاصيل الطلب #<?= htmlspecialchars($order['order_number']) ?></h1>

        <section class="info-section">
            <h2>معلومات عامة</h2>
            <ul>
                <li><strong>العميل:</strong> <?= htmlspecialchars($order['username'] ?? 'زائر') ?></li>
                <li><strong>الحالة الحالية:</strong> <?= htmlspecialchars($order['status']) ?></li>
                <li><strong>حالة الدفع:</strong> <?= htmlspecialchars($order['payment_status']) ?></li>
                <li><strong>طريقة الدفع:</strong> <?= htmlspecialchars($order['payment_method']) ?></li>
                <li><strong>إجمالي المبلغ:</strong> <?= number_format($order['total_amount'], 2) ?> جنيه</li>
                <li><strong>تاريخ الطلب:</strong> <?= $order['created_at'] ?></li>
                <li><strong>طريقة الاستلام:</strong> <?= $order['delivery_type'] === 'pickup' ? 'استلام من المطبعة' : 'توصيل للمنزل' ?></li>
            </ul>

            <?php if (!empty($order['payment_proof'])): ?>
            <div class="payment-proof">
                <h3>إثبات الدفع</h3>
                <div class="payment-image">
                    <?php
                    // استخراج اسم الملف من payment_proof
                    $original_filename = basename($order['payment_proof']);
                    // إذا كان الملف يبدأ بـ payment_، نقوم بإزالته
                    $filename = preg_replace('/^payment_\d+_/', '', $original_filename);
                    // إنشاء اسم الملف النهائي
                    $payment_file = 'designspayment_' . $order_id . '_' . $filename;
                    $file_extension = strtolower(pathinfo($payment_file, PATHINFO_EXTENSION));
                    $file_path = "../uploads/" . $payment_file;
                    
                    if (file_exists($file_path)) {
                        // التحقق من نوع الملف وعرضه بشكل مناسب
                        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                        
                        // تحديد أيقونة ونوع العرض حسب امتداد الملف
                        switch ($file_extension) {
                            case 'pdf':
                                echo '<div class="pdf-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="pdf-link">
                                            <i class="fas fa-file-pdf" style="font-size: 48px; color: #e74c3c;"></i>
                                            <span>عرض ملف PDF</span>
                                        </a>
                                      </div>';
                                break;
                            
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            case 'gif':
                            case 'bmp':
                            case 'tiff':
                            case 'tga':
                                echo '<a href="' . htmlspecialchars($file_path) . '" target="_blank">
                                        <img src="' . htmlspecialchars($file_path) . '" alt="إثبات الدفع" style="max-width: 300px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                      </a>';
                                break;
                            
                            case 'ai':
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file-vector" style="font-size: 48px; color: #e67e22;"></i>
                                            <span>ملف Illustrator</span>
                                        </a>
                                      </div>';
                                break;
                            
                            case 'psd':
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file-image" style="font-size: 48px; color: #3498db;"></i>
                                            <span>ملف Photoshop</span>
                                        </a>
                                      </div>';
                                break;
                            
                            case 'eps':
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file-vector" style="font-size: 48px; color: #9b59b6;"></i>
                                            <span>ملف EPS</span>
                                        </a>
                                      </div>';
                                break;
                            
                            case 'cdr':
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file-vector" style="font-size: 48px; color: #e74c3c;"></i>
                                            <span>ملف CorelDRAW</span>
                                        </a>
                                      </div>';
                                break;
                            
                            case 'svg':
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file-code" style="font-size: 48px; color: #2ecc71;"></i>
                                            <span>ملف SVG</span>
                                        </a>
                                      </div>';
                                break;
                            
                            case 'indd':
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file-alt" style="font-size: 48px; color: #f1c40f;"></i>
                                            <span>ملف InDesign</span>
                                        </a>
                                      </div>';
                                break;
                            
                            default:
                                echo '<div class="file-preview">
                                        <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="file-link">
                                            <i class="fas fa-file" style="font-size: 48px; color: #3498db;"></i>
                                            <span>تحميل الملف</span>
                                        </a>
                                      </div>';
                        }
                    } else {
                        echo '<div style="color: #e74c3c; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-left: 10px;"></i>
                                <span style="font-weight: 500;">لا يوجد إثبات دفع مرفق</span>
                              </div>';
                    }
                    ?>
                </div>
                <p class="payment-date">
                    <strong>تاريخ الدفع:</strong> 
                    <?= $order['payment_date'] ? date('Y-m-d H:i:s', strtotime($order['payment_date'])) : 'غير محدد' ?>
                </p>
            </div>
            <?php else: ?>
            <div class="payment-proof">
                <h3>إثبات الدفع</h3>
                <div style="color: #e74c3c; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-left: 10px;"></i>
                    <span style="font-weight: 500;">لا يوجد إثبات دفع مرفق</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- نموذج تحديث الحالة -->
            <form method="post" class="status-form">
                <label for="status"><strong>تحديث الحالة:</strong></label>
                <select name="status" id="status">
                    <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>جديد</option>
                    <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>قيد المعالجة</option>
                    <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>جاهز</option>
                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>تم التسليم</option>
                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                </select>
                <button type="submit">تحديث</button>
            </form>

            <?php if (isset($status_error)): ?>
                <p class="error"><?= $status_error ?></p>
            <?php endif; ?>
        </section>

        <?php if ($order['delivery_type'] === 'pickup'): ?>
        <section class="info-section">
            <h2>معلومات الاستلام من المطبعة</h2>
            <ul>
                <li><strong>الاسم:</strong> <?= htmlspecialchars($order['pickup_name']) ?></li>
                <li><strong>الهاتف:</strong> <?= htmlspecialchars($order['pickup_phone']) ?></li>
                <li><strong>عنوان المطبعة:</strong> دمياط، شارع وزير، بجوار مسجد تقي الدين</li>
                <li><strong>هاتف المطبعة:</strong> 201002889688+</li>
                <li><strong>مواعيد العمل:</strong> من السبت للخميس</li>
            </ul>
        </section>
        <?php else: ?>
        <section class="info-section">
            <h2>معلومات التوصيل</h2>
            <ul>
                <li><strong>الاسم:</strong> <?= htmlspecialchars($order['shipping_name']) ?></li>
                <li><strong>الهاتف:</strong> <?= htmlspecialchars($order['shipping_phone']) ?></li>
                <li><strong>البريد:</strong> <?= htmlspecialchars($order['shipping_email']) ?></li>
                <li><strong>المدينة:</strong> <?= htmlspecialchars($order['shipping_city']) ?></li>
                <li><strong>العنوان:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></li>
            </ul>
        </section>
        <?php endif; ?>

        <section>
            <h2><i class="fas fa-box"></i> العناصر المضمنة</h2>
            <?php
            // التحقق من وجود ملفات للتحميل
            $has_files = false;
            foreach ($order_items as $item) {
                if (!empty($item['files'])) {
                    $has_files = true;
                    break;
                }
            }
            // جمع كل الحقول المخصصة لجميع الخدمات في الطلب (لضمان ترتيب الأعمدة)
            $all_custom_fields = [];
            foreach ($service_custom_fields as $fields) {
                foreach ($fields as $field) {
                    $all_custom_fields[$field['field_id']] = $field['field_label'];
                }
            }

            // --- كود جديد: تحديد الأعمدة الأساسية التي يجب إظهارها ---
            $show_paper_type = false;
            $show_size = false;
            $show_colors = false;
            foreach ($order_items as $item) {
                if (!$show_paper_type && !empty($item['paper_type'])) $show_paper_type = true;
                if (!$show_size && !empty($item['size'])) $show_size = true;
                if (!$show_colors && !empty($item['colors'])) $show_colors = true;
            }
            // --- نهاية الكود الجديد ---
            ?>
            <?php if ($has_files): ?>
                <a href="download-files.php?order_id=<?= $order_id ?>" class="download-all-btn">
                    <i class="fas fa-download"></i> تحميل جميع الملفات
                </a>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>الخدمة</th>
                        <th>الكمية</th>
                        <?php if ($show_paper_type): ?><th>نوع الورق</th><?php endif; ?>
                        <?php if ($show_size): ?><th>المقاس</th><?php endif; ?>
                        <?php if ($show_colors): ?><th>الألوان</th><?php endif; ?>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                        <?php foreach ($all_custom_fields as $field_label): ?>
                            <th><?= htmlspecialchars($field_label) ?></th>
                        <?php endforeach; ?>
                        <th>الملاحظات</th>
                        <th>ملف التصميم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['service_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <?php if ($show_paper_type): ?><td><?= htmlspecialchars($item['paper_type']) ?></td><?php endif; ?>
                        <?php if ($show_size): ?><td><?= htmlspecialchars($item['size']) ?></td><?php endif; ?>
                        <?php if ($show_colors): ?><td><?= htmlspecialchars($item['colors']) ?></td><?php endif; ?>
                        <td><?= number_format($item['price'], 2) ?> جنيه</td>
                        <td><?= number_format($item['price'] * $item['quantity'], 2) ?> جنيه</td>
                        <?php
                        // عرض القيم للحقول المخصصة حسب ترتيب الأعمدة
                        foreach ($all_custom_fields as $field_id => $field_label) {
                            $field_value = isset($order_item_custom_values[$item['item_id']][$field_id]) ? $order_item_custom_values[$item['item_id']][$field_id] : null;
                            echo '<td>';
                            echo $field_value !== null && $field_value !== '' ? htmlspecialchars($field_value) : '-';
                            echo '</td>';
                        }
                        ?>
                        <td><?= nl2br(htmlspecialchars($item['notes'])) ?></td>
                        <td>
                            <div class="file-links">
                            <?php 
                            if (!empty($item['files'])) {
                                $files = explode(',', $item['files']);
                                foreach ($files as $file) {
                                    $file = trim($file);
                                    $file_path = "../uploads/designs/" . $file;
                                    $file_url = "../uploads/designs/" . rawurlencode($file);
                                    $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    if (file_exists($file_path)) {
                                        $icon_class = 'fa-file';
                                        $icon_color = '#3498db';
                                        switch ($file_extension) {
                                            case 'pdf': $icon_class = 'fa-file-pdf'; $icon_color = '#e74c3c'; break;
                                            case 'ai': $icon_class = 'fa-file-vector'; $icon_color = '#e67e22'; break;
                                            case 'psd': $icon_class = 'fa-file-image'; $icon_color = '#3498db'; break;
                                            case 'eps': $icon_class = 'fa-file-vector'; $icon_color = '#9b59b6'; break;
                                            case 'cdr': $icon_class = 'fa-file-vector'; $icon_color = '#e74c3c'; break;
                                            case 'svg': $icon_class = 'fa-file-code'; $icon_color = '#2ecc71'; break;
                                            case 'indd': $icon_class = 'fa-file-alt'; $icon_color = '#f1c40f'; break;
                                            case 'jpg': case 'jpeg': case 'png': case 'gif': $icon_class = 'fa-file-image'; $icon_color = '#2ecc71'; break;
                                        }
                                        echo '<a href="' . htmlspecialchars($file_url) . '" class="btn btn-view" target="_blank" style="display: inline-flex; align-items: center; gap: 5px;"><i class="fas ' . $icon_class . '" style="color: ' . $icon_color . ';"></i><span>عرض</span></a>';
                                    } else {
                                        echo '<span class="text-danger">الملف غير موجود</span>';
                                    }
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <p><a href="orders.php" class="back-link"><i class="fas fa-arrow-right"></i> الرجوع إلى قائمة الطلبات</a></p>
    </div>
</body>
</html>


            <?php if (!empty($pending_points_transactions)): ?>
            <section class="info-section" style="margin-top: 25px;">
                <h2>نقاط معلقة للتحقق</h2>
                <table class="points-table">
                    <thead>
                        <tr>
                            <th>المبلغ</th>
                            <th>الوصف</th>
                            <th>التاريخ</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_points_transactions as $pt): ?>
                            <tr>
                                <td><?= format_points($pt["points_amount"]) ?></td>
                                <td><?= htmlspecialchars($pt["description"]) ?></td>
                                <td><?= htmlspecialchars($pt["created_at"]) ?></td>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="transaction_id" value="<?= $pt["transaction_id"] ?>">
                                        <input type="hidden" name="points_amount" value="<?= $pt["points_amount"] ?>">
                                        <input type="hidden" name="user_id" value="<?= $pt["user_id"] ?>">
                                        <button type="submit" name="action_points" value="approve" class="btn btn-success">قبول</button>
                                    </form>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="transaction_id" value="<?= $pt["transaction_id"] ?>">
                                        <input type="hidden" name="points_amount" value="<?= $pt["points_amount"] ?>">
                                        <input type="hidden" name="user_id" value="<?= $pt["user_id"] ?>">
                                        <button type="submit" name="action_points" value="reject" class="btn btn-danger">رفض</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <?php endif; ?>



