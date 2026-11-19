<?php
require_once __DIR__ . '/../includes/site.php';
if (current_borrower($pdo)) redirect(url(safe_next($_GET['next'] ?? null)));
$next = safe_next($_REQUEST['next'] ?? null);
$error = '';
$email = '';
$locked = ($_SESSION['login_lock_until'] ?? 0) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if ($locked) {
        $error = 'Too many unsuccessful attempts. Please wait a minute and try again.';
    } else {
        $s = $pdo->prepare('SELECT id,password_hash,status FROM borrowers WHERE email=?');
        $s->execute([$email]);
        $b = $s->fetch();
        // Always run password_verify (against a dummy hash if needed) so timing does not reveal which emails exist.
        $hash = $b['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $ok = password_verify($password, $hash) && $b && $b['password_hash'];
        if ($ok && $b['status'] === 'Active') {
            unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
            session_regenerate_id(true);
            $_SESSION['borrower'] = ['id' => (int)$b['id']];
            $pdo->prepare('UPDATE borrowers SET last_login_at=NOW() WHERE id=?')->execute([$b['id']]);
            redirect(url($next));
        } elseif ($ok) {
            $error = 'This account is not currently active. Please contact CareShare for help.';
        } else {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= 5) { $_SESSION['login_lock_until'] = time() + 60; $_SESSION['login_fails'] = 0; }
            $error = 'That email address and password were not recognised.';
        }
    }
}
public_header('Log in', '');
?>
<section class="section section-alt"><div class="container"><div class="auth-wrap"><div class="auth-card">
  <h1>Log in</h1>
  <p style="color:var(--muted)">Log in to request equipment and follow your requests.</p>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
  <form method="post" action="<?= h(url('pages/login.php')) ?>" data-validate>
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= h($next) ?>">
    <div class="field"><label for="email">Email address</label><input type="email" id="email" name="email" required autocomplete="username" value="<?= h($email) ?>"></div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-block btn-lg" type="submit">Log in</button>
  </form>
  <p class="auth-alt">New to CareShare? <a href="<?= h(url('pages/register.php')) ?>?next=<?= urlencode($next) ?>">Create an account</a><br><small>Staff? <a href="<?= h(url('admin/')) ?>">Use the staff sign-in</a></small></p>
</div></div></div></section>
<?php public_footer();
