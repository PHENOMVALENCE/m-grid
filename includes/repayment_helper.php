<?php

declare(strict_types=1);

function generateRepaymentSchedule(PDO $pdo, int $applicationId, float $approvedAmount, int $months, string $startDate): void
{
    if ($months <= 0) {
        throw new RuntimeException('Repayment duration must be greater than zero.');
    }
    $base = round($approvedAmount / $months, 2);
    $remaining = $approvedAmount;

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM funding_repayment_schedules WHERE application_id = :app_id');
        $del->execute(['app_id' => $applicationId]);

        $ins = $pdo->prepare('
            INSERT INTO funding_repayment_schedules (application_id, installment_number, due_date, expected_amount, paid_amount, status, created_at, updated_at)
            VALUES (:application_id, :installment_number, :due_date, :expected_amount, 0, "pending", NOW(), NOW())
        ');
        $date = new DateTimeImmutable($startDate);
        for ($i = 1; $i <= $months; $i++) {
            $amount = $i === $months ? round($remaining, 2) : $base;
            $remaining = round($remaining - $amount, 2);
            $due = $date->modify('+' . ($i - 1) . ' month')->format('Y-m-d');
            $ins->execute([
                'application_id' => $applicationId,
                'installment_number' => $i,
                'due_date' => $due,
                'expected_amount' => $amount,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function updateRepaymentScheduleStatus(PDO $pdo, int $scheduleId): void
{
    $s = $pdo->prepare('SELECT expected_amount, paid_amount, due_date FROM funding_repayment_schedules WHERE id = :id LIMIT 1');
    $s->execute(['id' => $scheduleId]);
    $row = $s->fetch();
    if (!$row) {
        return;
    }
    $expected = (float) $row['expected_amount'];
    $paid = (float) $row['paid_amount'];
    $due = (string) $row['due_date'];
    $today = date('Y-m-d');

    $status = 'pending';
    $paidAt = null;
    if ($paid <= 0.0) {
        $status = $due < $today ? 'overdue' : 'pending';
    } elseif ($paid + 0.009 >= $expected) {
        $status = 'paid';
        $paidAt = date('Y-m-d H:i:s');
    } else {
        $status = $due < $today ? 'overdue' : 'partial';
    }
    $u = $pdo->prepare('UPDATE funding_repayment_schedules SET status = :status, paid_at = :paid_at, updated_at = NOW() WHERE id = :id');
    $u->execute(['status' => $status, 'paid_at' => $paidAt, 'id' => $scheduleId]);
}

function fundingRepaymentTotals(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare('
        SELECT
          COALESCE(SUM(expected_amount), 0) AS expected_total,
          COALESCE(SUM(paid_amount), 0) AS paid_total,
          SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) AS overdue_count
        FROM funding_repayment_schedules
        WHERE application_id = :app
    ');
    $stmt->execute(['app' => $applicationId]);
    $r = $stmt->fetch() ?: ['expected_total' => 0, 'paid_total' => 0, 'overdue_count' => 0];
    $expected = (float) $r['expected_total'];
    $paid = (float) $r['paid_total'];
    return [
        'expected_total' => $expected,
        'paid_total' => $paid,
        'balance' => max(0, round($expected - $paid, 2)),
        'overdue_count' => (int) $r['overdue_count'],
    ];
}
