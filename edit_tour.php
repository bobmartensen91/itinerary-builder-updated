<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

$id = (int)$_GET['id'];
$tour = $conn->query("SELECT * FROM tours WHERE id = $id")->fetch_assoc();

function compressImage($source, $destination, $quality = 75) {
  $info = getimagesize($source);
  if ($info === false) return false;

  $mime = $info['mime'];
  switch ($mime) {
    case 'image/jpeg':
      $image = imagecreatefromjpeg($source);
      imagejpeg($image, $destination, $quality);
      break;
    case 'image/png':
      $image = imagecreatefrompng($source);
      // Convert PNG to JPEG to compress better
      $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
      $white = imagecolorallocate($bg, 255, 255, 255);
      imagefill($bg, 0, 0, $white);
      imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
      imagejpeg($bg, $destination, $quality);
      imagedestroy($image);
      $image = $bg;
      break;
    default:
      return false;
  }

  imagedestroy($image);
  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $_POST['title'];
  $description = $_POST['description'];
  $updates = [null, null, null];

  for ($i = 0; $i < 3; $i++) {
    if (isset($_FILES['images']['error'][$i]) && $_FILES['images']['error'][$i] === 0) {
      $tmp = $_FILES['images']['tmp_name'][$i];
      $name = basename($_FILES['images']['name'][$i]);
      $ext = pathinfo($name, PATHINFO_EXTENSION);
      $filename = 'uploads/tours/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $name);

      if (!is_dir('uploads/tours')) mkdir('uploads/tours', 0777, true);

      if (is_uploaded_file($tmp)) {
        if (compressImage($tmp, $filename, 75)) {
          $updates[$i] = $filename;
        } else {
          move_uploaded_file($tmp, $filename); // fallback
          $updates[$i] = $filename;
        }
      }
    } else {
      $updates[$i] = $tour['image' . ($i + 1)];
    }
  }

  $stmt = $conn->prepare("UPDATE tours SET title=?, description=?, image1=?, image2=?, image3=? WHERE id=?");
  $stmt->bind_param("sssssi", $title, $description, $updates[0], $updates[1], $updates[2], $id);
  $stmt->execute();

  echo '<div class="alert alert-success">Tur opdateret!</div>';
  $tour = $conn->query("SELECT * FROM tours WHERE id = $id")->fetch_assoc();
}
?>

<h2>Rediger tur</h2>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Titel</label>
    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($tour['title']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Beskrivelse</label>
    <textarea name="description" class="form-control" id="editor"><?= htmlspecialchars($tour['description']) ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Opdater billeder (max 3)</label>
    <input type="file" name="images[]" multiple class="form-control" accept="image/*">
    <div class="mt-2">
      <?php for ($i = 1; $i <= 3; $i++): ?>
        <?php if (!empty($tour["image$i"])): ?>
          <img src="<?= htmlspecialchars($tour["image$i"]) ?>" width="80" class="me-2">
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  </div>
  <button class="btn btn-primary">Opdater</button>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/41.3.0/classic/ckeditor.js"></script>
<script>
  ClassicEditor.create(document.querySelector('#editor')).catch(error => console.error(error));
</script>

<?php include 'includes/footer.php'; ?>
