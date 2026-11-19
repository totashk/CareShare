<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cfg = require __DIR__ . '/../config.php';
try { $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }
catch (PDOException $e) { fwrite(STDERR, "Database connection failed. Check MAMP and config.php.\n"); exit(1); }
$email = $argv[1] ?? '';
$allowed = ['fatima@careshar.ly', 'hamza@careshare.ly'];
if (!in_array(strtolower($email), $allowed, true)) {
    fwrite(STDERR, "Usage: php database/set_staff_password.php fatima@careshar.ly|Hamza@careshare.ly\n"); exit(2);
}
$q = $pdo->prepare('SELECT id,email FROM users WHERE LOWER(email)=LOWER(?)'); $q->execute([$email]); $user = $q->fetch(PDO::FETCH_ASSOC);
if (!$user) { fwrite(STDERR, "That staff account is not present. Run migration_002_equipment_offers_staff.sql first.\n"); exit(3); }
function secret(string $prompt): string {
    fwrite(STDOUT, $prompt);
    $hidden = false;
    if (DIRECTORY_SEPARATOR === '/' && function_exists('exec')) { exec('stty -echo 2>/dev/null', $unused, $code); $hidden = ($code === 0); }
    $value = fgets(STDIN) ?: '';
    if ($hidden) { exec('stty echo 2>/dev/null'); fwrite(STDOUT, "\n"); }
    return rtrim($value, "\r\n");
}
$one = secret('New password (12 characters minimum): ');
$two = secret('Repeat password: ');
if (strlen($one) < 12 || !hash_equals($one, $two)) { fwrite(STDERR, "Passwords must match and be at least 12 characters. No changes were made.\n"); exit(4); }
$s = $pdo->prepare("UPDATE users SET password_hash=?,status='Active' WHERE id=? AND LOWER(email)=LOWER(?)");
$s->execute([password_hash($one, PASSWORD_DEFAULT), $user['id'], $email]);
fwrite(STDOUT, "Password set and account activated for " . $user['email'] . ".\n");
unset($one, $two);
