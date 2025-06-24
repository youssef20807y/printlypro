<?php
$host = 'localhost';
$dbname = 'printly_db';
$user = 'root';
$pass = ''; // اتركه فارغًا إذا لم يكن لديك كلمة مرور

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // تفعيل التقارير عن الأخطاء
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}
