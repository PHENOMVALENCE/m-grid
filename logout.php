<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_logout();
flash_set('success', 'You have been signed out.');
redirect('index.php');
