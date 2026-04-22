<?php

declare(strict_types=1);

/**
 * Status badges and labels for opportunity applications and training registrations.
 */

function opportunity_application_status_badge(string $status): string
{
    return match ($status) {
        'accepted', 'shortlisted' => 'success',
        'under_review' => 'warning',
        'submitted' => 'info',
        'rejected', 'withdrawn' => 'secondary',
        default => 'secondary',
    };
}

function opportunity_application_status_label(string $status): string
{
    return match ($status) {
        'under_review' => 'Under Review',
        'shortlisted' => 'Shortlisted',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function opportunity_completion_status_label(string $status): string
{
    return match ($status) {
        'n_a' => '—',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default => $status,
    };
}

function opportunity_certificate_status_label(string $status): string
{
    return match ($status) {
        'n_a' => '—',
        'none' => 'None',
        'issued' => 'Issued',
        'verified' => 'Verified',
        default => $status,
    };
}

function training_registration_status_badge(string $status): string
{
    return match ($status) {
        'approved' => 'success',
        'pending', 'waitlisted' => 'warning',
        'rejected', 'cancelled' => 'secondary',
        default => 'secondary',
    };
}

function training_registration_status_label(string $status): string
{
    return match ($status) {
        'waitlisted' => 'Waitlisted',
        default => ucwords($status),
    };
}

function training_participation_status_badge(string $status): string
{
    return match ($status) {
        'completed' => 'success',
        'attended' => 'info',
        'registered' => 'warning',
        'no_show', 'excused' => 'secondary',
        default => 'secondary',
    };
}

function training_participation_status_label(string $status): string
{
    return match ($status) {
        'no_show' => 'No show',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function training_certificate_status_badge(string $status): string
{
    return match ($status) {
        'verified' => 'success',
        'issued', 'pending_verification' => 'warning',
        'rejected' => 'danger',
        default => 'secondary',
    };
}

function training_certificate_status_label(string $status): string
{
    return match ($status) {
        'pending_verification' => 'Pending verification',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}
