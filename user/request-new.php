<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/account_parts.php';
$b = require_borrower($pdo);       
$equipmentId = (int)($_REQUEST['equipment'] ?? 0);
$rows = $equipmentId > 0 ? fetch_equipment($pdo, ['id' => $equipmentId]) : [];
if (!$rows) public_not_found('equipment item');
$e = $rows[0];
$today = date('Y-m-d');
$errors = [];
$old = ['start_date' => $today, 'due_date' => date('Y-m-d', strtotime('+14 days')), 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['start_date'] = (string)($_POST['start_date'] ?? '');
    $old['due_date'] = (string)($_POST['due_date'] ?? '');
    $old['notes'] = trim((string)($_POST['notes'] ?? ''));

    if ((int)$e['available_items'] < 1) $errors['form'] = 'Sorry, this item is not available at the moment, so it cannot be requested.';
    if (!valid_date($old['start_date']) || $old['start_date'] < $today) $errors['start_date'] = 'Choose a collection date that is today or later.';
    if (!valid_date($old['due_date'])) $errors['due_date'] = 'Choose a valid return date.';
    elseif (!isset($errors['start_date']) && $old['due_date'] <= $old['start_date']) $errors['due_date'] = 'The return date must be after the collection date.';
    elseif (!isset($errors['start_date']) && (strtotime($old['due_date']) - strtotime($old['start_date'])) / 86400 > MAX_LOAN_DAYS) $errors['due_date'] = 'Loans can be requested for up to ' . MAX_LOAN_DAYS . ' days.';
    if (mb_strlen($old['notes']) > 500) $errors['notes'] = 'Please keep your note to 500 characters or fewer.';

    if (!$errors) {
        $s = $pdo->prepare("SELECT COUNT(*) total, SUM(equipment_id=?) same FROM loan_requests WHERE borrower_id=? AND status IN ('Pending','Approved')");
        $s->execute([$equipmentId, $b['id']]);
        $open = $s->fetch();
        if ((int)$open['same'] > 0) $errors['form'] = 'You already have an open request for this item. You can see it under My requests.';
        elseif ((int)$open['total'] >= MAX_OPEN_REQUESTS) $errors['form'] = 'You can have up to ' . MAX_OPEN_REQUESTS . ' open requests at a time. Please wait for one to be completed or cancel one first.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare('INSERT INTO loan_requests(borrower_id,category_id,equipment_id,quantity,start_date,due_date,notes,created_by) VALUES(?,?,?,?,?,?,?,NULL)');
            $s->execute([$b['id'], $e['category_id'], $equipmentId, 1, $old['start_date'], $old['due_date'], $old['notes'] ?: null]);
            $rid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO request_items(request_id,category_id) VALUES(?,?)')->execute([$rid, $e['category_id']]);
            log_activity($pdo, 'Borrower submitted loan request', 'request', $rid);
            $pdo->commit();
            flash('Your request ' . request_ref($rid) . ' has been sent. Our team will review it and update its status here.');
            redirect(url('user/request-view.php') . '?id=' . $rid);
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['form'] = 'Sorry, your request could not be saved. Please try again.';
        }
    }
}
$err = fn(string $k) => isset($errors[$k]) ? '<div class="field-error" data-server="1" id="' . $k . '-error">' . h($errors[$k]) . '</div>' : '';
$cls = fn(string $k) => isset($errors[$k]) ? ' has-error' : '';
account_header('Request equipment', 'requests', $b);
$avail = (int)$e['available_items'] > 0;
?>
<div class="dash-grid">
  <section class="panel">
    <?php if (!empty($errors['form'])): ?><div class="alert alert-error" role="alert"><?= h($errors['form']) ?></div><?php endif; ?>
    <?php if (!$avail): ?>
      <div class="alert alert-error" role="alert">This item is currently unavailable, so it cannot be requested.</div>
      <a class="btn btn-outline" href="<?= h(url('pages/equipment.php')) ?>?category=<?= (int)$e['category_id'] ?>">See other <?= h(strtolower($e['category'])) ?></a>
    <?php else: ?>
    <form method="post" action="<?= h(url('user/request-new.php')) ?>" data-validate>
      <?= csrf_field() ?><input type="hidden" name="equipment" value="<?= $equipmentId ?>">
      <div class="form-grid">
        <div class="field<?= $cls('start_date') ?>"><label for="start_date">Collection date</label><input type="date" id="start_date" name="start_date" required min="<?= h($today) ?>" value="<?= h($old['start_date']) ?>"><?= $err('start_date') ?></div>
        <div class="field<?= $cls('due_date') ?>"><label for="due_date">Return date</label><input type="date" id="due_date" name="due_date" required min="<?= h(date('Y-m-d', strtotime($old['start_date'] ?: $today) + 86400)) ?>" value="<?= h($old['due_date']) ?>"><div class="hint">Up to <?= MAX_LOAN_DAYS ?> days.</div><?= $err('due_date') ?></div>
        <div class="field full<?= $cls('notes') ?>"><label for="notes">Note for our team <span class="optional">(optional)</span></label><textarea id="notes" name="notes" maxlength="500" style="min-height:100px"><?= h($old['notes']) ?></textarea><div class="hint">For example, preferred collection times. Please do not include medical details.</div><?= $err('notes') ?></div>
      </div>
      <div class="btn-row"><button class="btn btn-primary btn-lg" type="submit">Send request</button><a class="btn btn-ghost btn-lg" href="<?= h(url('pages/equipment-details.php')) ?>?id=<?= $equipmentId ?>">Cancel</a></div>
    </form>
    <?php endif; ?>
  </section>
  <aside class="panel panel-soft">
    <img src="<?= h(equipment_image($e['name'], $e['category'])) ?>" alt="" style="border-radius:12px;margin-bottom:12px">
    <h2><?= h($e['name']) ?></h2>
    <p><?= availability_badge($e) ?></p>
    <p><?= h($e['category']) ?></p>
    <p class="note">Your request goes to our staff for approval. It does not reserve the item until it is approved.</p>
  </aside>
</div>
<?php account_footer();
