<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/account_parts.php';
$b = require_borrower($pdo);
$errors = [];
$old = ['full_name' => $b['full_name'], 'phone' => $b['phone'], 'address' => (string)$b['address']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'details') {
        $old['full_name'] = trim((string)($_POST['full_name'] ?? ''));
        $old['phone'] = trim((string)($_POST['phone'] ?? ''));
        $old['address'] = trim((string)($_POST['address'] ?? ''));
        if (mb_strlen($old['full_name']) < 2 || mb_strlen($old['full_name']) > 120) $errors['full_name'] = 'Enter your full name (2 to 120 characters).';
        if (!preg_match('/^[+0-9() .-]{7,25}$/', $old['phone'])) $errors['phone'] = 'Enter a phone number using digits, spaces, + ( ) or -.';
        if (mb_strlen($old['address']) > 500) $errors['address'] = 'Please keep your address to 500 characters or fewer.';
        if (!$errors) {

            $pdo->prepare('UPDATE borrowers SET full_name=?,phone=?,address=? WHERE id=?')->execute([$old['full_name'], $old['phone'], $old['address'] ?: null, $b['id']]);
            log_activity($pdo, 'Borrower updated their details', 'borrower', (int)$b['id']);
            flash('Your details have been updated.');
            redirect(url('user/profile.php'));
        }
    } elseif ($action === 'password') {
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $conf = (string)($_POST['new_password_confirm'] ?? '');
        $s = $pdo->prepare('SELECT password_hash FROM borrowers WHERE id=?');
        $s->execute([$b['id']]);
        if (!password_verify($cur, (string)$s->fetchColumn())) $errors['current_password'] = 'Your current password is not correct.';
        if (strlen($new) < 8 || strlen($new) > 72) $errors['new_password'] = 'Your new password must be 8 to 72 characters.';
        if ($new !== $conf) $errors['new_password_confirm'] = 'The two new passwords do not match.';
        if (!$errors) {
            $pdo->prepare('UPDATE borrowers SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $b['id']]);
            session_regenerate_id(true);
            log_activity($pdo, 'Borrower changed their password', 'borrower', (int)$b['id']);
            flash('Your password has been changed.');
            redirect(url('user/profile.php'));
        }
    }
}
$err = fn(string $k) => isset($errors[$k]) ? '<div class="field-error" data-server="1" id="' . $k . '-error">' . h($errors[$k]) . '</div>' : '';
$cls = fn(string $k) => isset($errors[$k]) ? ' has-error' : '';
account_header('My details', 'profile', $b);
?>
<div class="grid grid-2" style="align-items:start">
  <section class="panel"><div class="panel-head"><h2>Contact details</h2></div>
    <form method="post" data-validate><?= csrf_field() ?><input type="hidden" name="action" value="details">
      <div class="field<?= $cls('full_name') ?>"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" required minlength="2" maxlength="120" value="<?= h($old['full_name']) ?>"><?= $err('full_name') ?></div>
      <div class="field"><label for="email">Email address</label><input type="email" id="email" value="<?= h($b['email']) ?>" disabled><div class="hint">Your email is your login. To change it, please contact us.</div></div>
      <div class="field<?= $cls('phone') ?>"><label for="phone">Phone number</label><input type="tel" id="phone" name="phone" required pattern="[+0-9() .\-]{7,25}" data-pattern-message="Enter a phone number using digits, spaces, + ( ) or -." value="<?= h($old['phone']) ?>"><?= $err('phone') ?></div>
      <div class="field<?= $cls('address') ?>"><label for="address">Address <span class="optional">(optional)</span></label><textarea id="address" name="address" maxlength="500" style="min-height:90px"><?= h($old['address']) ?></textarea><?= $err('address') ?></div>
      <button class="btn btn-primary">Save details</button>
    </form>
  </section>
  <section class="panel"><div class="panel-head"><h2>Change password</h2></div>
    <form method="post" data-validate><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="field<?= $cls('current_password') ?>"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" required autocomplete="current-password"><?= $err('current_password') ?></div>
      <div class="field<?= $cls('new_password') ?>"><label for="new_password">New password</label><input type="password" id="new_password" name="new_password" required minlength="8" maxlength="72" autocomplete="new-password"><div class="hint">At least 8 characters.</div><?= $err('new_password') ?></div>
      <div class="field<?= $cls('new_password_confirm') ?>"><label for="new_password_confirm">Confirm new password</label><input type="password" id="new_password_confirm" name="new_password_confirm" required data-match="new_password" autocomplete="new-password"><?= $err('new_password_confirm') ?></div>
      <button class="btn btn-primary">Change password</button>
    </form>
  </section>
</div>
<?php account_footer();
