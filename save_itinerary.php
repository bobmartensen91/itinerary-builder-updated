<?php
require 'includes/auth.php';
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['itinerary_id'])) {
    die("Invalid request.");
}

$itinerary_id = intval($_POST['itinerary_id']);
$title        = trim($_POST['title'] ?? '');
$price        = intval($_POST['price'] ?? 0);
$price_child  = intval($_POST['price_child'] ?? 0);
$num_adults   = intval($_POST['num_adults'] ?? 0);
$num_children = intval($_POST['num_children'] ?? 0);
$customer_id  = intval($_POST['customer_id'] ?? 0);
$included     = $_POST['included'] ?? '';
$not_included = $_POST['not_included'] ?? '';
$submitted_days = $_POST['days'] ?? [];
$submitted_flights = $_POST['flights'] ?? [];
$existing_ids = [];
$existing_flight_ids = [];

// ✅ Handle start_date conversion (d/m/Y → Y-m-d)
$start_date = null;
if (!empty($_POST['start_date'])) {
    $dateObj = DateTime::createFromFormat('d/m/Y', $_POST['start_date']);
    if ($dateObj) {
        $start_date = $dateObj->format('Y-m-d');
    }
}

// ✅ Update itinerary base info
$stmt = $conn->prepare("UPDATE itineraries 
    SET title = ?, price = ?, price_child = ?, num_adults = ?, num_children = ?, customer_id = ?, included = ?, not_included = ?, start_date = ?
    WHERE id = ?");
$stmt->bind_param("siiiissssi", $title, $price, $price_child, $num_adults, $num_children, $customer_id, $included, $not_included, $start_date, $itinerary_id);
$stmt->execute();

// ✅ Handle flights (NEW price fields)
foreach ($submitted_flights as $flight) {
    $airline       = trim($flight['airline'] ?? '');
    $content       = trim($flight['content'] ?? '');
    $num_adults_f  = intval($flight['num_adults'] ?? 0);
    $price_adult   = floatval($flight['price_adult'] ?? 0);
    $num_children_f= intval($flight['num_children'] ?? 0);
    $price_child_f = floatval($flight['price_child'] ?? 0);
    $num_toddlers  = intval($flight['num_toddlers'] ?? 0);
    $price_toddler = floatval($flight['price_toddler'] ?? 0);
    $num_infants   = intval($flight['num_infants'] ?? 0);
    $price_infant  = floatval($flight['price_infant'] ?? 0);
    $id            = isset($flight['existing_id']) ? intval($flight['existing_id']) : 0;

    if ($id > 0) {
        // Update existing flight
        $stmt = $conn->prepare("UPDATE itinerary_flights 
            SET airline_name = ?, content = ?, 
                num_adults = ?, price_adult = ?, 
                num_children = ?, price_child = ?, 
                num_toddlers = ?, price_toddler = ?, 
                num_infants = ?, price_infant = ?
            WHERE id = ? AND itinerary_id = ?");
        $stmt->bind_param("ssidiididiii", $airline, $content,
            $num_adults_f, $price_adult,
            $num_children_f, $price_child_f,
            $num_toddlers, $price_toddler,
            $num_infants, $price_infant,
            $id, $itinerary_id);
        $stmt->execute();
        $existing_flight_ids[] = $id;
    } elseif (!empty($airline) || !empty($content) || $price_adult > 0 || $price_child_f > 0 || $price_toddler > 0 || $price_infant > 0) {
        // Insert new flight
        $stmt = $conn->prepare("INSERT INTO itinerary_flights 
            (itinerary_id, airline_name, content, 
             num_adults, price_adult, 
             num_children, price_child, 
             num_toddlers, price_toddler, 
             num_infants, price_infant) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issidiididi", $itinerary_id, $airline, $content,
            $num_adults_f, $price_adult,
            $num_children_f, $price_child_f,
            $num_toddlers, $price_toddler,
            $num_infants, $price_infant);
        $stmt->execute();
        $existing_flight_ids[] = $conn->insert_id;
    }
}

// ✅ Loop through days (unchanged from your code)
foreach ($submitted_days as $index => $day) {
    $day_range   = trim($day['day_range']);
    $day_title   = trim($day['day_title']);
    $description = trim($day['description']);
    $overnight   = trim($day['overnight']);
    $sort_order  = $index;
    $meals       = isset($day['meals']) ? implode(', ', $day['meals']) : 'Ingen måltider inkluderet';
    $tour_id     = intval($day['tour_id'] ?? 0);

    if (isset($day['existing_id']) && is_numeric($day['existing_id'])) {
        $existing_id = intval($day['existing_id']);
        $stmt = $conn->prepare("UPDATE itinerary_days 
            SET day_range = ?, day_title = ?, description = ?, overnight = ?, sort_order = ?, meals = ? 
            WHERE id = ?");
        $stmt->bind_param("ssssisi", $day_range, $day_title, $description, $overnight, $sort_order, $meals, $existing_id);
        $stmt->execute();
        $day_id = $existing_id;
        $existing_ids[] = $day_id;
    } else {
        $stmt = $conn->prepare("INSERT INTO itinerary_days (itinerary_id, day_range, day_title, description, overnight, sort_order, meals) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssis", $itinerary_id, $day_range, $day_title, $description, $overnight, $sort_order, $meals);
        $stmt->execute();
        $day_id = $conn->insert_id;
        $existing_ids[] = $day_id;
    }

    // Tour images logic remains unchanged...
    if ($tour_id > 0) {
        $stmt = $conn->prepare("DELETE FROM itinerary_day_images WHERE day_id = ?");
        $stmt->bind_param("i", $day_id);
        $stmt->execute();

        $stmt = $conn->prepare("SELECT image1, image2, image3 FROM tours WHERE id = ?");
        $stmt->bind_param("i", $tour_id);
        $stmt->execute();
        $tour_images = $stmt->get_result()->fetch_assoc();

        if ($tour_images) {
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
}

// 🧹 Delete removed flights
if (!empty($existing_flight_ids)) {
    $placeholders = implode(',', array_fill(0, count($existing_flight_ids), '?'));
    $types = str_repeat('i', count($existing_flight_ids));
    $query = "DELETE FROM itinerary_flights WHERE itinerary_id = ? AND id NOT IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i' . $types, $itinerary_id, ...$existing_flight_ids);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("DELETE FROM itinerary_flights WHERE itinerary_id = ?");
    $stmt->bind_param("i", $itinerary_id);
    $stmt->execute();
}

header("Location: edit_itinerary.php?id=$itinerary_id&saved=1");
exit;