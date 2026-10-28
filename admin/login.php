<?php
require_once __DIR__ . '/../includes/functions.php';
start_session();
if (current_user()) {
    redirect('index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = post('username');
    $password = $_POST['password'] ?? '';
    // หน่วงเวลาเล็กน้อยเมื่อล็อกอินผิดหลายครั้ง ลดการเดารหัสผ่าน
    $fails = $_SESSION['login_fails'] ?? 0;
    if ($fails >= 5) {
        sleep(min(10, $fails - 4));
    }
    try {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && is_string($password) && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$u['id'], 'username' => $u['username'], 'fullname' => $u['fullname']];
            unset($_SESSION['login_fails']);
            redirect('index.php');
        }
        $_SESSION['login_fails'] = $fails + 1;
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้ ตรวจสอบการตั้งค่าใน config.php';
    }
}
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ · ระบบจัดการศูนย์ดิจิทัลชุมชน</title>
<link rel="icon" type="image/png" href="../assets/img/favicon.png">
<link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&family=Sarabun:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="login-page">
  <form method="post" class="login-card">
    <div class="text-center mb-4">
      <img class="brand-mark lg" src="../assets/img/logo.png" alt="โลโก้ศูนย์ดิจิทัลชุมชน">
      <h1 class="h4 mt-3 mb-1">ระบบบริหารจัดการ</h1>
      <p class="text-muted mb-0">ศูนย์ดิจิทัลชุมชน จังหวัด<?= h(PROVINCE) ?></p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">ชื่อผู้ใช้</label>
      <input name="username" class="form-control form-control-lg" required autofocus value="<?= h(post('username')) ?>">
    </div>
    <div class="mb-4">
      <label class="form-label">รหัสผ่าน</label>
      <input name="password" type="password" class="form-control form-control-lg" required>
    </div>
    <button class="btn btn-primary btn-lg w-100">เข้าสู่ระบบ</button>
    <a href="../digital.html" class="d-block text-center mt-3 small">← กลับหน้าเว็บไซต์</a>
  </form>
</body>
</html>
