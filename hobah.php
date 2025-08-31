<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
 * PHP Path+Icon Scanner (minimal)
 * - Output: <path> + ikon indikator aja
 *   ⚠️  = indikasi berbahaya (eval/exec/include dari input/obfuscation/remote include, dsb.)
 *   🔒  = ada form password / manual gate
 *   📤  = ada indikasi upload (input type=file / $_FILES / move_uploaded_file)
 * - Pure baca file + regex (no exec), recursive, ignore beberapa folder noisy
 */

function safe_read_small($path, $max=1048576){ // 1MB/file
  $sz = @filesize($path);
  if ($sz === false || $sz > $max) return [null, $sz];
  $c = @file_get_contents($path, false, null, 0, $max+1);
  return [$c===false?null:$c, $sz];
}

function regex_any($hay, $patterns){
  $labels=[];
  foreach($patterns as $label=>$rx){
    if (@preg_match($rx, $hay)) $labels[$label]=true;
  }
  return array_keys($labels);
}

function icon_flags_from_tags(array $tags) {
  $uploadTags = ['Form Upload (HTML)','Upload API (PHP)'];
  $passTags   = ['Form Password (HTML)','Manual Auth Gate'];
  $alertTags  = [
    'eval()','assert() as eval','preg_replace /e','create_function()',
    'variable function call','include from input','system/exec/shell',
    'base64_decode payload','gzinflate/gzuncompress','str_rot13',
    'long single line (packed)','allow_url_fopen usage','curl remote',
    'include http wrapper','include var path','post->cmd->exec'
  ];
  $hasUpload = (bool)array_intersect($tags, $uploadTags);
  $hasPass   = (bool)array_intersect($tags, $passTags);
  $hasAlert  = (bool)array_intersect($tags, $alertTags);
  $icons = [];
  if ($hasAlert)  $icons[] = '⚠️';
  if ($hasPass)   $icons[] = '🔒';
  if ($hasUpload) $icons[] = '📤';
  return implode(' ', $icons);
}

function fs_scan_dir_min($root, $opts=[]) {
  $root = rtrim($root);
  if ($root==='' || !is_dir($root)) return ['error'=>"Path tidak valid / bukan direktori: $root"];

  $maxFiles   = (int)($opts['max_files'] ?? 20000);
  $maxBytes   = (int)($opts['max_bytes'] ?? 1048576);
  $exts       = $opts['exts'] ?? ['php','phtml','php5','php7','php8','inc','phar'];
  $ignoreDirs = $opts['ignore_dirs'] ?? ['.git','.svn','.hg','node_modules','vendor/.cache','cache','tmp','.idea','.vscode','vendor'];
  $ignoreRx   = '#/(?:'.implode('|', array_map('preg_quote',$ignoreDirs)).')(/|$)#i';
  $extRx      = '#\.('.implode('|', array_map('preg_quote',$exts)).')$#i';

  // Pola-pola
  $patterns = [
    // Upload
    'Form Upload (HTML)'   => '#<input[^>]+type=["\']file["\']#i',
    'Upload API (PHP)'     => '#\$_FILES|\bmove_uploaded_file\s*\(#i',

    // Password gate
    'Form Password (HTML)' => '#<input[^>]+type=["\']password["\']#i',
    'Manual Auth Gate'     => '#(md5|sha1)\s*\(\s*\$?_?POST\[["\']password["\']#i',

    // Dangerous/obfuscation/remote
    'eval()'               => '#\beval\s*\(#i',
    'assert() as eval'     => '#\bassert\s*\(\s*[\'"]?#i',
    'preg_replace /e'      => '#preg_replace\s*\([^,]+,[^,]+,.*e[\'"]#i',
    'create_function()'    => '#\bcreate_function\s*\(#i',
    'variable function call'=> '#\$\w+\s*\(\s*\$?_?(?:GET|POST|REQUEST|COOKIE)\b#i',
    'include from input'   => '#\b(include|require|include_once|require_once)\s*\(\s*\$?_?(GET|POST|REQUEST|COOKIE)\b#i',
    'system/exec/shell'    => '#\b(shell_exec|system|exec|passthru|proc_open|popen)\s*\(#i',
    'base64_decode payload'=> '#base64_decode\s*\(\s*[\'"][A-Za-z0-9+/]{80,}={0,2}[\'"]\s*\)#',
    'gzinflate/gzuncompress'=> '#\b(gzinflate|gzuncompress|gzdecode)\s*\(#i',
    'str_rot13'            => '#\bstr_rot13\s*\(#i',
    'long single line (packed)' => '#.{4000,}#s',
    'allow_url_fopen usage'=> '#file_get_contents\s*\(\s*[\'"]https?://#i',
    'curl remote'          => '#\bcurl_(init|exec|setopt)\b#i',
    'include http wrapper' => '#(include|require)(_once)?\s*\(\s*[\'"]https?://#i',
    'include var path'     => '#(include|require)(_once)?\s*\(\s*\$?_?(?:GET|POST|REQUEST|COOKIE)\[[^\]]+\]\s*\)#i',
    'post->cmd->exec'      => '#\$_POST\[[\'"](?:cmd|exec|shell)[\'"]\]\s*;?.{0,120}\b(shell_exec|system|exec|passthru)\s*\(#is',
  ];

  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS|FilesystemIterator::FOLLOW_SYMLINKS),
    RecursiveIteratorIterator::SELF_FIRST
  );

  $rows = [];
  $count = 0;
  foreach($it as $p){
    if ($count >= $maxFiles) break;
    if ($p->isDir()) {
      $pathDir = str_replace('\\', '/', $p->getPathname());
      if (preg_match($ignoreRx, $pathDir)) { $it->next(); }
      continue;
    }
    $path = $p->getPathname();
    if (!preg_match($extRx, $path)) continue;

    [$content, $size] = safe_read_small($path, $maxBytes);
    if ($content === null) { $count++; continue; }

    $tags = regex_any($content, $patterns);
    if (empty($tags)) { $count++; continue; }

    $icons = icon_flags_from_tags($tags);
    if ($icons === '') { $count++; continue; }

    $rows[] = ['path'=>$path, 'icons'=>$icons];
    $count++;
  }

  // Sort: ⚠️ duluan, lalu 🔒/📤, lalu alfabetis
  usort($rows, function($a,$b){
    $prio = ['⚠️'=>3,'🔒'=>2,'📤'=>1];
    $pa = 0; foreach ($prio as $k=>$v) if (strpos($a['icons'],$k)!==false) $pa = max($pa,$v);
    $pb = 0; foreach ($prio as $k=>$v) if (strpos($b['icons'],$k)!==false) $pb = max($pb,$v);
    if ($pa !== $pb) return $pb <=> $pa;
    return strcasecmp($a['path'],$b['path']);
  });

  return ['items'=>$rows, 'scanned'=>$count];
}

// Handle request
$fsResults = null; $fsError = null;
if (isset($_GET['do_fs_scan'])) {
  $scanPath = trim($_GET['fs_path'] ?? '');
  $opts = [
    'max_files' => (int)($_GET['fs_max'] ?? 20000),
    'max_bytes' => (int)($_GET['fs_max_bytes'] ?? 1048576),
    'exts'      => isset($_GET['fs_exts']) && $_GET['fs_exts'] !== '' ? array_map('trim', explode(',', $_GET['fs_exts'])) : ['php','phtml','php5','php7','php8','inc','phar'],
  ];
  $fs = fs_scan_dir_min($scanPath, $opts);
  if (isset($fs['error'])) $fsError = $fs['error']; else $fsResults = $fs;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Path+Icon Scanner</title>
<style>
:root { color-scheme: dark; }
body { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background:#0f0f12; color:#e6e6e6; margin:24px; }
h2 { margin: 0 0 12px 0; }
.card { background:#17171b; border:1px solid #2a2a31; border-radius:12px; padding:14px; margin-bottom:16px; }
form { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
input[type=text], input[type=number] { background:#101014; color:#e6e6e6; border:1px solid #2a2a31; padding:6px 8px; border-radius:8px; }
button { background:#1f1f26; color:#fff; border:1px solid #2a2a31; padding:6px 10px; border-radius:8px; cursor:pointer; }
.small { color:#9aa0a6; font-size:12px; }
table { width:100%; border-collapse: collapse; }
th,td { border-bottom:1px solid #2a2a31; padding:8px; }
th { text-align:left; color:#aeb0b8; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
.icons { font-size:18px; }
</style>
</head>
<body>

<h2>File Scanner — Path + Ikon aja</h2>
<div class="card">
  <form method="get">
    <label>Directory:</label>
    <input type="text" name="fs_path" size="48" placeholder="/var/www/html" value="<?= htmlspecialchars($_GET['fs_path'] ?? '/var/www/html') ?>">
    <label>Exts:</label>
    <input type="text" name="fs_exts" size="20" value="<?= htmlspecialchars($_GET['fs_exts'] ?? 'php,phtml,inc,phar,php5,php7,php8') ?>">
    <label>Max Files:</label>
    <input type="number" name="fs_max" value="<?= htmlspecialchars($_GET['fs_max'] ?? '20000') ?>" min="1" max="200000">
    <label>Max Bytes/File:</label>
    <input type="number" name="fs_max_bytes" value="<?= htmlspecialchars($_GET['fs_max_bytes'] ?? '1048576') ?>" min="1024">
    <input type="hidden" name="do_fs_scan" value="1">
    <button type="submit">Scan</button>
  </form>
  <div class="small">Ikon: ⚠️ bahaya · 🔒 password form/gate · 📤 upload. Hanya indikator—cek manual sebelum aksi.</div>
</div>

<div class="card">
<?php if ($fsError): ?>
  <div class="small" style="color:#ff4d4f;"><?= htmlspecialchars($fsError) ?></div>
<?php elseif ($fsResults): ?>
  <?php if (!empty($fsResults['items'])): ?>
    <table>
      <tr><th>File</th><th>Indikator</th></tr>
      <?php foreach ($fsResults['items'] as $it): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($it['path']) ?></td>
          <td class="icons"><?= htmlspecialchars($it['icons']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <div class="small">Discan: ±<?= (int)$fsResults['scanned'] ?> file (dibatasi di opsi).</div>
  <?php else: ?>
    <div class="small">Nggak ada indikator yang ketemu di path tersebut.</div>
  <?php endif; ?>
<?php else: ?>
  <div class="small">Masukkan path lalu tekan Scan.</div>
<?php endif; ?>
</div>

</body>
</html>
