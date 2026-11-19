<?php
declare(strict_types=1);


function cancel_form(int $requestId, string $back = 'user/requests.php'): string {
    return '<form method="post" action="' . h(url('user/request-cancel.php')) . '" class="inline-form">' . csrf_field()
         . '<input type="hidden" name="id" value="' . $requestId . '"><input type="hidden" name="back" value="' . h($back) . '">'
         . '<button class="btn btn-danger btn-sm" data-confirm="Cancel this request?">Cancel request</button></form>';
}

function render_requests_table(array $rows, bool $withCancel = true): void {
    if (!$rows) { echo '<p class="empty-note">No requests to show.</p>'; return; } ?>
<div class="table-wrap"><table class="data-table">
  <thead><tr><th>Request</th><th>Equipment</th><th>Dates wanted</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Request"><a href="<?= h(url('user/request-view.php')) ?>?id=<?= (int)$r['id'] ?>"><?= h(request_ref((int)$r['id'])) ?></a><small>Sent <?= h(fmt_date($r['created_at'])) ?></small></td>
      <td data-label="Equipment"><?= h(request_item_name($r)) ?><small><?= h($r['category']) ?></small></td>
      <td data-label="Dates wanted"><?= h(fmt_date($r['start_date'])) ?> to <?= h(fmt_date($r['due_date'])) ?></td>
      <td data-label="Status"><?= request_status_badge($r) ?></td>
      <td class="actions"><a class="btn btn-outline btn-sm" href="<?= h(url('user/request-view.php')) ?>?id=<?= (int)$r['id'] ?>">View</a>
        <?php if ($withCancel && $r['status'] === 'Pending') echo cancel_form((int)$r['id']); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php }


function render_loans_table(array $loans, string $today, bool $history = false): void {
    if (!$loans) { echo '<p class="empty-note">' . ($history ? 'No completed loans yet.' : 'You have no equipment on loan right now.') . '</p>'; return; } ?>
<div class="table-wrap"><table class="data-table">
  <thead><tr><th>Equipment</th><th>Borrowed</th><th><?= $history ? 'Returned' : 'Return by' ?></th><?php if (!$history): ?><th>Status</th><?php endif; ?><th><span class="sr-only">Request</span></th></tr></thead>
  <tbody>
  <?php foreach ($loans as $l):
      $late = !$history && $l['due_date'] < $today;
      $days = $late ? (int)((strtotime($today) - strtotime($l['due_date'])) / 86400) : 0; ?>
    <tr class="<?= $late ? 'row-overdue' : '' ?>">
      <td data-label="Equipment"><?= h($l['equipment']) ?><small>Asset ID <?= h($l['asset_code']) ?></small></td>
      <td data-label="Borrowed"><?= h(fmt_date($l['issued_at'])) ?></td>
      <td data-label="<?= $history ? 'Returned' : 'Return by' ?>"><?= h(fmt_date($history ? $l['returned_at'] : $l['due_date'])) ?></td>
      <?php if (!$history): ?><td data-label="Status"><?= $late ? '<span class="badge overdue">Overdue by ' . $days . ' day' . ($days === 1 ? '' : 's') . '</span>' : '<span class="badge issued">On loan</span>' ?></td><?php endif; ?>
      <td class="actions"><?php if ($l['request_id']): ?><a class="btn btn-outline btn-sm" href="<?= h(url('user/request-view.php')) ?>?id=<?= (int)$l['request_id'] ?>">Request</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php }


function fetch_my_loans(PDO $pdo, int $borrowerId, string $where): array {
    $s = $pdo->prepare("SELECT l.id,l.request_id,l.issued_at,l.due_date,l.returned_at,i.asset_code,e.name equipment
        FROM loans l JOIN equipment_items i ON i.id=l.item_id JOIN equipment e ON e.id=i.equipment_id
        WHERE l.borrower_id=? $where");
    $s->execute([$borrowerId]);
    return $s->fetchAll();
}
