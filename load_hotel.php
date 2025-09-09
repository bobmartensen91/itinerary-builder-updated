<?php
require 'includes/db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare("SELECT name, link FROM hotels WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$hotel = $result->fetch_assoc();

echo json_encode($hotel);
