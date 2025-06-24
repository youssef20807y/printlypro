<?php
/**
 * صفحة تأكيد الطلب لموقع مطبعة برنتلي
 */

define('PRINTLY', true);
require_once 'includes/header.php';

// التحقق من وجود رقم الطلب في الجلسة
if (!isset($_SESSION['order_number'])) {
    redirect('index.php');
}

$order_number = $_SESSION['order_number'];
$payment_success = isset($_GET['payment_success']);

// مسح رقم الطلب من الجلسة بعد العرض
unset($_SESSION['order_number']);
unset($_SESSION['order_id']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الطلب - مطبعة برنتلي</title>
    
    <style>
        :root {
            --primary-color: #00adef;
            --primary-hover-color: #c4a130;
            --secondary-color: #343a40;
            --success-color: #28a745;
            --light-gray-color: #f8f9fa;
            --text-color: #333;
            --border-color: #dee2e6;
            --border-radius: 0.75rem;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f9f9f9;
            margin-top: 120px;
        }

        .confirmation-section {
            padding: 4rem 0;
            min-height: 70vh;
            display: flex;
            align-items: center;
        }

        .success-card {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .success-title {
            color: var(--success-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .success-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .order-details {
            background: var(--light-gray-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin: 2rem 0;
            border: 2px solid var(--primary-color);
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: monospace;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            margin: 0.5rem;
        }

        .status-pending {
            background: #00adef;
            color: #000;
        }

        .status-uploaded {
            background: var(--success-color);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover-color);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--secondary-color);
            border: 2px solid var(--border-color);
        }

        .btn-outline:hover {
            background: var(--secondary-color);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .info-text {
            color: #666;
            font-size: 0.95rem;
            margin-top: 1.5rem;
        }

        @media (max-width: 768px) {
            .success-card {
                padding: 2rem 1.5rem;
                margin: 0 1rem;
            }
            
            .success-title {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>
    <section class="confirmation-section">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <?php if ($payment_success): ?>
                    <h1 class="success-title">تم استلام طلبك وإثبات الدفع بنجاح!</h1>
                    <p class="success-message">
                        شكراً لك على ثقتك في مطبعة برنتلي. تم استلام طلبك وإثبات الدفع بنجاح. 
                        سنقوم بمراجعة الدفع والبدء في تنفيذ طلبك في أقرب وقت ممكن.
                    </p>
                <?php else: ?>
                    <h1 class="success-title">تم إنشاء طلبك بنجاح!</h1>
                    <p class="success-message">
                        شكراً لك على ثقتك في مطبعة برنتلي. تم إنشاء طلبك بنجاح وهو الآن في انتظار الدفع.
                    </p>
                <?php endif; ?>

                <div class="order-details">
                    <div class="order-number">
                        رقم الطلب: <?php echo htmlspecialchars($order_number); ?>
                    </div>
                    
                    <div>
                        <span class="status-badge <?php echo $payment_success ? 'status-uploaded' : 'status-pending'; ?>">
                            <?php if ($payment_success): ?>
                                <i class="fas fa-check me-2"></i>تم استلام إثبات الدفع
                            <?php else: ?>
                                <i class="fas fa-clock me-2"></i>في انتظار الدفع
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-home me-2"></i>
                        العودة للرئيسية
                    </a>
                    <a href="services.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>
                        استكشاف خدمات أخرى
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="account.php" class="btn btn-outline">
                            <i class="fas fa-user me-2"></i>
                            متابعة طلباتي
                        </a>
                    <?php endif; ?>
                </div>

                <div class="info-text">
                    <p>
                        <i class="fas fa-info-circle me-2"></i>
                        <?php if ($payment_success): ?>
                            سيتم التواصل معك قريباً لتأكيد تفاصيل الطلب وموعد التسليم.
                        <?php else: ?>
                            يمكنك متابعة حالة طلبك من خلال حسابك الشخصي أو التواصل معنا مباشرة.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>

