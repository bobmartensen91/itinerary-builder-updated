<?php
// includes/path_helper.php

class PathHelper {
    private static $basePath = null;
    private static $rootPath = null;
    
    public static function getBasePath() {
        if (self::$basePath === null) {
            self::$basePath = self::calculateBasePath();
        }
        return self::$basePath;
    }
    
    public static function getRootPath() {
        if (self::$rootPath === null) {
            self::$rootPath = self::findRootPath();
        }
        return self::$rootPath;
    }
    
    private static function calculateBasePath() {
        // Get the directory where PathHelper is located (should be includes/)
        $helperDir = dirname(__FILE__);
        
        // Get the directory that called PathHelper
        $backtrace = debug_backtrace();
        $callerFile = $backtrace[0]['file'] ?? __FILE__;
        $callerDir = dirname($callerFile);
        
        // Calculate relative path from caller to root
        $relativePath = self::getRelativePath($callerDir, dirname($helperDir));
        
        return $relativePath;
    }
    
    private static function findRootPath() {
        // Find the absolute path to the root directory
        $helperDir = dirname(__FILE__);
        return dirname($helperDir); // Go up one level from includes/
    }
    
    private static function getRelativePath($from, $to) {
        // Normalize paths
        $from = realpath($from);
        $to = realpath($to);
        
        if ($from === $to) {
            return '';
        }
        
        $from = explode(DIRECTORY_SEPARATOR, $from);
        $to = explode(DIRECTORY_SEPARATOR, $to);
        
        // Find common base
        $common = 0;
        while (isset($from[$common]) && isset($to[$common]) && $from[$common] === $to[$common]) {
            $common++;
        }
        
        // Build relative path
        $relativePath = str_repeat('..' . DIRECTORY_SEPARATOR, count($from) - $common);
        $relativePath .= implode(DIRECTORY_SEPARATOR, array_slice($to, $common));
        
        // Convert to web-friendly format
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        
        // Ensure trailing slash for directories
        if (!empty($relativePath) && !str_ends_with($relativePath, '/')) {
            $relativePath .= '/';
        }
        
        return $relativePath;
    }
    
    // Helper methods for common paths
    public static function includesPath($file = '') {
        return self::getBasePath() . 'includes/' . $file;
    }
    
    public static function uploadsPath($file = '') {
        return self::getBasePath() . 'uploads/' . $file;
    }
    
    public static function agentsUploadPath($file = '') {
        return self::getBasePath() . 'uploads/agents/' . $file;
    }
    
    public static function toursUploadPath($file = '') {
        return self::getBasePath() . 'uploads/tours/' . $file;
    }
    
    public static function usersPath($file = '') {
        return self::getBasePath() . 'users/' . $file;
    }
    
    public static function toursPath($file = '') {
        return self::getBasePath() . 'tours/' . $file;
    }
    
    // URL helpers (for links and image sources)
    public static function url($path = '') {
        return self::getBasePath() . ltrim($path, '/');
    }
    
    // File system helpers (for file operations) - uses absolute paths
    public static function path($path = '') {
        $rootPath = self::getRootPath();
        return $rootPath . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
    
    // Check if file exists relative to base path
    public static function fileExists($path) {
        return file_exists(self::path($path));
    }
    
    // Create directory relative to base path
    public static function createDir($path, $permissions = 0755, $recursive = true) {
        $fullPath = self::path($path);
        if (!is_dir($fullPath)) {
            return mkdir($fullPath, $permissions, $recursive);
        }
        return true;
    }
    
    // Safe require/include methods that preserve working directory
    public static function requireFile($path) {
        $originalDir = getcwd();
        $filePath = self::includesPath($path);
        
        if (!file_exists($filePath)) {
            throw new Exception("Required file not found: $filePath");
        }
        
        try {
            require $filePath;
        } finally {
            // Restore original working directory
            chdir($originalDir);
        }
    }
    
    public static function includeFile($path) {
        $originalDir = getcwd();
        $filePath = self::includesPath($path);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        try {
            include $filePath;
            return true;
        } finally {
            // Restore original working directory
            chdir($originalDir);
        }
    }
    
    public static function requireOnceFile($path) {
        $originalDir = getcwd();
        $filePath = self::includesPath($path);
        
        if (!file_exists($filePath)) {
            throw new Exception("Required file not found: $filePath");
        }
        
        try {
            require_once $filePath;
        } finally {
            // Restore original working directory
            chdir($originalDir);
        }
    }
    
    // Debug method to help troubleshoot path issues
    public static function debug() {
        return [
            'basePath' => self::getBasePath(),
            'rootPath' => self::getRootPath(),
            'helperLocation' => __FILE__,
            'currentWorkingDir' => getcwd(),
            'callerFile' => debug_backtrace()[0]['file'] ?? 'unknown',
            'includesPath' => self::includesPath(),
            'uploadsPath' => self::uploadsPath(),
        ];
    }
}

// Global convenience function
function basePath($append = '') {
    return PathHelper::getBasePath() . $append;
}

// Additional convenience functions
function includePath($file = '') {
    return PathHelper::includesPath($file);
}

function uploadPath($file = '') {
    return PathHelper::uploadsPath($file);
}
?>