<?php

declare(strict_types=1);

/**
 * Opens the member dashboard shell. Requires init_member.php beforehand.
 * Expects $mgrid_page_title to be set.
 */

if (!isset($mgrid_page_title)) {
    $mgrid_page_title = function_exists('__') ? __('shell.default_member') : 'M GRID';
}
$mgrid_layout = 'user';
$mgrid_sidebar_context = 'user';
require __DIR__ . '/../../includes/header.php';
