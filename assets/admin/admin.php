<?php
session_start();

// 1. THE GATEKEEPER: Strict Authorization Check
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

// 2. Connect to the Database
require __DIR__ . '/../../db/db.php'; 

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Technical Discipline Whitelist
$allowed_specialties = [
    'Chief Engineer',
    'Propulsion & Machinery Specialist',
    'Electrical & Automation Engineer',
    'Hydraulics & Deck Gear Technician',
    'Hull & Steel Fabricator',
    'Preventative Maintenance Expert',
    'General Marine Consultant'
];

$allowed_statuses = ['available', 'deployed', 'on_leave'];

// ==========================================
// FORM PROCESSORS: Engineer CRUD Operations
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['sys_msg'] = "Security token validation failed. Unauthorized request.";
        $_SESSION['sys_msg_type'] = "danger";
        header("Location: " . $_SERVER['SCRIPT_NAME']);
        exit();
    }

    $action = $_POST['action'];

// ---------------------------------------------------------
    // CREATE: Deploy New Engineer
    // ---------------------------------------------------------
    if ($action === 'add_engineer') {
        $name      = trim($_POST['eng_name'] ?? '');
        $email     = trim($_POST['eng_email'] ?? '');
        $password  = $_POST['eng_password'] ?? ''; // Never trim passwords
        $specialty = trim($_POST['eng_specialty'] ?? '');
        $status    = $_POST['eng_status'] ?? 'available';

        // Validate Enums
        if (!in_array($status, $allowed_statuses, true)) $status = 'available';
        if (!in_array($specialty, $allowed_specialties, true)) $specialty = ''; 

        // Validation Checks
        if (empty($name) || empty($email) || empty($password) || empty($specialty)) {
            $_SESSION['sys_msg'] = "All personnel fields are required for deployment. Please select a valid discipline.";
            $_SESSION['sys_msg_type'] = "warning";
            $_SESSION['old_post'] = $_POST;
            $_SESSION['failed_action'] = $action;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['sys_msg'] = "Invalid email format.";
            $_SESSION['sys_msg_type'] = "warning";
            $_SESSION['old_post'] = $_POST;
            $_SESSION['failed_action'] = $action;
        } elseif (strlen($password) < 8) {
            $_SESSION['sys_msg'] = "Access Code must be at least 8 characters.";
            $_SESSION['sys_msg_type'] = "warning";
            $_SESSION['old_post'] = $_POST;
            $_SESSION['failed_action'] = $action;
        } else {
            try {
                // Check for duplicate emails
                $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
                $stmtCheck->execute([':email' => $email]);
                
                if ($stmtCheck->fetch()) {
                    $_SESSION['sys_msg'] = "Registration Halted: Engineer email already exists in the matrix.";
                    $_SESSION['sys_msg_type'] = "danger";
                    $_SESSION['old_post'] = $_POST;
                    $_SESSION['failed_action'] = $action;
                } else {
                    // Execute Database Transaction
                    $pdo->beginTransaction();

                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt1 = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (:name, :email, :pass, 'engineer', 1)");
                    $stmt1->execute([
                        ':name'  => $name,
                        ':email' => $email,
                        ':pass'  => $hashed_password
                    ]);
                    
                    $new_id = $pdo->lastInsertId();

                    $stmt2 = $pdo->prepare("INSERT INTO engineer_profiles (engineer_id, specialty, current_status) VALUES (:id, :spec, :status)");
                    $stmt2->execute([
                        ':id'     => $new_id,
                        ':spec'   => $specialty,
                        ':status' => $status
                    ]);

                    $pdo->commit();
                    $_SESSION['sys_msg'] = "Success: Engineer $name has been officially deployed to the roster.";
                    $_SESSION['sys_msg_type'] = "success";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("DB Deployment Error: " . $e->getMessage());
                $_SESSION['sys_msg'] = "CRITICAL ERROR: Unable to deploy engineer due to a system fault.";
                $_SESSION['sys_msg_type'] = "danger";
                $_SESSION['old_post'] = $_POST;
                $_SESSION['failed_action'] = $action;
            }
        }
    }

    // ---------------------------------------------------------
    // UPDATE: Edit Existing Engineer
    // ---------------------------------------------------------
    elseif ($action === 'edit_engineer') {
        $id        = (int)($_POST['user_id'] ?? 0);
        $name      = trim($_POST['eng_name'] ?? '');
        $email     = trim($_POST['eng_email'] ?? '');
        $password  = $_POST['eng_password'] ?? ''; // Password update is optional
        $specialty = trim($_POST['eng_specialty'] ?? '');
        $status    = $_POST['eng_status'] ?? 'available';

        // Validate Enums
        if (!in_array($status, $allowed_statuses, true)) $status = 'available';
        if (!in_array($specialty, $allowed_specialties, true)) $specialty = ''; 

        // Validation Checks
        if (empty($id) || empty($name) || empty($email) || empty($specialty)) {
            $_SESSION['sys_msg'] = "Critical fields missing for profile update.";
            $_SESSION['sys_msg_type'] = "warning";
            $_SESSION['old_post'] = $_POST;
            $_SESSION['failed_action'] = $action;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['sys_msg'] = "Invalid email format provided.";
            $_SESSION['sys_msg_type'] = "warning";
            $_SESSION['old_post'] = $_POST;
            $_SESSION['failed_action'] = $action;
        } elseif (!empty($password) && strlen($password) < 8) {
            $_SESSION['sys_msg'] = "New Access Code must be at least 8 characters.";
            $_SESSION['sys_msg_type'] = "warning";
            $_SESSION['old_post'] = $_POST;
            $_SESSION['failed_action'] = $action;
        } else {
            try {
                // Ensure email isn't being taken by another user
                $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :id LIMIT 1");
                $stmtCheck->execute([':email' => $email, ':id' => $id]);
                
                if ($stmtCheck->fetch()) {
                    $_SESSION['sys_msg'] = "Update Halted: Email is already registered to another operative.";
                    $_SESSION['sys_msg_type'] = "danger";
                    $_SESSION['old_post'] = $_POST;
                    $_SESSION['failed_action'] = $action;
                } else {
                    // Execute Database Transaction
                    $pdo->beginTransaction();

                    if (!empty($password)) {
                        // Admin provided a new password -> Update all fields including hash
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt1 = $pdo->prepare("UPDATE users SET full_name = :name, email = :email, password_hash = :pass WHERE user_id = :id AND role = 'engineer'");
                        $stmt1->execute([':name' => $name, ':email' => $email, ':pass' => $hashed_password, ':id' => $id]);
                    } else {
                        // Admin left password blank -> Keep old password, update everything else
                        $stmt1 = $pdo->prepare("UPDATE users SET full_name = :name, email = :email WHERE user_id = :id AND role = 'engineer'");
                        $stmt1->execute([':name' => $name, ':email' => $email, ':id' => $id]);
                    }

                    $stmt2 = $pdo->prepare("UPDATE engineer_profiles SET specialty = :spec, current_status = :status WHERE engineer_id = :id");
                    $stmt2->execute([':spec' => $specialty, ':status' => $status, ':id' => $id]);

                    $pdo->commit();
                    $_SESSION['sys_msg'] = "Success: Engineer profile for $name updated successfully.";
                    $_SESSION['sys_msg_type'] = "success";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("DB Update Error: " . $e->getMessage());
                $_SESSION['sys_msg'] = "CRITICAL ERROR: Unable to update profile.";
                $_SESSION['sys_msg_type'] = "danger";
                $_SESSION['old_post'] = $_POST;
                $_SESSION['failed_action'] = $action;
            }
        }
    }

    // ---------------------------------------------------------
    // DELETE: Soft Delete Engineer (Revoke Access)
    // ---------------------------------------------------------
    elseif ($action === 'delete_engineer') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = :id AND role = 'engineer'");
                $stmt->execute([':id' => $id]);
                
                $stmt2 = $pdo->prepare("UPDATE engineer_profiles SET current_status = 'on_leave' WHERE engineer_id = :id");
                $stmt2->execute([':id' => $id]);
                $pdo->commit();

                $_SESSION['sys_msg'] = "Operative access revoked and profile deactivated.";
                $_SESSION['sys_msg_type'] = "success";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['sys_msg'] = "CRITICAL ERROR: Unable to deactivate profile.";
                $_SESSION['sys_msg_type'] = "danger";
            }
        }
    }

    // ---------------------------------------------------------
    // RESTORE: Reactivate Engineer
    // ---------------------------------------------------------
    elseif ($action === 'restore_engineer') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = :id AND role = 'engineer'");
                $stmt->execute([':id' => $id]);
                
                $stmt2 = $pdo->prepare("UPDATE engineer_profiles SET current_status = 'available' WHERE engineer_id = :id");
                $stmt2->execute([':id' => $id]);
                $pdo->commit();

                $_SESSION['sys_msg'] = "Operative access restored. Engineer is now available for deployment.";
                $_SESSION['sys_msg_type'] = "success";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['sys_msg'] = "CRITICAL ERROR: Unable to restore profile.";
                $_SESSION['sys_msg_type'] = "danger";
            }
        }
    }

    header("Location: " . $_SERVER['SCRIPT_NAME']);
    exit();
}

// Fetch Flash Messages and Sticky Inputs
$system_message = $_SESSION['sys_msg'] ?? '';
$message_type   = $_SESSION['sys_msg_type'] ?? '';
$failed_action  = $_SESSION['failed_action'] ?? '';
$old_user_id    = $_SESSION['old_post']['user_id'] ?? '';
$old_name       = $_SESSION['old_post']['eng_name'] ?? '';
$old_email      = $_SESSION['old_post']['eng_email'] ?? '';
$old_specialty  = $_SESSION['old_post']['eng_specialty'] ?? '';
$old_status     = $_SESSION['old_post']['eng_status'] ?? 'available';

unset($_SESSION['sys_msg'], $_SESSION['sys_msg_type'], $_SESSION['failed_action'], $_SESSION['old_post']);

// ==========================================
// DASHBOARD METRICS RETRIEVAL
// ==========================================
try {
    try {
        $stmt1 = $pdo->query("SELECT COUNT(*) FROM dispatch_requests WHERE status = 'pending' AND is_active = 1");
        $pending_dispatches = $stmt1->fetchColumn();
    } catch (PDOException $e) {
        $pending_dispatches = 0; 
    }

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1");
    $total_clients = $stmt2->fetchColumn();
    
    $stmt3 = $pdo->query("
        SELECT 
            SUM(CASE WHEN ep.current_status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN ep.current_status = 'deployed' THEN 1 ELSE 0 END) as deployed
        FROM engineer_profiles ep
        JOIN users u ON ep.engineer_id = u.user_id
        WHERE u.is_active = 1
    ");
    $eng_stats = $stmt3->fetch(PDO::FETCH_ASSOC);
    $available_engineers = $eng_stats['available'] ?? 0;
    $deployed_engineers  = $eng_stats['deployed'] ?? 0;

    // Fetch all engineers (including suspended) so Admin can manage/restore them
    $stmt4 = $pdo->query("
        SELECT u.user_id, u.full_name, u.email, u.is_active, ep.specialty, ep.current_status 
        FROM users u 
        JOIN engineer_profiles ep ON u.user_id = ep.engineer_id 
        WHERE u.role = 'engineer'
        ORDER BY u.is_active DESC, ep.current_status ASC, u.full_name ASC
    ");
    $engineers_list = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard Retrieval Error: " . $e->getMessage());
    $pending_dispatches  = $total_clients = "ERR";
    $available_engineers = $deployed_engineers = "ERR";
    $engineers_list      = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Config | Apex Marine</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 position-relative bg-grid-pattern font-cascadia">

    <!-- Top Navigation Command Bar -->
    <nav class="navbar shadow-lg px-4 py-3" style="background-color: rgba(13, 42, 74, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
        <div class="container-fluid">
            <!-- Left: System Identity -->
            <div class="d-flex align-items-center gap-3">
                <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill border border-warning border-opacity-25">
                    <span class="rounded-circle bg-warning me-2" style="width: 6px; height: 6px; box-shadow: 0 0 10px rgba(245, 158, 11, 0.8); animation: pulse 2s infinite;"></span>
                    <span class="fw-bold text-warning text-uppercase letter-spacing-widest" style="font-size: 10px;">SYS_CONFIG</span>
                </div>
                <h5 class="font-montserrat fw-bolder text-white mb-0 text-uppercase letter-spacing-wide">Apex Marine</h5>
            </div>
            
            <!-- Right: Minimalist Controls -->
            <div class="d-flex gap-4 align-items-center">
                <div class="d-flex gap-2">
                    <a href="active_ops.php" class="btn btn-sm btn-outline-success font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Active Ops
                    </a>
                    <a href="/apexx_marine/index.php" class="btn btn-sm btn-outline-info font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Public
                    </a>
                </div>
                <div class="vr bg-secondary opacity-25" style="width: 1px; height: 24px;"></div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary small font-cascadia">
                        ID: <strong class="text-white"><?= htmlspecialchars((string)($_SESSION['full_name'] ?? 'Admin')); ?></strong>
                    </span>
                    <a href="/apexx_marine/assets/includes/logout.php" class="btn btn-sm btn-warning font-montserrat fw-bold text-dark rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2" title="Terminate Session">
                        <span class="text-uppercase" style="font-size: 11px;">Eject</span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4 p-md-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="font-montserrat fw-bolder text-white text-uppercase">Central Command Terminal</h2>
                <p class="text-secondary">Execute administrative overrides, manage personnel, and dispatch resources.</p>
                
                <?php if (!empty($system_message)): ?>
                    <div class="alert alert-<?= htmlspecialchars((string)$message_type); ?> alert-dismissible fade show border-<?= htmlspecialchars((string)$message_type); ?> border-opacity-50 shadow-sm font-cascadia" role="alert" style="background: rgba(var(--bs-<?= htmlspecialchars((string)$message_type); ?>-rgb), 0.1);">
                        <strong>&gt; SYSTEM_LOG:</strong> <?= htmlspecialchars((string)$system_message); ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Dispatches -->
            <div class="col-md-4">
                <div class="card glass-card backdrop-blur-2xl rounded-4 shadow-lg border-0 h-100 p-4 border-top border-warning border-opacity-50 d-flex flex-column">
                    <h6 class="font-montserrat fw-bold text-warning text-uppercase mb-3">Pending Dispatches</h6>
                    <h1 class="text-white display-3 fw-bold font-cascadia mb-0"><?= htmlspecialchars((string)$pending_dispatches); ?></h1>
                    <div class="mt-auto pt-3 d-flex justify-content-between align-items-end border-top border-warning border-opacity-25">
                        <p class="small text-brand-steel mb-0 pb-1">Awaiting assignment</p>
                        <a href="pending_dispatch.php" class="btn btn-sm btn-warning font-montserrat fw-bold text-uppercase text-dark shadow-sm px-3">Assign Tasks &rarr;</a>
                    </div>
                </div>
            </div>
            
            <!-- Clients -->
            <div class="col-md-4">
                <div class="card glass-card backdrop-blur-2xl rounded-4 shadow-lg border-0 h-100 p-4 border-top border-info border-opacity-50 d-flex flex-column">
                    <h6 class="font-montserrat fw-bold text-info text-uppercase mb-3">Registered Clients</h6>
                    <h1 class="text-white display-3 fw-bold font-cascadia mb-0"><?= htmlspecialchars((string)$total_clients); ?></h1>
                    <div class="mt-auto pt-3 d-flex justify-content-between align-items-end border-top border-info border-opacity-25">
                        <p class="small text-brand-steel mb-0 pb-1">Active organizational profiles</p>
                        <a href="manage_clients.php" class="btn btn-sm btn-info font-montserrat fw-bold text-uppercase text-dark shadow-sm px-3">Manage &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Engineers Breakdown -->
            <div class="col-md-4">
                <div class="card glass-card backdrop-blur-2xl rounded-4 shadow-lg border-0 h-100 p-4 border-top border-success border-opacity-50 d-flex flex-column">
                    <h6 class="font-montserrat fw-bold text-success text-uppercase mb-3">Engineer Status</h6>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <h2 class="text-white fw-bold font-cascadia mb-0"><?= htmlspecialchars((string)$available_engineers); ?></h2>
                            <span class="text-success small text-uppercase fw-bold letter-spacing-wide">Available</span>
                        </div>
                        <div class="text-end">
                            <h2 class="text-white fw-bold font-cascadia mb-0"><?= htmlspecialchars((string)$deployed_engineers); ?></h2>
                            <span class="text-secondary small text-uppercase fw-bold letter-spacing-wide">Deployed</span>
                        </div>
                    </div>
                    <div class="mt-auto pt-3 d-flex justify-content-between align-items-end border-top border-success border-opacity-25">
                        <p class="small text-brand-steel mb-0 pb-1">Live deployment metrics</p>
                        <a href="active_ops.php" class="btn btn-sm btn-success font-montserrat fw-bold text-uppercase text-dark shadow-sm px-3">Track Ops &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engineer Data Grid -->
        <div class="row">
            <div class="col-12">
                <div class="card glass-card backdrop-blur-2xl rounded-4 shadow-lg border-0 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="font-montserrat fw-bold text-white text-uppercase mb-0 d-flex align-items-center gap-2">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-info"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Engineer Roster Management
                        </h5>
                        <button type="button" class="btn btn-outline-info font-montserrat fw-bold text-uppercase fs-7 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addEngineerModal">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                            Register Personnel
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle font-cascadia" style="background-color: transparent;">
                            <thead>
                                <tr class="text-uppercase text-secondary" style="font-size: 0.8rem; letter-spacing: 1px;">
                                    <th scope="col" class="bg-transparent border-secondary">Personnel Name</th>
                                    <th scope="col" class="bg-transparent border-secondary">Comms (Email)</th>
                                    <th scope="col" class="bg-transparent border-secondary">Specialty</th>
                                    <th scope="col" class="bg-transparent border-secondary">Status</th>
                                    <th scope="col" class="bg-transparent border-secondary text-end">Command</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($engineers_list)): ?>
                                    <tr>
                                        <td colspan="5" class="bg-transparent text-center text-secondary py-4">No engineers found in the database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($engineers_list as $eng): ?>
                                        <tr style="<?= $eng['is_active'] ? '' : 'opacity: 0.5; background: rgba(255,0,0,0.05);' ?>">
                                            <td class="bg-transparent text-white fw-bold"><?= htmlspecialchars((string)$eng['full_name']); ?></td>
                                            <td class="bg-transparent text-brand-steel"><?= htmlspecialchars((string)$eng['email']); ?></td>
                                            <td class="bg-transparent text-info small"><?= htmlspecialchars((string)$eng['specialty']); ?></td>
                                            <td class="bg-transparent">
                                                <?php if (!$eng['is_active']): ?>
                                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1">SUSPENDED</span>
                                                <?php elseif ($eng['current_status'] === 'available'): ?>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1">AVAILABLE</span>
                                                <?php elseif ($eng['current_status'] === 'deployed'): ?>
                                                    <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-50 px-2 py-1">DEPLOYED</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2 py-1">ON LEAVE</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="bg-transparent text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    
                                                    <!-- EDIT BUTTON (Triggers Edit Modal) -->
                                                    <!-- Uses ENT_QUOTES to safely pass data containing apostrophes -->
                                                    <button type="button" class="btn btn-sm btn-outline-warning font-montserrat fw-bold text-uppercase d-flex align-items-center gap-1 edit-eng-btn" 
                                                        style="font-size: 0.7rem;"
                                                        data-id="<?= htmlspecialchars((string)$eng['user_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-name="<?= htmlspecialchars((string)$eng['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-email="<?= htmlspecialchars((string)$eng['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-specialty="<?= htmlspecialchars((string)$eng['specialty'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-status="<?= htmlspecialchars((string)$eng['current_status'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                        Edit
                                                    </button>
                                                    
                                                    <!-- DELETE / RESTORE ACTIONS -->
                                                    <?php if ($eng['is_active']): ?>
                                                        <!-- DELETE BUTTON (Triggers Delete Modal) -->
                                                        <button type="button" class="btn btn-sm btn-outline-danger font-montserrat fw-bold text-uppercase d-flex align-items-center gap-1 delete-eng-btn" 
                                                            style="font-size: 0.7rem;"
                                                            data-id="<?= htmlspecialchars((string)$eng['user_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-name="<?= htmlspecialchars((string)$eng['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                            Delete
                                                        </button>
                                                    <?php else: ?>
                                                        <!-- RESTORE BUTTON (Direct Form Submission) -->
                                                        <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" class="m-0" onsubmit="return confirm('Restore this engineer to active duty?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                                                            <input type="hidden" name="action" value="restore_engineer">
                                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)$eng['user_id']); ?>">
                                                            <button type="submit" class="btn btn-sm btn-success font-montserrat fw-bold text-uppercase d-flex align-items-center gap-1 text-dark" style="font-size: 0.7rem;">
                                                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                                                Restore
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

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
        </div>

    </div>

<!-- 1. ADD ENGINEER MODAL -->
    <div class="modal fade" id="addEngineerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-secondary border-opacity-25 shadow-lg text-white font-cascadia" style="border-radius: 1.5rem; background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25 px-4 pt-4 pb-3">
                    <h5 class="modal-title font-montserrat fw-bolder text-uppercase text-brand-ocean d-flex align-items-center gap-2 fs-5">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Deploy New Engineer
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-50 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="add_engineer">
                        
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Full Name</label>
                            <input type="text" name="eng_name" required value="<?= htmlspecialchars((string)$old_name); ?>" class="form-control custom-input rounded-3 shadow-none" placeholder="Operative Designation">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Secure Email</label>
                            <input type="email" name="eng_email" required value="<?= htmlspecialchars((string)$old_email); ?>" class="form-control custom-input rounded-3 shadow-none" placeholder="engineer@apexmarine.com.ph">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Access Code (Password)</label>
                            <input type="password" name="eng_password" required minlength="8" class="form-control custom-input rounded-3 shadow-none" placeholder="••••••••">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Technical Discipline</label>
                            <select name="eng_specialty" required class="form-select custom-input rounded-3 shadow-none">
                                <option value="" style="background-color: var(--brand-navy);" <?= empty($old_specialty) ? 'selected' : ''; ?> disabled>Assign Role...</option>
                                <?php foreach ($allowed_specialties as $spec): ?>
                                    <option value="<?= htmlspecialchars((string)$spec); ?>" style="background-color: var(--brand-navy);" <?= $old_specialty === $spec ? 'selected' : ''; ?>><?= htmlspecialchars((string)$spec); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Initial Status</label>
                            <select name="eng_status" class="form-select custom-input rounded-3 shadow-none">
                                <option value="available" style="background-color: var(--brand-navy);" <?= $old_status === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="deployed" style="background-color: var(--brand-navy);" <?= $old_status === 'deployed' ? 'selected' : ''; ?>>Deployed</option>
                                <option value="on_leave" style="background-color: var(--brand-navy);" <?= $old_status === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-brand-blue w-100 py-3 rounded-3 font-montserrat fw-bolder text-uppercase letter-spacing-widest">
                            Initialize Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. EDIT ENGINEER MODAL -->
    <div class="modal fade" id="editEngineerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-secondary border-opacity-25 shadow-lg text-white font-cascadia" style="border-radius: 1.5rem; background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25 px-4 pt-4 pb-3">
                    <h5 class="modal-title font-montserrat fw-bolder text-uppercase text-brand-caution d-flex align-items-center gap-2 fs-5">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Edit Engineer Profile
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-50 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="edit_engineer">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Full Name</label>
                            <input type="text" name="eng_name" id="edit_eng_name" required class="form-control custom-input rounded-3 shadow-none">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Secure Email</label>
                            <input type="email" name="eng_email" id="edit_eng_email" required class="form-control custom-input rounded-3 shadow-none">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Update Access Code</label>
                            <input type="password" name="eng_password" minlength="8" class="form-control custom-input rounded-3 shadow-none" placeholder="Leave blank to keep current code">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Technical Discipline</label>
                            <select name="eng_specialty" id="edit_eng_specialty" required class="form-select custom-input rounded-3 shadow-none">
                                <?php foreach ($allowed_specialties as $spec): ?>
                                    <option value="<?= htmlspecialchars((string)$spec); ?>" style="background-color: var(--brand-navy);"><?= htmlspecialchars((string)$spec); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Current Status</label>
                            <select name="eng_status" id="edit_eng_status" class="form-select custom-input rounded-3 shadow-none">
                                <option value="available" style="background-color: var(--brand-navy);">Available</option>
                                <option value="deployed" style="background-color: var(--brand-navy);">Deployed</option>
                                <option value="on_leave" style="background-color: var(--brand-navy);">On Leave</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-brand-caution w-100 py-3 rounded-3 font-montserrat fw-bolder text-uppercase letter-spacing-widest">
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. EDIT ENGINEER MODAL -->
    <div class="modal fade" id="editEngineerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-secondary border-opacity-50 shadow-lg text-white font-cascadia" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title font-montserrat fw-bold text-uppercase text-warning d-flex align-items-center gap-2">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Edit Engineer Profile
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="edit_engineer">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Full Name</label>
                            <input type="text" name="eng_name" id="edit_eng_name" required class="form-control bg-dark border-secondary text-white shadow-none">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Secure Email</label>
                            <input type="email" name="eng_email" id="edit_eng_email" required class="form-control bg-dark border-secondary text-white shadow-none">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Update Access Code</label>
                            <input type="password" name="eng_password" minlength="8" class="form-control bg-dark border-secondary text-white shadow-none" placeholder="Leave blank to keep current code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Technical Discipline</label>
                            <select name="eng_specialty" id="edit_eng_specialty" required class="form-select bg-dark border-secondary text-white shadow-none">
                                <?php foreach ($allowed_specialties as $spec): ?>
                                    <option value="<?= htmlspecialchars((string)$spec); ?>" style="background-color: #0d1b2a; color: #ffffff;"><?= htmlspecialchars((string)$spec); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Current Status</label>
                            <select name="eng_status" id="edit_eng_status" class="form-select bg-dark border-secondary text-white shadow-none">
                                <option value="available" style="background-color: #0d1b2a; color: #ffffff;">Available</option>
                                <option value="deployed" style="background-color: #0d1b2a; color: #ffffff;">Deployed</option>
                                <option value="on_leave" style="background-color: #0d1b2a; color: #ffffff;">On Leave</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 py-2 rounded-3 font-montserrat fw-bold text-uppercase shadow-sm text-dark">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. DELETE (DEACTIVATE) CONFIRMATION MODAL -->
    <div class="modal fade" id="deleteEngineerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-danger border-opacity-50 shadow-lg text-white font-cascadia" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-body p-4 text-center">
                    <svg width="48" height="48" class="text-danger mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <h5 class="font-montserrat fw-bold text-uppercase text-danger mb-3">Revoke Operative Access?</h5>
                    <p class="text-secondary small mb-4">This will deactivate <strong id="delete_eng_name_display" class="text-white"></strong>'s account and set their status to 'On Leave'.</p>
                    
                    <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" class="d-flex gap-2 justify-content-center">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="delete_engineer">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        
                        <button type="button" class="btn btn-outline-secondary font-montserrat fw-bold text-uppercase fs-7" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger font-montserrat fw-bold text-uppercase fs-7 text-white">Confirm Revoke</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
    </style>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>

    <!-- Handle Dynamic Modal Data & Auto-open errors -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Contextually Auto-open the Correct modal if there was a form error
            <?php if (in_array($message_type, ['warning', 'danger'], true)): ?>
                <?php if (($failed_action ?? 'add_engineer') === 'add_engineer' && (!empty($old_name) || !empty($old_email))): ?>
                    var addModal = new bootstrap.Modal(document.getElementById('addEngineerModal'));
                    addModal.show();
                <?php elseif (($failed_action ?? '') === 'edit_engineer'): ?>
                    document.getElementById('edit_user_id').value = <?= json_encode((string)$old_user_id) ?>;
                    document.getElementById('edit_eng_name').value = <?= json_encode((string)$old_name) ?>;
                    document.getElementById('edit_eng_email').value = <?= json_encode((string)$old_email) ?>;
                    document.getElementById('edit_eng_specialty').value = <?= json_encode((string)$old_specialty) ?>;
                    document.getElementById('edit_eng_status').value = <?= json_encode((string)$old_status) ?>;
                    
                    var editModal = new bootstrap.Modal(document.getElementById('editEngineerModal'));
                    editModal.show();
                <?php endif; ?>
            <?php endif; ?>

            // Populate Edit Modal Data
            const editButtons = document.querySelectorAll('.edit-eng-btn');
            editButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_user_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_eng_name').value = this.getAttribute('data-name');
                    document.getElementById('edit_eng_email').value = this.getAttribute('data-email');
                    document.getElementById('edit_eng_specialty').value = this.getAttribute('data-specialty');
                    document.getElementById('edit_eng_status').value = this.getAttribute('data-status');
                    
                    var editModal = new bootstrap.Modal(document.getElementById('editEngineerModal'));
                    editModal.show();
                });
            });

            // Populate Delete Modal Data
            const deleteButtons = document.querySelectorAll('.delete-eng-btn');
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_user_id').value = this.getAttribute('data-id');
                    document.getElementById('delete_eng_name_display').textContent = this.getAttribute('data-name');
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteEngineerModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>