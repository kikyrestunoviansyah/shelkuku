<?php
/**
 * Tiny PHP File Manager (no external deps)
 * Purpose: Safe-ish file management on shared hosting without SSH.
 * Security: session login, CSRF tokens, base-dir jail, optional PHP upload block, no shell exec.
 * Author: you
 * Version: 1.1 (2025-08-24)
 *
 * IMPORTANT:
 * - Put this file somewhere private (e.g., /fileadmin/ protected by Basic Auth if possible).
 * - Change USERNAME and PASSWORD_SHA256_SALT/HASH below immediately.
 * - By default, uploading .php is blocked (toggle in CONFIG).
 */

declare(strict_types=1);
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

/* ===================== CONFIG ===================== */
$CONFIG = [
    // Base directory jail (absolute). Default: current directory of this script.
    'BASE_DIR' => realpath(__DIR__),

    // App name (UI only)
    'APP_NAME' => 'Tiny PHP File Manager',

    // --- AUTH (simple SHA-256) ---
    // Change this!
    'USERNAME' => 'admin',
    // Salt & SHA256 hash of (password . salt). Generate new with: php -r 'echo hash("sha256", "YOURPASSWORD"."YOURLONGSALT");'
    'PASSWORD_SHA256_SALT' => 'change_this_salt_please_3d0c98c9f1',
    // Default: hash of 'changeme123!' + the salt above. CHANGE THIS!
    'PASSWORD_SHA256_HASH' => '819f3615c06ece0720b0222d0a7137169a919e2c613f7dad2c0866c14bbf7367',

    // Block .php uploads for safety (recommended true). You can still edit existing .php if allowed below.
    'BLOCK_PHP_UPLOAD' => true,

    // Allow editing of these text extensions
    'EDITABLE_EXT' => ['txt','md','log','json','yaml','yml','xml','css','js','html','htm','env','ini','conf','php'],

    // Max size for text editor (bytes)
    'MAX_EDIT_BYTES' => 1024 * 1024, // 1 MB

    // Show hidden files (dotfiles)
    'SHOW_HIDDEN' => false,

    // Timezone for display
    'TIMEZONE' => 'Asia/Jakarta',
];
/* =================================================== */

date_default_timezone_set($CONFIG['TIMEZONE'] ?? 'UTC');

// Ensure BASE_DIR exists
if (!$CONFIG['BASE_DIR'] || !is_dir($CONFIG['BASE_DIR'])) {
    http_response_code(500);
    exit('BASE_DIR is invalid.');
}

$BASE = realpath($CONFIG['BASE_DIR']);

// CSRF helpers
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field() { echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; }
function csrf_verify() {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}

// Auth helpers
function is_logged_in() : bool { return !empty($_SESSION['auth_ok']) && $_SESSION['auth_ok'] === true; }
function require_login() {
    if (!is_logged_in()) {
        show_login();
        exit;
    }
}

// Path helpers
function clean_join($base, $rel) {
    $base = rtrim($base, DIRECTORY_SEPARATOR);
    $rel = str_replace(['\\','..'], ['/', ''], $rel); // strip backslashes and parent traversal
    $candidate = $base . '/' . ltrim($rel, '/');
    $real = realpath($candidate);
    if ($real === false) {
        // If not exists yet (e.g., new file), use normalized path but ensure it's still under base
        $real = $candidate;
    }
    // Ensure jail
    $baseReal = realpath($base);
    $check = realpath(dirname($real)) ?: realpath($base);
    if ($baseReal && $check && strpos($check, $baseReal) !== 0) {
        http_response_code(403);
        exit('Path escapes base directory.');
    }
    return $real;
}
function human_size($bytes) {
    $u = ['B','KB','MB','GB','TB']; $i = 0;
    while ($bytes >= 1024 && $i < count($u)-1) { $bytes /= 1024; $i++; }
    return sprintf('%.1f %s', $bytes, $u[$i]);
}
function ext_of($name) {
    $p = pathinfo($name);
    return strtolower($p['extension'] ?? '');
}
function is_hidden($name) {
    return substr(basename($name), 0, 1) === '.';
}

// Login throttling
$_SESSION['login_tries'] = $_SESSION['login_tries'] ?? 0;
$_SESSION['login_lock_until'] = $_SESSION['login_lock_until'] ?? 0;

// Routing
$action = $_GET['a'] ?? $_POST['a'] ?? 'list';
$relPath = $_GET['p'] ?? $_POST['p'] ?? '';
$here = clean_join($BASE, $relPath);

// Handle login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (time() < $_SESSION['login_lock_until']) {
        $rem = $_SESSION['login_lock_until'] - time();
        exit('Locked due to failed attempts. Try again in '.$rem.'s');
    }
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $salt = $CONFIG['PASSWORD_SHA256_SALT'];
    $hash = hash('sha256', $pass . $salt);
    if ($user === $CONFIG['USERNAME'] && hash_equals($CONFIG['PASSWORD_SHA256_HASH'], $hash)) {
        $_SESSION['auth_ok'] = true;
        $_SESSION['login_tries'] = 0;
        $_SESSION['login_lock_until'] = 0;
        header('Location: ?');
        exit;
    } else {
        $_SESSION['login_tries']++;
        if ($_SESSION['login_tries'] >= 5) {
            $_SESSION['login_lock_until'] = time() + 300; // 5 min lock
        }
        show_login('Username atau password salah.');
        exit;
    }
}
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ?');
    exit;
}

// Static assets (minimal CSS)
if ($action === 'css') {
    header('Content-Type: text/css; charset=UTF-8');
    echo "body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;max-width:1100px;margin:20px auto;padding:0 16px}
h1{font-size:20px;margin:10px 0}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
tr:hover{background:#fafafa}
a{color:#0b6; text-decoration:none}
a:hover{text-decoration:underline}
.header{display:flex;gap:10px;align-items:center;justify-content:space-between;margin:8px 0}
.actions a, .actions button{margin-right:8px}
.notice{padding:10px;border-radius:6px;background:#f6ffed;border:1px solid #b7eb8f;margin:8px 0}
.warn{background:#fff2e8;border-color:#ffbb96}
.err{background:#fff1f0;border-color:#ffa39e}
input[type=text],input[type=password]{padding:8px;border:1px solid #ddd;border-radius:6px;width:260px}
button,.btn{background:#0b6;color:#fff;border:0;border-radius:6px;padding:8px 12px;cursor:pointer}
button.secondary{background:#999}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;background:#eee;font-size:12px}
.path{font-family:monospace}
.footer{margin-top:24px;color:#666;font-size:12px}
.breadcrumbs a{margin-right:6px}
form.inline{display:inline}
textarea{width:100%;height:60vh;border:1px solid #ddd;border-radius:8px;padding:10px;font-family:ui-monospace,Menlo,Consolas,monospace}
";
    exit;
}

// Gate everything else behind login
require_login();

// --- Actions ---

// Download
if ($action === 'download' && is_file($here)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($here).'"');
    header('Content-Length: ' . filesize($here));
    readfile($here);
    exit;
}

// Preview (text only)
if ($action === 'view' && is_file($here)) {
    $ext = ext_of($here);
    $isText = in_array($ext, $CONFIG['EDITABLE_EXT'], true);
    if (!$isText) { header('Location: ?p='.urlencode(dirname($relPath))); exit; }
    $content = file_get_contents($here);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $content;
    exit;
}

// Save edited file
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!is_file($here)) { http_response_code(404); exit('File not found'); }
    $ext = ext_of($here);
    if (!in_array($ext, $CONFIG['EDITABLE_EXT'], true)) { exit('Not editable.'); }
    $data = $_POST['content'] ?? '';
    if (strlen($data) > $CONFIG['MAX_EDIT_BYTES']) { exit('Too large.'); }
    if (!is_writable($here)) { exit('File not writable.'); }
    file_put_contents($here, $data);
    header('Location: ?p=' . urlencode(dirname($relPath)) . '&msg=saved');
    exit;
}

// Upload
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!is_dir($here) || !is_writable($here)) { exit('Destination not writable.'); }
    $unzip = !empty($_POST['unzip']) && class_exists('ZipArchive');
    foreach ($_FILES['files']['error'] ?? [] as $i => $err) {
        if ($err !== UPLOAD_ERR_OK) continue;
        $name = basename($_FILES['files']['name'][$i]);
        $tmp = $_FILES['files']['tmp_name'][$i];
        $ext = ext_of($name);
        if ($CONFIG['BLOCK_PHP_UPLOAD'] && $ext === 'php') {
            continue; // silently skip php uploads by default
        }
        $dest = clean_join($BASE, $relPath . '/' . $name);
        if ($unzip && strtolower($ext) === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($tmp) === true) {
                $zip->extractTo($here);
                $zip->close();
            }
        } else {
            move_uploaded_file($tmp, $dest);
        }
    }
    header('Location: ?p=' . urlencode($relPath) . '&msg=uploaded');
    exit;
}

// New folder
if ($action === 'mkdir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $new = trim($_POST['name'] ?? '');
    if ($new === '' || preg_match('/[\\\\:*?"<>|]/', $new)) exit('Invalid name.');
    $dest = clean_join($BASE, $relPath . '/' . $new);
    if (!is_dir($dest)) { @mkdir($dest, 0755, true); }
    header('Location: ?p=' . urlencode($relPath));
    exit;
}

// Rename
if ($action === 'rename' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $here;
    $newName = trim($_POST['newname'] ?? '');
    if ($newName === '' || preg_match('/[\\\\:*?"<>|]/', $newName)) exit('Invalid name.');
    $dest = clean_join($BASE, dirname($relPath) . '/' . $newName);
    // prevent overwriting this manager
    if (realpath($old) === __FILE__) exit('Cannot rename this manager.');
    @rename($old, $dest);
    header('Location: ?p=' . urlencode(dirname($relPath)));
    exit;
}

// Delete
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $target = $here;
    if (realpath($target) === __FILE__) exit('Cannot delete this manager.');
    if (is_file($target)) {
        @unlink($target);
    } elseif (is_dir($target)) {
        // delete recursively
        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($rii as $file) {
            if ($file->isDir()) @rmdir($file->getRealPath());
            else @unlink($file->getRealPath());
        }
        @rmdir($target);
    }
    header('Location: ?p=' . urlencode(dirname($relPath)));
    exit;
}

// Editor screen
if ($action === 'edit' && is_file($here)) {
    $ext = ext_of($here);
    if (!in_array($ext, $CONFIG['EDITABLE_EXT'], true)) {
        header('Location: ?p=' . urlencode(dirname($relPath)));
        exit;
    }
    $size = filesize($here);
    $content = ($size <= $CONFIG['MAX_EDIT_BYTES']) ? file_get_contents($here) : '';
    page_header('Edit: ' . basename($here));
    echo '<div class="header"><div><a class="btn" href="?p='.urlencode(dirname($relPath)).'">&larr; Back</a></div></div>';
    echo '<p><span class="badge">'.htmlspecialchars(human_size($size)).'</span> <span class="path">'.htmlspecialchars($here).'</span></p>';
    echo '<form method="post" action="?a=save&p='.htmlspecialchars(urlencode($relPath)).'">';
    csrf_field();
    echo '<textarea name="content" placeholder="(File too large to display)" spellcheck="false">'.htmlspecialchars($content).'</textarea>';
    echo '<div style="margin-top:8px"><button class="btn">Save</button> <a class="btn secondary" href="?a=view&p='.urlencode($relPath).'">Raw view</a></div>';
    echo '</form>';
    page_footer();
    exit;
}

// Default: list directory
if (!is_dir($here)) {
    // If it's file but no action specified, show info
    if (is_file($here)) {
        header('Location: ?a=view&p=' . urlencode($relPath));
        exit;
    }
    // fallback to base
    $relPath = '';
    $here = $BASE;
}
page_header($CONFIG['APP_NAME'] . ' - ' . ($relPath !== '' ? $relPath : '/'));
breadcrumbs($relPath);

$showHidden = isset($_GET['hidden']) ? (bool)$_GET['hidden'] : $CONFIG['SHOW_HIDDEN'];

// Controls
echo '<div class="header">';
echo '<div>';
echo '<form class="inline" method="post" action="?a=mkdir&p='.htmlspecialchars(urlencode($relPath)).'">'; csrf_field();
echo '<input type="text" name="name" placeholder="New folder name"> <button class="btn">Create</button></form> ';
echo '</div>';
echo '<div>';
echo '<form class="inline" method="post" enctype="multipart/form-data" action="?a=upload&p='.htmlspecialchars(urlencode($relPath)).'">'; csrf_field();
echo '<input type="file" name="files[]" multiple> ';
if (class_exists('ZipArchive')) echo '<label><input type="checkbox" name="unzip" value="1"> Unzip .zip</label> ';
echo '<button class="btn">Upload</button></form> ';
echo '<a class="btn secondary" href="?p='.urlencode($relPath).'&hidden='.($showHidden?0:1).'">'.($showHidden?'Hide':'Show').' hidden</a> ';
echo '<a class="btn secondary" href="?a=logout">Logout</a>';
echo '</div>';
echo '</div>';

// Table
$items = @scandir($here) ?: [];
echo '<table><thead><tr><th>Name</th><th>Size</th><th>Modified</th><th>Actions</th></tr></thead><tbody>';

// Up link
if ($relPath !== '') {
    $parent = dirname($relPath);
    echo '<tr><td colspan="4"><a href="?p='.urlencode($parent).'">&larr; Up</a></td></tr>';
}

foreach ($items as $name) {
    if ($name === '.' || $name === '..') continue;
    if (!$showHidden && is_hidden($name)) continue;
    $full = $here . '/' . $name;
    $relChild = ltrim($relPath . '/' . $name, '/');
    $isDir = is_dir($full);
    $size = $isDir ? '-' : human_size(filesize($full));
    $mtime = date('Y-m-d H:i:s', filemtime($full));
    $actions = [];

    if ($isDir) {
        $actions[] = '<a href="?p='.urlencode($relChild).'">Open</a>';
    } else {
        $actions[] = '<a href="?a=download&p='.urlencode($relChild).'">Download</a>';
        $ext = ext_of($name);
        if (in_array($ext, $CONFIG['EDITABLE_EXT'], true) && filesize($full) <= $CONFIG['MAX_EDIT_BYTES']) {
            $actions[] = '<a href="?a=edit&p='.urlencode($relChild).'">Edit</a>';
            $actions[] = '<a href="?a=view&p='.urlencode($relChild).'">View</a>';
        }
    }
    // rename
    $actions[] = '<form class="inline" method="post" action="?a=rename&p='.urlencode($relChild).'" onsubmit="return confirm(\'Rename ' . htmlspecialchars($name, ENT_QUOTES) . '?\');">'
        . '<input type="hidden" name="newname" value="" id="rn_'.md5($relChild).'">'
        . '<input type="button" value="Rename" class="btn secondary" onclick="var n=prompt(\'New name\', \''.htmlspecialchars($name, ENT_QUOTES).'\'); if(n){ document.getElementById(\'rn_'.md5($relChild).'\').value=n; this.form.submit(); }">';
    csrf_field();
    $actions[] = '</form>';

    // delete
    $actions[] = '<form class="inline" method="post" action="?a=delete&p='.urlencode($relChild).'" onsubmit="return confirm(\'Delete ' . htmlspecialchars($name, ENT_QUOTES) . '?\');">';
    csrf_field();
    $actions[] = '<button class="secondary">Delete</button></form>';

    echo '<tr>';
    echo '<td>' . ($isDir ? '📁 ' : '📄 ') . '<span class="path">' . htmlspecialchars($name) . '</span></td>';
    echo '<td>' . htmlspecialchars($size) . '</td>';
    echo '<td>' . htmlspecialchars($mtime) . '</td>';
    echo '<td class="actions">' . implode(' ', $actions) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';

echo '<div class="footer"><span class="badge">'.htmlspecialchars($CONFIG['APP_NAME']).'</span> ';
echo 'Base: <span class="path">'.htmlspecialchars($BASE).'</span></div>';

page_footer();
exit;

// ------------- Views -------------

function page_header($title = 'File Manager') {
    global $CONFIG;
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>'.htmlspecialchars($CONFIG['APP_NAME'].' - '.$title).'</title>';
    echo '<link rel="stylesheet" href="?a=css">';
    echo '</head><body>';
    echo '<h1>'.htmlspecialchars($CONFIG['APP_NAME']).'</h1>';
}

function page_footer() {
    echo '</body></html>';
}

function breadcrumbs($relPath) {
    echo '<div class="breadcrumbs"><strong>Path:</strong> <span class="path">/</span> ';
    $acc = '';
    if ($relPath === '') { echo '</div>'; return; }
    $parts = explode('/', $relPath);
    foreach ($parts as $i => $p) {
        $acc .= ($i ? '/' : '') . $p;
        echo '<a class="path" href="?p='.urlencode($acc).'">'.htmlspecialchars($p).'</a> / ';
    }
    echo '</div>';
}

function show_login($error = '') {
    global $CONFIG;
    page_header('Login');
    if ($error) echo '<div class="notice err">'.htmlspecialchars($error).'</div>';
    if (!empty($_SESSION['login_lock_until']) && time() < $_SESSION['login_lock_until']) {
        $rem = $_SESSION['login_lock_until'] - time();
        echo '<div class="notice warn">Locked for '.$rem.'s due to failed logins.</div>';
    }
    echo '<form method="post" action="?a=login">';
    echo '<p><label>Username<br><input type="text" name="username" autocomplete="username" required></label></p>';
    echo '<p><label>Password<br><input type="password" name="password" autocomplete="current-password" required></label></p>';
    echo '<button class="btn">Login</button>';
    echo '</form>';
    echo '<p style="margin-top:10px;color:#666">Tip: Change config USERNAME, PASSWORD_SHA256_SALT & PASSWORD_SHA256_HASH.</p>';
    page_footer();
}
?>
