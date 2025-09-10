<?php
// Smart bootstrap - handles all includes and path detection automatically
require_once '../includes/bootstrap.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'agent') {
    die('<div class="alert alert-danger">Adgang nægtet.</div>');
}

$success = false;
$generatedPassword = '';
$message = '';
$messageType = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $role = $_POST['role'];

    // Validate email uniqueness
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        $errors[] = "En bruger med denne email eksisterer allerede.";
    }

    // Handle photo upload
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowedTypes)) {
            // Check file size (5MB limit)
            if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                // Validate that it's actually an image
                $imageInfo = getimagesize($_FILES['photo']['tmp_name']);
                if ($imageInfo) {
                    // Create upload directory if it doesn't exist
                    $uploadDir = appPath('uploads/agents');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $photoPath = 'uploads/agents/' . uniqid('agent_', true) . '.' . $ext;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], appPath($photoPath))) {
                        $errors[] = "Fejl ved upload af profilbillede.";
                        $photoPath = null;
                    }
                } else {
                    $errors[] = "Uploadet fil er ikke et gyldigt billede.";
                }
            } else {
                $errors[] = "Profilbillede er for stort (max 5MB).";
            }
        } else {
            $errors[] = "Ikke-understøttet filtype. Tilladt: JPG, JPEG, PNG, WebP.";
        }
    }

    // If no errors, create user
    if (empty($errors)) {
        // Generate random password
        $generatedPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'), 0, 10);
        $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, photo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $hashedPassword, $role, $photoPath);
        
        if ($stmt->execute()) {
            $success = true;
            $message = '<strong>Bruger oprettet!</strong><br>Ny adgangskode til <strong>' . htmlspecialchars($name) . '</strong>:<br><span class="font-monospace fs-4 text-primary">' . htmlspecialchars($generatedPassword) . '</span><br><small class="text-muted">Gem denne adgangskode sikkert og del den med brugeren.</small>';
            $messageType = 'warning';
        } else {
            $errors[] = "Fejl ved oprettelse af bruger: " . htmlspecialchars($stmt->error);
            // Clean up uploaded photo if user creation failed
            if ($photoPath && file_exists(appPath($photoPath))) {
                unlink(appPath($photoPath));
            }
        }
    }

    // Set error message if there are errors
    if (!empty($errors)) {
        $message = '<strong>Der opstod følgende fejl:</strong><ul class="mb-0 mt-2">';
        foreach ($errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul>';
        $messageType = 'danger';
    }
}

include appPath('includes/header.php');
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-user-plus text-primary me-2"></i>
                        Opret ny bruger
                    </h1>
                    <p class="text-muted mb-0">Tilføj en ny bruger til systemet</p>
                </div>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Tilbage til brugere
                </a>
            </div>
        </div>
    </div>

    <!-- Display Messages via Lightbox -->
    <?php if (!empty($message)): ?>
        <!-- Lightbox Modal for Messages -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-<?= $messageType ?> text-white border-0">
                        <h5 class="modal-title" id="messageModalLabel">
                            <?php if ($messageType === 'warning'): ?>
                                <i class="fas fa-user-check me-2"></i>Bruger Oprettet
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle me-2"></i>Fejl
                            <?php endif; ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center">
                            <?= $message ?>
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <?php if ($success): ?>
                            <a href="users.php" class="btn btn-primary me-2">
                                <i class="fas fa-users me-2"></i>Se alle brugere
                            </a>
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-user-plus me-2"></i>Opret endnu en bruger
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                <i class="fas fa-check me-2"></i>OK
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
        });
        </script>
    <?php endif; ?>

    <?php if (!$success): ?>
        <!-- Create User Form -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>
                            Brugeroplysninger
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Name -->
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">
                                        Navn *
                                        <i class="fas fa-user text-muted ms-1"></i>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                           placeholder="Indtast fulde navn"
                                           required>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        Email *
                                        <i class="fas fa-envelope text-muted ms-1"></i>
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                           placeholder="bruger@example.com"
                                           required>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        Telefon
                                        <i class="fas fa-phone text-muted ms-1"></i>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                                           placeholder="Telefonnummer (valgfrit)">
                                </div>

                                <!-- Role -->
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">
                                        Rolle *
                                        <i class="fas fa-user-tag text-muted ms-1"></i>
                                    </label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Vælg rolle...</option>
                                        <option value="agent" <?= isset($_POST['role']) && $_POST['role'] === 'agent' ? 'selected' : '' ?>>
                                            Agent
                                        </option>
                                        <option value="customer" <?= isset($_POST['role']) && $_POST['role'] === 'customer' ? 'selected' : '' ?>>
                                            Customer
                                        </option>
                                    </select>
                                </div>

                                <!-- Photo Upload -->
                                <div class="col-12 mb-4">
                                    <label for="photo" class="form-label">
                                        Profilbillede
                                        <i class="fas fa-camera text-muted ms-1"></i>
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="photo" 
                                           name="photo" 
                                           accept="image/jpeg,image/jpg,image/png,image/webp">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Understøttede formater: JPEG, PNG, WebP. Maksimal størrelse: 5MB.
                                    </div>
                                    
                                    <!-- Photo Preview -->
                                    <div id="photoPreview" class="mt-3" style="display: none;">
                                        <label class="form-label text-muted">Forhåndsvisning:</label>
                                        <div>
                                            <img id="previewImg" 
                                                 class="rounded-circle border" 
                                                 style="width: 80px; height: 80px; object-fit: cover;"
                                                 alt="Forhåndsvisning">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Vigtig information
                                </h6>
                                <ul class="mb-0">
                                    <li>En midlertidig adgangskode vil blive genereret automatisk</li>
                                    <li>Brugeren vil modtage login-oplysninger efter oprettelse</li>
                                    <li>Brugeren kan ændre sin adgangskode efter første login</li>
                                </ul>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex gap-3 justify-content-end">
                                <a href="users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Annuller
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Opret bruger
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Photo preview functionality
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photoPreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        // Check file size
        if (file.size > 5 * 1024 * 1024) {
            alert('Filen er for stor. Maksimal størrelse er 5MB.');
            this.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Ikke-understøttet filtype. Brug JPG, PNG eller WebP.');
            this.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');
    
    // Email validation
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email) {
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.setCustomValidity('Indtast en gyldig email-adresse');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        }
    });
    
    // Form change detection
    let formChanged = false;
    
    document.querySelectorAll('input, textarea, select').forEach(el => {
        el.addEventListener('change', () => {
            formChanged = true;
        });
    });
    
    if (form) {
        form.addEventListener('submit', () => {
            formChanged = false;
        });
    }
    
    window.addEventListener('beforeunload', function (e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

// Form submission with loading state
if (document.querySelector('form')) {
    document.querySelector('form').addEventListener('submit', function(e) {
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Opretter...';
        submitBtn.disabled = true;
        
        // Re-enable button after a delay in case of errors
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });
}
</script>

<?php include appPath('includes/footer.php'); ?>