<?php

declare(strict_types=1);

/**
 * Training programs, registrations, and M-SCORE-oriented aggregates.
 */

function trainings_module_ready(PDO $pdo): bool
{
    return mscore_table_exists($pdo, 'training_programs');
}

function ot_training_is_past(array $row): bool
{
    $end = $row['schedule_end'] ?? $row['schedule_start'] ?? null;
    if ($end === null || $end === '') {
        return false;
    }
    return strtotime((string) $end) < time();
}

function ot_training_listing_state(array $row): string
{
    if ((int) ($row['is_archived'] ?? 0) === 1) {
        return 'archived';
    }
    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return 'inactive';
    }
    if (ot_training_is_past($row)) {
        return 'past';
    }
    return 'active';
}

/**
 * @return array<int,array<string,mixed>>
 */
function trainings_list_for_public(
    PDO $pdo,
    ?string $type = null,
    ?string $format = null,
    ?string $listingFilter = null,
    ?string $keyword = null,
    int $limit = 100
): array {
    if (!trainings_module_ready($pdo)) {
        return [];
    }
    $where = ['p.is_archived = 0', 'p.is_active = 1'];
    $params = [];

    if ($type !== null && $type !== '') {
        $where[] = 'p.training_type = :t';
        $params['t'] = $type;
    }
    if ($format !== null && $format !== '') {
        $where[] = 'p.format = :f';
        $params['f'] = $format;
    }
    if ($keyword !== null && $keyword !== '') {
        $where[] = '(p.title LIKE :kw OR p.description LIKE :kw2 OR p.provider_name LIKE :kw3)';
        $like = '%' . $keyword . '%';
        $params['kw'] = $like;
        $params['kw2'] = $like;
        $params['kw3'] = $like;
    }

    $sql = '
        SELECT p.*
        FROM training_programs p
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.schedule_start IS NULL, p.schedule_start ASC, p.title ASC
        LIMIT ' . (int) max(1, min(500, $limit));

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll() ?: [];

    if ($listingFilter === 'active') {
        $rows = array_values(array_filter($rows, static fn ($r) => ot_training_listing_state($r) === 'active'));
    } elseif ($listingFilter === 'past') {
        $rows = array_values(array_filter($rows, static fn ($r) => ot_training_listing_state($r) === 'past'));
    }

    return $rows;
}

/**
 * @return array<string,mixed>|null
 */
function trainings_get_by_id(PDO $pdo, int $id): ?array
{
    if (!trainings_module_ready($pdo)) {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM training_programs WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

function trainings_user_has_active_registration(PDO $pdo, int $userId, int $programId): bool
{
    $st = $pdo->prepare('
        SELECT COUNT(*) FROM training_registrations
        WHERE user_id = :u AND training_program_id = :p
          AND status IN ("pending","approved","waitlisted")
    ');
    $st->execute(['u' => $userId, 'p' => $programId]);
    return (int) $st->fetchColumn() > 0;
}

/**
 * Count completed trainings (approved + participation completed).
 */
function ot_user_completed_trainings_count(PDO $pdo, int $userId): int
{
    if (!mscore_table_exists($pdo, 'training_registrations')) {
        return 0;
    }
    $st = $pdo->prepare('
        SELECT COUNT(*) FROM training_registrations
        WHERE user_id = :u AND status = "approved" AND participation_status = "completed"
    ');
    $st->execute(['u' => $userId]);
    return (int) $st->fetchColumn();
}

/**
 * Registrations with verified certificate status (platform-tracked).
 */
function ot_user_verified_training_certificates_count(PDO $pdo, int $userId): int
{
    if (!mscore_table_exists($pdo, 'training_registrations')) {
        return 0;
    }
    $st = $pdo->prepare('
        SELECT COUNT(*) FROM training_registrations
        WHERE user_id = :u AND certificate_status = "verified"
    ');
    $st->execute(['u' => $userId]);
    return (int) $st->fetchColumn();
}

/**
 * @return list<array<string,mixed>>
 */
function ot_user_active_training_registrations(PDO $pdo, int $userId): array
{
    if (!trainings_module_ready($pdo)) {
        return [];
    }
    $st = $pdo->prepare('
        SELECT r.*, p.title, p.training_type, p.provider_name
        FROM training_registrations r
        INNER JOIN training_programs p ON p.id = r.training_program_id
        WHERE r.user_id = :u AND r.status IN ("pending","approved","waitlisted")
        ORDER BY r.applied_at DESC
    ');
    $st->execute(['u' => $userId]);
    return $st->fetchAll() ?: [];
}

function ot_user_active_training_registrations_count(PDO $pdo, int $userId): int
{
    return count(ot_user_active_training_registrations($pdo, $userId));
}
