<?php
/**
 * الصفحة الرئيسية للوحة تحكم المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// التحقق من صلاحيات المسؤول
require_once 'auth.php';

// استدعاء ملف الرأس
require_once 'includes/header.php';

// الحصول على إحصائيات لوحة التحكم
try {
    // عدد الطلبات الجديدة
    $new_orders_query = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'new'");
    $new_orders_count = $new_orders_query->fetchColumn();
    
    // إجمالي الطلبات
    $total_orders_query = $db->query("SELECT COUNT(*) FROM orders");
    $total_orders_count = $total_orders_query->fetchColumn();
    
    // عدد المستخدمين
    $users_query = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $users_count = $users_query->fetchColumn();
    
    // عدد الخدمات
    $services_query = $db->query("SELECT COUNT(*) FROM services");
    $services_count = $services_query->fetchColumn();
    
    // عدد الرسائل الجديدة
    $new_messages_query = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'new'");
    $new_messages_count = $new_messages_query->fetchColumn();
    
    // آخر 5 طلبات
    $recent_orders_query = $db->query("
        SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $recent_orders_query->fetchAll();
    
    // آخر 5 رسائل
    $recent_messages_query = $db->query("
        SELECT * 
        FROM messages 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_messages = $recent_messages_query->fetchAll();
    
} catch (PDOException $e) {
    $error = 'حدث خطأ أثناء استرجاع البيانات: ' . $e->getMessage();
}
?>

<!-- محتوى الصفحة الرئيسية -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">لوحة التحكم</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">الرئيسية</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- بطاقات الإحصائيات -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $new_orders_count; ?></h3>
                            <p>طلبات جديدة</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="orders.php?status=new" class="small-box-footer">
                            عرض التفاصيل <i class="fas fa-arrow-circle-left"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $total_orders_count; ?></h3>
                            <p>إجمالي الطلبات</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <a href="orders.php" class="small-box-footer">
                            عرض التفاصيل <i class="fas fa-arrow-circle-left"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $users_count; ?></h3>
                            <p>المستخدمين</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="users.php" class="small-box-footer">
                            عرض التفاصيل <i class="fas fa-arrow-circle-left"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo $new_messages_count; ?></h3>
                            <p>رسائل جديدة</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <a href="messages.php" class="small-box-footer">
                            عرض التفاصيل <i class="fas fa-arrow-circle-left"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- آخر الطلبات -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">آخر الطلبات</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>العميل</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_orders)): ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>">
                                                        <?php echo $order['order_number']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $order['username'] ?: $order['shipping_name']; ?></td>
                                                <td><?php echo $order['total_amount']; ?> جنيه</td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    
                                                    switch ($order['status']) {
                                                        case 'new':
                                                            $status_class = 'badge-primary';
                                                            $status_text = 'جديد';
                                                            break;
                                                        case 'processing':
                                                            $status_class = 'badge-info';
                                                            $status_text = 'قيد التنفيذ';
                                                            break;
                                                        case 'ready':
                                                            $status_class = 'badge-success';
                                                            $status_text = 'جاهز';
                                                            break;
                                                        case 'delivered':
                                                            $status_class = 'badge-secondary';
                                                            $status_text = 'تم التسليم';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'badge-danger';
                                                            $status_text = 'ملغي';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">لا توجد طلبات حالياً</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer clearfix">
                            <a href="orders.php" class="btn btn-sm btn-secondary float-left">عرض جميع الطلبات</a>
                        </div>
                    </div>
                </div>
                
                <!-- آخر الرسائل -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">آخر الرسائل</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>المرسل</th>
                                        <th>الموضوع</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_messages)): ?>
                                        <?php foreach ($recent_messages as $message): ?>
                                            <tr>
                                                <td>
                                                    <a href="message-details.php?id=<?php echo $message['message_id']; ?>">
                                                        <?php echo $message['name']; ?>
                                                    </a>
                                                </td>
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
                                                <td><?php echo date('Y-m-d', strtotime($message['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">لا توجد رسائل حالياً</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer clearfix">
                            <a href="messages.php" class="btn btn-sm btn-secondary float-left">عرض جميع الرسائل</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- روابط سريعة -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">روابط سريعة</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <a href="services.php" class="btn btn-app">
                                        <i class="fas fa-list"></i> إدارة الخدمات
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <a href="portfolio.php" class="btn btn-app">
                                        <i class="fas fa-images"></i> معرض الأعمال
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <a href="users.php" class="btn btn-app">
                                        <i class="fas fa-users"></i> إدارة المستخدمين
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <a href="settings.php" class="btn btn-app">
                                        <i class="fas fa-cog"></i> الإعدادات
                                    </a>
                                </div>
                            </div>
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
