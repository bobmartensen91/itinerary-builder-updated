<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';
$tours = $conn->query("SELECT * FROM tours ORDER BY created_at DESC");
?>
<h2>Ture</h2>
<a href="add_tour.php" class="btn btn-primary mb-3">Tilføj ny tur</a>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>Titel</th>
      <th>Oprettet</th>
      <th>Billeder</th>
      <th>Handling</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $tours->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= $row['created_at'] ?></td>
        <td>
          <?php for ($i = 1; $i <= 3; $i++): ?>
            <?php if (!empty($row["image$i"])): ?>
              <img src="<?= $row["image$i"] ?>" width="60" class="me-1 mb-1">
            <?php endif; ?>
          <?php endfor; ?>
        </td>
        <td>
          <a href="edit_tour.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Rediger</a>
          <a href="delete_tour.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Slet denne tur?')">Slet</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<?php include 'includes/footer.php'; ?>