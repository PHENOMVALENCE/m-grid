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
if (!isset($mgrid_body_extra_class)) {
    $mgrid_body_extra_class = '';
}
if (!isset($mgrid_navbar_premium)) {
    $mgrid_navbar_premium = false;
}
if (!isset($mgrid_public_vanilla)) {
    $mgrid_public_vanilla = false;
}

$mgrid_default_lang = 'en';
if (session_status() === PHP_SESSION_ACTIVE) {
    $pl = (string) ($_SESSION['preferred_language'] ?? '');
    if ($pl === 'sw' || $pl === 'en') {
        $mgrid_default_lang = $pl;
    }
}
?>
<!doctype html>
<html lang="<?= $mgrid_default_lang === 'sw' ? 'sw' : 'en' ?>" data-mgrid-default-lang="<?= e($mgrid_default_lang) ?>">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($mgrid_page_title) ?></title>
  <link rel="shortcut icon" type="image/png" href="<?= e(asset('images/logos/favicon.png')) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <?php
  $mgrid_public_vanilla = !empty($mgrid_public_vanilla);
  $mgrid_css_rel = $mgrid_layout === 'public' && $mgrid_public_vanilla
      ? 'css/public-vanilla.min.css'
      : 'css/styles.min.css';
  $mgrid_css_path = __DIR__ . '/../assets/' . $mgrid_css_rel;
  $mgrid_css_v = @filemtime($mgrid_css_path) ?: time();
  ?>
  <link rel="stylesheet" href="<?= e(asset($mgrid_css_rel)) . '?v=' . $mgrid_css_v ?>" />
</head>

<body class="<?php
  if ($mgrid_layout === 'public') {
      $mgrid_pub_classes = ['mgrid-public'];
      if ($mgrid_body_extra_class !== '') {
          $mgrid_pub_classes[] = trim($mgrid_body_extra_class);
      }
      if ($mgrid_public_vanilla) {
          $mgrid_pub_classes[] = 'mgrid-public-vanilla';
      }
      echo implode(' ', $mgrid_pub_classes);
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
      <div class="position-absolute top-0 end-0 p-3 mgrid-auth-lang-wrap">
        <?php require __DIR__ . '/lang_toggle.php'; ?>
      </div>
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
