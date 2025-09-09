<?php
require 'includes/db.php';
require 'includes/auth.php';

$user_id = $_SESSION['user_id'];

$result = $conn->prepare("SELECT id, name, email FROM customers WHERE user_id = ?");
$result->bind_param("i", $user_id);
$result->execute();
$customers = $result->get_result();

$tours = $conn->query("SELECT id, title FROM tours_api ORDER BY title ASC");
$tourOptions = '<option value="">-- Select a tour you want to insert --</option>';
while ($t = $tours->fetch_assoc()) {
  $tourOptions .= '<option value="' . $t['id'] . '">' . htmlspecialchars($t['title']) . '</option>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customer_id   = intval($_POST['customer_id']);
  $title         = trim($_POST['title']);
  $itinerary_note = trim($_POST['itinerary_note'] ?? ''); // Handle note field
  $start_date    = $_POST['start_date'] ?? '';
  $flights       = $_POST['flights'] ?? [];
  $price         = intval($_POST['price'] ?? 0);
  $price_child   = intval($_POST['price_child'] ?? 0);
  $num_adults    = intval($_POST['num_adults'] ?? 0);
  $num_children  = intval($_POST['num_children'] ?? 0);
  $included      = $_POST['included'] ?? '';
  $not_included  = $_POST['not_included'] ?? '';

  // Convert start_date to proper format if provided
  $formatted_start_date = null;
  if (!empty($start_date)) {
    $formatted_start_date = date('Y-m-d', strtotime(str_replace('/', '-', $start_date)));
  }

  $token = bin2hex(random_bytes(32));
  $stmt = $conn->prepare("INSERT INTO itineraries 
    (customer_id, title, note, start_date, public_token, flight, price, price_child, num_adults, num_children, included, not_included) 
    VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("issssiiiiss", $customer_id, $title, $itinerary_note, $formatted_start_date, $token, $price, $price_child, $num_adults, $num_children, $included, $not_included);
  $stmt->execute();
  $itinerary_id = $stmt->insert_id;

  // NEW: insert flights separately with full pricing
  foreach ($_POST['flights'] as $flight) {
    $flight_content = trim($flight['content'] ?? '');
    $airline        = trim($flight['airline'] ?? '');
    $num_adults     = intval($flight['num_adults'] ?? 0);
    $price_adult    = floatval($flight['price_adult'] ?? 0);
    $num_children   = intval($flight['num_children'] ?? 0);
    $price_child    = floatval($flight['price_child'] ?? 0);
    $num_toddlers   = intval($flight['num_toddlers'] ?? 0);
    $price_toddler  = floatval($flight['price_toddler'] ?? 0);
    $num_infants    = intval($flight['num_infants'] ?? 0);
    $price_infant   = floatval($flight['price_infant'] ?? 0);

    if (!empty($flight_content) || !empty($airline) || 
        $price_adult > 0 || $price_child > 0 || $price_toddler > 0 || $price_infant > 0) {
      
      $stmt = $conn->prepare("INSERT INTO itinerary_flights 
        (itinerary_id, airline_name, content, num_adults, price_adult, num_children, price_child, 
         num_toddlers, price_toddler, num_infants, price_infant) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $stmt->bind_param("issididididd", $itinerary_id, $airline, $flight_content, 
        $num_adults, $price_adult, $num_children, $price_child,
        $num_toddlers, $price_toddler, $num_infants, $price_infant);
      
      $stmt->execute();
    }
  }

  foreach ($_POST['days'] as $index => $day) {
    $day_range   = trim($day['day_range']);
    $day_title   = trim($day['day_title']);
    $description = trim($day['description']);
    $overnight   = trim($day['overnight']);
    $meals       = isset($day['meals']) ? implode(', ', $day['meals']) : 'Ingen måltider inkluderet';

    $stmt = $conn->prepare("INSERT INTO itinerary_days (itinerary_id, day_range, day_title, description, overnight, meals) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $itinerary_id, $day_range, $day_title, $description, $overnight, $meals);
    $stmt->execute();
    $day_id = $stmt->insert_id;

    $tour_id = intval($day['tour_id'] ?? 0);
    if ($tour_id > 0) {
      $stmt = $conn->prepare("SELECT image1, image2, image3 FROM tours_api WHERE id = ?");
      $stmt->bind_param("i", $tour_id);
      $stmt->execute();
      $tour_images = $stmt->get_result()->fetch_assoc();

      foreach (["image1", "image2", "image3"] as $img_field) {
        $img_path = $tour_images[$img_field];
        if (!empty($img_path)) {
          $stmt = $conn->prepare("INSERT INTO itinerary_day_images (day_id, image_path) VALUES (?, ?)");
          $stmt->bind_param("is", $day_id, $img_path);
          $stmt->execute();
        }
      }
    }
  }

  header("Location: dashboard.php");
  exit;
}
?>

<?php include 'includes/header.php'; ?>
<h2>Create new itinerary</h2>

<!-- Validation Error Summary -->
<div id="validationErrorSummary" class="validation-error-summary">
  <h6>Please fix the following errors:</h6>
  <ul id="errorList"></ul>
</div>

<form method="POST" id="itineraryForm">
  <div class="row">
    <div class="col">
      <button type="submit" class="btn btn-success mt-3 float-end">Save itinerary</button>
    </div>
  </div>
  
  <!-- ROW 1: ITINERARY TITLE & GUEST CONNECTION -->
  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="title" class="form-label">Itinerary title</label>
        <input type="text" name="title" id="title" class="form-control" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="customer_id" class="form-label">Connect a guest to itinerary</label>
        <select name="customer_id" id="customer_id" class="form-select" required>
          <option value="">-- Choose guest here --</option>
          <?php while ($row = $customers->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?> ┃ <?= htmlspecialchars($row['email']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- ROW 2: START DATE & NOTE -->
  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="start_date" class="form-label">Trip start date</label>
        <input type="text" name="start_date" id="start_date" class="form-control" placeholder="Select Date.." value="<?= htmlspecialchars($itinerary['start_date'] ?? '') ?>">
        <small class="text-muted" id="formattedDate"></small>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="itinerary_note" class="form-label">Note</label>
        <input type="text" class="form-control" id="itinerary_note" name="itinerary_note" placeholder="Add any notes...">
      </div>
    </div>
  </div>

  <!-- ROW 3: ADULT PRICING & COUNT -->
  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="price" class="form-label">Price per adult (USD)</label>
        <input type="number" name="price" id="price" class="form-control" placeholder="e.g., 1995">
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="num_adults" class="form-label">Number of adults</label>
        <input type="number" name="num_adults" id="num_adults" class="form-control" value="2">
      </div>
    </div>
  </div>

  <!-- ROW 4: CHILD PRICING & COUNT -->
  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="price_child" class="form-label">Price per child under 12 (USD)</label>
        <input type="number" name="price_child" id="price_child" class="form-control" placeholder="e.g., 995">
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="num_children" class="form-label">Number of children under 12</label>
        <input type="number" name="num_children" id="num_children" class="form-control" value="0">
      </div>
    </div>
  </div>
  
  <div class="mb-3">
    <label for="included" class="form-label">Included in the tour</label>
    <textarea name="included" id="included" class="form-control ck5-editor">
      <ul>
        <li>Alle overnatninger med morgenmad</li>
        <li>Alle transfer til/fra hotel/lufthavne</li>
        <li>Indenrigsfly (max 23 kg - flere kg kan tilkøbes)</li>
        <li>Alle nævnte ture med engelsktalende guide</li>
        <li>Alle ture med privat guide (undtaget Halong Bay)</li>
        <li>Du får et simkort med på rejsen, så du 24/7 kan komme i kontakt med vores danske og vietnamesiske medarbejdere på destinationen.</li>
        <li>Infomøde ved ankomst til Hanoi</li>
        <li>Måltider: B= Breakfast  L= Lunch  D= Dinner</li>
      </ul>
    </textarea>
  </div>

  <div class="mb-3">
    <label for="not_included" class="form-label">Not included in the tour</label>
    <textarea name="not_included" id="not_included" class="form-control ck5-editor">
      <ul>
        <li>Fly til/fra Danmark/Vietnam</li>
        <li>Rejseforsikring</li>
        <li>Drikkepenge</li>
        <li>Andet der ikke er nævnt under "Turen inkluderer"</li>
        <li>Evt tillæg for jul/nytår</li>
      </ul>
    </textarea>
  </div>

  <div id="flightsContainer" class="mb-3">
    <label class="form-label">Flight information</label>
  </div>
  <button type="button" class="btn btn-outline-primary mb-3" id="addFlight">Add Flight</button>

  <hr>
  <h4>Create new program</h4>
  <p>Add a day to day program</p>
  <div id="daysContainer"></div>
  <div class="row">
    <div class="col">
      <button type="button" id="addDay" class="btn btn-outline-primary mt-3">Add new day</button>
      <button type="submit" class="btn btn-success mt-3 float-end">Save itinerary</button>
    </div>
  </div>
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/da.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.3.0/classic/ckeditor.js"></script>

<script>
let dayCounter = 0;
let flightCounter = 0;
let hasUnsavedChanges = false;
let formSubmitted = false;

// VALIDATION FUNCTIONS
function showError(field, message) {
  field.addClass('is-invalid');
  field.siblings('.error-message').remove();
  field.after(`<span class="error-message">${message}</span>`);
}

function clearError(field) {
  field.removeClass('is-invalid');
  field.siblings('.error-message').remove();
}

function clearAllErrors() {
  $('.is-invalid').removeClass('is-invalid');
  $('.error-message').remove();
  $('.day-block').removeClass('has-errors');
  $('#validationErrorSummary').hide();
}

function validateForm() {
  clearAllErrors();
  let errors = [];
  let isValid = true;

  const title = $('#title');
  if (!title.val().trim()) {
    showError(title, 'Itinerary title is required');
    errors.push('Itinerary title is required');
    isValid = false;
  }

  const customerId = $('#customer_id');
  if (!customerId.val()) {
    showError(customerId, 'Please select a guest');
    errors.push('Guest selection is required');
    isValid = false;
  }

  const price = $('#price');
  if (!price.val() || parseInt(price.val()) <= 0) {
    showError(price, 'Price per adult must be greater than 0');
    errors.push('Valid price per adult is required');
    isValid = false;
  }

  const numAdults = $('#num_adults');
  if (!numAdults.val() || parseInt(numAdults.val()) <= 0) {
    showError(numAdults, 'Number of adults must be at least 1');
    errors.push('At least 1 adult is required');
    isValid = false;
  }

  const dayBlocks = $('.day-block');
  if (dayBlocks.length === 0) {
    errors.push('At least one day must be added to the itinerary');
    isValid = false;
  } else {
    dayBlocks.each(function(index) {
      const dayBlock = $(this);
      let dayHasErrors = false;

      const dayRange = dayBlock.find('input[name*="[day_range]"]');
      if (!dayRange.val().trim()) {
        showError(dayRange, 'Day range is required');
        errors.push(`Day ${index + 1}: Day range is required`);
        dayHasErrors = true;
        isValid = false;
      }

      const dayTitle = dayBlock.find('input[name*="[day_title]"]');
      if (!dayTitle.val().trim()) {
        showError(dayTitle, 'Day title is required');
        errors.push(`Day ${index + 1}: Day title is required`);
        dayHasErrors = true;
        isValid = false;
      }

      const tourId = dayBlock.find('.tour-id-input').val();
      const description = dayBlock.find('textarea[name*="[description]"]');
      const hasDescription = description[0] && description[0].ckeditorInstance ? 
        description[0].ckeditorInstance.getData().trim() : 
        description.val().trim();

      if (!tourId && !hasDescription) {
        showError(dayBlock.find('.tour-search-input'), 'Please select a tour or add custom description');
        errors.push(`Day ${index + 1}: Either select a tour or add custom description`);
        dayHasErrors = true;
        isValid = false;
      }

      if (dayHasErrors) {
        dayBlock.addClass('has-errors');
      }
    });
  }

  if (errors.length > 0) {
    const errorList = $('#errorList');
    errorList.empty();
    errors.forEach(error => {
      errorList.append(`<li>${error}</li>`);
    });
    $('#validationErrorSummary').show();
    
    $('html, body').animate({
      scrollTop: $('#validationErrorSummary').offset().top - 20
    }, 500);
  }

  return isValid;
}

function setupRealTimeValidation() {
  $('#title').on('blur', function() {
    const field = $(this);
    if (!field.val().trim()) {
      showError(field, 'Itinerary title is required');
    } else {
      clearError(field);
    }
  });

  $('#customer_id').on('change', function() {
    const field = $(this);
    if (!field.val()) {
      showError(field, 'Please select a guest');
    } else {
      clearError(field);
    }
  });

  $('#price').on('blur', function() {
    const field = $(this);
    if (!field.val() || parseInt(field.val()) <= 0) {
      showError(field, 'Price per adult must be greater than 0');
    } else {
      clearError(field);
    }
  });

  $('#num_adults').on('blur', function() {
    const field = $(this);
    if (!field.val() || parseInt(field.val()) <= 0) {
      showError(field, 'Number of adults must be at least 1');
    } else {
      clearError(field);
    }
  });
}

function setupDayValidation() {
  $(document).off('blur', 'input[name*="[day_range]"]').on('blur', 'input[name*="[day_range]"]', function() {
    const field = $(this);
    if (!field.val().trim()) {
      showError(field, 'Day range is required');
    } else {
      clearError(field);
      field.closest('.day-block').removeClass('has-errors');
    }
  });

  $(document).off('blur', 'input[name*="[day_title]"]').on('blur', 'input[name*="[day_title]"]', function() {
    const field = $(this);
    if (!field.val().trim()) {
      showError(field, 'Day title is required');
    } else {
      clearError(field);
      field.closest('.day-block').removeClass('has-errors');
    }
  });
}

// TOUR SEARCH FUNCTIONS
function createTourSearchDropdown(index) {
  return `
    <div class="tour-search-dropdown">
      <input type="text" 
             class="tour-search-input" 
             placeholder="🔍 Search for a tour..." 
             data-day-index="${index}"
             autocomplete="off">
      <input type="hidden" name="days[${index}][tour_id]" class="tour-id-input">
      <div class="tour-options-list"></div>
    </div>
  `;
}

let allTours = [];
function loadTours() {
  $.getJSON('get_tours.php', function(data) {
    allTours = data;
  });
}

function escapeRegex(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function filterTours(searchTerm, optionsList) {
  const searchWords = searchTerm.toLowerCase().trim().split(/\s+/).filter(word => word.length > 0);
  
  if (searchWords.length === 0) {
    optionsList.hide();
    return;
  }
  
  const filtered = allTours.filter(tour => {
    const titleLower = tour.title.toLowerCase();
    return searchWords.every(word => titleLower.includes(word));
  }).sort((a, b) => {
    const aTitle = a.title.toLowerCase();
    const bTitle = b.title.toLowerCase();
    const searchLower = searchTerm.toLowerCase();
    
    if (aTitle === searchLower && bTitle !== searchLower) return -1;
    if (bTitle === searchLower && aTitle !== searchLower) return 1;
    
    if (aTitle.startsWith(searchLower) && !bTitle.startsWith(searchLower)) return -1;
    if (bTitle.startsWith(searchLower) && !aTitle.startsWith(searchLower)) return 1;
    
    return a.title.length - b.title.length;
  });
  
  optionsList.empty();
  
  if (filtered.length === 0) {
    optionsList.append('<div class="no-results">No tours found for "' + searchTerm + '"</div>');
  } else {
    filtered.forEach(tour => {
      let highlightedTitle = tour.title;
      searchWords.forEach(word => {
        const regex = new RegExp(`(${escapeRegex(word)})`, 'gi');
        highlightedTitle = highlightedTitle.replace(regex, '<mark>$1</mark>');
      });
      
      const option = $(`<div class="tour-option" data-tour-id="${tour.id}">${highlightedTitle}</div>`);
      optionsList.append(option);
    });
    
    if (filtered.length > 0) {
      const statsText = filtered.length === 1 ? '1 tour found' : `${filtered.length} tours found`;
      optionsList.prepend(`<div class="search-stats">${statsText}</div>`);
    }
  }
  
  optionsList.show();
}

// CORE FUNCTIONS - MUST BE DEFINED BEFORE USE
function updateDayNumbers() {
  $('#daysContainer .day-block').each(function(index) {
    const i = index + 1;
    $(this).attr('data-day', i);
    
    // Update h5 based on day_range input or fallback to day number
    const dayRangeInput = $(this).find('input[name*="[day_range]"]');
    const h5Element = $(this).find('h5');
    const currentDayRange = dayRangeInput.val();
    
    if (currentDayRange && currentDayRange.trim()) {
      h5Element.text('Dag ' + currentDayRange.trim());
    } else {
      h5Element.text('Dag ' + i);
    }
    
    $(this).find('input, textarea, select').each(function () {
      const name = $(this).attr('name');
      if (name) {
        const newName = name.replace(/days\[\d+\]/, 'days[' + i + ']');
        $(this).attr('name', newName);
        const id = $(this).attr('id');
        if (id) {
          $(this).attr('id', id.replace(/\d+/, i));
        }
      }
    });
    $(this).find('label[for]').each(function () {
      const f = $(this).attr('for');
      if (f) $(this).attr('for', f.replace(/\d+/, i));
    });
    $(this).find('.tour-search-input').attr('data-day-index', i);
  });
}

function createDayBlock(index) {
  return `
    <div class="day-block border p-3 rounded mb-3 bg-white position-relative" data-day="${index}">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Dag ${index}</h5>
        <div>
          <button type="button" class="btn btn-sm btn-outline-secondary move-up">⬆</button>
          <button type="button" class="btn btn-sm btn-outline-secondary move-down">⬇</button>
        </div>
      </div>
      <div class="mb-3">
        <label>Choose tour to insert</label>
        ${createTourSearchDropdown(index)}
      </div>
      <div class="mb-3">
        <label for="day_range_${index}">Day (ex. 1 or 2-3)</label>
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
      <div class="tour-image-preview d-flex flex-wrap gap-2 mb-3"></div>
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
      <button type="button" class="btn btn-sm btn-outline-danger remove-day mt-2">Remove day</button>
    </div>
  `;
}

function createFlightBlock(index) {
  return `
    <div class="flight-block mb-3 border p-3 rounded bg-light">
      <h6>Flight #${index}</h6>
      <div class="mb-2">
        <label class="form-label">Airline</label>
        <input type="text" name="flights[${index}][airline]" class="form-control" placeholder="e.g., Qatar Airways">
      </div>
      <div class="mb-2">
        <label class="form-label">Flight details</label>
        <textarea name="flights[${index}][content]" class="form-control ck5-editor" placeholder="Flight info"></textarea>
      </div>
      <div class="row">
        <div class="col mb-2">
          <label class="form-label"># Adults</label>
          <input type="number" name="flights[${index}][num_adults]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per adult</label>
          <input type="number" step="0.01" name="flights[${index}][price_adult]" class="form-control">
        </div>
        <div class="col mb-2">
          <label class="form-label"># Children (2–11 yrs)</label>
          <input type="number" name="flights[${index}][num_children]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per child</label>
          <input type="number" step="0.01" name="flights[${index}][price_child]" class="form-control">
        </div>
      </div>
      <div class="row">
        <div class="col mb-2">
          <label class="form-label"># Toddlers (own seat)</label>
          <input type="number" name="flights[${index}][num_toddlers]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per toddler</label>
          <input type="number" step="0.01" name="flights[${index}][price_toddler]" class="form-control">
        </div>
        <div class="col mb-2">
          <label class="form-label"># Infants (on lap)</label>
          <input type="number" name="flights[${index}][num_infants]" class="form-control" value="0">
        </div>
        <div class="col mb-2">
          <label class="form-label">Price per infant</label>
          <input type="number" step="0.01" name="flights[${index}][price_infant]" class="form-control">
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-danger remove-flight mt-2">Remove Flight</button>
    </div>
  `;
}

function initEditor(textarea) {
  if (textarea && !textarea.ckeditorInstance) {
    ClassicEditor.create(textarea, {
      toolbar: {
        items: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo']
      }
    }).then(editor => {
      textarea.ckeditorInstance = editor;
    }).catch(error => console.error(error));
  }
}

// TRACK FORM CHANGES FOR UNSAVED WARNING
function trackFormChanges() {
  $(document).on('input change', '#itineraryForm input, #itineraryForm select, #itineraryForm textarea', function() {
    if (!formSubmitted) {
      hasUnsavedChanges = true;
    }
  });
  
  $(document).on('click', '#addDay, .remove-day, .move-up, .move-down, #addFlight, .remove-flight', function() {
    if (!formSubmitted) {
      hasUnsavedChanges = true;
    }
  });
}

// INITIALIZE EVERYTHING
document.addEventListener('DOMContentLoaded', () => {
  loadTours();
  setupRealTimeValidation();
  trackFormChanges();
  
  ['#included', '#not_included'].forEach(selector => {
    const el = document.querySelector(selector);
    if (el) {
      ClassicEditor.create(el, {
        toolbar: {
          items: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo']
        }
      }).then(editor => {
        el.ckeditorInstance = editor;
      }).catch(error => console.error(error));
    }
  });
});

// TOUR SEARCH EVENT HANDLERS
$(document).on('input', '.tour-search-input', function() {
  const searchTerm = $(this).val().trim();
  const optionsList = $(this).siblings('.tour-options-list');
  
  if (searchTerm.length >= 1) {
    filterTours(searchTerm, optionsList);
  } else {
    optionsList.hide();
  }
});

$(document).on('keydown', '.tour-search-input', function(e) {
  const optionsList = $(this).siblings('.tour-options-list');
  const options = optionsList.find('.tour-option');
  const current = options.filter('.highlighted');
  
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    if (current.length === 0) {
      options.first().addClass('highlighted');
    } else {
      current.removeClass('highlighted');
      const next = current.next('.tour-option');
      if (next.length > 0) {
        next.addClass('highlighted');
      } else {
        options.first().addClass('highlighted');
      }
    }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    if (current.length === 0) {
      options.last().addClass('highlighted');
    } else {
      current.removeClass('highlighted');
      const prev = current.prev('.tour-option');
      if (prev.length > 0) {
        prev.addClass('highlighted');
      } else {
        options.last().addClass('highlighted');
      }
    }
  } else if (e.key === 'Enter') {
    e.preventDefault();
    if (current.length > 0) {
      current.click();
    }
  } else if (e.key === 'Escape') {
    optionsList.hide();
    $(this).blur();
  }
});

$(document).on('click', '.tour-option', function() {
  const tourId = $(this).data('tour-id');
  const tourTitle = $(this).text();
  const dropdown = $(this).closest('.tour-search-dropdown');
  const searchInput = dropdown.find('.tour-search-input');
  const hiddenInput = dropdown.find('.tour-id-input');
  const optionsList = dropdown.find('.tour-options-list');
  const block = dropdown.closest('.day-block');
  
  searchInput.val(tourTitle);
  hiddenInput.val(tourId);
  optionsList.hide();
  optionsList.find('.tour-option').removeClass('highlighted');
  
  if (tourId) {
    $.getJSON('load_tour.php', { id: tourId }, function (data) {
      if (data && data.title && data.description) {
        // Set title immediately
        block.find('input[name$="[day_title]"]').val(data.title);
        
        // Handle CKEditor with proper timing
        const editorElement = block.find('.ck5-editor')[0];
        
        function setEditorContent() {
          if (editorElement && editorElement.ckeditorInstance) {
            editorElement.ckeditorInstance.setData(data.description);
          } else {
            // If editor not ready, try again in 100ms
            setTimeout(setEditorContent, 100);
          }
        }
        
        setEditorContent();

        // Handle images
        const preview = block.find('.tour-image-preview');
        preview.empty();
        ['image1', 'image2', 'image3', 'image4'].forEach(key => {
          if (data[key]) {
            const img = $('<img>').attr('src', data[key])
              .addClass('img-thumbnail rounded border')
              .css({ width: '140px', height: 'auto' });
            preview.append(img);
          }
        });
      }
    }).fail(function() {
      console.error('Failed to load tour data for ID:', tourId);
    });
  }
});

$(document).on('mouseenter', '.tour-option', function() {
  $(this).siblings().removeClass('highlighted');
  $(this).addClass('highlighted');
});

$(document).on('mouseleave', '.tour-options-list', function() {
  $(this).find('.tour-option').removeClass('highlighted');
});

$(document).on('click', function(e) {
  if (!$(e.target).closest('.tour-search-dropdown').length) {
    $('.tour-options-list').hide();
  }
});

$(document).on('focus', '.tour-search-input', function() {
  const dropdown = $(this).closest('.tour-search-dropdown');
  const hiddenInput = dropdown.find('.tour-id-input');
  
  if ($(this).val() && hiddenInput.val()) {
    $(this).select();
  }
});

// DAY BLOCK EVENT HANDLERS
$('#addDay').on('click', function () {
  dayCounter++;
  $('#daysContainer').append(createDayBlock(dayCounter));
  updateDayNumbers();
  setupDayValidation();
  setTimeout(() => {
    const block = document.querySelector(`#daysContainer .day-block[data-day="${dayCounter}"]`);
    if (block) {
      block.querySelectorAll('.ck5-editor').forEach(el => initEditor(el));
    }
  }, 0);
});

$('#daysContainer').on('click', '.remove-day', function () {
  $(this).closest('.day-block').remove();
  updateDayNumbers();
});

$('#daysContainer').on('click', '.move-up', function () {
  const block = $(this).closest('.day-block');
  const prev = block.prev('.day-block');
  
  if (prev.length) {
    block.addClass('moving');
    prev.addClass('move-target');
    
    setTimeout(() => {
      block.addClass('slide-up');
      prev.addClass('slide-down');
      
      block.insertBefore(prev);
      updateDayNumbers();
      
      setTimeout(() => {
        block.removeClass('moving slide-up').addClass('moved-success');
        prev.removeClass('move-target slide-down');
        
        setTimeout(() => {
          block.removeClass('moved-success');
        }, 600);
      }, 200);
    }, 100);
  } else {
    block.addClass('moving');
    setTimeout(() => {
      block.removeClass('moving');
    }, 300);
  }
});

$('#daysContainer').on('click', '.move-down', function () {
  const block = $(this).closest('.day-block');
  const next = block.next('.day-block');
  
  if (next.length) {
    block.addClass('moving');
    next.addClass('move-target');
    
    setTimeout(() => {
      block.addClass('slide-down');
      next.addClass('slide-up');
      
      block.insertAfter(next);
      updateDayNumbers();
      
      setTimeout(() => {
        block.removeClass('moving slide-down').addClass('moved-success');
        next.removeClass('move-target slide-up');
        
        setTimeout(() => {
          block.removeClass('moved-success');
        }, 600);
      }, 200);
    }, 100);
  } else {
    block.addClass('moving');
    setTimeout(() => {
      block.removeClass('moving');
    }, 300);
  }
});

// FLIGHT BLOCK EVENT HANDLERS
$('#addFlight').on('click', function () {
  const currentCount = $('#flightsContainer .flight-block').length;
  if (currentCount >= 3) return;

  flightCounter++;
  const block = $(createFlightBlock(flightCounter)).appendTo('#flightsContainer');
  const textarea = block.find('textarea')[0];
  ClassicEditor.create(textarea).then(editor => {
    textarea.ckeditorInstance = editor;
  });
});

$('#flightsContainer').on('click', '.remove-flight', function () {
  $(this).closest('.flight-block').remove();
});

// FORM SUBMISSION
document.querySelector('form').addEventListener('submit', function (e) {
  document.querySelectorAll('.ck5-editor').forEach(el => {
    if (el.ckeditorInstance) {
      el.value = el.ckeditorInstance.getData();
    }
  });
  
  if (!validateForm()) {
    e.preventDefault();
    return false;
  }
  
  formSubmitted = true;
  hasUnsavedChanges = false;
});

// DATEPICKER
flatpickr("#start_date", {
  dateFormat: "d/m/Y",
  locale: "da",
  allowInput: true,
  defaultDate: "<?= $itinerary['start_date'] ?? '' ?>",
});

// STICKY SAVE BUTTON
$(document).ready(function() {
  const stickyBtn = $('<button type="submit" class="btn btn-success sticky-save-btn" form="itineraryForm">Save Itinerary</button>');
  $('body').append(stickyBtn);
  
  $(window).scroll(function() {
    const originalBtn = $('form#itineraryForm .row:first .col button[type="submit"]');
    
    if (originalBtn.length > 0) {
      const originalBtnOffset = originalBtn.offset().top;
      const scrollTop = $(window).scrollTop();
      
      if (scrollTop > originalBtnOffset + originalBtn.outerHeight()) {
        stickyBtn.addClass('show');
      } else {
        stickyBtn.removeClass('show');
      }
    }
  });
});

// UNSAVED CHANGES WARNING
window.addEventListener('beforeunload', function(e) {
  if (hasUnsavedChanges && !formSubmitted) {
    const message = 'You have unsaved changes. Are you sure you want to leave?';
    e.preventDefault();
    e.returnValue = message;
    return message;
  }
});

window.addEventListener('popstate', function(e) {
  if (hasUnsavedChanges && !formSubmitted) {
    const confirmLeave = confirm('You have unsaved changes. Are you sure you want to leave this page?');
    if (!confirmLeave) {
      history.pushState(null, null, window.location.href);
    } else {
      hasUnsavedChanges = false;
    }
  }
});

$(document).ready(function() {
  history.pushState(null, null, window.location.href);
  hasUnsavedChanges = false;
});

// Live update day titles based on day_range input
$(document).on('input', 'input[name*="[day_range]"]', function() {
  const dayRange = $(this).val().trim();
  const dayBlock = $(this).closest('.day-block');
  const h5Element = dayBlock.find('h5');
  
  if (dayRange) {
    h5Element.text('Dag ' + dayRange);
  } else {
    // Fallback to the day number if field is empty
    const dayNumber = dayBlock.attr('data-day');
    h5Element.text('Dag ' + dayNumber);
  }
});

// Also update on blur to handle any edge cases
$(document).on('blur', 'input[name*="[day_range]"]', function() {
  const dayRange = $(this).val().trim();
  const dayBlock = $(this).closest('.day-block');
  const h5Element = dayBlock.find('h5');
  
  if (dayRange) {
    h5Element.text('Dag ' + dayRange);
  } else {
    // Fallback to the day number if field is empty
    const dayNumber = dayBlock.attr('data-day');
    h5Element.text('Dag ' + dayNumber);
  }
});
</script>

<?php include 'includes/footer.php'; ?>