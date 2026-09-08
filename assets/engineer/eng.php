<?php
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

require_once '../../db/db.php'; 

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$engineer_id = (int)$_SESSION['user_id'];
$engineer_name = $_SESSION['full_name'] ?? 'Engineer';

// ---------------------------------------------------------
// HANDLE FORM SUBMISSION: Engineer Updates Status
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_task_status') {
    
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['sys_msg'] = "Security token validation failed.";
        $_SESSION['sys_msg_type'] = "danger";
    } else {
        $request_id = (int)$_POST['request_id'];
        $new_status = trim($_POST['new_status']); // e.g., 'in_progress', 'resolved'
        $milestone  = trim($_POST['milestone']); // e.g., 'Engineer On Site', 'Repairs Completed'
        $details    = trim($_POST['details']);

        if (empty($request_id) || empty($new_status) || empty($milestone) || empty($details)) {
            $_SESSION['sys_msg'] = "All log fields are required to update operations.";
            $_SESSION['sys_msg_type'] = "warning";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Update the overall request status
                $stmt1 = $pdo->prepare("UPDATE dispatch_requests SET status = :status WHERE request_id = :req_id");
                $stmt1->execute([':status' => $new_status, ':req_id' => $request_id]);

                // 2. Insert into operation_updates for the Client Timeline
                $stmt2 = $pdo->prepare("INSERT INTO operation_updates (request_id, status_milestone, detailed_message) VALUES (:req_id, :milestone, :details)");
                $stmt2->execute([
                    ':req_id'    => $request_id,
                    ':milestone' => $milestone,
                    ':details'   => $details
                ]);

                // 3. If resolved, free up the engineer
                if ($new_status === 'resolved') {
                    $stmt3 = $pdo->prepare("UPDATE engineer_profiles SET current_status = 'available' WHERE engineer_id = :eng_id");
                    $stmt3->execute([':eng_id' => $engineer_id]);
                    
                    $stmt4 = $pdo->prepare("UPDATE deployments SET deployment_status = 'completed' WHERE request_id = :req_id AND engineer_id = :eng_id");
                    $stmt4->execute([':req_id' => $request_id, ':eng_id' => $engineer_id]);
                } else {
                    // Otherwise just update deployment status to active
                    $stmt4 = $pdo->prepare("UPDATE deployments SET deployment_status = 'active' WHERE request_id = :req_id AND engineer_id = :eng_id");
                    $stmt4->execute([':req_id' => $request_id, ':eng_id' => $engineer_id]);
                }

                $pdo->commit();
                $_SESSION['sys_msg'] = "Operational log updated successfully.";
                $_SESSION['sys_msg_type'] = "success";

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Task Update Error: " . $e->getMessage());
                $_SESSION['sys_msg'] = "CRITICAL FAULT: Unable to log update.";
                $_SESSION['sys_msg_type'] = "danger";
            }
        }
    }
    header("Location: " . $_SERVER['SCRIPT_NAME']);
    exit();
}

// ---------------------------------------------------------
// FETCH ENGINEER'S ACTIVE DEPLOYMENTS
// ---------------------------------------------------------
$tasks = [];
try {
    // Join deployments with dispatch_requests to get full vessel details
    $stmt = $pdo->prepare("
        SELECT 
            d.deployment_id, d.deployment_status, 
            r.request_id, r.vessel_name, r.company, r.description, r.status, r.requested_at
        FROM deployments d
        JOIN dispatch_requests r ON d.request_id = r.request_id
        WHERE d.engineer_id = :eng_id 
          AND d.deployment_status != 'completed'
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([':eng_id' => $engineer_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Task Fetch Error: " . $e->getMessage());
}

$total_tasks = count($tasks);
$pending_tasks = count(array_filter($tasks, fn($t) => $t['status'] === 'deployed')); // Admin just assigned it
$in_progress = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));

// Check for session messages
$sys_msg = $_SESSION['sys_msg'] ?? null;
$sys_msg_type = $_SESSION['sys_msg_type'] ?? 'info';
unset($_SESSION['sys_msg'], $_SESSION['sys_msg_type']);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Field Operations | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-navy text-brand-steel min-vh-100 bg-grid-pattern font-cascadia position-relative overflow-x-hidden">

    <!-- Ambient Glows -->
    <div class="glow-blob glow-blob-blue pointer-events-none position-fixed" style="top: -100px; left: -100px; width: 400px; height: 400px; z-index: 0;"></div>
    <div class="glow-blob glow-blob-ocean pointer-events-none position-fixed" style="bottom: 10%; right: -5%; width: 500px; height: 500px; opacity: 0.5; z-index: 0;"></div>

    <!-- Minimalist Engineer Navigation Bar -->
    <nav class="navbar shadow-lg px-4 py-3 position-relative z-1" style="background-color: rgba(13, 27, 42, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(251, 191, 36, 0.2);">
        <div class="container-fluid">
            
            <div class="d-flex align-items-center gap-3">
                <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill border border-warning border-opacity-25">
                    <span class="rounded-circle bg-warning me-2" style="width: 6px; height: 6px; box-shadow: 0 0 10px rgba(251, 191, 36, 0.8); animation: pulse-warning 2s infinite;"></span>
                    <span class="fw-bold text-warning text-uppercase letter-spacing-widest" style="font-size: 10px;">FIELD_OPS</span>
                </div>
                <h5 class="font-montserrat fw-bolder text-white mb-0 text-uppercase letter-spacing-wide">Apex Marine</h5>
            </div>

            <div class="d-flex gap-4 align-items-center">
                <div class="d-flex gap-2">
                    <a href="eng.php" class="btn btn-sm btn-outline-warning font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50 text-white hover-white">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh Feed
                    </a>
                    <a href="/apexx_marine/index.php" class="btn btn-sm btn-outline-light font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 001 1m-6 0h6"></path></svg>
                        Home
                    </a>
                </div>

                <div class="vr bg-secondary opacity-25" style="width: 1px; height: 24px;"></div>

                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary small font-cascadia">
                        ENG: <strong class="text-white"><?= htmlspecialchars($engineer_name); ?></strong>
                    </span>
                    <a href="/apexx_marine/assets/includes/logout.php" class="btn btn-sm btn-danger font-montserrat fw-bold text-white rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2" title="Terminate Session">
                        <span class="text-uppercase" style="font-size: 11px;">Log Out</span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Array -->
    <div class="container p-4 p-md-5 position-relative z-1">
        
        <div class="row mb-5 align-items-end">
            <div class="col-lg-8">
                <h2 class="text-white font-montserrat fw-bolder text-uppercase letter-spacing-wide mb-2">
                    Active Deployment Protocol
                </h2>
                <p class="text-brand-steel fw-light mb-0" style="font-size: 1.05rem;">
                    Review your assigned technical interventions and log updates directly to the client timeline.
                </p>
            </div>
            
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="glass-card p-3 rounded-4 d-flex justify-content-around backdrop-blur-xl border border-secondary border-opacity-25">
                    <div class="text-center">
                        <span class="d-block text-white font-cascadia fw-bold fs-4"><?= $total_tasks; ?></span>
                        <span class="text-brand-steel text-uppercase letter-spacing-wide" style="font-size: 0.65rem;">Assigned</span>
                    </div>
                    <div class="border-end border-secondary border-opacity-25"></div>
                    <div class="text-center">
                        <span class="d-block text-brand-caution font-cascadia fw-bold fs-4"><?= $pending_tasks; ?></span>
                        <span class="text-brand-steel text-uppercase letter-spacing-wide" style="font-size: 0.65rem;">En Route</span>
                    </div>
                    <div class="border-end border-secondary border-opacity-25"></div>
                    <div class="text-center">
                        <span class="d-block text-brand-blue font-cascadia fw-bold fs-4"><?= $in_progress; ?></span>
                        <span class="text-brand-steel text-uppercase letter-spacing-wide" style="font-size: 0.65rem;">Active</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($sys_msg): ?>
            <div class="alert alert-<?= $sys_msg_type; ?> glass-card border-<?= $sys_msg_type; ?> text-white mb-4 rounded-3 d-flex align-items-center">
                <svg width="20" height="20" class="me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= $sys_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Task Grid -->
        <div class="row g-4">
            <?php if (empty($tasks)): ?>
                <div class="col-12 text-center py-5">
                    <svg width="64" height="64" class="mx-auto mb-3 text-secondary opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <h5 class="text-white font-montserrat fw-bold text-uppercase">No Active Assignments</h5>
                    <p class="text-secondary font-cascadia">You are currently cleared of all tasks. Standby for deployment orders.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="glass-card p-4 rounded-4 h-100 d-flex flex-column border border-secondary border-opacity-25 shadow-lg" style="background: rgba(13, 27, 42, 0.4);">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge border border-info text-info bg-info bg-opacity-10 text-uppercase letter-spacing-wide px-2 py-1 font-cascadia" style="font-size: 0.6rem;">
                                    REQ-<?= str_pad($task['request_id'], 4, '0', STR_PAD_LEFT); ?>
                                </span>
                                
                                <?php 
                                    $status_color = $task['status'] === 'in_progress' ? '#3b82f6' : '#fbbf24';
                                    $status_label = $task['status'] === 'in_progress' ? 'ON SITE / ACTIVE' : 'EN ROUTE';
                                ?>
                                <div class="d-flex align-items-center" style="font-size: 0.75rem; color: <?= $status_color; ?>;">
                                    <span class="rounded-circle me-2" style="width: 6px; height: 6px; background-color: <?= $status_color; ?>; box-shadow: 0 0 6px <?= $status_color; ?>;"></span>
                                    <span class="font-montserrat fw-bold text-uppercase"><?= $status_label; ?></span>
                                </div>
                            </div>

                            <h4 class="text-white font-montserrat fw-bold mb-1 fs-5"><?= htmlspecialchars($task['vessel_name']); ?></h4>
                            <p class="text-brand-ocean font-cascadia mb-3" style="font-size: 0.8rem;">
                                <?= htmlspecialchars($task['company'] ?? 'Private Client'); ?>
                            </p>
                            
                            <p class="text-brand-steel mb-4 flex-grow-1" style="font-size: 0.85rem; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($task['description'])); ?>
                            </p>

                            <div class="border-top border-secondary border-opacity-25 pt-3 mt-auto d-flex justify-content-between align-items-center">
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    <span class="text-uppercase letter-spacing-wide d-block mb-1">Time Logged</span>
                                    <span class="font-cascadia text-light">
                                        <?= date('M d, Y - H:i', strtotime($task['requested_at'])); ?>
                                    </span>
                                </div>
                                
                                <button type="button" 
                                        class="btn btn-sm btn-outline-warning font-montserrat fw-bold text-uppercase letter-spacing-wide shadow-none log-update-btn" 
                                        style="font-size: 0.7rem;"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#logUpdateModal"
                                        data-reqid="<?= htmlspecialchars($task['request_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-vessel="<?= htmlspecialchars($task['vessel_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-status="<?= htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Update Log
                                </button>
                            </div>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- UPDATE LOG MODAL -->
    <div class="modal fade" id="logUpdateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card backdrop-blur-2xl border-secondary border-opacity-25 shadow-lg text-white font-cascadia" style="border-radius: 1.5rem; background: rgba(13, 27, 42, 0.95);">
                <div class="modal-header border-bottom border-secondary border-opacity-25 px-4 pt-4 pb-3">
                    <h5 class="modal-title font-montserrat fw-bolder text-uppercase text-warning d-flex align-items-center gap-2 fs-5">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
                        Submit Operations Log
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-50 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="update_task_status">
                        <input type="hidden" name="request_id" id="modal_req_id">
                        
                        <div class="mb-4 text-center">
                            <span class="text-brand-steel text-uppercase letter-spacing-widest" style="font-size: 0.7rem;">Target Vessel</span>
                            <h4 id="modal_vessel_name" class="font-montserrat fw-bold text-white mt-1 mb-0"></h4>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">New Operational Status</label>
                            <select name="new_status" id="modal_status" required class="form-select custom-input rounded-3 shadow-none">
                                <option value="pending" style="background-color: var(--brand-navy);">Pending (Awaiting Action)</option>
                                <option value="acknowledged" style="background-color: var(--brand-navy);">Acknowledged (Reviewed)</option>
                                <option value="deployed" style="background-color: var(--brand-navy);">Deployed (En Route)</option>
                                <option value="in_progress" style="background-color: var(--brand-navy);">In Progress (On Site)</option>
                                <option value="resolved" style="background-color: var(--brand-navy);">Resolved / Completed</option>
                                <option value="cancelled" style="background-color: var(--brand-navy);">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Status Milestone (Short Summary)</label>
                            <input type="text" name="milestone" required class="form-control custom-input rounded-3 shadow-none" placeholder="e.g. Engineer Arrived on Site">
                        </div>

                        <div class="mb-5">
                            <label class="form-label text-brand-steel fw-bold text-uppercase letter-spacing-widest mb-2" style="font-size: 0.65rem;">Detailed Log Message (Visible to Client)</label>
                            <textarea name="details" rows="3" required class="form-control custom-input rounded-3 shadow-none" placeholder="Provide technical details regarding the current state of repairs..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-brand-caution w-100 py-3 rounded-3 font-montserrat fw-bolder text-uppercase letter-spacing-widest">
                            Transmit Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes pulse-warning {
            0% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(251, 191, 36, 0); }
            100% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0); }
        }
        .hover-white:hover { color: white !important; }
        .bg-grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 30px 30px;
        }
    </style>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script to populate the modal with the correct Task Data when clicked
        document.addEventListener('DOMContentLoaded', function() {
            const updateButtons = document.querySelectorAll('.log-update-btn');
            const reqIdInput = document.getElementById('modal_req_id');
            const vesselNameText = document.getElementById('modal_vessel_name');
            const statusSelect = document.getElementById('modal_status');

            updateButtons.forEach(button => {
                button.addEventListener('click', function() {
                    reqIdInput.value = this.getAttribute('data-reqid');
                    vesselNameText.textContent = this.getAttribute('data-vessel');
                    
                    // Automatically set the dropdown to the task's current status
                    const currentStatus = this.getAttribute('data-status');
                    if (currentStatus) {
                        statusSelect.value = currentStatus;
                    }
                });
            });
        });
    </script>
</body>
</html>