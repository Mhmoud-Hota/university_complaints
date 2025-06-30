<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/SMTP.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    $stmt = $conn->prepare("SELECT student_id, full_name FROM students WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // إنشاء كود OTP مكون من 6 أرقام
        $otp = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // تخزين OTP في قاعدة البيانات
        $updateStmt = $conn->prepare("
            UPDATE students 
            SET reset_token = ?, reset_expires = ? 
            WHERE student_id = ?
        ");
        $updateStmt->execute([$otp, $expires, $user['student_id']]);
        
        try {
            $mail = new PHPMailer(true);
            
            // استخدام إعدادات SMTP من config.php
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($email, $user['full_name']);
            
            $mail->isHTML(true);
            $mail->Subject = 'كود التحقق لاستعادة كلمة المرور - نظام البلاغات الجامعي';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #2c3e50;'>مرحباً {$user['full_name']},</h2>
                    <p style='font-size: 16px;'>لقد طلبت إعادة تعيين كلمة المرور لحسابك في نظام البلاغات الجامعي.</p>
                    <p style='font-size: 18px; font-weight: bold; color: #e74c3c;'>كود التحقق الخاص بك هو: $otp</p>
                    <p style='font-size: 14px; color: #7f8c8d;'>صلاحية هذا الكود تنتهي بعد 10 دقائق.</p>
                    <hr style='border: 1px solid #ecf0f1;'>
                    <p style='font-size: 12px; color: #95a5a6;'>إذا لم تطلب هذا الكود، يرجى تجاهل هذه الرسالة أو تغيير كلمة المرور لحسابك.</p>
                </div>
            ";
            
            $mail->send();
            $_SESSION['otp_email'] = $email;
            header("Location: verify_otp.php");
            exit();
            
        } catch (Exception $e) {
            $message = "حدث خطأ أثناء إرسال كود التحقق. يرجى المحاولة لاحقاً.";
            error_log("Mail Error: " . $e->getMessage());
        }
    } else {
        $message = "البريد الإلكتروني غير مسجل في النظام";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور - نظام البلاغات الجامعي</title>
    <link rel="stylesheet" href="assets/css/style.css">
        <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-info {
            background-color: #d9edf7;
            color: #31708f;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>استعادة كلمة المرور</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= strpos($message, 'خطأ') !== false ? 'danger' : 'info' ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">البريد الإلكتروني المسجل</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit" class="btn">إرسال كود التحقق</button>
        </form>
        
        <p class="login-link">تذكرت كلمة المرور؟ <a href="login.php">تسجيل الدخول</a></p>
    </div>
</body>
</html>