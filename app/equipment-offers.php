<?php
$id = (int)($_GET['id'] ?? 0);
$error = '';
verify_csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $note = trim((string)($_POST['staff_note'] ?? ''));
    if (!in_array($action, ['approve', 'reject'], true) || strlen($note) > 4000) {
        $error = 'Choose a valid review action and keep the staff note under 4,000 characters.';
    } else {
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("SELECT * FROM equipment_offers WHERE id=? FOR UPDATE");
            $s->execute([$id]); $offer = $s->fetch();
            if (!$offer || $offer['status'] !== 'Pending') throw new RuntimeException('This offer has already been reviewed or no longer exists.');
            if ($action === 'approve') {
                $s = $pdo->prepare('SELECT id FROM equipment WHERE category_id=? AND LOWER(name)=LOWER(?) ORDER BY id LIMIT 1');
                $s->execute([$offer['category_id'], $offer['name']]); $equipmentId = $s->fetchColumn();
                if (!$equipmentId) {
                    $s = $pdo->prepare('INSERT INTO equipment(category_id,name,description) VALUES(?,?,?)');
                    $s->execute([$offer['category_id'], $offer['name'], $offer['description']]); $equipmentId = (int)$pdo->lastInsertId();
                }
                $asset = 'CS-OFFER-' . str_pad((string)$id, 8, '0', STR_PAD_LEFT);
                $s = $pdo->prepare("INSERT INTO equipment_items(equipment_id,asset_code,condition_label,status,notes) VALUES(?,?,'Needs inspection','Awaiting Inspection','Donated equipment offer; inspection required before lending.')");
                $s->execute([$equipmentId, $asset]); $itemId = (int)$pdo->lastInsertId();
                $s = $pdo->prepare("UPDATE equipment_offers SET status='Approved',staff_note=?,reviewed_by=?,reviewed_at=NOW(),equipment_id=?,equipment_item_id=? WHERE id=? AND status='Pending'");
                $s->execute([$note ?: null, user()['id'], $equipmentId, $itemId, $id]);
                log_activity($pdo, 'Approved borrower equipment offer', 'equipment_offer', $id);
            } else {
                $s = $pdo->prepare("UPDATE equipment_offers SET status='Rejected',staff_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='Pending'");
                $s->execute([$note ?: null, user()['id'], $id]);
                log_activity($pdo, 'Rejected borrower equipment offer', 'equipment_offer', $id);
            }
            $pdo->commit(); flash($action === 'approve' ? 'Offer approved. The unit is awaiting inspection.' : 'Offer rejected.');
            redirect('?page=equipment-offers' . ($action === 'approve' ? '&id=' . $id : ''));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'The offer could not be reviewed. Check the database and try again.';
        }
    }
}
$status = $_GET['status'] ?? 'Pending';
if (!in_array($status, ['Pending','Approved','Rejected','All'], true)) $status = 'Pending';
$sql = 'SELECT o.*,b.full_name borrower,c.name category,u.full_name reviewer FROM equipment_offers o JOIN borrowers b ON b.id=o.borrower_id JOIN equipment_categories c ON c.id=o.category_id LEFT JOIN users u ON u.id=o.reviewed_by';
$offers = $pdo->query($sql . ($status === 'All' ? '' : ' WHERE o.status=' . $pdo->quote($status)) . ' ORDER BY o.created_at DESC')->fetchAll();
$selected = null;
if ($id) { foreach ($offers as $row) if ((int)$row['id'] === $id) $selected = $row; if (!$selected) { $s=$pdo->prepare('SELECT o.*,b.full_name borrower,c.name category,u.full_name reviewer FROM equipment_offers o JOIN borrowers b ON b.id=o.borrower_id JOIN equipment_categories c ON c.id=o.category_id LEFT JOIN users u ON u.id=o.reviewed_by WHERE o.id=?'); $s->execute([$id]); $selected=$s->fetch() ?: null; } }
layout_start('Equipment offers','equipment-offers');
?>
<div class="page-heading"><div><p class="eyebrow">DONATIONS</p><h1>Equipment offers</h1><p class="muted">Review borrower-submitted equipment. Approved units remain unavailable until inspected.</p></div></div>
<?php if ($error): ?><div class="alert error" role="alert"><?= h($error) ?></div><?php endif; ?>
<section class="panel"><form method="get" class="filter-bar"><input type="hidden" name="page" value="equipment-offers"><label>Status <select name="status"><option <?= $status==='Pending'?'selected':'' ?>>Pending</option><option <?= $status==='Approved'?'selected':'' ?>>Approved</option><option <?= $status==='Rejected'?'selected':'' ?>>Rejected</option><option <?= $status==='All'?'selected':'' ?>>All</option></select></label><button class="button secondary">Filter</button></form>
<div class="table-scroll"><table><thead><tr><th>Offer</th><th>Borrower</th><th>Submitted</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($offers as $o): ?><tr><td><strong><?= h($o['name']) ?></strong><small class="cell-sub"><?= h($o['category']) ?></small></td><td><?= h($o['borrower']) ?></td><td><?= h(date('j M Y',strtotime($o['created_at']))) ?></td><td><span class="badge <?= strtolower($o['status']) ?>"><?= h($o['status']) ?></span></td><td><a class="text-link" href="?page=equipment-offers&id=<?= (int)$o['id'] ?>&status=<?= h($status) ?>">Review →</a></td></tr><?php endforeach; ?></tbody></table><?php if(!$offers): ?><div class="table-empty">No offers in this status.</div><?php endif; ?></div></section>
<?php if ($selected): ?><section class="panel" style="margin-top:20px"><div class="page-heading"><div><h2><?= h($selected['name']) ?></h2><p><?= h($selected['category']) ?> · from <?= h($selected['borrower']) ?></p></div></div><img src="<?= h(asset($selected['photo_path'])) ?>" alt="Borrower-submitted photo of <?= h($selected['name']) ?>" style="max-width:100%;max-height:420px;object-fit:contain"><p><?= nl2br(h($selected['description'] ?: 'No description provided.')) ?></p><?php if($selected['staff_note']): ?><p><strong>Staff note:</strong> <?= nl2br(h($selected['staff_note'])) ?></p><?php endif; ?><?php if($selected['status']==='Pending'): ?><form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="id" value="<?= (int)$selected['id'] ?>"><label for="staff-note">Internal staff note</label><textarea id="staff-note" name="staff_note" maxlength="4000" rows="3"></textarea><div class="button-row"><button name="action" value="approve" class="button primary">Approve and add awaiting inspection</button><button name="action" value="reject" class="button danger">Reject offer</button></div></form><?php else: ?><p>Reviewed by <?= h($selected['reviewer'] ?? 'staff') ?> on <?= h(date('j M Y',strtotime($selected['reviewed_at']))) ?>.</p><?php endif; ?></section><?php endif; ?>
<?php layout_end();
