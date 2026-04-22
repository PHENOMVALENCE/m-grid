<?php

declare(strict_types=1);

/**
 * In-app notifications: create, read state, unread queries.
 */

function notifications_module_ready(PDO $pdo): bool
{
    return mscore_table_exists($pdo, 'notifications');
}

/**
 * @param 'info'|'success'|'warning'|'alert' $type
 * @return int|null New notification id, or null if module/table missing or insert failed
 */
function createNotification(
    int $userId,
    string $title,
    string $message,
    string $type = 'info',
    string $sourceModule = 'system',
    ?int $relatedRecordId = null,
    ?string $actionUrl = null,
    ?int $announcementId = null
): ?int {
    $pdo = db();
    if (!notifications_module_ready($pdo) || $userId <= 0) {
        return null;
    }
    if (!in_array($type, ['info', 'success', 'warning', 'alert'], true)) {
        $type = 'info';
    }
    $sourceModule = preg_replace('/[^a-z0-9_\-]+/i', '_', $sourceModule) ?: 'system';
    if (strlen($sourceModule) > 64) {
        $sourceModule = substr($sourceModule, 0, 64);
    }
    try {
        $st = $pdo->prepare('
            INSERT INTO notifications (
              user_id, title, message, type, source_module, related_record_id, action_url, announcement_id
            ) VALUES (:uid, :t, :m, :ty, :sm, :rid, :url, :aid)
        ');
        $st->execute([
            'uid' => $userId,
            't' => $title,
            'm' => $message,
            'ty' => $type,
            'sm' => $sourceModule,
            'rid' => $relatedRecordId,
            'url' => $actionUrl !== null && $actionUrl !== '' ? $actionUrl : null,
            'aid' => $announcementId,
        ]);
        $id = (int) $pdo->lastInsertId();
        return $id > 0 ? $id : null;
    } catch (Throwable $e) {
        return null;
    }
}

function markNotificationAsRead(int $notificationId, int $userId): bool
{
    $pdo = db();
    if (!notifications_module_ready($pdo) || $notificationId <= 0 || $userId <= 0) {
        return false;
    }
    $st = $pdo->prepare('
        UPDATE notifications SET is_read = 1, read_at = NOW()
        WHERE id = :id AND user_id = :uid AND is_read = 0
        LIMIT 1
    ');
    $st->execute(['id' => $notificationId, 'uid' => $userId]);
    return $st->rowCount() > 0;
}

function markAllNotificationsReadForUser(int $userId): int
{
    $pdo = db();
    if (!notifications_module_ready($pdo) || $userId <= 0) {
        return 0;
    }
    $st = $pdo->prepare('
        UPDATE notifications SET is_read = 1, read_at = NOW()
        WHERE user_id = :uid AND is_read = 0
    ');
    $st->execute(['uid' => $userId]);
    return $st->rowCount();
}

/**
 * @return list<array<string,mixed>>
 */
function getUnreadNotifications(int $userId, int $limit = 20): array
{
    $pdo = db();
    if (!notifications_module_ready($pdo) || $userId <= 0) {
        return [];
    }
    $lim = max(1, min(100, $limit));
    $st = $pdo->prepare('
        SELECT * FROM notifications
        WHERE user_id = :uid AND is_read = 0
        ORDER BY created_at DESC
        LIMIT ' . (int) $lim
    );
    $st->execute(['uid' => $userId]);
    return $st->fetchAll() ?: [];
}

function notifications_unread_count(int $userId): int
{
    $pdo = db();
    if (!notifications_module_ready($pdo) || $userId <= 0) {
        return 0;
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $st->execute(['uid' => $userId]);
    return (int) $st->fetchColumn();
}

/**
 * @return list<array<string,mixed>>
 */
function notifications_list_for_user(int $userId, ?bool $unreadOnly = null, int $limit = 50, int $offset = 0): array
{
    $pdo = db();
    if (!notifications_module_ready($pdo) || $userId <= 0) {
        return [];
    }
    $lim = max(1, min(200, $limit));
    $off = max(0, $offset);
    $where = 'user_id = :uid';
    $params = ['uid' => $userId];
    if ($unreadOnly === true) {
        $where .= ' AND is_read = 0';
    } elseif ($unreadOnly === false) {
        $where .= ' AND is_read = 1';
    }
    $sql = 'SELECT * FROM notifications WHERE ' . $where . ' ORDER BY created_at DESC LIMIT ' . (int) $lim . ' OFFSET ' . (int) $off;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
}

function notification_type_badge_class(string $type): string
{
    return match ($type) {
        'success' => 'success',
        'warning' => 'warning',
        'alert' => 'danger',
        default => 'info',
    };
}
