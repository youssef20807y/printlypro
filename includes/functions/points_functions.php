<?php
/**
 * دوال نظام النقاط
 */

/**
 * الحصول على إعدادات نظام النقاط
 */
function get_points_settings($key = null) {
    global $db;
    
    try {
        if ($key) {
            $stmt = $db->prepare("SELECT setting_value FROM points_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : null;
        } else {
            $stmt = $db->query("SELECT setting_key, setting_value FROM points_settings");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        }
    } catch (PDOException $e) {
        error_log("Error getting points settings: " . $e->getMessage());
        return null;
    }
}

/**
 * تحديث إعدادات نظام النقاط
 */
function update_points_setting($key, $value) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO points_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        error_log("Error updating points setting: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على رصيد نقاط المستخدم
 */
function get_user_points($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT points_balance, total_points_earned, total_points_spent 
            FROM users WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user points: " . $e->getMessage());
        return null;
    }
}

/**
 * إضافة نقاط للمستخدم
 */
function add_user_points($user_id, $points, $type = 'earn', $order_id = null, $description = null, $reference = null) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // تحديث رصيد المستخدم
        $update_stmt = $db->prepare("
            UPDATE users 
            SET points_balance = points_balance + ?, 
                total_points_earned = total_points_earned + ? 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$points, $points, $user_id]);
        
        // تسجيل المعاملة
        $transaction_stmt = $db->prepare("
            INSERT INTO points_transactions (
                user_id, order_id, transaction_type, points_amount, description, reference
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $transaction_stmt->execute([
            $user_id, $order_id, $type, $points, $description, $reference
        ]);
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error adding user points: " . $e->getMessage());
        return false;
    }
}

/**
 * خصم نقاط من المستخدم
 */
function deduct_user_points($user_id, $points, $type = 'spend', $order_id = null, $description = null, $reference = null) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // التحقق من رصيد النقاط
        $balance_stmt = $db->prepare("SELECT points_balance FROM users WHERE user_id = ?");
        $balance_stmt->execute([$user_id]);
        $user = $balance_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || $user['points_balance'] < $points) {
            $db->rollBack();
            return false; // رصيد غير كافي
        }
        
        // تحديث رصيد المستخدم
        $update_stmt = $db->prepare("
            UPDATE users 
            SET points_balance = points_balance - ?, 
                total_points_spent = total_points_spent + ? 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$points, $points, $user_id]);
        
        // تسجيل المعاملة
        $transaction_stmt = $db->prepare("
            INSERT INTO points_transactions (
                user_id, order_id, transaction_type, points_amount, description, reference
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $transaction_stmt->execute([
            $user_id, $order_id, $type, -$points, $description, $reference
        ]);
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error deducting user points: " . $e->getMessage());
        return false;
    }
}

/**
 * حساب النقاط المكتسبة من مبلغ الطلب
 */
function calculate_points_from_amount($amount) {
    $points_per_100 = (int)get_points_settings('points_per_100_egp');
    return floor($amount / 100) * $points_per_100;
}

/**
 * حساب قيمة الخصم من النقاط
 */
function calculate_points_discount($points) {
    $redemption_rate = (float)get_points_settings('points_redemption_rate');
    return ($points / 100) * $redemption_rate;
}

/**
 * إضافة نقاط من الطلب المكتمل
 */
function add_points_from_order($order_id) {
    global $db;
    
    try {
        // جلب بيانات الطلب
        $order_stmt = $db->prepare("
            SELECT user_id, total_amount, points_earned 
            FROM orders WHERE order_id = ? AND status = 'delivered'
        ");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order || $order['points_earned'] > 0) {
            return false; // الطلب غير موجود أو تم إضافة النقاط مسبقاً
        }
        
        // حساب النقاط المكتسبة
        $points_earned = calculate_points_from_amount($order['total_amount']);
        
        if ($points_earned > 0) {
            // إضافة النقاط للمستخدم
            if (add_user_points(
                $order['user_id'], 
                $points_earned, 
                'earn', 
                $order_id, 
                'نقاط مكتسبة من الطلب #' . $order_id
            )) {
                // تحديث الطلب
                $update_stmt = $db->prepare("
                    UPDATE orders SET points_earned = ? WHERE order_id = ?
                ");
                $update_stmt->execute([$points_earned, $order_id]);
                return true;
            }
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error adding points from order: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على معاملات النقاط للمستخدم
 */
function get_user_points_transactions($user_id, $limit = 20, $offset = 0) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT pt.*, o.order_number 
            FROM points_transactions pt 
            LEFT JOIN orders o ON pt.order_id = o.order_id 
            WHERE pt.user_id = ? 
            ORDER BY pt.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user points transactions: " . $e->getMessage());
        return [];
    }
}

/**
 * إنشاء طلب شراء نقاط
 */
function create_points_purchase($user_id, $points_amount) {
    global $db;
    
    try {
        $purchase_price = (float)get_points_settings('points_purchase_price');
        $purchase_rate = (int)get_points_settings('points_purchase_rate');
        
        $payment_amount = ($points_amount / $purchase_rate) * $purchase_price;
        
        $stmt = $db->prepare("
            INSERT INTO points_purchases (
                user_id, points_amount, payment_amount, payment_status
            ) VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$user_id, $points_amount, $payment_amount]);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating points purchase: " . $e->getMessage());
        return false;
    }
}

/**
 * إنشاء طلب استبدال نقاط
 */
function create_points_redemption($user_id, $points_amount, $redemption_type = 'discount') {
    global $db;
    
    try {
        $redemption_value = calculate_points_discount($points_amount);
        
        $stmt = $db->prepare("
            INSERT INTO points_redemptions (
                user_id, points_amount, redemption_value, redemption_type, status
            ) VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user_id, $points_amount, $redemption_value, $redemption_type]);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating points redemption: " . $e->getMessage());
        return false;
    }
}

/**
 * التحقق من صلاحية نظام النقاط
 */
function is_points_system_enabled() {
    return (bool)get_points_settings('enable_points_system');
}

/**
 * الحصول على الحد الأدنى لاستبدال النقاط
 */
function get_min_points_redemption() {
    return (int)get_points_settings('min_points_redemption');
}

/**
 * تنسيق عرض النقاط
 */
function format_points($points) {
    return number_format($points) . ' نقطة';
}

/**
 * تنسيق عرض قيمة النقاط
 */
function format_points_value($points) {
    $value = calculate_points_discount($points);
    return number_format($value, 2) . ' جنيه';
}
?> 