<?php
/**
 * صفحة الدفع المحدثة لموقع مطبعة برنتلي
 * 
 * التحديث: تم تعديل الكود لاستخدام المجموع المحفوظ في قاعدة البيانات
 * بدلاً من إعادة حسابه من order_items لضمان دقة البيانات
 * التي تم حسابها في checkout.php
 */

define('PRINTLY', true);

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تفعيل مخزن الإخراج
ob_start();

require_once 'includes/config.php';

// التحقق من وجود منتجات في السلة
try {
    $cart_stmt = $db->prepare("
        SELECT COUNT(*) as cart_count
        FROM cart 
        WHERE (user_id = ? OR session_id = ?)
    ");
    $cart_stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0, session_id()]);
    $cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['cart_count'];
    
    if ($cart_count == 0 && !isset($_SESSION['order_id'])) {
        header('Location: cart.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Cart Check Error: ' . $e->getMessage());
}

$error = null;
$order = null;
$debug_info = array();

// التحقق من وجود معرف الطلب في الجلسة
if (!isset($_SESSION['order_id']) || !isset($_SESSION['order_number'])) {
    $debug_info['session'] = $_SESSION;
    
    // محاولة استرداد آخر طلب للمستخدم
    if (isset($_SESSION['user_id'])) {
        try {
            // أولاً، نتحقق من وجود منتجات في السلة مع معلومات الخدمة والسعر
            $cart_stmt = $db->prepare("
                SELECT c.*, s.name as service_name, s.price_start,
                       COUNT(*) as cart_count,
                       SUM(c.quantity) as total_quantity,
                       SUM(c.price) as total_price
                FROM cart c
                LEFT JOIN services s ON c.service_id = s.service_id
                WHERE (c.user_id = ? OR c.session_id = ?)
                AND s.status = 'active'
                GROUP BY c.service_id
            ");
            $cart_stmt->execute([$_SESSION['user_id'], session_id()]);
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($cart_items)) {
                $cart_summary = [];
                $total_price = 0;
                foreach ($cart_items as $item) {
                    $cart_summary[] = $item['service_name'] . ' (الكمية: ' . $item['quantity'] . ')';
                    $total_price += $item['price'];
                }
                
                // إنشاء طلب جديد من منتجات السلة
                try {
                    $db->beginTransaction();
                    
                    // إنشاء رقم الطلب
                    $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
                    
                    // التحقق من عدم تكرار رقم الطلب
                    $check_number = $db->prepare("SELECT COUNT(*) FROM orders WHERE order_number = ?");
                    $check_number->execute([$order_number]);
                    $attempts = 0;
                    while ($check_number->fetchColumn() > 0 && $attempts < 10) {
                        $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
                        $check_number->execute([$order_number]);
                        $attempts++;
                    }
                    
                    // إنشاء الطلب
                    $order_stmt = $db->prepare("
                        INSERT INTO orders (
                            user_id, order_number, total_amount, status, 
                            payment_status, payment_method, created_at, updated_at
                        ) VALUES (?, ?, ?, 'new', 'pending', 'bank_transfer', NOW(), NOW())
                    ");
                    $order_stmt->execute([$_SESSION['user_id'], $order_number, $total_price]);
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
                    
                    // نقل منتجات السلة إلى تفاصيل الطلب
                    $items_created = 0;
                    foreach ($cart_items as $item) {
                        // التحقق من وجود الخدمة قبل إضافة عنصر الطلب
                        $check_service = $db->prepare("SELECT service_id FROM services WHERE service_id = ? AND status = 'active'");
                        $check_service->execute([$item['service_id']]);
                        if (!$check_service->fetch()) {
                            throw new Exception('الخدمة غير موجودة أو غير نشطة: ' . $item['service_id']);
                        }
                        
                        $order_item_stmt = $db->prepare("
                            INSERT INTO order_items (
                                order_id, service_id, quantity, paper_type, 
                                size, colors, notes, price
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $order_item_stmt->execute([
                            $order_id, $item['service_id'], $item['quantity'],
                            $item['paper_type'], $item['size'], $item['colors'],
                            $item['notes'], $item['price']
                        ]);
                        
                        $item_id = $db->lastInsertId();
                        if (!$item_id) {
                            throw new Exception('فشل في إنشاء عنصر الطلب للخدمة: ' . $item['service_id']);
                        }
                        
                        // نقل ملفات التصميم إذا وجدت
                        if (!empty($item['design_file'])) {
                            $file_stmt = $db->prepare("
                                INSERT INTO order_item_files (
                                    item_id, file_name, uploaded_at, is_payment_proof
                                ) VALUES (?, ?, NOW(), 0)
                            ");
                            $file_stmt->execute([$item_id, $item['design_file']]);
                            
                            $file_id = $db->lastInsertId();
                            if (!$file_id) {
                                throw new Exception('فشل في حفظ ملف التصميم لعنصر الطلب: ' . $item_id);
                            }
                        }
                        
                        $items_created++;
                    }
                    
                    // التحقق من أن جميع عناصر الطلب تم إنشاؤها بنجاح
                    if ($items_created === 0) {
                        throw new Exception('لم يتم إنشاء أي عناصر للطلب');
                    }
                    
                    // التحقق النهائي من صحة البيانات
                    $final_check = $db->prepare("
                        SELECT COUNT(*) as items_count 
                        FROM order_items 
                        WHERE order_id = ?
                    ");
                    $final_check->execute([$order_id]);
                    $final_items_count = $final_check->fetchColumn();
                    
                    if ($final_items_count !== $items_created) {
                        throw new Exception('عدم تطابق عدد عناصر الطلب - المتوقع: ' . $items_created . ', الفعلي: ' . $final_items_count);
                    }
                    
                    // مسح السلة
                    $clear_cart_stmt = $db->prepare("
                        DELETE FROM cart 
                        WHERE user_id = ? OR session_id = ?
                    ");
                    $clear_cart_stmt->execute([$_SESSION['user_id'], session_id()]);
                    
                    $db->commit();
                    
                    // تحديث الجلسة
                    $_SESSION['order_id'] = $order_id;
                    $_SESSION['order_number'] = $order_number;
                    
                    // إعادة توجيه المستخدم لصفحة الدفع
                    header('Location: payment.php');
                    exit;
                    
                } catch (PDOException $e) {
                    $db->rollBack();
                    error_log('Payment Order Creation Error: ' . $e->getMessage() . ' - User ID: ' . $_SESSION['user_id'] . ' - Order Number: ' . ($order_number ?? 'unknown'));
                    $error = 'حدث خطأ أثناء إنشاء الطلب. يرجى المحاولة مرة أخرى أو التواصل مع الدعم الفني.';
                    $debug_info['error'] = $e->getMessage();
                    
                    // إذا كان الخطأ يتعلق بقاعدة البيانات، أضف رسالة إضافية
                    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                        $error .= ' قد تكون هناك مشكلة في قاعدة البيانات. يرجى التواصل مع الدعم الفني.';
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log('Payment Order Creation Error: ' . $e->getMessage() . ' - User ID: ' . $_SESSION['user_id'] . ' - Order Number: ' . ($order_number ?? 'unknown'));
                    $error = 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage();
                    $debug_info['error'] = $e->getMessage();
                }
            } else {
                // البحث عن الطلبات المعلقة
                $stmt = $db->prepare("
                    SELECT o.*, u.email as user_email, u.phone as user_phone 
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.user_id 
                    WHERE o.user_id = ? 
                    AND o.payment_status = 'pending'
                    AND o.status != 'cancelled'
                    AND o.status != 'trash'
                    ORDER BY o.created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $last_order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($last_order) {
                    $_SESSION['order_id'] = $last_order['order_id'];
                    $_SESSION['order_number'] = $last_order['order_number'];
                    $debug_info['last_order'] = $last_order;
                    $error = 'تم استرداد آخر طلب معلق. يرجى متابعة عملية الدفع.';
                } else {
                    $error = 'لم يتم العثور على أي طلبات معلقة. يرجى <a href="services.php" class="alert-link">تصفح خدماتنا</a> و<a href="cart.php" class="alert-link">إنشاء طلب جديد</a>.';
                }
            }
        } catch (PDOException $e) {
            error_log('Payment Error: ' . $e->getMessage() . ' - User ID: ' . $_SESSION['user_id']);
            $error = 'حدث خطأ أثناء البحث عن الطلبات. يرجى المحاولة مرة أخرى أو التواصل مع الدعم الفني.';
            $debug_info['error'] = $e->getMessage();
        }
    } else {
        $error = 'يرجى <a href="login.php" class="alert-link">تسجيل الدخول</a> أولاً للوصول إلى صفحة الدفع.';
    }
} else {
    $order_id = $_SESSION['order_id'];
    $order_number = $_SESSION['order_number'];
    $debug_info['order_id'] = $order_id;
    $debug_info['order_number'] = $order_number;

    // تحديد مسار حفظ إثبات الدفع
    if (!defined('DESIGNS_PATH')) {
        define('DESIGNS_PATH', __DIR__ . '/uploads/designs/');
    }

    // إنشاء مجلد التصميمات إذا لم يكن موجوداً
    if (!file_exists(DESIGNS_PATH)) {
        mkdir(DESIGNS_PATH, 0777, true);
    }

    // تضمين ملف الهيدر بعد كل عمليات إعادة التوجيه المحتملة
    require_once 'includes/header.php';

    // جلب بيانات الطلب
    try {
        // أولاً، نتحقق من وجود الطلب
        $stmt = $db->prepare("
            SELECT o.*, u.email as user_email, u.phone as user_phone
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id 
            WHERE o.order_id = ? AND o.order_number = ?
        ");
        $stmt->execute([$order_id, $order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            // إذا لم يتم العثور على الطلب، نتحقق من سبب الخطأ
            $stmt = $db->prepare("SELECT order_id FROM orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order_exists = $stmt->fetch();
            
            if (!$order_exists) {
                $error = 'لم يتم العثور على الطلب في قاعدة البيانات. معرف الطلب: ' . $order_id;
            } else {
                // تحويل رقم الطلب إلى التنسيق الصحيح
                if (preg_match('/^ORD-(\d{8})-(\d+)-(\d+)$/', $order_number, $matches)) {
                    $date = $matches[1];
                    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $new_order_number = 'ORD-' . $date . '-' . $random;
                    
                    try {
                        // تحديث رقم الطلب في قاعدة البيانات
                        $update_stmt = $db->prepare("UPDATE orders SET order_number = ? WHERE order_id = ?");
                        $update_stmt->execute([$new_order_number, $order_id]);
                        
                        // تحديث رقم الطلب في الجلسة
                        $_SESSION['order_number'] = $new_order_number;
                        
                        // إعادة تحميل الصفحة مع رقم الطلب الجديد
                        header('Location: payment.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'حدث خطأ أثناء تحديث رقم الطلب. يرجى المحاولة مرة أخرى.';
                    }
                } else {
                    // التحقق من وجود رقم الطلب في قاعدة البيانات
                    $stmt = $db->prepare("SELECT order_number FROM orders WHERE order_number = ?");
                    $stmt->execute([$order_number]);
                    $number_exists = $stmt->fetch();
                    
                    if (!$number_exists) {
                        $error = 'رقم الطلب غير موجود في قاعدة البيانات. يرجى المحاولة مرة أخرى.';
                    } else {
                        $error = 'لم يتم العثور على الطلب بالمعايير المحددة. معرف الطلب: ' . $order_id . ', رقم الطلب: ' . $order_number;
                    }
                }
            }
        } else if ($order['payment_status'] !== 'pending') {
            $error = 'تم الدفع مسبقاً لهذا الطلب. حالة الدفع الحالية: ' . $order['payment_status'];
        } else if (is_logged_in() && $order['user_id'] != $_SESSION['user_id']) {
            $error = 'ليس لديك صلاحية للوصول إلى هذا الطلب. معرف المستخدم الحالي: ' . $_SESSION['user_id'] . ', معرف مستخدم الطلب: ' . $order['user_id'];
        } else {
            // استخدام المجموع المحفوظ في قاعدة البيانات بدلاً من إعادة حسابه
            $total = $order['total_amount'];
            
            // جلب عناصر الطلب للعرض فقط (بدون إعادة حساب المجموع)
            $items_stmt = $db->prepare("
                SELECT oi.*, s.name as service_name 
                FROM order_items oi 
                JOIN services s ON oi.service_id = s.service_id 
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$order_id]);
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // التحقق من وجود عناصر في الطلب
            if (empty($order_items)) {
                $error = 'لا توجد عناصر في هذا الطلب. يرجى التواصل مع الدعم الفني.';
            } else {
                // حساب المجموع الفرعي من عناصر الطلب للعرض فقط
                $subtotal = 0;
                foreach ($order_items as $item) {
                    // استخدام السعر المحفوظ في order_items (السعر الإجمالي للعنصر)
                    $subtotal += $item['price'];
                }
                
                // حساب الخصم (إذا كان هناك)
                $discount = $subtotal - $total;
                if ($discount < 0) {
                    $discount = 0;
                }
                
                // التأكد من أن المجموع صحيح
                if ($total <= 0) {
                    $total = $subtotal;
                }
                
                // تحديث المتغيرات للعرض
                $order['total_amount'] = $total;
            }
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ أثناء جلب بيانات الطلب: ' . $e->getMessage() . '. معرف الطلب: ' . $order_id . ', رقم الطلب: ' . $order_number;
    }
}

// ملاحظة: تم تعديل الكود لاستخدام المجموع المحفوظ في قاعدة البيانات
// بدلاً من إعادة حسابه من order_items لضمان دقة البيانات
// التي تم حسابها في checkout.php مع مراعاة النقاط والخصومات

// إذا تم إرسال نموذج الدفع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];
    $design_file_uploaded = false;
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
    $max_size = 15 * 1024 * 1024; // 15 MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_type = $file['type'];
        $file_size = $file['size'];

        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= $max_size) {
                $new_file_name = 'payment_' . $order_id . '_' . uniqid() . '_' . basename($file['name']);
                $destination = DESIGNS_PATH . $new_file_name;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    try {
                        // تحديث حالة الدفع
                        $stmt = $db->prepare("
                            UPDATE orders 
                            SET 
                                payment_status = 'pending',
                                payment_date = NOW(),
                                payment_proof = ?,
                                notes = CONCAT(COALESCE(notes, ''), '\nتم رفع إثبات الدفع')
                            WHERE order_id = ? AND order_number = ?
                        ");
                        
                        $stmt->execute([$new_file_name, $order_id, $order_number]);
                        
                        // مسح عربة التسوق بعد نجاح الدفع
                        $clear_cart_stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? OR session_id = ?");
                        $clear_cart_stmt->execute([$_SESSION['user_id'], session_id()]);
                        
                        // حفظ رقم الطلب في الجلسة قبل إعادة التوجيه
                        $_SESSION['order_number'] = $order_number;
                        
                        // إعادة التوجيه إلى صفحة نجاح الطلب
                        redirect('order-confirmation.php?payment_success=1');
                    } catch (PDOException $e) {
                        $error = 'حدث خطأ أثناء تحديث حالة الدفع: ' . $e->getMessage() . '. معرف الطلب: ' . $order_id . ', رقم الطلب: ' . $order_number;
                    }
                } else {
                    $error = 'حدث خطأ أثناء رفع الملف. يرجى المحاولة مرة أخرى. نوع الملف: ' . $file_type . ', حجم الملف: ' . $file_size;
                }
            } else {
                $error = 'حجم الملف كبير جداً. الحد الأقصى المسموح به هو 15 ميجابايت. حجم الملف الحالي: ' . round($file_size / (1024 * 1024), 2) . ' ميجابايت';
            }
        } else {
            $error = 'نوع الملف غير مدعوم. الأنواع المدعومة هي: JPG, PNG, GIF, PDF. نوع الملف الحالي: ' . $file_type;
        }
    } else {
        $upload_errors = array(
            UPLOAD_ERR_INI_SIZE => 'حجم الملف يتجاوز الحد المسموح به في إعدادات PHP',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف يتجاوز الحد المسموح به في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد التخزين المؤقت غير موجود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
            UPLOAD_ERR_EXTENSION => 'تم إيقاف رفع الملف بواسطة إضافة PHP'
        );
        $error = isset($upload_errors[$file['error']]) ? $upload_errors[$file['error']] : 'حدث خطأ غير معروف أثناء رفع الملف';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدفع - مطبعة برنتلي</title>
    
    <style>
        :root {
            --primary-color: #00adef;
            --primary-hover-color: #c4a130;
            --secondary-color: #343a40;
            --light-gray-color: #f8f9fa;
            --text-color: #333;
            --border-color: #dee2e6;
            --border-radius: 0.75rem;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* تنسيق الـ header الثابت */
        header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            background-color: #f9f9f9;
            font-family: 'Cairo', sans-serif;
            padding-top: 120px !important; /* ارتفاع ثابت للـ header */
            min-height: 100vh;
            overflow-x: hidden;
        }

        .payment-section {
            padding: 3rem 0;
            position: relative;
            z-index: 1;
        }

        /* تنسيق المحتوى الرئيسي */
        .main-content {
            position: relative;
            z-index: 1;
            background: #f9f9f9;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 2px solid var(--border-color);
            padding: 1.5rem 2rem;
        }

        .card-body {
            padding: 2rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .payment-method {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .payment-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .method-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .method-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-gray-color);
            border-radius: 50%;
            margin-left: 1rem;
        }

        .method-icon i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }

        .method-title {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .phone-display {
            display: flex;
            align-items: center;
            background: var(--light-gray-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            gap: 1rem;
            border: 1px solid var(--border-color);
        }

        .phone-input {
            border: none;
            background: transparent;
            flex: 1;
            padding: 0.5rem;
            font-size: 1.2rem;
            color: var(--text-color);
            font-weight: 600;
            font-family: monospace;
        }

        .copy-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .copy-btn:hover {
            background: var(--primary-hover-color);
            transform: translateY(-2px);
        }

        .instapay-btn {
            display: inline-flex;
            align-items: center;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            margin-top: 1rem;
            transition: all 0.3s ease;
            font-weight: 600;
            gap: 0.75rem;
            width: 100%;
            justify-content: center;
        }

        .instapay-btn:hover {
            background: var(--primary-hover-color);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding: 1rem 1.25rem;
            background: var(--light-gray-color);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .info-label {
            color: #666;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }

        .order-number {
            background: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-family: monospace;
            letter-spacing: 1px;
            font-size: 1.1rem;
        }

        .copy-number-btn {
            background: transparent;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 1.1rem;
        }

        .copy-number-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .total-row {
            border-top: 2px solid var(--primary-color);
            padding-top: 1.25rem;
            margin-top: 1.25rem;
            background: #fff !important;
        }

        .total-amount {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .badge-warning {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .form-control {
            border-radius: var(--border-radius);
            border: 2px dashed var(--primary-color);
            padding: 1.25rem;
            background: var(--light-gray-color);
            font-size: 1.1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 173, 239, 0.25);
        }

        .form-help {
            color: #666;
            margin-top: 0.75rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 1rem 2.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-primary:hover {
            background: var(--primary-hover-color);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .error-message {
            background-color: #fff3f3;
            color: #dc3545;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ffcdd2;
            border-radius: var(--border-radius);
            text-align: center;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .debug-info {
            background-color: #f8f9fa;
            padding: 1.25rem;
            margin: 1.5rem 0;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }

        .main-flex-layout {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: center;
        }
        .order-payment-flex {
            display: flex;
            flex-direction: row;
            gap: 2.5rem;
            align-items: flex-start;
            width: 100%;
        }
        .order-summary-card {
            flex: 1 1 340px;
            min-width: 320px;
            max-width: 400px;
            margin-bottom: 0;
        }
        .payment-methods-card {
            flex: 2 1 0;
            min-width: 0;
            width: 100%;
        }
        .confirm-payment-card {
            margin-top: 2rem;
        }
        @media (max-width: 1200px) {
            .order-payment-flex {
                gap: 1.5rem;
            }
        }
        @media (max-width: 992px) {
            .order-payment-flex {
                flex-direction: column;
                gap: 2rem;
            }
            .order-summary-card, .payment-methods-card {
                max-width: 100%;
                min-width: 0;
                width: 100%;
            }
        }
        @media (max-width: 768px) {
            .payment-section {
                padding: 1.2rem 0 0 0;
            }
            .main-flex-layout {
                padding: 0 0.5rem;
            }
            .order-payment-flex {
                flex-direction: column;
                gap: 1.1rem;
            }
            .order-summary-card, .payment-methods-card {
                max-width: 100%;
                min-width: 0;
                width: 100%;
                margin-bottom: 1rem;
            }
            .card-header, .card-body {
                padding: 1rem 0.7rem;
            }
            .info-row, .total-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.3rem;
                padding: 0.7rem 0.7rem;
                font-size: 1rem;
            }
            .order-number {
                font-size: 1rem;
                padding: 0.3rem 0.7rem;
            }
            .copy-number-btn, .copy-btn {
                font-size: 1rem;
                padding: 0.4rem 0.7rem;
            }
            .payment-methods {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin: 1rem 0;
            }
            .payment-method {
                padding: 1.1rem 0.7rem;
            }
            .method-header {
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
            }
            .method-title {
                font-size: 1.1rem;
            }
            .phone-display {
                padding: 0.5rem 0.7rem;
                gap: 0.5rem;
            }
            .phone-input {
                font-size: 1rem;
            }
            .instapay-btn {
                padding: 0.7rem 1rem;
                font-size: 1rem;
                border-radius: 8px;
            }
            .confirm-payment-card .form-group {
                max-width: 100%;
                padding: 0 0.2rem;
            }
            .form-label, .form-help {
                font-size: 1rem;
            }
            .form-control {
                padding: 0.7rem 1rem;
                font-size: 1rem;
            }
            .btn-primary {
                padding: 0.7rem 1.2rem;
                font-size: 1.05rem;
                border-radius: 10px;
            }
            .error-message, .alert {
                font-size: 1rem;
                padding: 0.7rem 0.7rem;
            }
            .debug-info {
                font-size: 0.85rem;
                padding: 0.7rem 0.7rem;
            }
        }
        @media (max-width: 480px) {
            .main-flex-layout {
                padding: 0 2px;
            }
            .order-summary-card, .payment-methods-card {
                margin-bottom: 0.5rem;
            }
            .card-header, .card-body {
                padding: 0.7rem 0.3rem;
            }
            .info-row, .total-row {
                padding: 0.5rem 0.3rem;
                font-size: 0.95rem;
            }
            .order-number {
                font-size: 0.95rem;
                padding: 0.2rem 0.5rem;
            }
            .copy-number-btn, .copy-btn {
                font-size: 0.95rem;
                padding: 0.3rem 0.5rem;
            }
            .payment-method {
                padding: 0.7rem 0.3rem;
            }
            .method-title {
                font-size: 1rem;
            }
            .phone-display {
                padding: 0.3rem 0.3rem;
                gap: 0.3rem;
            }
            .phone-input {
                font-size: 0.95rem;
            }
            .instapay-btn {
                padding: 0.5rem 0.7rem;
                font-size: 0.95rem;
                border-radius: 7px;
            }
            .form-label, .form-help {
                font-size: 0.95rem;
            }
            .form-control {
                padding: 0.5rem 0.7rem;
                font-size: 0.95rem;
            }
            .btn-primary {
                padding: 0.5rem 0.8rem;
                font-size: 0.95rem;
                border-radius: 8px;
            }
            .error-message, .alert {
                font-size: 0.95rem;
                padding: 0.5rem 0.3rem;
            }
            .debug-info {
                font-size: 0.8rem;
                padding: 0.5rem 0.3rem;
            }
            .confirm-payment-card input[type="file"] {
                min-width: 120px;
                max-width: 100%;
            }
        }
        @media (min-width: 769px) {
            .confirm-payment-card .card-body form {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 100%;
                max-width: 420px;
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>

<body>
    <section class="payment-section">
        <div class="container">
            <div class="main-flex-layout">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php if (!empty($debug_info)): ?>
                        <div class="debug-info">
                            <strong>معلومات التشخيص:</strong>
                            <?php echo json_encode($debug_info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
                        </div>
                    <?php endif; ?>
                    <div class="text-center mt-4">
                        <?php if (isset($cart_items) && !empty($cart_items)): ?>
                            <a href="cart.php" class="btn btn-primary me-2">
                                <i class="fas fa-shopping-cart me-2"></i>
                                العودة إلى السلة (<?php echo count($cart_items); ?> منتج)
                            </a>
                        <?php endif; ?>
                        <a href="services.php" class="btn btn-secondary">
                            <i class="fas fa-list me-2"></i>
                            تصفح الخدمات
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!$error && $order): ?>
                    <div class="order-payment-flex">
                        <!-- ملخص الطلب -->
                        <div class="order-summary-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-receipt me-2"></i>
                                        تفاصيل الطلب
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">رقم الطلب:</span>
                                        <div class="info-value">
                                            <span class="order-number"><?php echo $order['order_number']; ?></span>
                                            <button type="button" class="copy-number-btn" onclick="copyToClipboard('<?php echo $order['order_number']; ?>')" title="نسخ الرقم">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">حالة الطلب:</span>
                                        <span class="badge badge-warning">في انتظار الدفع</span>
                                    </div>
                                    <?php if ($discount > 0): ?>
                                    <div class="info-row">
                                        <span class="info-label">الخصم:</span>
                                        <span class="info-value text-success">-<?php echo number_format($discount, 2); ?> جنيه</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="info-row total-row">
                                        <span class="info-label">الإجمالي:</span>
                                        <span class="info-value total-amount"><?php echo number_format($total, 2); ?> جنيه</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- طرق الدفع -->
                        <div class="payment-methods-card">
                            <h3 class="mb-3">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                طرق الدفع المتاحة
                            </h3>
                            <div class="payment-methods">
                                <!-- فودافون كاش -->
                                <div class="payment-method">
                                    <div class="method-header">
                                        <div class="method-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <h4 class="method-title">فودافون كاش</h4>
                                    </div>
                                    <div class="method-body">
                                        <p class="method-label">رقم المحفظة:</p>
                                        <div class="phone-display">
                                            <input type="text" value="01002889688" class="phone-input" readonly>
                                            <button type="button" class="copy-btn" onclick="copyToClipboard('01002889688')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- انستا باي -->
                                <div class="payment-method">
                                    <div class="method-header">
                                        <div class="method-icon">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <h4 class="method-title">InstaPay</h4>
                                    </div>
                                    <div class="method-body">
                                        <p class="method-label">الدفع المباشر عبر الرابط:</p>
                                        <a href="https://ipn.eg/S/zeyadkamelali/instapay/7XaLOl" class="instapay-btn" target="_blank">
                                            <i class="fas fa-external-link-alt me-2"></i>
                                            الدفع عبر InstaPay
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- قسم تأكيد الدفع -->
                            <div class="card confirm-payment-card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-check-circle me-2"></i>
                                        تأكيد الدفع
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form action="payment.php" method="post" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="payment_proof" class="form-label">
                                                صورة إثبات الدفع <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" 
                                                   name="payment_proof" 
                                                   id="payment_proof" 
                                                   class="form-control" 
                                                   accept=".jpg,.jpeg,.png,.gif,.pdf"
                                                   required>
                                            <div class="form-help">
                                                <i class="fas fa-info-circle me-1"></i>
                                                يرجى رفع صورة لإيصال التحويل (JPG, PNG, PDF). الحد الأقصى: 15 ميجابايت
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-check-circle me-2"></i>
                                                تأكيد الدفع وإرسال الطلب
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('تم نسخ الرقم بنجاح!');
                }).catch(err => {
                    console.error('فشل في نسخ النص: ', err);
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('تم نسخ الرقم بنجاح!');
                } else {
                    alert('فشل في نسخ الرقم');
                }
            } catch (err) {
                console.error('فشل في نسخ النص: ', err);
                alert('فشل في نسخ الرقم');
            }
            
            document.body.removeChild(textArea);
        }

        // إضافة التحقق من حجم الملف
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const fileInput = document.getElementById('payment_proof');
            const maxSize = 15 * 1024 * 1024; // 15 MB

            form.addEventListener('submit', function(e) {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    if (file.size > maxSize) {
                        e.preventDefault();
                        alert('حجم الملف كبير جداً. الحد الأقصى المسموح به هو 15 ميجابايت');
                        fileInput.value = ''; // مسح الملف المحدد
                    }
                }
            });

        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>


