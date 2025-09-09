<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SESSION['role'] !== 'customer') {
  header("Location: dashboard.php");
  exit;
}

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'] ?? 'Gæst';

// Get itinerary and token
$stmt = $conn->prepare("
  SELECT i.id, i.title, i.public_token, cu.name AS customer_name
  FROM itineraries i
  JOIN customers cu ON i.customer_id = cu.id
  WHERE cu.id = ?
");
if (!$stmt) {
  die('SQL Error: ' . $conn->error);
}
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$itinerary_id = null;
$itinerary_token = null;

if ($result->num_rows > 0) {
  $itinerary = $result->fetch_assoc();
  $itinerary_id = $itinerary['id'];
  $itinerary_token = $itinerary['public_token'];
  $customer_name = $itinerary['customer_name'];
}

// Fetch uploaded files
$stmt = $conn->prepare("SELECT * FROM customer_files WHERE customer_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$files_result = $stmt->get_result();

include 'includes/header.php';
?>
<h2 class="mb-4">Min rejse</h2>

<div class="row row-cols-1 row-cols-md-2 g-4">
  <!-- Box 1: Welcome -->
  <div class="col">
    <div class="card h-100 text-center p-4">
      <div class="card-body">
        <h5 class="card-title">Velkommen</h5>
        <p class="card-text">Hej <?= htmlspecialchars($customer_name) ?>, velkommen til din rejse side!</p>
      </div>
    </div>
  </div>

  <!-- Box 2: View Travel Plan -->
  <div class="col">
    <div class="card h-100 text-center p-4">
      <div class="card-body">
        <h5 class="card-title">Din rejseplan</h5>
        <?php if ($itinerary_token): ?>
          <a href="view_itinerary.php?token=<?= $itinerary_token ?>" class="btn btn-primary">Klik for at se din rejseplan</a>
        <?php else: ?>
          <p>Du venter midlertidig på at få lavet et rejseprogram</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Box 3: Uploaded Files -->
  <div class="col">
    <div class="card h-100 text-center p-4">
      <div class="card-body">
        <h5 class="card-title">Dine dokumenter</h5>
        <?php if ($files_result->num_rows > 0): ?>
          <ul class="list-unstyled">
            <?php while ($file = $files_result->fetch_assoc()): ?>
              <li>
                <a href="<?= $file['file_path'] ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <p>Ingen filer uploadet endnu.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Box 4: Countdown Placeholder -->
  <div class="col">
    <div class="card h-100 text-center p-4">
      <div class="card-body">
        <h5 class="card-title">Din rejse starter om</h5>
        <p id="countdown">21 dage, 10 timer, 30 minutter</p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
