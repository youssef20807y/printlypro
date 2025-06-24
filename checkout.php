<?php
/**
 * صفحة إتمام الطلب المحدثة لموقع مطبعة برنتلي
 */

// استدعاء ملف الإعدادات
require_once 'includes/config.php';
require_once 'includes/points_functions.php'; // إضافة دوال النقاط

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// الاتصال بقاعدة البيانات
try {
    $db = db_connect();
} catch (Exception $db_error) {
    error_log('Database connection error: ' . $db_error->getMessage());
    $_SESSION['error'] = 'حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى.';
    header('Location: cart.php');
    exit;
}

// التحقق من وجود منتجات في السلة
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ? OR session_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? null, session_id()]);
    $cart_count = $stmt->fetchColumn();

    if ($cart_count == 0) {
        $_SESSION['error'] = 'السلة فارغة. يرجى إضافة منتجات قبل إتمام الطلب.';
        header('Location: cart.php');
        exit;
    }
} catch (Exception $cart_error) {
    error_log('Error checking cart: ' . $cart_error->getMessage());
    $_SESSION['error'] = 'حدث خطأ في التحقق من محتويات السلة. يرجى المحاولة مرة أخرى.';
    header('Location: cart.php');
    exit;
}

// جلب النقاط المستخدمة من URL
$points_used = isset($_GET['points_used']) ? (int)$_GET['points_used'] : 0;

// معالجة إرسال نموذج إتمام الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $errors = [];
    
    // التحقق من تسجيل الدخول
    if (!is_logged_in()) {
        $errors[] = 'يجب تسجيل الدخول أولاً لإتمام الطلب';
    }
    
    // التحقق من اختيار طريقة الاستلام
    if (empty($_POST['delivery_method'])) {
        $errors[] = 'يرجى اختيار طريقة الاستلام';
    }
    
    // التحقق من البيانات المطلوبة حسب طريقة الاستلام
    if ($_POST['delivery_method'] === 'pickup') {
        if (empty($_POST['name_pickup'])) {
            $errors[] = 'يرجى إدخال الاسم الكامل للاستلام من المطبعة';
        }
        if (empty($_POST['phone_pickup'])) {
            $errors[] = 'يرجى إدخال رقم الهاتف للاستلام من المطبعة';
        }
    } elseif ($_POST['delivery_method'] === 'delivery') {
        $required_fields = ['name_delivery', 'phone_delivery', 'email', 'address', 'city', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = 'جميع الحقول المميزة بعلامة (*) مطلوبة للتوصيل للمنزل';
                break;
            }
        }
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'يرجى إدخال بريد إلكتروني صحيح';
        }
    }
    
    // التحقق من النقاط المستخدمة
    if (isset($_POST['points_used']) && $_POST['points_used'] > 0) {
        $points_used = (int)$_POST['points_used'];
        if (is_points_system_enabled() && isset($_SESSION['user_id'])) {
            $user_points = get_user_points($_SESSION['user_id']);
            if (!$user_points || $user_points['balance'] < $points_used) {
                $errors[] = 'رصيد النقاط غير كافي';
            }
        }
    }
    
    // إذا لم تكن هناك أخطاء، قم بإنشاء الطلب
    if (empty($errors)) {
        try {
            // بدء المعاملة
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }
            
            // إنشاء رقم الطلب الفريد
            $timestamp = time();
            $date = date('Ymd');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $order_number = 'ORD-' . $date . '-' . $random;
            
            // التحقق من عدم تكرار رقم الطلب وإنشاء رقم جديد إذا كان مكرراً
            $max_attempts = 10;
            $attempt = 0;
            $is_unique = false;
            
            while (!$is_unique && $attempt < $max_attempts) {
                try {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE order_number = ?");
                    $stmt->execute([$order_number]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count == 0) {
                        $is_unique = true;
                    } else {
                        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                        $order_number = 'ORD-' . $date . '-' . $random;
                        $attempt++;
                    }
                } catch (PDOException $e) {
                    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $order_number = 'ORD-' . $date . '-' . $random;
                    $attempt++;
                }
            }
            
            if (!$is_unique) {
                throw new Exception('فشل في إنشاء رقم طلب فريد بعد عدة محاولات');
            }
            
            // الحصول على بيانات المستخدم
            $user_id = $_SESSION['user_id'];
            $delivery_method = $_POST['delivery_method'];
            if ($delivery_method === 'pickup') {
                $name = $_POST['name_pickup'];
                $phone = $_POST['phone_pickup'];
                $email = '';
                $address = '';
                $city = '';
                $payment_method = '';
            } else {
                $name = $_POST['name_delivery'];
                $phone = $_POST['phone_delivery'];
                $email = $_POST['email'] ?? '';
                $address = $_POST['address'] ?? '';
                $city = $_POST['city'] ?? '';
                $payment_method = $_POST['payment_method'] ?? '';
            }
            $postal_code = $_POST['postal_code'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            // حساب المجموع الفرعي من السلة
            $stmt = $db->prepare("
                SELECT c.*, s.name as service_name, s.image, s.price_start 
                FROM cart c 
                JOIN services s ON c.service_id = s.service_id 
                WHERE (c.user_id = ? OR c.session_id = ?) AND s.status = 'active'
            ");
            $stmt->execute([$user_id, session_id()]);
            $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $subtotal = 0;
            $cart_items = [];
            foreach ($cart_data as $item) {
                $item_total = $item['price'] ?? ($item['price_start'] * $item['quantity']);
                $subtotal += $item_total;
                
                // فك تشفير القيم المخصصة إذا وجدت
                $custom_fields_values = [];
                if (isset($item['custom_fields_values']) && $item['custom_fields_values']) {
                    $decoded = json_decode($item['custom_fields_values'], true);
                    if (is_array($decoded)) {
                        $custom_fields_values = $decoded;
                    }
                }

                $cart_item = [
                    'service_id' => $item['service_id'],
                    'name' => $item['service_name'],
                    'price' => $item['price_start'],
                    'price_start' => $item['price_start'],
                    'quantity' => $item['quantity'],
                    'image' => $item['image'] ?? 'default-service.jpg',
                    'paper_type' => $item['paper_type'],
                    'size' => $item['size'],
                    'colors' => $item['colors'],
                    'notes' => $item['notes'],
                    'design_file' => $item['design_file'],
                    'total' => $item_total,
                    'custom_fields_values' => $custom_fields_values
                ];
                $cart_items[] = $cart_item;
            }
            
            // حساب تكلفة الشحن
            $stmt = $db->prepare("SELECT cost FROM delivery_methods WHERE type = ? AND status = 'active'");
            $stmt->execute([$delivery_method]);
            $shipping_cost = $stmt->fetchColumn() ?: 0;
            
            // التحقق من وجود طريقة التوصيل
            $stmt = $db->prepare("SELECT delivery_id FROM delivery_methods WHERE type = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$delivery_method]);
            $delivery_method_id = $stmt->fetchColumn();
            
            if (!$delivery_method_id) {
                throw new Exception('طريقة التوصيل المختارة غير متاحة');
            }
            
            // حساب خصم النقاط
            $points_discount = 0;
            if ($points_used > 0 && is_points_system_enabled()) {
                // حساب الخصم من النقاط: 1 نقطة = 0.01 جنيه (مطابق لـ cart.php)
                $points_discount = $points_used * 0.01;
            }
            
            // حساب الإجمالي النهائي
            $total = $subtotal + $shipping_cost - $points_discount;
            
            // التأكد من أن الإجمالي لا يكون سالباً
            if ($total < 0) {
                $total = 0;
            }
            
            // إنشاء الطلب في قاعدة البيانات
            $stmt = $db->prepare("
                INSERT INTO orders (
                    user_id, order_number, total_amount, status, payment_method, payment_status,
                    delivery_method_id, delivery_type, shipping_name, shipping_phone, shipping_email, 
                    shipping_address, shipping_city, shipping_cost, pickup_name, pickup_phone,
                    notes, points_used, points_discount, created_at
                ) VALUES (
                    ?, ?, ?, 'new', ?, 'pending', 
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");
            
            $shipping_address = $address . (empty($postal_code) ? '' : ', ' . $postal_code);
            
            $stmt->execute([
                $user_id,
                $order_number,
                $total,
                $payment_method,
                $delivery_method_id,
                $delivery_method,
                $delivery_method === 'delivery' ? $name : null,
                $delivery_method === 'delivery' ? $phone : null,
                $delivery_method === 'delivery' ? $email : null,
                $delivery_method === 'delivery' ? $shipping_address : null,
                $delivery_method === 'delivery' ? $city : null,
                $shipping_cost,
                $delivery_method === 'pickup' ? $name : null,
                $delivery_method === 'pickup' ? $phone : null,
                $notes,
                $points_used,
                $points_discount
            ]);
            
            $order_id = $db->lastInsertId();

            // التحقق من أن الطلب تم إنشاؤه بنجاح
            if (!$order_id) {
                throw new Exception('فشل في إنشاء الطلب - لم يتم الحصول على معرف الطلب');
            }

            // التحقق من وجود الطلب في قاعدة البيانات
            $check_order = $db->prepare("SELECT order_id FROM orders WHERE order_id = ?");
            $check_order->execute([$order_id]);
            if (!$check_order->fetch()) {
                throw new Exception('فشل في التحقق من وجود الطلب في قاعدة البيانات');
            }

            // التحقق من أن السلة تحتوي على عناصر
            if (empty($cart_items)) {
                throw new Exception('السلة فارغة - لا يمكن إنشاء طلب بدون منتجات');
            }
            
            // إضافة نقاط غير مؤكدة للمستخدم عند إتمام الطلب
            if (isset($_SESSION["user_id"]) && is_points_system_enabled()) {
                $points_to_earn_on_order = calculate_points_from_amount($total);
                if ($points_to_earn_on_order > 0) {
                    try {
                        add_unverified_points(
                            $user_id, 
                            $points_to_earn_on_order, 
                            $order_id, 
                            "نقاط غير مؤكدة من الطلب #" . $order_number,
                            null,
                            $db
                        );
                    } catch (Exception $points_error) {
                        error_log('Error adding unverified points: ' . $points_error->getMessage());
                        // لا نوقف العملية إذا فشل إضافة النقاط
                    }
                }
            }
            
            // خصم النقاط المستخدمة
            if ($points_used > 0 && is_points_system_enabled()) {
                try {
                    $deduct_success = deduct_user_points(
                        $user_id, 
                        $points_used, 
                        'spend', 
                        $order_id, 
                        'استخدام نقاط في الطلب #' . $order_number,
                        null,
                        $db
                    );
                    
                    if (!$deduct_success) {
                        error_log('Failed to deduct points for order: ' . $order_id);
                    }
                } catch (Exception $points_error) {
                    error_log('Error deducting points: ' . $points_error->getMessage());
                    // لا نوقف العملية إذا فشل خصم النقاط
                }
            }
            
            // إضافة عناصر الطلب من السلة
            $items_created = 0;
            $failed_items = [];
            
            foreach ($cart_items as $item) {
                try {
                    // التحقق من وجود الخدمة قبل إضافة عنصر الطلب
                    $check_service = $db->prepare("SELECT service_id FROM services WHERE service_id = ? AND status = 'active'");
                    $check_service->execute([$item['service_id']]);
                    if (!$check_service->fetch()) {
                        $failed_items[] = 'الخدمة غير موجودة أو غير نشطة: ' . $item['service_id'];
                        continue;
                    }

                    $stmt = $db->prepare("
                        INSERT INTO order_items (
                            order_id, service_id, quantity, price, paper_type, size, colors, notes
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?
                        )
                    ");
                    
                    // استخدام السعر الأساسي للخدمة
                    $item_price = $item['price_start'];
                    
                    $stmt->execute([
                        $order_id,
                        $item['service_id'],
                        $item['quantity'],
                        $item_price,
                        $item['paper_type'],
                        $item['size'],
                        $item['colors'],
                        $item['notes']
                    ]);
                    
                    $item_id = $db->lastInsertId();
                    if (!$item_id) {
                        $failed_items[] = 'فشل في إنشاء عنصر الطلب للخدمة: ' . $item['service_id'];
                        continue;
                    }
                    
                    // إذا كان هناك ملف تصميم، احفظه في جدول order_item_files
                    if (!empty($item['design_file'])) {
                        $stmt = $db->prepare("
                            INSERT INTO order_item_files (item_id, file_name) 
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$item_id, $item['design_file']]);
                        
                        $file_id = $db->lastInsertId();
                        if (!$file_id) {
                            error_log('فشل في حفظ ملف التصميم لعنصر الطلب: ' . $item_id);
                        }
                    }
                    
                    // جلب القيم المخصصة للحقول من cart (custom_fields_values)
                    $custom_fields_values = isset($item['custom_fields_values']) ? $item['custom_fields_values'] : [];
                    if (!empty($custom_fields_values)) {
                        foreach ($custom_fields_values as $field_id => $field_value) {
                            $insert_field_stmt = $db->prepare("INSERT INTO order_field_values (order_item_id, field_id, field_value) VALUES (?, ?, ?)");
                            $insert_field_stmt->execute([$item_id, $field_id, $field_value]);
                        }
                    }
                    
                    $items_created++;
                } catch (Exception $item_error) {
                    $failed_items[] = 'خطأ في إضافة عنصر الطلب: ' . $item_error->getMessage();
                    error_log('Order item creation error: ' . $item_error->getMessage());
                }
            }
            
            // التحقق من أن جميع عناصر الطلب تم إنشاؤها بنجاح
            if ($items_created === 0) {
                throw new Exception('لم يتم إنشاء أي عناصر للطلب. الأخطاء: ' . implode(', ', $failed_items));
            }
            
            // إذا فشل بعض العناصر، أضف تحذير
            if (!empty($failed_items)) {
                error_log('Some order items failed to create: ' . implode(', ', $failed_items));
            }
            
            // التحقق النهائي من صحة البيانات
            try {
                $final_check = $db->prepare("
                    SELECT COUNT(*) as items_count 
                    FROM order_items 
                    WHERE order_id = ?
                ");
                $final_check->execute([$order_id]);
                $final_items_count = $final_check->fetchColumn();
                
                if ($final_items_count !== $items_created) {
                    error_log('Order items count mismatch - Expected: ' . $items_created . ', Actual: ' . $final_items_count);
                    // لا نوقف العملية إذا كان هناك اختلاف في العدد
                }
            } catch (Exception $validation_error) {
                error_log('Error in final validation: ' . $validation_error->getMessage());
                // لا نوقف العملية إذا فشل التحقق النهائي
            }
            
            // تأكيد المعاملة
            $db->commit();
            
            // إفراغ السلة
            try {
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? OR session_id = ?");
                $stmt->execute([$user_id, session_id()]);
            } catch (Exception $cart_error) {
                error_log('Error clearing cart: ' . $cart_error->getMessage());
                // لا نوقف العملية إذا فشل إفراغ السلة
            }
            
            // تخزين معرف الطلب ورقمه في الجلسة للعرض في صفحة التأكيد
            $_SESSION['order_id'] = $order_id;
            $_SESSION['order_number'] = $order_number;
            
            // إعادة التوجيه إلى صفحة الدفع
            header('Location: payment.php');
            exit;
            
        } catch (Exception $e) {
            // التحقق من وجود معاملة نشطة قبل التراجع عنها
            if ($db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (PDOException $rollback_error) {
                    error_log('Rollback Error: ' . $rollback_error->getMessage());
                }
            }
            
            // تسجيل الخطأ في السجلات
            error_log('Checkout Error: ' . $e->getMessage() . ' - User ID: ' . ($user_id ?? 'unknown') . ' - Order Number: ' . ($order_number ?? 'unknown'));
            
            $errors[] = 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage();
            
            // إذا كان الخطأ يتعلق بقاعدة البيانات، أضف رسالة إضافية
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errors[] = 'يرجى المحاولة مرة أخرى أو التواصل مع الدعم الفني إذا استمرت المشكلة.';
            }
        }
    }
}

define('PRINTLY', true);
require_once 'includes/header.php';

// جلب محتويات السلة للعرض
$cart_items = [];
$subtotal = 0;

try {
    $stmt = $db->prepare("
        SELECT c.*, s.name as service_name, s.image, s.price_start 
        FROM cart c 
        JOIN services s ON c.service_id = s.service_id 
        WHERE (c.user_id = ? OR c.session_id = ?) AND s.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'] ?? null, session_id()]);
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cart_data as $item) {
        $item_total = $item['price'] ?? ($item['price_start'] * $item['quantity']);
        $subtotal += $item_total;
        
        // فك تشفير القيم المخصصة إذا وجدت
        $custom_fields_values = [];
        if (isset($item['custom_fields_values']) && $item['custom_fields_values']) {
            $decoded = json_decode($item['custom_fields_values'], true);
            if (is_array($decoded)) {
                $custom_fields_values = $decoded;
            }
        }

        $cart_item = [
            'service_id' => $item['service_id'],
            'name' => $item['service_name'],
            'price' => $item['price_start'],
            'price_start' => $item['price_start'],
            'quantity' => $item['quantity'],
            'image' => $item['image'] ?? 'default-service.jpg',
            'paper_type' => $item['paper_type'],
            'size' => $item['size'],
            'colors' => $item['colors'],
            'notes' => $item['notes'],
            'design_file' => $item['design_file'],
            'total' => $item_total,
            'custom_fields_values' => $custom_fields_values
        ];
        $cart_items[] = $cart_item;
    }
} catch (PDOException $e) {
    error_log('Error fetching cart contents: ' . $e->getMessage());
    $error_message = 'حدث خطأ أثناء جلب محتويات السلة';
    // لا نوقف العملية، فقط نعرض رسالة خطأ
}

// حساب الضريبة والشحن
$tax_rate = 0;
$tax = 0;
$shipping_cost = 0;

// حساب خصم النقاط
$points_discount = 0;
if ($points_used > 0 && is_points_system_enabled()) {
    // حساب الخصم من النقاط: 1 نقطة = 0.01 جنيه (مطابق لـ cart.php)
    $points_discount = $points_used * 0.01;
}

// حساب الإجمالي النهائي
$total = $subtotal + $tax + $shipping_cost - $points_discount;

// التأكد من أن الإجمالي لا يكون سالباً
if ($total < 0) {
    $total = 0;
}

$currency = 'جنيه';

// الحصول على بيانات المستخدم إذا كان مسجل الدخول
$user_data = [];
if (is_logged_in()) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching user data: ' . $e->getMessage());
        // تجاهل الخطأ، سيتم استخدام قيم فارغة
    }
}

// جلب رصيد النقاط
$user_points = 0;
$points_to_earn = 0;
try {
    $points_to_earn = calculate_points_from_amount($total);
} catch (Exception $points_error) {
    error_log('Error calculating points to earn: ' . $points_error->getMessage());
    // تجاهل الخطأ، سيتم استخدام قيمة 0
}

if (isset($_SESSION['user_id']) && is_points_system_enabled()) {
    try {
        $points_data = get_user_points($_SESSION['user_id']);
        if ($points_data) {
            $user_points = $points_data['balance'];
        }
    } catch (Exception $points_error) {
        error_log('Error fetching user points: ' . $points_error->getMessage());
        // تجاهل الخطأ، سيتم استخدام قيمة 0
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الطلب - مطبعة برنتلي</title>
    
    <style>
:root {
    --primary-color: #00adef;
    --primary-hover-color: #00adef;
    --primary-gradient: linear-gradient(135deg, #00adef, #00adef);
    --secondary-color: #343a40;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --text-color: #333;
    --border-color: #dee2e6;
    --muted-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    
    --border-radius: 1rem;
    --border-radius-sm: 0.5rem;
    --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --box-shadow-hover: 0 8px 25px rgba(212, 175, 55, 0.2);
    --transition: all 0.3s ease;
}

body {
    background-color: #f5f7fa;
    margin-top: 120px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--text-color);
    line-height: 1.6;
}

.checkout-section {
    padding: 3rem 0;
    max-width: 1200px;
    margin: 0 auto;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary-color);
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100px;
    height: 2px;
    background: var(--primary-gradient);
}

.form-group {
    margin-bottom: 1.75rem;
    position: relative;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--secondary-color);
    display: block;
    transition: var(--transition);
}

.form-control, 
.form-select {
    border-radius: var(--border-radius-sm);
    border: 2px solid var(--border-color);
    padding: 0.85rem 1.5rem;
    font-size: 1rem;
    width: 100%;
    transition: var(--transition);
    background-color: var(--light-color);
}

.form-control:focus, 
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.15);
    outline: none;
    background-color: #fff;
    transform: translateY(-2px);
}

.form-control:hover,
.form-select:hover {
    border-color: var(--primary-color);
}

.btn {
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    transition: var(--transition);
    padding: 0.9rem 2rem;
    position: relative;
    overflow: hidden;
}

.btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn:hover::after {
    width: 300px;
    height: 300px;
}

.btn-primary {
    background: var(--primary-gradient);
    border: none;
    color: #fff;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
}

.btn-primary:hover {
    background: var(--primary-hover-color);
    box-shadow: var(--box-shadow-hover);
    transform: translateY(-3px);
    color: #fff;
}

.btn-lg {
    padding: 1rem 2.5rem;
    font-size: 1.1rem;
}

.checkout-section .row {
    justify-content: space-between;
    gap: 2rem;
}

.col-lg-8 {
    flex: 0 0 60%;
    max-width: 60%;
}

.col-lg-4 {
    flex: 0 0 35%;
    max-width: 35%;
    position: sticky;
    top: 100px;
    align-self: flex-start;
}

.delivery-option {
    background: #fff;
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
}

.delivery-option:hover {
    transform: translateY(-5px);
    box-shadow: var(--box-shadow-hover);
}

.delivery-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.delivery-option-card {
    background: #fff;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: var(--transition);
    cursor: pointer;
    border: 2px solid var(--border-color);
}

.delivery-option-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-5px);
    box-shadow: var(--box-shadow);
}

.delivery-option-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.delivery-option-card input[type="radio"]:checked + .delivery-option-label .delivery-option-content {
    background: var(--primary-gradient);
    color: white;
}

.delivery-option-card input[type="radio"]:checked + .delivery-option-label .delivery-option-content i {
    color: white;
}

.delivery-option-label {
    margin: 0;
    cursor: pointer;
    width: 100%;
    display: block;
}

.delivery-option-content {
    text-align: center;
    padding: 1.5rem;
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.delivery-option-content i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
    transition: var(--transition);
}

.delivery-option-content h5 {
    margin: 0.5rem 0;
    font-weight: 600;
    font-size: 1.2rem;
}

.delivery-option-content p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.8;
}

.order-summary {
    background: #fff;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--box-shadow);
}

.order-items {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 1rem;
    margin-bottom: 1.5rem;
}

.order-items::-webkit-scrollbar {
    width: 6px;
}

.order-items::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.order-items::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1.25rem 0;
    border-bottom: 1px solid var(--border-color);
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--border-radius-sm);
    border: 2px solid var(--border-color);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.order-item-details {
    flex: 1;
}

.order-item-details h6 {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    color: var(--secondary-color);
}

.order-item-details small {
    color: var(--muted-color);
    font-size: 0.9rem;
    display: block;
    margin-bottom: 0.25rem;
}

.order-item-total {
    font-weight: 600;
    color: var(--primary-color);
    white-space: nowrap;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px dashed var(--border-color);
    color: var(--muted-color);
}

.summary-item .label {
    font-weight: 600;
    color: var(--secondary-color);
    text-align: right;
}

.summary-item .value {
    font-weight: 500;
    text-align: left;
    min-width: 100px;
}

.summary-item.total {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-color);
    border-top: 2px solid var(--primary-color);
    border-bottom: none;
    padding: 1.25rem 0;
    margin-top: 1.25rem;
}

.login-prompt {
    background: var(--primary-gradient);
    color: white;
    padding: 2.5rem;
    border-radius: var(--border-radius);
    text-align: center;
    margin-bottom: 2.5rem;
    box-shadow: var(--box-shadow);
    position: relative;
    overflow: hidden;
}

.login-prompt::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    z-index: 1;
}

.login-prompt h3 {
    margin-bottom: 1.75rem;
    font-weight: 700;
    position: relative;
    z-index: 2;
}

.login-prompt p {
    margin-bottom: 2rem;
    position: relative;
    z-index: 2;
}

.login-prompt .btn {
    background: white;
    color: var(--primary-color);
    border: none;
    margin: 0 1rem;
    padding: 0.75rem 2rem;
    font-weight: 600;
    position: relative;
    z-index: 2;
    transition: var(--transition);
}

.login-prompt .btn:hover {
    background: #f8f9fa;
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.alert {
    border-radius: var(--border-radius);
    padding: 1.25rem;
    margin-bottom: 2rem;
    border: none;
    box-shadow: var(--box-shadow);
}

.alert-danger {
    background-color: #fff5f5;
    color: var(--danger-color);
    border-right: 4px solid var(--danger-color);
}

.alert ul {
    margin: 0;
    padding-right: 1.5rem;
}

.alert li {
    margin-bottom: 0.5rem;
}

.alert li:last-child {
    margin-bottom: 0;
}

@media (max-width: 992px) {
    .col-lg-8,
    .col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .col-lg-4 {
        position: static;
        margin-top: 2rem;
    }
    
    .order-items {
        max-height: 300px;
    }
}

@media (max-width: 768px) {
    .checkout-section .row {
        gap: 1rem;
    }
    
    .order-item {
        padding: 1rem 0;
    }
    
    .order-item-image {
        width: 60px;
        height: 60px;
    }
    
    .summary-item.total {
        font-size: 1.25rem;
    }
    
    .delivery-option-card {
        padding: 1rem;
    }

    .delivery-option-content {
        padding: 1rem;
    }

    .delivery-option-content i {
        font-size: 2rem;
    }

    .delivery-option-content h5 {
        font-size: 1.1rem;
    }
}

::selection {
    background: var(--primary-color);
    color: white;
}

::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-hover-color);
}

#map {
    width: 100% !important;
    min-width: 200px;
    height: 250px !important;
    border-radius: var(--border-radius-sm);
    display: block;
}

/* تنسيق قسم النقاط التي سيحصل عليها المستخدم */
.points-earn-section {
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin: 1rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    color: white;
}

.points-earn-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.points-earn-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.points-earn-info i {
    font-size: 2rem;
    color: #ffc107;
}

.points-earn-amount {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.points-earn-info small {
    opacity: 0.9;
    font-size: 0.9rem;
}

.points-earn-value {
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .points-earn-section {
        padding: 1rem;
        margin: 0.5rem 0;
    }

    .points-earn-info {
        flex-direction: column;
        gap: 0.5rem;
    }

    .points-earn-info i {
        font-size: 1.5rem;
    }

    .points-earn-amount {
        font-size: 1.2rem;
    }

    .points-earn-value {
        padding: 0.3rem 0.8rem;
        font-size: 0.9rem;
    }
}
    </style>
</head>

<body>
    <section class="checkout-section">
        <div class="container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!is_logged_in()): ?>
                <div class="login-prompt">
                    <h3><i class="fas fa-user-lock me-2"></i>يجب تسجيل الدخول لإتمام الطلب</h3>
                    <p>لحماية بياناتك وضمان متابعة طلبك، يرجى تسجيل الدخول أو إنشاء حساب جديد</p>
                    <a href="login.php" class="btn btn-lg me-2">
                        <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                    </a>
                    <a href="register.php" class="btn btn-lg">
                        <i class="fas fa-user-plus me-2"></i>إنشاء حساب جديد
                    </a>
                </div>
            <?php endif; ?>
            
            <form action="checkout.php" method="post" id="checkout-form">
                <!-- حقل مخفي للنقاط المستخدمة -->
                <input type="hidden" name="points_used" value="<?= $points_used ?>">
                
                <div class="row">
                    <div class="col-lg-8">
                        <h2 class="section-title">
                            <i class="fas fa-shipping-fast me-2"></i>
                            معلومات الاستلام
                        </h2>
                        
                        <div class="delivery-option">
                            <div class="form-group">
                                <label class="form-label">طريقة الاستلام *</label>
                                <div class="delivery-options">
                                    <div class="delivery-option-card">
                                        <input class="form-check-input" type="radio" name="delivery_method" id="pickup" value="pickup" required>
                                        <label class="delivery-option-label" for="pickup">
                                            <div class="delivery-option-content">
                                                <i class="fas fa-store"></i>
                                                <h5>استلام من المطبعة</h5>
                                                <p>استلام الطلب من مقر المطبعة</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="delivery-option-card">
                                        <input class="form-check-input" type="radio" name="delivery_method" id="delivery" value="delivery" required>
                                        <label class="delivery-option-label" for="delivery">
                                            <div class="delivery-option-content">
                                                <i class="fas fa-truck"></i>
                                                <h5>توصيل للمنزل</h5>
                                                <p>توصيل الطلب إلى عنوانك</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات الاستلام من المطبعة -->
                        <div id="pickup-info" class="delivery-option" style="display: none;">
                            <h5 class="section-title">
                                <i class="fas fa-store me-2"></i>
                                معلومات المطبعة
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-box p-3 bg-white rounded">
                                        <p class="mb-1"><strong>العنوان:</strong></p>
                                        <p class="mb-0">دمياط , شارع وزير , بجوار مسجد تقي الدين</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box p-3 bg-white rounded">
                                        <p class="mb-1"><strong>مواعيد العمل:</strong></p>
                                        <p class="mb-0">من السبت للخميس</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box p-3 bg-white rounded">
                                        <p class="mb-1"><strong>هاتف:</strong></p>
                                        <p class="mb-0">201002889688+</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>موقع المطبعة</h6>
                                <div id="map" style="height: 250px; width: 100%; min-width: 200px; border-radius: var(--border-radius-sm);" class="bg-white"></div>
                            </div>
                            <!-- حقول الاستلام من المطبعة -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name_pickup" class="form-label">الاسم الكامل *</label>
                                        <input type="text" id="name_pickup" name="name_pickup" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone_pickup" class="form-label">رقم الهاتف *</label>
                                        <input type="tel" id="phone_pickup" name="phone_pickup" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- حقول التوصيل -->
                        <div id="delivery-fields" class="delivery-option" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name_delivery" class="form-label">الاسم الكامل *</label>
                                        <input type="text" id="name_delivery" name="name_delivery" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone_delivery" class="form-label">رقم الهاتف *</label>
                                        <input type="tel" id="phone_delivery" name="phone_delivery" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">البريد الإلكتروني *</label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city" class="form-label">المحافظة *</label>
                                        <select id="city" name="city" class="form-select">
                                            <option value="">اختر المحافظة</option>
                                            <option value="القاهرة">القاهرة</option>
                                            <option value="الجيزة">الجيزة</option>
                                            <option value="الشرقية">الشرقية</option>
                                            <option value="الدقهلية">الدقهلية</option>
                                            <option value="البحيرة">البحيرة</option>
                                            <option value="الغربية">الغربية</option>
                                            <option value="المنوفية">المنوفية</option>
                                            <option value="القليوبية">القليوبية</option>
                                            <option value="الإسكندرية">الإسكندرية</option>
                                            <option value="بورسعيد">بورسعيد</option>
                                            <option value="دمياط">دمياط</option>
                                            <option value="كفر الشيخ">كفر الشيخ</option>
                                            <option value="المنيا">المنيا</option>
                                            <option value="أسيوط">أسيوط</option>
                                            <option value="سوهاج">سوهاج</option>
                                            <option value="قنا">قنا</option>
                                            <option value="الأقصر">الأقصر</option>
                                            <option value="أسوان">أسوان</option>
                                            <option value="البحر الأحمر">البحر الأحمر</option>
                                            <option value="الوادي الجديد">الوادي الجديد</option>
                                            <option value="مطروح">مطروح</option>
                                            <option value="شمال سيناء">شمال سيناء</option>
                                            <option value="جنوب سيناء">جنوب سيناء</option>
                                            <option value="بني سويف">بني سويف</option>
                                            <option value="الفيوم">الفيوم</option>
                                            <option value="الإسماعيلية">الإسماعيلية</option>
                                            <option value="السويس">السويس</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="address" class="form-label">العنوان التفصيلي *</label>
                                        <textarea id="address" name="address" class="form-control" rows="3" 
                                                  placeholder="الشارع، رقم المبنى، الحي..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="postal_code" class="form-label">الرمز البريدي</label>
                                        <input type="text" id="postal_code" name="postal_code" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method" class="form-label">طريقة الدفع *</label>
                                        <select id="payment_method" name="payment_method" class="form-select">
                                            <option value="">اختر طريقة الدفع</option>
                                            <option value="bank_transfer">تحويل بنكي</option>
                                            <option value="cash_on_delivery">الدفع عند الاستلام</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="notes" class="form-label">ملاحظات إضافية</label>
                                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                                  placeholder="أي ملاحظات خاصة بالطلب..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="order-summary">
                            <h4 class="section-title">
                                <i class="fas fa-shopping-cart me-2"></i>
                                ملخص الطلب
                            </h4>
                            
                            <!-- عناصر الطلب -->
                            <div class="order-items">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="order-item">
                                        <img src="uploads/services/<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>" 
                                             class="order-item-image"
                                             onerror="this.src='assets/images/default-service.jpg'">
                                        <div class="order-item-details">
                                            <h6><?= htmlspecialchars($item['name']) ?></h6>
                                            <small>الكمية: <?= $item['quantity'] ?> × <?= number_format($item['price'], 2) ?> <?= $currency ?></small>
                                            <?php if (!empty($item['paper_type']) || !empty($item['size'])): ?>
                                                <br><small class="text-muted">
                                                    <?= !empty($item['paper_type']) ? 'ورق: ' . $item['paper_type'] : '' ?>
                                                    <?= !empty($item['size']) ? ' | مقاس: ' . $item['size'] : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="order-item-total">
                                            <strong><?= number_format($item['total'], 2) ?> <?= $currency ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- ملخص الأسعار -->
                            <div class="summary-item">
                                <span class="label">المجموع الفرعي:</span>
                                <span class="value"><?= number_format($subtotal, 2) ?> <?= $currency ?></span>
                            </div>
                            <?php if ($points_discount > 0): ?>
                            <div class="summary-item">
                                <span class="label">خصم النقاط:</span>
                                <span class="value text-success">-<?= number_format($points_discount, 2) ?> <?= $currency ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="summary-item shipping-cost" style="display: none;">
                                <span class="label">الشحن:</span>
                                <span class="value"><?= $shipping_cost > 0 ? number_format($shipping_cost, 2) . ' ' . $currency : 'مجاني' ?></span>
                            </div>
                            <hr>
                            <div class="summary-item total">
                                <span class="label">الإجمالي:</span>
                                <span class="value"><?= number_format($total, 2) ?> <?= $currency ?></span>
                            </div>
                            
                            <!-- قسم النقاط التي سيحصل عليها المستخدم -->
                            <?php if (isset($_SESSION['user_id']) && is_points_system_enabled() && $points_to_earn > 0): ?>
                                <div class="points-earn-section">
                                    <div class="points-earn-info">
                                        <i class="fas fa-gift text-warning"></i>
                                        <div>
                                            <div class="points-earn-amount">+<?= number_format($points_to_earn) ?> نقطة</div>
                                            <small>ستحصل عليها بعد إكمال الطلب</small>
                                        </div>
                                    </div>
                                    <div class="points-earn-value">
                                        قيمة النقاط: <?= number_format($points_to_earn * 0.01, 2) ?> جنيه
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 mt-4">
                                <?php if (is_logged_in()): ?>
                                    <button type="submit" name="checkout" class="btn btn-primary btn-lg">
                                        <i class="fas fa-credit-card me-2"></i>
                                        تأكيد الطلب والانتقال للدفع
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.href='login.php'">
                                        <i class="fas fa-lock me-2"></i>
                                        يجب تسجيل الدخول أولاً
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- إضافة مكتبة Leaflet للخريطة -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // الحصول على عناصر النموذج
            const pickupRadio = document.getElementById('pickup');
            const deliveryRadio = document.getElementById('delivery');
            const pickupInfo = document.getElementById('pickup-info');
            const deliveryFields = document.getElementById('delivery-fields');
            const shippingCostElement = document.querySelector('.shipping-cost');
            const totalElement = document.querySelector('.summary-item.total .value');
            const subtotal = <?= $subtotal ?>;
            const shippingCost = <?= $shipping_cost ?>;
            const pointsDiscount = <?= $points_discount ?>;
            const checkoutForm = document.getElementById('checkout-form');
            let map = null;
            let mapInitialized = false;

            function initializeMap() {
                if (mapInitialized) {
                    setTimeout(function() { map.invalidateSize(); }, 200);
                    return;
                }
                map = L.map('map').setView([31.409996144156036, 31.808802982632688], 17);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                L.marker([31.409996144156036, 31.808802982632688]).addTo(map)
                    .bindPopup(`
                        <div style="text-align: right; direction: rtl;">
                            <h5 style="margin: 0 0 10px 0;">مطبعة برنتلي</h5>
                            <p style="margin: 0;">شارع الجلاء، دمياط</p>
                            <p style="margin: 5px 0;">هاتف: 201002889688+</p>
                        </div>
                    `)
                    .openPopup();
                mapInitialized = true;
                setTimeout(function() { map.invalidateSize(); }, 200);
            }

            // دالة لتحديث عرض الحقول
            function updateFields() {
                if (pickupRadio.checked) {
                    pickupInfo.style.display = 'block';
                    deliveryFields.style.display = 'none';
                    shippingCostElement.style.display = 'none';
                    const total = calculateTotal(false);
                    totalElement.textContent = total.toFixed(2) + ' <?= $currency ?>';
                    initializeMap();
                    
                    // تحديث النقاط التي سيحصل عليها المستخدم
                    updatePointsEarned(total);
                    
                    // تحديث الحقول المطلوبة
                    document.getElementById('name_pickup').required = true;
                    document.getElementById('phone_pickup').required = true;
                    document.getElementById('name_delivery').required = false;
                    document.getElementById('phone_delivery').required = false;
                    document.getElementById('email').required = false;
                    document.getElementById('city').required = false;
                    document.getElementById('address').required = false;
                    document.getElementById('payment_method').required = false;
                } else if (deliveryRadio.checked) {
                    pickupInfo.style.display = 'none';
                    deliveryFields.style.display = 'block';
                    shippingCostElement.style.display = 'block';
                    const total = calculateTotal(true);
                    totalElement.textContent = total.toFixed(2) + ' <?= $currency ?>';
                    
                    // تحديث النقاط التي سيحصل عليها المستخدم
                    updatePointsEarned(total);
                    
                    // تحديث الحقول المطلوبة
                    document.getElementById('name_pickup').required = false;
                    document.getElementById('phone_pickup').required = false;
                    document.getElementById('name_delivery').required = true;
                    document.getElementById('phone_delivery').required = true;
                    document.getElementById('email').required = true;
                    document.getElementById('city').required = true;
                    document.getElementById('address').required = true;
                    document.getElementById('payment_method').required = true;
                }
            }

            // دالة لتحديث النقاط التي سيحصل عليها المستخدم
            function updatePointsEarned(totalAmount) {
                const pointsEarnedSection = document.querySelector('.points-earn-section');
                if (pointsEarnedSection) {
                    // حساب النقاط: 5 نقاط لكل 100 جنيه
                    const pointsEarned = Math.floor(totalAmount / 100) * 5;
                    const pointsValue = (pointsEarned * 0.01).toFixed(2);
                    
                    const pointsAmountElement = pointsEarnedSection.querySelector('.points-earn-amount');
                    const pointsValueElement = pointsEarnedSection.querySelector('.points-earn-value');
                    
                    if (pointsAmountElement) {
                        pointsAmountElement.textContent = `+${pointsEarned.toLocaleString()} نقطة`;
                    }
                    if (pointsValueElement) {
                        pointsValueElement.textContent = `قيمة النقاط: ${pointsValue} جنيه`;
                    }
                }
            }

            // إضافة مستمعي الأحداث
            pickupRadio.addEventListener('change', updateFields);
            deliveryRadio.addEventListener('change', updateFields);

            // تحديث الحقول عند تحميل الصفحة
            updateFields();

            // التحقق من اختيار طريقة الاستلام قبل إرسال النموذج
            checkoutForm.addEventListener('submit', function(e) {
                if (!pickupRadio.checked && !deliveryRadio.checked) {
                    e.preventDefault();
                    alert('يرجى اختيار طريقة الاستلام قبل المتابعة');
                    return false;
                }

                // التحقق من الحقول المطلوبة حسب طريقة الاستلام
                if (pickupRadio.checked) {
                    const name = document.getElementById('name_pickup').value.trim();
                    const phone = document.getElementById('phone_pickup').value.trim();

                    if (!name || !phone) {
                        e.preventDefault();
                        if (!name) {
                            alert('يرجى إدخال الاسم الكامل');
                            document.getElementById('name_pickup').focus();
                        } else if (!phone) {
                            alert('يرجى إدخال رقم الهاتف');
                            document.getElementById('phone_pickup').focus();
                        }
                        return false;
                    }
                } else {
                    const requiredFields = ['name_delivery', 'phone_delivery', 'email', 'city', 'address', 'payment_method'];
                    for (const field of requiredFields) {
                        const input = document.getElementById(field);
                        if (!input.value.trim()) {
                            e.preventDefault();
                            let fieldName = '';
                            switch(field) {
                                case 'name_delivery':
                                    fieldName = 'الاسم الكامل';
                                    break;
                                case 'phone_delivery':
                                    fieldName = 'رقم الهاتف';
                                    break;
                                case 'email':
                                    fieldName = 'البريد الإلكتروني';
                                    break;
                                case 'city':
                                    fieldName = 'المحافظة';
                                    break;
                                case 'address':
                                    fieldName = 'العنوان التفصيلي';
                                    break;
                                case 'payment_method':
                                    fieldName = 'طريقة الدفع';
                                    break;
                            }
                            alert(`يرجى ملء حقل "${fieldName}" في معلومات التوصيل`);
                            input.focus();
                            return false;
                        }
                    }
                }
            });

            // دالة لحساب الإجمالي
            function calculateTotal(includeShipping = false) {
                let total = subtotal - pointsDiscount;
                if (includeShipping) {
                    total += shippingCost;
                }
                return Math.max(0, total); // التأكد من أن الإجمالي لا يكون سالباً
            }
        });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>

