<?php
require 'includes/db.php';
require 'includes/auth.php';

if (!isset($_GET['id'])) {
  http_response_code(400);
  exit('Missing image ID');
}

$image_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch image and check access
$stmt = $conn->prepare("SELECT idi.image_path FROM itinerary_day_images idi JOIN itinerary_days d ON idi.day_id = d.id JOIN itineraries i ON d.itinerary_id = i.id JOIN customers c ON i.customer_id = c.id WHERE idi.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $image_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
  http_response_code(403);
  exit('Access denied');
}

$image = $result->fetch_assoc();
$file = $image['image_path'];

// Delete DB record
$stmt = $conn->prepare("DELETE FROM itinerary_day_images WHERE id = ?");
$stmt->bind_param("i", $image_id);
$stmt->execute();

// Delete file from server
if (file_exists($file)) {
  unlink($file);
}

echo "deleted";