<?php

declare(strict_types=1);

/**
 * Admin-only bootstrap. No HTML output.
 */

require_once __DIR__ . '/../../includes/init.php';

auth_require_admin();
