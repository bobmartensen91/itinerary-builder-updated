<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Itinerary Builder</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/style.css?v=1.5">
  <link rel="stylesheet" href="assets/tour_style.css?v=1.5">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
<div class="page-wrapper">
<div class="content">

<header class="bg-dark text-white">
  <div class="container py-3 d-flex justify-content-between align-items-center">
    <a class="navbar-brand" href="dashboard.php">
      <img class="nav-logo" src="img/vietnam-rejser-logo-2024-light.png" alt="Logo">
    </a>

    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php if ($_SESSION['role'] === 'agent' || $_SESSION['role'] === 'admin'): ?>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a></li>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'add_customer_itinerary.php' ? 'active' : '' ?>" href="add_customer_itinerary.php">Itineraries</a></li>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tours.php' ? 'active' : '' ?>" href="tours.php">Tours</a></li>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'api_tours.php' ? 'active' : '' ?>" href="api_tours.php">API Tours</a></li>
              <!-- <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'hotels.php' ? 'active' : '' ?>" href="hotels.php">Hotels</a></li> -->
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : '' ?>" href="customers.php">Guest</a></li>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="users.php">Users</a></li>
              <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">Admin Panel</a></li>
              <?php endif; ?>
            <?php elseif ($_SESSION['role'] === 'customer'): ?>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Min Rejse</a></li>
              <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'customer_profile.php' ? 'active' : '' ?>" href="customer_profile.php">Min Profil</a></li>
            <?php endif; ?>
          </ul>

          <span class="navbar-text text-white align-items-center d-flex">
            <?php
              require_once __DIR__ . '/db.php';
              $uid = $_SESSION['user_id'];
              $role = $_SESSION['role'];
              $name = '';
              $photo = '';

              if ($role === 'agent' || $role === 'admin') {
                $stmt = $conn->prepare("SELECT name, photo FROM users WHERE id = ?");
                $stmt->bind_param("i", $uid);
              } else {
                $stmt = $conn->prepare("SELECT name FROM customers WHERE id = ?");
                $stmt->bind_param("i", $uid);
              }

              $stmt->execute();
              $result = $stmt->get_result();
              if ($user = $result->fetch_assoc()) {
                $name = htmlspecialchars($user['name']);
                if ($role === 'agent' || $role === 'admin') {
                  $photo = !empty($user['photo']) && file_exists($user['photo']) ? $user['photo'] : 'img/default-user.png';
                }
              }
              ?>
              <span class="navbar-text text-white d-flex align-items-center">
                <?php if ($photo): ?>
                  <img src="<?= htmlspecialchars($photo) ?>" alt="Profile" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
                <?php endif; ?>
                Hi, <?= $name ?>
              </span>
          <a href="logout.php" class="btn btn-outline-light btn-sm" style="margin-left: 20px;">Log out</a>
        </div>
      </div>
    </nav>
    <?php endif; ?>
  </div>
</header>

<?php 
  if (strpos($_SERVER['REQUEST_URI'], "view_itinerary.php") !== false)
    //|| strpos($_SERVER['REQUEST_URI'], "api_tours_view.php") !== false) 
  {
      // Do nothing or add specific code here
  } else {
      echo "<div class='container mt-4 mainCon form__group'>";
  }
?>

