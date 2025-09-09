<?php
require 'includes/db.php';
require 'vendor/autoload.inc.php'; // Dompdf via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

$token = $_GET['token'] ?? '';
if (empty($token)) {
  die("Token mangler.");
}

$user_id = $_SESSION['user_id'] ?? null;

// Fetch itinerary using token and user ownership
$stmt = $conn->prepare("
  SELECT i.id, i.title, c.name AS customer_name, c.notes, u.name AS agent_name
  FROM itineraries i
  JOIN customers c ON c.id = i.customer_id
  JOIN users u ON c.user_id = u.id
  WHERE i.public_token = ? AND c.user_id = ?
");
if (!$stmt) {
  die("SQL fejl: " . $conn->error);
}
$stmt->bind_param("si", $token, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result->num_rows) die("Rejseplan ikke fundet eller adgang nægtet.");
$itinerary = $result->fetch_assoc();

// Fetch days
$stmt = $conn->prepare("SELECT * FROM itinerary_days WHERE itinerary_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->bind_param("i", $itinerary['id']);
$stmt->execute();
$days = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch images per day
$imagesByDay = [];
foreach ($days as $day) {
  $stmt = $conn->prepare("SELECT image_path FROM itinerary_day_images WHERE day_id = ?");
  $stmt->bind_param("i", $day['id']);
  $stmt->execute();
  $imagesByDay[$day['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Setup Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Start HTML buffer
ob_start();
?>

<style>
  body { font-family: Poppins, sans-serif; font-size: 12px; }
  h2 { background: #912e2e; color: white; padding: 10px; }
  h3 { margin-bottom: 5px; color: #444; }
  .day-block { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; border-radius: 6px; }
  .img-row { display: flex; gap: 10px; margin-top: 10px; }
  .img-row img { width: 100%; border-radius: 4px; }
  .img-wrap { width: 30%; }
  .page-break { page-break-after: always; }
  .d-flex { display: flex !important; }
  .flex-fill { flex: 1 1 auto !important; }
</style>

<h2><?= htmlspecialchars($itinerary['title']) ?></h2>
<div class="d-flex bd-highlight itineraryInfo">
  <div class="p-2 flex-fill bd-highlight"><p><strong>Kunde:</strong> <?= htmlspecialchars($itinerary['customer_name']) ?></p></div>
  <div class="p-2 flex-fill bd-highlight"><p><strong>Rejsekonsulent:</strong> <?= htmlspecialchars($itinerary['agent_name']) ?></p></div>
  <?php if (!empty($itinerary['notes'])): ?>
    <div class="p-2 flex-fill bd-highlight"><p><strong>Noter:</strong> <?= nl2br(htmlspecialchars($itinerary['notes'])) ?></p></div>
  <?php endif; ?>
</div>

<?php foreach ($days as $day): ?>
  <div class="day-block">
    <h3>Dag <?= htmlspecialchars($day['day_range']) ?>: <?= htmlspecialchars($day['day_title']) ?></h3>
    <p><strong>Beskrivelse:</strong><br><?= nl2br($day['description']) ?></p>
    <p><strong>Overnatning:</strong> <?= htmlspecialchars($day['overnight']) ?></p>

    <?php
      $mealMap = [
        'Breakfast' => 'Morgenmad',
        'Lunch' => 'Frokost',
        'Dinner' => 'Aftensmad'
      ];
      $mealArray = array_filter(array_map('trim', explode(',', $day['meals'] ?? '')));
      echo "<p><strong>Måltider:</strong> ";
      if (!empty($mealArray)) {
        $translatedMeals = array_map(fn($m) => $mealMap[$m] ?? $m, $mealArray);
        echo implode(', ', $translatedMeals);
      } else {
        echo "Ingen måltider inkluderet";
      }
      echo "</p>";
    ?>

    <?php if (!empty($imagesByDay[$day['id']])): ?>
      <table width="100%" cellspacing="10">
        <tr>
          <?php foreach (array_slice($imagesByDay[$day['id']], 0, 3) as $img): ?>
            <td width="33%" valign="top">
              <?php
                $imgPath = __DIR__ . '/' . $img['image_path'];
                if (file_exists($imgPath)) {
                  $imgData = base64_encode(file_get_contents($imgPath));
                  $ext = pathinfo($imgPath, PATHINFO_EXTENSION);
                  $mimeType = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                  $src = 'data:' . $mimeType . ';base64,' . $imgData;
                  echo '<img src="' . $src . '" style="width:100%; border-radius:4px;">';
                } else {
                  echo '<p style="color:red;">Billede ikke fundet.</p>';
                }
              ?>
            </td>
          <?php endforeach; ?>
        </tr>
      </table>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Rejseplan_' . preg_replace('/\s+/', '_', $itinerary['customer_name']) . '_' . preg_replace('/\s+/', '_', $itinerary['title']) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
exit;
