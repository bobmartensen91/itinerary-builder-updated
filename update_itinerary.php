<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['itinerary_id'])) {
  die("Ugyldig anmodning");
}

$itinerary_id = intval($_POST['itinerary_id']);
$user_id = $_SESSION['user_id'];

// Check access
$stmt = $conn->prepare("SELECT i.id FROM itineraries i JOIN customers c ON i.customer_id = c.id WHERE i.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $itinerary_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->num_rows) die("Ingen adgang");

foreach ($_POST['days'] as $day) {
  $day_id = intval($day['id']);
  $range = trim($day['day_range']);
  $title = trim($day['day_title']);
  $desc = trim($day['description']);
  $overnight = trim($day['overnight']);

  $stmt = $conn->prepare("UPDATE itinerary_days SET day_range = ?, day_title = ?, description = ?, overnight = ? WHERE id = ? AND itinerary_id = ?");
  $stmt->bind_param("ssssii", $range, $title, $desc, $overnight, $day_id, $itinerary_id);
  $stmt->execute();

  // Handle image upload
  if (isset($_FILES['images']['name'][$day_id])) {
    $totalImages = count($_FILES['images']['name'][$day_id]);
    for ($i = 0; $i < min(4, $totalImages); $i++) {
      if ($_FILES['images']['error'][$day_id][$i] === 0) {
        $tmp = $_FILES['images']['tmp_name'][$day_id][$i];
        $name = basename($_FILES['images']['name'][$day_id][$i]);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $safeName = uniqid() . '.' . $ext;
        $target = "uploads/" . $safeName;

        if (move_uploaded_file($tmp, $target)) {
          $stmt = $conn->prepare("INSERT INTO itinerary_day_images (day_id, image_path) VALUES (?, ?)");
          $stmt->bind_param("is", $day_id, $target);
          $stmt->execute();
        }
      }
    }
  }
}

header("Location: dashboard.php");
exit;
