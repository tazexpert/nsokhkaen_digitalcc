<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pdo = db();

// ลบการตรวจเยี่ยม (รวมกิจกรรมและภาพที่ผูกกัน)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    check_csrf();
    $id = int_param('id');
    $st = $pdo->prepare('SELECT activity_id FROM visits WHERE id = ?');
    $st->execute([$id]);
    $actId = (int)$st->fetchColumn();
    $files = [];
    if ($actId) {
        $st = $pdo->prepare('SELECT file FROM activity_images WHERE activity_id = ? UNION ALL SELECT cover_image FROM activities WHERE id = ?');
        $st->execute([$actId, $actId]);
        $files = $st->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare('DELETE FROM activities WHERE id = ?')->execute([$actId]);
    }
    $pdo->prepare('DELETE FROM visits WHERE id = ?')->execute([$id]);
    array_map('delete_upload', array_filter($files));
    flash('ลบบันทึกการตรวจเยี่ยมเรียบร้อยแล้ว');
    redirect('visits.php');
}

$centerId = int_param('center_id');
$district = trim($_GET['district'] ?? '');
$where = [];
$params = [];
if ($centerId) { $where[] = 'v.center_id = ?'; $params[] = $centerId; }
if ($district !== '') { $where[] = 'c.district = ?'; $params[] = $district; }
$st = $pdo->prepare(
    "SELECT v.*, c.name AS center_name, c.code, c.district,
            (SELECT COUNT(*) FROM activity_images i WHERE i.activity_id = v.activity_id) AS photo_count
     FROM visits v JOIN centers c ON c.id = v.center_id" .
    ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY v.visit_date DESC, v.id DESC'
);
$st->execute($params);
$rows = $st->fetchAll();

$progress = $pdo->query(
    'SELECT COUNT(*) AS total, SUM(EXISTS(SELECT 1 FROM visits v WHERE v.center_id = c.id)) AS visited FROM centers c'
)->fetch();
$notVisited = $pdo->query(
    'SELECT id, code, name, district FROM centers c WHERE NOT EXISTS (SELECT 1 FROM visits v WHERE v.center_id = c.id) ORDER BY district, name'
)->fetchAll();

$pageTitle = 'ตรวจเยี่ยมศูนย์';
$active = 'visits';
require __DIR__ . '/partials/header.php';
$pct = $progress['total'] ? round($progress['visited'] / $progress['total'] * 100) : 0;
?>
<div class="row g-3 mb-3">
  <div class="col-md-8">
    <div class="stat-card h-100">
      <span class="stat-icon text-bg-success"><i class="bi bi-clipboard-check"></i></span>
      <div class="flex-grow-1">
        <div class="d-flex"><span class="stat-num"><?= (int)$progress['visited'] ?> / <?= (int)$progress['total'] ?></span><span class="ms-auto fw-semibold"><?= $pct ?>%</span></div>
        <div class="stat-label mb-1">ศูนย์ที่ตรวจเยี่ยมแล้ว</div>
        <div class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%"></span></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 d-grid">
    <a href="visit_form.php" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2"><i class="bi bi-plus-circle"></i> บันทึกการตรวจเยี่ยมใหม่</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header">
    <form class="row g-2 align-items-center">
      <div class="col-auto"><strong>ประวัติการตรวจเยี่ยม</strong></div>
      <div class="col-md-3 ms-auto">
        <select name="district" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">ทุกอำเภอ</option>
          <?php foreach (KK_DISTRICTS as $dn): ?><option <?= $dn === $district ? 'selected' : '' ?>><?= h($dn) ?></option><?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>วันที่</th><th>ศูนย์</th><th>ผู้ตรวจเยี่ยม</th><th class="text-center">ภาพ</th><th class="text-end">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-nowrap"><?= thai_date($r['visit_date']) ?><?= $r['visit_time'] ? '<div class="small text-muted">' . h(substr($r['visit_time'], 0, 5)) . ' น.</div>' : '' ?></td>
          <td><a href="visit_view.php?id=<?= (int)$r['id'] ?>" class="fw-semibold text-decoration-none"><?= h($r['center_name']) ?></a>
            <div class="small text-muted"><?= h(trim(($r['code'] ? $r['code'] . ' · ' : '') . 'อ.' . $r['district'])) ?></div></td>
          <td><?= h($r['inspector_name']) ?></td>
          <td class="text-center"><?= (int)$r['photo_count'] ?></td>
          <td class="text-end text-nowrap">
            <a href="visit_view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-light" title="ดู/พิมพ์"><i class="bi bi-printer"></i></a>
            <a href="visit_form.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-light" title="แก้ไข"><i class="bi bi-pencil"></i></a>
            <form method="post" class="d-inline" data-confirm="ลบบันทึกการตรวจเยี่ยมและภาพทั้งหมด?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-light text-danger" title="ลบ"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">ยังไม่มีบันทึกการตรวจเยี่ยม</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($notVisited): ?>
<div class="card">
  <div class="card-header"><strong>ศูนย์ที่ยังไม่ได้ตรวจเยี่ยม (<?= count($notVisited) ?>)</strong></div>
  <div class="list-group list-group-flush">
    <?php foreach ($notVisited as $c): ?>
      <a href="visit_form.php?center_id=<?= (int)$c['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center">
        <span><?= h($c['name']) ?> <span class="small text-muted"><?= h(($c['code'] ? $c['code'] . ' · ' : '') . 'อ.' . $c['district']) ?></span></span>
        <span class="ms-auto small text-primary text-nowrap"><i class="bi bi-clipboard-plus"></i> ตรวจเยี่ยม</span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
