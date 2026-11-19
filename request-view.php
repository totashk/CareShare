<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/account_parts.php';
$b = require_borrower($pdo);
$id = (int)($_GET['id'] ?? 0);

$s = $pdo->prepare(MY_REQUESTS_SQL . ' WHERE r.id=? AND r.borrower_id=?');
$s->execute([$id, $b['id']]);
$r = $s->fetch();
if (!$r) public_not_found('request');

$s = $pdo->prepare("SELECT l.id,l.request_id,l.issued_at,l.due_date,l.returned_at,i.asset_code,e.name equipment
    FROM loans l JOIN equipment_items i ON i.id=l.item_id JOIN equipment e ON e.id=i.equipment_id
    WHERE l.request_id=? AND l.borrower_id=? ORDER BY l.id");
$s->execute([$id, $b['id']]);
$loans = $s->fetchAll();
$today = date('Y-m-d');
[$cls, $label] = request_status($r);
$explain = [
    'Pending'   => 'Your request has been received and is waiting for a member of our team to review it. You can still cancel it.',
    'Approved'  => 'Good news: your request has been approved. Our team will contact you to arrange collection.',
    'Rejected'  => 'Sorry, this request could not be approved. Please contact us if you would like to know more or try a different item.',
    'Issued'    => 'The equipment has been issued to you. Please return it by the date shown below.',
    'Cancelled' => $r['cancelled_by_borrower'] ? 'You cancelled this request.' : 'This request was cancelled by our team.',
][$r['status']];
if ($label === 'Returned') $explain = 'This loan is complete and the equipment has been returned. Thank you!';
account_header('Request ' . request_ref($id), 'requests', $b);
?>
<p><a href="<?= h(url('user/requests.php')) ?>">← All my requests</a></p>
<div class="dash-grid">
  <div>
    <section class="panel">
      <div class="panel-head"><h2><?= h(request_item_name($r)) ?></h2><?= request_status_badge($r) ?></div>
      <p><?= h($explain) ?></p>
      <dl class="info-list wide">
        <dt>Equipment</dt><dd><?= h(request_item_name($r)) ?> (<?= h($r['category']) ?>)</dd>
        <dt>Quantity</dt><dd><?= (int)$r['quantity'] ?></dd>
        <dt>Collection wanted</dt><dd><?= h(fmt_date($r['start_date'])) ?></dd>
        <dt>Return by</dt><dd><?= h(fmt_date($r['due_date'])) ?></dd>
        <dt>Request sent</dt><dd><?= h(fmt_date($r['created_at'])) ?></dd>
        <?php if ($r['reviewed_at']): ?><dt>Last updated</dt><dd><?= h(fmt_date($r['reviewed_at'])) ?></dd><?php endif; ?>
        <dt>Your note</dt><dd><?= $r['notes'] ? nl2br(h($r['notes'])) : 'None' ?></dd>
      </dl>
      <?php if ($r['status'] === 'Pending') echo cancel_form($id, 'user/request-view.php?id=' . $id); ?>
    </section>
    <?php if ($loans): ?>
    <section class="panel"><div class="panel-head"><h2>Loan details</h2></div>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Item</th><th>Borrowed</th><th>Return by</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($loans as $l): $late = !$l['returned_at'] && $l['due_date'] < $today; ?>
        <tr><td data-label="Item"><?= h($l['equipment']) ?><small>Asset ID <?= h($l['asset_code']) ?></small></td>
            <td data-label="Borrowed"><?= h(fmt_date($l['issued_at'])) ?></td><td data-label="Return by"><?= h(fmt_date($l['due_date'])) ?></td>
            <td data-label="Status"><?= $l['returned_at'] ? '<span class="badge returned">Returned ' . h(fmt_date($l['returned_at'])) . '</span>' : ($late ? '<span class="badge overdue">Overdue</span>' : '<span class="badge issued">On loan</span>') ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    </section>
    <?php endif; ?>
  </div>
  <aside><section class="panel panel-soft"><h2>Questions?</h2><p>If anything about this request is unclear, send us a message and quote <strong><?= h(request_ref($id)) ?></strong>.</p><a class="btn btn-outline btn-block" href="<?= h(url('pages/contact.php')) ?>">Contact CareShare</a></section></aside>
</div>
<?php account_footer();
