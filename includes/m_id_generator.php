<?php

declare(strict_types=1);

/**
 * M-ID allocation: format M-YYYY-000001 (unique, never user-editable).
 * Uses m_id_counters for safe sequencing under typical concurrent registration load.
 */

require_once __DIR__ . '/db.php';

/**
 * Reserve the next M-ID for the current calendar year inside an open transaction.
 * Caller must BEGIN and COMMIT/ROLLBACK.
 */
function m_id_allocate_next(PDO $pdo): string
{
    $year = (int) date('Y');

    $upd = $pdo->prepare('
        INSERT INTO m_id_counters (year, last_number)
        VALUES (:y, 1)
        ON DUPLICATE KEY UPDATE last_number = last_number + 1
    ');
    $upd->execute(['y' => $year]);

    $sel = $pdo->prepare('SELECT last_number FROM m_id_counters WHERE year = :y LIMIT 1');
    $sel->execute(['y' => $year]);
    $row = $sel->fetch();
    $n = (int) ($row['last_number'] ?? 1);

    return sprintf('M-%d-%06d', $year, $n);
}
