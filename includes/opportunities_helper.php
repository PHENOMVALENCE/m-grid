<?php

declare(strict_types=1);

/**
 * Opportunities listing, filters, and shared queries.
 */

function opportunities_module_ready(PDO $pdo): bool
{
    return mscore_table_exists($pdo, 'opportunities');
}

function ot_opportunity_is_expired(array $row): bool
{
    $today = date('Y-m-d');
    $dl = $row['deadline'] ?? null;
    if ($dl !== null && $dl !== '' && (string) $dl < $today) {
        return true;
    }
    $end = $row['end_date'] ?? null;
    if ($end !== null && $end !== '' && (string) $end < $today) {
        return true;
    }
    return false;
}

function ot_opportunity_listing_state(array $row): string
{
    if ((int) ($row['is_archived'] ?? 0) === 1) {
        return 'archived';
    }
    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return 'inactive';
    }
    if (ot_opportunity_is_expired($row)) {
        return 'expired';
    }
    return 'active';
}

/**
 * @return array<int,array<string,mixed>>
 */
function opportunities_list_for_public(
    PDO $pdo,
    ?string $type = null,
    ?int $categoryId = null,
    ?string $deadlineFrom = null,
    ?string $deadlineTo = null,
    ?string $listingFilter = null,
    ?string $keyword = null,
    int $limit = 100
): array {
    if (!opportunities_module_ready($pdo)) {
        return [];
    }
    $where = ['o.is_archived = 0', 'o.is_active = 1'];
    $params = [];

    if ($type !== null && $type !== '') {
        $where[] = 'o.opportunity_type = :t';
        $params['t'] = $type;
    }
    if ($categoryId !== null && $categoryId > 0) {
        $where[] = 'o.category_id = :cid';
        $params['cid'] = $categoryId;
    }
    if ($deadlineFrom !== '') {
        $where[] = 'o.deadline IS NOT NULL AND o.deadline >= :df';
        $params['df'] = $deadlineFrom;
    }
    if ($deadlineTo !== '') {
        $where[] = 'o.deadline IS NOT NULL AND o.deadline <= :dt';
        $params['dt'] = $deadlineTo;
    }
    if ($keyword !== null && $keyword !== '') {
        $where[] = '(o.title LIKE :kw OR o.description LIKE :kw2 OR o.provider_name LIKE :kw3)';
        $like = '%' . $keyword . '%';
        $params['kw'] = $like;
        $params['kw2'] = $like;
        $params['kw3'] = $like;
    }

    $sql = '
        SELECT o.*, c.name AS category_name, c.slug AS category_slug
        FROM opportunities o
        INNER JOIN opportunity_categories c ON c.id = o.category_id AND c.is_active = 1
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY o.deadline IS NULL, o.deadline ASC, o.title ASC
        LIMIT ' . (int) max(1, min(500, $limit));

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll() ?: [];

    if ($listingFilter === 'active') {
        $rows = array_values(array_filter($rows, static fn ($r) => ot_opportunity_listing_state($r) === 'active'));
    } elseif ($listingFilter === 'expired') {
        $rows = array_values(array_filter($rows, static fn ($r) => ot_opportunity_listing_state($r) === 'expired'));
    }

    return $rows;
}

/**
 * @return array<string,mixed>|null
 */
function opportunities_get_by_id(PDO $pdo, int $id): ?array
{
    if (!opportunities_module_ready($pdo)) {
        return null;
    }
    $st = $pdo->prepare('
        SELECT o.*, c.name AS category_name
        FROM opportunities o
        INNER JOIN opportunity_categories c ON c.id = o.category_id
        WHERE o.id = :id LIMIT 1
    ');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

function opportunities_user_has_active_application(PDO $pdo, int $userId, int $opportunityId): bool
{
    $st = $pdo->prepare('
        SELECT COUNT(*) FROM opportunity_applications
        WHERE user_id = :u AND opportunity_id = :o
          AND status IN ("submitted","under_review","shortlisted")
    ');
    $st->execute(['u' => $userId, 'o' => $opportunityId]);
    return (int) $st->fetchColumn() > 0;
}

/**
 * @return list<array<string,mixed>>
 */
function ot_user_active_opportunity_applications(PDO $pdo, int $userId): array
{
    if (!opportunities_module_ready($pdo)) {
        return [];
    }
    $st = $pdo->prepare('
        SELECT a.*, o.title, o.opportunity_type, o.provider_name
        FROM opportunity_applications a
        INNER JOIN opportunities o ON o.id = a.opportunity_id
        WHERE a.user_id = :u AND a.status IN ("submitted","under_review","shortlisted")
        ORDER BY a.applied_at DESC
    ');
    $st->execute(['u' => $userId]);
    return $st->fetchAll() ?: [];
}

function ot_user_active_opportunity_applications_count(PDO $pdo, int $userId): int
{
    return count(ot_user_active_opportunity_applications($pdo, $userId));
}
