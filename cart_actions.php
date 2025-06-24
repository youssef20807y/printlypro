<?php
/**
 * ملف معالجة إجراءات عربة التسوق لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الإعدادات
require_once 'includes/config.php';
require_once 'includes/points_functions.php';

// تأكد من أن الطلب من AJAX
header('Content-Type: application/json');

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من وجود مصفوفة السلة في الجلسة
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// التحقق من وجود الإجراء المطلوب
if (!isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'لم يتم تحديد الإجراء المطلوب'
    ]);
    exit;
}

$action = $_POST['action'];

// الاتصال بقاعدة البيانات
try {
    $db = db_connect();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الاتصال بقاعدة البيانات'
    ]);
    exit;
}

switch ($action) {
    case 'add':
    try {
        // التحقق من وجود معرف المنتج
        if (!isset($_POST['product_id'])) {
            throw new Exception('لم يتم تحديد المنتج');
        }
        
        $product_id = (int)$_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        $options = isset($_POST['options']) ? $_POST['options'] : [];
        
        // الحصول على بيانات المنتج من قاعدة البيانات
        $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('المنتج غير موجود أو غير متاح');
        }
        
        // التحقق من توفر المنتج في المخزون
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('الكمية المطلوبة غير متوفرة في المخزون');
        }
        
        // تحديد السعر (استخدام سعر العرض إذا كان متاحًا)
        $price = $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'];
        
        // إضافة المنتج إلى السلة أو تحديث الكمية إذا كان موجودًا بالفعل
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'image' => $product['image'],
                'options' => $options
            ];
        }
        
        // إرجاع النتيجة
        echo json_encode([
            'success' => true,
            'message' => 'تمت إضافة المنتج إلى السلة بنجاح',
            'cart_count' => count($_SESSION['cart'])
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    } catch (PDOException $e) {
        // في حالة عدم وجود جدول المنتجات، نستخدم بيانات افتراضية للتجربة
        try {
            $product_id = (int)$_POST['product_id'];
            $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
            $options = isset($_POST['options']) ? $_POST['options'] : [];
            
            // إضافة المنتج إلى السلة أو تحديث الكمية إذا كان موجودًا بالفعل
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                // بيانات افتراضية للمنتج
                $_SESSION['cart'][$product_id] = [
                    'product_id' => $product_id,
                    'name' => 'منتج رقم ' . $product_id,
                    'price' => 100, // سعر افتراضي
                    'quantity' => $quantity,
                    'image' => null,
                    'options' => $options
                ];
            }
            
            // إرجاع النتيجة
            echo json_encode([
                'success' => true,
                'message' => 'تمت إضافة المنتج إلى السلة بنجاح',
                'cart_count' => count($_SESSION['cart'])
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
        break;
        
    case 'update':
        if (isset($_POST['cart_id']) && isset($_POST['quantity'])) {
            $cart_id = (int)$_POST['cart_id'];
        $quantity = max(1, (int)$_POST['quantity']);
        
            try {
                // جلب سعر المنتج
                $stmt = $db->prepare("
                    SELECT s.price_start 
                    FROM cart c 
                    JOIN services s ON c.service_id = s.service_id 
                    WHERE c.cart_id = ?
                ");
                $stmt->execute([$cart_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($item) {
                    // حساب السعر الجديد
                    $new_price = $item['price_start'] * $quantity;
                    
                    // تحديث الكمية والسعر في السلة
                    $stmt = $db->prepare("
                        UPDATE cart 
                        SET quantity = ?, 
                            price = ?, 
                            updated_at = NOW() 
                        WHERE cart_id = ? AND (user_id = ? OR session_id = ?)
                    ");
                    $stmt->execute([
                        $quantity, 
                        $new_price, 
                        $cart_id, 
                        $_SESSION['user_id'] ?? null, 
                        session_id()
                    ]);
                    
        echo json_encode([
            'success' => true,
                        'message' => 'تم تحديث السلة بنجاح'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'لم يتم العثور على المنتج'
                    ]);
                }
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث السلة'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'البيانات المطلوبة غير مكتملة'
            ]);
        }
        break;
        
    case 'remove':
        if (isset($_POST['cart_id'])) {
            $cart_id = (int)$_POST['cart_id'];
            
            try {
                $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND (user_id = ? OR session_id = ?)");
                $stmt->execute([$cart_id, $_SESSION['user_id'] ?? null, session_id()]);
                
        echo json_encode([
            'success' => true,
                    'message' => 'تم حذف المنتج من السلة'
        ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف المنتج'
                ]);
    }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'لم يتم تحديد المنتج المراد حذفه'
            ]);
        }
        break;
        
    case 'clear':
    try {
        // إفراغ السلة
        $_SESSION['cart'] = [];
        
        // إرجاع النتيجة
        echo json_encode([
            'success' => true,
            'message' => 'تم إفراغ السلة بنجاح',
            'cart_count' => 0
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
        break;

    case 'get_count':
    try {
        // إرجاع عدد العناصر في السلة
        echo json_encode([
            'success' => true,
            'cart_count' => isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'إجراء غير معروف'
        ]);
        break;
}


        $_SESSION['cart'] = [];
        
        // إرجاع النتيجة
        echo json_encode([
            'success' => true,
            'message' => 'تم إفراغ السلة بنجاح',
            'cart_count' => 0
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
        break;

    case 'get_count':
    try {
        // إرجاع عدد العناصر في السلة
        echo json_encode([
            'success' => true,
            'cart_count' => isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'إجراء غير معروف'
        ]);
        break;
}

