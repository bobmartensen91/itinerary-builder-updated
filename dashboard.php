<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SESSION['role'] === 'customer') {
  header("Location: customer_dashboard.php");
  exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT i.id, i.title, c.travel_number, i.created_at, c.name AS customer_name, u.name AS agent_name, i.public_token
  FROM itineraries i
  JOIN customers c ON i.customer_id = c.id
  JOIN users u ON c.user_id = u.id
  WHERE c.user_id = ?
  ORDER BY i.created_at DESC");
if (!$stmt) {
  die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$itineraries = $stmt->get_result();
?>
<?php include 'includes/header.php'; ?>
<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert" id="deleteSuccess">
    ✅ Itinerary has been deleted successfully. Redirecting in <span id="countdown">5</span> seconds...
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>

  <script>
    let seconds = 5;
    const countdownEl = document.getElementById('countdown');

    const interval = setInterval(() => {
      seconds--;
      countdownEl.textContent = seconds;

      if (seconds <= 0) {
        clearInterval(interval);
        document.getElementById('deleteSuccess').classList.remove('show');
        document.getElementById('deleteSuccess').style.display = 'none';
      }
    }, 1000);
  </script>
<?php endif; ?>
<h2>Itineraries</h2>

<div class="mb-3">
  <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search for a guest or itinerary title...">
</div>

<!-- Loader -->
<div id="loader" class="text-center my-3" style="display: none;">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
</div>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Travel Number</th>
      <th>Itinerary title</th>
      <th>Guest</th>
      <th>Contact person</th>
      <th>Updated</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody id="itineraryTableBody">
    <?php while ($row = $itineraries->fetch_assoc()): ?>
      <tr>
        <td style="width: 120px;"><?= htmlspecialchars($row['travel_number']) ?></td>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td style="width: 300px;"><?= htmlspecialchars($row['customer_name']) ?></td>
        <td style="width: 180px;"><?= htmlspecialchars($row['agent_name']) ?></td>
        <td style="width: 120px;"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
        <td style="width: 325px;">
          <a href="edit_itinerary.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
          <a href="view_itinerary.php?token=<?= $row['public_token'] ?>" class="btn btn-sm btn-secondary">Show</a>
          <a href="duplicate_itinerary.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Duplicate</a>
          <a target="_blank" href="export_pdf.php?token=<?= $row['public_token'] ?>" class="btn btn-sm btn-success">PDF</a>
          <a href="delete_itinerary.php?id=<?= $row['id'] ?>" 
             onclick="return confirm('Are you sure you want to delete this itinerary?')"
             class="btn btn-sm btn-danger">
             Delete
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- No Results Message -->
<div id="noResults" class="alert alert-warning text-center" style="display: none;">
  Ingen resultater fundet.
</div>

<div class="container">
  <div class="row">
    <div class="col">
      <h3>Itinerary Builder functions</h3>
      <ul>
        <li>Lav en database med ture ✓</li>
        <li>Flyt rundt på dagene med en pil op og ned - kræver lidt mere justering</li> 
        <li>Hele rejser som man kan lave kopier af ✓</li>
        <li>Tilføj måltider ✓</li>
        <li>Kun 3 billeder evt. med lightbox ✓</li>
        <li>Noter skal med i rejseplanoversigt ✓</li>
        <li>VR Kontakt person ✓</li>
        <li>Fil upload til en gæst ✓</li>
        <li>Søg på gæster under dashboard ✓</li>
        <li>Tilføj database med hoteller ✓</li>
        <li>Tilføj kasse med info, faq, mm</li>
        <li>Tilføj billeder fra ture og indsæt dem i rejseplan</li>
      </ul>
    </div>
    <div class="col">
      <h3>Need to have</h3>
      <ul>
        <li>Tilføj database med hoteller - Navn, link ✓</li>
        <li>Info box nederst og øverst i rejseforslag med logo ✓</li> 
        <li>Inkluderet og ikke inkluderet ✓</li>
        <li>Tilføje specielle inkl. og ikke inkl. ✓</li>
        <li>Tilføj telefonnr, email, (billede) ✓</li>
        <li>Gæst i stedet for kunde ✓</li>
        <li>Pris pr. person for voksne og børn ✓</li>
        <li>Flytilbud i rejseplanen ✓</li>
        <li>Alt på engelsk (semi færdig) ✓</li>
      </ul>
    </div>
    <div class="col">
      <h3>Nice to have</h3>
      <ul>
        <li>Tilføj kasse med info, faq, mm (Mine rejse)</li>
        <li>Dato i programmerne med automatisk udregning af antal dage</li> 
        <li>Accordions på opret og rediger rejseforslag</li>
        <li>Tilføj "lidt om rejsekonsulent"</li>
        <li>Tæller der viser hvor mange gange kunden har set linket</li>
        <li>Session skal udløbe hvis ikke aktiv</li>
        <li>Oversæt til dansk knap</li>
        <li>Offline (ikke vises når man laver opdateringer)</li>
        <li>offline (ikke vises når man laver opdateringer)</li>
        <li>Tilføj database med hoteller</li>
        <li>Tilføj kasse med info, faq, mm</li>
        <li>Tilføj billeder fra ture og indsæt dem i rejseplan</li>
      </ul>
    </div>
  </div>
  
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#searchInput').on('input', function () {
  const searchQuery = $(this).val();
  $('#loader').show();
  $('#noResults').hide();

  $.ajax({
    url: 'search_itineraries.php',
    method: 'POST',
    data: { search: searchQuery },
    success: function (data) {
      $('#loader').hide();
      $('#itineraryTableBody').html(data.trim());

      if (data.trim() === '') {
        $('#noResults').show();
      } else {
        $('#noResults').hide();
      }
    },
    error: function () {
      $('#loader').hide();
      $('#noResults').show().text('Noget gik galt under søgningen.');
    }
  });
});
</script>

<?php include 'includes/footer.php'; ?>