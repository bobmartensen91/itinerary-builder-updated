<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Ugyldigt tur-ID.</div>";
    include 'includes/footer.php';
    exit;
}

$tour_id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM tours_api WHERE id = ?");
$stmt->bind_param("i", $tour_id);
$stmt->execute();
$result = $stmt->get_result();
$tour = $result->fetch_assoc();

if (!$tour) {
    echo "<div class='alert alert-danger'>Tur ikke fundet.</div>";
    include 'includes/footer.php';
    exit;
}

// Decode ACF JSON field
$acf = !empty($tour['acf']) ? json_decode($tour['acf'], true) : null;
$acf_title = $acf['title'] ?? $tour['title'];
$acf_duration = $acf['duration'] ?? '';
$acf_description = $acf['description'] ?? '';
?>

<?php if (!empty($tour['featured_image'])): ?>
<div class="container">
    <div class="hero-image-container">
        <img src="<?= htmlspecialchars($tour['featured_image']) ?>" class="hero-image" alt="Featured image">
        <div class="hero-overlay">
            <h1 class="hero-title"><?= htmlspecialchars($acf_title) ?></h1>
            <?php if ($acf_duration): ?>
                <div class="hero-duration">
                    <i class="fas fa-clock me-2"></i>
                    Duration: <?= htmlspecialchars($acf_duration) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Fallback if no featured image -->
<div class="container mt-4">
    <h1><?= htmlspecialchars($acf_title) ?></h1>
    <?php if ($acf_duration): ?>
        <p><strong>Duration:</strong> <?= htmlspecialchars($acf_duration) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="container">
    <?php if ($acf_description): ?>
        <div class="mb-4">
            <?= $acf_description ?> <!-- already contains HTML -->
        </div>
    <?php endif; ?>
    
    <h4>Images</h4>
    <div class="row">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <?php if (!empty($tour["image$i"])): ?>
                <div class="col-md-3 mb-3">
                    <img src="<?= htmlspecialchars($tour["image$i"]) ?>" class="img-fluid rounded">
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    
    <a href="api_tours.php" class="btn btn-secondary mt-3">Back to tourlist</a>
</div>

<?php include 'includes/footer.php'; ?>