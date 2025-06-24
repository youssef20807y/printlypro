<?php
// منع الوصول المباشر
if (!defined('PRINTLY')) {
    exit('ممنوع الوصول المباشر');
}

// تأمين المتغيرات
if (!isset($new_orders_count)) $new_orders_count = 0;
if (!isset($new_messages_count)) $new_messages_count = 0;
if (!isset($_SESSION['admin_username'])) $_SESSION['admin_username'] = 'مدير';

// معرفة اسم الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم مطبعة برنتلي</title>

    <!-- خطوط Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- AdminLTE RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <!-- Summernote -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css">

    <style>
        :root {
            --color-black: #000000;
            --color-gold: #00adef;
            --color-dark-gray: #333333;
            --color-light-gray: #F5F5F5;
            --color-white: #FFFFFF;
        }

        body {
            font-family: 'Tajawal', sans-serif;
        }

        .main-header {
            border-bottom: 1px solid var(--color-gold);
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100%;
            background-color: var(--color-black);
            z-index: 1050;
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.5);
        }

        .sidebar-overlay.show {
            transform: translateX(0);
        }

        .sidebar-backdrop {
            position: fixed;
            top: 0;
            right: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
        }

        .sidebar-backdrop.show {
            display: block;
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background-color: var(--color-gold);
            color: var(--color-black);
        }

        .brand-link {
            border-bottom: 1px solid var(--color-gold) !important;
        }

        .brand-link .brand-image {
            max-height: 40px;
        }

        .content-wrapper {
            background-color: var(--color-light-gray);
        }

        .card-primary.card-outline {
            border-top: 3px solid var(--color-gold);
        }

        .btn-primary {
            background-color: var(--color-gold);
            border-color: var(--color-gold);
            color: var(--color-black);
        }

        .btn-primary:hover {
            background-color: #c4a02f;
            border-color: #c4a02f;
            color: var(--color-black);
        }

        .page-item.active .page-link {
            background-color: var(--color-gold);
            border-color: var(--color-gold);
            color: var(--color-black);
        }

        .page-link {
            color: var(--color-dark-gray);
        }

        .nav-pills .nav-link.active {
            background-color: var(--color-gold);
            color: var(--color-black);
        }

        .btn-app {
            min-width: 120px;
            height: 90px;
            margin: 10px;
            font-size: 14px;
        }

        .btn-app i {
            font-size: 24px;
            display: block;
            margin-bottom: 10px;
        }

        .small-box .icon {
            right: auto;
            left: 10px;
        }

        .small-box .icon i {
            font-size: 50px;
        }

        .small-box h3 {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .small-box p {
            font-size: 1.2rem;
        }

        .small-box .small-box-footer {
            text-align: left;
        }

        .table th {
            font-weight: 600;
        }

        .custom-file-label::after {
            content: "استعراض";
        }

        .select2-container--default .select2-selection--single {
            height: calc(2.25rem + 2px);
            padding: .375rem .75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px);
        }

        .note-editor .note-toolbar {
            direction: ltr;
        }

        .note-editor .note-editing-area {
            direction: rtl;
        }

        /* Service Images Square Styles */
        .service-image {
            width: 60px !important;
            height: 60px !important;
            object-fit: cover !important;
            border-radius: 8px !important;
            border: 2px solid #e3e6f0 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            max-width: 60px !important;
            max-height: 60px !important;
            min-width: 60px !important;
            min-height: 60px !important;
        }

        .service-image-placeholder {
            width: 60px !important;
            height: 60px !important;
            background-color: #f8f9fc !important;
            border: 2px dashed #d1d3e2 !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #858796 !important;
            font-size: 12px !important;
            text-align: center !important;
            max-width: 60px !important;
            max-height: 60px !important;
            min-width: 60px !important;
            min-height: 60px !important;
        }

        /* Override any existing table image styles */
        .table img.service-image {
            width: 60px !important;
            height: 60px !important;
            object-fit: cover !important;
            border-radius: 8px !important;
            border: 2px solid #e3e6f0 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            max-width: 60px !important;
            max-height: 60px !important;
            min-width: 60px !important;
            min-height: 60px !important;
        }

        /* Force override for all images in services table */
        .table tbody td img {
            width: 60px !important;
            height: 60px !important;
            object-fit: cover !important;
            border-radius: 8px !important;
            border: 2px solid #e3e6f0 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            max-width: 60px !important;
            max-height: 60px !important;
            min-width: 60px !important;
            min-height: 60px !important;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- خلفية منبثقة -->
    <div id="sidebar-backdrop" class="sidebar-backdrop"></div>

    <!-- الشريط الجانبي كنافذة منبثقة -->
    <div id="sidebar" class="sidebar-overlay sidebar-dark-primary elevation-4">
        <a href="index.php" class="brand-link">
            <img src="../assets/images/logo.png" alt="مطبعة برنتلي" class="brand-image">
            <span class="brand-text font-weight-light">لوحة التحكم</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="assets/img/admin-avatar.png" class="img-circle elevation-2" alt="صورة المستخدم">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?php echo $_SESSION['admin_username']; ?></a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>لوحة التحكم</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link <?php echo in_array($current_page, ['orders.php', 'order-details.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>
                                إدارة الطلبات
                                <?php if ($new_orders_count > 0): ?>
                                    <span class="badge badge-info right"><?php echo $new_orders_count; ?></span>
                                <?php endif; ?>
                            </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="services.php" class="nav-link <?php echo in_array($current_page, ['services.php', 'service-edit.php', 'service-add.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-list"></i>
                            <p>إدارة الخدمات</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="portfolio.php" class="nav-link <?php echo in_array($current_page, ['portfolio.php', 'portfolio-edit.php', 'portfolio-add.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-images"></i>
                            <p>معرض الأعمال</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link <?php echo in_array($current_page, ['users.php', 'user-edit.php', 'user-add.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>إدارة المستخدمين</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link <?php echo in_array($current_page, ['messages.php', 'message-details.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>
                                الرسائل
                                <?php if ($new_messages_count > 0): ?>
                                    <span class="badge badge-info right"><?php echo $new_messages_count; ?></span>
                                <?php endif; ?>
                            </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>الإعدادات</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>تسجيل الخروج</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- الشريط العلوي -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" id="sidebar-toggle" href="#"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link">الرئيسية</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../index.php" class="nav-link" target="_blank">عرض الموقع</a>
            </li>
        </ul>

        <ul class="navbar-nav mr-auto-navbav">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <?php $notifications_count = $new_orders_count + $new_messages_count; ?>
                    <?php if ($notifications_count > 0): ?>
                        <span class="badge badge-warning navbar-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-left">
                    <span class="dropdown-item dropdown-header"><?php echo $notifications_count; ?> إشعارات</span>
                    <div class="dropdown-divider"></div>
                    <a href="orders.php?status=new" class="dropdown-item">
                        <i class="fas fa-shopping-cart ml-2"></i> <?php echo $new_orders_count; ?> طلبات جديدة
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="messages.php?status=new" class="dropdown-item">
                        <i class="fas fa-envelope ml-2"></i> <?php echo $new_messages_count; ?> رسائل جديدة
                    </a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </li>
        </ul>
    </nav>

    <!-- JavaScript لإظهار/إخفاء القائمة -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const toggleBtn = document.getElementById('sidebar-toggle');

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });

        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    </script>
