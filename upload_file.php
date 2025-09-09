<?php
require 'includes/db.php';
require 'includes/auth.php';

if ($_SESSION['role'] !== 'agent') {
    die("Adgang nægtet.");
}

$uploadMessages = [];

// Image optimization function
function optimizeImage($sourcePath, $destinationPath, $quality = 85, $maxWidth = 1920, $maxHeight = 1080) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Create source image resource
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) return false;
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
    $newWidth = round($sourceWidth * $ratio);
    $newHeight = round($sourceHeight * $ratio);
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save optimized image
    $success = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $success = imagejpeg($newImage, $destinationPath, $quality);
            break;
        case 'image/png':
            // PNG quality is 0-9, convert from 0-100
            $pngQuality = round((100 - $quality) * 9 / 100);
            $success = imagepng($newImage, $destinationPath, $pngQuality);
            break;
        case 'image/gif':
            $success = imagegif($newImage, $destinationPath);
            break;
        case 'image/webp':
            $success = imagewebp($newImage, $destinationPath, $quality);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $success;
}

// Get file size in human readable format
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $imageQuality = intval($_POST['image_quality'] ?? 85);
    $maxWidth = intval($_POST['max_width'] ?? 1920);
    $maxHeight = intval($_POST['max_height'] ?? 1080);
    $optimizeImages = isset($_POST['optimize_images']);
    
    if (!empty($_FILES['files']['name'][0])) {
        $uploadDir = 'uploads/customer_files/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['files']['error'][$index] === UPLOAD_ERR_OK) {
                $originalName = basename($_FILES['files']['name'][$index]);
                $safeName = preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $originalName);
                $uniquePath = $uploadDir . time() . '_' . $safeName;
                
                $originalSize = $_FILES['files']['size'][$index];
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                
                $uploadSuccess = false;
                $finalSize = $originalSize;
                
                if ($isImage && $optimizeImages) {
                    // Try to optimize the image
                    if (optimizeImage($tmpName, $uniquePath, $imageQuality, $maxWidth, $maxHeight)) {
                        $finalSize = filesize($uniquePath);
                        $uploadSuccess = true;
                        $compressionRatio = round((($originalSize - $finalSize) / $originalSize) * 100, 1);
                        $uploadMessages[] = "✅ Optimeret og uploadet: $originalName<br>" . 
                                          "&nbsp;&nbsp;&nbsp;Størrelse: " . formatFileSize($originalSize) . " → " . formatFileSize($finalSize) . 
                                          " (sparet {$compressionRatio}%)";
                    } else {
                        // Fallback to normal upload if optimization fails
                        if (move_uploaded_file($tmpName, $uniquePath)) {
                            $uploadSuccess = true;
                            $uploadMessages[] = "✅ Uploadet (optimering fejlede): $originalName - " . formatFileSize($originalSize);
                        }
                    }
                } else {
                    // Normal upload for non-images or when optimization is disabled
                    if (move_uploaded_file($tmpName, $uniquePath)) {
                        $uploadSuccess = true;
                        $uploadMessages[] = "✅ Uploadet: $originalName - " . formatFileSize($originalSize);
                    }
                }
                
                if ($uploadSuccess) {
                    // Save to database
                    $stmt = $conn->prepare("INSERT INTO customer_files (customer_id, file_name, file_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $customer_id, $originalName, $uniquePath);
                    $stmt->execute();
                } else {
                    $uploadMessages[] = "❌ Fejl ved upload af $originalName";
                }
            } else {
                $uploadMessages[] = "❌ Upload-fejl for " . $_FILES['files']['name'][$index];
            }
        }
    } else {
        $uploadMessages[] = "❌ Ingen filer valgt.";
    }
}

$customers = $conn->query("SELECT id, name FROM customers ORDER BY name");
?>

<?php include 'includes/header.php'; ?>
<h2>Upload filer til kunde</h2>

<?php if (!empty($uploadMessages)): ?>
  <div class="alert alert-info">
    <?php foreach ($uploadMessages as $msg): ?>
      <p><?= $msg ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label for="customer_id">Vælg kunde:</label>
    <select name="customer_id" id="customer_id" class="form-select" required>
      <option value="">-- Vælg kunde --</option>
      <?php while ($row = $customers->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>
  
  <div class="mb-3">
    <label for="files">Vælg filer (flere tilladt):</label>
    <input type="file" name="files[]" class="form-control" multiple required accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx">
  </div>
  
  <!-- Image Optimization Settings -->
  <div class="card mb-3">
    <div class="card-header">
      <h6 class="mb-0">Billedeoptimering (kun for billeder)</h6>
    </div>
    <div class="card-body">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="optimize_images" id="optimize_images" checked>
        <label class="form-check-label" for="optimize_images">
          <strong>Optimer billeder automatisk</strong>
          <small class="text-muted d-block">Reducerer filstørrelse og tilpasser dimensioner</small>
        </label>
      </div>
      
      <div id="optimization-settings">
        <div class="row">
          <div class="col-md-4">
            <label for="image_quality" class="form-label">Kvalitet (0-100):</label>
            <input type="range" class="form-range" name="image_quality" id="image_quality" min="50" max="100" value="85">
            <div class="text-center">
              <small class="text-muted">Værdi: <span id="quality-value">85</span>%</small>
            </div>
          </div>
          <div class="col-md-4">
            <label for="max_width" class="form-label">Maks bredde (px):</label>
            <select name="max_width" id="max_width" class="form-select">
              <option value="1920" selected>1920px (Full HD)</option>
              <option value="1600">1600px</option>
              <option value="1280">1280px (HD)</option>
              <option value="1024">1024px</option>
              <option value="800">800px</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="max_height" class="form-label">Maks højde (px):</label>
            <select name="max_height" id="max_height" class="form-select">
              <option value="1080" selected>1080px (Full HD)</option>
              <option value="1200">1200px</option>
              <option value="960">960px</option>
              <option value="768">768px</option>
              <option value="600">600px</option>
            </select>
          </div>
        </div>
        
        <div class="mt-3">
          <small class="text-muted">
            <strong>Tips:</strong> Højere kvalitet = større filer. 85% giver god balance mellem kvalitet og størrelse.
            Billeder skaleres kun ned, aldrig op.
          </small>
        </div>
      </div>
    </div>
  </div>
  
  <button type="submit" class="btn btn-primary">Upload filer</button>
</form>

<script>
// Update quality value display
document.getElementById('image_quality').addEventListener('input', function() {
  document.getElementById('quality-value').textContent = this.value;
});

// Show/hide optimization settings
document.getElementById('optimize_images').addEventListener('change', function() {
  const settings = document.getElementById('optimization-settings');
  settings.style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include 'includes/footer.php'; ?>