<?php
require 'includes/db.php';
require 'includes/auth.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("SELECT title, description, image1, image2, image3, image4 FROM tours_api WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Tour not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid ID']);
}
?>