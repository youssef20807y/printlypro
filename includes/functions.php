<?php
/**
 * ملف الدوال المساعدة
 */

// دالة إرسال بريد إعادة تعيين كلمة المرور
function send_reset_email($email, $username, $reset_code) {
    global $errors; // إضافة متغير عام للأخطاء
    
    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "خطأ: البريد الإلكتروني غير صالح - " . $email;
        return false;
    }

    // استدعاء مكتبة PHPMailer
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    require_once __DIR__ . '/PHPMailer/Exception.php';

    // إنشاء كائن PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // تفعيل وضع التصحيح
        $mail->SMTPDebug = 3; // تفعيل التصحيح الكامل
        $debug_output = [];
        $mail->Debugoutput = function($str, $level) use (&$debug_output) {
            $debug_output[] = "PHPMailer Debug [$level]: $str";
        };

        // إعدادات الخادم
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yjmt46999@gmail.com';
        $mail->Password = 'enfgdisiottvcozi';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 60;
        
        // إعدادات SSL/TLS
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // إعدادات إضافية
        $mail->SMTPKeepAlive = true;
        $mail->Priority = 1;

        // التحقق من الاتصال بالخادم
        if (!$mail->smtpConnect()) {
            $errors[] = "خطأ: فشل الاتصال بخادم SMTP";
            $errors[] = "تفاصيل الاتصال:";
            $errors = array_merge($errors, $debug_output);
            return false;
        }

        // إعدادات المرسل والمستلم
        $mail->setFrom('yjmt46999@gmail.com', 'مطبعة برنتلي', false);
        $mail->addAddress($email, $username);

        // إنشاء رابط إعادة التعيين
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?code=" . $reset_code;

        // محتوى البريد
        $mail->isHTML(true);
        $mail->Subject = "إعادة تعيين كلمة المرور - مطبعة برنتلي";
        
        $message = "
        <html dir='rtl'>
        <head>
            <title>إعادة تعيين كلمة المرور</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .button { 
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #00adef;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer { 
                    text-align: center;
                    padding: 20px;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>إعادة تعيين كلمة المرور</h2>
                </div>
                <div class='content'>
                    <p>مرحباً {$username},</p>
                    <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك.</p>
                    <p>يمكنك إعادة تعيين كلمة المرور الخاصة بك عن طريق النقر على الزر أدناه:</p>
                    <p style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>إعادة تعيين كلمة المرور</a>
                    </p>
                    <p>إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.</p>
                    <p>ينتهي هذا الرابط خلال ساعة واحدة من إرساله.</p>
                </div>
                <div class='footer'>
                    <p>هذا البريد الإلكتروني تم إرساله تلقائياً، يرجى عدم الرد عليه.</p>
                    <p>© " . date('Y') . " مطبعة برنتلي. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $message;
        $mail->AltBody = "مرحباً {$username},\n\nلقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك.\n\nيمكنك إعادة تعيين كلمة المرور الخاصة بك عن طريق النقر على الرابط التالي:\n{$reset_link}\n\nإذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.\n\nينتهي هذا الرابط خلال ساعة واحدة من إرساله.";

        // محاولة إرسال البريد
        if (!$mail->send()) {
            $errors[] = "خطأ في إرسال البريد: " . $mail->ErrorInfo;
            $errors[] = "تفاصيل عملية الإرسال:";
            $errors = array_merge($errors, $debug_output);
            return false;
        }

        return true;
    } catch (Exception $e) {
        $errors[] = "خطأ في إرسال البريد: " . $e->getMessage();
        $errors[] = "تفاصيل الخطأ:";
        $errors[] = $e->getTraceAsString();
        $errors[] = "سجل التصحيح:";
        $errors = array_merge($errors, $debug_output);
        return false;
    } finally {
        // إغلاق الاتصال
        if (isset($mail) && $mail->getSMTPInstance()) {
            $mail->getSMTPInstance()->quit();
        }
    }
} 