<?php
// users/create_user.php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'agent') {
    die('<div class="alert alert-danger">Adgang nægtet.</div>');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $message = '<div class="alert alert-danger">Email eksisterer allerede.</div>';
    } else {
        // Handle photo upload
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $uploadDir = appPath('uploads/users/');
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $photo = 'uploads/users/' . $filename;
            $fullPath = appPath($photo);
            
            move_uploaded_file($_FILES['photo']['tmp_name'], $fullPath);
        }
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, photo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $password, $role, $photo);
        
        if ($stmt->execute()) {
            header("Location: " . appUrl('users/users.php'));
            exit;
        } else {
            $message = '<div class="alert alert-danger">Fejl ved oprettelse af bruger.</div>';
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= appUrl('users/users.php') ?>">Brugere</a></li>
                    <li class="breadcrumb-item active">Opret ny bruger</li>
                </ol>
            </nav>
        </div>
    </div>

    <h2>Opret ny bruger</h2>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="name" class="form-label">Navn *</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="password" class="form-label">Adgangskode *</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Rolle *</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="agent">Agent</option>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="photo" class="form-label">Profilbillede</label>
                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            Opret bruger
        </button>
        <a href="<?= appUrl('users/users.php') ?>" class="btn btn-secondary">
            <i class="fas fa-times me-1"></i>
            Annuller
        </a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>