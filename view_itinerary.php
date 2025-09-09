<?php
require 'includes/db.php';

// ============================================================================
// 🔐 AUTHENTICATION & AUTHORIZATION
// ============================================================================
$token = $_GET['token'] ?? null;

if (!$token) {
    require 'includes/auth.php';
    $user_id = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    $itinerary_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
} else {
    $user_id = null;
    $role = null;
    $itinerary_id = 0;
}

// ============================================================================
// 📊 DATA LOADING FUNCTIONS
// ============================================================================
function loadItinerary($conn, $token, $role, $itinerary_id, $user_id) {
    if ($token) {
        $stmt = $conn->prepare("SELECT i.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.notes, c.travel_number, u.name AS agent_name, u.email AS agent_email, u.photo AS agent_photo FROM itineraries i JOIN customers c ON c.id = i.customer_id JOIN users u ON c.user_id = u.id WHERE i.public_token = ?");
        $stmt->bind_param("s", $token);
    } elseif ($role === 'customer') {
        $stmt = $conn->prepare("SELECT i.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.notes, c.travel_number, u.name AS agent_name, u.email AS agent_email, u.photo AS agent_photo FROM itineraries i JOIN customers c ON c.id = i.customer_id JOIN users u ON c.user_id = u.id WHERE i.id = ? AND c.id = ?");
        $stmt->bind_param("ii", $itinerary_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT i.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.notes, c.travel_number, u.name AS agent_name, u.email AS agent_email, u.photo AS agent_photo FROM itineraries i JOIN customers c ON c.id = i.customer_id JOIN users u ON c.user_id = u.id WHERE i.id = ?");
        $stmt->bind_param("i", $itinerary_id);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function loadFlights($conn, $itinerary_id) {
    $stmt = $conn->prepare("SELECT airline_name, content, price_adult, price_child, price_toddler, price_infant, num_adults, num_children, num_toddlers, num_infants FROM itinerary_flights WHERE itinerary_id = ?");
    $stmt->bind_param("i", $itinerary_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function loadDays($conn, $itinerary_id) {
    $stmt = $conn->prepare("SELECT * FROM itinerary_days WHERE itinerary_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param("i", $itinerary_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function loadImagesByDay($conn, $days) {
    $imagesByDay = [];
    $stmt = $conn->prepare("SELECT image_path FROM itinerary_day_images WHERE day_id = ?");
    foreach ($days as $day) {
        $stmt->bind_param("i", $day['id']);
        $stmt->execute();
        $imagesByDay[$day['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return $imagesByDay;
}

// ============================================================================
// 💰 PRICING CALCULATION FUNCTIONS
// ============================================================================
function getExchangeRate() {
    $usd_to_dkk = 7.00; // fallback
    $response = @file_get_contents('https://api.frankfurter.app/latest?from=USD&to=DKK');
    if ($response !== false) {
        $data = json_decode($response, true);
        if (!empty($data['rates']['DKK'])) {
            $usd_to_dkk = floor($data['rates']['DKK'] * 100) / 100;
        }
    }
    return $usd_to_dkk;
}

function calculatePricing($itinerary, $flights) {
    // Trip details
    $adults = (int) ($itinerary['num_adults'] ?? 0);
    $children = (int) ($itinerary['num_children'] ?? 0);
    $price_adult = (int) ($itinerary['price'] ?? 0);
    $price_child = (int) ($itinerary['price_child'] ?? 0);
    $total_people = $adults + $children;
    $trip_total_usd = ($adults * $price_adult) + ($children * $price_child);

    // Flight pricing
    $flight = $flights[0] ?? null;
    $flight_total_dkk = 0;
    $flight_breakdown = [];
    
    if ($flight) {
        $amounts = [
            'adult' => (int) ($flight['num_adults'] ?? 0),
            'child' => (int) ($flight['num_children'] ?? 0),
            'toddler' => (int) ($flight['num_toddlers'] ?? 0),
            'infant' => (int) ($flight['num_infants'] ?? 0),
        ];
        $prices = [
            'adult' => (float) ($flight['price_adult'] ?? 0),
            'child' => (float) ($flight['price_child'] ?? 0),
            'toddler' => (float) ($flight['price_toddler'] ?? 0),
            'infant' => (float) ($flight['price_infant'] ?? 0),
        ];
        
        foreach ($amounts as $type => $qty) {
            $line = $qty * $prices[$type];
            $flight_total_dkk += $line;
            if ($qty > 0 && $prices[$type] > 0) {
                $label = match($type) {
                    'adult' => 'Voksne',
                    'child' => 'Børn (2–11 år)',
                    'toddler' => 'Barn (eget sæde)',
                    'infant' => 'Barn (på skød)',
                };
                $flight_breakdown[] = "<div class='row'><div class='col-6'><p><strong>{$label}:</strong> {$qty} x ".number_format($prices[$type], 0, ',', '.')." DKK</p></div><div class='col-6'><span>= </span><p style='float:right;'>".number_format($line, 0, ',', '.')." DKK</p></div></div>";
            }
        }
    }

    // Currency conversion with margin
    $usd_to_dkk = getExchangeRate();
    $margin_dkk_total = $total_people * 200;
    $margin_per_usd = ($trip_total_usd > 0) ? ($margin_dkk_total / $trip_total_usd) : 0;
    $adjusted_usd_to_dkk = $usd_to_dkk + $margin_per_usd;
    $trip_total_dkk = $trip_total_usd * $adjusted_usd_to_dkk;
    $total_combined_dkk = $trip_total_dkk + $flight_total_dkk;

    return compact('adults', 'children', 'price_adult', 'price_child', 'total_people', 'trip_total_usd', 'flight_total_dkk', 'flight_breakdown', 'adjusted_usd_to_dkk', 'trip_total_dkk', 'total_combined_dkk');
}

// ============================================================================
// 📅 DATE FORMATTING FUNCTIONS
// ============================================================================
function getDanishMonths() {
    return [
        1 => 'januar', 2 => 'februar', 3 => 'marts', 4 => 'april',
        5 => 'maj', 6 => 'juni', 7 => 'juli', 8 => 'august',
        9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'
    ];
}

function formatDateRange($startDateObj, $dayRange) {
    if (!$startDateObj) return '';
    
    $danishMonths = getDanishMonths();
    
    if (strpos($dayRange, '-') !== false) {
        list($startNum, $endNum) = array_map('intval', explode('-', $dayRange));
    } else {
        $startNum = $endNum = intval($dayRange);
    }
    
    $dayStartObj = clone $startDateObj;
    $dayStartObj->modify('+' . ($startNum - 1) . ' days');
    $dayStart = $dayStartObj->format('j') . '. ' . $danishMonths[(int)$dayStartObj->format('n')] . ' ' . $dayStartObj->format('Y');
    
    if ($startNum != $endNum) {
        $dayEndObj = clone $startDateObj;
        $dayEndObj->modify('+' . ($endNum - 1) . ' days');
        $dayEnd = $dayEndObj->format('j') . '. ' . $danishMonths[(int)$dayEndObj->format('n')] . ' ' . $dayEndObj->format('Y');
        return "$dayStart – $dayEnd";
    } else {
        return $dayStart;
    }
}

function formatMeals($meals) {
    $mealMap = [
        'Breakfast' => 'Morgenmad',
        'Lunch' => 'Frokost',
        'Dinner' => 'Aftensmad'
    ];
    $mealArray = array_filter(array_map('trim', explode(',', $meals ?? '')));
    
    if (!empty($mealArray)) {
        $translatedMeals = array_map(function ($m) use ($mealMap) {
            return $mealMap[$m] ?? $m;
        }, $mealArray);
        return "<p><strong class='strongH3'>Måltider:</strong><br> " . implode(', ', $translatedMeals) . "</p>";
    } else {
        return "<p><strong class='strongH3'>Måltider:</strong></p><br><p>Ingen måltider inkluderet</p>";
    }
}

// ============================================================================
// 🔄 LOAD DATA
// ============================================================================
$itinerary = loadItinerary($conn, $token, $role, $itinerary_id, $user_id);
if (!$itinerary) {
    http_response_code(404);
    exit("Rejseplan ikke fundet eller adgang nægtet.");
}

$itinerary_id = (int)$itinerary['id'];
$flights = loadFlights($conn, $itinerary_id);
$days = loadDays($conn, $itinerary_id);
$imagesByDay = loadImagesByDay($conn, $days);
$pricing = calculatePricing($itinerary, $flights);

// Date setup
$startDateObj = null;
if (!empty($itinerary['start_date'])) {
    $startDateObj = DateTime::createFromFormat('Y-m-d', $itinerary['start_date']);
}

include 'includes/header.php';
?>

<!-- BANNER -->
<div class="itinBanner">
    <div class="container">
        <div class="row">
            <div class="col">
                <h2><?= htmlspecialchars($itinerary['title']) ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- CUSTOMER & AGENT INFO -->
<div class="container itinInfo d-flex">
    <div class="row">
        <div class="itinNumber">
            <?php if (!empty($itinerary['travel_number'])): ?>
                <p style="background-color: white;margin-bottom: 0;"><strong>Rejse nr.:</strong> <?= htmlspecialchars($itinerary['travel_number']) ?></p>
            <?php endif; ?>
        </div>
    </div>  
    <div class="row bg-light rounded w-100">
        <div class="col-8">
            <div class="day-block d-flex itineraryInfo itin-top cus-itin-top">
                <div class="row cusInfo">
                    <p><strong>Gæst:</strong> <?= htmlspecialchars($itinerary['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($itinerary['customer_email']) ?></p>
                </div>
                <div class="row cusInfo">
                    <p><strong>Telefonnummer:</strong> <?= htmlspecialchars($itinerary['customer_phone']) ?></p>
                    <?php if (!empty($itinerary['notes'])): ?>
                        <p><strong>Noter:</strong> <?= nl2br(htmlspecialchars($itinerary['notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="day-block d-flex itineraryInfo itin-top itin-aProfile">
                <?php if (!empty($itinerary['agent_photo']) && file_exists($itinerary['agent_photo'])): ?>
                    <img src="<?= htmlspecialchars($itinerary['agent_photo']) ?>" alt="Agent photo" style="width: 100px;" class="rounded-circle mb-2 border">
                <?php else: ?>
                    <div class="text-muted"></div>
                <?php endif; ?>
                <div class="flex-column">
                    <p><strong>Rejsekonsulent:</strong> <?= htmlspecialchars($itinerary['agent_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($itinerary['agent_email']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 form__group">

<!-- ITINERARY DAYS -->
<h4 class="h4Divider"><span>Rejseprogram</span></h4>
<?php foreach ($days as $day): ?>
    <?php 
    $dateText = formatDateRange($startDateObj, $day['day_range']);
    $startNum = strpos($day['day_range'], '-') !== false ? intval(explode('-', $day['day_range'])[0]) : intval($day['day_range']);
    ?>
    <div class="day-block day-block-<?= $startNum ?> border rounded p-3 my-3 bg-light">
        <?php if (!empty($dateText)): ?>
            <p><strong>Dato:</strong> <?= $dateText ?></p>
        <?php endif; ?>

        <h3>Dag <?= htmlspecialchars($day['day_range']) ?>: <?= htmlspecialchars($day['day_title']) ?></h3>
        <p><?= $day['description'] ?></p>
        <p class="paddingzero"><strong class="strongH3">Overnight:</strong><?= $day['overnight'] ?></p>

        <?= formatMeals($day['meals']) ?>

        <?php if (!empty($imagesByDay[$day['id']])): ?>
            <div class="row g-2 mt-2">
                <?php foreach (array_slice($imagesByDay[$day['id']], 0, 3) as $img): ?>
                    <div class="col-md-4">
                        <a href="<?= $img['image_path'] ?>" data-lightbox="day-<?= $day['id'] ?>" data-title="Dag <?= htmlspecialchars($day['day_range']) ?>">
                            <img src="<?= $img['image_path'] ?>" class="img-fluid rounded w-100" alt="Billede">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<!-- FLIGHT OFFERS -->
<?php if (!empty($flights)): ?>
    <h4 class="h4Divider"><span>Flytilbud</span></h4>
    <div class="day-block border rounded p-3 bg-light itineraryInfo itin-flights">
        <h4 class="mb-3">Flyinformation</h4>
        <?php foreach ($flights as $i => $flight): ?>
            <div class="mb-4">
                <strong class="mb-2">Flytilbud #<?= $i + 1 ?></strong>
                <?php if (!empty($flight['airline_name'])): ?>
                    <p><strong>Flyselskab:</strong> <?= htmlspecialchars($flight['airline_name']) ?></p>
                <?php endif; ?>
                <br>
                <?php if (!empty($flight['content'])): ?>
                    <div><p><strong>Rejseplan:</strong></p><?= $flight['content'] ?></div>
                <?php endif; ?>
            </div>
            <?php if ($i < count($flights) - 1): ?>
                <hr>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- PRICING & INCLUDES -->
<h4 class="h4Divider"><span>Pris og indhold</span></h4>
<div class="row">
    <div class="col-6" style="display: flex;">
        <div class="day-block border rounded p-3 bg-light itineraryInfo itin-price" style="width:100%">
            <!-- TOUR PRICING -->
            <h5>Rundrejse</h5>
            <div class="row">
                <?php if ($pricing['adults'] > 0 && $pricing['price_adult'] > 0): ?>
                    <div class="col-6"><p><strong>Pris pr. voksen:</strong> <?= number_format($pricing['price_adult'], 0, ',', '.') ?> USD</p></div>
                    <div class="col-6"><p><strong>Antal voksne:</strong> <?= $pricing['adults'] ?></p></div>
                <?php endif; ?>
                <?php if ($pricing['children'] > 0 && $pricing['price_child'] > 0): ?>
                    <div class="col-6"><p><strong>Pris pr. barn u. 12 år:</strong> <?= number_format($pricing['price_child'], 0, ',', '.') ?> USD</p></div>
                    <div class="col-6"><p><strong>Antal børn u. 12 år:</strong> <?= $pricing['children'] ?></p></div>
                <?php endif; ?>
            </div>

            <?php if ($pricing['trip_total_usd'] > 0): ?>
                <hr>
                <strong>Samlet rundrejsepris:</strong>
                <h5><?= number_format($pricing['trip_total_usd'], 0, ',', '.') ?> USD ≈ <?= number_format($pricing['trip_total_dkk'], 0, ',', '.') ?> DKK</h5>
            <?php endif; ?>

            <!-- FLIGHT PRICING -->
            <?php if (!empty($flights[0]) && $pricing['flight_total_dkk'] > 0): ?>
                <hr>
                <h5>Fly</h5>
                <?= implode('', $pricing['flight_breakdown']) ?>
                <hr>
                <div class="mt-2"><strong>Samlet flypris:</strong><h5><?= number_format($pricing['flight_total_dkk'], 0, ',', '.') ?> DKK</h5></div>
            <?php endif; ?>

            <!-- TOTAL PACKAGE -->
            <?php if ($pricing['total_combined_dkk'] > 0): ?>
                <hr>
                <strong>Samlet pakkepris</strong>
                <h5>Total: <?= number_format($pricing['total_combined_dkk'], 0, ',', '.') ?> DKK</h5>
                <p style="font-size: 14px;">Valutakurs: 1 USD ≈ <?= number_format($pricing['adjusted_usd_to_dkk'], 2, ',', '.') ?> DKK</p>
                <i class="text-muted" style="font-size: 14px;">Priserne er med forbehold for ændringer og udsolgte værelser/fly. <br>Prisen er udregnet med dagens kurs og kan derfor være svingende.</i>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6" style="display: flex;">
        <div class="day-block border rounded p-3 bg-light itineraryInfo itin-include">
            <?php if (!empty($itinerary['included'])): ?>
                <h4>Inkluderet i rejsen</h4>
                <div><?= $itinerary['included'] ?></div>
            <?php endif; ?>

            <?php if (!empty($itinerary['not_included'])): ?>
                <h4>Ikke inkluderet</h4>
                <div><?= $itinerary['not_included'] ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ABOUT US -->
<h4 class="h4Divider"><span>Hvem er vi?</span></h4>
<div class="p-3 my-3 itin-bottom">
    <div class="p-2 flex-fill bd-highlight">
        <h3>Om Vietnam Rejser</h3>
        <img class="itin-logo itin-bottom" src="img/vietnam-rejser-logo-2024.png" alt="Logo">
        <p>Vietnam Rejser er et rejsebureau lokaliseret i Vietnams hovedstad Hanoi. Rejsebureauet drives af Kenneth Rasmussen, som i 2007 rejste til Vietnam som backpacker, og siden da har gjort Vietnam til sit andet hjem.<br>
        Efter Kenneth flyttede til Vietnam i år 2011, og startede han Vietnam Rejser op i 2012, har han haft travlt med at skabe store rejseoplevelser for vores mange danske gæster. <br><br>Landets alsidighed og unikhed har givet utallige storslåede rejseoplevelse til Vietnam Rejsers gæster, og vi gør en dyd ud af at sammensætte rejser, der er skræddersyet til gæsternes behov. Læs udtalelser fra vores gæster her: <strong>"<a target="_blank" href="https://vietnam-rejser.dk/vietnam-rejser-anmeldelser/">Gæsterne siger</a>"</strong>.<br>
        Så skriv eller ring endelig, hvis du overvejer, at dit næste store rejseeventyr skal gå til Vietnam – så deler vi meget gerne ud af vores ekspertviden, og hjælper dig med at planlægge din rejse. Vi ser frem til at høre fra dig!<br><br>
        <b>De bedste rejsehilsner</b><br>
        Kenneth Egebjerg Rasmussen</p>
    </div>
</div>

</div>

<script>
    document.querySelectorAll('.day-block p a').forEach(link => {
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');
    });
</script>

<?php include 'includes/footer.php'; ?>