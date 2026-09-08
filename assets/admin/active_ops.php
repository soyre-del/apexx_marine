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
// FORM PROCESSORS
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['sys_msg'] = "Security validation failed.";
        $_SESSION['sys_msg_type'] = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        $request_id = (int) $_POST['request_id'];
        $admin_id = $_SESSION['user_id'];

        if ($action === 'add_update') {
            // POST A LIVE UPDATE & SYNC ALL TABLES
            $milestone = trim($_POST['milestone']);
            $message = trim($_POST['detailed_message']);
            $new_status = $_POST['new_status']; // 'pending', 'acknowledged', 'deployed', 'in_progress', 'resolved', 'cancelled'
            $engineer_id = (int) $_POST['engineer_id'];

            // Map the dispatch status to the deployment table's ENUM safely
            $deployment_status = 'active'; // Fallback for general active tasks
            if ($new_status === 'deployed') $deployment_status = 'en_route';
            if ($new_status === 'in_progress') $deployment_status = 'on_site';
            if ($new_status === 'resolved') $deployment_status = 'completed';
            if ($new_status === 'cancelled') $deployment_status = 'completed'; // Free engineer if cancelled

            try {
                $pdo->beginTransaction();
                
                // 1. Log the timeline update
                $stmt1 = $pdo->prepare("INSERT INTO operation_updates (request_id, updater_id, status_milestone, detailed_message) VALUES (:req, :upd, :mile, :msg)");
                $stmt1->execute([':req' => $request_id, ':upd' => $admin_id, ':mile' => $milestone, ':msg' => $message]);
                
                // 2. Update overall dispatch status
                $stmt2 = $pdo->prepare("UPDATE dispatch_requests SET status = :stat WHERE request_id = :req");
                $stmt2->execute([':stat' => $new_status, ':req' => $request_id]);

                // 3. Sync the deployments table
                $stmt3 = $pdo->prepare("UPDATE deployments SET deployment_status = :dstat WHERE request_id = :req AND engineer_id = :eng");
                $stmt3->execute([':dstat' => $deployment_status, ':req' => $request_id, ':eng' => $engineer_id]);

                // 4. Free the engineer if the task is finished or cancelled
                if ($new_status === 'resolved' || $new_status === 'cancelled') {
                    $stmt4 = $pdo->prepare("UPDATE engineer_profiles SET current_status = 'available' WHERE engineer_id = :eng");
                    $stmt4->execute([':eng' => $engineer_id]);
                    
                    $_SESSION['sys_msg'] = "Mission Accomplished: Request #$request_id $new_status. Engineer freed.";
                    $_SESSION['sys_msg_type'] = "success";
                } else {
                    $_SESSION['sys_msg'] = "Timeline Updated for Request #$request_id.";
                    $_SESSION['sys_msg_type'] = "success";
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Active Ops Update Error: " . $e->getMessage());
                $_SESSION['sys_msg'] = "Database Error: Unable to sync operational update.";
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
// FETCH ACTIVE OPERATIONS
// ==========================================
try {
    // FIXED SQL: Look for all deployments that are NOT completed, rather than strictly checking dispatch status.
    // This catches everything the engineer touches as long as they haven't resolved it.
    $ops_sql = "
        SELECT
            d.request_id, d.vessel_name, d.status, d.is_urgent,
            e.full_name AS eng_name, ep.engineer_id
        FROM dispatch_requests d
        JOIN deployments dep ON d.request_id = dep.request_id
        JOIN users e ON dep.engineer_id = e.user_id
        JOIN engineer_profiles ep ON e.user_id = ep.engineer_id
        WHERE dep.deployment_status != 'completed'
        AND d.status NOT IN ('resolved', 'cancelled')
        GROUP BY d.request_id, dep.engineer_id
        ORDER BY d.is_urgent DESC, d.requested_at DESC
    ";
    $active_ops = $pdo->query($ops_sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch Ops Error: " . $e->getMessage());
    $active_ops = [];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Active Ops | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 bg-grid-pattern font-cascadia p-4">

    <a href="admin.php" class="btn btn-outline-info mb-4 text-uppercase font-montserrat fw-bold">&larr; Return to Matrix</a>
    <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4">Active Operations Tracker</h2>

    <?php if (!empty($system_message)): ?>
        <div class="alert alert-<?= $message_type; ?> border-<?= $message_type; ?> border-opacity-50 shadow-sm">
            <strong>&gt; SYSTEM_LOG:</strong> <?= htmlspecialchars($system_message); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (empty($active_ops)): ?>
            <div class="col-12">
                <div class="card glass-card backdrop-blur-2xl border-secondary border-opacity-25 p-5 text-center">
                    <h4 class="text-white font-montserrat fw-bold text-uppercase">No Active Deployments</h4>
                    <p class="text-secondary font-cascadia">All personnel are currently on standby or pending assignment.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($active_ops as $op): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card glass-card backdrop-blur-2xl border-info border-opacity-50 p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="text-white font-montserrat fw-bold text-uppercase m-0"><?= htmlspecialchars($op['vessel_name']); ?></h4>
                            <span class="badge bg-brand-ocean text-dark font-cascadia">REQ-<?= str_pad($op['request_id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <p class="text-info mb-3 small text-uppercase letter-spacing-wide">Assigned: <strong><?= htmlspecialchars($op['eng_name']); ?></strong></p>
                        
                        <!-- Unified Action Form -->
                        <form action="" method="POST" class="mt-auto pt-3 border-top border-secondary border-opacity-25" onsubmit="return confirmStatusChange(event, this);">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="add_update">
                            <input type="hidden" name="request_id" value="<?= $op['request_id']; ?>">
                            <input type="hidden" name="engineer_id" value="<?= $op['engineer_id']; ?>">
                            
                            <input type="text" name="milestone" class="form-control bg-dark border-secondary text-white shadow-none mb-2" placeholder="Milestone (e.g., Arrived, Part Replaced, Fixed)" required>
                            <textarea name="detailed_message" class="form-control bg-dark border-secondary text-white shadow-none mb-3" placeholder="Detailed diagnostic or action log..." rows="2" required></textarea>
                            
                            <div class="d-flex flex-column flex-xxl-row gap-2">
                                <select name="new_status" class="form-select bg-dark border-secondary text-white shadow-none status-selector">
                                    <option value="pending" <?= $op['status'] === 'pending' ? 'selected' : '' ?>>Pending (Awaiting Action)</option>
                                    <option value="acknowledged" <?= $op['status'] === 'acknowledged' ? 'selected' : '' ?>>Acknowledged (Reviewed)</option>
                                    <option value="deployed" <?= $op['status'] === 'deployed' ? 'selected' : '' ?>>Deployed (En Route)</option>
                                    <option value="in_progress" <?= $op['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress (Working)</option>
                                    <option value="resolved" class="text-success fw-bold" <?= $op['status'] === 'resolved' ? 'selected' : '' ?>>Resolved (Finish Task)</option>
                                    <option value="cancelled" class="text-danger fw-bold" <?= $op['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-info font-montserrat fw-bold text-uppercase text-dark shadow-sm">Post Log</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap Warning Modal -->
    <div class="modal fade" id="resolveWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-info border-opacity-50 glass-card">
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title font-montserrat fw-bold text-uppercase text-info">Confirm Resolution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body font-cascadia">
                    <p class="mb-0 text-warning">WARNING: Marking this as 'Resolved' or 'Cancelled' will finish the operation and immediately release the engineer back to the available roster.</p>
                    <p class="mt-2 mb-0">Proceed with closing this request?</p>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary font-montserrat fw-bold text-uppercase" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info font-montserrat fw-bold text-uppercase text-dark" id="confirmResolveBtn">Proceed</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let pendingForm = null;
        let resolveModal = null;

        document.addEventListener("DOMContentLoaded", function() {
            resolveModal = new bootstrap.Modal(document.getElementById('resolveWarningModal'));
            
            // Attach click event to the modal's proceed button
            document.getElementById('confirmResolveBtn').addEventListener('click', function() {
                if (pendingForm) {
                    pendingForm.submit(); 
                }
            });
        });

        function confirmStatusChange(event, formElement) {
            var selectedStatus = formElement.querySelector('.status-selector').value;
            if (selectedStatus === 'resolved' || selectedStatus === 'cancelled') {
                event.preventDefault(); // Stop immediate form submission
                pendingForm = formElement; // Save the form that was triggered
                resolveModal.show(); // Show the UI modal instead of the JS alert
                return false;
            }
            return true;
        }
    </script>
</body>
</html>