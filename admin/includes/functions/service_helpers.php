<?php
/**
 * ملف مساعد للخدمات
 */

function process_service_form() {
    $default_data = [
        'status' => 'form',
        'errors' => [],
        'data' => [
            'name' => '',
            'description' => '',
            'price_start' => '',
            'category' => '',
            'status' => 'active',
            'is_featured' => false
        ]
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $default_data;
    }

    // هنا تضيف منطق معالجة النموذج
    // هذا مثال مبسط:
    $result = $default_data;
    $result['status'] = 'error';
    
    // التحقق من الحقول المطلوبة
    if (empty($_POST['name'])) {
        $result['errors'][] = 'اسم الخدمة مطلوب';
    }
    
    // حفظ البيانات المرسلة لإعادة عرضها
    $result['data'] = array_merge($result['data'], $_POST);
    $result['data']['is_featured'] = isset($_POST['is_featured']);

    // إذا لم تكن هناك أخطاء
    if (empty($result['errors'])) {
        $result['status'] = 'success';
        // هنا تضيف منطق حفظ البيانات في قاعدة البيانات
    }

    return $result;
}