<?php

declare(strict_types=1);

/**
 * M-BENEFITS: offers, eligibility, claims, badges, claim references.
 */

function mbenefits_module_ready(PDO $pdo): bool
{
    return mscore_table_exists($pdo, 'benefit_offers');
}

function mbenefits_claim_status_badge(string $status): string
{
    return match ($status) {
        'approved', 'redeemed' => 'success',
        'pending' => 'warning',
        'rejected', 'cancelled' => 'danger',
        default => 'secondary',
    };
}

function mbenefits_claim_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'redeemed' => 'Redeemed',
        'cancelled' => 'Cancelled',
        default => ucfirst($status),
    };
}

function mbenefits_benefit_type_label(string $type): string
{
    return match ($type) {
        'discount' => 'Discount',
        'credit' => 'Credit',
        'voucher' => 'Voucher',
        'service' => 'Service',
        default => 'Other',
    };
}

function mbenefits_benefit_type_badge(string $type): string
{
    return match ($type) {
        'discount' => 'primary',
        'credit' => 'info',
        'voucher' => 'success',
        'service' => 'secondary',
        default => 'dark',
    };
}

/** Map display tier label to canonical slug for rules. */
function mbenefits_tier_slug_from_label(string $tierLabel): string
{
    $s = strtolower(trim($tierLabel));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? $s;
    $s = trim((string) $s, '_');
    if ($s === 'investment_ready' || str_contains($tierLabel, 'Investment')) {
        return 'investment_ready';
    }
    if ($s === 'starter') {
        return 'starter';
    }
    if ($s === 'emerging') {
        return 'emerging';
    }
    if ($s === 'growth') {
        return 'growth';
    }
    return $s !== '' ? $s : 'starter';
}

function mbenefits_tier_min_rank(string $slug): int
{
    $slug = strtolower(trim($slug));
    return match ($slug) {
        'investment_ready', 'investmentready' => 4,
        'growth' => 3,
        'emerging' => 2,
        'starter' => 1,
        default => 1,
    };
}

/**
 * @return array{score:float,tier_label:string,tier_slug:string,profile_completion:int}
 */
function mbenefits_user_context(PDO $pdo, int $userId): array
{
    $score = 0.0;
    $tierLabel = 'Starter';

    if (mscore_table_exists($pdo, 'mscore_current_scores')) {
        $st = $pdo->prepare('SELECT total_score, tier_label FROM mscore_current_scores WHERE user_id = :u LIMIT 1');
        $st->execute(['u' => $userId]);
        $row = $st->fetch();
        if ($row) {
            $score = (float) ($row['total_score'] ?? 0);
            $tierLabel = (string) ($row['tier_label'] ?? $tierLabel);
        }
    }
    if ($score <= 0.0 && mscore_table_exists($pdo, 'm_scores')) {
        $st = $pdo->prepare('SELECT score, tier FROM m_scores WHERE user_id = :u LIMIT 1');
        $st->execute(['u' => $userId]);
        $row = $st->fetch();
        if ($row) {
            $score = (float) ($row['score'] ?? 0);
            $tierLabel = (string) ($row['tier'] ?? $tierLabel);
        }
    }

    $prof = 0;
    if (mscore_table_exists($pdo, 'user_profiles')) {
        $st = $pdo->prepare('SELECT profile_completion FROM user_profiles WHERE user_id = :u LIMIT 1');
        $st->execute(['u' => $userId]);
        $prof = (int) ($st->fetchColumn() ?: 0);
    }

    return [
        'score' => $score,
        'tier_label' => $tierLabel,
        'tier_slug' => mbenefits_tier_slug_from_label($tierLabel),
        'profile_completion' => $prof,
    ];
}

function mbenefits_user_has_verified_document(PDO $pdo, int $userId): bool
{
    if (!mscore_table_exists($pdo, 'user_documents')) {
        return false;
    }
    $st = $pdo->prepare('SELECT 1 FROM user_documents WHERE user_id = :u AND status = "verified" LIMIT 1');
    $st->execute(['u' => $userId]);
    return (bool) $st->fetchColumn();
}

/**
 * @return array<string,mixed>|null
 */
function mbenefits_get_offer(PDO $pdo, int $offerId): ?array
{
    $st = $pdo->prepare('
        SELECT o.*, c.name AS category_name, c.slug AS category_slug,
               p.name AS provider_name, p.slug AS provider_slug, p.contact_email AS provider_email,
               p.website_url AS provider_website, p.description AS provider_description
        FROM benefit_offers o
        INNER JOIN benefit_categories c ON c.id = o.category_id
        INNER JOIN benefit_providers p ON p.id = o.provider_id
        WHERE o.id = :id
        LIMIT 1
    ');
    $st->execute(['id' => $offerId]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * @return array{ok:bool,messages:array<int,string>}
 */
function mbenefits_evaluate_eligibility(PDO $pdo, int $userId, array $offer): array
{
    $messages = [];

    if ((int) ($offer['is_active'] ?? 0) !== 1) {
        $messages[] = 'This offer is not active.';
    }

    $today = date('Y-m-d');
    $from = (string) ($offer['valid_from'] ?? '');
    $to = (string) ($offer['valid_to'] ?? '');
    if ($from !== '' && $today < $from) {
        $messages[] = 'This offer is not yet valid.';
    }
    if ($to !== '' && $today > $to) {
        $messages[] = 'This offer has expired.';
    }

    $ctx = mbenefits_user_context($pdo, $userId);
    $minScore = (float) ($offer['min_mscore'] ?? 0);
    if ($ctx['score'] + 0.00001 < $minScore) {
        $messages[] = 'Your M-SCORE must be at least ' . number_format($minScore, 2) . ' (yours is ' . number_format($ctx['score'], 2) . ').';
    }

    $reqTier = trim((string) ($offer['eligible_tier'] ?? ''));
    if ($reqTier !== '') {
        $need = mbenefits_tier_min_rank($reqTier);
        $have = mbenefits_tier_min_rank($ctx['tier_slug']);
        if ($have < $need) {
            $messages[] = 'Requires tier ' . ucwords(str_replace('_', ' ', strtolower($reqTier))) . ' or higher (you are ' . $ctx['tier_label'] . ').';
        }
    }

    if ((int) ($offer['requires_verified_documents'] ?? 0) === 1 && !mbenefits_user_has_verified_document($pdo, $userId)) {
        $messages[] = 'At least one verified document is required.';
    }

    $needProf = (int) ($offer['requires_profile_complete_percent'] ?? 0);
    if ($needProf > 0 && $ctx['profile_completion'] < $needProf) {
        $messages[] = 'M-Profile completion must be at least ' . $needProf . '% (yours is ' . $ctx['profile_completion'] . '%).';
    }

    $allowRepeat = (int) ($offer['allow_repeat_claims'] ?? 0) === 1;
    $oid = (int) ($offer['id']);
    if ($oid > 0) {
        if ($allowRepeat) {
            $st = $pdo->prepare('
                SELECT COUNT(*) FROM benefit_claims
                WHERE user_id = :u AND benefit_offer_id = :o
                  AND status IN ("pending","approved")
            ');
        } else {
            $st = $pdo->prepare('
                SELECT COUNT(*) FROM benefit_claims
                WHERE user_id = :u AND benefit_offer_id = :o
                  AND status NOT IN ("rejected","cancelled")
            ');
        }
        $st->execute(['u' => $userId, 'o' => $oid]);
        if ((int) $st->fetchColumn() > 0) {
            $messages[] = $allowRepeat
                ? 'You already have a pending or approved claim for this offer.'
                : 'You have already claimed this offer.';
        }
    }

    return [
        'ok' => $messages === [],
        'messages' => $messages,
    ];
}

function mbenefits_can_user_claim_benefit(PDO $pdo, int $userId, int $benefitId): bool
{
    if (!mbenefits_module_ready($pdo)) {
        return false;
    }
    $offer = mbenefits_get_offer($pdo, $benefitId);
    if ($offer === null) {
        return false;
    }
    return mbenefits_evaluate_eligibility($pdo, $userId, $offer)['ok'];
}

function mbenefits_get_eligibility_message(PDO $pdo, int $userId, int $benefitId): string
{
    if (!mbenefits_module_ready($pdo)) {
        return 'Benefits module is not installed.';
    }
    $offer = mbenefits_get_offer($pdo, $benefitId);
    if ($offer === null) {
        return 'Benefit not found.';
    }
    $ev = mbenefits_evaluate_eligibility($pdo, $userId, $offer);
    if ($ev['ok']) {
        return 'You are eligible to claim this benefit.';
    }
    return implode(' ', $ev['messages']);
}

/**
 * @return list<array<string,mixed>>
 */
function mbenefits_get_eligible_offers_for_user(PDO $pdo, int $userId): array
{
    if (!mbenefits_module_ready($pdo)) {
        return [];
    }
    $st = $pdo->query('
        SELECT o.*, c.name AS category_name, p.name AS provider_name
        FROM benefit_offers o
        INNER JOIN benefit_categories c ON c.id = o.category_id AND c.is_active = 1
        INNER JOIN benefit_providers p ON p.id = o.provider_id AND p.is_active = 1
        WHERE o.is_active = 1
          AND o.valid_from <= CURDATE() AND o.valid_to >= CURDATE()
        ORDER BY c.sort_order ASC, o.title ASC
    ');
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $row) {
        if (mbenefits_evaluate_eligibility($pdo, $userId, $row)['ok']) {
            $out[] = $row;
        }
    }
    return $out;
}

/**
 * @return list<array<string,mixed>>
 */
function mbenefits_list_active_offers(PDO $pdo, ?int $categoryId = null): array
{
    if (!mbenefits_module_ready($pdo)) {
        return [];
    }
    $sql = '
        SELECT o.*, c.name AS category_name, c.slug AS category_slug, p.name AS provider_name
        FROM benefit_offers o
        INNER JOIN benefit_categories c ON c.id = o.category_id AND c.is_active = 1
        INNER JOIN benefit_providers p ON p.id = o.provider_id AND p.is_active = 1
        WHERE o.is_active = 1
          AND o.valid_from <= CURDATE() AND o.valid_to >= CURDATE()
    ';
    $params = [];
    if ($categoryId !== null && $categoryId > 0) {
        $sql .= ' AND o.category_id = :cid';
        $params['cid'] = $categoryId;
    }
    $sql .= ' ORDER BY c.sort_order ASC, o.title ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
}

function mbenefits_generate_claim_reference(PDO $pdo): string
{
    $year = (int) date('Y');
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT year, last_number FROM benefit_claim_counters WHERE year = :y FOR UPDATE');
        $lock->execute(['y' => $year]);
        $row = $lock->fetch();
        if (!$row) {
            $pdo->prepare('INSERT INTO benefit_claim_counters (year, last_number) VALUES (:y, 0)')->execute(['y' => $year]);
            $last = 0;
        } else {
            $last = (int) $row['last_number'];
        }
        $next = $last + 1;
        $pdo->prepare('UPDATE benefit_claim_counters SET last_number = :n WHERE year = :y')->execute(['n' => $next, 'y' => $year]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return sprintf('MB-%d-%05d', $year, $next);
}

function mbenefits_log_claim_change(PDO $pdo, int $claimId, ?int $adminId, ?int $actorUserId, ?string $oldStatus, string $newStatus, ?string $note): void
{
    $st = $pdo->prepare('
        INSERT INTO benefit_claim_logs (claim_id, admin_id, user_id, old_status, new_status, note)
        VALUES (:cid, :aid, :uid, :os, :ns, :note)
    ');
    $st->execute([
        'cid' => $claimId,
        'aid' => $adminId,
        'uid' => $actorUserId,
        'os' => $oldStatus,
        'ns' => $newStatus,
        'note' => $note,
    ]);
}

/** @param array<string,mixed> $offer */
function mbenefits_eligibility_rule_summary(array $offer): string
{
    $parts = [];
    $parts[] = 'M-SCORE ≥ ' . number_format((float) ($offer['min_mscore'] ?? 0), 2);
    $t = trim((string) ($offer['eligible_tier'] ?? ''));
    if ($t !== '') {
        $parts[] = 'Tier: ' . ucwords(str_replace('_', ' ', strtolower($t))) . '+';
    }
    if ((int) ($offer['requires_verified_documents'] ?? 0) === 1) {
        $parts[] = 'Verified document(s)';
    }
    $p = (int) ($offer['requires_profile_complete_percent'] ?? 0);
    if ($p > 0) {
        $parts[] = 'Profile ≥ ' . $p . '%';
    }
    if ((int) ($offer['allow_repeat_claims'] ?? 0) !== 1) {
        $parts[] = 'One claim per member';
    }
    return implode(' · ', $parts);
}

/* --- Public API names requested in spec (use db()) --- */

function canUserClaimBenefit(int $userId, int $benefitId): bool
{
    return mbenefits_can_user_claim_benefit(db(), $userId, $benefitId);
}

function getBenefitEligibilityMessage(int $userId, int $benefitId): string
{
    return mbenefits_get_eligibility_message(db(), $userId, $benefitId);
}

/**
 * @return list<array<string,mixed>>
 */
function getEligibleBenefitsForUser(int $userId): array
{
    return mbenefits_get_eligible_offers_for_user(db(), $userId);
}
