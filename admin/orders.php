<?php
/**
 * صفحة إدارة الطلبات في لوحة تحكم المسؤول
 */

define('PRINTLY', true);
require_once 'auth.php';
require_once 'includes/header.php';

// معالجة تحديث حالة الطلب
if (isset($_POST['update_status'])) {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? clean_input($_POST['status']) : '';
    
    if ($order_id > 0 && !empty($status)) {
        try {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $order_id]);
            $success_message = 'تم تحديث حالة الطلب بنجاح';
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ أثناء تحديث حالة الطلب: ' . $e->getMessage();
        }
    }
}

// معالجة الإجراءات الجماعية
if (isset($_POST['bulk_action']) && isset($_POST['selected_orders'])) {
    $action = clean_input($_POST['bulk_action']);
    $selected_orders = array_map('intval', $_POST['selected_orders']);
    
    if (!empty($selected_orders)) {
        try {
            $placeholders = str_repeat('?,', count($selected_orders) - 1) . '?';
            
            switch ($action) {
                case 'archive':
                    $stmt = $db->prepare("UPDATE orders SET status = 'archived' WHERE order_id IN ($placeholders)");
                    $stmt->execute($selected_orders);
                    $success_message = 'تم نقل الطلبات المحددة إلى الأرشيف بنجاح';
                    break;
                    
                case 'trash':
                    $stmt = $db->prepare("UPDATE orders SET status = 'trash' WHERE order_id IN ($placeholders)");
                    $stmt->execute($selected_orders);
                    $success_message = 'تم نقل الطلبات المحددة إلى سلة المهملات بنجاح';
                    break;
                    
                case 'restore':
                    $stmt = $db->prepare("UPDATE orders SET status = 'new' WHERE order_id IN ($placeholders)");
                    $stmt->execute($selected_orders);
                    $success_message = 'تم استعادة الطلبات المحددة بنجاح';
                    break;
                    
                case 'delete':
                    $stmt = $db->prepare("DELETE FROM orders WHERE order_id IN ($placeholders)");
                    $stmt->execute($selected_orders);
                    $success_message = 'تم حذف الطلبات المحددة بنجاح';
                    break;
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ أثناء تنفيذ العملية: ' . $e->getMessage();
        }
    }
}

// حالة التصفية
$filter_status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// استعلام الطلبات
$query = "
    SELECT o.*, u.username 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE 1
";
$params = [];

// تعديل الاستعلام حسب حالة التصفية
if (!empty($filter_status)) {
    if ($filter_status === 'active') {
        // عرض الطلبات النشطة فقط (غير المؤرشفة وغير المحذوفة)
        $query .= " AND o.status NOT IN ('archived', 'trash')";
    } else {
        $query .= " AND o.status = ?";
        $params[] = $filter_status;
    }
} else {
    // افتراضياً، عرض الطلبات النشطة فقط
    $query .= " AND o.status NOT IN ('archived', 'trash')";
}

$query .= " ORDER BY o.created_at DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء استرجاع الطلبات: ' . $e->getMessage();
    $orders = [];
}
?>

<!-- واجهة الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1 class="m-0">إدارة الطلبات</h1></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">إدارة الطلبات</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h5><i class="icon fas fa-check"></i> نجاح!</h5>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> خطأ!</h5>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">قائمة الطلبات</h3>
                    <div class="card-tools">
                        <form method="post" id="bulk-action-form" class="d-inline-block">
                            <!-- أزرار الإجراءات الجماعية (تظهر فقط عند تحديد الطلبات) -->
                            <div id="bulk-actions" class="btn-group d-none">
                                <?php if ($filter_status !== 'archived' && $filter_status !== 'trash'): ?>
                                <button type="submit" name="bulk_action" value="archive" class="btn btn-sm btn-warning">
                                    <i class="fas fa-archive"></i> نقل إلى الأرشيف
                                </button>
                                <button type="submit" name="bulk_action" value="trash" class="btn btn-sm btn-dark">
                                    <i class="fas fa-trash"></i> نقل إلى سلة المهملات
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($filter_status === 'archived' || $filter_status === 'trash'): ?>
                                <button type="submit" name="bulk_action" value="restore" class="btn btn-sm btn-info">
                                    <i class="fas fa-undo"></i> استعادة
                                </button>
                                <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف الطلبات المحددة؟')">
                                    <i class="fas fa-trash-alt"></i> حذف نهائي
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        <div class="btn-group ml-2">
                            <a href="orders.php" class="btn btn-sm btn-default <?php echo empty($filter_status) ? 'active' : ''; ?>">الطلبات النشطة</a>
                            <a href="orders.php?status=new" class="btn btn-sm btn-primary <?php echo $filter_status == 'new' ? 'active' : ''; ?>">جديد</a>
                            <a href="orders.php?status=processing" class="btn btn-sm btn-info <?php echo $filter_status == 'processing' ? 'active' : ''; ?>">قيد التنفيذ</a>
                            <a href="orders.php?status=ready" class="btn btn-sm btn-success <?php echo $filter_status == 'ready' ? 'active' : ''; ?>">جاهز</a>
                            <a href="orders.php?status=delivered" class="btn btn-sm btn-secondary <?php echo $filter_status == 'delivered' ? 'active' : ''; ?>">تم التسليم</a>
                            <a href="orders.php?status=cancelled" class="btn btn-sm btn-danger <?php echo $filter_status == 'cancelled' ? 'active' : ''; ?>">ملغي</a>
                            <a href="orders.php?status=archived" class="btn btn-sm btn-warning <?php echo $filter_status == 'archived' ? 'active' : ''; ?>">الأرشيف</a>
                            <a href="orders.php?status=trash" class="btn btn-sm btn-dark <?php echo $filter_status == 'trash' ? 'active' : ''; ?>">سلة المهملات</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="orders-form">
                        <table class="table table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="select-all">
                                            <label class="custom-control-label" for="select-all"></label>
                                        </div>
                                    </th>
                                    <th>#</th>
                                    <th>رقم الطلب</th>
                                    <th>العميل</th>
                                    <th>المبلغ</th>
                                    <th>حالة الطلب</th>
                                    <th>حالة الدفع</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $index => $order): ?>
                                        <tr>
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input order-checkbox" id="order-<?php echo $order['order_id']; ?>" name="selected_orders[]" value="<?php echo $order['order_id']; ?>">
                                                    <label class="custom-control-label" for="order-<?php echo $order['order_id']; ?>"></label>
                                                </div>
                                            </td>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <a href="order-details.php?order_id=<?php echo $order['order_id']; ?>">
                                                    <?php echo $order['order_number']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $order['username'] ?: $order['shipping_name']; ?></td>
                                            <td><?php echo $order['total_amount']; ?> جنيه</td>
                                            <td>
                                                <?php
                                                $status_class = match($order['status']) {
                                                    'new' => 'badge-primary',
                                                    'processing' => 'badge-info',
                                                    'ready' => 'badge-success',
                                                    'delivered' => 'badge-secondary',
                                                    'cancelled' => 'badge-danger',
                                                    'archived' => 'badge-warning',
                                                    'trash' => 'badge-dark',
                                                    default => 'badge-light'
                                                };
                                                $status_text = match($order['status']) {
                                                    'new' => 'جديد',
                                                    'processing' => 'قيد التنفيذ',
                                                    'ready' => 'جاهز',
                                                    'delivered' => 'تم التسليم',
                                                    'cancelled' => 'ملغي',
                                                    'archived' => 'في الأرشيف',
                                                    'trash' => 'في سلة المهملات',
                                                    default => 'غير معروف'
                                                };
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $payment_class = match($order['payment_status']) {
                                                    'pending' => 'badge-warning',
                                                    'paid' => 'badge-success',
                                                    'refunded' => 'badge-info',
                                                    default => 'badge-light'
                                                };
                                                $payment_text = match($order['payment_status']) {
                                                    'pending' => 'في الانتظار',
                                                    'paid' => 'مدفوع',
                                                    'refunded' => 'مسترجع',
                                                    default => 'غير معروف'
                                                };
                                                ?>
                                                <span class="badge <?php echo $payment_class; ?>"><?php echo $payment_text; ?></span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="order-details.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> عرض
                                                    </a>
                                                </div>

                                                <!-- نافذة تعديل الحالة -->
                                                <div class="modal fade" id="statusModal<?php echo $order['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">تحديث حالة الطلب #<?php echo $order['order_number']; ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <form action="orders.php<?php echo !empty($filter_status) ? '?status=' . $filter_status : ''; ?>" method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                    <div class="form-group">
                                                                        <label for="status">حالة الطلب</label>
                                                                        <select name="status" class="form-control">
                                                                            <option value="new" <?php echo $order['status'] == 'new' ? 'selected' : ''; ?>>جديد</option>
                                                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                                                                            <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>جاهز</option>
                                                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>تم التسليم</option>
                                                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                                                            <option value="archived" <?php echo $order['status'] == 'archived' ? 'selected' : ''; ?>>نقل إلى الأرشيف</option>
                                                                            <option value="trash" <?php echo $order['status'] == 'trash' ? 'selected' : ''; ?>>نقل إلى سلة المهملات</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">تحديث</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد طلبات متاحة</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- تحديث JavaScript للتحكم في ظهور الأزرار وإرسال النموذج -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const orderCheckboxes = document.getElementsByClassName('order-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const ordersForm = document.getElementById('orders-form');
    const bulkActionForm = document.getElementById('bulk-action-form');
    
    function updateBulkActionsVisibility() {
        const hasSelected = Array.from(orderCheckboxes).some(cb => cb.checked);
        if (hasSelected) {
            bulkActions.classList.remove('d-none');
        } else {
            bulkActions.classList.add('d-none');
        }
    }
    
    selectAll.addEventListener('change', function() {
        Array.from(orderCheckboxes).forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionsVisibility();
    });
    
    Array.from(orderCheckboxes).forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            selectAll.checked = Array.from(orderCheckboxes).every(cb => cb.checked);
            updateBulkActionsVisibility();
        });
    });

    // إضافة معالجة النقر على أزرار الإجراءات الجماعية
    bulkActionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const selectedOrders = Array.from(orderCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        if (selectedOrders.length === 0) {
            alert('الرجاء تحديد طلب واحد على الأقل');
            return;
        }

        const formData = new FormData(ordersForm);
        formData.append('bulk_action', e.submitter.value);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
