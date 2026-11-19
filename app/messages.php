<?php

verify_csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($mid && in_array($action, ['handled', 'reopen'], true)) {
        if ($action === 'handled') {
            $s = $pdo->prepare("UPDATE contact_messages SET status='Handled',handled_by=?,handled_at=NOW() WHERE id=?");
            $s->execute([user()['id'], $mid]);
        } else {
            $s = $pdo->prepare("UPDATE contact_messages SET status='New',handled_by=NULL,handled_at=NULL WHERE id=?");
            $s->execute([$mid]);
        }
        log_activity($pdo, $action === 'handled' ? 'Marked contact message handled' : 'Reopened contact message', 'contact_message', $mid);
        flash('Message updated.');
    }
    redirect('?page=messages' . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
}
$status = $_GET['status'] ?? 'New';
if (!in_array($status, ['New', 'Handled', 'All'], true)) $status = 'New';
$sql = 'SELECT m.*,u.full_name handler FROM contact_messages m LEFT JOIN users u ON u.id=m.handled_by' . ($status === 'All' ? '' : ' WHERE m.status=?') . ' ORDER BY m.created_at DESC LIMIT 200';
$s = $pdo->prepare($sql);
$s->execute($status === 'All' ? [] : [$status]);
$rows = $s->fetchAll();
layout_start('Contact messages', 'messages'); ?>
<div class="page-heading"><div><p class="eyebrow">PUBLIC WEBSITE</p><h1>Contact messages</h1><p class="muted">Messages sent through the Contact page. Reply to the sender by email, then mark the message handled.</p></div></div>
<section class="panel">
<form class="filter-bar" method="get"><input type="hidden" name="page" value="messages"><select name="status"><?php foreach (['New', 'Handled', 'All'] as $v): ?><option <?= $status === $v ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select><button class="button secondary">Filter</button></form>
<?php if (!$rows): ?><div class="table-empty">No <?= h(strtolower($status === 'All' ? '' : $status)) ?> messages.</div><?php endif; ?>
<?php foreach ($rows as $m): ?>
<article class="message-card">
  <header><div><strong><?= h($m['subject']) ?></strong><small class="cell-sub"><?= h($m['name']) ?> · <a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a> · <?= date('j M Y, g:i a', strtotime($m['created_at'])) ?></small></div><span class="badge <?= $m['status'] === 'New' ? 'pending' : 'active' ?>"><?= h($m['status']) ?></span></header>
  <p><?= nl2br(h($m['message'])) ?></p>
  <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
  <?php if ($m['status'] === 'New'): ?><input type="hidden" name="action" value="handled"><button class="button small secondary">Mark as handled</button>
  <?php else: ?><input type="hidden" name="action" value="reopen"><small class="muted">Handled by <?= h($m['handler'] ?? 'staff') ?></small> <button class="button small ghost">Reopen</button><?php endif; ?></form>
</article>
<?php endforeach; ?>
</section>
<?php layout_end();
