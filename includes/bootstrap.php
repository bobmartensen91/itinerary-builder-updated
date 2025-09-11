<?php
// includes/bootstrap.php - Universal bootstrap that works from any directory
if (!defined('APP_BOOTSTRAP_LOADED')) {
    define('APP_BOOTSTRAP_LOADED', true);
    
    // Determine the root path based on where bootstrap.php is located
    define('APP_ROOT', dirname(__DIR__) . '/');
    
    // Calculate the relative path from current script to root
    function calculateRelativePath() {
        $scriptPath = $_SERVER['SCRIPT_FILENAME'];
        $rootPath = realpath(APP_ROOT);
        $scriptDir = dirname(realpath($scriptPath));
        
        // If we're in the root
        if ($scriptDir === $rootPath) {
            return '';
        }
        
        // Calculate relative path
        $relative = '';
        $scriptParts = explode(DIRECTORY_SEPARATOR, $scriptDir);
        $rootParts = explode(DIRECTORY_SEPARATOR, $rootPath);
        
        // Remove common parts
        $commonCount = 0;
        foreach ($rootParts as $i => $part) {
            if (isset($scriptParts[$i]) && $scriptParts[$i] === $part) {
                $commonCount++;
            } else {
                break;
            }
        }
        
        // Add ../ for each remaining directory level
        $levels = count($scriptParts) - $commonCount;
        for ($i = 0; $i < $levels; $i++) {
            $relative .= '../';
        }
        
        return $relative;
    }
    
    define('APP_BASE_PATH', calculateRelativePath());
    
    // Helper function to get URL to any app resource
    function appUrl($path = '') {
        return APP_BASE_PATH . ltrim($path, '/');
    }
    
    // Helper function to get file system path
    function appPath($path = '') {
        return APP_ROOT . ltrim($path, '/');
    }
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Auto-load database connection
    require_once APP_ROOT . 'includes/db.php';
}
?>