<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>تسجيل الدخول</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="includes/auth.php">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">تسجيل الدخول</button>
        </form>
        <div class="links">
            <a href="forgot_password.php">نسيت كلمة المرور؟</a>
            <a href="register.php">إنشاء حساب جديد</a>
        </div>
    </div>
</body>
</html>