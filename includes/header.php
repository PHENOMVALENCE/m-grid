<?php

declare(strict_types=1);

/**
 * Opens HTML document. Set before include:
 * - $mgrid_page_title (string)
 * - $mgrid_layout: public | auth | user | admin
 * - $mgrid_sidebar_context: user | admin (for user/admin layouts)
 */

if (!isset($mgrid_layout)) {
    $mgrid_layout = 'public';
}
if (!isset($mgrid_page_title)) {
    $mgrid_page_title = 'M-GRID';
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
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/styles.min.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/m-grid.css')) ?>" />
</head>

<body class="<?= $mgrid_layout === 'public' ? 'mgrid-public' : '' ?>">

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
        <div class="container-fluid py-4">

<?php endif; ?>
