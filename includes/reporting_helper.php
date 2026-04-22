<?php

declare(strict_types=1);

/**
 * CSV exports and report metadata. Streams output; caller must not print before headers.
 */

function reporting_csv_filename(string $reportType): string
{
    $safe = preg_replace('/[^a-z0-9_\-]+/i', '_', $reportType) ?: 'report';
    return 'mgrid_' . $safe . '_' . date('Y-m-d_His') . '.csv';
}

/**
 * @param 'users'|'funding'|'documents'|'mscore'|'training'|'benefits'|'opportunities' $reportType
 */
function exportReportToCsv(string $reportType, ?string $dateFrom = null, ?string $dateTo = null): void
{
    $pdo = db();
    $df = $dateFrom !== null && $dateFrom !== '' ? $dateFrom : null;
    $dt = $dateTo !== null && $dateTo !== '' ? $dateTo : null;

    $filename = reporting_csv_filename($reportType);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        return;
    }
    fwrite($out, "\xEF\xBB\xBF");

    switch ($reportType) {
        case 'users':
            fputcsv($out, ['user_id', 'm_id', 'full_name', 'email', 'phone', 'status', 'created_at']);
            $w = '1=1';
            $p = [];
            if ($df) {
                $w .= ' AND DATE(created_at) >= :df';
                $p['df'] = $df;
            }
            if ($dt) {
                $w .= ' AND DATE(created_at) <= :dt';
                $p['dt'] = $dt;
            }
            $st = $pdo->prepare("SELECT id, m_id, full_name, email, phone, status, created_at FROM users WHERE $w ORDER BY id ASC");
            $st->execute($p);
            while ($row = $st->fetch()) {
                fputcsv($out, [
                    (int) $row['id'],
                    (string) $row['m_id'],
                    (string) $row['full_name'],
                    (string) $row['email'],
                    (string) $row['phone'],
                    (string) $row['status'],
                    (string) $row['created_at'],
                ]);
            }
            break;

        case 'funding':
            if (!mscore_table_exists($pdo, 'funding_applications')) {
                fputcsv($out, ['error', 'funding_applications table not found']);
                break;
            }
            fputcsv($out, ['application_id', 'reference_number', 'user_id', 'm_id', 'type', 'requested_amount', 'status', 'submitted_at']);
            $w = '1=1';
            $p = [];
            if ($df) {
                $w .= ' AND DATE(submitted_at) >= :df';
                $p['df'] = $df;
            }
            if ($dt) {
                $w .= ' AND DATE(submitted_at) <= :dt';
                $p['dt'] = $dt;
            }
            $st = $pdo->prepare("SELECT id, reference_number, user_id, m_id, application_type, requested_amount, status, submitted_at FROM funding_applications WHERE $w ORDER BY id ASC");
            $st->execute($p);
            while ($row = $st->fetch()) {
                fputcsv($out, [
                    (int) $row['id'],
                    (string) $row['reference_number'],
                    (int) $row['user_id'],
                    (string) $row['m_id'],
                    (string) $row['application_type'],
                    (string) $row['requested_amount'],
                    (string) $row['status'],
                    (string) $row['submitted_at'],
                ]);
            }
            break;

        case 'documents':
            if (!mscore_table_exists($pdo, 'user_documents')) {
                fputcsv($out, ['error', 'user_documents table not found']);
                break;
            }
            fputcsv($out, ['document_id', 'user_id', 'm_id', 'title', 'status', 'uploaded_at', 'updated_at']);
            $w = '1=1';
            $p = [];
            if ($df) {
                $w .= ' AND DATE(uploaded_at) >= :df';
                $p['df'] = $df;
            }
            if ($dt) {
                $w .= ' AND DATE(uploaded_at) <= :dt';
                $p['dt'] = $dt;
            }
            $st = $pdo->prepare("SELECT id, user_id, m_id, title, status, uploaded_at, updated_at FROM user_documents WHERE $w ORDER BY id ASC");
            $st->execute($p);
            while ($row = $st->fetch()) {
                fputcsv($out, [
                    (int) $row['id'],
                    (int) $row['user_id'],
                    (string) $row['m_id'],
                    (string) $row['title'],
                    (string) $row['status'],
                    (string) $row['uploaded_at'],
                    (string) $row['updated_at'],
                ]);
            }
            break;

        case 'mscore':
            if (mscore_table_exists($pdo, 'mscore_current_scores')) {
                fputcsv($out, ['user_id', 'm_id', 'total_score', 'tier_label', 'calculated_at']);
                $st = $pdo->query('SELECT user_id, m_id, total_score, tier_label, calculated_at FROM mscore_current_scores ORDER BY user_id ASC');
                while ($row = $st->fetch()) {
                    fputcsv($out, [
                        (int) $row['user_id'],
                        (string) $row['m_id'],
                        (string) $row['total_score'],
                        (string) $row['tier_label'],
                        (string) $row['calculated_at'],
                    ]);
                }
                break;
            }
            if (mscore_table_exists($pdo, 'm_scores')) {
                fputcsv($out, ['user_id', 'score', 'tier', 'last_calculated_at']);
                $st = $pdo->query('SELECT user_id, score, tier, last_calculated_at FROM m_scores ORDER BY user_id ASC');
                while ($row = $st->fetch()) {
                    fputcsv($out, [
                        (int) $row['user_id'],
                        (string) $row['score'],
                        (string) $row['tier'],
                        (string) ($row['last_calculated_at'] ?? ''),
                    ]);
                }
                break;
            }
            fputcsv($out, ['error', 'No M-SCORE snapshot table']);
            break;

        case 'training':
            if (!mscore_table_exists($pdo, 'training_registrations')) {
                fputcsv($out, ['error', 'training_registrations not found']);
                break;
            }
            fputcsv($out, ['registration_id', 'user_id', 'program_id', 'status', 'participation_status', 'certificate_status', 'applied_at']);
            $w = '1=1';
            $p = [];
            if ($df) {
                $w .= ' AND DATE(applied_at) >= :df';
                $p['df'] = $df;
            }
            if ($dt) {
                $w .= ' AND DATE(applied_at) <= :dt';
                $p['dt'] = $dt;
            }
            $st = $pdo->prepare("SELECT id, user_id, training_program_id, status, participation_status, certificate_status, applied_at FROM training_registrations WHERE $w ORDER BY id ASC");
            $st->execute($p);
            while ($row = $st->fetch()) {
                fputcsv($out, [
                    (int) $row['id'],
                    (int) $row['user_id'],
                    (int) $row['training_program_id'],
                    (string) $row['status'],
                    (string) $row['participation_status'],
                    (string) $row['certificate_status'],
                    (string) $row['applied_at'],
                ]);
            }
            break;

        case 'benefits':
            if (!mscore_table_exists($pdo, 'benefit_claims')) {
                fputcsv($out, ['error', 'benefit_claims not found']);
                break;
            }
            fputcsv($out, ['claim_id', 'user_id', 'offer_id', 'reference', 'status', 'claimed_at']);
            $w = '1=1';
            $p = [];
            if ($df) {
                $w .= ' AND DATE(claimed_at) >= :df';
                $p['df'] = $df;
            }
            if ($dt) {
                $w .= ' AND DATE(claimed_at) <= :dt';
                $p['dt'] = $dt;
            }
            $st = $pdo->prepare("SELECT id, user_id, benefit_offer_id, claim_reference, status, claimed_at FROM benefit_claims WHERE $w ORDER BY id ASC");
            $st->execute($p);
            while ($row = $st->fetch()) {
                fputcsv($out, [
                    (int) $row['id'],
                    (int) $row['user_id'],
                    (int) $row['benefit_offer_id'],
                    (string) $row['claim_reference'],
                    (string) $row['status'],
                    (string) $row['claimed_at'],
                ]);
            }
            break;

        case 'opportunities':
            if (!mscore_table_exists($pdo, 'opportunity_applications')) {
                fputcsv($out, ['error', 'opportunity_applications not found']);
                break;
            }
            fputcsv($out, ['application_id', 'user_id', 'opportunity_id', 'status', 'applied_at']);
            $w = '1=1';
            $p = [];
            if ($df) {
                $w .= ' AND DATE(applied_at) >= :df';
                $p['df'] = $df;
            }
            if ($dt) {
                $w .= ' AND DATE(applied_at) <= :dt';
                $p['dt'] = $dt;
            }
            $st = $pdo->prepare("SELECT id, user_id, opportunity_id, status, applied_at FROM opportunity_applications WHERE $w ORDER BY id ASC");
            $st->execute($p);
            while ($row = $st->fetch()) {
                fputcsv($out, [
                    (int) $row['id'],
                    (int) $row['user_id'],
                    (int) $row['opportunity_id'],
                    (string) $row['status'],
                    (string) $row['applied_at'],
                ]);
            }
            break;

        default:
            fputcsv($out, ['error', 'Unknown report type']);
    }

    fclose($out);
}
