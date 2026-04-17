<?php

declare(strict_types=1);

/**
 * Front controller include: configuration, database, helpers, session.
 * Every public PHP entry point should require this file first.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/m_id_generator.php';

auth_start_session();
