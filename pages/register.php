<?php
require_once __DIR__ . '/../includes/site.php';
if (current_borrower($pdo)) redirect(url('user/dashboard.php'));
$next = safe_next($_REQUEST['next'] ?? null);
$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['phone'] = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');

    if (mb_strlen($old['full_name']) < 2 || mb_strlen($old['full_name']) > 120) $errors['full_name'] = 'Enter your full name (2 to 120 characters).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) $errors['email'] = 'Enter a valid email address.';
    if (!preg_match('/^[+0-9() .-]{7,25}$/', $old['phone'])) $errors['phone'] = 'Enter a phone number using digits, spaces, + ( ) or -.';
    if (strlen($password) < 8) $errors['password'] = 'Your password must be at least 8 characters.';
    elseif (strlen($password) > 72) $errors['password'] = 'Your password must be 72 characters or fewer.';
    elseif (strtolower($password) === $old['email']) $errors['password'] = 'Your password must not be the same as your email address.';
    if ($password !== $confirm) $errors['password_confirm'] = 'The two passwords do not match.';
    if (empty($_POST['consent'])) $errors['consent'] = 'Please confirm that we may store your contact details to run the lending service.';

    if (!$errors) {
        $dupMsg = 'We could not create an account with that email address. If you already have an account, please log in; otherwise contact us for help.';
        $s = $pdo->prepare('SELECT COUNT(*) FROM borrowers WHERE email=?');
        $s->execute([$old['email']]);
        if ($s->fetchColumn()) {
            $errors['email'] = $dupMsg;
        } else {
            try {
                $s = $pdo->prepare('INSERT INTO borrowers(full_name,phone,email,password_hash,last_login_at) VALUES(?,?,?,?,NOW())');
                $s->execute([$old['full_name'], $old['phone'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]);
                $newId = (int)$pdo->lastInsertId();
                log_activity($pdo, 'Borrower registered online', 'borrower', $newId);
                session_regenerate_id(true);
                $_SESSION['borrower'] = ['id' => $newId];
                flash('Welcome to CareShare, ' . explode(' ', $old['full_name'])[0] . '! Your account is ready.');
                redirect(url($next));
            } catch (PDOException $e) {
            
                $errors['email'] = $e->getCode() === '23000' ? $dupMsg : 'Sorry, we could not create your account just now. Please try again.';
            }
        }
    }
}
$err = fn(string $k) => isset($errors[$k]) ? '<div class="field-error" data-server="1" id="' . $k . '-error">' . h($errors[$k]) . '</div>' : '';
$cls = fn(string $k) => isset($errors[$k]) ? ' has-error' : '';
public_header('Create an account', '');
?>
<section class="section section-alt"><div class="container"><div class="auth-wrap"><div class="auth-card">
  <h1>Create your account</h1>
  <p class="muted" style="color:var(--muted)">You need an account to request equipment. We only ask for the details needed to run the lending service.</p>
  <?php if ($errors): ?><div class="alert alert-error" role="alert">Please fix the highlighted fields and try again.</div><?php endif; ?>
  <form method="post" action="<?= h(url('pages/register.php')) ?>" data-validate>
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= h($next) ?>">
    <div class="field<?= $cls('full_name') ?>"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" required minlength="2" maxlength="120" autocomplete="name" value="<?= h($old['full_name']) ?>"><?= $err('full_name') ?></div>
    <div class="field<?= $cls('email') ?>"><label for="email">Email address</label><input type="email" id="email" name="email" required maxlength="190" autocomplete="email" value="<?= h($old['email']) ?>"><?= $err('email') ?></div>
    <div class="field<?= $cls('phone') ?>"><label for="phone">Phone number</label><input type="tel" id="phone" name="phone" required pattern="[+0-9() .\-]{7,25}" data-pattern-message="Enter a phone number using digits, spaces, + ( ) or -." autocomplete="tel" value="<?= h($old['phone']) ?>"><?= $err('phone') ?></div>
    <div class="field<?= $cls('password') ?>"><label for="password">Password</label><input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><div class="hint">At least 8 characters.</div><?= $err('password') ?></div>
    <div class="field<?= $cls('password_confirm') ?>"><label for="password_confirm">Confirm password</label><input type="password" id="password_confirm" name="password_confirm" required data-match="password" autocomplete="new-password"><?= $err('password_confirm') ?></div>
    <div class="field<?= $cls('consent') ?>"><div class="check"><input type="checkbox" id="consent" name="consent" value="1" required <?= !empty($_POST['consent']) ? 'checked' : '' ?>><label for="consent">I agree that CareShare may store my name, email and phone number to manage my equipment requests and loans. I will not enter medical information.</label></div><?= $err('consent') ?></div>
    <button class="btn btn-primary btn-block btn-lg" type="submit">Create account</button>
  </form>
  <p class="auth-alt">Already registered? <a href="<?= h(url('pages/login.php')) ?>?next=<?= urlencode($next) ?>">Log in</a></p>
</div></div></div></section>
<?php public_footer();
