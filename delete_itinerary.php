<?php
require 'includes/db.php';
require 'includes/auth.php';

$itinerary_id = intval($_GET['id'] ?? 0);

if (!$itinerary_id) {
  die("Ugyldig rejseplan ID.");
}

// Fetch all day IDs for the itinerary
$stmt = $conn->prepare("SELECT id FROM itinerary_days WHERE itinerary_id = ?");
$stmt->bind_param("i", $itinerary_id);
$stmt->execute();
$result = $stmt->get_result();

$day_ids = [];
while ($row = $result->fetch_assoc()) {
  $day_ids[] = $row['id'];
}

// Delete associated images from disk and DB
foreach ($day_ids as $day_id) {
  $stmt = $conn->prepare("SELECT image_path FROM itinerary_day_images WHERE day_id = ?");
  $stmt->bind_param("i", $day_id);
  $stmt->execute();
  $images = $stmt->get_result();

  while ($img = $images->fetch_assoc()) {
    $path = $img['image_path'];
    if (file_exists($path)) {
      unlink($path);
    }
  }

  $del_stmt = $conn->prepare("DELETE FROM itinerary_day_images WHERE day_id = ?");
  $del_stmt->bind_param("i", $day_id);
  $del_stmt->execute();
}

// Delete itinerary days
$del_stmt = $conn->prepare("DELETE FROM itinerary_days WHERE itinerary_id = ?");
$del_stmt->bind_param("i", $itinerary_id);
$del_stmt->execute();

// Delete the itinerary itself
$del_stmt = $conn->prepare("DELETE FROM itineraries WHERE id = ?");
$del_stmt->bind_param("i", $itinerary_id);
$del_stmt->execute();

// Redirect with success flag
header("Location: dashboard.php?deleted=1");
exit;
