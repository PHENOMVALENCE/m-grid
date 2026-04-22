<?php

declare(strict_types=1);

/**
 * Admin announcements: draft, targeted recipients, fan-out to notifications.
 */

function announcements_module_ready(PDO $pdo): bool
{
    return mscore_table_exists($pdo, 'announcements');
}

/**
 * @return int|null announcement id
 */
function announcement_create_draft(
    PDO $pdo,
    int $adminId,
    string $title,
    string $message,
    string $targetScope,
    ?string $targetTier,
    array $explicitUserIds
): ?int {
    if (!announcements_module_ready($pdo)) {
        return null;
    }
    if (!in_array($targetScope, ['all', 'tier', 'users'], true)) {
        $targetScope = 'all';
    }
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('
            INSERT INTO announcements (
              created_by_admin_id, title, message, target_scope, target_tier, status
            ) VALUES (:aid, :t, :m, :scope, :tier, "draft")
        ');
        $st->execute([
            'aid' => $adminId,
            't' => $title,
            'm' => $message,
            'scope' => $targetScope,
            'tier' => $targetTier !== null && $targetTier !== '' ? $targetTier : null,
        ]);
        $annId = (int) $pdo->lastInsertId();
        if ($targetScope === 'users' && $explicitUserIds !== []) {
            $insT = $pdo->prepare('INSERT IGNORE INTO announcement_targets (announcement_id, user_id) VALUES (:a, :u)');
            foreach (array_unique(array_filter($explicitUserIds)) as $uid) {
                $uid = (int) $uid;
                if ($uid > 0) {
                    $insT->execute(['a' => $annId, 'u' => $uid]);
                }
            }
        }
        $pdo->commit();
        return $annId > 0 ? $annId : null;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return null;
    }
}

/**
 * Resolves recipient user IDs for an announcement (not yet sent).
 *
 * @return list<int>
 */
function announcement_resolve_recipient_user_ids(PDO $pdo, int $announcementId): array
{
    if (!announcements_module_ready($pdo) || $announcementId <= 0) {
        return [];
    }
    $st = $pdo->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
    $st->execute(['id' => $announcementId]);
    $row = $st->fetch();
    if (!$row) {
        return [];
    }
    $scope = (string) ($row['target_scope'] ?? 'all');
    $ids = [];

    if ($scope === 'all') {
        $q = $pdo->query('SELECT id FROM users WHERE status = "active"');
        $ids = array_map(static fn ($r) => (int) $r['id'], $q->fetchAll() ?: []);
    } elseif ($scope === 'tier') {
        $tier = trim((string) ($row['target_tier'] ?? ''));
        if ($tier === '') {
            return [];
        }
        $seen = [];
        if (mscore_table_exists($pdo, 'mscore_current_scores')) {
            $st2 = $pdo->prepare('SELECT user_id FROM mscore_current_scores WHERE LOWER(tier_label) = LOWER(:t)');
            $st2->execute(['t' => $tier]);
            foreach ($st2->fetchAll() ?: [] as $r) {
                $uid = (int) $r['user_id'];
                $seen[$uid] = true;
                $ids[] = $uid;
            }
        }
        if (mscore_table_exists($pdo, 'm_scores')) {
            $st3 = $pdo->prepare('SELECT user_id FROM m_scores WHERE LOWER(tier) = LOWER(:t)');
            $st3->execute(['t' => $tier]);
            foreach ($st3->fetchAll() ?: [] as $r) {
                $uid = (int) $r['user_id'];
                if (!isset($seen[$uid])) {
                    $seen[$uid] = true;
                    $ids[] = $uid;
                }
            }
        }
    } elseif ($scope === 'users') {
        $st4 = $pdo->prepare('SELECT user_id FROM announcement_targets WHERE announcement_id = :a');
        $st4->execute(['a' => $announcementId]);
        $ids = array_map(static fn ($r) => (int) $r['user_id'], $st4->fetchAll() ?: []);
    }

    return array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
}

/**
 * Fan-out: creates one notification per recipient, delivery log rows, marks announcement sent.
 *
 * @return array{ok:bool, count:int, error?:string}
 */
function sendAnnouncementToUsers(int $announcementId): array
{
    $pdo = db();
    if (!announcements_module_ready($pdo) || !notifications_module_ready($pdo) || $announcementId <= 0) {
        return ['ok' => false, 'count' => 0, 'error' => 'Module not ready.'];
    }
    $st = $pdo->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
    $st->execute(['id' => $announcementId]);
    $ann = $st->fetch();
    if (!$ann) {
        return ['ok' => false, 'count' => 0, 'error' => 'Not found.'];
    }
    if ((string) ($ann['status'] ?? '') !== 'draft') {
        return ['ok' => false, 'count' => 0, 'error' => 'Already sent or cancelled.'];
    }

    $userIds = announcement_resolve_recipient_user_ids($pdo, $announcementId);
    if ($userIds === []) {
        return ['ok' => false, 'count' => 0, 'error' => 'No recipients resolved.'];
    }

    $title = (string) $ann['title'];
    $message = (string) $ann['message'];
    $annId = (int) $ann['id'];

    try {
        $pdo->beginTransaction();
        $insN = $pdo->prepare('
            INSERT INTO notifications (
              user_id, title, message, type, source_module, related_record_id, action_url, announcement_id
            ) VALUES (:uid, :t, :m, "info", "announcements", NULL, :url, :annid)
        ');
        $insL = null;
        if (mscore_table_exists($pdo, 'notification_delivery_log')) {
            $insL = $pdo->prepare('
                INSERT INTO notification_delivery_log (announcement_id, notification_id, user_id, channel)
                VALUES (:annid, :nid, :uid, "in_app")
            ');
        }

        $count = 0;
        foreach ($userIds as $uid) {
            $insN->execute([
                'uid' => $uid,
                't' => $title,
                'm' => $message,
                'url' => url('user/notifications.php'),
                'annid' => $annId,
            ]);
            $nid = (int) $pdo->lastInsertId();
            if ($insL !== null && $nid > 0) {
                $insL->execute(['annid' => $annId, 'nid' => $nid, 'uid' => $uid]);
            }
            $count++;
        }

        $pdo->prepare('
            UPDATE announcements SET status = "sent", sent_at = NOW(), recipient_count = :c WHERE id = :id LIMIT 1
        ')->execute(['c' => $count, 'id' => $annId]);

        $pdo->commit();
        return ['ok' => true, 'count' => $count];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'count' => 0, 'error' => 'Send failed.'];
    }
}
