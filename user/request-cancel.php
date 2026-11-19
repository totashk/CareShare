<?php

require_once __DIR__ . '/../includes/site.php';
$borrower = require_borrower($pdo);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('user/requests.php'));
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$back = safe_next($_POST['back'] ?? null);

$s = $pdo->prepare("UPDATE loan_requests SET status='Cancelled',cancelled_by_borrower=1,reviewed_at=NOW() WHERE id=? AND borrower_id=? AND status='Pending'");
$s->execute([$id, $borrower['id']]);
if ($s->rowCount() === 1) {
    log_activity($pdo, 'Borrower cancelled loan request', 'request', $id);
    flash('Your request ' . request_ref($id) . ' has been cancelled.');
} else {
    flash('That request could not be cancelled. Only your own pending requests can be cancelled.', 'error');
}
redirect(url($back));
