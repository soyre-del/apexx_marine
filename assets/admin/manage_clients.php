<?php
session_start();

// THE GATEKEEPER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

require __DIR__ . '/../../db/db.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================
// FORM PROCESSORS: CRUD Operations
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted_token = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['sys_msg'] = "Security validation failed. Unauthorized request.";
        $_SESSION['sys_msg_type'] = "danger";
    } else {
        $action = $_POST['action'] ?? '';

        // 1. ADD NEW CLIENT
        if ($action === 'add_client') {
            $name     = trim($_POST['client_name']);
            $email    = trim($_POST['client_email']);
            $password = $_POST['client_password'];

            if (empty($name) || empty($email) || empty($password)) {
                $_SESSION['sys_msg'] = "All fields are required to register a client.";
                $_SESSION['sys_msg_type'] = "warning";
            } elseif (strlen($password) < 8) {
                $_SESSION['sys_msg'] = "Access Code must be at least 8 characters.";
                $_SESSION['sys_msg_type'] = "warning";
            } else {
                try {
                    $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
                    $stmtCheck->execute([':email' => $email]);
                    
                    if ($stmtCheck->fetch()) {
                        $_SESSION['sys_msg'] = "Registration Halted: Email already exists in the system.";
                        $_SESSION['sys_msg_type'] = "danger";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (:name, :email, :pass, 'client', 1)");
                        $stmt->execute([
                            ':name'  => $name,
                            ':email' => $email,
                            ':pass'  => $hashed_password
                        ]);
                        $_SESSION['sys_msg'] = "Success: Client profile for '$name' created.";
                        $_SESSION['sys_msg_type'] = "success";
                    }
                } catch (PDOException $e) {
                    $_SESSION['sys_msg'] = "Database Error: " . $e->getMessage();
                    $_SESSION['sys_msg_type'] = "danger";
                }
            }
        }

        // 2. EDIT EXISTING CLIENT
        elseif ($action === 'edit_client') {
            $user_id  = (int)$_POST['client_id'];
            $name     = trim($_POST['edit_name']);
            $email    = trim($_POST['edit_email']);
            $password = $_POST['edit_password']; // Optional

            try {
                $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :id LIMIT 1");
                $stmtCheck->execute([':email' => $email, ':id' => $user_id]);
                
                if ($stmtCheck->fetch()) {
                    $_SESSION['sys_msg'] = "Update Halted: That email is already in use by another account.";
                    $_SESSION['sys_msg_type'] = "danger";
                } else {
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET full_name = :name, email = :email, password_hash = :pass WHERE user_id = :id AND role = 'client'");
                        $stmt->execute([':name' => $name, ':email' => $email, ':pass' => $hashed_password, ':id' => $user_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = :name, email = :email WHERE user_id = :id AND role = 'client'");
                        $stmt->execute([':name' => $name, ':email' => $email, ':id' => $user_id]);
                    }
                    $_SESSION['sys_msg'] = "Client profile updated successfully.";
                    $_SESSION['sys_msg_type'] = "success";
                }
            } catch (PDOException $e) {
                $_SESSION['sys_msg'] = "Database Error: " . $e->getMessage();
                $_SESSION['sys_msg_type'] = "danger";
            }
        }

        // 3. TOGGLE CLIENT STATUS (Soft Delete)
        elseif ($action === 'toggle_status') {
            $user_id = (int)$_POST['client_id'];
            $new_status = (int)$_POST['new_status'];
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE user_id = :id AND role = 'client'");
                $stmt->execute([':status' => $new_status, ':id' => $user_id]);
                
                $status_word = $new_status === 1 ? 'Reactivated' : 'Suspended (Soft Deleted)';
                $_SESSION['sys_msg'] = "Client account has been $status_word.";
                $_SESSION['sys_msg_type'] = $new_status === 1 ? "success" : "warning";
            } catch (PDOException $e) {
                $_SESSION['sys_msg'] = "Database Error: " . $e->getMessage();
                $_SESSION['sys_msg_type'] = "danger";
            }
        }
        
        // 4. PERMANENT DELETE CLIENT (Purge)
        elseif ($action === 'hard_delete_client') {
            $user_id = (int)$_POST['client_id'];
            
            try {
                // Permanently removes the user and cascades to delete all their dispatch requests
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id AND role = 'client'");
                $stmt->execute([':id' => $user_id]);
                
                $_SESSION['sys_msg'] = "Critical: Client account and all associated operational data permanently erased.";
                $_SESSION['sys_msg_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['sys_msg'] = "Database Error: Cannot delete profile. Ensure all dependencies are resolved. (" . $e->getMessage() . ")";
                $_SESSION['sys_msg_type'] = "danger";
            }
        }
    }
    
    header("Location: " . $_SERVER['SCRIPT_NAME']);
    exit();
}

$system_message = $_SESSION['sys_msg'] ?? '';
$message_type   = $_SESSION['sys_msg_type'] ?? '';
unset($_SESSION['sys_msg'], $_SESSION['sys_msg_type']);

// ==========================================
// DATA RETRIEVAL (Perfected Relational Mapping)
// ==========================================
try {
    $stmt = $pdo->query("
        SELECT 
            u.user_id, u.full_name, u.email, u.is_active, u.created_at,
            COUNT(d.request_id) AS total_dispatches,
            SUM(CASE WHEN d.status IN ('pending', 'acknowledged', 'deployed', 'in_progress') THEN 1 ELSE 0 END) AS active_dispatches,
            (SELECT company FROM dispatch_requests WHERE client_id = u.user_id ORDER BY requested_at DESC LIMIT 1) AS latest_company,
            (SELECT contact_person FROM dispatch_requests WHERE client_id = u.user_id ORDER BY requested_at DESC LIMIT 1) AS latest_contact_person,
            (SELECT contact_phone FROM dispatch_requests WHERE client_id = u.user_id ORDER BY requested_at DESC LIMIT 1) AS latest_contact_phone
        FROM users u
        LEFT JOIN dispatch_requests d ON u.user_id = d.client_id
        WHERE u.role = 'client'
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $clients = [];
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Registry | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 position-relative bg-grid-pattern font-cascadia">

    <!-- Minimalistic Navigation Bar -->
    <nav class="navbar shadow-lg px-4 py-3" style="background-color: rgba(13, 42, 74, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3">
                <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill border border-info border-opacity-25">
                    <span class="fw-bold text-info text-uppercase letter-spacing-widest" style="font-size: 10px;">CLIENT_DB</span>
                </div>
                <h5 class="font-montserrat fw-bolder text-white mb-0 text-uppercase letter-spacing-wide">Apex Marine</h5>
            </div>
            <div class="d-flex gap-4 align-items-center">
                <a href="admin.php" class="btn btn-sm btn-outline-secondary font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                    &larr; Back to Matrix
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard -->
    <div class="container-fluid p-4 p-md-5">
        
        <?php if (!empty($system_message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type); ?> alert-dismissible fade show border-<?= htmlspecialchars($message_type); ?> border-opacity-50 shadow-sm font-cascadia" role="alert" style="background: rgba(var(--bs-<?= htmlspecialchars($message_type); ?>-rgb), 0.1);">
                <strong>&gt; SYSTEM_LOG:</strong> <?= htmlspecialchars($system_message); ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card glass-card backdrop-blur-2xl rounded-4 shadow-lg border-0 p-4 border-top border-info border-opacity-50">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="font-montserrat fw-bolder text-white text-uppercase mb-1">Client Authorization Roster</h4>
                    <p class="text-secondary small mb-0">Manage access levels and monitor organizational dispatch metrics.</p>
                </div>
                
                <button type="button" class="btn btn-info font-montserrat fw-bold text-uppercase fs-7 d-flex align-items-center gap-2 shadow-sm text-dark px-4 py-2" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                    Register New Client
                </button>
            </div>

            <!-- Client Matrix Table -->
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle font-cascadia m-0" style="background-color: transparent;">
                    <thead>
                        <tr class="text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">
                            <th class="bg-transparent border-secondary border-opacity-25 py-3">Account & Organization</th>
                            <th class="bg-transparent border-secondary border-opacity-25 py-3">Contact Information</th>
                            <th class="bg-transparent border-secondary border-opacity-25 py-3 text-center">Dispatch Metrics</th>
                            <th class="bg-transparent border-secondary border-opacity-25 py-3">Status</th>
                            <th class="bg-transparent border-secondary border-opacity-25 py-3 text-end">Command</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="5" class="bg-transparent text-center text-secondary py-5">No client profiles detected in the sector.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <tr style="<?= $client['is_active'] ? '' : 'opacity: 0.5; background: rgba(255,0,0,0.05);' ?>">
                                    
                                    <!-- ACCOUNT & ORGANIZATION -->
                                    <td class="bg-transparent py-3">
                                        <span class="text-white fw-bold d-block"><?= htmlspecialchars($client['full_name']); ?></span>
                                        <?php if (!empty($client['latest_company'])): ?>
                                            <span class="text-info font-cascadia" style="font-size: 0.75rem;">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                <?= htmlspecialchars($client['latest_company']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-secondary font-cascadia" style="font-size: 0.75rem;">No Company Logged</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- CONTACT INFORMATION -->
                                    <td class="bg-transparent py-3">
                                        <span class="text-secondary d-block mb-1" style="font-size: 0.85rem;"><?= htmlspecialchars($client['email']); ?></span>
                                        <?php if (!empty($client['latest_contact_person'])): ?>
                                            <span class="text-white d-block" style="font-size: 0.8rem;">
                                                POC: <?= htmlspecialchars($client['latest_contact_person']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($client['latest_contact_phone'])): ?>
                                            <span class="text-info font-cascadia d-block" style="font-size: 0.75rem;">
                                                &#9742; <?= htmlspecialchars($client['latest_contact_phone']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- DISPATCH METRICS -->
                                    <td class="bg-transparent text-center py-3">
                                        <?php if ($client['active_dispatches'] > 0): ?>
                                            <span class="badge bg-warning text-dark fw-bold mb-1"><?= (int)$client['active_dispatches']; ?> Active Ops</span><br>
                                        <?php else: ?>
                                            <span class="text-secondary d-block mb-1" style="font-size: 0.75rem;">0 Active Ops</span>
                                        <?php endif; ?>
                                        <span class="text-info font-cascadia" style="font-size: 0.7rem;"><?= (int)$client['total_dispatches']; ?> Total Lifetime</span>
                                    </td>
                                    
                                    <!-- STATUS -->
                                    <td class="bg-transparent py-3">
                                        <?php if ($client['is_active']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">ACTIVE</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">SUSPENDED</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- COMMAND BUTTONS -->
                                    <td class="bg-transparent text-end py-3">
                                        <div class="d-flex gap-2 justify-content-end">
                                            
                                            <button class="btn btn-sm btn-outline-secondary font-montserrat fw-bold text-uppercase px-3" 
                                                    onclick="openEditModal(<?= $client['user_id']; ?>, '<?= htmlspecialchars(addslashes($client['full_name'])); ?>', '<?= htmlspecialchars(addslashes($client['email'])); ?>')">
                                                Edit
                                            </button>
                                            
                                            <!-- SUSPEND / RESTORE FORM (Now intercepted by JS Modal) -->
                                            <form action="" method="POST" class="m-0 toggle-status-form">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="client_id" value="<?= $client['user_id']; ?>">
                                                <input type="hidden" name="new_status" value="<?= $client['is_active'] ? '0' : '1'; ?>">
                                                
                                                <?php if ($client['is_active']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning font-montserrat fw-bold text-uppercase px-3 toggle-btn" data-status="0" data-name="<?= htmlspecialchars($client['full_name'], ENT_QUOTES, 'UTF-8'); ?>">Suspend</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-success text-dark font-montserrat fw-bold text-uppercase px-3 toggle-btn" data-status="1" data-name="<?= htmlspecialchars($client['full_name'], ENT_QUOTES, 'UTF-8'); ?>">Restore</button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- NEW: PERMANENT DELETE (PURGE) BUTTON -->
                                            <button type="button" class="btn btn-sm btn-outline-danger font-montserrat fw-bold text-uppercase px-3 purge-client-btn"
                                                    data-id="<?= htmlspecialchars((string)$client['user_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-name="<?= htmlspecialchars($client['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                Purge
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 1. ADD CLIENT MODAL -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-info border-opacity-50 shadow-lg text-white font-cascadia" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-info border-opacity-25">
                    <h5 class="modal-title font-montserrat fw-bold text-uppercase text-info">Initialize Profile</h5>
                    <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="add_client">
                        
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Organization / Contact Name</label>
                            <input type="text" name="client_name" required class="form-control bg-dark border-secondary text-white shadow-none">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Secure Email</label>
                            <input type="email" name="client_email" required class="form-control bg-dark border-secondary text-white shadow-none">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Temporary Password</label>
                            <input type="password" name="client_password" required minlength="8" class="form-control bg-dark border-secondary text-white shadow-none" placeholder="Min 8 characters">
                        </div>
                        <button type="submit" class="btn btn-info w-100 py-3 rounded-3 font-montserrat fw-bold text-uppercase text-dark">Grant System Access</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. EDIT CLIENT MODAL -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-secondary border-opacity-50 shadow-lg text-white font-cascadia" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title font-montserrat fw-bold text-uppercase text-white">Modify Profile</h5>
                    <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="edit_client">
                        <input type="hidden" name="client_id" id="edit_client_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Organization / Contact Name</label>
                            <input type="text" name="edit_name" id="edit_name" required class="form-control bg-dark border-secondary text-white shadow-none">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Secure Email</label>
                            <input type="email" name="edit_email" id="edit_email" required class="form-control bg-dark border-secondary text-white shadow-none">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Reset Password (Optional)</label>
                            <input type="password" name="edit_password" minlength="8" class="form-control bg-dark border-secondary text-white shadow-none" placeholder="Leave blank to keep current">
                        </div>
                        <button type="submit" class="btn btn-secondary w-100 py-3 rounded-3 font-montserrat fw-bold text-uppercase text-white">Save Modifications</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. PERMANENT DELETE (PURGE) MODAL -->
    <div class="modal fade" id="purgeClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-danger border-opacity-50 shadow-lg text-white font-cascadia" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-body p-4 text-center">
                    <svg width="48" height="48" class="text-danger mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <h5 class="font-montserrat fw-bold text-uppercase text-danger mb-3">CRITICAL: Purge Account?</h5>
                    <p class="text-secondary small mb-4">This will permanently delete <strong id="purge_client_name_display" class="text-white"></strong>'s account and instantly wipe all of their historical dispatch data from the server. This cannot be undone.</p>
                    
                    <form action="" method="POST" class="d-flex gap-2 justify-content-center">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="hard_delete_client">
                        <input type="hidden" name="client_id" id="purge_user_id">
                        
                        <button type="button" class="btn btn-outline-secondary font-montserrat fw-bold text-uppercase fs-7" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger font-montserrat fw-bold text-uppercase fs-7 text-white">Execute Purge</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 4. STATUS TOGGLE WARNING MODAL -->
    <div class="modal fade" id="statusToggleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-warning border-opacity-50 shadow-lg text-white font-cascadia" id="statusToggleContent" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title font-montserrat fw-bold text-uppercase" id="statusToggleTitle">Confirm Action</h5>
                    <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p id="statusToggleMessage" class="mb-4">Are you sure?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary font-montserrat fw-bold text-uppercase fs-7" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning font-montserrat fw-bold text-uppercase fs-7 text-dark" id="confirmToggleBtn">Proceed</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(id, name, email) {
            document.getElementById('edit_client_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            
            var editModal = new bootstrap.Modal(document.getElementById('editClientModal'));
            editModal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Trigger Purge Modal Logic
            const purgeButtons = document.querySelectorAll('.purge-client-btn');
            purgeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('purge_user_id').value = this.getAttribute('data-id');
                    document.getElementById('purge_client_name_display').textContent = this.getAttribute('data-name');
                    
                    var purgeModal = new bootstrap.Modal(document.getElementById('purgeClientModal'));
                    purgeModal.show();
                });
            });

            // Trigger Status Toggle Warning Modal Logic
            let pendingToggleForm = null;
            const toggleButtons = document.querySelectorAll('.toggle-btn');
            let toggleModalInstance = null;
            
            if (document.getElementById('statusToggleModal')) {
                toggleModalInstance = new bootstrap.Modal(document.getElementById('statusToggleModal'));
            }

            toggleButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    pendingToggleForm = this.closest('form');
                    const status = this.getAttribute('data-status');
                    const clientName = this.getAttribute('data-name');
                    
                    const titleEl = document.getElementById('statusToggleTitle');
                    const msgEl = document.getElementById('statusToggleMessage');
                    const confirmBtn = document.getElementById('confirmToggleBtn');
                    const modalContent = document.getElementById('statusToggleContent');

                    if (status === '0') {
                        titleEl.textContent = 'Suspend Account';
                        titleEl.className = 'modal-title font-montserrat fw-bold text-uppercase text-warning';
                        msgEl.innerHTML = `WARNING: Suspending <strong>${clientName}</strong> revokes their login access immediately. Proceed?`;
                        confirmBtn.className = 'btn btn-warning font-montserrat fw-bold text-uppercase fs-7 text-dark';
                        confirmBtn.textContent = 'Suspend';
                        modalContent.classList.remove('border-success');
                        modalContent.classList.add('border-warning');
                    } else {
                        titleEl.textContent = 'Restore Account';
                        titleEl.className = 'modal-title font-montserrat fw-bold text-uppercase text-success';
                        msgEl.innerHTML = `Reactivate the account for <strong>${clientName}</strong>? They will regain system access.`;
                        confirmBtn.className = 'btn btn-success font-montserrat fw-bold text-uppercase fs-7 text-dark';
                        confirmBtn.textContent = 'Restore';
                        modalContent.classList.remove('border-warning');
                        modalContent.classList.add('border-success');
                    }

                    toggleModalInstance.show();
                });
            });

            if (document.getElementById('confirmToggleBtn')) {
                document.getElementById('confirmToggleBtn').addEventListener('click', function() {
                    if (pendingToggleForm) {
                        pendingToggleForm.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>