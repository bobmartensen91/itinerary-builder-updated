<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

// Image optimization function
function optimizeImage($sourcePath, $destinationPath, $maxWidth = 1200, $maxHeight = 800, $quality = 75) {
    // Get image info
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($originalWidth, $originalHeight, $type) = $imageInfo;
    
    // Calculate new dimensions (only resize if image is larger than max dimensions)
    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
    } else {
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
    }
    
    // Create new image canvas
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle different image types
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            // Preserve transparency for PNG
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $sourceImage = imagecreatefromwebp($sourcePath);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }
    
    // Resize the image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Save optimized image
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($newImage, $destinationPath, $quality);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($newImage, $destinationPath, 6);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $success = imagewebp($newImage, $destinationPath, $quality);
            }
            break;
    }
    
    // Clean up memory
    imagedestroy($newImage);
    imagedestroy($sourceImage);
    
    return $success;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $images = [null, null, null]; // initialize with 3 nulls
    $uploadErrors = [];
    
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        // Create upload directory if it doesn't exist
        if (!is_dir('uploads/tours')) {
            mkdir('uploads/tours', 0777, true);
        }
        
        for ($i = 0; $i < count($_FILES['images']['name']) && $i < 3; $i++) {
            // Skip empty uploads
            if (empty($_FILES['images']['name'][$i])) {
                continue;
            }
            
            if (isset($_FILES['images']['error'][$i]) && $_FILES['images']['error'][$i] === 0) {
                $tmp = $_FILES['images']['tmp_name'][$i];
                $originalName = basename($_FILES['images']['name'][$i]);
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Validate file type
                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowedTypes)) {
                    $uploadErrors[] = "Billede " . ($i + 1) . ": Ikke-understøttet filtype ($ext). Tilladt: " . implode(', ', $allowedTypes);
                    continue;
                }
                
                // Check file size (5MB limit)
                if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                    $uploadErrors[] = "Billede " . ($i + 1) . ": Filen er for stor (max 5MB)";
                    continue;
                }
                
                // Validate that it's actually an image
                $imageInfo = getimagesize($tmp);
                if (!$imageInfo) {
                    $uploadErrors[] = "Billede " . ($i + 1) . ": Ikke en gyldig billedfil";
                    continue;
                }
                
                // Generate unique filename
                $newName = uniqid('tour_', true) . '.' . $ext;
                $targetPath = 'uploads/tours/' . $newName;
                
                // Try to optimize the image
                if (optimizeImage($tmp, $targetPath)) {
                    $images[$i] = $targetPath;
                } else {
                    // Fallback: use original file processing method
                    switch ($ext) {
                        case 'jpg':
                        case 'jpeg':
                            $image = imagecreatefromjpeg($tmp);
                            if ($image) {
                                imagejpeg($image, $targetPath, 75);
                                imagedestroy($image);
                                $images[$i] = $targetPath;
                            } else {
                                $uploadErrors[] = "Billede " . ($i + 1) . ": Kunne ikke behandle JPEG-fil";
                            }
                            break;
                        case 'png':
                            $image = imagecreatefrompng($tmp);
                            if ($image) {
                                imagepng($image, $targetPath, 6);
                                imagedestroy($image);
                                $images[$i] = $targetPath;
                            } else {
                                $uploadErrors[] = "Billede " . ($i + 1) . ": Kunne ikke behandle PNG-fil";
                            }
                            break;
                        case 'webp':
                            if (function_exists('imagecreatefromwebp')) {
                                $image = imagecreatefromwebp($tmp);
                                if ($image) {
                                    imagewebp($image, $targetPath, 75);
                                    imagedestroy($image);
                                    $images[$i] = $targetPath;
                                } else {
                                    $uploadErrors[] = "Billede " . ($i + 1) . ": Kunne ikke behandle WebP-fil";
                                }
                            } else {
                                // WebP not supported, just move the file
                                if (move_uploaded_file($tmp, $targetPath)) {
                                    $images[$i] = $targetPath;
                                } else {
                                    $uploadErrors[] = "Billede " . ($i + 1) . ": Kunne ikke uploade fil";
                                }
                            }
                            break;
                        default:
                            // Unsupported format, just move as-is
                            if (move_uploaded_file($tmp, $targetPath)) {
                                $images[$i] = $targetPath;
                            } else {
                                $uploadErrors[] = "Billede " . ($i + 1) . ": Kunne ikke uploade fil";
                            }
                            break;
                    }
                }
            } else {
                // Handle upload errors
                $errorMessage = "Billede " . ($i + 1) . ": ";
                switch ($_FILES['images']['error'][$i]) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage .= "Filen er for stor";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage .= "Filen blev kun delvist uploadet";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMessage .= "Ingen fil blev uploadet";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMessage .= "Midlertidig mappe mangler";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMessage .= "Kunne ikke skrive til disk";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errorMessage .= "Upload stoppet af extension";
                        break;
                    default:
                        $errorMessage .= "Ukendt fejl";
                        break;
                }
                $uploadErrors[] = $errorMessage;
            }
        }
    }
    
    // Show upload errors if any
    if (!empty($uploadErrors)) {
        foreach ($uploadErrors as $error) {
            echo '<div class="alert alert-warning">' . htmlspecialchars($error) . '</div>';
        }
    }
    
    // Insert into database only if we have at least title and description
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO tours (title, description, image1, image2, image3) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description, $images[0], $images[1], $images[2]);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Tur oprettet!</div>';
            if (count(array_filter($images)) > 0) {
                echo '<div class="alert alert-info">Billeder optimeret og uploadet: ' . count(array_filter($images)) . ' af ' . count(array_filter($_FILES['images']['name'] ?? [])) . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Fejl ved oprettelse af tur: ' . htmlspecialchars($stmt->error) . '</div>';
        }
    }
}
?>

<h2>Opret ny tur</h2>
<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="title" class="form-label">Titel</label>
        <input type="text" class="form-control" name="title" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Beskrivelse</label>
        <textarea name="description" class="form-control" id="editor"></textarea>
    </div>
    <div class="mb-3">
        <label for="images" class="form-label">Billeder (max 3, max 5MB hver)</label>
        <input type="file" name="images[]" multiple class="form-control" accept="image/jpeg,image/jpg,image/png,image/webp">
        <div class="form-text">Understøttede formater: JPEG, PNG, WebP. Billeder resizes automatisk til max 1200x800px for optimal performance.</div>
    </div>
    <button type="submit" class="btn btn-primary">Gem</button>
</form>

<!-- ✅ CKEditor script -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('#editor'))
    .catch(error => console.error(error));

let formChanged = false;

// Watch for changes in the form
document.querySelectorAll('input, textarea, select').forEach(el => {
    el.addEventListener('change', () => {
        formChanged = true;
    });
});

// Disable warning if form is submitted
document.querySelector('form').addEventListener('submit', () => {
    formChanged = false;
});

// Warn before unloading
window.addEventListener('beforeunload', function (e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = ''; // Required for Chrome
    }
});
</script>

<?php include 'includes/footer.php'; ?>