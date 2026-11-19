<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/account_parts.php';
$b = require_borrower($pdo);
$today = date('Y-m-d');
$active = fetch_my_loans($pdo, (int)$b['id'], 'AND l.returned_at IS NULL ORDER BY l.due_date');
$history = fetch_my_loans($pdo, (int)$b['id'], 'AND l.returned_at IS NOT NULL ORDER BY l.returned_at DESC');
account_header('My loans', 'loans', $b);
?>
<section class="panel"><div class="panel-head"><h2>Currently on loan</h2></div><?php render_loans_table($active, $today); ?>
  <p class="note">Please return equipment by the date shown. If you need longer, contact CareShare before the return date.</p></section>
<section class="panel"><div class="panel-head"><h2>Borrowing history</h2></div><?php render_loans_table($history, $today, true); ?></section>
<?php account_footer();
