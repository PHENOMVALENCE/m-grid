<?php

declare(strict_types=1);

if (!isset($mgrid_page_title)) {
    $mgrid_page_title = function_exists('__') ? __('shell.default_admin') : 'M GRID — Admin';
}
$mgrid_layout = 'admin';
$mgrid_sidebar_context = 'admin';
require __DIR__ . '/../../includes/header.php';
