<?php
// Only set cookie parameters if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 9600, // 1 hour
        'path' => '/',      // cookie valid for whole domain
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Function to return login.php path based on project directory
function getLoginPath() {
    $projectFolder = '/itinerary-builder/'; // adjust if your folder name changes
    return $projectFolder . 'login.php';
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
