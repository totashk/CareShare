<?php
require_once __DIR__ . '/../includes/site.php';
$b = require_borrower($pdo);
$s = $pdo->prepare('SELECT o.*,c.name category FROM equipment_offers o JOIN equipment_categories c ON c.id=o.category_id WHERE o.borrower_id=? ORDER BY o.created_at DESC');
$s->execute([(int)$b['id']]);
$offers = $s->fetchAll();
account_header('My equipment offers', 'offers', $b);
?>
<div class="panel-head"><h2>Your offers</h2><a class="btn btn-primary btn-sm" href="<?= h(url('user/offer-new.php')) ?>">Offer equipment</a></div>
<?php if (!$offers): ?><section class="panel"><p>You have not offered equipment yet.</p><a href="<?= h(url('user/offer-new.php')) ?>">Submit an equipment offer</a></section>
<?php else: ?><div class="equipment-grid"><?php foreach ($offers as $o): ?><article class="equipment-card"><div class="equipment-img"><img src="<?= h(asset($o['photo_path'])) ?>" alt="Photo submitted for <?= h($o['name']) ?>"></div><div class="equipment-body"><span class="equipment-cat"><?= h($o['category']) ?></span><h3><?= h($o['name']) ?></h3><?= status_badge($o['status']) ?><p><?= h($o['description']) ?></p><?php if ($o['staff_note']): ?><p><strong>Staff note:</strong> <?= h($o['staff_note']) ?></p><?php endif; ?><small>Submitted <?= h(fmt_date($o['created_at'])) ?></small></div></article><?php endforeach; ?></div><?php endif; ?>
<?php account_footer();
