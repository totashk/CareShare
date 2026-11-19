<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/account_parts.php';
$b = require_borrower($pdo);
$today = date('Y-m-d');

$s = $pdo->prepare(MY_REQUESTS_SQL . " WHERE r.borrower_id=? AND r.status IN ('Pending','Approved') ORDER BY r.created_at DESC");
$s->execute([$b['id']]);
$current = $s->fetchAll();
$active = fetch_my_loans($pdo, (int)$b['id'], 'AND l.returned_at IS NULL ORDER BY l.due_date');
$history = fetch_my_loans($pdo, (int)$b['id'], 'AND l.returned_at IS NOT NULL ORDER BY l.returned_at DESC LIMIT 5');
$overdue = count(array_filter($active, fn($l) => $l['due_date'] < $today));

account_header('Welcome, ' . explode(' ', $b['full_name'])[0], 'dashboard', $b);
?>
<div class="summary-grid">
  <div class="summary"><strong><?= count($current) ?></strong><span>Open request<?= count($current) === 1 ? '' : 's' ?></span></div>
  <div class="summary"><strong><?= count($active) ?></strong><span>Item<?= count($active) === 1 ? '' : 's' ?> on loan</span></div>
  <div class="summary <?= $overdue ? 'summary-alert' : '' ?>"><strong><?= $overdue ?></strong><span>Overdue</span></div>
</div>
<?php if ($overdue): ?><div class="alert alert-error" role="alert">You have equipment that is past its return date. Please contact CareShare to arrange its return.</div><?php endif; ?>

<div class="dash-grid">
  <div>
    <section class="panel">
      <div class="panel-head"><h2>Current requests</h2><a href="<?= h(url('user/requests.php')) ?>">All requests →</a></div>
      <?php if ($current): render_requests_table($current); else: ?>
        <p class="empty-note">You have no open requests. <a href="<?= h(url('pages/equipment.php')) ?>">Browse equipment</a> to make one.</p>
      <?php endif; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2>Active loans</h2><a href="<?= h(url('user/loans.php')) ?>">All loans →</a></div>
      <?php render_loans_table($active, $today); ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2>Recent borrowing history</h2></div>
      <?php render_loans_table($history, $today, true); ?>
    </section>
  </div>
  <aside>
    <section class="panel">
      <div class="panel-head"><h2>Your details</h2><a href="<?= h(url('user/profile.php')) ?>">Edit</a></div>
      <dl class="info-list">
        <dt>Name</dt><dd><?= h($b['full_name']) ?></dd>
        <dt>Email</dt><dd><?= h($b['email']) ?></dd>
        <dt>Phone</dt><dd><?= h($b['phone']) ?></dd>
        <dt>Member since</dt><dd><?= h(fmt_date($b['registered_at'])) ?></dd>
      </dl>
    </section>
    <section class="panel panel-soft">
      <h2>Need something?</h2>
      <p>See what is available today.</p>
      <a class="btn btn-primary btn-block" href="<?= h(url('pages/equipment.php')) ?>">Browse equipment</a>
      <a class="btn btn-outline btn-block" href="<?= h(url('user/offer-new.php')) ?>" style="margin-top:8px">Offer equipment to CareShare</a>
      <form method="post" action="<?= h(url('pages/logout.php')) ?>" style="margin-top:12px"><?= csrf_field() ?><button class="btn btn-outline btn-block">Log out</button></form>
    </section>
  </aside>
</div>
<?php account_footer();
