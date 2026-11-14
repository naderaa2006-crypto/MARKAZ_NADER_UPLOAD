
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT id, username, password_hash
         FROM admins
         WHERE username = ?
         LIMIT 1"
    );

    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        header("Location: admin/bookings.php");
        exit;

    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>دخول المدير | MARKAZ NADER</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background: #ede4d3;
            min-height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            font-family: inherit;
        }

        .login-box {
            width: min(400px, calc(100% - 40px));
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-sizing: border-box;
        }

        .login-box h1 {
            color: #5f8c89;
            font-size: 25px;
        }

        .login-box p {
            color: #777;
            margin-bottom: 28px;
        }

        .login-box label {
            display: block;
            margin: 18px 0 8px;
        }

        .login-box input {
            width: 100%;
            box-sizing: border-box;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font: inherit;
        }

        .login-box button {
            width: 100%;
            padding: 15px;
            margin-top: 25px;
            background: #5f8c89;
            color: white;
            border: 0;
            border-radius: 9px;
            font: inherit;
            cursor: pointer;
        }

        .login-error {
            color: #e8213a;
            background: #fff0f0;
            padding: 12px;
            border-radius: 8px;
        }

        .login-back {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #5f8c89;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="login-box">

    <h1>MARKAZ NADER</h1>
    <p>تسجيل دخول المدير</p>

    <?php if ($error): ?>
        <div class="login-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>اسم المستخدم</label>
        <input type="text" name="username" required>

        <label>كلمة المرور</label>
        <input type="password" name="password" required>

        <button type="submit">تسجيل الدخول</button>

    </form>

    <a class="login-back" href="index.php">العودة للرئيسية</a>

</div>

</body>
</html>
