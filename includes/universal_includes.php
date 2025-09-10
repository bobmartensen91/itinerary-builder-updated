<?php
// Universal include helper - works from any directory
function includeWithFallback($file) {
    $paths = [
        __DIR__ . '/' . $file,           // From includes folder
        '../includes/' . $file,          // From subfolder  
        'includes/' . $file,             // From root
        '../../includes/' . $file,       // From deeper subfolder
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return require_once $path;
        }
    }
    
    die("Could not locate required file: $file");
}

// Load PathHelper if available
$usePathHelper = false;
foreach (['path_helper.php', '../includes/path_helper.php', 'includes/path_helper.php'] as $path) {
    if (file_exists($path)) {
        require_once $path;
        $usePathHelper = true;
        break;
    }
}
?>