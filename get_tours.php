<?php
require 'includes/db.php';
require 'includes/auth.php';

header('Content-Type: application/json');

try {
    // Get all tours for the search functionality
    $stmt = $conn->prepare("SELECT id, title FROM tours_api ORDER BY title ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $tours = [];
    while ($row = $result->fetch_assoc()) {
        $tours[] = [
            'id' => (int)$row['id'],
            'title' => $row['title']
        ];
    }

    echo json_encode($tours);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load tours']);
}
?>