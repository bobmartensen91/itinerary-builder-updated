<?php
require 'includes/auth.php';
require 'includes/db.php';
header('Content-Type: application/json');

// Simple cleaning function - no DOMDocument complexity
function simpleCleanDescription($rawDescription) {
    if (empty($rawDescription)) {
        return '(No description available)';
    }
    
    $cleaned = $rawDescription;
    
    // Remove JSON-like content at the beginning
    $cleaned = preg_replace('/^\s*\{[^}]*\}\s*/', '', $cleaned);
    $cleaned = preg_replace('/^[^<]*"[^"]*"[^<]*:/', '', $cleaned);
    
    // Remove inline styles
    $cleaned = preg_replace('/style\s*=\s*"[^"]*"/i', '', $cleaned);
    
    // Remove class attributes
    $cleaned = preg_replace('/class\s*=\s*"[^"]*"/i', '', $cleaned);
    
    // Remove id attributes
    $cleaned = preg_replace('/id\s*=\s*"[^"]*"/i', '', $cleaned);
    
    // Remove data-* attributes
    $cleaned = preg_replace('/data-[a-zA-Z0-9_-]*\s*=\s*"[^"]*"/i', '', $cleaned);
    
    // Clean up any double spaces left by attribute removal
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    $cleaned = preg_replace('/<(\w+)\s+>/', '<$1>', $cleaned); // Remove trailing spaces in tags
    
    return trim($cleaned);
}

// Get POST values
$wp_id = $_POST['wp_id'] ?? null;
$title = $_POST['title'] ?? '';
$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // ✅ Fixes &#8211; to real dash
$title = trim($title);

$content = $_POST['description'] ?? '';
$acf = $_POST['acf'] ?? '{}';
$featured_image = $_POST['featured_image'] ?? '';
$image1 = $_POST['image1'] ?? '';
$image2 = $_POST['image2'] ?? '';
$image3 = $_POST['image3'] ?? '';
$image4 = $_POST['image4'] ?? '';

if (!$wp_id) {
    echo json_encode(['success' => false, 'message' => 'No WP ID provided']);
    exit;
}

// Clean the description
$cleanedContent = simpleCleanDescription($content);

// Check if page already exists
$stmt = $conn->prepare("SELECT id FROM tours_api WHERE wp_id = ?");
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Prepare failed (SELECT): ' . $conn->error]));
}
$stmt->bind_param("i", $wp_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Update
    $stmt->close();
    $stmt = $conn->prepare("UPDATE tours_api 
        SET title=?, description=?, acf=?, featured_image=?, image1=?, image2=?, image3=?, image4=?, updated_at=NOW() 
        WHERE wp_id=?");
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Prepare failed (UPDATE): ' . $conn->error]));
    }
    $stmt->bind_param("ssssssssi", $title, $cleanedContent, $acf, $featured_image, $image1, $image2, $image3, $image4, $wp_id);
    if (!$stmt->execute()) {
        die(json_encode(['success' => false, 'message' => 'Execute failed (UPDATE): ' . $stmt->error]));
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Updated']);
} else {
    // Insert
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO tours_api 
        (wp_id, title, description, acf, featured_image, image1, image2, image3, image4) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Prepare failed (INSERT): ' . $conn->error]));
    }
    $stmt->bind_param("issssssss", $wp_id, $title, $cleanedContent, $acf, $featured_image, $image1, $image2, $image3, $image4);
    if (!$stmt->execute()) {
        die(json_encode(['success' => false, 'message' => 'Execute failed (INSERT): ' . $stmt->error]));
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Inserted']);
}
?>
