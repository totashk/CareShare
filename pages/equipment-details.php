<?php
require_once __DIR__ . '/../includes/site.php';
$id = (int)($_GET['id'] ?? 0);
$rows = $id > 0 ? fetch_equipment($pdo, ['id' => $id]) : [];
if (!$rows) public_not_found('equipment item');
$e = $rows[0];
$avail = (int)$e['available_items'] > 0;
$borrower = current_borrower($pdo);


$open = null;
if ($borrower) {
    $s = $pdo->prepare("SELECT id,status FROM loan_requests WHERE borrower_id=? AND equipment_id=? AND status IN ('Pending','Approved') ORDER BY id DESC LIMIT 1");
    $s->execute([$borrower['id'], $id]);
    $open = $s->fetch() ?: null;
}
$requestUrl = 'user/request-new.php?equipment=' . $id;
public_header($e['name'], 'equipment');
?>
<section class="page-banner"><div class="container">
  <p class="breadcrumb"><a href="<?= h(url('pages/equipment.php')) ?>">Browse equipment</a> › <a href="<?= h(url('pages/equipment.php')) ?>?category=<?= (int)$e['category_id'] ?>"><?= h($e['category']) ?></a> › <?= h($e['name']) ?></p>
  <h1><?= h($e['name']) ?></h1>
</div></section>
<section class="section">
  <div class="container detail-layout">
    <div class="detail-img">
      <img src="<?= h(equipment_image($e['name'], $e['category'])) ?>" alt="Illustration of <?= h(strtolower($e['name'])) ?>">
      <div class="img-caption">Illustration only. The item you receive may look slightly different.</div>
    </div>
    <div>
      <p class="equipment-cat"><?= h($e['category']) ?></p>
      <p><?= availability_badge($e) ?></p>
      <p class="lead"><?= nl2br(h($e['description'] ?: 'No description has been added for this item yet.')) ?></p>
      <ul class="detail-meta">
        <li><span>Category</span><span><?= h($e['category']) ?></span></li>
        <li><span>Available now</span><span><?= (int)$e['available_items'] ?> of <?= (int)$e['total_items'] ?></span></li>
        <?php if (!$avail && $e['next_return']): ?><li><span>Next expected back</span><span><?= h(fmt_date($e['next_return'])) ?></span></li><?php endif; ?>
        <li><span>Loan length</span><span>Up to <?= MAX_LOAN_DAYS ?> days</span></li>
      </ul>

      <div class="request-box">
        <h2 style="font-size:1.25rem">How to request this item</h2>
        <p>Log in, choose the dates you need it, and send your request. Our team will review it and let you know in your account. A request is not a guarantee: it depends on staff approval and availability.</p>
        <?php if (!$avail): ?>
          <p><strong>This item is currently unavailable, so it cannot be requested.</strong> Please check back later or browse similar equipment.</p>
          <a class="btn btn-outline" href="<?= h(url('pages/equipment.php')) ?>?category=<?= (int)$e['category_id'] ?>">See other <?= h(strtolower($e['category'])) ?></a>
        <?php elseif (!$borrower): ?>
          <div class="btn-row">
            <a class="btn btn-primary" href="<?= h(url('pages/login.php')) ?>?next=<?= urlencode($requestUrl) ?>">Log in to request</a>
            <a class="btn btn-outline" href="<?= h(url('pages/register.php')) ?>?next=<?= urlencode($requestUrl) ?>">Create an account</a>
          </div>
        <?php elseif ($open): ?>
          <div class="alert alert-info">You already have an open request for this item (<?= h($open['status']) ?>).</div>
          <a class="btn btn-primary" href="<?= h(url('user/request-view.php')) ?>?id=<?= (int)$open['id'] ?>">View my request</a>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="<?= h(url($requestUrl)) ?>">Request this equipment</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php public_footer();
