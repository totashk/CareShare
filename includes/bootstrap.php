<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');


function base_path(): string {
    static $base = null;
    if ($base !== null) return $base;
    $project = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($project !== '' && strpos($script, $project . '/') === 0) {
        $rel = substr($script, strlen($project) + 1);
        $len = strlen($rel) + 1;
        if ($name !== '' && substr($name, -$len) === '/' . $rel) return $base = rtrim(substr($name, 0, -$len), '/');
    }
   
    $root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    if ($root !== '' && strpos($project, $root) === 0) return $base = rtrim(substr($project, strlen($root)), '/');
    return $base = '';
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('public/assets/' . ltrim($path, '/')); }
date_default_timezone_set('Africa/Tripoli');
$config = require __DIR__ . '/../config.php';
try { $pdo = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]); }
catch (PDOException $e) { http_response_code(503); echo '<h1>CareShare is not connected to its database</h1><p>Start MySQL in MAMP, import database/careshare.sql and database/migration_001_public_website.sql, and check config.php.</p>'; exit; }

try { $pdo->exec("SET time_zone = '".date('P')."'"); } catch (PDOException $e) {  }
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function logged_in(): bool { return isset($_SESSION['user']); }
function user(): array { return $_SESSION['user'] ?? []; }
function redirect(string $url): never { header('Location: '.$url); exit; }
function require_role(string $role): void { if (!logged_in() || user()['role'] !== $role) { http_response_code(403); require __DIR__.'/../app/denied.php'; exit; } }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals(csrf(), $_POST['csrf'] ?? '')) { http_response_code(419); exit('Form expired. Go back and try again.'); } }
function flash(string $message, string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function log_activity(PDO $pdo, string $action, string $entity, ?int $id=null): void { $s=$pdo->prepare('INSERT INTO activity_logs(user_id,action,entity_type,entity_id) VALUES(?,?,?,?)'); $s->execute([user()['id']??null,$action,$entity,$id]); }
function layout_start(string $title, string $active=''): void { $flash=take_flash(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?> · CareShare</title><link rel="icon" href="<?=h(asset('logo/favicon.svg'))?>" type="image/svg+xml"><link rel="stylesheet" href="<?=h(asset('css/admin.css'))?>"></head><body>
<?php if (logged_in()): ?><header class="topbar"><button class="menu-toggle" aria-label="Toggle menu" onclick="document.body.classList.toggle('nav-open')">☰</button><a href="?page=dashboard" class="mobile-brand"><img src="<?=h(asset('logo/careshare-logo.svg'))?>" alt="CareShare"></a><span class="top-spacer"></span><a class="user-chip" href="?page=profile"><?=h(user()['name'])?> <small><?=h(user()['role'])?></small></a><a class="logout-link" href="?page=logout">Sign out</a></header><aside class="sidebar"><a class="brand" href="?page=dashboard"><img src="<?=h(asset('logo/careshare-logo.svg'))?>" alt="CareShare"></a><div class="nav-label">WORKSPACE</div><nav><?php foreach ([['dashboard','⌂','Dashboard'],['equipment','▦','Equipment'],['equipment-offers','♧','Equipment offers'],['borrowers','♙','Borrowers'],['requests','▤','Loan requests'],['loans','↔','Active loans'],['overdue','◷','Overdue loans'],['messages','✉','Contact messages'],['reports','▥','Reports']] as [$p,$icon,$label]): ?><a class="nav-link <?=$active===$p?'active':''?>" href="?page=<?=$p?>"><span><?=$icon?></span><?=h($label)?></a><?php endforeach; ?><?php if(user()['role']==='Administrator'): ?><div class="nav-label admin-label">ADMINISTRATION</div><a class="nav-link <?=$active==='users'?'active':''?>" href="?page=users"><span>♙</span>Staff accounts</a><?php endif; ?></nav><div class="sidebar-footer"><a class="site-link" href="<?=h(url('index.php'))?>">↗ View public website</a><br><span class="status-dot"></span>Community equipment, cared for.</div></aside><main class="main-content"><div class="page-wrap">
<?php if($flash): ?><div class="alert <?=h($flash['type'])?>" role="status"><?=h($flash['message'])?></div><?php endif; ?>
<?php else: ?><main class="auth-shell"><?php endif; ?>
<?php }
function layout_end(): void { if(logged_in()): ?></div><footer class="footer">CareShare · Community equipment lending</footer></main><?php else: ?></main><?php endif; ?><script src="<?=h(asset('js/admin.js'))?>"></script></body></html><?php }
