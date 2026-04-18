<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$st = $pdo->prepare('SELECT preferred_language FROM users WHERE id = :id LIMIT 1');
$st->execute(['id' => (int) auth_user()['user_id']]);
$langRow = $st->fetch();
$lang = (string) ($langRow['preferred_language'] ?? 'en');
$langLabel = $lang === 'sw' ? 'Kiswahili' : 'English';

$mgrid_page_title = 'Settings — Malkia Grid';
require __DIR__ . '/includes/shell_open.php';
?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <h1 class="h4 mgrid-dash-page-title mb-2">Settings</h1>
    <p class="text-muted small mb-4">Language preferences and security options will expand in upcoming releases.</p>
    <div class="border rounded p-3 bg-light">
      <p class="small mb-0"><strong>Preferred language on file:</strong> <?= e($langLabel) ?></p>
      <p class="small text-muted mt-2 mb-0">Interface copy is English-first for this MVP; Kiswahili strings can be layered via your preferred_language field as content grows.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
