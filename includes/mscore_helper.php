<?php

declare(strict_types=1);

function mscore_tier_for_score(float $score): string
{
    if ($score >= 75.0) {
        return 'Investment Ready';
    }
    if ($score >= 50.0) {
        return 'Growth';
    }
    if ($score >= 25.0) {
        return 'Emerging';
    }
    return 'Starter';
}

function mscore_tier_badge_class(string $tier): string
{
    return match ($tier) {
        'Investment Ready' => 'success',
        'Growth' => 'warning',
        'Emerging' => 'info',
        default => 'secondary',
    };
}

function mscore_readiness_label(float $score): string
{
    if ($score >= 75.0) {
        return 'High readiness for funding and partnerships';
    }
    if ($score >= 50.0) {
        return 'Growing readiness, complete a few key milestones';
    }
    if ($score >= 25.0) {
        return 'Early progress, more milestones needed';
    }
    return 'Foundational stage, start with profile and verification';
}

/**
 * @param array<string,array<string,mixed>> $breakdown
 * @return array<int,string>
 */
function mscore_recommendations_from_breakdown(array $breakdown): array
{
    $recommendations = [];
    foreach ($breakdown as $key => $entry) {
        $pct = (float) ($entry['percentage'] ?? 0.0);
        if ($pct >= 99.9) {
            continue;
        }
        if ($key === 'profile_completion') {
            $recommendations[] = 'Complete remaining M-Profile fields to unlock full profile points.';
        } elseif ($key === 'verified_documents') {
            $recommendations[] = 'Upload and verify more key documents such as BRELA, TRA/TIN and bank statement.';
        } elseif ($key === 'banking_readiness') {
            $recommendations[] = 'Add bank details or mobile money financial profile to improve readiness.';
        } elseif ($key === 'training_capacity') {
            $recommendations[] = 'Complete verified trainings to strengthen capacity-building score.';
        } elseif ($key === 'business_compliance') {
            $recommendations[] = 'Verify formalization documents (license, registration, tax) to improve compliance score.';
        }
    }
    if ($recommendations === []) {
        $recommendations[] = 'Great progress! Maintain document validity and continue growing your business profile.';
    }
    return $recommendations;
}
