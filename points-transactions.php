<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/points_functions.php';

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم
$stmt = $conn->prepare("SELECT username, points_balance FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// جلب جميع معاملات النقاط
$points_transactions = get_user_points_transactions($user_id, 100, 0);
?>

<style>
.transactions-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem;
}

.transactions-header {
    background: linear-gradient(135deg, #00adef, #0098d4);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 173, 239, 0.3);
}

.transactions-header h1 {
    margin: 0 0 1rem 0;
    font-size: 2rem;
}

.points-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.summary-value {
    font-size: 2rem;
    font-weight: bold;
    color: #00adef;
    margin-bottom: 0.5rem;
}

.summary-label {
    color: #666;
    font-size: 0.9rem;
}

.transactions-list {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    transition: background-color 0.3s ease;
}

.transaction-item:hover {
    background-color: #f8f9fa;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-info {
    flex: 1;
}

.transaction-amount {
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.transaction-amount.positive {
    color: #28a745;
}

.transaction-amount.negative {
    color: #dc3545;
}

.transaction-date {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.transaction-description {
    font-size: 0.85rem;
    color: #888;
}

.transaction-type {
    text-align: center;
    min-width: 120px;
}

.transaction-type span {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.back-btn {
    display: inline-block;
    background: #6c757d;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 2rem;
    transition: background-color 0.3s ease;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .transactions-container {
        padding: 1rem;
        margin: 1rem auto;
    }
    
    .transactions-header {
        padding: 1.5rem;
    }
    
    .transactions-header h1 {
        font-size: 1.5rem;
    }
    
    .transaction-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .transaction-type {
        align-self: flex-end;
    }
    
    .points-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="transactions-container">
    <a href="account.php" class="back-btn">
        <i class="fas fa-arrow-right"></i> العودة إلى الحساب
    </a>
    
    <div class="transactions-header">
        <h1><i class="fas fa-history"></i> معاملات النقاط</h1>
        <p>عرض جميع معاملات النقاط الخاصة بك</p>
    </div>
    
    <div class="points-summary">
        <div class="summary-card">
            <div class="summary-value"><?= number_format($user['points_balance']) ?></div>
            <div class="summary-label">الرصيد الحالي</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= count($points_transactions) ?></div>
            <div class="summary-label">إجمالي المعاملات</div>
        </div>
    </div>
    
    <div class="transactions-list">
        <h3><i class="fas fa-list"></i> جميع المعاملات</h3>
        
        <?php if (!empty($points_transactions)): ?>
            <?php foreach ($points_transactions as $transaction): ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-amount <?= $transaction['points_amount'] > 0 ? 'positive' : 'negative' ?>">
                            <?= $transaction['points_amount'] > 0 ? '+' : '' ?><?= number_format($transaction['points_amount']) ?> نقطة
                        </div>
                        <div class="transaction-date">
                            <i class="fas fa-calendar"></i>
                            <?= date('Y/m/d H:i', strtotime($transaction['created_at'])) ?>
                            <?php if (!empty($transaction['order_number'])): ?>
                                - طلب #<?= $transaction['order_number'] ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($transaction['description'])): ?>
                            <div class="transaction-description">
                                <i class="fas fa-info-circle"></i>
                                <?= htmlspecialchars($transaction['description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="transaction-type">
                        <?php
                        switch($transaction['transaction_type']) {
                            case 'earn':
                                if ($transaction['status'] === 'pending') {
                                    echo '<span style="background: #fff3cd; color: #856404;"><i class="fas fa-clock"></i> في الانتظار</span>';
                                } else {
                                    echo '<span style="background: #d4edda; color: #155724;"><i class="fas fa-plus-circle"></i> اكتساب</span>';
                                }
                                break;
                            case 'spend':
                                echo '<span style="background: #f8d7da; color: #721c24;"><i class="fas fa-minus-circle"></i> استهلاك</span>';
                                break;
                            case 'purchase':
                                echo '<span style="background: #cce5ff; color: #004085;"><i class="fas fa-shopping-cart"></i> شراء</span>';
                                break;
                            case 'redemption':
                                echo '<span style="background: #e2d9f3; color: #6f42c1;"><i class="fas fa-exchange-alt"></i> استبدال</span>';
                                break;
                            default:
                                echo '<span style="background: #e2e3e5; color: #383d41;"><i class="fas fa-exchange-alt"></i> معاملة</span>';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h4>لا توجد معاملات نقاط</h4>
                <p>لم تقم بأي معاملات نقاط حتى الآن</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 