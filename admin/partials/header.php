<?php
/** @var string $pageTitle  @var string $active */
$user = require_login();
$pageTitle = $pageTitle ?? 'ระบบจัดการ';
$active = $active ?? '';
$nav = [
    'dashboard'  => ['index.php', 'bi-speedometer2', 'ภาพรวม'],
    'centers'    => ['centers.php', 'bi-building', 'ศูนย์ดิจิทัลชุมชน'],
    'visits'     => ['visits.php', 'bi-clipboard-check', 'ตรวจเยี่ยมศูนย์'],
    'activities' => ['activities.php', 'bi-calendar-event', 'กิจกรรม'],
    'account'    => ['account.php', 'bi-person-gear', 'บัญชีผู้ใช้'],
];
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> · ระบบจัดการศูนย์ดิจิทัลชุมชน</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrap">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="index.php">
      <span class="brand-mark"><i class="bi bi-wifi"></i></span>
      <span><strong>Digital Community</strong><small>จังหวัด<?= h(PROVINCE) ?></small></span>
    </a>
    <nav>
      <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
        <a href="<?= $href ?>" class="<?= $active === $key ? 'active' : '' ?>"><i class="bi <?= $icon ?>"></i><?= h($label) ?></a>
      <?php endforeach; ?>
      <hr>
      <a href="../digital.html" target="_blank"><i class="bi bi-box-arrow-up-right"></i>ดูหน้าเว็บไซต์</a>
      <a href="logout.php"><i class="bi bi-box-arrow-left"></i>ออกจากระบบ</a>
    </nav>
  </aside>
  <main class="content">
    <header class="topbar">
      <button class="btn btn-light d-lg-none" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
      <h1 class="h5 mb-0"><?= h($pageTitle) ?></h1>
      <div class="ms-auto small text-muted"><i class="bi bi-person-circle"></i> <?= h($user['fullname'] ?: $user['username']) ?></div>
    </header>
    <div class="content-body">
    <?php foreach (take_flash() as $f): ?>
      <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show"><?= h($f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endforeach; ?>
