<?php
require_once __DIR__ . '/../includes/site.php';
public_header('How it works', 'how');
?>
<section class="page-banner"><div class="container"><h1>How it works</h1><p>Borrowing from CareShare takes six simple steps.</p></div></section>
<section class="section">
  <div class="container narrow">
    <ol class="steps">
      <li><h3>Browse available equipment</h3><p>Search our equipment list. Each item shows whether it is available right now.</p></li>
      <li><h3>Register or log in</h3><p>Create a free account with your name, email and phone number so we can contact you about your request.</p></li>
      <li><h3>Submit an equipment request</h3><p>Choose the equipment, the date you would like to collect it and the date you expect to return it.</p></li>
      <li><h3>Wait for staff approval</h3><p>A member of our team reviews every request. You can follow its status (Pending, Approved, Rejected or Issued) in your account.</p></li>
      <li><h3>Arrange collection and borrow</h3><p>Once approved, we will arrange collection with you. The item is recorded as issued to you.</p></li>
      <li><h3>Return it when the loan ends</h3><p>Bring the equipment back by your return date. We check each item before it is lent to someone else.</p></li>
    </ol>
    <div class="notice">
      <strong>Good to know:</strong> a request is not a guarantee. Requests are subject to staff approval and to what is actually in stock when we review them. You can cancel a request while it is still pending.
    </div>
    <p class="btn-row"><a class="btn btn-primary btn-lg" href="<?= h(url('pages/equipment.php')) ?>">Browse equipment</a>
    <?php if (!borrower_logged_in()): ?><a class="btn btn-outline btn-lg" href="<?= h(url('pages/register.php')) ?>">Register</a><?php endif; ?></p>
  </div>
</section>
<?php public_footer();
