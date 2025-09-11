<?php
// Smart header.php - automatically handles paths and dependencies

// Load bootstrap if not already loaded
if (!defined('APP_BOOTSTRAP_LOADED')) {
    // Try to find bootstrap.php
    $bootstrapPaths = [
        __DIR__ . '/bootstrap.php',
        __DIR__ . '/../includes/bootstrap.php',
        'includes/bootstrap.php',
        '../includes/bootstrap.php'
    ];
    
    foreach ($bootstrapPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Itinerary Builder</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= appUrl('assets/style.css?v=1.6') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="page-wrapper">
<div class="content">

<header class="bg-dark text-white">
  <div class="container py-3 d-flex justify-content-between align-items-center">
    <a class="navbar-brand" href="<?= appUrl('dashboard.php') ?>">
      <img class="nav-logo" src="<?= appUrl('img/vietnam-rejser-logo-2024-light.png') ?>" alt="Logo">
    </a>

    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php if ($_SESSION['role'] === 'agent' || $_SESSION['role'] === 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" 
                   href="<?= appUrl('dashboard.php') ?>">
                   <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'add_customer_itinerary.php' ? 'active' : '' ?>" 
                   href="<?= appUrl('add_customer_itinerary.php') ?>">
                   <i class="fas fa-route me-1"></i>Itineraries
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'api_tours.php' ? 'active' : '' ?>" 
                   href="<?= appUrl('api_tours.php') ?>">
                   <i class="fas fa-map-marked-alt me-1"></i>Tours
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : '' ?>" 
                   href="<?= appUrl('customers.php') ?>">
                   <i class="fas fa-users me-1"></i>Guest
                </a>
              </li>
              <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                  <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>" 
                     href="<?= appUrl('admin_dashboard.php') ?>">
                     <i class="fas fa-crown me-1"></i>Admin Panel
                  </a>
                </li>
              <?php endif; ?>
            <?php elseif ($_SESSION['role'] === 'customer'): ?>
              <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" 
                   href="<?= appUrl('dashboard.php') ?>">
                   <i class="fas fa-suitcase-rolling me-1"></i>Min Rejse
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'customer_profile.php' ? 'active' : '' ?>" 
                   href="<?= appUrl('customer_profile.php') ?>">
                   <i class="fas fa-user me-1"></i>Min Profil
                </a>
              </li>
            <?php endif; ?>
          </ul>

          <span class="navbar-text text-white align-items-center d-flex">
            <?php
              $uid = $_SESSION['user_id'];
              $role = $_SESSION['role'];
              $name = '';
              $photo = '';

              // Get user data if database connection exists
              if (isset($conn) && $conn !== null) {
                  try {
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
                          $photo = !empty($user['photo']) && file_exists(appPath($user['photo'])) 
                            ? appUrl($user['photo']) 
                            : appUrl('img/default-user.png');
                        }
                      }
                  } catch (Exception $e) {
                      // Fallback to session data
                      $name = $_SESSION['name'] ?? 'User';
                  }
              } else {
                  $name = $_SESSION['name'] ?? 'User';
              }
              ?>
              <span class="navbar-text text-white d-flex align-items-center">
                <?php if ($photo): ?>
                  <img src="<?= htmlspecialchars($photo) ?>" 
                       alt="Profile" 
                       width="32" 
                       height="32" 
                       class="rounded-circle me-2" 
                       style="object-fit: cover;">
                <?php endif; ?>
                <span class="d-none d-md-inline">Hi, <?= $name ?></span>
                <span class="d-md-none"><i class="fas fa-user"></i></span>
              </span>
          <a href="<?= appUrl('logout.php') ?>" class="btn btn-outline-light btn-sm ms-3">
            <i class="fas fa-sign-out-alt me-1"></i>
            <span class="d-none d-md-inline">Log out</span>
            <span class="d-md-none"><i class="fas fa-sign-out-alt"></i></span>
          </a>
          <a class="btn btn-outline-light btn-sm ms-3 <?= str_contains($_SERVER['REQUEST_URI'], 'users/') ? 'active' : '' ?>" 
             href="<?= appUrl('users/users.php') ?>">
             <i class="fas fa-user-cog me-1"></i>Users
          </a>
        </div>
      </div>
    </nav>
    <?php endif; ?>
  </div>
</header>

<?php 
  if (strpos($_SERVER['REQUEST_URI'], "view_itinerary.php") !== false) {
    // No container for view_itinerary.php
  } else {
    echo "<div class='container mt-4 mainCon form__group'>";
  }
?>