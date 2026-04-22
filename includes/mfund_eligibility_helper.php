<?php

declare(strict_types=1);

/**
 * Funding eligibility checks.
 */

function checkFundingEligibility(int $userId): array
{
    $pdo = db();
    $settings = mfund_settings_map($pdo);
    $minScore = (float) ($settings['minimum_mscore'] ?? 50);
    $minProfileCompletion = (float) ($settings['minimum_profile_completion'] ?? 60);
    $requiredVerifiedDocs = (int) ($settings['required_verified_docs_count'] ?? 2);
    $allowMultiple = ((string) ($settings['allow_multiple_open_applications'] ?? '0')) === '1';

    $scoreCurrent = mscore_current_for_user($userId);
    if ($scoreCurrent === null) {
        try {
            $scoreCurrent = calculateUserMScore($userId);
        } catch (Throwable) {
            $scoreCurrent = ['total_score' => 0];
        }
    }
    $score = (float) ($scoreCurrent['total_score'] ?? 0);

    $profStmt = $pdo->prepare('SELECT profile_completion FROM user_profiles WHERE user_id = :uid LIMIT 1');
    $profStmt->execute(['uid' => $userId]);
    $profileCompletion = (float) ($profStmt->fetchColumn() ?: 0);

    $verifiedDocCount = 0;
    if (mscore_table_exists($pdo, 'user_documents')) {
        $docStmt = $pdo->prepare('SELECT COUNT(*) FROM user_documents WHERE user_id = :uid AND status = "verified"');
        $docStmt->execute(['uid' => $userId]);
        $verifiedDocCount = (int) ($docStmt->fetchColumn() ?: 0);
    }

    $bankReady = false;
    if (mscore_table_exists($pdo, 'user_financial_profiles')) {
        $bankStmt = $pdo->prepare('
            SELECT bank_name, account_name, account_number, mobile_money_provider, mobile_money_number
            FROM user_financial_profiles
            WHERE user_id = :uid LIMIT 1
        ');
        $bankStmt->execute(['uid' => $userId]);
        $bank = $bankStmt->fetch() ?: [];
        $bankReady = trim((string) ($bank['bank_name'] ?? '')) !== ''
            && trim((string) ($bank['account_name'] ?? '')) !== ''
            && (trim((string) ($bank['account_number'] ?? '')) !== ''
                || trim((string) ($bank['mobile_money_number'] ?? '')) !== '');
    }

    $openConflict = false;
    if (!$allowMultiple && mscore_table_exists($pdo, 'funding_applications')) {
        $in = '"' . implode('","', mfund_open_statuses()) . '"';
        $q = $pdo->prepare('SELECT COUNT(*) FROM funding_applications WHERE user_id = :uid AND status IN (' . $in . ')');
        $q->execute(['uid' => $userId]);
        $openConflict = ((int) $q->fetchColumn()) > 0;
    }

    $checks = [
        'minimum_mscore' => [
            'ok' => $score >= $minScore,
            'message' => 'M-SCORE ' . number_format($score, 2) . ' / required ' . number_format($minScore, 2),
        ],
        'profile_completion' => [
            'ok' => $profileCompletion >= $minProfileCompletion,
            'message' => 'Profile completion ' . number_format($profileCompletion, 0) . '% / required ' . number_format($minProfileCompletion, 0) . '%',
        ],
        'verified_documents' => [
            'ok' => $verifiedDocCount >= $requiredVerifiedDocs,
            'message' => 'Verified documents ' . $verifiedDocCount . ' / required ' . $requiredVerifiedDocs,
        ],
        'banking_readiness' => [
            'ok' => $bankReady,
            'message' => $bankReady ? 'Financial details are available.' : 'Missing bank/financial profile details.',
        ],
        'open_application_policy' => [
            'ok' => !$openConflict,
            'message' => $openConflict ? 'You already have an open funding application.' : 'No conflicting open applications.',
        ],
    ];

    $missing = [];
    foreach ($checks as $check) {
        if (!$check['ok']) {
            $missing[] = $check['message'];
        }
    }
    return [
        'eligible' => $missing === [],
        'score' => $score,
        'min_score' => $minScore,
        'profile_completion' => $profileCompletion,
        'checks' => $checks,
        'missing_requirements' => $missing,
    ];
}

function getEligibilityRequirements(int $userId): array
{
    return checkFundingEligibility($userId);
}

function canUserApplyForFunding(int $userId): bool
{
    $elig = checkFundingEligibility($userId);
    return (bool) $elig['eligible'];
}
