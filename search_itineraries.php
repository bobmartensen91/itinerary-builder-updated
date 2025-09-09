<?php
require 'includes/db.php';
require 'includes/auth.php';
$user_id = $_SESSION['user_id'];
$search = '%' . ($_POST['search'] ?? '') . '%';
$stmt = $conn->prepare("
  SELECT i.id, i.title, c.travel_number, i.created_at, c.name AS customer_name, u.name AS agent_name
  FROM itineraries i
  JOIN customers c ON i.customer_id = c.id
  JOIN users u ON c.user_id = u.id
  WHERE c.user_id = ? AND (i.title LIKE ? OR c.name LIKE ? OR c.travel_number LIKE ?)
  ORDER BY i.created_at DESC
");
$stmt->bind_param("isss", $user_id, $search, $search, $search);
$stmt->execute();
$results = $stmt->get_result();
while ($row = $results->fetch_assoc()):
?>
<tr>
  <td style="width: 120px;"><?= htmlspecialchars($row['travel_number']) ?></td>
  <td><?= htmlspecialchars($row['title']) ?></td>
  <td style="width: 300px;"><?= htmlspecialchars($row['customer_name']) ?></td>
  <td style="width: 180px;"><?= htmlspecialchars($row['agent_name']) ?></td>
  <td style="width: 120px;"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
  <td style="width: 325px;">
    <a href="edit_itinerary.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Rediger</a>
    <a href="view_itinerary.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary">Vis</a>
    <a href="duplicate_itinerary.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Duplikér</a>
    <a target="_blank" href="export_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">PDF</a>
    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Slet denne rejseplan?')" class="btn btn-sm btn-danger">Slet</a>
  </td>
</tr>
<?php endwhile; ?>