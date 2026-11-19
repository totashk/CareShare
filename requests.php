<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/account_parts.php';
$b = require_borrower($pdo);
$status = $_GET['status'] ?? '';
$valid = ['Pending', 'Approved', 'Rejected', 'Issued', 'Cancelled'];
$sql = MY_REQUESTS_SQL . ' WHERE r.borrower_id=?';
$args = [$b['id']];
if (in_array($status, $valid, true)) { $sql .= ' AND r.status=?'; $args[] = $status; } else { $status = ''; }
$s = $pdo->prepare($sql . ' ORDER BY r.created_at DESC');
$s->execute($args);
account_header('My requests', 'requests', $b);
?>
<section class="panel">
  <form method="get" class="inline-filter"><label for="status">Show</label>
    <select id="status" name="status"><option value="">All requests</option><?php foreach ($valid as $v): ?><option <?= $status === $v ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select>
    <button class="btn btn-outline btn-sm">Filter</button></form>
  <?php render_requests_table($s->fetchAll()); ?>
</section>
<p><a class="btn btn-primary" href="<?= h(url('pages/equipment.php')) ?>">Browse equipment to request</a></p>
<?php account_footer();
