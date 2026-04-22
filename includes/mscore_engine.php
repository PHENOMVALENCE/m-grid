<?php

declare(strict_types=1);

/**
 * M-SCORE calculation engine.
 * Works with current schema and gracefully handles optional tables if absent.
 */

/**
 * @return array<int,array<string,mixed>>
 */
function mscore_active_settings(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT category_key, category_name, max_points, sort_order
        FROM mscore_settings
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ');
    return $stmt->fetchAll() ?: [];
}

function mscore_table_exists(PDO $pdo, string $table): bool
{
    $safe = $pdo->quote($table);
    $sql = 'SHOW TABLES LIKE ' . $safe;
    $st = $pdo->query($sql);
    return (bool) ($st ? $st->fetchColumn() : false);
}

/**
 * @return array<string,mixed>
 */
function getProfileScore(PDO $pdo, int $userId, float $maxPoints): array
{
    $stmt = $pdo->prepare('
        SELECT u.full_name, u.email, u.phone, p.region, p.business_status, p.bio, p.date_of_birth, p.age_range
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE u.id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch() ?: [];

    $checks = [
        'full_name' => trim((string) ($row['full_name'] ?? '')) !== '',
        'email' => trim((string) ($row['email'] ?? '')) !== '',
        'phone' => trim((string) ($row['phone'] ?? '')) !== '',
        'region' => trim((string) ($row['region'] ?? '')) !== '',
        'business_status' => trim((string) ($row['business_status'] ?? '')) !== '',
        'bio' => trim((string) ($row['bio'] ?? '')) !== '',
        'dob_or_age' => trim((string) ($row['date_of_birth'] ?? '')) !== '' || trim((string) ($row['age_range'] ?? '')) !== '',
    ];

    $completed = array_sum(array_map(static fn ($v): int => $v ? 1 : 0, $checks));
    $total = count($checks);
    $ratio = $total > 0 ? $completed / $total : 0.0;
    $awarded = round($maxPoints * $ratio, 2);

    return [
        'points_awarded' => $awarded,
        'max_points' => $maxPoints,
        'percentage' => round($ratio * 100, 2),
        'details' => [
            'completed_fields' => $completed,
            'required_fields' => $total,
            'checks' => $checks,
        ],
    ];
}

/**
 * @return array<string,mixed>
 */
function getVerifiedDocumentsScore(PDO $pdo, int $userId, float $maxPoints): array
{
    if (!mscore_table_exists($pdo, 'user_documents') || !mscore_table_exists($pdo, 'document_types')) {
        return [
            'points_awarded' => 0.0,
            'max_points' => $maxPoints,
            'percentage' => 0.0,
            'details' => ['note' => 'Document module tables not available yet.'],
        ];
    }

    $required = ['national_id', 'brela_certificate', 'tra_tin_certificate', 'bank_statement', 'training_certificate'];
    $st = $pdo->prepare('
        SELECT DISTINCT dt.slug
        FROM user_documents d
        INNER JOIN document_types dt ON dt.id = d.document_type_id
        WHERE d.user_id = :uid
          AND d.status = "verified"
          AND dt.slug IN ("national_id", "brela_certificate", "tra_tin_certificate", "bank_statement", "training_certificate")
    ');
    $st->execute(['uid' => $userId]);
    $verified = array_map(static fn ($r): string => (string) $r['slug'], $st->fetchAll() ?: []);
    $verifiedSet = array_fill_keys($verified, true);
    $count = 0;
    $checks = [];
    foreach ($required as $slug) {
        $ok = isset($verifiedSet[$slug]);
        $checks[$slug] = $ok;
        if ($ok) {
            $count++;
        }
    }
    $ratio = count($required) > 0 ? $count / count($required) : 0.0;
    $awarded = round($maxPoints * $ratio, 2);

    return [
        'points_awarded' => $awarded,
        'max_points' => $maxPoints,
        'percentage' => round($ratio * 100, 2),
        'details' => [
            'verified_count' => $count,
            'required_count' => count($required),
            'checks' => $checks,
        ],
    ];
}

/**
 * @return array<string,mixed>
 */
function getBankingScore(PDO $pdo, int $userId, float $maxPoints): array
{
    if (!mscore_table_exists($pdo, 'user_financial_profiles')) {
        return [
            'points_awarded' => 0.0,
            'max_points' => $maxPoints,
            'percentage' => 0.0,
            'details' => ['note' => 'Financial profile table not available.'],
        ];
    }
    $st = $pdo->prepare('
        SELECT bank_name, account_name, account_number, mobile_money_provider, mobile_money_number
        FROM user_financial_profiles
        WHERE user_id = :uid
        LIMIT 1
    ');
    $st->execute(['uid' => $userId]);
    $row = $st->fetch() ?: [];

    $checks = [
        'bank_name' => trim((string) ($row['bank_name'] ?? '')) !== '',
        'account_name' => trim((string) ($row['account_name'] ?? '')) !== '',
        'account_number' => trim((string) ($row['account_number'] ?? '')) !== '',
        'mobile_money_channel' => trim((string) ($row['mobile_money_provider'] ?? '')) !== '' || trim((string) ($row['mobile_money_number'] ?? '')) !== '',
    ];
    $completed = array_sum(array_map(static fn ($v): int => $v ? 1 : 0, $checks));
    $ratio = $completed / count($checks);
    return [
        'points_awarded' => round($maxPoints * $ratio, 2),
        'max_points' => $maxPoints,
        'percentage' => round($ratio * 100, 2),
        'details' => ['checks' => $checks],
    ];
}

/**
 * @return array<string,mixed>
 */
function getTrainingScore(PDO $pdo, int $userId, float $maxPoints): array
{
    if (!mscore_table_exists($pdo, 'user_training_records')) {
        return [
            'points_awarded' => 0.0,
            'max_points' => $maxPoints,
            'percentage' => 0.0,
            'details' => ['note' => 'Training records table not available.'],
        ];
    }
    $st = $pdo->prepare('
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN completion_status = "completed" THEN 1 ELSE 0 END) AS completed_count,
          SUM(CASE WHEN completion_status = "completed" AND verified_status = "verified" THEN 1 ELSE 0 END) AS verified_completed_count
        FROM user_training_records
        WHERE user_id = :uid
    ');
    $st->execute(['uid' => $userId]);
    $row = $st->fetch() ?: ['total' => 0, 'completed_count' => 0, 'verified_completed_count' => 0];
    $verifiedCompleted = (int) ($row['verified_completed_count'] ?? 0);
    $completed = (int) ($row['completed_count'] ?? 0);
    $total = max(1, (int) ($row['total'] ?? 0));

    $ratio = min(1.0, ($verifiedCompleted * 1.0 + max(0, $completed - $verifiedCompleted) * 0.5) / $total);
    return [
        'points_awarded' => round($maxPoints * $ratio, 2),
        'max_points' => $maxPoints,
        'percentage' => round($ratio * 100, 2),
        'details' => [
            'total_records' => (int) ($row['total'] ?? 0),
            'completed_count' => $completed,
            'verified_completed_count' => $verifiedCompleted,
        ],
    ];
}

/**
 * @return array<string,mixed>
 */
function getComplianceScore(PDO $pdo, int $userId, float $maxPoints): array
{
    if (!mscore_table_exists($pdo, 'user_documents') || !mscore_table_exists($pdo, 'document_types')) {
        return [
            'points_awarded' => 0.0,
            'max_points' => $maxPoints,
            'percentage' => 0.0,
            'details' => ['note' => 'Document module tables not available yet.'],
        ];
    }

    $required = ['business_license', 'brela_certificate', 'tra_tin_certificate'];
    $st = $pdo->prepare('
        SELECT DISTINCT dt.slug
        FROM user_documents d
        INNER JOIN document_types dt ON dt.id = d.document_type_id
        WHERE d.user_id = :uid
          AND d.status = "verified"
          AND dt.slug IN ("business_license", "brela_certificate", "tra_tin_certificate")
    ');
    $st->execute(['uid' => $userId]);
    $verified = array_map(static fn ($r): string => (string) $r['slug'], $st->fetchAll() ?: []);
    $verifiedSet = array_fill_keys($verified, true);

    $count = 0;
    $checks = [];
    foreach ($required as $slug) {
        $ok = isset($verifiedSet[$slug]);
        $checks[$slug] = $ok;
        if ($ok) {
            $count++;
        }
    }

    $ratio = $count / count($required);
    return [
        'points_awarded' => round($maxPoints * $ratio, 2),
        'max_points' => $maxPoints,
        'percentage' => round($ratio * 100, 2),
        'details' => [
            'verified_count' => $count,
            'required_count' => count($required),
            'checks' => $checks,
        ],
    ];
}

/**
 * @return array<string,mixed>
 */
function calculateUserMScore(int $userId): array
{
    $pdo = db();
    $userStmt = $pdo->prepare('SELECT id, m_id FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $settings = mscore_active_settings($pdo);
    if ($settings === []) {
        throw new RuntimeException('M-SCORE settings are missing.');
    }

    $breakdown = [];
    $total = 0.0;
    $maxTotal = 0.0;
    foreach ($settings as $cfg) {
        $key = (string) $cfg['category_key'];
        $maxPoints = (float) $cfg['max_points'];
        $entry = match ($key) {
            'profile_completion' => getProfileScore($pdo, $userId, $maxPoints),
            'verified_documents' => getVerifiedDocumentsScore($pdo, $userId, $maxPoints),
            'banking_readiness' => getBankingScore($pdo, $userId, $maxPoints),
            'training_capacity' => getTrainingScore($pdo, $userId, $maxPoints),
            'business_compliance' => getComplianceScore($pdo, $userId, $maxPoints),
            default => [
                'points_awarded' => 0.0,
                'max_points' => $maxPoints,
                'percentage' => 0.0,
                'details' => ['note' => 'Unknown category.'],
            ],
        };
        $entry['category_key'] = $key;
        $entry['category_name'] = (string) $cfg['category_name'];
        $breakdown[$key] = $entry;
        $total += (float) $entry['points_awarded'];
        $maxTotal += (float) $entry['max_points'];
    }
    $normalized = $maxTotal > 0 ? round(($total / $maxTotal) * 100, 2) : 0.0;
    $tier = mscore_tier_for_score($normalized);
    $readiness = mscore_readiness_label($normalized);
    $recommendations = mscore_recommendations_from_breakdown($breakdown);

    $pdo->beginTransaction();
    try {
        $history = $pdo->prepare('
            INSERT INTO mscore_score_history (user_id, m_id, total_score, tier_label, breakdown_json, calculation_notes, calculated_at)
            VALUES (:user_id, :m_id, :total_score, :tier_label, :breakdown_json, :notes, NOW())
        ');
        $history->execute([
            'user_id' => $userId,
            'm_id' => (string) $user['m_id'],
            'total_score' => $normalized,
            'tier_label' => $tier,
            'breakdown_json' => json_encode($breakdown, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'notes' => 'Calculated from active category settings and current user records.',
        ]);

        $clear = $pdo->prepare('DELETE FROM mscore_category_results WHERE user_id = :user_id');
        $clear->execute(['user_id' => $userId]);
        $catIns = $pdo->prepare('
            INSERT INTO mscore_category_results (user_id, category_key, points_awarded, max_points, details_json, calculated_at)
            VALUES (:user_id, :category_key, :points_awarded, :max_points, :details_json, NOW())
        ');
        foreach ($breakdown as $item) {
            $catIns->execute([
                'user_id' => $userId,
                'category_key' => (string) $item['category_key'],
                'points_awarded' => (float) $item['points_awarded'],
                'max_points' => (float) $item['max_points'],
                'details_json' => json_encode($item['details'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }

        $upCurrent = $pdo->prepare('
            INSERT INTO mscore_current_scores (user_id, m_id, total_score, tier_label, readiness_label, breakdown_json, recommendations_json, calculated_at, updated_at)
            VALUES (:user_id, :m_id, :total_score, :tier_label, :readiness_label, :breakdown_json, :recommendations_json, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              m_id = VALUES(m_id),
              total_score = VALUES(total_score),
              tier_label = VALUES(tier_label),
              readiness_label = VALUES(readiness_label),
              breakdown_json = VALUES(breakdown_json),
              recommendations_json = VALUES(recommendations_json),
              calculated_at = VALUES(calculated_at),
              updated_at = NOW()
        ');
        $upCurrent->execute([
            'user_id' => $userId,
            'm_id' => (string) $user['m_id'],
            'total_score' => $normalized,
            'tier_label' => $tier,
            'readiness_label' => $readiness,
            'breakdown_json' => json_encode($breakdown, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'recommendations_json' => json_encode($recommendations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (mscore_table_exists($pdo, 'm_scores')) {
            $tierSlug = strtolower(str_replace(' ', '_', $tier));
            $upLegacy = $pdo->prepare('
                INSERT INTO m_scores (user_id, score, tier, last_calculated_at, created_at, updated_at)
                VALUES (:user_id, :score, :tier, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  score = VALUES(score),
                  tier = VALUES(tier),
                  last_calculated_at = NOW(),
                  updated_at = NOW()
            ');
            $upLegacy->execute([
                'user_id' => $userId,
                'score' => $normalized,
                'tier' => $tierSlug,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'user_id' => $userId,
        'm_id' => (string) $user['m_id'],
        'total_score' => $normalized,
        'tier_label' => $tier,
        'readiness_label' => $readiness,
        'breakdown' => $breakdown,
        'recommendations' => $recommendations,
        'calculated_at' => date('Y-m-d H:i:s'),
    ];
}

/**
 * @return array<string,mixed>|null
 */
function mscore_current_for_user(int $userId): ?array
{
    $pdo = db();
    if (!mscore_table_exists($pdo, 'mscore_current_scores')) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM mscore_current_scores WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['breakdown'] = json_decode((string) $row['breakdown_json'], true) ?: [];
    $row['recommendations'] = json_decode((string) $row['recommendations_json'], true) ?: [];
    return $row;
}
