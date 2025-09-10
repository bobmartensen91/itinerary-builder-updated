<?php
// Smart bootstrap - handles all includes and path detection automatically
require_once '../includes/bootstrap.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'agent') {
    die('<div class="alert alert-danger">Adgang nægtet.</div>');
}

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($userId <= 0) {
    die('<div class="alert alert-danger">Ugyldig bruger-ID.</div>');
}

// ALWAYS load user data first, before any processing
function loadUserData($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        return false;
    }
    
    // Ensure all required fields have defaults
    return array_merge([
        'name' => '',
        'email' => '',
        'phone' => '',
        'role' => 'agent',
        'photo' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'id' => $userId
    ], $user);
}

// Helper function to safely get user data
function getUserField($user, $field, $default = '') {
    return isset($user[$field]) ? $user[$field] : $default;
}

// Load user data
$user = loadUserData($conn, $userId);
if (!$user) {
    die('<div class="alert alert-danger">Bruger ikke fundet.</div>');
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'] ?? null;
        $role = $_POST['role'];

        // Handle photo upload
        $photoPath = getUserField($user, 'photo', ''); // Keep existing photo by default
        if (!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowedTypes)) {
                // Create upload directory if it doesn't exist
                $uploadDir = appPath('uploads/agents');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Delete old photo if exists
                $oldPhoto = getUserField($user, 'photo', '');
                if (!empty($oldPhoto) && file_exists(appPath($oldPhoto))) {
                    unlink(appPath($oldPhoto));
                }
                
                $photoPath = 'uploads/agents/' . uniqid('agent_', true) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], appPath($photoPath));
            } else {
                $message = 'Ikke-understøttet filtype. Tilladt: JPG, JPEG, PNG, WebP';
                $messageType = 'warning';
            }
        }

        // Update user
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, photo=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $role, $photoPath, $userId);
        
        if ($stmt->execute()) {
            $message = 'Profil opdateret!';
            $messageType = 'success';
            // Reload user data after update
            $user = loadUserData($conn, $userId);
            if (!$user) {
                $message = 'Fejl: Kunne ikke genindlæse brugerdata efter opdatering.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Fejl ved opdatering: ' . htmlspecialchars($stmt->error);
            $messageType = 'danger';
        }
    } elseif (isset($_POST['reset_password'])) {
        $newPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'), 0, 10);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            $message = '<strong>Ny adgangskode genereret:</strong> <span class="font-monospace fs-5">' . htmlspecialchars($newPassword) . '</span><br><small>Gem denne adgangskode og del den sikkert med brugeren.</small>';
            $messageType = 'warning';
        } else {
            $message = 'Fejl ved nulstilling af adgangskode.';
            $messageType = 'danger';
        }
    }
}

include appPath('includes/header.php');

// Final safety check - ensure user data is still valid before rendering
if (!isset($user) || !is_array($user) || empty($user['id'])) {
    $user = loadUserData($conn, $userId);
    if (!$user) {
        die('<div class="alert alert-danger">Kritisk fejl: Brugerdata kunne ikke indlæses.</div>');
    }
}
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Rediger bruger
                    </h1>
                    <p class="text-muted mb-0">Opdater brugeroplysninger og indstillinger</p>
                </div>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Tilbage til brugere
                </a>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (!empty($message)): ?>
        <!-- Lightbox Modal for Messages -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-<?= $messageType ?> text-white border-0">
                        <h5 class="modal-title" id="messageModalLabel">
                            <?php if ($messageType === 'success'): ?>
                                <i class="fas fa-check-circle me-2"></i>Succes
                            <?php elseif ($messageType === 'warning'): ?>
                                <i class="fas fa-exclamation-triangle me-2"></i>Adgangskode
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
                        <?php if ($messageType === 'success'): ?>
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Lukker automatisk om <span id="countdown">3</span> sekunder
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="button" class="btn btn-<?= $messageType ?>" data-bs-dismiss="modal">
                            <i class="fas fa-check me-2"></i>OK
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
            
            <?php if ($messageType === 'success'): ?>
                // Auto close after 3 seconds for success messages
                let seconds = 3;
                const countdownEl = document.getElementById('countdown');
                
                const interval = setInterval(() => {
                    seconds--;
                    if (countdownEl) {
                        countdownEl.textContent = seconds;
                    }
                    
                    if (seconds <= 0) {
                        clearInterval(interval);
                        messageModal.hide();
                    }
                }, 1000);
                
                // Clear interval if user closes manually
                document.getElementById('messageModal').addEventListener('hidden.bs.modal', function() {
                    clearInterval(interval);
                });
            <?php endif; ?>
        });
        </script>
    <?php endif; ?>

    <div class="row">
        <!-- User Profile Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center">
                    <!-- Current Photo -->
                    <div class="mb-4">
                        <?php if (!empty($user['photo']) && file_exists(appPath($user['photo']))): ?>
                            <img src="<?= appUrl($user['photo']) ?>" 
                                 class="rounded-circle border" 
                                 style="width: 120px; height: 120px; object-fit: cover;"
                                 alt="Profilbillede">
                        <?php else: ?>
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 120px; height: 120px;">
                                <i class="fas fa-user text-muted fa-3x"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- User Info -->
                    <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <!-- Role Badge -->
                    <span class="badge <?= $user['role'] === 'agent' ? 'bg-primary' : 'bg-secondary' ?> mb-3 px-3 py-2">
                        <i class="fas <?= $user['role'] === 'agent' ? 'fa-user-tie' : 'fa-user' ?> me-1"></i>
                        <?= ucfirst($user['role']) ?>
                    </span>

                    <!-- Meta Info -->
                    <div class="text-muted small">
                        <p class="mb-1">
                            <i class="fas fa-calendar me-1"></i>
                            Oprettet: <?= isset($user['created_at']) && $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'Unknown' ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock me-1"></i>
                            Sidst opdateret: <?= isset($user['created_at']) && $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'Unknown' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Hurtige handlinger
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post" class="mb-3">
                        <input type="hidden" name="reset_password" value="1">
                        <button type="submit" class="btn btn-warning w-100" 
                                onclick="return confirm('Nulstil adgangskode for <?= htmlspecialchars($user['name']) ?>?')">
                            <i class="fas fa-key me-2"></i>
                            Nulstil adgangskode
                        </button>
                    </form>
                    
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="users.php" class="btn btn-outline-danger w-100"
                           onclick="if(confirm('Slet bruger <?= htmlspecialchars($user['name']) ?>?')) { 
                               var form = document.createElement('form'); 
                               form.method = 'POST'; 
                               form.action = 'users.php'; 
                               var input = document.createElement('input'); 
                               input.type = 'hidden'; 
                               input.name = 'delete_user_id'; 
                               input.value = '<?= $user['id'] ?>'; 
                               form.appendChild(input); 
                               document.body.appendChild(form); 
                               form.submit(); 
                               return false; 
                           } return false;">
                            <i class="fas fa-trash me-2"></i>
                            Slet bruger
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Rediger brugeroplysninger
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row">
                            <!-- Name -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Navn *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars($user['name']) ?>" 
                                       required>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" 
                                       required>
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= htmlspecialchars($user['phone']) ?>" 
                                       placeholder="Telefonnummer">
                            </div>

                            <!-- Role -->
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Rolle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="agent" <?= $user['role'] === 'agent' ? 'selected' : '' ?>>Agent</option>
                                    <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                </select>
                            </div>

                            <!-- Photo Upload -->
                            <div class="col-12 mb-4">
                                <label for="photo" class="form-label">Profilbillede</label>
                                <input type="file" 
                                       class="form-control" 
                                       id="photo" 
                                       name="photo" 
                                       accept="image/jpeg,image/jpg,image/png,image/webp">
                                <div class="form-text">Understøttede formater: JPEG, PNG, WebP. Maks 5MB.</div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Gem ændringer
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annuller
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add form change detection
let formChanged = false;

document.querySelectorAll('input, textarea, select').forEach(el => {
    el.addEventListener('change', () => {
        formChanged = true;
    });
});

document.querySelector('form').addEventListener('submit', () => {
    formChanged = false;
});

window.addEventListener('beforeunload', function (e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Photo preview
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const currentImg = document.querySelector('.rounded-circle');
            if (currentImg && currentImg.tagName === 'IMG') {
                currentImg.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include appPath('includes/footer.php'); ?>