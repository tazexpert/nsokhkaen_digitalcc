<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_login();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = post('action');
    if ($action === 'password') {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$me['id']]);
        $new = (string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''), (string)$st->fetchColumn())) {
            $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif (mb_strlen($new) < 8) {
            $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
        } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
            $errors[] = 'ยืนยันรหัสผ่านไม่ตรงกัน';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            flash('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
            redirect('account.php');
        }
    } elseif ($action === 'add_user') {
        $username = post('username');
        $pass = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $errors[] = 'ชื่อผู้ใช้ต้องเป็น a-z, 0-9, _ . - ความยาว 3–50 ตัว';
        } elseif (mb_strlen($pass) < 8) {
            $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        } else {
            try {
                $pdo->prepare('INSERT INTO users (username, password_hash, fullname) VALUES (?, ?, ?)')
                    ->execute([$username, password_hash($pass, PASSWORD_DEFAULT), post('fullname')]);
                flash('เพิ่มผู้ใช้ ' . $username . ' เรียบร้อยแล้ว');
                redirect('account.php');
            } catch (PDOException $e) {
                $errors[] = 'ชื่อผู้ใช้นี้มีอยู่แล้ว';
            }
        }
    } elseif ($action === 'delete_user') {
        $uid = int_param('user_id');
        if ($uid === $me['id']) {
            $errors[] = 'ไม่สามารถลบบัญชีของตัวเองได้';
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            flash('ลบผู้ใช้เรียบร้อยแล้ว');
            redirect('account.php');
        }
    }
}
$users = $pdo->query('SELECT id, username, fullname, created_at FROM users ORDER BY id')->fetchAll();

$pageTitle = 'บัญชีผู้ใช้';
$active = 'account';
require __DIR__ . '/partials/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('h', $errors)) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-5">
    <form method="post" class="card">
      <div class="card-header"><strong><i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน</strong></div>
      <div class="card-body">
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <div class="mb-2"><label class="form-label">รหัสผ่านปัจจุบัน</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">รหัสผ่านใหม่</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
        <div class="mb-3"><label class="form-label">ยืนยันรหัสผ่านใหม่</label><input type="password" name="confirm_password" class="form-control" minlength="8" required></div>
        <button class="btn btn-primary">บันทึก</button>
      </div>
    </form>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><strong><i class="bi bi-people"></i> ผู้ใช้งานระบบ</strong></div>
      <table class="table align-middle mb-0">
        <thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อ-สกุล</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr><td><?= h($u['username']) ?></td><td><?= h($u['fullname']) ?></td>
            <td class="text-end"><?php if ((int)$u['id'] !== $me['id']): ?>
              <form method="post" data-confirm="ลบผู้ใช้ <?= h($u['username']) ?>?"><?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>
            <?php else: ?><span class="badge text-bg-light">คุณ</span><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post" class="card-footer row g-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_user">
        <div class="col-md-3"><input name="username" class="form-control" placeholder="ชื่อผู้ใช้" required></div>
        <div class="col-md-4"><input name="fullname" class="form-control" placeholder="ชื่อ-สกุล"></div>
        <div class="col-md-3"><input name="password" type="password" class="form-control" placeholder="รหัสผ่าน" minlength="8" required></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">เพิ่ม</button></div>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
