<?php

declare(strict_types=1);

/**
 * Read-only analytics from existing module tables. No materialized tables required at typical scale;
 * add summary tables later if nightly rollups are needed for multi-year trends.
 */

function analytics_db(?PDO $pdo = null): PDO
{
    return $pdo ?? db();
}

/**
 * @return array<string,int|float>
 */
function getUserGrowthStats(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    $row = $pdo->query('
        SELECT
          COUNT(*) AS total_users,
          SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active_users,
          SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_users,
          SUM(CASE WHEN status = "suspended" THEN 1 ELSE 0 END) AS suspended_users
        FROM users
    ')->fetch() ?: [];

    $verifiedProfiles = 0;
    $avgPc = 0.0;
    $withProfile = 0;
    if (mscore_table_exists($pdo, 'user_profiles')) {
        $p = $pdo->query('
            SELECT
              SUM(CASE WHEN national_id_status = "approved" THEN 1 ELSE 0 END) AS verified_id,
              AVG(profile_completion) AS avg_pc,
              COUNT(*) AS n
            FROM user_profiles
        ')->fetch() ?: [];
        $verifiedProfiles = (int) ($p['verified_id'] ?? 0);
        $avgPc = (float) ($p['avg_pc'] ?? 0);
        $withProfile = (int) ($p['n'] ?? 0);
    }

    return [
        'total_users' => (int) ($row['total_users'] ?? 0),
        'active_users' => (int) ($row['active_users'] ?? 0),
        'pending_users' => (int) ($row['pending_users'] ?? 0),
        'suspended_users' => (int) ($row['suspended_users'] ?? 0),
        'verified_profiles' => $verifiedProfiles,
        'avg_profile_completion' => round($avgPc, 2),
        'profiles_tracked' => $withProfile,
    ];
}

/**
 * @return array{source:string,avg_score:float,investment_ready:int,tiers:list<array{label:string,count:int}>}
 */
function getMScoreDistribution(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    $out = [
        'source' => 'none',
        'avg_score' => 0.0,
        'investment_ready' => 0,
        'tiers' => [],
    ];
    if (mscore_table_exists($pdo, 'mscore_current_scores')) {
        $out['source'] = 'mscore_current_scores';
        $out['avg_score'] = (float) ($pdo->query('SELECT AVG(total_score) FROM mscore_current_scores')->fetchColumn() ?: 0);
        $rows = $pdo->query('SELECT tier_label AS label, COUNT(*) AS c FROM mscore_current_scores GROUP BY tier_label ORDER BY c DESC')->fetchAll() ?: [];
        foreach ($rows as $r) {
            $label = (string) ($r['label'] ?? '');
            $c = (int) ($r['c'] ?? 0);
            $out['tiers'][] = ['label' => $label, 'count' => $c];
            if (stripos($label, 'Investment') !== false) {
                $out['investment_ready'] += $c;
            }
        }
        return $out;
    }
    if (mscore_table_exists($pdo, 'm_scores')) {
        $out['source'] = 'm_scores';
        $out['avg_score'] = (float) ($pdo->query('SELECT AVG(score) FROM m_scores WHERE score IS NOT NULL')->fetchColumn() ?: 0);
        $rows = $pdo->query('SELECT tier AS label, COUNT(*) AS c FROM m_scores GROUP BY tier ORDER BY c DESC')->fetchAll() ?: [];
        foreach ($rows as $r) {
            $label = (string) ($r['label'] ?? '');
            $c = (int) ($r['c'] ?? 0);
            $out['tiers'][] = ['label' => $label, 'count' => $c];
            if (stripos($label, 'Investment') !== false) {
                $out['investment_ready'] += $c;
            }
        }
    }
    return $out;
}

/**
 * @return array<string,mixed>
 */
function getFundingStats(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'funding_applications')) {
        return ['available' => false, 'total_applications' => 0, 'by_status' => [], 'approved_volume' => 0.0, 'total_disbursed' => 0.0];
    }
    $byStatus = [];
    $st = $pdo->query('SELECT status, COUNT(*) AS c FROM funding_applications GROUP BY status');
    foreach ($st->fetchAll() ?: [] as $r) {
        $byStatus[(string) $r['status']] = (int) $r['c'];
    }
    $total = (int) array_sum($byStatus);
    $approvedStatuses = ['approved', 'disbursed', 'active_repayment', 'completed'];
    $placeholders = implode(',', array_fill(0, count($approvedStatuses), '?'));
    $st2 = $pdo->prepare("
        SELECT COALESCE(SUM(requested_amount),0) AS v
        FROM funding_applications
        WHERE status IN ($placeholders)
    ");
    $st2->execute($approvedStatuses);
    $approvedVol = (float) ($st2->fetchColumn() ?: 0);

    $disbursed = 0.0;
    if (mscore_table_exists($pdo, 'funding_disbursements')) {
        $disbursed = (float) ($pdo->query('SELECT COALESCE(SUM(disbursed_amount),0) FROM funding_disbursements')->fetchColumn() ?: 0);
    }

    return [
        'available' => true,
        'total_applications' => $total,
        'by_status' => $byStatus,
        'approved_volume' => $approvedVol,
        'total_disbursed' => $disbursed,
    ];
}

/**
 * @return array<string,mixed>
 */
function getVerificationStats(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'user_documents')) {
        return ['available' => false, 'total' => 0, 'by_status' => []];
    }
    $by = [];
    $st = $pdo->query('SELECT status, COUNT(*) AS c FROM user_documents GROUP BY status');
    foreach ($st->fetchAll() ?: [] as $r) {
        $by[(string) $r['status']] = (int) $r['c'];
    }
    return [
        'available' => true,
        'total' => (int) array_sum($by),
        'by_status' => $by,
    ];
}

/**
 * @return array<string,mixed>
 */
function getTrainingStats(?PDO $pdo = null, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'training_registrations')) {
        return ['available' => false, 'registrations_total' => 0, 'completed' => 0, 'by_month' => []];
    }
    $where = '1=1';
    $params = [];
    if ($dateFrom !== null && $dateFrom !== '') {
        $where .= ' AND DATE(applied_at) >= :df';
        $params['df'] = $dateFrom;
    }
    if ($dateTo !== null && $dateTo !== '') {
        $where .= ' AND DATE(applied_at) <= :dt';
        $params['dt'] = $dateTo;
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM training_registrations WHERE $where");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $st2 = $pdo->prepare("
        SELECT COUNT(*) FROM training_registrations
        WHERE $where AND status = 'approved' AND participation_status = 'completed'
    ");
    $st2->execute($params);
    $completed = (int) $st2->fetchColumn();

    $st3 = $pdo->prepare("
        SELECT DATE_FORMAT(applied_at, '%Y-%m') AS ym, COUNT(*) AS c
        FROM training_registrations
        WHERE $where
        GROUP BY ym
        ORDER BY ym ASC
        LIMIT 36
    ");
    $st3->execute($params);
    $byMonth = $st3->fetchAll() ?: [];

    return [
        'available' => true,
        'registrations_total' => $total,
        'completed' => $completed,
        'by_month' => $byMonth,
    ];
}

/**
 * @return array<string,mixed>
 */
function getBenefitsStats(?PDO $pdo = null, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'benefit_claims')) {
        return ['available' => false, 'claims_total' => 0, 'by_status' => [], 'by_category' => []];
    }
    $where = '1=1';
    $params = [];
    if ($dateFrom !== null && $dateFrom !== '') {
        $where .= ' AND DATE(c.claimed_at) >= :df';
        $params['df'] = $dateFrom;
    }
    if ($dateTo !== null && $dateTo !== '') {
        $where .= ' AND DATE(c.claimed_at) <= :dt';
        $params['dt'] = $dateTo;
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM benefit_claims c WHERE $where");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $byStatus = [];
    $st2 = $pdo->prepare("SELECT c.status, COUNT(*) AS n FROM benefit_claims c WHERE $where GROUP BY c.status");
    $st2->execute($params);
    foreach ($st2->fetchAll() ?: [] as $r) {
        $byStatus[(string) $r['status']] = (int) $r['n'];
    }

    $byCat = [];
    if (mscore_table_exists($pdo, 'benefit_offers')) {
        $st3 = $pdo->prepare("
            SELECT COALESCE(cat.name, 'Uncategorised') AS category_name, COUNT(*) AS n
            FROM benefit_claims c
            INNER JOIN benefit_offers o ON o.id = c.benefit_offer_id
            LEFT JOIN benefit_categories cat ON cat.id = o.category_id
            WHERE $where
            GROUP BY cat.id, cat.name
            ORDER BY n DESC
        ");
        $st3->execute($params);
        $byCat = $st3->fetchAll() ?: [];
    }

    return [
        'available' => true,
        'claims_total' => $total,
        'by_status' => $byStatus,
        'by_category' => $byCat,
    ];
}

/**
 * @return array{available:bool,total:int}
 */
function getPartnerRequestStats(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    if (mscore_table_exists($pdo, 'partner_requests')) {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM partner_requests')->fetchColumn();
        return ['available' => true, 'total' => $n];
    }
    return ['available' => false, 'total' => 0];
}

/**
 * @return array{available:bool,applications:int,by_status:array<string,int>}
 */
function getOpportunityEngagementStats(?PDO $pdo = null, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'opportunity_applications')) {
        return ['available' => false, 'applications' => 0, 'by_status' => []];
    }
    $where = '1=1';
    $params = [];
    if ($dateFrom !== null && $dateFrom !== '') {
        $where .= ' AND DATE(applied_at) >= :df';
        $params['df'] = $dateFrom;
    }
    if ($dateTo !== null && $dateTo !== '') {
        $where .= ' AND DATE(applied_at) <= :dt';
        $params['dt'] = $dateTo;
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM opportunity_applications WHERE $where");
    $st->execute($params);
    $total = (int) $st->fetchColumn();
    $by = [];
    $st2 = $pdo->prepare("SELECT status, COUNT(*) AS n FROM opportunity_applications WHERE $where GROUP BY status");
    $st2->execute($params);
    foreach ($st2->fetchAll() ?: [] as $r) {
        $by[(string) $r['status']] = (int) $r['n'];
    }
    return ['available' => true, 'applications' => $total, 'by_status' => $by];
}

/**
 * New user registrations by calendar month (users.created_at).
 *
 * @return list<array{ym:string,c:int}>
 */
function getUserGrowthByMonth(?PDO $pdo = null, int $months = 12, ?string $dateTo = null): array
{
    $pdo = analytics_db($pdo);
    $months = max(1, min(60, $months));
    $end = $dateTo !== null && $dateTo !== '' ? $dateTo : date('Y-m-d');
    $st = $pdo->prepare('
        SELECT DATE_FORMAT(created_at, "%Y-%m") AS ym, COUNT(*) AS c
        FROM users
        WHERE created_at <= :end_ts AND created_at >= DATE_SUB(:end_day, INTERVAL :m MONTH)
        GROUP BY ym
        ORDER BY ym ASC
    ');
    $st->execute(['end_ts' => $end . ' 23:59:59', 'end_day' => $end, 'm' => $months]);
    return $st->fetchAll() ?: [];
}

/**
 * @return list<array{status:string,c:int}>
 */
function getFundingStatusBreakdown(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'funding_applications')) {
        return [];
    }
    return $pdo->query('SELECT status, COUNT(*) AS c FROM funding_applications GROUP BY status ORDER BY c DESC')->fetchAll() ?: [];
}

/**
 * @return list<array{status:string,c:int}>
 */
function getDocumentStatusBreakdown(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    if (!mscore_table_exists($pdo, 'user_documents')) {
        return [];
    }
    return $pdo->query('SELECT status, COUNT(*) AS c FROM user_documents GROUP BY status ORDER BY c DESC')->fetchAll() ?: [];
}

/**
 * @return array<string,mixed> Combined KPI block for dashboard cards
 */
/**
 * @return list<array{ym:string,c:int}>
 */
function getTrainingParticipationByMonth(?PDO $pdo = null, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $t = getTrainingStats($pdo, $dateFrom, $dateTo);
    return $t['by_month'] ?? [];
}

function getAnalyticsOverviewKpis(?PDO $pdo = null): array
{
    $pdo = analytics_db($pdo);
    $u = getUserGrowthStats($pdo);
    $m = getMScoreDistribution($pdo);
    $f = getFundingStats($pdo);
    $b = getBenefitsStats($pdo, null, null);
    $t = getTrainingStats($pdo, null, null);
    $p = getPartnerRequestStats($pdo);

    return [
        'users' => $u,
        'mscore' => $m,
        'funding' => $f,
        'benefits' => $b,
        'training' => $t,
        'partners' => $p,
    ];
}
