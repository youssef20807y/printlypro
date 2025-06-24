<?php
$host = 'localhost';
$db   = 'printly_db';
$user = 'root'; // أو اسم المستخدم الخاص بك
$pass = '';     // اتركها فارغة إذا لم يكن هناك كلمة مرور

$conn = new mysqli($host, $user, $pass, $db);

// تحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين الترميز
$conn->set_charset("utf8mb4");
