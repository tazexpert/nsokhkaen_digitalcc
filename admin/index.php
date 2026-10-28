<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'ภาพรวม';
$active = 'dashboard';
$pdo = db();
$stats = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM centers) AS centers,
            (SELECT COUNT(DISTINCT district) FROM centers WHERE district <> '') AS districts,
            (SELECT COUNT(*) FROM activities) AS activities,
            (SELECT COALESCE(SUM(participants),0) FROM activities) AS participants,
            (SELECT COUNT(*) FROM centers WHERE lat IS NULL OR lng IS NULL) AS no_location"
)->fetch();
$recent = $pdo->query(
    "SELECT a.id, a.title, a.activity_date, a.participants, c.name AS center_name
     FROM activities a JOIN centers c ON c.id = a.center_id
     ORDER BY a.activity_date IS NULL, a.activity_date DESC, a.id DESC LIMIT 8"
)->fetchAll();
$byDistrict = $pdo->query(
    "SELECT district, COUNT(*) n FROM centers WHERE district <> '' GROUP BY district ORDER BY n DESC, district LIMIT 10"
)->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<div class="row g-3 mb-4">
  <?php foreach ([
      ['centers', 'bi-building', 'ศูนย์ทั้งหมด', 'primary'],
      ['districts', 'bi-geo-alt', 'อำเภอที่มีศูนย์', 'success'],
      ['activities', 'bi-calendar-check', 'กิจกรรม', 'warning'],
      ['participants', 'bi-people', 'ผู้เข้าร่วม (คน)', 'info'],
  ] as [$k, $icon, $label, $color]): ?>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <span class="stat-icon text-bg-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
      <div><div class="stat-num"><?= number_format((int)$stats[$k]) ?></div><div class="stat-label"><?= $label ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if ((int)$stats['no_location'] > 0): ?>
  <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i>
    มี <?= (int)$stats['no_location'] ?> ศูนย์ที่ยังไม่ได้ระบุพิกัด lat/long จึงยังไม่แสดงบนแผนที่ — <a href="geocode.php" class="alert-link">ค้นหาพิกัดอัตโนมัติ</a> · <a href="centers.php?nolocation=1">ดูรายการ</a></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center">
        <strong>กิจกรรมล่าสุด</strong>
        <a href="activity_form.php" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-plus-lg"></i> เพิ่มกิจกรรม</a>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($recent as $r): ?>
          <a class="list-group-item list-group-item-action" href="activity_form.php?id=<?= (int)$r['id'] ?>">
            <div class="d-flex"><strong class="me-2"><?= h($r['title']) ?></strong>
              <span class="ms-auto small text-muted text-nowrap"><?= thai_date($r['activity_date']) ?></span></div>
            <div class="small text-muted"><?= h($r['center_name']) ?> · <?= number_format((int)$r['participants']) ?> คน</div>
          </a>
        <?php endforeach; ?>
        <?php if (!$recent): ?><div class="list-group-item text-muted">ยังไม่มีกิจกรรม</div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center">
        <strong>จำนวนศูนย์ตามอำเภอ</strong>
        <a href="center_form.php" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-plus-lg"></i> เพิ่มศูนย์</a>
      </div>
      <div class="card-body">
        <?php $max = max(1, ...array_map(fn($r) => (int)$r['n'], $byDistrict ?: [['n' => 1]])); ?>
        <?php foreach ($byDistrict as $r): ?>
          <div class="bar-row">
            <span class="bar-label"><?= h($r['district']) ?></span>
            <span class="bar-track"><span class="bar-fill" style="width:<?= round($r['n'] / $max * 100) ?>%"></span></span>
            <span class="bar-val"><?= (int)$r['n'] ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (!$byDistrict): ?><p class="text-muted mb-0">ยังไม่มีข้อมูล</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
