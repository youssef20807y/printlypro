<?php
session_start(); // بدء الجلسة

// إزالة جميع بيانات الجلسة
$_SESSION = array();

// إنهاء الجلسة
session_destroy();

// إعادة توجيه المستخدم إلى صفحة تسجيل الدخول
header("Location: login.php");
exit;
?>
