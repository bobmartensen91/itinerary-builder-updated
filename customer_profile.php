<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SESSION['role'] !== 'customer') {
  header("Location: dashboard.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);

  $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
  $stmt->bind_param("ssi", $name, $email, $user_id);
  $stmt->execute();

  $_SESSION['name'] = $name; // Update session name
  $message = "<div class='alert alert-success'>Profil opdateret.</div>";
}

// Handle password update
if (isset($_POST['new_password']) && $_POST['new_password'] !== '') {
  $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
  $stmt->bind_param("si", $new_password, $user_id);
  $stmt->execute();
  $message .= "<div class='alert alert-success'>Adgangskode ændret.</div>";
}

// Get current profile info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();
?>

<?php include 'includes/header.php'; ?>
<h2>Min Profil</h2>

<?= $message ?>

<form method="POST" class="mt-3">
  <div class="mb-3">
    <label>Navn</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Ny adgangskode <small>(valgfri)</small></label>
    <input type="password" name="new_password" class="form-control">
  </div>
  <button class="btn btn-primary">Opdater profil</button>
</form>

<?php include 'includes/footer.php'; ?>