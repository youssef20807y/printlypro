<?php
/**
 * دوال نظام النقاط
 */

/**
 * الحصول على إعدادات النقاط
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
 * تحديث إعدادات النقاط
 */
function update_points_setting($key, $value) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO points_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating points setting: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على نقاط المستخدم
 */
function get_user_points($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT points_balance, total_points_earned, total_points_spent, unverified_points_balance 
            FROM users 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? [
            'balance' => (int)$result['points_balance'],
            'total_earned' => (int)$result['total_points_earned'],
            'total_spent' => (int)$result['total_points_spent'],
            'unverified' => (int)$result['unverified_points_balance']
        ] : null;
    } catch (PDOException $e) {
        error_log("Error getting user points: " . $e->getMessage());
        return null;
    }
}

/**
 * إضافة نقاط للمستخدم
 */
function add_user_points($user_id, $points, $type = "earn", $order_id = null, $description = null, $reference = null, $db_connection = null) {
    global $db;
    $pdo = $db_connection ?: $db;
    
    try {
        // إذا لم يتم تمرير اتصال قاعدة البيانات، استخدم معاملة جديدة
        if (!$db_connection) {
            $pdo->beginTransaction();
        }
        
        // تحديث رصيد النقاط
        $update_stmt = $pdo->prepare("
            UPDATE users 
            SET points_balance = points_balance + ?, 
                total_points_earned = total_points_earned + ? 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$points, $points, $user_id]);
        
        // إضافة معاملة النقاط
        $transaction_stmt = $pdo->prepare("
            INSERT INTO points_transactions (
                user_id, order_id, transaction_type, points_amount, description, reference, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'approved')
        ");
        $transaction_stmt->execute([$user_id, $order_id, $type, $points, $description, $reference]);
        
        // إذا لم يتم تمرير اتصال قاعدة البيانات، أكد المعاملة
        if (!$db_connection) {
            $pdo->commit();
        }
        return true;
    } catch (PDOException $e) {
        // إذا لم يتم تمرير اتصال قاعدة البيانات، تراجع عن المعاملة
        if (!$db_connection && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error adding user points: " . $e->getMessage());
        return false;
    }
}

/**
 * إضافة نقاط غير مؤكدة للمستخدم
 */
function add_unverified_points($user_id, $points, $order_id = null, $description = null, $reference = null, $db_connection = null) {
    global $db;
    $pdo = $db_connection ?: $db;
    
    try {
        // إذا لم يتم تمرير اتصال قاعدة البيانات، استخدم معاملة جديدة
        if (!$db_connection) {
            $pdo->beginTransaction();
        }
        
        // تحديث رصيد النقاط غير المؤكدة
        $update_stmt = $pdo->prepare("
            UPDATE users 
            SET unverified_points_balance = unverified_points_balance + ? 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$points, $user_id]);
        
        // إضافة معاملة النقاط
        $transaction_stmt = $pdo->prepare("
            INSERT INTO points_transactions (
                user_id, order_id, transaction_type, points_amount, description, reference, status
            ) VALUES (?, ?, 'earn', ?, ?, ?, 'pending')
        ");
        $transaction_stmt->execute([$user_id, $order_id, $points, $description, $reference]);
        
        // إذا لم يتم تمرير اتصال قاعدة البيانات، أكد المعاملة
        if (!$db_connection) {
            $pdo->commit();
        }
        return true;
    } catch (PDOException $e) {
        // إذا لم يتم تمرير اتصال قاعدة البيانات، تراجع عن المعاملة
        if (!$db_connection && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error adding unverified points: " . $e->getMessage());
        return false;
    }
}

/**
 * خصم نقاط من المستخدم
 */
function deduct_user_points($user_id, $points, $type = "spend", $order_id = null, $description = null, $reference = null, $db_connection = null) {
    global $db;
    $pdo = $db_connection ?: $db;
    
    try {
        // إذا لم يتم تمرير اتصال قاعدة البيانات، استخدم معاملة جديدة
        if (!$db_connection) {
            $pdo->beginTransaction();
        }
        
        // التحقق من الرصيد المتاح
        $balance_stmt = $pdo->prepare("SELECT points_balance FROM users WHERE user_id = ?");
        $balance_stmt->execute([$user_id]);
        $current_balance = $balance_stmt->fetchColumn();
        
        if ($current_balance < $points) {
            // إذا لم يتم تمرير اتصال قاعدة البيانات، تراجع عن المعاملة
            if (!$db_connection && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false; // رصيد غير كافي
        }
        
        // تحديث رصيد النقاط
        $update_stmt = $pdo->prepare("
            UPDATE users 
            SET points_balance = points_balance - ?, 
                total_points_spent = total_points_spent + ? 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$points, $points, $user_id]);
        
        // إضافة معاملة النقاط
        $transaction_stmt = $pdo->prepare("
            INSERT INTO points_transactions (
                user_id, order_id, transaction_type, points_amount, description, reference, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'approved')
        ");
        $transaction_stmt->execute([$user_id, $order_id, $type, $points, $description, $reference]);
        
        // إذا لم يتم تمرير اتصال قاعدة البيانات، أكد المعاملة
        if (!$db_connection) {
            $pdo->commit();
        }
        return true;
    } catch (PDOException $e) {
        // إذا لم يتم تمرير اتصال قاعدة البيانات، تراجع عن المعاملة
        if (!$db_connection && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error deducting user points: " . $e->getMessage());
        return false;
    }
}

/**
 * حساب النقاط من المبلغ
 */
function calculate_points_from_amount($amount) {
    $points_per_100 = (int)get_points_settings("points_per_100_egp");
    return floor($amount / 100) * $points_per_100;
}

/**
 * حساب قيمة الخصم من النقاط
 */
function calculate_points_discount($points) {
    $redemption_rate = (float)get_points_settings("points_redemption_rate");
    return ($points / 100) * $redemption_rate;
}

/**
 * إضافة نقاط من الطلب
 */
function add_points_from_order($order_id) {
    global $db;
    
    try {
        // جلب بيانات الطلب
        $order_stmt = $db->prepare("
            SELECT user_id, total_amount, points_earned 
            FROM orders 
            WHERE order_id = ? AND payment_status = 'paid'
        ");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order || $order['points_earned'] > 0) {
            return false; // الطلب غير موجود أو تم إضافة النقاط مسبقاً
        }
        
        $points = calculate_points_from_amount($order['total_amount']);
        
        if ($points > 0) {
            // إضافة نقاط غير مؤكدة
            add_unverified_points(
                $order['user_id'], 
                $points, 
                $order_id, 
                "نقاط من الطلب #" . $order_id,
                "order_" . $order_id
            );
            
            // تحديث الطلب
            $update_stmt = $db->prepare("UPDATE orders SET points_earned = ? WHERE order_id = ?");
            $update_stmt->execute([$points, $order_id]);
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error adding points from order: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على معاملات نقاط المستخدم
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
function create_points_purchase($user_id, $points_amount, $price, $payment_method) {
    global $db;
    
    try {
        // البحث عن الحزمة المناسبة
        $stmt = $db->prepare("
            SELECT package_id FROM points_packages 
            WHERE points_amount = ? AND is_active = 1
        ");
        $stmt->execute([$points_amount]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$package) {
            // إنشاء حزمة مؤقتة إذا لم تكن موجودة
            $stmt = $db->prepare("
                INSERT INTO points_packages (points_amount, price, bonus_points) 
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$points_amount, $price]);
            $package_id = $db->lastInsertId();
        } else {
            $package_id = $package['package_id'];
        }
        
        $stmt = $db->prepare("
            INSERT INTO points_purchases (
                user_id, package_id, points_amount, price, payment_method, payment_status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user_id, $package_id, $points_amount, $price, $payment_method]);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating points purchase: " . $e->getMessage());
        return false;
    }
}

/**
 * إنشاء طلب استبدال نقاط
 */
function create_points_redemption($user_id, $points_amount, $redemption_type = "discount") {
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
    return (bool)get_points_settings("enable_points_system");
}

/**
 * الحصول على الحد الأدنى لاستبدال النقاط
 */
function get_min_points_redemption() {
    return (int)get_points_settings("min_points_redemption");
}

/**
 * تنسيق عرض النقاط
 */
function format_points($points) {
    return number_format($points) . " نقطة";
}

/**
 * تنسيق عرض قيمة النقاط
 */
function format_points_value($points) {
    $value = calculate_points_discount($points);
    return number_format($value, 2) . " جنيه";
}

/**
 * إرسال رسالة للمستخدم
 */
function send_user_message($user_id, $message_text, $message_type = "general") {
    global $db;
    try {
        $stmt = $db->prepare("INSERT INTO messages (user_id, message_text, message_type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $message_text, $message_type]);
        return true;
    } catch (PDOException $e) {
        error_log("Error sending user message: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على رسائل المستخدم
 */
function get_user_messages($user_id, $limit = 10, $offset = 0) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user messages: " . $e->getMessage());
        return [];
    }
}

/**
 * الحصول على خيارات شراء النقاط
 */
function get_points_purchase_options() {
    global $db;
    
    try {
        $stmt = $db->query("
            SELECT points_amount, price, bonus_points 
            FROM points_packages 
            WHERE is_active = 1 
            ORDER BY points_amount ASC
        ");
        
        $options = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $options[] = [
                'points' => (int)$row['points_amount'],
                'price' => (float)$row['price'],
                'bonus' => (int)$row['bonus_points']
            ];
        }
        
        // إذا لم تكن هناك حزم في قاعدة البيانات، استخدم القيم الافتراضية
        if (empty($options)) {
            $options = [
                [
                    'points' => 100,
                    'price' => 10.00,
                    'bonus' => 0
                ],
                [
                    'points' => 500,
                    'price' => 45.00,
                    'bonus' => 50
                ],
                [
                    'points' => 1000,
                    'price' => 85.00,
                    'bonus' => 150
                ],
                [
                    'points' => 2000,
                    'price' => 160.00,
                    'bonus' => 400
                ]
            ];
        }
        
        return $options;
    } catch (PDOException $e) {
        error_log("Error getting points purchase options: " . $e->getMessage());
        
        // إرجاع القيم الافتراضية في حالة الخطأ
        return [
            [
                'points' => 100,
                'price' => 10.00,
                'bonus' => 0
            ],
            [
                'points' => 500,
                'price' => 45.00,
                'bonus' => 50
            ],
            [
                'points' => 1000,
                'price' => 85.00,
                'bonus' => 150
            ],
            [
                'points' => 2000,
                'price' => 160.00,
                'bonus' => 400
            ]
        ];
    }
}

?>


