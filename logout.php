<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$keepLang = in_array((string) ($_SESSION['preferred_language'] ?? 'sw'), ['en', 'sw'], true)
    ? (string) $_SESSION['preferred_language']
    : 'sw';
auth_logout();
auth_start_session();
$_SESSION['preferred_language'] = $keepLang;
flash_set('success', __('flash.signed_out'));
redirect('index.php');
