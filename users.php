<?php
// Simple fallback approach - works without PathHelper
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Try to load PathHelper for navigation links
$usePathHelper = false;
if (file_exists('../includes/path_helper.php')) {
    require_once '../includes/path_helper.php';
    $usePathHelper = true;
}

include '../includes/header.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'agent') {
    die('<div class="alert alert-danger">Adgang nægtet.</div>');
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $id = intval($_POST['delete_user_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo '<div class="alert alert-danger">Bruger slettet.</div>';
}

// Get users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-users text-primary me-2"></i>
                        Brugere
                    </h1>
                    <p class="text-muted mb-0">Administrer brugere og deres adgang</p>
                </div>
                <a href="<?= $usePathHelper ? PathHelper::usersPath('create_user.php') : 'create_user.php' ?>" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>
                    Tilføj ny bruger
                </a>
            </div>
        </div>
    </div>

    <?php if ($users->num_rows > 0): ?>
        <!-- Users Grid -->
        <div class="row g-4">
            <?php while ($user = $users->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <!-- User Photo -->
                            <div class="text-center mb-3">
                                <?php if (!empty($user['photo']) && file_exists('../' . $user['photo'])): ?>
                                    <img src="<?= '../' . htmlspecialchars($user['photo']) ?>" 
                                         class="rounded-circle border" 
                                         style="width: 80px; height: 80px; object-fit: cover;"
                                         alt="Profilbillede">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center mx-auto" 
                                         style="width: 80px; height: 80px;">
                                        <i class="fas fa-user text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- User Info -->
                            <div class="text-center">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                                
                                <?php if (!empty($user['phone'])): ?>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-phone me-1"></i>
                                        <?= htmlspecialchars($user['phone']) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Role Badge -->
                                <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> mb-3">
                                    <i class="fas <?= $user['role'] === 'admin' ? 'fa-crown' : 'fa-user-tie' ?> me-1"></i>
                                    <?= ucfirst($user['role']) ?>
                                </span>

                                <!-- Created Date -->
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-calendar me-1"></i>
                                    Oprettet: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Card Footer with Actions -->
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-grid gap-2">
                                <a href="<?= $usePathHelper ? PathHelper::usersPath('user_profile.php?id=' . $user['id']) : 'user_profile.php?id=' . $user['id'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>
                                    Rediger profil
                                </a>
                                
                                <div class="d-flex gap-2">
                                    <form method="post" class="flex-fill" onsubmit="return confirm('Nulstil adgangskode for <?= htmlspecialchars($user['name']) ?>?')">
                                        <input type="hidden" name="reset_user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                            <i class="fas fa-key me-1"></i>
                                            Reset kode
                                        </button>
                                    </form>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm flex-fill"
                                                onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash me-1"></i>
                                            Slet
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="text-muted mb-3">Ingen brugere endnu</h3>
                    <p class="text-muted mb-4">Kom i gang med at oprette den første bruger</p>
                    <a href="<?= $usePathHelper ? PathHelper::usersPath('create_user.php') : 'create_user.php' ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>
                        Opret den første bruger
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Bekræft sletning
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Er du sikker på, at du vil slette brugeren <strong id="userName"></strong>?</p>
                <p class="text-muted small mb-0">Denne handling kan ikke fortrydes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Annuller
                </button>
                <form method="post" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="delete_user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Slet bruger
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteUserId').value = userId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Add hover effects to cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<style>
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.card-footer {
    padding: 1rem;
}
</style>

<?php include '../includes/footer.php'; ?>