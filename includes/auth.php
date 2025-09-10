<?php
// Only set cookie parameters if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600, // 1 hour
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Function to calculate path to root login.php
function getLoginPath() {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $pathParts = explode('/', trim($scriptPath, '/'));
    
    // Remove the script filename
    array_pop($pathParts);
    
    // Count directory levels (excluding root)
    $levels = count($pathParts);
    
    // Build relative path back to root
    if ($levels === 0) {
        return 'login.php';
    } else {
        return str_repeat('../', $levels) . 'login.php';
    }
}

// Prevent redirect loops - check what page we're on
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$loginPages = ['login.php', 'index.php'];
$isPublicPage = in_array($currentScript, $loginPages);

// Redirect to login if not authenticated (but not if on a public page)
if (!isset($_SESSION['user_id']) && !$isPublicPage) {
    header('Location: ' . getLoginPath());
    exit;
}

// Session timeout check - only if user is logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        header('Location: ' . getLoginPath() . '?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}
?>