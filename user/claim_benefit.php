<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user/benefits.php');
}

$token = $_POST['_csrf'] ?? null;
if (!csrf_verify(is_string($token) ? $token : null)) {
    flash_set('error', 'Invalid session token. Please try again.');
    redirect('user/benefits.php');
}

$bid = (int) ($_POST['benefit_offer_id'] ?? 0);
if (!mbenefits_module_ready($pdo) || $bid <= 0) {
    flash_set('error', 'Invalid benefit.');
    redirect('user/benefits.php');
}

$offer = mbenefits_get_offer($pdo, $bid);
if ($offer === null || (int) $offer['is_active'] !== 1) {
    flash_set('error', 'This offer is not available.');
    redirect('user/benefits.php');
}

if (!mbenefits_evaluate_eligibility($pdo, $uid, $offer)['ok']) {
    flash_set('error', getBenefitEligibilityMessage($uid, $bid));
    redirect('user/benefit_detail.php?id=' . $bid);
}

$notes = clean_string($_POST['user_notes'] ?? '');
if (strlen($notes) > 500) {
    $notes = substr($notes, 0, 500);
}

try {
    $pdo->beginTransaction();
    $ref = mbenefits_generate_claim_reference($pdo);
    $ins = $pdo->prepare('
        INSERT INTO benefit_claims (user_id, benefit_offer_id, claim_reference, status, user_notes)
        VALUES (:u, :o, :r, "pending", :n)
    ');
    $ins->execute(['u' => $uid, 'o' => $bid, 'r' => $ref, 'n' => $notes !== '' ? $notes : null]);
    $cid = (int) $pdo->lastInsertId();
    mbenefits_log_claim_change($pdo, $cid, null, $uid, null, 'pending', 'Member submitted claim.');
    $pdo->commit();
    flash_set('success', 'Claim submitted. Reference: ' . $ref . '. You can track status under My Benefits.');
    redirect('user/my_benefits.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', 'Could not submit claim. Please try again.');
    redirect('user/benefit_detail.php?id=' . $bid);
}
