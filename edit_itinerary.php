<?php
require 'includes/db.php';
require 'includes/auth.php';

$itinerary_id = intval($_GET['id'] ?? 0);
if (!$itinerary_id) {
  die("Itinerary ID mangler.");
}

$user_id = $_SESSION['user_id'];

// Fetch customers
$result = $conn->prepare("SELECT id, name, email FROM customers WHERE user_id = ?");
$result->bind_param("i", $user_id);
$result->execute();
$customers = $result->get_result();

// Fetch tours
$tours = $conn->query("SELECT id, title FROM tours ORDER BY title ASC");
$tourOptions = '<option value="">-- Ingen --</option>';
while ($t = $tours->fetch_assoc()) {
  $tourOptions .= '<option value="' . $t['id'] . '">' . htmlspecialchars($t['title']) . '</option>';
}

// Fetch flights
$stmt = $conn->prepare("SELECT * FROM itinerary_flights WHERE itinerary_id = ?");
$stmt->bind_param("i", $itinerary_id);
$stmt->execute();
$flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get itinerary
$stmt = $conn->prepare("SELECT * FROM itineraries WHERE id = ?");
$stmt->bind_param("i", $itinerary_id);
$stmt->execute();
$itinerary = $stmt->get_result()->fetch_assoc();
if (!$itinerary) {
  die("Itinerary ikke fundet.");
}

// Get itinerary days
$days = [];
$stmt = $conn->prepare("SELECT * FROM itinerary_days WHERE itinerary_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->bind_param("i", $itinerary_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $row['images'] = [];
  $stmt_img = $conn->prepare("SELECT * FROM itinerary_day_images WHERE day_id = ?");
  $stmt_img->bind_param("i", $row['id']);
  $stmt_img->execute();
  $img_res = $stmt_img->get_result();
  while ($img = $img_res->fetch_assoc()) {
    $row['images'][] = $img['image_path'];
  }
  $days[] = $row;
}
?>

<?php include 'includes/header.php'; ?>
<?php if (isset($_GET['saved']) && $_GET['saved'] == 1): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    ✅ Itinerary has been saved successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<h2>Edit itinerary</h2>
<form method="POST" action="save_itinerary.php" enctype="multipart/form-data">
  <input type="hidden" name="itinerary_id" value="<?= $itinerary_id ?>">
  <div class="row">
    <div class="col">
      <button type="submit" class="btn btn-success mt-3 float-end">💾 Save itinerary</button>
    </div>    
  </div>
  <div class="row">
    <div class="mb-3">
      <label for="title" class="form-label">Itinerary title</label>
      <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($itinerary['title']) ?>" required>
    </div>
    <div class="col">
      <div class="mb-3">
        <label for="customer_id" class="form-label">Connect a guest to itinerary</label>
        <select name="customer_id" id="customer_id" class="form-select" required>
          <option value="">-- Choose guest here --</option>
          <?php while ($row = $customers->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>" <?= $row['id'] == $itinerary['customer_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($row['name']) ?> ┃ <?= htmlspecialchars($row['email']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>      
      <div class="mb-3">
        <label for="price" class="form-label">Price per adult (USD)</label>
        <input type="number" name="price" id="price" class="form-control" value="<?= $itinerary['price'] ?>" required>
      </div>
      <div class="mb-3">
        <label for="num_adults" class="form-label">Number of adults</label>
        <input type="number" name="num_adults" id="num_adults" class="form-control" value="<?= $itinerary['num_adults'] ?? '' ?>">
      </div>      
    </div>
    <div class="col">
      <div class="mb-3">
        <label for="start_date" class="form-label">Trip start date</label>
        <?php
          $startDateValue = '';
          if (!empty($itinerary['start_date'])) {
            $dateObj = DateTime::createFromFormat('Y-m-d', $itinerary['start_date']);
            if ($dateObj) {
              $startDateValue = $dateObj->format('d/m/Y'); // for Flatpickr display
            }
          }
        ?>
        <input type="text" name="start_date" id="start_date" class="form-control" placeholder="Select Date.." value="<?= $startDateValue ?>">
      </div>
      <div class="mb-3">
        <label for="price_child" class="form-label">Price per child under 12 (USD)</label>
        <input type="number" name="price_child" id="price_child" class="form-control" value="<?= $itinerary['price_child'] ?? '' ?>">
      </div>
      <div class="mb-3">
        <label for="num_children" class="form-label">Number of children under 12</label>
        <input type="number" name="num_children" id="num_children" class="form-control" value="<?= $itinerary['num_children'] ?? '' ?>">
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <div class="mb-3">
        <label for="included" class="form-label">Included in the tour</label>
        <textarea name="included" id="included" class="form-control ck5-editor" rows="6"><?= htmlspecialchars($itinerary['included']) ?></textarea>
      </div>
    </div>
    <div class="col">
      <div class="mb-3">
        <label for="not_included" class="form-label">Not included in the tour</label>
        <textarea name="not_included" id="not_included" class="form-control ck5-editor" rows="6"><?= htmlspecialchars($itinerary['not_included']) ?></textarea>
      </div>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Flight information</label>
    <div id="flightsContainer">
      <?php $f = 0; foreach ($flights as $flight): ?>
        <div class="flight-block mb-3 border p-3 rounded bg-light">
          <input type="hidden" name="flights[<?= $f ?>][existing_id]" value="<?= $flight['id'] ?>">
          <h6>Flight #<?= $f + 1 ?></h6>
          <div class="mb-2">
            <label class="form-label">Airline</label>
            <input type="text" name="flights[<?= $f ?>][airline]" class="form-control" value="<?= htmlspecialchars($flight['airline_name']) ?>">
          </div>
          <div class="row">
            <div class="col mb-2">
              <label class="form-label"># Adults</label>
              <input type="number" name="flights[<?= $f ?>][num_adults]" class="form-control" value="<?= $flight['num_adults'] ?? 0 ?>">
            </div>
            <div class="col mb-2">
              <label class="form-label">Price per adult</label>
              <input type="number" step="0.01" name="flights[<?= $f ?>][price_adult]" class="form-control" value="<?= $flight['price_adult'] ?? 0 ?>">
            </div>
            <div class="col mb-2">
              <label class="form-label"># Children</label>
              <input type="number" name="flights[<?= $f ?>][num_children]" class="form-control" value="<?= $flight['num_children'] ?? 0 ?>">
            </div>
            <div class="col mb-2">
              <label class="form-label">Price per child</label>
              <input type="number" step="0.01" name="flights[<?= $f ?>][price_child]" class="form-control" value="<?= $flight['price_child'] ?? 0 ?>">
            </div>
          </div>
          <div class="row">
            <div class="col mb-2">
              <label class="form-label"># Toddlers (own seat)</label>
              <input type="number" name="flights[<?= $f ?>][num_toddlers]" class="form-control" value="<?= $flight['num_toddlers'] ?? 0 ?>">
            </div>
            <div class="col mb-2">
              <label class="form-label">Price per toddler</label>
              <input type="number" step="0.01" name="flights[<?= $f ?>][price_toddler]" class="form-control" value="<?= $flight['price_toddler'] ?? 0 ?>">
            </div>
            <div class="col mb-2">
              <label class="form-label"># Infants (on lap)</label>
              <input type="number" name="flights[<?= $f ?>][num_infants]" class="form-control" value="<?= $flight['num_infants'] ?? 0 ?>">
            </div>
            <div class="col mb-2">
              <label class="form-label">Price per infant</label>
              <input type="number" step="0.01" name="flights[<?= $f ?>][price_infant]" class="form-control" value="<?= $flight['price_infant'] ?? 0 ?>">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label">Flight details</label>
            <textarea name="flights[<?= $f ?>][content]" class="form-control ck5-editor"><?= htmlspecialchars($flight['content']) ?></textarea>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger remove-flight">Remove Flight</button>
        </div>
      <?php $f++; endforeach; ?>
    </div>
    <button type="button" class="btn btn-outline-primary mt-2" id="addFlight">➕ Add Flight</button>
  </div>

  <hr>
  <h4>Edit program</h4>
  <div id="daysContainer">
    <!-- Existing days are loaded via PHP logic -->
    <?php $i = 1; foreach ($days as $day): ?>
      <div class="day-block border p-3 rounded mb-3 bg-white position-relative" data-day="<?= $i ?>">
        <input type="hidden" name="days[<?= $i ?>][existing_id]" value="<?= $day['id'] ?>">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5>Dag <?= $i ?></h5>
          <div>
            <button type="button" class="btn btn-sm btn-outline-secondary move-up">⯅</button>
            <button type="button" class="btn btn-sm btn-outline-secondary move-down">⯆</button>
            <button type="button" class="btn btn-sm btn-danger remove-day">Remove day</button>
          </div>
        </div>
        <div class="mb-3">
          <label for="day_range_<?= $i ?>">Day (e.g., 1 or 2-3)</label>
          <input type="text" name="days[<?= $i ?>][day_range]" id="day_range_<?= $i ?>" value="<?= htmlspecialchars($day['day_range']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="day_title_<?= $i ?>">Day title</label>
          <input type="text" name="days[<?= $i ?>][day_title]" id="day_title_<?= $i ?>" value="<?= htmlspecialchars($day['day_title']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="description_<?= $i ?>">Day description</label>
          <textarea name="days[<?= $i ?>][description]" id="description_<?= $i ?>" class="form-control ck5-editor"><?= htmlspecialchars($day['description']) ?></textarea>
        </div>
        <div class="mb-3 daytour-overnight">
          <label for="overnight_<?= $i ?>">Hotel/overnight</label>
          <textarea name="days[<?= $i ?>][overnight]" id="overnight_<?= $i ?>" class="form-control ck5-editor"><?= htmlspecialchars($day['overnight']) ?></textarea>
        </div>
        <div class="tour-image-preview d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($day['images'] as $img): ?>
            <img src="<?= htmlspecialchars($img) ?>" class="img-thumbnail rounded border" style="width:140px;height:auto;">
          <?php endforeach; ?>
        </div>
        <div class="mb-3">
          <label for="day_images_<?= $i ?>">Replace images (max 3)</label>
          <input type="file" name="day_images_<?= $i ?>[]" id="day_images_<?= $i ?>" class="form-control" accept="image/*" multiple>
        </div>
        <label>Meals:</label>
        <?php
          $meals = explode(',', $day['meals']);
          $meals = array_map('trim', $meals);
        ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="days[<?= $i ?>][meals][]" value="Breakfast" <?= in_array('Breakfast', $meals) ? 'checked' : '' ?>>
          <label class="form-check-label">Breakfast</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="days[<?= $i ?>][meals][]" value="Lunch" <?= in_array('Lunch', $meals) ? 'checked' : '' ?>>
          <label class="form-check-label">Lunch</label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="days[<?= $i ?>][meals][]" value="Dinner" <?= in_array('Dinner', $meals) ? 'checked' : '' ?>>
          <label class="form-check-label">Dinner</label>
        </div>
      </div>
    <?php $i++; endforeach; ?>
  </div>

  <div class="row">
    <div class="col">
      <button type="button" id="addDay" class="btn btn-outline-primary mt-3">➕ Add new day</button>
      <button type="submit" class="btn btn-success mt-3 float-end">💾 Save itinerary</button>
    </div>
  </div>
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/da.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.3.0/classic/ckeditor.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.ck5-editor').forEach(el => {
    if (!el.ckeditorInstance) {
      ClassicEditor
        .create(el, {
          toolbar: {
            items: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo']
          }
        })
        .then(editor => {
          el.ckeditorInstance = editor;

          if (el.id === 'flight') {
            editor.editing.view.change(writer => {
              const viewRoot = editor.editing.view.document.getRoot();
              writer.setStyle('max-height', '400px', viewRoot);
              writer.setStyle('min-height', '300px', viewRoot);
              writer.setStyle('overflow-y', 'auto', viewRoot);
            });
          }
        })
        .catch(error => console.error('CKEditor error:', error));
    }
  });
});

let flightCounter = <?= count($flights) ?>;

function createFlightBlock(index) {
  return `
    <div class="flight-block mb-3 border p-3 rounded bg-light">
      <h6>Flight #${index + 1}</h6>
      <div class="mb-2">
        <label class="form-label">Airline</label>
        <input type="text" name="flights[${index}][airline]" class="form-control" placeholder="e.g., Qatar Airways">
      </div>

      <div class="row">
        <div class="col mb-2">
          <label class="form-label"># Adults</label>
          <input type="number" name="flights[${index}][num_adults]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per adult</label>
          <input type="number" step="0.01" name="flights[${index}][price_adult]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label"># Children</label>
          <input type="number" name="flights[${index}][num_children]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per child</label>
          <input type="number" step="0.01" name="flights[${index}][price_child]" class="form-control" value="0">
        </div>
      </div>

      <div class="row">
        <div class="col mb-2">
          <label class="form-label"># Toddlers (own seat)</label>
          <input type="number" name="flights[${index}][num_toddlers]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per toddler</label>
          <input type="number" step="0.01" name="flights[${index}][price_toddler]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label"># Infants (on lap)</label>
          <input type="number" name="flights[${index}][num_infants]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per infant</label>
          <input type="number" step="0.01" name="flights[${index}][price_infant]" class="form-control" value="0">
        </div>
      </div>

      <div class="mb-2">
        <label class="form-label">Flight details</label>
        <textarea name="flights[${index}][content]" class="form-control ck5-editor" placeholder="Flight info"></textarea>
      </div>
      <button type="button" class="btn btn-sm btn-outline-danger remove-flight">Remove Flight</button>
    </div>
  `;
}

$('#addFlight').on('click', function () {
  if (flightCounter >= 3) return;
  const block = $(createFlightBlock(flightCounter)).appendTo('#flightsContainer');
  const textarea = block.find('textarea')[0];
  ClassicEditor.create(textarea).then(editor => {
    textarea.ckeditorInstance = editor;
  });
  flightCounter++;
});

$('#flightsContainer').on('click', '.remove-flight', function () {
  $(this).closest('.flight-block').remove();
  updateFlightNames(); // optional function to reindex names if you want
});

let dayCounter = <?= $i - 1 ?>; // Continue from last PHP-generated day

function createDayBlock(index) {
  return `
    <div class="day-block border p-3 rounded mb-3 bg-white position-relative" data-day="${index}">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Dag ${index}</h5>
        <div>
          <button type="button" class="btn btn-sm btn-outline-secondary move-up">⯅</button>
          <button type="button" class="btn btn-sm btn-outline-secondary move-down">⯆</button>
          <button type="button" class="btn btn-sm btn-danger remove-day">Remove day</button>
        </div>
      </div>
      <div class="mb-3">
        <label for="day_range_${index}">Day (e.g., 1 or 2-3)</label>
        <input type="text" name="days[${index}][day_range]" id="day_range_${index}" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="day_title_${index}">Day title</label>
        <input type="text" name="days[${index}][day_title]" id="day_title_${index}" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="description_${index}">Day description</label>
        <textarea name="days[${index}][description]" id="description_${index}" class="form-control ck5-editor"></textarea>
      </div>
      <div class="mb-3 daytour-overnight">
        <label for="overnight_${index}">Hotel/overnight</label>
        <textarea name="days[${index}][overnight]" id="overnight_${index}" class="form-control ck5-editor"></textarea>
      </div>
      <div class="mb-3">
        <label for="day_images_${index}">Upload images (max 3)</label>
        <input type="file" name="day_images_${index}[]" id="day_images_${index}" class="form-control" accept="image/*" multiple>
      </div>
      <label>Meals:</label>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="days[${index}][meals][]" value="Breakfast" id="breakfast_${index}">
        <label class="form-check-label" for="breakfast_${index}">Breakfast</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="days[${index}][meals][]" value="Lunch" id="lunch_${index}">
        <label class="form-check-label" for="lunch_${index}">Lunch</label>
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="days[${index}][meals][]" value="Dinner" id="dinner_${index}">
        <label class="form-check-label" for="dinner_${index}">Dinner</label>
      </div>
    </div>
  `;
}

$('#addDay').on('click', function () {
  dayCounter++;
  const newDay = createDayBlock(dayCounter);
  $('#daysContainer').append(newDay);

  // Re-initialize CKEditor for new textareas
  setTimeout(() => {
    $(`#daysContainer [id^=description_${dayCounter}], #daysContainer [id^=overnight_${dayCounter}]`).each(function () {
      ClassicEditor.create(this).then(editor => {
        this.ckeditorInstance = editor;
      }).catch(error => console.error(error));
    });
  }, 50);
});

// Optional: allow moving days up/down
$('#daysContainer').on('click', '.move-up', function () {
  const block = $(this).closest('.day-block');
  const prev = block.prev('.day-block');
  if (prev.length) block.insertBefore(prev);
});

$('#daysContainer').on('click', '.move-down', function () {
  const block = $(this).closest('.day-block');
  const next = block.next('.day-block');
  if (next.length) block.insertAfter(next);
});

$('#daysContainer').on('click', '.remove-day', function () {
  $(this).closest('.day-block').remove();
});

// Change datepicker format
flatpickr("#start_date", {
  dateFormat: "d/m/Y", // or "d.m.Y" or "Y-m-d"
  allowInput: true,
  defaultDate: "<?= $itinerary['start_date'] ?? '' ?>",
});
</script>

<?php include 'includes/footer.php'; ?>
