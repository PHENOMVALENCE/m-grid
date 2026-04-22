<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user/trainings.php');
}

$token = $_POST['_csrf'] ?? null;
if (!csrf_verify(is_string($token) ? $token : null)) {
    flash_set('error', 'Invalid session.');
    redirect('user/trainings.php');
}

$pdo = db();
$uid = (int) auth_user()['user_id'];
$pid = (int) ($_POST['training_program_id'] ?? 0);

if (!trainings_module_ready($pdo) || $pid <= 0) {
    flash_set('error', 'Invalid programme.');
    redirect('user/trainings.php');
}

$p = trainings_get_by_id($pdo, $pid);
if ($p === null || (int) $p['is_archived'] === 1 || (int) $p['is_active'] !== 1) {
    flash_set('error', 'This programme is not available.');
    redirect('user/trainings.php');
}

if (ot_training_listing_state($p) !== 'active') {
    flash_set('error', 'Registration is closed.');
    redirect('user/training_detail.php?id=' . $pid);
}

if ((int) ($p['register_internal'] ?? 0) !== 1) {
    flash_set('error', 'Please use external registration for this programme.');
    redirect('user/training_detail.php?id=' . $pid);
}

if (trainings_user_has_active_registration($pdo, $uid, $pid)) {
    flash_set('error', 'You already have a registration.');
    redirect('user/training_detail.php?id=' . $pid);
}

$msg = clean_string($_POST['user_message'] ?? '');
if (strlen($msg) > 2000) {
    $msg = substr($msg, 0, 2000);
}

try {
    $pdo->prepare('
        INSERT INTO training_registrations (user_id, training_program_id, status, user_message)
        VALUES (:u, :p, "pending", :m)
    ')->execute(['u' => $uid, 'p' => $pid, 'm' => $msg !== '' ? $msg : null]);
    flash_set('success', 'Registration submitted. You will see status under My Trainings.');
    redirect('user/my_trainings.php');
} catch (Throwable $e) {
    flash_set('error', 'Could not register.');
    redirect('user/training_detail.php?id=' . $pid);
}
