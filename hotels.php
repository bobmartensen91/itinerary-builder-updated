<?php
require 'includes/auth.php';
require 'includes/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    if ($name) {
      $stmt = $conn->prepare("INSERT INTO hotels (name, link) VALUES (?, ?)");
      $stmt->bind_param("ss", $name, $link);
      $stmt->execute();
      $message = 'Hotel tilføjet!';
    }
  }

  if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    if ($id && $name) {
      $stmt = $conn->prepare("UPDATE hotels SET name = ?, link = ? WHERE id = ?");
      $stmt->bind_param("ssi", $name, $link, $id);
      $stmt->execute();
      $message = 'Hotel opdateret!';
    }
  }

  if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    if ($id) {
      $stmt = $conn->prepare("DELETE FROM hotels WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $message = 'Hotel slettet!';
    }
  }
}

$result = $conn->query("SELECT * FROM hotels ORDER BY name ASC");
?>

<?php include 'includes/header.php'; ?>
<div class="container mt-4">
  <h2>Hoteller</h2>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
  <?php endif; ?>

  <form method="post" class="row g-3 mb-4">
    <div class="col-md-4">
      <input type="text" name="name" class="form-control" placeholder="Hotelnavn" required>
    </div>
    <div class="col-md-5">
      <input type="url" name="link" class="form-control" placeholder="Hotel website (valgfri)">
    </div>
    <div class="col-md-3">
      <button type="submit" name="add" class="btn btn-primary">➕ Tilføj hotel</button>
    </div>
  </form>

  <table class="table table-bordered table-striped">
    <thead class="table-light">
      <tr>
        <th>Navn</th>
        <th>Website</th>
        <th style="width: 160px;">Handling</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <form method="post">
            <td>
              <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required>
            </td>
            <td>
              <input type="url" name="link" value="<?= htmlspecialchars($row['link']) ?>" class="form-control">
            </td>
            <td>
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button type="submit" name="update" class="btn btn-sm btn-success">💾 Gem</button>
              <button type="submit" name="delete" class="btn btn-sm btn-danger" onclick="return confirm('Slet hotel?')">🗑 Slet</button>
            </td>
          </form>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
