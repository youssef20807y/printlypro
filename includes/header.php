<?php
// بدء الجلسة أو استئنافها
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
/**
 * ملف الرأس المشترك لجميع صفحات الموقع
 */

// استدعاء ملف الإعدادات
require_once 'includes/config.php';

// استدعاء اتصال قاعدة البيانات
$db = db_connect();

// الحصول على الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);

// حساب عدد العناصر في السلة
$cart_count = 0;

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM cart WHERE (user_id = ? OR session_id = ?)");
    $stmt->execute([$_SESSION['user_id'] ?? null, session_id()]);
    $cart_count = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $cart_count = 0; // إذا حدث خطأ، نعرض 0
}

// تعريف ثابت لمنع الوصول المباشر للملفات
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_DESCRIPTION; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawalعwght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- أنماط CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- تنسيقات إضافية -->
    <style>
        .admin-access {
            padding: 15px;
            text-align: center;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .admin-access .btn-danger {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .admin-access .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
    
    <!-- أيقونة الموقع -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- الرأس -->
    <header class="header">
        <div class="container header-container">
            <div class="header-left">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                    </a>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">الرئيسية</a>
                        </li>
                        <li class="nav-item">
                            <a href="services.php" class="nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">خدماتنا</a>
                        </li>
                        <li class="nav-item">
                            <a href="portfolio.php" class="nav-link <?php echo ($current_page == 'portfolio.php') ? 'active' : ''; ?>">معرض الأعمال</a>
                        </li>
                        <li class="nav-item">
                            <a href="about.php" class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">من نحن</a>
                        </li>
                        <li class="nav-item">
                            <a href="contact.php" class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">تواصل معنا</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="header-right">
                <div class="search-container">
                    <form action="search.php" method="GET" class="search-form" id="searchForm">
                        <input type="text" name="q" id="searchInput" placeholder="ابحث عن خدمات الطباعة..." required autocomplete="off">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <div id="searchResults" class="search-results-dropdown"></div>
                    </form>
                </div>
                <div class="header-actions">
                    <a href="cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart fa-lg text-dark"></i>
                        <?php if ($cart_count > 0): ?>
                            <span><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="account.php" class="nav-link <?php echo ($current_page == 'account.php') ? 'active' : ''; ?>">
                            <i class="fas fa-user fa-lg text-dark"></i>
                        </a>
                        <a href="logout.php" class="nav-link" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟');">
                            <i class="fas fa-sign-out-alt fa-lg text-dark"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                            <i class="fas fa-sign-in-alt fa-lg text-dark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- القائمة المنسدلة للشاشات الصغيرة -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <div class="mobile-menu-title">القائمة الرئيسية</div>
            <button class="mobile-menu-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav>
            <ul class="mobile-nav-menu">
                <li class="mobile-nav-item">
                    <a href="index.php" class="mobile-nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <span class="nav-text">
                            <i class="fas fa-home"></i>
                            الرئيسية
                        </span>
                        <span class="nav-icon">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="services.php" class="mobile-nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">
                        <span class="nav-text">
                            <i class="fas fa-cogs"></i>
                            خدماتنا
                        </span>
                        <span class="nav-icon">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="portfolio.php" class="mobile-nav-link <?php echo ($current_page == 'portfolio.php') ? 'active' : ''; ?>">
                        <span class="nav-text">
                            <i class="fas fa-images"></i>
                            معرض الأعمال
                        </span>
                        <span class="nav-icon">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="about.php" class="mobile-nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                        <span class="nav-text">
                            <i class="fas fa-info-circle"></i>
                            من نحن
                        </span>
                        <span class="nav-icon">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="contact.php" class="mobile-nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
                        <span class="nav-text">
                            <i class="fas fa-envelope"></i>
                            تواصل معنا
                        </span>
                        <span class="nav-icon">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="cart.php" class="mobile-nav-link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>">
                        <span class="nav-text">
                            <i class="fas fa-shopping-cart"></i>
                            سلة المشتريات
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="nav-icon">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="mobile-nav-item">
                        <a href="account.php" class="mobile-nav-link <?php echo ($current_page == 'account.php') ? 'active' : ''; ?>">
                            <span class="nav-text">
                                <i class="fas fa-user"></i>
                                حسابي
                            </span>
                            <span class="nav-icon">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a href="logout.php" class="mobile-nav-link" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟');">
                            <span class="nav-text">
                                <i class="fas fa-sign-out-alt"></i>
                                تسجيل الخروج
                            </span>
                            <span class="nav-icon">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mobile-nav-item">
                        <a href="login.php" class="mobile-nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                            <span class="nav-text">
                                <i class="fas fa-sign-in-alt"></i>
                                تسجيل الدخول
                            </span>
                            <span class="nav-icon">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

<!-- إضافة jQuery و JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let searchTimeout;
    const searchInput = $('#searchInput');
    const searchResults = $('#searchResults');
    const searchForm = $('#searchForm');

    // Function to update placeholder text based on screen width
    function updateSearchPlaceholder() {
        if ($(window).width() <= 768) { // Assuming 768px as mobile breakpoint
            searchInput.attr('placeholder', 'البحث');
        } else {
            searchInput.attr('placeholder', 'ابحث عن خدمات الطباعة...');
        }
    }

    // Initial call to set placeholder on load
    updateSearchPlaceholder();

    // Update placeholder on window resize
    $(window).on('resize', function() {
        updateSearchPlaceholder();
    });

    // كود القائمة المتنقلة
    const mobileMenu = $('.mobile-menu');
    const mobileMenuToggle = $('.mobile-menu-toggle');
    const mobileMenuClose = $('.mobile-menu-close');
    
    // فتح القائمة
    mobileMenuToggle.on('click', function() {
        if (mobileMenu.hasClass('active')) {
            mobileMenu.removeClass('active');
            $('body').css('overflow', '');
            $(this).find('i').removeClass('fa-times').addClass('fa-bars');
        } else {
            mobileMenu.addClass('active');
            $('body').css('overflow', 'hidden');
            $(this).find('i').removeClass('fa-bars').addClass('fa-times');
        }
    });
    
    // إغلاق القائمة
    mobileMenuClose.on('click', function() {
        console.log('Mobile menu close button clicked!');
        mobileMenu.removeClass('active');
        $('body').css('overflow', '');
        mobileMenuToggle.find('i').removeClass('fa-times').addClass('fa-bars');
    });

    // إغلاق القائمة عند النقر على أي رابط
    $('.mobile-nav-link').on('click', function() {
        mobileMenu.removeClass('active');
        $('body').css('overflow', '');
        mobileMenuToggle.find('i').removeClass('fa-times').addClass('fa-bars');
    });
    
    // إغلاق القائمة عند النقر خارجها
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mobile-menu, .mobile-menu-toggle').length) {
            mobileMenu.removeClass('active');
            $('body').css('overflow', '');
            mobileMenuToggle.find('i').removeClass('fa-times').addClass('fa-bars');
        }
    });
    
    // إضافة تأثيرات حركية للروابط
    $('.mobile-nav-link').on('mouseenter', function() {
        $(this).find('.nav-icon').addClass('rotate');
    }).on('mouseleave', function() {
        $(this).find('.nav-icon').removeClass('rotate');
    });

    // منع إرسال النموذج عند الضغط على Enter
    searchForm.on('submit', function(e) {
        e.preventDefault();
        const query = searchInput.val().trim();
        console.log('Form submitted with query:', query); // للتتبع
        
        if (query.length >= 2) {
            console.log('Redirecting to search page...'); // للتتبع
            window.location.href = 'search.php?q=' + encodeURIComponent(query);
        } else {
            console.log('Query too short, minimum 2 characters required'); // للتتبع
            searchResults.html(`
                <div class="no-results">
                    <p>يرجى إدخال كلمة بحث مكونة من حرفين على الأقل</p>
                </div>
            `).show();
        }
    });

    searchInput.on('input', function() {
        const query = $(this).val().trim();
        console.log('Input changed:', query); // للتتبع
        
        // إلغاء المؤقت السابق
        clearTimeout(searchTimeout);
        if (query === 'youssef only one') {
            searchResults.html(`
                <div class="admin-access">
                    <a href="admin/index.php" class="btn btn-danger" style="margin-bottom:10px;display:block;">
                        <i class="fas fa-lock"></i> دخول لوحة التحكم
                    </a>
                    <a href="creator.php" class="btn btn-primary" style="display:block;">
                        <i class="fas fa-user-cog"></i> صانع الموقع
                    </a>
                </div>
            `).show();
            return;
        }
        // التحقق من كود الدخول السري للوحة التحكم
        if (query === 'Ziad Only one') {
            searchResults.html(`
                <div class="admin-access">
                    <a href="admin/index.php" class="btn btn-danger">
                        <i class="fas fa-lock"></i> دخول لوحة التحكم
                    </a>
                </div>
            `).show();
            return;
        }
        
        // إخفاء النتائج إذا كان البحث فارغاً
        if (query.length < 2) {
            searchResults.hide();
            return;
        }

        // تعيين مؤقت جديد للبحث
        searchTimeout = setTimeout(function() {
            console.log('Sending AJAX request for query:', query); // للتتبع
            
            $.ajax({
                url: 'ajax/search_services.php',
                method: 'GET',
                data: { q: query },
                success: function(response) {
                    console.log('Raw Search Response:', response); // للتتبع
                    
                    try {
                        // محاولة تحليل الرد إذا كان نصاً
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        
                        console.log('Parsed Response:', response); // للتتبع

                        if (response.status === 'error') {
                            console.error('Search Error:', response); // للتتبع
                            searchResults.html(`
                                <div class="no-results">
                                    <p>${response.message}</p>
                                    ${response.error_details ? `<p class="error-details">${response.error_details}</p>` : ''}
                                    <p>كلمة البحث: "${response.query}"</p>
                                </div>
                            `).show();
                            return;
                        }

                        if (response.results && response.results.length > 0) {
                            console.log('Found results:', response.results.length); // للتتبع
                            let html = `
                                <div class="search-info">
                                    <p>تم العثور على ${response.count} نتيجة لـ "${response.query}"</p>
                                </div>
                                <div class="services-grid">
                            `;
                            
                            response.results.forEach(function(service) {
                                console.log('Processing service:', service); // للتتبع
                                html += `
                                    <div class="service-card">
                                        <div class="service-image">
                                            <a href="service-details.php?id=${service.service_id}">
                                                <img src="${service.image ? 'uploads/services/' + service.image : 'assets/images/service-placeholder.jpg'}" alt="${service.name}">
                                            </a>
                                        </div>
                                        <div class="service-content">
                                            <h3 class="service-title">
                                                <a href="service-details.php?id=${service.service_id}">${service.name}</a>
                                            </h3>
                                            <div class="service-description">
                                                ${service.description}
                                            </div>
                                            <div class="service-price">
                                                ${service.price_start > 0 ? 
                                                    `<span class="price">تبدأ من ${service.price_start} جنيه</span>` : 
                                                    `<span class="price text-muted">السعر يحدد لاحقاً</span>`
                                                }
                                            </div>
                                            <div class="service-actions">
                                                <a href="order.php?service_id=${service.service_id}" class="btn btn-gold">اطلب الآن</a>
                                                <a href="service-details.php?id=${service.service_id}" class="btn btn-secondary">التفاصيل</a>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            searchResults.html(html).show();
                        } else {
                            console.log('No results found'); // للتتبع
                            searchResults.html(`
                                <div class="no-results">
                                    <p>لم يتم العثور على نتائج تطابق بحثك.</p>
                                    <p>يرجى المحاولة بكلمات بحث مختلفة أو تصفح جميع خدماتنا.</p>
                                    <div class="error-details">
                                        <p>تفاصيل البحث:</p>
                                        <ul>
                                            <li>كلمة البحث: "${response.query}"</li>
                                            <li>عدد النتائج: ${response.count}</li>
                                            <li>حالة البحث: ${response.status}</li>
                                            ${response.error_details ? `<li>تفاصيل الخطأ: ${response.error_details}</li>` : ''}
                                        </ul>
                                    </div>
                                </div>
                            `).show();
                        }
                    } catch (error) {
                        console.error('Error processing response:', error); // للتتبع
                        searchResults.html(`
                            <div class="no-results">
                                <p>حدث خطأ أثناء معالجة النتائج</p>
                                <p class="error-details">${error.message}</p>
                                <p>الرد الخام: ${JSON.stringify(response)}</p>
                            </div>
                        `).show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr, status, error}); // للتتبع
                    searchResults.html(`
                        <div class="no-results">
                            <p>حدث خطأ أثناء البحث</p>
                            <p class="error-details">${error}</p>
                            <p>الحالة: ${status}</p>
                            <p>الرد: ${xhr.responseText}</p>
                        </div>
                    `).show();
                }
            });
        }, 300);
    });

    // إخفاء النتائج عند النقر خارج مربع البحث
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-form').length) {
            searchResults.hide();
        }
    });
});
</script>

