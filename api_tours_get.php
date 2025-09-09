<?php
require 'includes/auth.php';
require 'includes/db.php';

$result = $conn->query("SELECT id, wp_id FROM tours_api");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

header('Content-Type: application/json');
echo json_encode($rows);
?>