<?php
require 'includes/auth.php';
require 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the tour first to get image paths
$stmt = $conn->prepare("SELECT image1, image2, image3 FROM tours WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
  $tour = $result->fetch_assoc();

  // Delete images if they exist
  for ($i = 1; $i <= 3; $i++) {
    $img = $tour["image$i"];
    if ($img && file_exists($img)) {
      unlink($img);
    }
  }

  // Delete the tour record
  $delete = $conn->prepare("DELETE FROM tours WHERE id = ?");
  $delete->bind_param("i", $id);
  $delete->execute();
}

header("Location: tours.php");
exit;
