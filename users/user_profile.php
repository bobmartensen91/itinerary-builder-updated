<?php
// users/user_profile.php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'agent') {
    die('<div class="alert alert-danger">Adgang nægtet.</div>');
}

$user_id = intval($_GET['id'] ?? $_SESSION['user_id']);
$message = '';

// Only admins can edit other users
if ($_SESSION['role'] !== 'admin' && $user_id !== $_SESSION['user_id']) {
    die('<div class="alert alert-danger">Du kan kun redigere din egen profil.</div>');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    
    // Handle photo upload
    $photo_sql = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $uploadDir = appPath('uploads/users/');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $photo = 'uploads/users/' . $filename;
        $fullPath = appPath($photo);
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $fullPath)) {
            // Delete old photo if exists
            $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old_user = $result->fetch_assoc();
            if ($old_user['photo'] && file_exists(appPath($old_user['photo']))) {
                unlink(appPath($old_user['photo']));
            }
            
            $photo_sql = ", photo = '$photo'";
        }
    }
    
    // Update user
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?";
    $params = [$name, $email, $phone];
    $types = "sss";
    
    // Add role update if admin is editing
    if ($_SESSION['role'] === 'admin' && isset($_POST['role'])) {
        $sql .= ", role = ?";
        $params[] = $_POST['role'];
        $types .= "s";
    }
    
    // Add password update if provided
    if (!empty($_POST['password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $types .= "s";
    }
    
    $sql .= $photo_sql . " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Profil opdateret.</div>';
        
        // Update session if editing own profile
        if ($user_id === $_SESSION['user_id']) {
            $_SESSION['name'] = $name;
        }
    } else {
        $message = '<div class="alert alert-danger">Fejl ved opdatering af profil.</div>';
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die('<div class="alert alert-danger">Bruger ikke fundet.</div>');
}

include '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= appUrl('users/users.php') ?>">Brugere</a></li>
                    <li class="breadcrumb-item active">Rediger profil</li>
                </ol>
            </nav>
        </div>
    </div>

    <h2>Rediger profil</h2>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="name" class="form-label">Navn *</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rolle</label>
                        <select name="role" id="role" class="form-select">
                            <option value="agent" <?= $user['role'] === 'agent' ? 'selected' : '' ?>>Agent</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="password" class="form-label">Ny adgangskode (valgfri)</label>
                    <input type="password" name="password" id="password" class="form-control">
                    <small class="text-muted">Lad feltet være tomt for at beholde nuværende adgangskode</small>
                </div>
                
                <div class="mb-3">
                    <label for="photo" class="form-label">Profilbillede</label>
                    <?php if (!empty($user['photo']) && file_exists(appPath($user['photo']))): ?>
                        <div class="mb-2">
                            <img src="<?= appUrl($user['photo']) ?>" width="100" class="rounded-circle">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            Gem ændringer
        </button>
        <a href="<?= appUrl('users/users.php') ?>" class="btn btn-secondary">
            <i class="fas fa-times me-1"></i>
            Annuller
        </a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>