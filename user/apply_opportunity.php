<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user/opportunities.php');
}

$token = $_POST['_csrf'] ?? null;
if (!csrf_verify(is_string($token) ? $token : null)) {
    flash_set('error', 'Invalid session.');
    redirect('user/opportunities.php');
}

$pdo = db();
$uid = (int) auth_user()['user_id'];
$oid = (int) ($_POST['opportunity_id'] ?? 0);

if (!opportunities_module_ready($pdo) || $oid <= 0) {
    flash_set('error', 'Invalid listing.');
    redirect('user/opportunities.php');
}

$o = opportunities_get_by_id($pdo, $oid);
if ($o === null || (int) $o['is_archived'] === 1 || (int) $o['is_active'] !== 1) {
    flash_set('error', 'This listing is not available.');
    redirect('user/opportunities.php');
}

if (ot_opportunity_listing_state($o) !== 'active') {
    flash_set('error', 'Applications are closed for this listing.');
    redirect('user/opportunity_detail.php?id=' . $oid);
}

if ((int) ($o['apply_internal'] ?? 0) !== 1) {
    flash_set('error', 'Use the external application link for this programme.');
    redirect('user/opportunity_detail.php?id=' . $oid);
}

if (opportunities_user_has_active_application($pdo, $uid, $oid)) {
    flash_set('error', 'You already applied.');
    redirect('user/opportunity_detail.php?id=' . $oid);
}

$msg = clean_string($_POST['user_message'] ?? '');
if (strlen($msg) > 2000) {
    $msg = substr($msg, 0, 2000);
}

try {
    $pdo->prepare('
        INSERT INTO opportunity_applications (user_id, opportunity_id, status, user_message)
        VALUES (:u, :o, "submitted", :m)
    ')->execute(['u' => $uid, 'o' => $oid, 'm' => $msg !== '' ? $msg : null]);
    flash_set('success', 'Application submitted.');
    redirect('user/my_opportunities.php');
} catch (Throwable $e) {
    flash_set('error', 'Could not submit application.');
    redirect('user/opportunity_detail.php?id=' . $oid);
}
