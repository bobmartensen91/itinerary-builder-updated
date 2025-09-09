<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle customer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $notes = $_POST['notes'];

  // Generate a secure random password
  $plainPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'), 0, 10);
  $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, notes, user_id, password, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
  if (!$stmt) {
    die("SQL prepare error: " . $conn->error);
  }

  $stmt->bind_param("ssssis", $name, $email, $phone, $notes, $user_id, $hashedPassword);
  $stmt->execute();

  echo '<div class="alert alert-success">Kunde oprettet. Midlertidig adgangskode: <strong>' . htmlspecialchars($plainPassword) . '</strong></div>';
}

// Fetch customers
if ($role === 'admin') {
  $customers = $conn->query("SELECT customers.*, users.name AS agent_name FROM customers LEFT JOIN users ON customers.user_id = users.id ORDER BY customers.created_at DESC");
} else {
  $stmt = $conn->prepare("SELECT customers.*, users.name AS agent_name FROM customers LEFT JOIN users ON customers.user_id = users.id WHERE customers.user_id = ? ORDER BY customers.created_at DESC");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $customers = $stmt->get_result();
}
?>

<h2>Kunder</h2>
<h3>Tilføj en ny kunde</h3>
<form method="post" class="mb-4">
  <div class="row g-3">
    <div class="col-md-3">
      <input type="text" name="name" class="form-control" placeholder="Navn" required>
    </div>
    <div class="col-md-3">
      <input type="email" name="email" class="form-control" placeholder="Email" required>
    </div>
    <div class="col-md-2">
      <input type="text" name="phone" class="form-control" placeholder="Telefon">
    </div>
    <div class="col-md-3">
      <input type="text" name="notes" class="form-control" placeholder="Noter">
    </div>
    <div class="col-md-1">
      <button class="btn btn-primary w-100">Gem</button>
    </div>
  </div>
</form>

<h3>Liste over brugere</h3>
<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Navn</th>
      <th>Email</th>
      <th>Telefon</th>
      <th>Noter</th>
      <th>Agent</th>
      <th>Oprettet</th>
      <th>Handlinger</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($c = $customers->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= htmlspecialchars($c['phone']) ?></td>
        <td><?= htmlspecialchars($c['notes']) ?></td>
        <td><?= htmlspecialchars($c['agent_name'] ?? '–') ?></td>
        <td><?= $c['created_at'] ?></td>
        <td>
          <a href="upload_file.php?customer_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
            Upload filer
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include 'includes/footer.php'; ?>
