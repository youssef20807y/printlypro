<?php
/**
 * صفحة تحرير المستخدم في لوحة تحكم المسؤول
 */
define('PRINTLY', true);
require_once 'auth.php';
require_once 'includes/header.php';
require_once '../includes/points_functions.php'; // إضافة دوال النقاط

// التحقق من وجود معرف المستخدم
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);
$message = '';
$success = false;

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';
    
    // إدارة النقاط
    $points_balance = isset($_POST['points_balance']) ? intval($_POST['points_balance']) : null;
    $total_points_earned = isset($_POST['total_points_earned']) ? intval($_POST['total_points_earned']) : null;
    $total_points_spent = isset($_POST["total_points_spent"]) ? intval($_POST["total_points_spent"]) : null;
    $unverified_points_balance = isset($_POST["unverified_points_balance"]) ? intval($_POST["unverified_points_balance"]) : null;
    $points_action = $_POST['points_action'] ?? '';
    $points_amount = intval($_POST['points_amount'] ?? 0);
    $points_reason = trim($_POST['points_reason'] ?? '');

    // التحقق من صحة البيانات
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'يرجى إدخال بريد إلكتروني صحيح';
    } else {
        try {
            // جلب بيانات المستخدم الحالية
            $current_user_stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $current_user_stmt->execute([$user_id]);
            $current_user = $current_user_stmt->fetch();
            
            // الاحتفاظ بالقيم الحالية إذا لم يتم إدخال قيم جديدة
            if (empty($username)) {
                $username = $current_user['username'];
            }
            if (empty($email)) {
                $email = $current_user['email'];
            }
            if (empty($phone)) {
                $phone = $current_user['phone'];
            }
            if (empty($city)) {
                $city = $current_user['city'];
            }
            if (empty($country)) {
                $country = $current_user['country'];
            }
            if (empty($address)) {
                $address = $current_user['address'];
            }
            
            // الاحتفاظ بقيم النقاط الحالية إذا لم يتم إدخال قيم جديدة
            if ($points_balance === null) {
                $points_balance = intval($current_user['points_balance'] ?? 0);
            }
            if ($total_points_earned === null) {
                $total_points_earned = intval($current_user['total_points_earned'] ?? 0);
            }
            if ($total_points_spent === null) {
                $total_points_spent = intval($current_user['total_points_spent'] ?? 0);
            }
            if ($unverified_points_balance === null) {
                $unverified_points_balance = intval($current_user['unverified_points_balance'] ?? 0);
            }
            
            // التحقق من عدم وجود بريد إلكتروني مكرر إذا تم تغيير البريد الإلكتروني
            if ($email !== $current_user['email']) {
                $check_email = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $check_email->execute([$email, $user_id]);
                
                if ($check_email->rowCount() > 0) {
                    $message = 'البريد الإلكتروني مستخدم بالفعل';
                } else {
                    // بدء المعاملة
                    $db->beginTransaction();
                    
                    // تحديث بيانات المستخدم الأساسية (بدون النقاط)
                    $update_user = $db->prepare("UPDATE users SET username = ?, email = ?, phone = ?, city = ?, country = ?, address = ?, role = ?, status = ? WHERE user_id = ?");
                    $update_user->execute([$username, $email, $phone, $city, $country, $address, $role, $status, $user_id]);
                    
                    // إضافة معاملة نقاط إذا تم تحديدها
                    if (!empty($points_action) && $points_amount > 0) {
                        $transaction_type = ($points_action === 'add') ? 'admin_add' : 'admin_deduct';
                        $description = $points_reason ?: (($points_action === 'add') ? 'إضافة نقاط من قبل المسؤول' : 'خصم نقاط من قبل المسؤول');
                        
                        // استخدام الدوال الجديدة لإضافة/خصم النقاط مع تمرير اتصال قاعدة البيانات الحالي
                        if ($points_action === 'add') {
                            add_user_points($user_id, $points_amount, $transaction_type, null, $description, null, $db);
                        } elseif ($points_action === 'deduct') {
                            deduct_user_points($user_id, $points_amount, $transaction_type, null, $description, null, $db);
                        }
                    } else {
                        // تحديث النقاط مباشرة إذا لم يتم تحديد إجراء نقاط
                        $update_points = $db->prepare("UPDATE users SET points_balance = ?, total_points_earned = ?, total_points_spent = ?, unverified_points_balance = ? WHERE user_id = ?");
                        $update_points->execute([$points_balance, $total_points_earned, $total_points_spent, $unverified_points_balance, $user_id]);
                    }
                    
                    $db->commit();
                    $success = true;
                    $message = 'تم تحديث بيانات المستخدم بنجاح';
                }
            } else {
                // إذا لم يتم تغيير البريد الإلكتروني، تحديث البيانات مباشرة
                $db->beginTransaction();
                
                // تحديث بيانات المستخدم الأساسية (بدون النقاط)
                $update_user = $db->prepare("UPDATE users SET username = ?, email = ?, phone = ?, city = ?, country = ?, address = ?, role = ?, status = ? WHERE user_id = ?");
                $update_user->execute([$username, $email, $phone, $city, $country, $address, $role, $status, $user_id]);
                
                // إضافة معاملة نقاط إذا تم تحديدها
                if (!empty($points_action) && $points_amount > 0) {
                    $transaction_type = ($points_action === 'add') ? 'admin_add' : 'admin_deduct';
                    $description = $points_reason ?: (($points_action === 'add') ? 'إضافة نقاط من قبل المسؤول' : 'خصم نقاط من قبل المسؤول');
                    
                    // استخدام الدوال الجديدة لإضافة/خصم النقاط مع تمرير اتصال قاعدة البيانات الحالي
                    if ($points_action === 'add') {
                        add_user_points($user_id, $points_amount, $transaction_type, null, $description, null, $db);
                    } elseif ($points_action === 'deduct') {
                        deduct_user_points($user_id, $points_amount, $transaction_type, null, $description, null, $db);
                    }
                } else {
                    // تحديث النقاط مباشرة إذا لم يتم تحديد إجراء نقاط
                    $update_points = $db->prepare("UPDATE users SET points_balance = ?, total_points_earned = ?, total_points_spent = ?, unverified_points_balance = ? WHERE user_id = ?");
                    $update_points->execute([$points_balance, $total_points_earned, $total_points_spent, $unverified_points_balance, $user_id]);
                }
                
                $db->commit();
                $success = true;
                $message = 'تم تحديث بيانات المستخدم بنجاح';
            }
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $message = 'حدث خطأ أثناء تحديث البيانات: ' . $e->getMessage();
        }
    }
}

// جلب بيانات المستخدم
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: users.php");
        exit();
    }
    
    // جلب معاملات النقاط باستخدام دالة points_functions
    $points_transactions = get_user_points_transactions($user_id, 10);
    
    // جلب طلبات المستخدم
    $orders_stmt = $db->prepare("
        SELECT o.*
        FROM orders o
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $orders_stmt->execute([$user_id]);
    $orders = $orders_stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = 'حدث خطأ أثناء استرجاع البيانات: ' . $e->getMessage();
    $user = null;
    $points_transactions = [];
    $orders = [];
}
?>

<!-- محتوى الصفحة -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">تحرير المستخدم</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="users.php">إدارة المستخدمين</a></li>
                        <li class="breadcrumb-item active">تحرير المستخدم</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-<?php echo $success ? 'check' : 'ban'; ?>"></i> <?php echo $success ? 'نجاح!' : 'خطأ!'; ?></h5>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($user): ?>
                <div class="row">
                    <!-- معلومات المستخدم الأساسية -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">معلومات المستخدم</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>ملاحظة:</strong> جميع الحقول اختيارية. يمكنك ترك أي حقل فارغاً إذا كنت لا تريد تحديثه.
                                </div>
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>اسم المستخدم <small class="text-muted">(اختياري)</small></label>
                                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>البريد الإلكتروني <small class="text-muted">(اختياري)</small></label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>رقم الجوال <small class="text-muted">(اختياري)</small></label>
                                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>المدينة <small class="text-muted">(اختياري)</small></label>
                                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>الدولة <small class="text-muted">(اختياري)</small></label>
                                                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>العنوان <small class="text-muted">(اختياري)</small></label>
                                                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>الدور</label>
                                                <select name="role" class="form-control">
                                                    <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>عميل</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مسؤول</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>الحالة</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                                    <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>محظور</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> حفظ التغييرات
                                        </button>
                                        <a href="users.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> العودة
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- إدارة النقاط -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-star"></i> إدارة النقاط
                                </h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label>رصيد النقاط الحالي</label>
                                        <input type="number" name="points_balance" class="form-control" value="<?php echo intval($user['points_balance'] ?? 0); ?>" min="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>إجمالي النقاط المكتسبة</label>
                                        <input type="number" name="total_points_earned" class="form-control" value="<?php echo intval($user['total_points_earned'] ?? 0); ?>" min="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>إجمالي النقاط المستهلكة</label>
                                        <input type="number" name="total_points_spent" class="form-control" value="<?php echo intval($user['total_points_spent'] ?? 0); ?>" min="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>رصيد النقاط غير المؤكدة</label>
                                        <input type="number" name="unverified_points_balance" class="form-control" value="<?php echo intval($user['unverified_points_balance'] ?? 0); ?>" min="0">
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="form-group">
                                        <label>إجراء إضافي للنقاط</label>
                                        <select name="points_action" class="form-control">
                                            <option value="">لا يوجد إجراء</option>
                                            <option value="add">إضافة نقاط</option>
                                            <option value="deduct">خصم نقاط</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>عدد النقاط</label>
                                        <input type="number" name="points_amount" class="form-control" value="0" min="1">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>سبب الإجراء</label>
                                        <textarea name="points_reason" class="form-control" rows="2" placeholder="سبب إضافة أو خصم النقاط"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fas fa-coins"></i> تطبيق إجراء النقاط
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- معاملات النقاط الأخيرة -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-history"></i> معاملات النقاط الأخيرة
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($points_transactions)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>النوع</th>
                                                    <th>المبلغ</th>
                                                    <th>التاريخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($points_transactions as $transaction): ?>
                                                    <tr>
                                                        <td>
                                                            <?php
                                                            $type_labels = [
                                                                'purchase' => 'شراء',
                                                                'earn' => 'اكتساب',
                                                                'spend' => 'استهلاك',
                                                                'admin_adjust' => 'تعديل مسؤول',
                                                                'expire' => 'انتهاء صلاحية'
                                                            ];
                                                            echo $type_labels[$transaction['transaction_type']] ?? $transaction['transaction_type'];
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?php echo in_array($transaction['transaction_type'], ['purchase', 'earn', 'admin_adjust']) ? 'success' : 'danger'; ?>">
                                                                <?php echo ($transaction['points_amount'] > 0) ? '+' : ''; ?><?php echo number_format($transaction['points_amount']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">لا توجد معاملات نقاط</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- آخر الطلبات -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-shopping-cart"></i> آخر الطلبات
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($orders)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>رقم الطلب</th>
                                                    <th>المجموع</th>
                                                    <th>النقاط</th>
                                                    <th>الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                        <td><?php echo number_format($order['total_amount'], 2); ?> جنيه</td>
                                                        <td>
                                                            <?php if ($order['points_earned'] > 0): ?>
                                                                <span class="badge badge-success">+<?php echo number_format($order['points_earned']); ?></span>
                                                            <?php elseif ($order['points_used'] > 0): ?>
                                                                <span class="badge badge-danger">-<?php echo number_format($order['points_used']); ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_labels = [
                                                                'new' => 'في المراجعة',
                                                                'processing' => 'قيد التنفيذ',
                                                                'ready' => 'جاهز',
                                                                'delivered' => 'تم التسليم',
                                                                'cancelled' => 'ملغي',
                                                                'trash' => 'محذوف',
                                                                'archived' => 'مؤرشف'
                                                            ];
                                                            $status_colors = [
                                                                'new' => 'secondary',
                                                                'processing' => 'info',
                                                                'ready' => 'primary',
                                                                'delivered' => 'success',
                                                                'cancelled' => 'danger',
                                                                'trash' => 'dark',
                                                                'archived' => 'warning'
                                                            ];
                                                            ?>
                                                            <span class="badge badge-<?php echo $status_colors[$order['status']] ?? 'secondary'; ?>">
                                                                <?php echo $status_labels[$order['status']] ?? 'غير محدد'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">لا توجد طلبات</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // إخفاء/إظهار حقول إجراء النقاط
    $('select[name="points_action"]').change(function() {
        var action = $(this).val();
        if (action) {
            $('input[name="points_amount"], textarea[name="points_reason"]').closest('.form-group').show();
        } else {
            $('input[name="points_amount"], textarea[name="points_reason"]').closest('.form-group').hide();
        }
    });
    
    // إخفاء الحقول في البداية
    $('input[name="points_amount"], textarea[name="points_reason"]').closest('.form-group').hide();
});
</script>

<?php
require_once 'includes/footer.php';
?> 