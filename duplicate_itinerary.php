<?php
require 'includes/db.php';
require 'includes/auth.php';

if (!isset($_GET['id']) && !isset($_POST['original_id'])) {
    die("Ingen rejseplan valgt.");
}

$original_id = $_GET['id'] ?? $_POST['original_id'];
$original_id = intval($original_id);

// Fetch original itinerary
$stmt = $conn->prepare("SELECT * FROM itineraries WHERE id = ?");
$stmt->bind_param("i", $original_id);
$stmt->execute();
$original = $stmt->get_result()->fetch_assoc();

if (!$original) {
    die("Rejseplan ikke fundet.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_customer_id = intval($_POST['customer_id']);
    $new_title = trim($_POST['title']);
    $new_token = bin2hex(random_bytes(32));

    // Duplicate itinerary
    $stmt = $conn->prepare("INSERT INTO itineraries (customer_id, title, public_token) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $new_customer_id, $new_title, $new_token);
    $stmt->execute();
    $new_itinerary_id = $stmt->insert_id;

    // Duplicate days
    $days = $conn->query("SELECT * FROM itinerary_days WHERE itinerary_id = $original_id");
    while ($day = $days->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO itinerary_days (itinerary_id, day_range, day_title, description, overnight) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $new_itinerary_id, $day['day_range'], $day['day_title'], $day['description'], $day['overnight']);
        $stmt->execute();
        $new_day_id = $stmt->insert_id;

        // Duplicate images
        $imgs = $conn->query("SELECT * FROM itinerary_day_images WHERE day_id = " . $day['id']);
        while ($img = $imgs->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO itinerary_day_images (day_id, image_path) VALUES (?, ?)");
            $stmt->bind_param("is", $new_day_id, $img['image_path']);
            $stmt->execute();
        }
    }

    header("Location: edit_itinerary.php?id=$new_itinerary_id");
    exit;
}

// Load customers
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customers = $stmt->get_result();
?>
<?php include 'includes/header.php'; ?>
<h2>Duplikér Rejseplan</h2>
<p>Original titel: <strong><?= htmlspecialchars($original['title']) ?></strong></p>

<form method="POST">
    <input type="hidden" name="original_id" value="<?= $original_id ?>">
    <div class="mb-3">
        <label for="customer_id" class="form-label">Vælg ny kunde</label>
        <select name="customer_id" id="customer_id" class="form-select" required>
            <option value="">-- Vælg kunde --</option>
            <?php while ($row = $customers->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Ny titel</label>
        <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($original['title']) ?> (Kopi)">
    </div>
    <button type="submit" class="btn btn-primary">Opret kopi</button>
    <a href="dashboard.php" class="btn btn-secondary">Annuller</a>
</form>
<?php include 'includes/footer.php'; ?>
