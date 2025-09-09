<?php
// General-purpose reusable functions

// --- AUTH ---
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'agent';
}

// --- PASSWORD ---
function hash_password($plain_text) {
    return password_hash($plain_text, PASSWORD_BCRYPT);
}

function verify_password($plain, $hashed) {
    return password_verify($plain, $hashed);
}

// --- DATE / PRICE ---
function format_date($datetime) {
    return date('d-m-Y H:i', strtotime($datetime));
}

function format_price($price) {
    return number_format($price, 0, ',', '.') . ' DKK';
}

// --- FILE UPLOAD ---
function move_uploaded_file_with_prefix($file, $destination_folder, $prefix = '') {
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($prefix, true) . '.' . $ext;
    $target = rtrim($destination_folder, '/') . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $target);
    return $target;
}
////////////////////////////////////////

?>
