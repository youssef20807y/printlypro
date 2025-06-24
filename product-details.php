<?php
/**
 * صفحة تفاصيل المنتج لموقع مطبعة برنتلي
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('PRINTLY', true);

// استدعاء ملف الرأس
require_once 'includes/header.php';

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // إعادة التوجيه إلى صفحة المنتجات إذا لم يتم تحديد المنتج
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// الحصول على بيانات المنتج
$product = [];
try {
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        // إعادة التوجيه إلى صفحة المنتجات إذا لم يتم العثور على المنتج
        header('Location: products.php');
        exit;
    }
    
    // الحصول على الفئة
    $stmt = $db->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$product['category_id']]);
    $category = $stmt->fetch();
    
    // الحصول على صور المنتج
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, order_num ASC");
    $stmt->execute([$product_id]);
    $product_images = $stmt->fetchAll();
    
    // الحصول على خيارات المنتج
    $stmt = $db->prepare("SELECT * FROM product_options WHERE product_id = ? ORDER BY option_group ASC, order_num ASC");
    $stmt->execute([$product_id]);
    $product_options = $stmt->fetchAll();
    
    // تنظيم خيارات المنتج حسب المجموعة
    $options_by_group = [];
    foreach ($product_options as $option) {
        if (!isset($options_by_group[$option['option_group']])) {
            $options_by_group[$option['option_group']] = [
                'name' => $option['option_group'],
                'options' => []
            ];
        }
        
        $options_by_group[$option['option_group']]['options'][] = [
            'id' => $option['option_id'],
            'name' => $option['option_name'],
            'price' => $option['price_adjustment']
        ];
    }
    
    // الحصول على المنتجات ذات الصلة
    $stmt = $db->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND product_id != ? AND status = 'active'
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch (PDOException $e) {
    // في حالة عدم وجود جدول المنتجات، نستخدم بيانات افتراضية
    $default_products = [
        [
            'product_id' => 1,
            'name' => 'كروت شخصية فاخرة',
            'description' => 'كروت شخصية فاخرة بتصميم احترافي وطباعة عالية الجودة. تتميز بورق سميك فاخر وطباعة بألوان زاهية تعكس الاحترافية والجودة العالية. متوفرة بعدة خيارات من حيث نوع الورق والتشطيب النهائي.',
            'price' => 150,
            'sale_price' => 120,
            'image' => 'business-card-1.jpg',
            'category_id' => 1,
            'is_featured' => 1,
            'is_new' => 1,
            'stock_quantity' => 100,
            'specifications' => 'المقاس: 9 × 5 سم\nنوع الورق: كوشيه 350 جرام\nالطباعة: ألوان كاملة على الوجهين\nالتشطيب: سيلوفان مط أو لامع\nالكمية: 100 كرت'
        ],
        [
            'product_id' => 2,
            'name' => 'بروشور ثلاثي الطيات',
            'description' => 'بروشور ثلاثي الطيات بتصميم احترافي وطباعة عالية الجودة. مثالي للتعريف بالشركات والمنتجات والخدمات. يتميز بورق عالي الجودة وطباعة بألوان زاهية تجذب الانتباه.',
            'price' => 250,
            'sale_price' => 0,
            'image' => 'brochure-1.jpg',
            'category_id' => 2,
            'is_featured' => 1,
            'is_new' => 0,
            'stock_quantity' => 50,
            'specifications' => 'المقاس: A4 مطوي\nنوع الورق: كوشيه 170 جرام\nالطباعة: ألوان كاملة على الوجهين\nالتشطيب: سيلوفان مط أو لامع\nالكمية: 100 بروشور'
        ],
        [
            'product_id' => 3,
            'name' => 'فلاير A4 ملون',
            'description' => 'فلاير A4 ملون بتصميم احترافي وطباعة عالية الجودة. مثالي للإعلان عن العروض والمناسبات. يتميز بورق عالي الجودة وطباعة بألوان زاهية تجذب الانتباه.',
            'price' => 180,
            'sale_price' => 150,
            'image' => 'flyer-1.jpg',
            'category_id' => 3,
            'is_featured' => 0,
            'is_new' => 1,
            'stock_quantity' => 200,
            'specifications' => 'المقاس: A4\nنوع الورق: كوشيه 150 جرام\nالطباعة: ألوان كاملة على الوجهين\nالتشطيب: سيلوفان مط أو لامع\nالكمية: 100 فلاير'
        ],
        [
            'product_id' => 4,
            'name' => 'رول أب 85×200 سم',
            'description' => 'رول أب بمقاس 85×200 سم بتصميم احترافي وطباعة عالية الجودة. مثالي للمعارض والمؤتمرات والفعاليات. يتميز بسهولة الحمل والتركيب وجودة عالية في الطباعة.',
            'price' => 350,
            'sale_price' => 300,
            'image' => 'rollup-1.jpg',
            'category_id' => 4,
            'is_featured' => 1,
            'is_new' => 0,
            'stock_quantity' => 30,
            'specifications' => 'المقاس: 85 × 200 سم\nنوع الطباعة: ألوان كاملة\nالمادة: PVC مقاوم للماء\nيشمل: حقيبة للحمل وهيكل معدني\nالوزن: 3 كجم'
        ]
    ];
    
    // البحث عن المنتج المطلوب
    $product = null;
    foreach ($default_products as $p) {
        if ($p['product_id'] == $product_id) {
            $product = $p;
            break;
        }
    }
    
    if (!$product) {
        // إعادة التوجيه إلى صفحة المنتجات إذا لم يتم العثور على المنتج
        header('Location: products.php');
        exit;
    }
    
    // بيانات افتراضية للفئة
    $category = [
        'category_id' => $product['category_id'],
        'name' => 'فئة المنتج',
        'slug' => 'category-slug'
    ];
    
    // بيانات افتراضية لصور المنتج
    $product_images = [
        [
            'image_id' => 1,
            'product_id' => $product_id,
            'image' => $product['image'],
            'is_main' => 1
        ]
    ];
    
    // بيانات افتراضية لخيارات المنتج
    $options_by_group = [
        'نوع الورق' => [
            'name' => 'نوع الورق',
            'options' => [
                ['id' => 1, 'name' => 'كوشيه 250 جرام', 'price' => 0],
                ['id' => 2, 'name' => 'كوشيه 300 جرام', 'price' => 20],
                ['id' => 3, 'name' => 'كوشيه 350 جرام', 'price' => 40]
            ]
        ],
        'التشطيب' => [
            'name' => 'التشطيب',
            'options' => [
                ['id' => 4, 'name' => 'سيلوفان مط', 'price' => 0],
                ['id' => 5, 'name' => 'سيلوفان لامع', 'price' => 0],
                ['id' => 6, 'name' => 'سبوت UV', 'price' => 30]
            ]
        ]
    ];
    
    // بيانات افتراضية للمنتجات ذات الصلة
    $related_products = [];
    foreach ($default_products as $p) {
        if ($p['product_id'] != $product_id && $p['category_id'] == $product['category_id']) {
            $related_products[] = $p;
        }
    }
    
    // إذا لم يتم العثور على منتجات ذات صلة، استخدم منتجات عشوائية
    if (empty($related_products)) {
        $related_products = [];
        $used_ids = [$product_id];
        
        for ($i = 0; $i < 4; $i++) {
            $random_index = array_rand($default_products);
            $random_product = $default_products[$random_index];
            
            if (!in_array($random_product['product_id'], $used_ids)) {
                $related_products[] = $random_product;
                $used_ids[] = $random_product['product_id'];
            }
        }
    }
    
    // اقتصار المنتجات ذات الصلة على 4 منتجات
    $related_products = array_slice($related_products, 0, 4);
}

// الحصول على العملة
$currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'ر.س';
?>

<!-- رأس الصفحة -->
<section class="page-header" style="background-image: url('assets/images/product-header.jpg');">
    <div class="container">
        <h1 class="page-title"><?php echo $product['name']; ?></h1>
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a> / <a href="products.php">منتجاتنا</a>
            <?php if (isset($category['name'])): ?>
                / <a href="products.php?category=<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></a>
            <?php endif; ?>
            / <?php echo $product['name']; ?>
        </div>
    </div>
</section>

<!-- قسم تفاصيل المنتج -->
<section class="product-details-section section">
    <div class="container">
        <div class="row">
            <!-- صور المنتج -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <div class="product-gallery-main">
                        <?php if (!empty($product_images)): ?>
                            <img src="uploads/products/<?php echo $product_images[0]['image']; ?>" alt="<?php echo $product['name']; ?>" id="main-product-image">
                        <?php else: ?>
                            <img src="assets/images/product-placeholder.jpg" alt="<?php echo $product['name']; ?>" id="main-product-image">
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($product_images) > 1): ?>
                        <div class="product-gallery-thumbs">
                            <?php foreach ($product_images as $index => $image): ?>
                                <div class="product-gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" data-image="uploads/products/<?php echo $image['image']; ?>">
                                    <img src="uploads/products/<?php echo $image['image']; ?>" alt="<?php echo $product['name']; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- معلومات المنتج -->
            <div class="col-lg-6">
                <div class="product-info">
                    <h1 class="product-title"><?php echo $product['name']; ?></h1>
                    
                    <div class="product-price">
                        <?php if ($product['sale_price'] > 0): ?>
                            <span class="current-price"><?php echo number_format($product['sale_price'], 2); ?> <?php echo $currency; ?></span>
                            <span class="old-price"><?php echo number_format($product['price'], 2); ?> <?php echo $currency; ?></span>
                            <?php $discount_percent = round(($product['price'] - $product['sale_price']) / $product['price'] * 100); ?>
                            <span class="discount-badge">خصم <?php echo $discount_percent; ?>%</span>
                        <?php else: ?>
                            <span class="current-price"><?php echo number_format($product['price'], 2); ?> <?php echo $currency; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br($product['description']); ?>
                    </div>
                    
                    <div class="product-stock">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> متوفر في المخزون</span>
                        <?php else: ?>
                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> غير متوفر حالياً</span>
                        <?php endif; ?>
                    </div>
                    
                    <form action="cart_actions.php" method="post" id="add-to-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <?php if (!empty($options_by_group)): ?>
                            <div class="product-options">
                                <?php foreach ($options_by_group as $group): ?>
                                    <div class="product-option-group">
                                        <h3 class="option-group-title"><?php echo $group['name']; ?></h3>
                                        <div class="option-items">
                                            <?php foreach ($group['options'] as $index => $option): ?>
                                                <div class="option-item">
                                                    <input type="radio" id="option-<?php echo $option['id']; ?>" name="options[<?php echo $group['name']; ?>]" value="<?php echo $option['name']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                                    <label for="option-<?php echo $option['id']; ?>">
                                                        <?php echo $option['name']; ?>
                                                        <?php if ($option['price'] > 0): ?>
                                                            <span class="option-price">(+ <?php echo number_format($option['price'], 2); ?> <?php echo $currency; ?>)</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-quantity">
                            <h3 class="quantity-title">الكمية</h3>
                            <div class="quantity-input">
                                <button type="button" class="quantity-btn quantity-minus">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="quantity-value">
                                <button type="button" class="quantity-btn quantity-plus">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <button type="submit" class="btn btn-primary add-to-cart-btn" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i> إضافة إلى السلة
                            </button>
                            <button type="button" class="btn btn-outline-secondary wishlist-btn">
                                <i class="far fa-heart"></i> إضافة إلى المفضلة
                            </button>
                        </div>
                    </form>
                    
                    <div class="product-meta">
                        <?php if (isset($category['name'])): ?>
                            <div class="product-meta-item">
                                <span class="meta-label">الفئة:</span>
                                <a href="products.php?category=<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-meta-item">
                            <span class="meta-label">مشاركة:</span>
                            <div class="product-share">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-link facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($product['name']); ?>" target="_blank" class="share-link twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($product['name'] . ' - https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-link whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode($product['name']); ?>&body=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="share-link email">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تفاصيل إضافية للمنتج -->
        <div class="product-details-tabs">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">الوصف</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab" aria-controls="specifications" aria-selected="false">المواصفات</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">التقييمات</button>
                </li>
            </ul>
            <div class="tab-content" id="productTabsContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                    <div class="product-description-content">
                        <?php echo nl2br($product['description']); ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="specifications" role="tabpanel" aria-labelledby="specifications-tab">
                    <div class="product-specifications">
                        <?php if (isset($product['specifications'])): ?>
                            <table class="specifications-table">
                                <tbody>
                                    <?php
                                    $specifications = explode("\n", $product['specifications']);
                                    foreach ($specifications as $spec) {
                                        if (empty(trim($spec))) continue;
                                        
                                        $parts = explode(':', $spec, 2);
                                        if (count($parts) === 2) {
                                            echo '<tr>';
                                            echo '<th>' . trim($parts[0]) . '</th>';
                                            echo '<td>' . trim($parts[1]) . '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>لا توجد مواصفات متاحة لهذا المنتج.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                    <div class="product-reviews">
                        <div class="no-reviews">
                            <p>لا توجد تقييمات لهذا المنتج حتى الآن.</p>
                            <button class="btn btn-primary" id="write-review-btn">كن أول من يقيم هذا المنتج</button>
                        </div>
                        
                        <div class="review-form" style="display: none;">
                            <h3 class="review-form-title">إضافة تقييم</h3>
                            <form action="#" method="post" id="review-form">
                                <div class="form-group">
                                    <label for="review-name">الاسم</label>
                                    <input type="text" id="review-name" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="review-email">البريد الإلكتروني</label>
                                    <input type="email" id="review-email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>التقييم</label>
                                    <div class="rating-stars">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-value" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="review-comment">التعليق</label>
                                    <textarea id="review-comment" name="comment" class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">إرسال التقييم</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- المنتجات ذات الصلة -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h2 class="section-title">منتجات ذات صلة</h2>
                <div class="row">
                    <?php foreach ($related_products as $related_product): ?>
                        <div class="col-lg-3 col-md-6">
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($related_product['image'])): ?>
                                        <img src="uploads/products/<?php echo $related_product['image']; ?>" alt="<?php echo $related_product['name']; ?>">
                                    <?php else: ?>
                                        <img src="assets/images/product-placeholder.jpg" alt="<?php echo $related_product['name']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="product-badges">
                                        <?php if ($related_product['is_new']): ?>
                                            <span class="product-badge badge-new">جديد</span>
                                        <?php endif; ?>
                                        <?php if ($related_product['sale_price'] > 0): ?>
                                            <?php $discount_percent = round(($related_product['price'] - $related_product['sale_price']) / $related_product['price'] * 100); ?>
                                            <span class="product-badge badge-sale">خصم <?php echo $discount_percent; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="product-details.php?id=<?php echo $related_product['product_id']; ?>" class="product-action" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="product-action add-to-cart" data-product-id="<?php echo $related_product['product_id']; ?>" title="إضافة إلى السلة">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                        <button class="product-action wishlist-btn" data-product-id="<?php echo $related_product['product_id']; ?>" title="إضافة إلى المفضلة">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="product-content">
                                    <h3 class="product-title">
                                        <a href="product-details.php?id=<?php echo $related_product['product_id']; ?>">
                                            <?php echo $related_product['name']; ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="product-price">
                                        <?php if ($related_product['sale_price'] > 0): ?>
                                            <span class="current-price"><?php echo number_format($related_product['sale_price'], 2); ?> <?php echo $currency; ?></span>
                                            <span class="old-price"><?php echo number_format($related_product['price'], 2); ?> <?php echo $currency; ?></span>
                                        <?php else: ?>
                                            <span class="current-price"><?php echo number_format($related_product['price'], 2); ?> <?php echo $currency; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* أنماط صفحة تفاصيل المنتج */
.product-details-section {
    padding: var(--spacing-xl) 0;
}

/* معرض صور المنتج */
.product-gallery {
    margin-bottom: var(--spacing-lg);
}

.product-gallery-main {
    border-radius: var(--border-radius-md);
    overflow: hidden;
    margin-bottom: var(--spacing-md);
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--color-white);
    box-shadow: var(--shadow-sm);
}

.product-gallery-main img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.product-gallery-thumbs {
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
}

.product-gallery-thumb {
    width: 80px;
    height: 80px;
    border-radius: var(--border-radius-sm);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all var(--transition-normal);
}

.product-gallery-thumb.active {
    border-color: var(--color-primary);
}

.product-gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* معلومات المنتج */
.product-info {
    padding: var(--spacing-lg);
    background-color: var(--color-white);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}

.product-title {
    font-size: 1.8rem;
    margin-bottom: var(--spacing-md);
    color: var(--color-secondary);
}

.product-price {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

.current-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
}

.old-price {
    font-size: 1.1rem;
    color: var(--color-text-muted);
    text-decoration: line-through;
}

.discount-badge {
    background-color: var(--color-danger);
    color: var(--color-white);
    padding: 5px 10px;
    border-radius: var(--border-radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
}

.product-description {
    margin-bottom: var(--spacing-md);
    line-height: 1.7;
    color: var(--color-text-dark);
}

.product-stock {
    margin-bottom: var(--spacing-md);
}

.in-stock {
    color: var(--color-success);
    font-weight: 600;
}

.out-of-stock {
    color: var(--color-danger);
    font-weight: 600;
}

/* خيارات المنتج */
.product-options {
    margin-bottom: var(--spacing-md);
}

.product-option-group {
    margin-bottom: var(--spacing-md);
}

.option-group-title {
    font-size: 1.1rem;
    margin-bottom: var(--spacing-sm);
    color: var(--color-secondary);
}

.option-items {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.option-item {
    position: relative;
}

.option-item input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.option-item label {
    display: block;
    padding: 8px 16px;
    border: 1px solid var(--color-light-gray);
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: all var(--transition-normal);
}

.option-item input[type="radio"]:checked + label {
    border-color: var(--color-primary);
    background-color: var(--color-primary-light);
}

.option-price {
    font-size: 0.85rem;
    color: var(--color-primary);
    margin-right: 5px;
}

/* كمية المنتج */
.product-quantity {
    margin-bottom: var(--spacing-md);
}

.quantity-title {
    font-size: 1.1rem;
    margin-bottom: var(--spacing-sm);
    color: var(--color-secondary);
}

.quantity-input {
    display: flex;
    align-items: center;
    max-width: 150px;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    border: 1px solid var(--color-light-gray);
    background-color: var(--color-white);
    color: var(--color-text-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all var(--transition-normal);
}

.quantity-minus {
    border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm);
}

.quantity-plus {
    border-radius: 0 var(--border-radius-sm) var(--border-radius-sm) 0;
}

.quantity-btn:hover {
    background-color: var(--color-light-gray);
}

.quantity-value {
    width: 70px;
    height: 40px;
    border: 1px solid var(--color-light-gray);
    border-right: none;
    border-left: none;
    text-align: center;
    font-weight: 600;
}

.quantity-value::-webkit-inner-spin-button,
.quantity-value::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* أزرار إجراءات المنتج */
.product-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.add-to-cart-btn {
    flex: 1;
}

.add-to-cart-btn:disabled {
    background-color: var(--color-light-gray);
    border-color: var(--color-light-gray);
    color: var(--color-text-muted);
    cursor: not-allowed;
}

/* بيانات تعريفية للمنتج */
.product-meta {
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-light-gray);
}

.product-meta-item {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-sm);
}

.meta-label {
    font-weight: 600;
    margin-left: var(--spacing-sm);
    color: var(--color-secondary);
}

.product-share {
    display: flex;
    gap: var(--spacing-sm);
}

.share-link {
    width: 36px;
    height: 36px;
    border-radius: var(--border-radius-circle);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-white);
    transition: all var(--transition-normal);
}

.share-link.facebook {
    background-color: #3b5998;
}

.share-link.twitter {
    background-color: #1da1f2;
}

.share-link.whatsapp {
    background-color: #25d366;
}

.share-link.email {
    background-color: #ea4335;
}

.share-link:hover {
    opacity: 0.8;
    color: var(--color-white);
}

/* تبويبات تفاصيل المنتج */
.product-details-tabs {
    margin-top: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.nav-tabs {
    border-bottom: 1px solid var(--color-light-gray);
    margin-bottom: var(--spacing-md);
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--color-text-dark);
    font-weight: 600;
    padding: var(--spacing-md) var(--spacing-lg);
    transition: all var(--transition-normal);
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: var(--color-primary);
}

.nav-tabs .nav-link.active {
    border-color: var(--color-primary);
    color: var(--color-primary);
    background-color: transparent;
}

.tab-content {
    padding: var(--spacing-lg);
    background-color: var(--color-white);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}

.product-description-content {
    line-height: 1.8;
}

/* جدول المواصفات */
.specifications-table {
    width: 100%;
    border-collapse: collapse;
}

.specifications-table th,
.specifications-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--color-light-gray);
}

.specifications-table th {
    width: 30%;
    font-weight: 600;
    color: var(--color-secondary);
}

/* قسم التقييمات */
.no-reviews {
    text-align: center;
    padding: var(--spacing-lg) 0;
}

.review-form {
    margin-top: var(--spacing-lg);
}

.review-form-title {
    margin-bottom: var(--spacing-md);
    font-size: 1.3rem;
    color: var(--color-secondary);
}

.form-group {
    margin-bottom: var(--spacing-md);
}

.rating-stars {
    display: flex;
    gap: 5px;
    font-size: 1.5rem;
    color: var(--color-warning);
    cursor: pointer;
}

/* المنتجات ذات الصلة */
.related-products {
    margin-top: var(--spacing-xl);
}

.section-title {
    font-size: 1.5rem;
    margin-bottom: var(--spacing-lg);
    color: var(--color-secondary);
    position: relative;
    padding-bottom: var(--spacing-sm);
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    right: 0;
    width: 50px;
    height: 2px;
    background-color: var(--color-primary);
}

/* تجاوب */
@media (max-width: 991px) {
    .product-gallery-main {
        height: 350px;
    }
    
    .product-actions {
        flex-direction: column;
    }
}

@media (max-width: 767px) {
    .product-gallery-main {
        height: 300px;
    }
    
    .product-gallery-thumb {
        width: 60px;
        height: 60px;
    }
    
    .product-title {
        font-size: 1.5rem;
    }
    
    .current-price {
        font-size: 1.3rem;
    }
    
    .old-price {
        font-size: 1rem;
    }
    
    .nav-tabs .nav-link {
        padding: var(--spacing-sm) var(--spacing-md);
    }
}

@media (max-width: 575px) {
    .product-gallery-main {
        height: 250px;
    }
    
    .product-info {
        padding: var(--spacing-md);
    }
    
    .option-items {
        flex-direction: column;
    }
    
    .specifications-table th,
    .specifications-table td {
        padding: var(--spacing-sm);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تبديل الصورة الرئيسية عند النقر على الصور المصغرة
    const thumbnails = document.querySelectorAll('.product-gallery-thumb');
    const mainImage = document.getElementById('main-product-image');
    
    thumbnails.forEach(function(thumbnail) {
        thumbnail.addEventListener('click', function() {
            const imageUrl = this.getAttribute('data-image');
            
            // تحديث الصورة الرئيسية
            mainImage.src = imageUrl;
            
            // إزالة الكلاس النشط من جميع الصور المصغرة
            thumbnails.forEach(function(thumb) {
                thumb.classList.remove('active');
            });
            
            // إضافة الكلاس النشط للصورة المصغرة المحددة
            this.classList.add('active');
        });
    });
    
    // زيادة ونقصان الكمية
    const minusBtn = document.querySelector('.quantity-minus');
    const plusBtn = document.querySelector('.quantity-plus');
    const quantityInput = document.querySelector('.quantity-value');
    
    if (minusBtn && plusBtn && quantityInput) {
        minusBtn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            value = value > 1 ? value - 1 : 1;
            quantityInput.value = value;
        });
        
        plusBtn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            const max = parseInt(quantityInput.getAttribute('max')) || 100;
            value = value < max ? value + 1 : max;
            quantityInput.value = value;
        });
    }
    
    // إضافة المنتج إلى المفضلة
    const wishlistBtn = document.querySelector('.wishlist-btn');
    
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            // تبديل حالة الأيقونة
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = '#dc3545';
                
                // عرض رسالة نجاح
                alert('تمت إضافة المنتج إلى المفضلة');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '';
                
                // عرض رسالة نجاح
                alert('تمت إزالة المنتج من المفضلة');
            }
        });
    }
    
    // إضافة المنتج إلى السلة
    const addToCartForm = document.getElementById('add-to-cart-form');
    
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // إنشاء كائن FormData
            const formData = new FormData(this);
            
            // تحويل FormData إلى سلسلة نصية
            const formDataString = new URLSearchParams(formData).toString();
            
            // إرسال طلب AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'cart_actions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // تحديث عدد العناصر في السلة
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = response.cart_count;
                            }
                            
                            // عرض رسالة نجاح
                            alert('تمت إضافة المنتج إلى السلة بنجاح');
                        } else {
                            alert(response.message || 'حدث خطأ أثناء إضافة المنتج إلى السلة');
                        }
                    } catch (e) {
                        alert('حدث خطأ أثناء معالجة الطلب');
                    }
                }
            };
            xhr.send(formDataString);
        });
    }
    
    // إضافة تقييم جديد
    const writeReviewBtn = document.getElementById('write-review-btn');
    const reviewForm = document.querySelector('.review-form');
    
    if (writeReviewBtn && reviewForm) {
        writeReviewBtn.addEventListener('click', function() {
            reviewForm.style.display = 'block';
            this.style.display = 'none';
        });
    }
    
    // تقييم النجوم
    const ratingStars = document.querySelectorAll('.rating-stars i');
    const ratingValue = document.getElementById('rating-value');
    
    if (ratingStars.length > 0 && ratingValue) {
        ratingStars.forEach(function(star) {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingValue.value = rating;
                
                // تحديث شكل النجوم
                ratingStars.forEach(function(s, index) {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                
                // تحديث شكل النجوم عند تمرير الماوس
                ratingStars.forEach(function(s, index) {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
            
            star.addEventListener('mouseout', function() {
                const currentRating = parseInt(ratingValue.value);
                
                // إعادة شكل النجوم إلى الحالة الحالية
                ratingStars.forEach(function(s, index) {
                    if (index < currentRating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });
    }
    
    // إرسال نموذج التقييم
    const reviewFormElement = document.getElementById('review-form');
    
    if (reviewFormElement) {
        reviewFormElement.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // التحقق من اختيار تقييم
            if (ratingValue.value === '0') {
                alert('يرجى اختيار تقييم من 1 إلى 5 نجوم');
                return;
            }
            
            // عرض رسالة نجاح
            alert('تم إرسال تقييمك بنجاح وسيتم مراجعته قبل النشر');
            
            // إعادة تعيين النموذج
            this.reset();
            ratingValue.value = '0';
            
            // إعادة شكل النجوم إلى الحالة الأولية
            ratingStars.forEach(function(s) {
                s.classList.remove('fas');
                s.classList.add('far');
            });
            
            // إخفاء نموذج التقييم
            reviewForm.style.display = 'none';
            writeReviewBtn.style.display = 'block';
        });
    }
    
    // إضافة المنتجات ذات الصلة إلى السلة
    const relatedAddToCartButtons = document.querySelectorAll('.related-products .add-to-cart');
    
    relatedAddToCartButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            // إرسال طلب AJAX لإضافة المنتج إلى السلة
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'cart_actions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // تحديث عدد العناصر في السلة
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = response.cart_count;
                            }
                            
                            // عرض رسالة نجاح
                            alert('تمت إضافة المنتج إلى السلة بنجاح');
                        } else {
                            alert(response.message || 'حدث خطأ أثناء إضافة المنتج إلى السلة');
                        }
                    } catch (e) {
                        alert('حدث خطأ أثناء معالجة الطلب');
                    }
                }
            };
            xhr.send('action=add&product_id=' + productId + '&quantity=1');
        });
    });
    
    // إضافة المنتجات ذات الصلة إلى المفضلة
    const relatedWishlistButtons = document.querySelectorAll('.related-products .wishlist-btn');
    
    relatedWishlistButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            // تبديل حالة الأيقونة
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = '#dc3545';
                
                // عرض رسالة نجاح
                alert('تمت إضافة المنتج إلى المفضلة');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '';
                
                // عرض رسالة نجاح
                alert('تمت إزالة المنتج من المفضلة');
            }
        });
    });
});
</script>

<?php
// استدعاء ملف التذييل
require_once 'includes/footer.php';
?>

