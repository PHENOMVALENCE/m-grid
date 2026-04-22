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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <?php
  $mgrid_public_vanilla = !empty($mgrid_public_vanilla);
  $mgrid_css_rel = $mgrid_layout === 'public' && $mgrid_public_vanilla
      ? 'css/public-vanilla.min.css'
      : 'css/styles.min.css';
  $mgrid_css_path = __DIR__ . '/../assets/' . $mgrid_css_rel;
  $mgrid_css_v = @filemtime($mgrid_css_path) ?: time();
  ?>
  <link rel="stylesheet" href="<?= e(asset($mgrid_css_rel)) . '?v=' . $mgrid_css_v ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
  <?php
  $mgrid_theme_v = @filemtime(__DIR__ . '/../assets/css/mgrid-theme.css') ?: time();
  $mgrid_premium_v = @filemtime(__DIR__ . '/../assets/css/mgrid-premium-ui.css') ?: time();
  $mgrid_auth_v = @filemtime(__DIR__ . '/../assets/css/mgrid-auth.css') ?: time();
  $mgrid_dashboard_v = @filemtime(__DIR__ . '/../assets/css/mgrid-dashboard.css') ?: time();
  $mgrid_public_polish_v = @filemtime(__DIR__ . '/../assets/css/mgrid-public-polish.css') ?: time();
  ?>
  <link rel="stylesheet" href="<?= e(asset('css/mgrid-theme.css')) . '?v=' . $mgrid_theme_v ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/mgrid-premium-ui.css')) . '?v=' . $mgrid_premium_v ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/mgrid-auth.css')) . '?v=' . $mgrid_auth_v ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/mgrid-dashboard.css')) . '?v=' . $mgrid_dashboard_v ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/mgrid-public-polish.css')) . '?v=' . $mgrid_public_polish_v ?>" />
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
      echo 'mgrid-app mgrid-dash mgrid-ui-premium' . ($mgrid_layout === 'admin' ? ' mgrid-admin' : '');
  }
?>">

<?php if ($mgrid_layout === 'public'): ?>
  <?php require __DIR__ . '/navbar.php'; ?>

<?php elseif ($mgrid_layout === 'auth'): ?>
  <div class="mgrid-auth-wrapper">
      <div class="mgrid-auth-lang-wrap">
        <?php require __DIR__ . '/lang_toggle.php'; ?>
      </div>
      <div class="mgrid-auth-container">

<?php elseif (in_array($mgrid_layout, ['user', 'admin'], true)): ?>
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div class="mgrid-main">
    <?php require __DIR__ . '/topbar.php'; ?>
    <div class="mgrid-content">

<?php endif; ?>
