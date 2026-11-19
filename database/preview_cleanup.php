<?php
declare(strict_types=1);
// Read-only preview. It performs SELECTs only and never deletes or updates rows.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cfg = require __DIR__ . '/../config.php';
try { $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
catch (PDOException $e) { fwrite(STDERR, "Database connection failed. Start MAMP and check config.php.\n"); exit(1); }
$candidateNames = ['Morgan Ellis','Jamie Patel','Alex Morgan','Taylor Brooks','Jordan Reed','Casey Lane','Riley Quinn','Avery Chen','Sam Rivera'];
$marks = implode(',', array_fill(0, count($candidateNames), '?'));
$q = $pdo->prepare("SELECT id,full_name,email,role,status FROM users WHERE id<>7 AND LOWER(email) NOT IN ('fatima@careshar.ly','hamza@careshare.ly') ORDER BY id");
$q->execute(); $staff = $q->fetchAll();
echo "READ-ONLY CLEANUP PREVIEW — no records were changed.\n\nNon-canonical staff accounts that a two-account cleanup might affect:\n";
if (!$staff) echo "  none\n";
$staffRefs = [
 'activity_logs' => $pdo->prepare('SELECT id,action FROM activity_logs WHERE user_id=? ORDER BY id'),
 'loan_requests created' => $pdo->prepare('SELECT id,status FROM loan_requests WHERE created_by=? ORDER BY id'),
 'loan_requests reviewed' => $pdo->prepare('SELECT id,status FROM loan_requests WHERE reviewed_by=? ORDER BY id'),
 'loans issued' => $pdo->prepare('SELECT id,item_id FROM loans WHERE issued_by=? ORDER BY id'),
 'loans returned' => $pdo->prepare('SELECT id,item_id FROM loans WHERE returned_by=? ORDER BY id'),
 'contact messages handled' => $pdo->prepare('SELECT id,status FROM contact_messages WHERE handled_by=? ORDER BY id'),
];
foreach ($staff as $u) {
    echo sprintf("  user id=%d | %s | %s | %s | %s\n", $u['id'], $u['full_name'], $u['email'], $u['role'], $u['status']);
    foreach ($staffRefs as $label=>$query) { $query->execute([$u['id']]); $rows=$query->fetchAll(PDO::FETCH_COLUMN); if ($rows) echo '    ' . $label . ' IDs: ' . implode(', ', $rows) . "\n"; }
}
$q = $pdo->prepare("SELECT id,full_name,email,status,registered_at FROM borrowers WHERE full_name IN ($marks) OR email LIKE '%@example.test' ORDER BY id");
$q->execute($candidateNames); $borrowers = $q->fetchAll();
echo "\nRecognizable demo borrowers (verify each identity before any cleanup):\n";
if (!$borrowers) echo "  none\n";
$requests = $pdo->prepare('SELECT id,status,created_at FROM loan_requests WHERE borrower_id=? ORDER BY id');
$loans = $pdo->prepare('SELECT id,item_id,issued_at,due_date,returned_at FROM loans WHERE borrower_id=? ORDER BY id');
$requestChildren = $pdo->prepare('SELECT ri.id,ri.request_id FROM request_items ri JOIN loan_requests r ON r.id=ri.request_id WHERE r.borrower_id=? ORDER BY ri.id');
$returns = $pdo->prepare('SELECT rr.id,rr.loan_id,rr.returned_at,rr.condition_label FROM return_records rr JOIN loans l ON l.id=rr.loan_id WHERE l.borrower_id=? ORDER BY rr.id');
$borrowerActivity = $pdo->prepare("SELECT id,action,entity_type,entity_id FROM activity_logs WHERE entity_type='borrower' AND entity_id=? ORDER BY id");
foreach ($borrowers as $b) {
    echo sprintf("  borrower id=%d | %s | %s | %s | registered %s\n", $b['id'], $b['full_name'], $b['email'] ?? '(no email)', $b['status'], $b['registered_at']);
    $requests->execute([$b['id']]); foreach ($requests->fetchAll() as $r) echo sprintf("    request id=%d | %s | %s\n", $r['id'], $r['status'], $r['created_at']);
    $requestChildren->execute([$b['id']]); foreach ($requestChildren->fetchAll() as $r) echo sprintf("    request_items row id=%d | request id=%d\n", $r['id'], $r['request_id']);
    $loans->execute([$b['id']]); foreach ($loans->fetchAll() as $l) echo sprintf("    loan id=%d | item=%d | %s through %s | returned %s\n", $l['id'], $l['item_id'], $l['issued_at'], $l['due_date'], $l['returned_at'] ?? 'still active');
    $returns->execute([$b['id']]); foreach ($returns->fetchAll() as $r) echo sprintf("    return record id=%d | loan id=%d | %s | %s\n", $r['id'], $r['loan_id'], $r['returned_at'], $r['condition_label']);
    $borrowerActivity->execute([$b['id']]); foreach ($borrowerActivity->fetchAll() as $a) echo sprintf("    activity log id=%d | %s | %s:%s\n", $a['id'], $a['action'], $a['entity_type'], $a['entity_id']);
}
echo "\nNo deletion statements were run. Foreign-key dependent requests and loans are listed above.\n";
