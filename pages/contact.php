<?php
require_once __DIR__ . '/../includes/site.php';
$borrower = current_borrower($pdo);
$errors = [];
$old = ['name' => $borrower['full_name'] ?? '', 'email' => $borrower['email'] ?? '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['name', 'email', 'subject', 'message'] as $k) $old[$k] = trim((string)($_POST[$k] ?? ''));
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 120) $errors['name'] = 'Enter your name (2 to 120 characters).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) $errors['email'] = 'Enter a valid email address.';
    if (mb_strlen($old['subject']) < 3 || mb_strlen($old['subject']) > 150) $errors['subject'] = 'Enter a subject (3 to 150 characters).';
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 3000) $errors['message'] = 'Your message must be between 10 and 3000 characters.';
    if (!empty($_POST['website'])) $errors['form'] = 'Your message could not be sent.';                      
    if (!$errors && (time() - (int)($_SESSION['last_contact_at'] ?? 0)) < 20) $errors['form'] = 'Please wait a few seconds before sending another message.';

    if (!$errors) {
        try {
            $s = $pdo->prepare('INSERT INTO contact_messages(name,email,subject,message) VALUES(?,?,?,?)');
            $s->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);
            $_SESSION['last_contact_at'] = time();
            flash('Thank you. Your message has been received and saved for our team. We will reply by email.');
            redirect(url('pages/contact.php'));     
        } catch (PDOException $e) {
            $errors['form'] = 'Sorry, your message could not be saved just now, so it has not been sent. Please try again later' . (SITE_CONTACT['email'] !== '' ? ' or email ' . SITE_CONTACT['email'] : '') . '.';
        }
    }
}
$err = fn(string $k) => isset($errors[$k]) ? '<div class="field-error" data-server="1" id="' . $k . '-error">' . h($errors[$k]) . '</div>' : '';
$cls = fn(string $k) => isset($errors[$k]) ? ' has-error' : '';
public_header('Contact us', 'contact');
?>
<section class="page-banner"><div class="container"><h1>Contact us</h1><p>Questions about equipment, your account or how we work? Send us a message.</p></div></section>
<section class="section">
  <div class="container grid grid-2" style="gap:48px;align-items:start">
    <div>
      <?php if (!empty($errors['form'])): ?><div class="alert alert-error" role="alert"><?= h($errors['form']) ?></div><?php endif; ?>
      <form method="post" action="<?= h(url('pages/contact.php')) ?>" data-validate>
        <?= csrf_field() ?>
        <div class="hp-field" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
          <div class="field<?= $cls('name') ?>"><label for="name">Your name</label><input type="text" id="name" name="name" required minlength="2" maxlength="120" value="<?= h($old['name']) ?>"><?= $err('name') ?></div>
          <div class="field<?= $cls('email') ?>"><label for="email">Email address</label><input type="email" id="email" name="email" required maxlength="190" value="<?= h($old['email']) ?>"><?= $err('email') ?></div>
          <div class="field full<?= $cls('subject') ?>"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required minlength="3" maxlength="150" value="<?= h($old['subject']) ?>"><?= $err('subject') ?></div>
          <div class="field full<?= $cls('message') ?>"><label for="message">Message</label><textarea id="message" name="message" required minlength="10" maxlength="3000"><?= h($old['message']) ?></textarea><div class="hint">Please do not include medical details.</div><?= $err('message') ?></div>
        </div>
        <button class="btn btn-primary btn-lg" type="submit">Send message</button>
      </form>
    </div>
    <aside class="card">
      <h2 style="font-size:1.3rem">Other ways to reach us</h2>
      <p>Email: <?= contact_value('email') ?></p>
      <p>Phone: <?= contact_value('phone') ?></p>
      <p>Address: <?= contact_value('address') ?></p>
      <p>Opening hours: <?= contact_value('hours') ?></p>
      <p class="hint" style="color:var(--muted);font-size:.9rem">Messages sent with the form are saved for our staff and answered by email. This form is not for emergencies.</p>
    </aside>
  </div>
</section>
<?php public_footer();
