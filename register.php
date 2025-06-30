<?php
// register.php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    
    // التحقق من عدم وجود مستخدم بنفس الاسم
    $stmt = $conn->prepare("SELECT * FROM students WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: register.php?error=user_exists");
        exit();
    }
    
    // تسجيل المستخدم الجديد
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO students (username, password, full_name, email) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $full_name, $email]);
        
        header("Location: login.php?success=registered");
        exit();
    } catch(PDOException $e) {
        header("Location: register.php?error=database_error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="register-container">
        <h1>تسجيل حساب جديد</h1>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="full_name">الاسم الكامل</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">تسجيل الحساب</button>
        </form>
        <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول هنا</a></p>
    </div>
    
    <script src="assets/js/register.js"></script>
</body>
</html>