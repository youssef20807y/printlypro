<?php
// service-details.php
require_once 'includes/config.php';

// الحصول على معرف الخدمة من URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب بيانات الخدمة من قاعدة البيانات
$service = [];
$related_services = [];
$portfolio_items = [];

if ($service_id > 0) {
    // جلب بيانات الخدمة الرئيسية
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ? AND status = 'active'");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($service) {
        // جلب خدمات ذات صلة (نفس الفئة)
        $stmt = $pdo->prepare("SELECT * FROM services WHERE category = ? AND service_id != ? AND status = 'active' LIMIT 4");
        $stmt->execute([$service['category'], $service_id]);
        $related_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // جلب أعمال معرض متعلقة بهذه الخدمة
        $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE service_id = ? ORDER BY created_at DESC LIMIT 6");
        $stmt->execute([$service_id]);
        $portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// إذا لم يتم العثور على الخدمة
if (!$service) {
    header("Location: services.php");
    exit();
}

// معالجة إضافة إلى السلة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['cart_session'])) {
        $_SESSION['cart_session'] = uniqid();
    }
    
    $cart_data = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'session_id' => $_SESSION['cart_session'] ?? null,
        'service_id' => $service_id,
        'quantity' => intval($_POST['quantity']),
        'paper_type' => $_POST['paper_type'] ?? null,
        'size' => $_POST['size'] ?? null,
        'colors' => intval($_POST['colors']),
        'design_file' => null,
        'notes' => $_POST['notes'] ?? null,
        'price' => calculate_service_price($service_id, $_POST) // دالة لحساب السعر حسب الخيارات
    ];
    
    // رفع ملف التصميم إذا تم تحميله
    if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/designs/';
        $file_name = uniqid() . '_' . basename($_FILES['design_file']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['design_file']['tmp_name'], $target_file)) {
            $cart_data['design_file'] = $file_name;
        }
    }
    
    // إضافة إلى السلة
    add_to_cart($cart_data);
    $_SESSION['success_message'] = "تمت إضافة الخدمة إلى سلة التسوق بنجاح";
    header("Location: cart.php");
    exit();
}

// دالة مساعدة لحساب سعر الخدمة حسب الخيارات
function calculate_service_price($service_id, $options) {
    global $pdo;
    // يمكنك تطوير هذه الدالة حسب احتياجاتك
    $base_price = 50; // سعر افتراضي
    return $base_price * intval($options['quantity']);
}

// دالة مساعدة لإضافة عنصر إلى السلة
function add_to_cart($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, session_id, service_id, quantity, paper_type, size, colors, design_file, notes, price) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['user_id'],
        $data['session_id'],
        $data['service_id'],
        $data['quantity'],
        $data['paper_type'],
        $data['size'],
        $data['colors'],
        $data['design_file'],
        $data['notes'],
        $data['price']
    ]);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['name']); ?> - مطبعة برنتلي</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- شريط التنقل -->
    <?php include 'includes/header.php'; ?>

    <!-- عنوان الصفحة -->
    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h1><?php echo htmlspecialchars($service['name']); ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="services.php">خدماتنا</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($service['name']); ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- تفاصيل الخدمة -->
    <section class="service-details py-5">
        <div class="container">
            <div class="row">
                <!-- صورة الخدمة -->
                <div class="col-lg-6 mb-4">
                    <div class="service-image">
                        <img src="uploads/services/<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="img-fluid rounded">
                    </div>
                    
                    <!-- معرض الأعمال المتعلقة بالخدمة -->
                    <?php if (!empty($portfolio_items)): ?>
                    <div class="portfolio-gallery mt-4">
                        <h4 class="mb-3">أعمالنا في <?php echo htmlspecialchars($service['name']); ?></h4>
                        <div class="row">
                            <?php foreach ($portfolio_items as $item): ?>
                            <div class="col-4 col-md-3 mb-3">
                                <a href="uploads/portfolio/<?php echo htmlspecialchars($item['image']); ?>" data-lightbox="portfolio">
                                    <img src="uploads/portfolio/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="img-thumbnail">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- معلومات الخدمة -->
                <div class="col-lg-6">
                    <div class="service-info">
                        <h2 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h2>
                        
                        <div class="service-meta mb-4">
                            <?php if ($service['price_start'] && $service['price_end']): ?>
                            <span class="price-badge">
                                تبدأ الأسعار من <?php echo number_format($service['price_start'], 2); ?> ر.س 
                                إلى <?php echo number_format($service['price_end'], 2); ?> ر.س
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($service['is_featured']): ?>
                            <span class="featured-badge">مميز</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-description mb-4">
                            <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                        </div>
                        
                        <!-- خصائص الخدمة -->
                        <div class="service-features mb-4">
                            <h5>مميزات الخدمة:</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i> جودة طباعة عالية</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> خيارات متعددة للورق</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> تسليم في الوقت المحدد</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> دعم فني متكامل</li>
                            </ul>
                        </div>
                        
                        <!-- نموذج الطلب -->
                        <div class="order-form card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">طلب الخدمة</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="quantity" class="form-label">الكمية</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="paper_type" class="form-label">نوع الورق</label>
                                            <select class="form-select" id="paper_type" name="paper_type" required>
                                                <option value="glossy">لماع</option>
                                                <option value="matte" selected>غير لامع</option>
                                                <option value="cardstock">كرتون</option>
                                                <option value="recycled">معاد تدويره</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="size" class="form-label">الحجم</label>
                                            <select class="form-select" id="size" name="size" required>
                                                <option value="A4">A4</option>
                                                <option value="A5">A5</option>
                                                <option value="A3">A3</option>
                                                <option value="custom">مخصص</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="colors" class="form-label">عدد الألوان</label>
                                            <select class="form-select" id="colors" name="colors" required>
                                                <option value="1">أحادي اللون</option>
                                                <option value="2">لونان</option>
                                                <option value="4" selected>4 ألوان (CMYK)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="design_file" class="form-label">رفع ملف التصميم</label>
                                        <input class="form-control" type="file" id="design_file" name="design_file" accept=".pdf,.ai,.psd,.jpg,.png">
                                        <small class="text-muted">الامتدادات المسموحة: PDF, AI, PSD, JPG, PNG</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">ملاحظات إضافية</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                            <i class="fas fa-shopping-cart me-2"></i> أضف إلى السلة
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- معلومات إضافية عن الخدمة -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="service-tabs">
                        <ul class="nav nav-tabs" id="serviceTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">تفاصيل إضافية</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">المواصفات</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="delivery-tab" data-bs-toggle="tab" data-bs-target="#delivery" type="button" role="tab">التسليم</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="faq-tab" data-bs-toggle="tab" data-bs-target="#faq" type="button" role="tab">أسئلة شائعة</button>
                            </li>
                        </ul>
                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="serviceTabsContent">
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <h5>معلومات مفصلة عن الخدمة</h5>
                                <p>هنا يمكنك إضافة وصف مفصل للخدمة، خطوات العمل، المواد المستخدمة، ومعلومات أخرى تهم العميل.</p>
                                <p>يمكنك استخدام HTML لعرض المعلومات بطريقة منظمة مع عناوين فرعية، قوائم، وجداول إذا لزم الأمر.</p>
                            </div>
                            <div class="tab-pane fade" id="specs" role="tabpanel">
                                <h5>المواصفات الفنية</h5>
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th width="30%">نوع الطباعة</th>
                                            <td>أوفست / ديجيتال</td>
                                        </tr>
                                        <tr>
                                            <th>أحجام متاحة</th>
                                            <td>A3, A4, A5, A6</td>
                                        </tr>
                                        <tr>
                                            <th>خيارات الورق</th>
                                            <td>لماع، غير لامع، كرتون، معاد تدويره</td>
                                        </tr>
                                        <tr>
                                            <th>وقت التسليم</th>
                                            <td>2-5 أيام عمل حسب الكمية</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="delivery" role="tabpanel">
                                <h5>سياسة التسليم</h5>
                                <p>نقدم خدمة التوصيل لكافة أنحاء المملكة العربية السعودية:</p>
                                <ul>
                                    <li>الرياض: توصيل خلال 24-48 ساعة</li>
                                    <li>جدة، الدمام، الخبر: توصيل خلال 2-3 أيام</li>
                                    <li>باقي المدن: توصيل خلال 3-5 أيام</li>
                                </ul>
                                <p>يمكنك اختيار الاستلام من مقرنا في الرياض إذا كنت تفضل ذلك.</p>
                            </div>
                            <div class="tab-pane fade" id="faq" role="tabpanel">
                                <h5>أسئلة شائعة حول هذه الخدمة</h5>
                                <div class="accordion" id="faqAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq1">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                                ما هي الملفات المطلوبة للطباعة؟
                                            </button>
                                        </h2>
                                        <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1">
                                            <div class="accordion-body">
                                                نفضل استلام الملفات بصيغة PDF مع وجود هوامش قص لا تقل عن 3مم. كما نقبل ملفات AI, PSD, JPG, PNG بشرط أن تكون بدقة عالية (300DPI على الأقل).
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq2">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                                هل تقدمون خدمة التصميم؟
                                            </button>
                                        </h2>
                                        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2">
                                            <div class="accordion-body">
                                                نعم، لدينا فريق تصميم محترف يمكنه مساعدتك في إنشاء التصميم المطلوب حسب احتياجاتك. يرجى التواصل معنا لمعرفة التفاصيل والأسعار.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- خدمات ذات صلة -->
            <?php if (!empty($related_services)): ?>
            <div class="related-services mt-5">
                <div class="section-header mb-4">
                    <h3 class="section-title">خدمات ذات صلة</h3>
                    <a href="services.php" class="btn btn-outline-primary">عرض جميع الخدمات</a>
                </div>
                
                <div class="row">
                    <?php foreach ($related_services as $related): ?>
                    <div class="col-md-3 mb-4">
                        <div class="service-card card h-100">
                            <img src="uploads/services/<?php echo htmlspecialchars($related['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                <p class="card-text text-muted"><?php echo mb_substr($related['description'], 0, 100); ?>...</p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="service-details.php?id=<?php echo $related['service_id']; ?>" class="btn btn-sm btn-primary">تفاصيل الخدمة</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- تذييل الصفحة -->
    <?php include 'includes/footer.php'; ?>

    <!-- الأكواد البرمجية الجافاسكريبت -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/lightbox.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>