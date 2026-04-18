<?php

declare(strict_types=1);

/**
 * Opens HTML document. Set before include:
 * - $mgrid_page_title (string) — browser title; default "Malkia Grid"
 * - $mgrid_layout: public | auth | user | admin
 * - $mgrid_sidebar_context: user | admin (for user/admin layouts)
 */

if (!isset($mgrid_layout)) {
    $mgrid_layout = 'public';
}
if (!isset($mgrid_page_title)) {
    $mgrid_page_title = 'Malkia Grid';
}
if (!isset($mgrid_sidebar_context)) {
    $mgrid_sidebar_context = $mgrid_layout === 'admin' ? 'admin' : 'user';
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($mgrid_page_title) ?></title>
  <link rel="shortcut icon" type="image/png" href="<?= e(asset('images/logos/favicon.png')) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,400..600;1,9..40,400..600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/styles.min.css')) ?>" />
</head>

<body class="<?php
  if ($mgrid_layout === 'public') {
      echo 'mgrid-public';
  } elseif ($mgrid_layout === 'auth') {
      echo 'mgrid-auth';
  } else {
      echo 'mgrid-app mgrid-dash' . ($mgrid_layout === 'admin' ? ' mgrid-admin' : '');
  }
?>">

<?php if ($mgrid_layout === 'public'): ?>
  <?php require __DIR__ . '/navbar.php'; ?>

<?php elseif ($mgrid_layout === 'auth'): ?>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center">
      <div class="d-flex align-items-center justify-content-center w-100 py-5">

<?php elseif (in_array($mgrid_layout, ['user', 'admin'], true)): ?>
  <!-- Flexy-style shell: sidebar + body (matches template assets/js/sidebarmenu.js) -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="body-wrapper">
      <?php require __DIR__ . '/topbar.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid px-3 px-lg-4 pb-4 mgrid-dash-main">

<?php endif; ?>
