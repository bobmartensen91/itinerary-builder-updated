<?php
session_start();
require 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Check in users table (agents/admins)
  $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['name'] = $user['name'];
      $_SESSION['role'] = $user['role'];
      
      if ($user['role'] === 'agent') {
        header("Location: dashboard.php");
      } else {
        header("Location: customer_dashboard.php");
      }
      exit;
    }
  }else {
    // Not found in users → check in customers
    $stmt = $conn->prepare("SELECT id, name, password, role FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $customer = $result->fetch_assoc();
      if (password_verify($password, $customer['password'])) {
        $_SESSION['user_id'] = $customer['id'];
        $_SESSION['name'] = $customer['name'];
        $_SESSION['role'] = $customer['role'];
        header("Location: customer_dashboard.php");
        exit;
      }
    }
  }

  $error = "Ugyldig email eller adgangskode.";
}
?>

<?php include 'includes/header.php'; ?>
<div class="loginCon">
  <h2>Login</h2>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Adgangskode</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary">Login</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>