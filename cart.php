<?php
/**
 * صفحة عربة التسوق المحدثة لموقع مطبعة برنتلي
 */

define('PRINTLY', true);
require_once 'includes/header.php';
require_once 'includes/points_functions.php'; // إضافة دوال النقاط

// معالجة الإجراءات
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
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
                        
                        $success_message = 'تم تحديث عربة التسوق بنجاح';
                    }
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ أثناء تحديث السلة';
                }
            }
            break;
            
        case 'remove':
            if (isset($_POST['cart_id'])) {
                $cart_id = (int)$_POST['cart_id'];
                
                try {
                    $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND (user_id = ? OR session_id = ?)");
                    $stmt->execute([$cart_id, $_SESSION['user_id'] ?? null, session_id()]);
                    $success_message = 'تم حذف المنتج من عربة التسوق';
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ أثناء حذف المنتج';
                }
            }
            break;
            
        case 'clear':
            try {
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? OR session_id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? null, session_id()]);
                $success_message = 'تم إفراغ عربة التسوق';
            } catch (PDOException $e) {
                $error_message = 'حدث خطأ أثناء إفراغ السلة';
            }
            break;
            
        case 'apply_points_discount':
            if (isset($_SESSION['user_id']) && isset($_POST['points_used'])) {
                $points_used = (int)$_POST['points_used'];
                
                try {
                    // التحقق من رصيد النقاط
                    $points_data = get_user_points($_SESSION['user_id']);
                    if ($points_data && $points_data['balance'] >= $points_used) {
                        // حفظ النقاط المستخدمة في الجلسة
                        $_SESSION['points_used'] = $points_used;
                        $success_message = 'تم تطبيق خصم النقاط بنجاح';
                    } else {
                        $error_message = 'النقاط المطلوبة غير متوفرة في حسابك';
                    }
                } catch (Exception $e) {
                    $error_message = 'حدث خطأ أثناء تطبيق خصم النقاط';
                }
            }
            break;
            
        case 'reset_points_discount':
            if (isset($_SESSION['user_id'])) {
                unset($_SESSION['points_used']);
                $success_message = 'تم إعادة تعيين خصم النقاط';
            }
            break;
    }
}

// جلب محتويات السلة من قاعدة البيانات
$cart_items = [];
$subtotal = 0;
$currency = 'جنيه';

try {
    $cols = $db->query("SHOW COLUMNS FROM cart LIKE 'custom_fields_values'")->fetch();
    if (!$cols) {
        // ملاحظة: يجب إضافة العمود يدوياً في قاعدة البيانات
        echo '<div style="color:red;font-weight:bold">⚠️ يجب إضافة عمود custom_fields_values من نوع TEXT إلى جدول cart في قاعدة البيانات ليعمل النظام بشكل صحيح مع الحقول المخصصة.</div>';
    }

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
            'cart_id' => $item['cart_id'],
            'service_id' => $item['service_id'],
            'name' => $item['service_name'],
            'price' => $item['price_start'],
            'quantity' => $item['quantity'],
            'image' => $item['image'],
            'paper_type' => $item['paper_type'],
            'size' => $item['size'],
            'colors' => $item['colors'],
            'notes' => $item['notes'],
            'design_file' => $item['design_file'],
            'total' => $item_total
        ];
        // أضف القيم المخصصة للحقول
        foreach ($custom_fields_values as $field_id => $field_value) {
            $cart_item['custom_field_' . $field_id] = $field_value;
        }
        $cart_items[] = $cart_item;
    }
} catch (PDOException $e) {
    $error_message = 'حدث خطأ أثناء جلب محتويات السلة';
}

// جلب رصيد النقاط للمستخدم
$user_points = 0;
$points_discount = 0;
$points_used = 0;
$points_to_earn = 0; // النقاط التي سيحصل عليها المستخدم

if (isset($_SESSION['user_id']) && is_points_system_enabled()) {
    $points_data = get_user_points($_SESSION['user_id']);
    if ($points_data) {
        $user_points = $points_data['balance'];
        
        // حساب النقاط التي يمكن استخدامها
        $max_points_discount = $subtotal * 0.10; // خصم أقصى 10% من المجموع الفرعي
        $max_points_usage = floor($max_points_discount / 0.01); // 1 نقطة = 0.01 جنيه
        $max_points_usage = min($max_points_usage, $user_points); // لا يتجاوز رصيد المستخدم
        
        // جلب النقاط المستخدمة من النموذج أو الجلسة
        $points_used = isset($_POST['points_used']) ? (int)$_POST['points_used'] : 0;
        if ($points_used == 0 && isset($_SESSION['points_used'])) {
            $points_used = (int)$_SESSION['points_used'];
        }
        $points_used = min($points_used, $max_points_usage);
        
        // حساب الخصم من النقاط
        $points_discount = $points_used * 0.01; // 1 نقطة = 0.01 جنيه
    }
}

// حساب الخصومات والضرائب
$discount = $points_discount;
$total = $subtotal - $discount;

// حساب النقاط التي سيحصل عليها المستخدم من هذا الطلب
if (isset($_SESSION['user_id']) && is_points_system_enabled()) {
    $points_to_earn = calculate_points_from_amount($total);
}

// جلب الحقول المخصصة لكل خدمة في السلة
$service_custom_fields = [];
$service_ids = array_unique(array_column($cart_items, 'service_id'));
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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عربة التسوق - مطبعة برنتلي</title>
    
    <style>
        :root {
            --primary-color: #00adef;
            --primary-hover-color:rgb(79, 205, 255);
            --secondary-color: #343a40;
            --light-gray-color: #f8f9fa;
            --text-color: #333;
            --border-color: #dee2e6;
            --border-radius: 0.75rem;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f9f9f9;
            margin-top: 120px;
            font-family: 'Cairo', sans-serif;
        }

        .cart-section {
            padding: 2rem 0;
            min-height: calc(100vh - 120px);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            background-color: #fff;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table th {
            font-weight: 600;
            color: var(--secondary-color);
            background-color: var(--light-gray-color);
            border-bottom: 2px solid var(--primary-color);
            padding: 1rem 1.5rem;
            white-space: nowrap;
        }

        .table td {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .product-info {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: nowrap;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        .product-details {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            color: var(--secondary-color);
        }

        .product-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.4em 0.6em;
            border-radius: 0.5rem;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border-radius: 0;
            border: 1px solid var(--border-color);
        }

        .quantity-btn {
            border-radius: 0;
            padding: 0.25rem 0.5rem;
        }

        .input-group {
            width: auto;
            display: inline-flex;
        }

        .btn-update-quantity {
            padding: 0.375rem 0.75rem;
            background-color: var(--primary-color);
            border: none;
            color: white;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .btn-update-quantity:hover {
            background-color: var(--primary-hover-color);
        }

        .btn-remove-item {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-remove-item:hover {
            color: #a71d2a;
            transform: scale(1.1);
        }

        .cart-actions {
            padding: 1.5rem;
            background-color: var(--light-gray-color);
            border-bottom-left-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #555;
            padding: 0.5rem 0;
        }

        .summary-item .label {
            font-weight: 500;
            color: var(--secondary-color);
        }

        .summary-item.total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--secondary-color);
            border-top: 2px solid var(--primary-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s ease;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover-color);
            border-color: var(--primary-hover-color);
            transform: translateY(-2px);
        }

        .empty-cart {
            padding: 4rem 0;
            text-align: center;
        }

        .empty-cart i {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        /* تنسيق قسم النقاط */
        .points-section {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 2px solid #ffc107;
        }

        .points-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shine 3s infinite;
            pointer-events: none;
            z-index: 1;
        }

        @keyframes shine {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .points-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            background: rgba(255,255,255,0.9);
            padding: 0.5rem;
            border-radius: 10px;
            border: 1px solid #ffc107;
            position: relative;
            z-index: 2;
        }

        .points-balance {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        .points-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.95);
            padding: 1rem;
            border-radius: 10px;
            border: 2px solid #ffc107;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }

        .points-label {
            font-weight: bold;
            color: #333;
            margin: 0;
            white-space: nowrap;
        }

        .points-input {
            width: 120px;
            text-align: center;
            border: 2px solid #00adef;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: bold;
            background: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 3;
        }

        .points-input:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
            outline: none;
            transform: scale(1.02);
        }

        .points-input:hover {
            border-color: #ffc107;
        }

        .points-input:invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .points-unit {
            font-weight: bold;
            color: #333;
            white-space: nowrap;
        }

        .points-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
            margin-top: 0.5rem;
            background: rgba(40, 167, 69, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #28a745;
            position: relative;
            z-index: 2;
        }

        .points-limits {
            text-align: center;
            margin: 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
            background: rgba(255,255,255,0.8);
            padding: 0.5rem;
            border-radius: 5px;
            position: relative;
            z-index: 2;
        }

        .points-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
            position: relative;
            z-index: 2;
        }

        .points-actions .btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 3;
        }

        .points-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .points-quick-buttons {
            background: rgba(255,255,255,0.9);
            padding: 0.75rem;
            border-radius: 10px;
            border: 1px solid #ffc107;
            position: relative;
            z-index: 2;
        }

        .points-quick-buttons .btn {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            margin: 0.1rem;
            position: relative;
            z-index: 3;
        }

        .points-quick-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .points-disabled {
            background: #f8f9fa;
            color: #6c757d;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
            position: relative;
            z-index: 2;
        }

        .points-disabled i {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
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
            pointer-events: none;
            z-index: 1;
        }

        .points-earn-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .points-earn-info i {
            font-size: 2rem;
            color: #ffc107;
            position: relative;
            z-index: 2;
        }

        .points-earn-amount {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 2;
        }

        .points-earn-info small {
            opacity: 0.9;
            font-size: 0.9rem;
            position: relative;
            z-index: 2;
        }

        .points-earn-value {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            body {
                margin-top: 80px;
            }

            .cart-section {
                padding: 1rem 0;
            }

            .table th, .table td {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .product-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 0.75rem;
            }

            .product-image {
                width: 100px;
                height: 100px;
                margin-bottom: 0.5rem;
            }

            .product-details {
                min-width: auto;
                width: 100%;
            }

            .product-name {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }

            .product-options {
                justify-content: center;
                gap: 0.5rem;
            }

            .badge {
                font-size: 0.8rem;
                padding: 0.4em 0.6em;
            }

            .quantity-form {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }

            .input-group {
                width: 140px !important;
            }

            .quantity-input {
                width: 60px;
                padding: 0.375rem;
            }

            .btn-update-quantity {
                padding: 0.375rem 0.75rem;
                width: 140px;
            }

            .cart-actions {
                padding: 1rem;
            }

            .cart-actions .d-flex {
                flex-direction: column;
                gap: 1rem;
            }

            .cart-actions .btn {
                width: 100%;
                padding: 0.75rem;
                font-size: 1rem;
            }

            .summary-item {
                font-size: 1rem;
                padding: 0.75rem 0;
            }

            .summary-item.total {
                font-size: 1.2rem;
            }

            .btn-checkout {
                padding: 1rem;
                font-size: 1.1rem;
            }

            .card-header {
                padding: 1.25rem;
            }

            .card-title {
                font-size: 1.35rem;
            }

            .empty-cart {
                padding: 3rem 1rem;
            }

            .empty-cart i {
                font-size: 4rem;
            }

            .empty-cart h2 {
                font-size: 1.75rem;
            }

            .empty-cart p {
                font-size: 1rem;
                margin-bottom: 2rem;
            }

            .empty-cart .btn {
                padding: 1rem 2rem;
                font-size: 1.1rem;
            }

            .points-info {
                flex-direction: column;
                gap: 1rem;
            }

            .points-input-group {
                justify-content: center;
            }

            .points-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .points-actions .btn {
                width: 100%;
                padding: 0.75rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .table {
                display: table;
                width: 100%;
                border-collapse: collapse;
                font-size: 0.85rem;
            }

            .table thead {
                display: table-header-group;
                background-color: var(--light-gray-color);
            }

            .table tbody {
                display: table-row-group;
            }

            .table tr {
                display: table-row;
                margin-bottom: 0;
                border: none;
                padding: 0;
                background-color: transparent;
                box-shadow: none;
            }

            .table tr:nth-child(even) {
                background-color: #f8f9fa;
            }

            .table td {
                display: table-cell;
                text-align: center;
                padding: 0.4rem;
                border: 1px solid #dee2e6;
                position: relative;
                vertical-align: middle;
                white-space: nowrap;
            }

            .table td::before {
                display: none;
            }

            .table th {
                display: table-cell;
                padding: 0.4rem;
                font-size: 0.8rem;
                background-color: var(--primary-color);
                color: white;
                border: 1px solid var(--primary-color);
                white-space: nowrap;
                font-weight: 600;
            }

            .product-info {
                margin: 0;
                padding: 0;
                background-color: transparent;
                border-radius: 0;
                display: flex;
                align-items: center;
                gap: 0.4rem;
                min-width: 150px;
            }

            .product-image {
                width: 45px;
                height: 45px;
                margin: 0;
                border-radius: 4px;
                box-shadow: none;
                object-fit: cover;
            }

            .product-details {
                text-align: right;
                flex: 1;
                min-width: 0;
            }

            .product-name {
                font-size: 0.85rem;
                margin-bottom: 0.2rem;
                color: var(--secondary-color);
                white-space: normal;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .product-options {
                justify-content: flex-start;
                gap: 0.2rem;
                margin-top: 0.2rem;
                flex-wrap: wrap;
            }

            .badge {
                font-size: 0.65rem;
                padding: 0.15em 0.3em;
                margin: 0.1rem;
                border-radius: 3px;
                white-space: nowrap;
            }

            .quantity-form {
                margin: 0;
                padding: 0;
                background-color: transparent;
                border-radius: 0;
                min-width: 100px;
            }

            .input-group {
                width: 90px !important;
                margin: 0 auto;
            }

            .quantity-input {
                width: 35px;
                padding: 0.25rem;
                font-size: 0.8rem;
                border: 1px solid #dee2e6;
                border-radius: 3px;
                text-align: center;
            }

            .quantity-btn {
                padding: 0.25rem 0.35rem;
                font-size: 0.8rem;
                background-color: #fff;
                border: 1px solid #dee2e6;
                color: var(--secondary-color);
            }

            .btn-update-quantity {
                width: 90px;
                margin: 0.2rem auto 0;
                display: block;
                padding: 0.25rem;
                font-size: 0.75rem;
                border-radius: 3px;
            }

            .btn-remove-item {
                margin: 0;
                font-size: 0.9rem;
                padding: 0.25rem;
                background-color: transparent;
                border-radius: 0;
                box-shadow: none;
                color: #dc3545;
            }

            .table-responsive {
                margin: 0;
                padding: 0;
                border: none;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-responsive::-webkit-scrollbar {
                height: 4px;
            }

            .table-responsive::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 2px;
            }

            .table-responsive::-webkit-scrollbar-thumb {
                background: var(--primary-color);
                border-radius: 2px;
            }

            .table-responsive::-webkit-scrollbar-thumb:hover {
                background: var(--primary-hover-color);
            }

            .cart-actions {
                padding: 0.4rem;
                background-color: var(--light-gray-color);
                border-radius: 4px;
                margin-top: 0.4rem;
            }

            .cart-actions .d-flex {
                flex-direction: row;
                gap: 0.4rem;
                justify-content: space-between;
            }

            .cart-actions .btn {
                width: auto;
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
                margin: 0;
                border-radius: 3px;
            }

            .summary-card {
                margin-top: 0.5rem;
            }

            .container {
                padding-left: 4px;
                padding-right: 4px;
            }

            .card {
                margin-bottom: 0.5rem;
                border-radius: 4px;
            }

            .card-body {
                padding: 0.4rem;
            }

            .card-header {
                padding: 0.4rem;
            }

            .card-title {
                font-size: 0.9rem;
            }

            .summary-item {
                padding: 0.3rem 0;
                font-size: 0.8rem;
            }

            .summary-item.total {
                font-size: 0.9rem;
                padding-top: 0.4rem;
                margin-top: 0.4rem;
            }

            .btn-checkout {
                padding: 0.4rem;
                font-size: 0.85rem;
                border-radius: 4px;
                margin-top: 0.5rem;
            }

            .alert {
                margin: 0.4rem 0;
                padding: 0.4rem;
                border-radius: 4px;
                font-size: 0.8rem;
            }

            .empty-cart {
                padding: 1rem 0.5rem;
            }

            .empty-cart i {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }

            .empty-cart h2 {
                font-size: 1.1rem;
                margin-bottom: 0.4rem;
            }

            .empty-cart p {
                font-size: 0.8rem;
                margin-bottom: 0.8rem;
            }

            .empty-cart .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
                border-radius: 4px;
            }

            .cart-section {
                padding: 0.5rem 0;
            }

            .row {
                margin: 0 -4px;
            }

            .col-lg-8, .col-lg-4 {
                padding: 0 4px;
            }

            .points-section {
                padding: 1rem;
            }

            .points-info {
                padding: 0.5rem;
            }

            .points-balance {
                font-size: 1.2rem;
            }

            .points-input {
                width: 80px;
                padding: 0.3rem;
                font-size: 0.9rem;
            }

            .points-actions {
                flex-direction: column;
                gap: 0.3rem;
            }

            .points-actions .btn {
                width: 100%;
                padding: 0.5rem;
                font-size: 0.85rem;
                border-radius: 15px;
            }

            .points-value {
                font-size: 1rem;
                padding: 0.3rem 0.8rem;
            }

            .points-limits {
                font-size: 0.8rem;
                padding: 0.3rem;
            }
        }

        .row {
            margin: 0 -15px;
        }

        .col-lg-8, .col-lg-4 {
            padding: 0 15px;
        }

        @media (min-width: 992px) {
            .col-lg-8 {
                width: 66.666667%;
                float: right;
            }
            
            .col-lg-4 {
                width: 33.333333%;
                float: right;
            }
        }

        .card {
            height: 100%;
            margin-bottom: 30px;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table-responsive {
            margin: 0;
            padding: 0;
        }

        .summary-card {
            position: sticky;
            top: 100px;
        }

        @media (max-width: 991px) {
            .summary-card {
                position: static;
                margin-top: 2rem;
            }
            
            .col-lg-8, .col-lg-4 {
                width: 100%;
                float: none;
            }
        }

        .cart-actions {
            margin-top: auto;
        }

        .summary-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item.total {
            border-top: 2px solid var(--primary-color);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .btn-checkout {
            width: 100%;
            margin-top: 1.5rem;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .points-input {
            width: 80px;
            padding: 0.3rem;
            font-size: 0.9rem;
        }

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

        .points-actions .btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
        }

        .points-quick-buttons {
            padding: 0.5rem;
        }

        .points-quick-buttons .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            margin: 0.05rem;
        }
    </style>
</head>

<body>
    <section class="cart-section">
        <div class="container">
            <!-- رسائل التنبيه -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="text-center empty-cart">
                    <i class="fas fa-shopping-basket fa-4x text-muted mb-4"></i>
                    <h2 class="mb-3">عربة التسوق فارغة</h2>
                    <p class="text-muted mb-4">لم تقم بإضافة أي منتجات إلى عربة التسوق حتى الآن.</p>
                    <a href="services.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>
                        ابدأ التسوق الآن
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    عربة التسوق (<?= count($cart_items) ?>)
                                </h2>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">المنتج</th>
                                                <th scope="col" class="text-center">سعر القطعة</th>
                                                <th scope="col" class="text-center">الكمية</th>
                                                <th scope="col" class="text-center">الإجمالي</th>
                                                <th scope="col" class="text-center">حذف</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cart_items as $item): ?>
                                                <tr>
                                                    <td data-label="المنتج">
                                                        <div class="product-info">
                                                            <img src="uploads/services/<?= htmlspecialchars($item['image']) ?>" 
                                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                                 class="product-image"
                                                                 onerror="this.src='assets/images/default-service.jpg'">
                                                            <div class="product-details">
                                                                <h5 class="product-name mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                                                <div class="product-options">
                                                                    <?php if (!empty($item['paper_type'])): ?>
                                                                        <span class="badge bg-light text-dark">نوع الورق: <?= htmlspecialchars($item['paper_type']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['size'])): ?>
                                                                        <span class="badge bg-light text-dark">المقاس: <?= htmlspecialchars($item['size']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['colors'])): ?>
                                                                        <span class="badge bg-light text-dark">الألوان: <?= htmlspecialchars($item['colors']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['notes'])): ?>
                                                                        <span class="badge bg-info text-white">ملاحظات</span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['design_file'])): ?>
                                                                        <span class="badge bg-success text-white">ملف التصميم</span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($service_custom_fields[$item['service_id']])): ?>
                                                                        <?php foreach ($service_custom_fields[$item['service_id']] as $field): ?>
                                                                            <?php 
                                                                            $field_name = 'custom_field_' . $field['field_id'];
                                                                            if (!empty($item[$field_name])): ?>
                                                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($field['field_label']); ?>: <?php echo htmlspecialchars($item[$field_name]); ?></span>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center" data-label="سعر القطعة">
                                                        <strong><?= number_format($item['price'], 2) ?> <?= $currency ?></strong>
                                                    </td>
                                                    <td class="text-center" data-label="الكمية">
                                                        <form action="cart.php" method="post" class="quantity-form">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                            <div class="input-group" style="width: 120px; margin: 0 auto;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="50000" class="form-control form-control-sm text-center quantity-input" oninput="showConfirmButton(this)">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm quantity-btn" onclick="updateQuantity(this, 1)">+</button>
                                                            </div>
                                                            <div class="d-flex justify-content-center mt-1">
                                                                <button type="submit" class="btn btn-success btn-sm confirm-quantity" style="display: none; width: 120px;">
                                                                    <i class="fas fa-check"></i> تأكيد
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                    <td class="text-center" data-label="الإجمالي">
                                                        <strong><?= number_format($item['total'], 2) ?> <?= $currency ?></strong>
                                                    </td>
                                                    <td class="text-center" data-label="حذف">
                                                        <form action="cart.php" method="post" style="display: inline;">
                                                            <input type="hidden" name="action" value="remove">
                                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                            <button type="submit" class="btn-remove-item" 
                                                                    onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="cart-actions">
                                <div class="d-flex justify-content-between">
                                    <form action="cart.php" method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="clear">
                                        <button type="submit" class="btn btn-outline-danger" 
                                                onclick="return confirm('هل أنت متأكد من إفراغ السلة؟')">
                                            <i class="fas fa-trash me-2"></i>
                                            إفراغ السلة
                                        </button>
                                    </form>
                                    <a href="services.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus me-2"></i>
                                        إضافة منتجات أخرى
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card summary-card">
                            <div class="card-header">
                                <h4 class="card-title">
                                    <i class="fas fa-calculator me-2"></i>
                                    ملخص الطلب
                                </h4>
                            </div>
                            <div class="card-body">
                                <!-- قسم النقاط -->
                                <?php if (isset($_SESSION['user_id']) && is_points_system_enabled() && $user_points > 0): ?>
                                    <div class="points-section">
                                        <h5><i class="fas fa-star"></i> استخدام النقاط لتخفيض السعر</h5>
                                        <div class="alert alert-info" style="margin-bottom: 1rem; font-size: 0.9rem;">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>كيفية الاستخدام:</strong> اكتب عدد النقاط التي تريد استخدامها، ثم اضغط "تطبيق الخصم"
                                        </div>
                                        <div class="points-info">
                                            <div>
                                                <div class="points-balance"><?= number_format($user_points) ?> نقطة</div>
                                                <small>متاح في حسابك</small>
                                            </div>
                                            <div class="points-input-group">
                                                <label for="points-used" class="points-label">عدد النقاط:</label>
                                                <input type="number" id="points-used" name="points_used" 
                                                       class="points-input" min="0" max="<?= $max_points_usage ?>" 
                                                       value="<?= $points_used ?>"
                                                       placeholder="0"
                                                       step="1">
                                                <span class="points-unit">نقطة</span>
                                            </div>
                                        </div>
                                        <div class="points-quick-buttons" style="margin: 0.5rem 0;">
                                            <small class="text-muted">استخدام سريع:</small>
                                            <div class="d-flex gap-1 mt-1" style="flex-wrap: wrap;">
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="setPoints(100)">
                                                    100 نقطة
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="setPoints(500)">
                                                    500 نقطة
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="setPoints(1000)">
                                                    1000 نقطة
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="setPoints(<?= $max_points_usage ?>)">
                                                    الحد الأقصى
                                                </button>
                                            </div>
                                        </div>
                                        <div class="points-limits">
                                            <small>الحد الأقصى: <?= number_format($max_points_usage) ?> نقطة</small>
                                            <br>
                                            <small class="text-info">1 نقطة = 0.01 جنيه خصم</small>
                                        </div>
                                        <div class="points-value">
                                            خصم: <?= number_format($points_discount, 2) ?> جنيه
                                        </div>
                                        <div class="points-actions">
                                            <button type="button" class="btn btn-outline-primary" onclick="applyPointsDiscount()">
                                                <i class="fas fa-check"></i> تطبيق الخصم
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="resetPointsDiscount()">
                                                <i class="fas fa-undo"></i> إعادة تعيين
                                            </button>
                                        </div>
                                    </div>
                                <?php elseif (isset($_SESSION['user_id']) && is_points_system_enabled()): ?>
                                    <div class="points-disabled">
                                        <i class="fas fa-star"></i>
                                        <div>لا توجد نقاط متاحة في حسابك</div>
                                        <small>يمكنك إكمال طلبات للحصول على نقاط</small>
                                    </div>
                                <?php endif; ?>

                                <div class="summary-item">
                                    <span class="label">المجموع الفرعي:</span>
                                    <span><?= number_format($subtotal, 2) ?> <?= $currency ?></span>
                                </div>
                                
                                <!-- عرض رقم الخصم إذا وجد -->
                                <?php if (isset($_SESSION['discount_code']) && !empty($_SESSION['discount_code'])): ?>
                                    <div class="summary-item">
                                        <span class="label">رقم الخصم:</span>
                                        <span class="text-primary"><?= htmlspecialchars($_SESSION['discount_code']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($points_discount > 0): ?>
                                    <div class="summary-item">
                                        <span class="label">خصم النقاط:</span>
                                        <span class="text-success">-<?= number_format($points_discount, 2) ?> <?= $currency ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="summary-item total">
                                    <span class="label">الإجمالي:</span>
                                    <span><?= number_format($total, 2) ?> <?= $currency ?></span>
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
                                
                                <a href="checkout.php<?= $points_used > 0 ? '?points_used=' . $points_used : '' ?>" class="btn btn-primary btn-checkout">
                                    <i class="fas fa-credit-card me-2"></i>
                                    متابعة للدفع
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateQuantity(button, change) {
        const input = button.parentElement.querySelector('.quantity-input');
        const currentValue = parseInt(input.value);
        const newValue = currentValue + change;
        
        if (newValue >= 1) {
            input.value = newValue;
            showConfirmButton(input);
        }
    }

    function showConfirmButton(input) {
        const form = input.form;
        const confirmButton = form.querySelector('.confirm-quantity');
        const maxQuantity = 50000;
        let currentValue = parseInt(input.value);

        if (currentValue > maxQuantity) {
            alert('لا يمكن تجاوز الحد الأقصى للكمية (50000). سيتم تعيين الكمية إلى 50000.');
            input.value = maxQuantity;
            currentValue = maxQuantity; // Update currentValue after setting input.value
        }

        if (currentValue >= 1) {
            confirmButton.style.display = 'block';
        } else {
            confirmButton.style.display = 'none';
            input.value = 1; // Ensure quantity is at least 1
        }
    }

    // تحديث السعر عند تأكيد الكمية
    document.querySelectorAll('.confirm-quantity').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.form;
            const formData = new FormData(form);
            
            // إرسال طلب AJAX لتحديث الكمية والسعر
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // تحديث الصفحة لعرض التغييرات
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    // إضافة معالجة الأحداث عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        // إضافة معالجة الأحداث لحقل النقاط
        const pointsInput = document.getElementById('points-used');
        if (pointsInput) {
            pointsInput.addEventListener('input', function() {
                updatePointsDiscount();
            });
            
            pointsInput.addEventListener('change', function() {
                updatePointsDiscount();
            });
            
            pointsInput.addEventListener('blur', function() {
                // التأكد من أن القيمة صحيحة عند فقدان التركيز
                const value = parseInt(this.value) || 0;
                const max = parseInt(this.max) || 0;
                
                if (value < 0) {
                    this.value = 0;
                } else if (value > max) {
                    this.value = max;
                    alert(`لا يمكن استخدام أكثر من ${max.toLocaleString()} نقطة`);
                }
                
                updatePointsDiscount();
            });
            
            // منع إدخال قيم سالبة أو أحرف غير رقمية
            pointsInput.addEventListener('keypress', function(e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
            });
            
            // منع اللصق من الحافظة إذا كان يحتوي على أحرف غير رقمية
            pointsInput.addEventListener('paste', function(e) {
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                if (!/^\d*$/.test(pastedText)) {
                    e.preventDefault();
                }
            });
        }
        
        // تهيئة القيم الأولية
        updatePointsDiscount();
    });

    // دالة لتطبيق خصم النقاط
    function applyPointsDiscount() {
        const pointsUsed = parseInt(document.getElementById('points-used').value) || 0;
        const userPoints = <?= $user_points ?>;
        
        if (pointsUsed > userPoints) {
            alert('النقاط المطلوبة غير متوفرة في حسابك!');
            return;
        }
        
        if (pointsUsed > 0) {
            // إرسال طلب لتطبيق الخصم
            const formData = new FormData();
            formData.append('action', 'apply_points_discount');
            formData.append('points_used', pointsUsed);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // تحديث الصفحة لعرض التغييرات
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تطبيق الخصم');
            });
        } else {
            alert('يرجى اختيار عدد النقاط التي تريد استخدامها');
        }
    }

    // دالة لإعادة تعيين خصم النقاط
    function resetPointsDiscount() {
        // إرسال طلب لإعادة تعيين النقاط
        const formData = new FormData();
        formData.append('action', 'reset_points_discount');
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // تحديث الصفحة لعرض التغييرات
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء إعادة تعيين النقاط');
        });
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

    // دوال النقاط
    function updatePointsFromSlider(value) {
        document.getElementById('points-used').value = value;
        updatePointsDiscount();
    }

    function setPoints(points) {
        const pointsInput = document.getElementById('points-used');
        const maxPoints = parseInt(pointsInput.max) || 0;
        const actualPoints = Math.min(points, maxPoints);
        
        pointsInput.value = actualPoints;
        updatePointsDiscount();
        
        // إظهار رسالة تأكيد
        if (actualPoints > 0) {
            const discount = (actualPoints * 0.01).toFixed(2);
            alert(`تم تعيين النقاط إلى ${actualPoints.toLocaleString()} نقطة\nالخصم: ${discount} جنيه`);
        }
    }

    function updatePointsDiscount() {
        const pointsUsed = parseInt(document.getElementById('points-used').value) || 0;
        const subtotal = <?= $subtotal ?>;
        const userPoints = <?= $user_points ?>;
        
        // حساب الحد الأقصى للنقاط التي يمكن استخدامها
        const maxDiscount = subtotal * 0.10; // خصم أقصى 10%
        const maxPointsUsage = Math.floor(maxDiscount / 0.01); // 1 نقطة = 0.01 جنيه
        const actualMaxPoints = Math.min(maxPointsUsage, userPoints);
        
        // تحديث الحد الأقصى في المدخلات
        document.getElementById('points-used').max = actualMaxPoints;
        
        // تحديث معلومات الحد الأقصى
        const pointsLimitsElement = document.querySelector('.points-limits small');
        if (pointsLimitsElement) {
            pointsLimitsElement.textContent = `الحد الأقصى: ${actualMaxPoints.toLocaleString()} نقطة`;
        }
        
        // التأكد من أن النقاط المستخدمة لا تتجاوز الحد الأقصى
        const actualPointsUsed = Math.min(pointsUsed, actualMaxPoints);
        document.getElementById('points-used').value = actualPointsUsed;
        
        // حساب الخصم: 1 نقطة = 0.01 جنيه
        const pointsDiscount = actualPointsUsed * 0.01;
        
        const pointsValueElement = document.querySelector('.points-value');
        if (pointsValueElement) {
            pointsValueElement.textContent = `خصم: ${pointsDiscount.toFixed(2)} جنيه`;
        }
        
        // تحديث الإجمالي
        const total = subtotal - pointsDiscount;
        const totalElement = document.querySelector('.summary-item.total span:last-child');
        if (totalElement) {
            totalElement.textContent = `${total.toFixed(2)} جنيه`;
        }
        
        // تحديث النقاط التي سيحصل عليها المستخدم
        updatePointsEarned(total);
        
        // تحديث رابط الدفع
        const checkoutLink = document.querySelector('.btn-checkout');
        if (checkoutLink) {
            if (actualPointsUsed > 0) {
                checkoutLink.href = `checkout.php?points_used=${actualPointsUsed}`;
            } else {
                checkoutLink.href = 'checkout.php';
            }
        }
    }
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>


        // تحديث الحد الأقصى في المدخلات
        document.getElementById('points-used').max = actualMaxPoints;
        
        // تحديث معلومات الحد الأقصى
        const pointsLimitsElement = document.querySelector('.points-limits small');
        if (pointsLimitsElement) {
            pointsLimitsElement.textContent = `الحد الأقصى: ${actualMaxPoints.toLocaleString()} نقطة`;
        }
        
        // التأكد من أن النقاط المستخدمة لا تتجاوز الحد الأقصى
        const actualPointsUsed = Math.min(pointsUsed, actualMaxPoints);
        document.getElementById('points-used').value = actualPointsUsed;
        
        // حساب الخصم: 1 نقطة = 0.01 جنيه
        const pointsDiscount = actualPointsUsed * 0.01;
        
        const pointsValueElement = document.querySelector('.points-value');
        if (pointsValueElement) {
            pointsValueElement.textContent = `خصم: ${pointsDiscount.toFixed(2)} جنيه`;
        }
        
        // تحديث الإجمالي
        const total = subtotal - pointsDiscount;
        const totalElement = document.querySelector('.summary-item.total span:last-child');
        if (totalElement) {
            totalElement.textContent = `${total.toFixed(2)} جنيه`;
        }
        
        // تحديث النقاط التي سيحصل عليها المستخدم
        updatePointsEarned(total);
        
        // تحديث رابط الدفع
        const checkoutLink = document.querySelector('.btn-checkout');
        if (checkoutLink) {
            if (actualPointsUsed > 0) {
                checkoutLink.href = `checkout.php?points_used=${actualPointsUsed}`;
            } else {
                checkoutLink.href = 'checkout.php';
            }
        }
    }
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>

