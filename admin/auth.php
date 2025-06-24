<?php
/**
 * ملف التحقق من صلاحيات المسؤول
 */

// تعريف ثابت لمنع الوصول المباشر للملفات


// استدعاء ملف الإعدادات
require_once __DIR__ . '/../includes/config.php';

// التحقق من وجود جلسة مسجلة للمسؤول
if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

// التحقق من صلاحيات المسؤول
if ($_SESSION['admin_role'] !== 'admin') {
    // تسجيل الخروج وإعادة التوجيه إلى صفحة تسجيل الدخول
    session_unset();
    session_destroy();
    redirect('login.php');
}
