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
// FORM PROCESSOR 1: Assign Engineer (Transaction)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'assign_engineer') {
    
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['sys_msg'] = "Security token validation failed.";
        $_SESSION['sys_msg_type'] = "danger";
    } else {
        $request_id = (int) $_POST['request_id'];
        $engineer_id = (int) $_POST['engineer_id'];
        $admin_id = $_SESSION['user_id'];

        if (empty($request_id) || empty($engineer_id)) {
            $_SESSION['sys_msg'] = "Invalid assignment parameters. Please select a valid engineer.";
            $_SESSION['sys_msg_type'] = "warning";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. PREVENT DUPLICATES: Check if request is already assigned
                $check = $pdo->prepare("SELECT deployment_id FROM deployments WHERE request_id = :req_id LIMIT 1");
                $check->execute([':req_id' => $request_id]);

                if ($check->fetch()) {
                    $pdo->rollBack();
                    $_SESSION['sys_msg'] = "Duplicate Blocked: This request has already been assigned to an engineer.";
                    $_SESSION['sys_msg_type'] = "warning";
                } else {
                    // 2. Perform dispatch insert
                    $stmt1 = $pdo->prepare("INSERT INTO deployments (request_id, admin_id, engineer_id, deployment_status) VALUES (:req, :adm, :eng, 'en_route')");
                    $stmt1->execute([':req' => $request_id, ':adm' => $admin_id, ':eng' => $engineer_id]);

                    // 3. Update dispatch_requests status
                    $stmt2 = $pdo->prepare("UPDATE dispatch_requests SET status = 'deployed' WHERE request_id = :req");
                    $stmt2->execute([':req' => $request_id]);

                    // 4. Update engineer status
                    $stmt3 = $pdo->prepare("UPDATE engineer_profiles SET current_status = 'deployed' WHERE engineer_id = :eng");
                    $stmt3->execute([':eng' => $engineer_id]);

                    $pdo->commit();
                    $_SESSION['sys_msg'] = "Deployment Authorized: Engineer dispatched to Request #$request_id.";
                    $_SESSION['sys_msg_type'] = "success";
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Deployment Assignment Error: " . $e->getMessage());
                $_SESSION['sys_msg'] = "CRITICAL FAULT: Database rejected the deployment.";
                $_SESSION['sys_msg_type'] = "danger";
            }
        }
    }
    header("Location: " . $_SERVER['SCRIPT_NAME']);
    exit();
}

// ==========================================
// FORM PROCESSOR 2: Finish or Terminate Task
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_task_status') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['sys_msg'] = "Security token validation failed.";
        $_SESSION['sys_msg_type'] = "danger";
    } else {
        $request_id = (int) $_POST['request_id'];
        $new_status = $_POST['new_status'] ?? ''; // expects 'resolved' or 'cancelled'

        if (in_array($new_status, ['resolved', 'cancelled'], true)) {
            try {
                $stmt = $pdo->prepare("UPDATE dispatch_requests SET status = :status WHERE request_id = :req");
                $stmt->execute([':status' => $new_status, ':req' => $request_id]);
                
                $status_msg = $new_status === 'resolved' ? 'Finished/Resolved' : 'Terminated/Cancelled';
                $_SESSION['sys_msg'] = "Task #$request_id has been successfully marked as $status_msg.";
                $_SESSION['sys_msg_type'] = $new_status === 'resolved' ? "success" : "warning";
                
            } catch (PDOException $e) {
                error_log("Task Update Error: " . $e->getMessage());
                $_SESSION['sys_msg'] = "CRITICAL FAULT: Unable to modify task status.";
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
// DATA RETRIEVAL
// ==========================================
try {
    $pending_sql = "
        SELECT 
            d.request_id, d.vessel_name, d.imo_number, d.vessel_type, 
            d.company, d.contact_person, d.contact_phone, d.description,
            d.service_type, d.is_urgent, d.eta_date, d.requested_at, d.attachment_path,
            u.full_name AS client_name, u.email AS client_email,
            l.port_name, h.hub_name
        FROM dispatch_requests d
        LEFT JOIN users u ON d.client_id = u.user_id
        LEFT JOIN service_locations l ON d.location_id = l.location_id
        LEFT JOIN operational_hubs h ON l.hub_id = h.hub_id
        WHERE d.status IN ('pending', 'acknowledged') AND d.is_active = 1
        ORDER BY d.is_urgent DESC, d.requested_at ASC
    ";
    $pending_requests = $pdo->query($pending_sql)->fetchAll(PDO::FETCH_ASSOC);

    $eng_sql = "
        SELECT u.user_id, u.full_name, ep.specialty 
        FROM users u 
        JOIN engineer_profiles ep ON u.user_id = ep.engineer_id 
        WHERE ep.current_status = 'available' AND u.is_active = 1
        ORDER BY ep.specialty ASC, u.full_name ASC
    ";
    $available_engineers = $pdo->query($eng_sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pending_requests = [];
    $available_engineers = [];
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Assignments | Apex Marine</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 position-relative bg-grid-pattern font-cascadia">

    <nav class="navbar shadow-lg px-4 py-3" style="background-color: rgba(13, 42, 74, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(14, 165, 233, 0.3);">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3">
                <a href="admin.php" class="btn btn-outline-secondary rounded-pill btn-sm d-flex align-items-center gap-2">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                    Back to Matrix
                </a>
                <h4 class="font-montserrat fw-bolder text-white mb-0 text-uppercase letter-spacing-wide ms-2">Task Assignment</h4>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-white small">Active Available Engineers: <strong class="text-success"><?= count($available_engineers); ?></strong></span>
            </div>
        </div>
    </nav>

    <div class="container p-4 p-md-5">
        
        <?php if (!empty($system_message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type); ?> alert-dismissible fade show font-cascadia shadow-sm" role="alert">
                <strong>&gt; SYSTEM_LOG:</strong> <?= htmlspecialchars($system_message); ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4 align-items-center">
            <div class="col-lg-8">
                <h2 class="font-montserrat fw-bolder text-white text-uppercase mb-1">Awaiting Deployment</h2>
                <p class="text-secondary small">Review incoming dispatches and assign highly specialized personnel.</p>
            </div>
        </div>

        <?php if (empty($pending_requests)): ?>
            <div class="card glass-card backdrop-blur-2xl rounded-4 border-0 p-5 text-center mt-4">
                <svg width="64" height="64" class="mx-auto mb-3 text-success opacity-75" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h4 class="font-montserrat fw-bold text-white text-uppercase">Sector Clear</h4>
                <p class="text-secondary font-cascadia">No pending dispatches detected in the system.</p>
                <a href="admin.php" class="btn btn-outline-info mt-3 font-montserrat fw-bold text-uppercase fs-7">Return to Dash</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($pending_requests as $req): ?>
                    <div class="col-12 col-xl-6">
                        <div class="card glass-card backdrop-blur-2xl rounded-4 border-0 h-100 <?= $req['is_urgent'] ? 'border-start border-4 border-danger' : 'border-start border-4 border-info' ?>" style="background: rgba(13, 27, 42, 0.6);">
                            <div class="card-body p-4 d-flex flex-column">
                                
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge bg-brand-ocean text-dark font-cascadia mb-2">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                        <?php if ($req['is_urgent']): ?>
                                            <span class="badge bg-danger ms-2 animation-pulse">CRITICAL</span>
                                        <?php endif; ?>
                                        <h4 class="font-montserrat fw-bolder text-white mb-0 text-uppercase"><?= htmlspecialchars($req['vessel_name'] ?? 'Unknown Vessel'); ?></h4>
                                        <span class="text-secondary small font-cascadia">IMO: <?= htmlspecialchars($req['imo_number']); ?> | <?= htmlspecialchars($req['vessel_type']); ?></span>
                                    </div>
                                    <div class="text-end text-brand-steel small font-cascadia">
                                        Requested:<br><?= date('M d, H:i', strtotime($req['requested_at'])); ?>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4 font-cascadia text-brand-steel small p-3 rounded-3" style="background: rgba(255,255,255,0.02);">
                                    <div class="col-6">
                                        <span class="text-uppercase text-secondary" style="font-size: 0.65rem;">Client / Company</span><br>
                                        <strong class="text-white"><?= htmlspecialchars($req['client_name'] ?? 'Unlinked'); ?></strong>
                                        <?php if (!empty($req['company'])): ?>
                                            <br><span class="text-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($req['company']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-uppercase text-secondary" style="font-size: 0.65rem;">On-Site Contact</span><br>
                                        <strong class="text-white"><?= htmlspecialchars($req['contact_person']); ?></strong>
                                        <br><span class="text-info" style="font-size: 0.7rem;"><?= htmlspecialchars($req['contact_phone']); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-uppercase text-secondary" style="font-size: 0.65rem;">Location Hub</span><br>
                                        <strong class="text-info">
                                            <?= htmlspecialchars($req['port_name'] ?? 'Unknown'); ?> 
                                            <?= !empty($req['hub_name']) ? '(' . htmlspecialchars($req['hub_name']) . ')' : ''; ?>
                                        </strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-uppercase text-secondary" style="font-size: 0.65rem;">Discipline Required</span><br>
                                        <strong class="text-warning"><?= htmlspecialchars($req['service_type']); ?></strong>
                                    </div>
                                    <div class="col-12 mt-3 pt-3 border-top border-secondary border-opacity-25">
                                        <span class="text-uppercase text-secondary mb-1 d-block" style="font-size: 0.65rem;">Fault Description</span>
                                        <p class="mb-0 text-white" style="font-size: 0.8rem; line-height: 1.4;"><?= nl2br(htmlspecialchars($req['description'])); ?></p>
                                        
                                        <?php if (!empty($req['attachment_path'])): ?>
                                            <a href="<?= htmlspecialchars($req['attachment_path']); ?>" target="_blank" class="badge bg-secondary bg-opacity-25 text-info text-decoration-none mt-2 d-inline-block border border-info border-opacity-25">
                                                <svg width="12" height="12" class="me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                View Attached Diagnostic
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Footer: Command Buttons -->
                                <div class="mt-auto pt-2 d-flex flex-column gap-2">
                                    <!-- Primary Action: Deploy -->
                                    <button class="btn <?= $req['is_urgent'] ? 'btn-danger' : 'btn-info text-dark' ?> w-100 py-2 font-montserrat fw-bold text-uppercase fs-7 shadow-sm" 
                                            onclick="openAssignmentModal(<?= $req['request_id']; ?>, '<?= htmlspecialchars(addslashes($req['vessel_name'])); ?>', '<?= htmlspecialchars(addslashes($req['service_type'])); ?>')">
                                        Assign Personnel & Deploy
                                    </button>
                                    
                                    <!-- Secondary Actions: Terminate & Finish -->
                                    <div class="d-flex gap-2">
                                        <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" class="w-50 m-0" onsubmit="return confirm('WARNING: Are you sure you want to terminate this request?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_task_status">
                                            <input type="hidden" name="request_id" value="<?= $req['request_id']; ?>">
                                            <input type="hidden" name="new_status" value="cancelled">
                                            <button type="submit" class="btn btn-outline-danger w-100 py-1 font-montserrat fw-bold text-uppercase shadow-sm" style="font-size: 0.65rem;">
                                                Terminate Task
                                            </button>
                                        </form>

                                        <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" class="w-50 m-0" onsubmit="return confirm('Confirm this dispatch has been resolved out-of-band?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_task_status">
                                            <input type="hidden" name="request_id" value="<?= $req['request_id']; ?>">
                                            <input type="hidden" name="new_status" value="resolved">
                                            <button type="submit" class="btn btn-outline-success w-100 py-1 font-montserrat fw-bold text-uppercase shadow-sm" style="font-size: 0.65rem;">
                                                Finish / Resolve
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ASSIGNMENT MODAL -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-secondary border-opacity-50 shadow-lg text-white font-cascadia" style="background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title font-montserrat fw-bold text-uppercase text-info">
                        Authorize Deployment
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-secondary mb-4">You are assigning an engineer to <strong class="text-white" id="modalVesselName"></strong>. The requested discipline is <strong class="text-warning" id="modalReqSpecialty"></strong>.</p>
                    
                    <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="assign_engineer">
                        <input type="hidden" name="request_id" id="modalRequestId" value="">
                        
                        <div class="mb-4">
                            <label class="form-label text-brand-steel small text-uppercase fw-bold letter-spacing-wide">Select Available Engineer</label>
                            
                            <?php if (empty($available_engineers)): ?>
                                <div class="alert alert-danger small p-2 text-center">NO ENGINEERS AVAILABLE ON ROSTER</div>
                            <?php else: ?>
                                <select name="engineer_id" required class="form-select bg-dark border-secondary text-white shadow-none" size="6">
                                    <?php foreach ($available_engineers as $eng): ?>
                                        <option value="<?= $eng['user_id']; ?>" class="p-2 border-bottom border-secondary border-opacity-25">
                                            <?= htmlspecialchars($eng['full_name']); ?> — [ <?= htmlspecialchars($eng['specialty']); ?> ]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-warning w-100 py-3 rounded-3 font-montserrat fw-bold text-uppercase shadow-sm text-dark" <?= empty($available_engineers) ? 'disabled' : '' ?>>
                            Confirm Dispatch Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animation-pulse {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { opacity: 0.6; box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { opacity: 1; box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    </style>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function openAssignmentModal(reqId, vesselName, specialty) {
            document.getElementById('modalRequestId').value = reqId;
            document.getElementById('modalVesselName').innerText = vesselName;
            document.getElementById('modalReqSpecialty').innerText = specialty;
            
            var assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
            assignModal.show();
        }
    </script>
</body>
</html>