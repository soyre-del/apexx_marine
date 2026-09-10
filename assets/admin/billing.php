<?php
session_start();

// Security Gatekeeper
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

require __DIR__ . '/../../db/db.php';

$message = '';
$error = '';

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Create New Invoice
    if (isset($_POST['action']) && $_POST['action'] === 'create_invoice') {
        $request_id = (int)$_POST['request_id'];
        $amount = (float)$_POST['amount'];
        $currency = htmlspecialchars($_POST['currency'] ?? 'USD');

        try {
            // Get client_id from dispatch request
            $reqStmt = $pdo->prepare("SELECT client_id FROM dispatch_requests WHERE request_id = ?");
            $reqStmt->execute([$request_id]);
            $client_id = $reqStmt->fetchColumn();

            if ($client_id) {
                $insStmt = $pdo->prepare("
                    INSERT INTO invoices (request_id, client_id, amount, currency, payment_status) 
                    VALUES (:req_id, :client_id, :amount, :curr, 'unpaid')
                ");
                $insStmt->execute([
                    ':req_id' => $request_id,
                    ':client_id' => $client_id,
                    ':amount' => $amount,
                    ':curr' => $currency
                ]);
                $message = "Invoice successfully generated for REQ-" . str_pad($request_id, 4, '0', STR_PAD_LEFT);
            } else {
                $error = "Dispatch Request not found.";
            }
        } catch (PDOException $e) {
            $error = "Failed to create invoice: " . $e->getMessage();
        }
    }

    // 2. Update Payment Status Manually (e.g., mark as Paid for offline/cash)
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $invoice_id = (int)$_POST['invoice_id'];
        $status = htmlspecialchars($_POST['payment_status']);
        $method = !empty($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method']) : 'Manual Admin Override';

        try {
            $updStmt = $pdo->prepare("
                UPDATE invoices 
                SET payment_status = :status, payment_method = :method 
                WHERE invoice_id = :inv_id
            ");
            $updStmt->execute([
                ':status' => $status,
                ':method' => $method,
                ':inv_id' => $invoice_id
            ]);
            $message = "Invoice INV-" . str_pad($invoice_id, 5, '0', STR_PAD_LEFT) . " updated to " . strtoupper($status);
        } catch (PDOException $e) {
            $error = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch Unbilled Requests - Accurately pulling from operation_updates
try {
    $unbilledStmt = $pdo->query("
        SELECT 
            d.request_id, 
            d.vessel_name, 
            d.status, 
            u.full_name AS client_name,
            -- Subqueries perfectly mapped to your actual database columns
            (SELECT status_milestone FROM operation_updates WHERE request_id = d.request_id ORDER BY logged_at DESC LIMIT 1) AS latest_milestone,
            (SELECT detailed_message FROM operation_updates WHERE request_id = d.request_id ORDER BY logged_at DESC LIMIT 1) AS problem_description
        FROM dispatch_requests d
        JOIN users u ON d.client_id = u.user_id
        LEFT JOIN invoices i ON d.request_id = i.request_id
        WHERE i.invoice_id IS NULL
        ORDER BY 
            CASE WHEN d.status = 'completed' THEN 1 ELSE 2 END, -- Bring completed to the top
            d.requested_at DESC
    ");
    $unbilled_requests = $unbilledStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Query Failed: " . $e->getMessage();
    $unbilled_requests = [];
}

// Fetch All Invoices
try {
    $sql = "
        SELECT 
            i.invoice_id, i.amount, i.currency, i.payment_status, i.payment_method, i.reference_number, i.created_at,
            u.full_name AS client_name, u.email AS client_email,
            d.request_id, d.vessel_name
        FROM invoices i
        JOIN users u ON i.client_id = u.user_id
        JOIN dispatch_requests d ON i.request_id = d.request_id
        ORDER BY i.created_at DESC
    ";
    $invoices = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Dashboard Totals
    $total_revenue = 0;
    $pending_revenue = 0;
    foreach ($invoices as $inv) {
        if ($inv['payment_status'] === 'paid') $total_revenue += $inv['amount'];
        if ($inv['payment_status'] === 'unpaid') $pending_revenue += $inv['amount'];
    }
} catch (PDOException $e) {
    $invoices = [];
    $total_revenue = 0;
    $pending_revenue = 0;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Billing & Revenue | Apex Marine Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 bg-grid-pattern font-cascadia p-4">

    <!-- Top Navigation Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-3">
            <a href="admin.php" class="btn btn-outline-info text-uppercase font-montserrat fw-bold btn-sm rounded-3">&larr; Matrix</a>
            <h3 class="font-montserrat fw-bold text-white text-uppercase mb-0">Financial & Billing Operations</h3>
        </div>
        
        <button class="btn btn-info font-montserrat fw-bold text-uppercase btn-sm px-3 text-dark rounded-3" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
            + Generate Invoice
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show font-cascadia" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show font-cascadia" role="alert">
            <?= htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card glass-card backdrop-blur-2xl border-success border-opacity-50 p-4">
                <span class="text-success text-uppercase letter-spacing-wide small fw-bold">Collected Revenue</span>
                <h2 class="font-montserrat fw-bold text-white mt-2 mb-0">
                    USD <?= number_format($total_revenue, 2); ?>
                </h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card backdrop-blur-2xl border-warning border-opacity-50 p-4">
                <span class="text-warning text-uppercase letter-spacing-wide small fw-bold">Outstanding Receivables</span>
                <h2 class="font-montserrat fw-bold text-white mt-2 mb-0">
                    USD <?= number_format($pending_revenue, 2); ?>
                </h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card backdrop-blur-2xl border-info border-opacity-50 p-4">
                <span class="text-info text-uppercase letter-spacing-wide small fw-bold">Total Issued Invoices</span>
                <h2 class="font-montserrat fw-bold text-white mt-2 mb-0">
                    <?= count($invoices); ?>
                </h2>
            </div>
        </div>
    </div>

    <!-- NEW: Unbilled Operations Ledger -->
    <div class="d-flex align-items-center mb-3 gap-2">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-warning" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <h5 class="font-montserrat fw-bold text-white text-uppercase mb-0">Operations Awaiting Billing</h5>
    </div>
    
    <div class="card glass-card backdrop-blur-2xl border-warning border-opacity-50 overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 font-cascadia align-middle">
                <thead class="border-bottom border-warning border-opacity-50 text-uppercase letter-spacing-wide" style="font-size: 0.8rem;">
                    <tr>
                        <th class="py-3 px-4 text-warning">Request ID</th>
                        <th class="py-3 px-4">Client Organization</th>
                        <th class="py-3 px-4">Vessel & Issue</th>
                        <th class="py-3 px-4 text-center">Resolve Status</th>
                        <th class="py-3 px-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($unbilled_requests)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">All operations have been successfully billed.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($unbilled_requests as $req): ?>
                            <tr class="border-bottom border-secondary border-opacity-25 <?= $req['status'] === 'completed' ? 'bg-warning bg-opacity-10' : ''; ?>">
                                <td class="px-4 text-white fw-bold">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="px-4 text-white"><?= htmlspecialchars($req['client_name']); ?></td>
                                <td class="px-4">
                                    <span class="text-info fw-bold"><?= htmlspecialchars($req['vessel_name']); ?></span>
                                    
                                    <?php if (!empty($req['latest_milestone'])): ?>
                                        <div class="text-white small mt-1">
                                            <strong>Status:</strong> <?= htmlspecialchars($req['latest_milestone']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($req['problem_description'])): ?>
                                        <div class="text-secondary small text-truncate" style="max-width: 350px;" title="<?= htmlspecialchars($req['problem_description']); ?>">
                                            <?= htmlspecialchars($req['problem_description']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-secondary small fst-italic">No operation logs submitted.</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 text-center">
                                    <?php if ($req['status'] === 'completed'): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1 mb-1 d-inline-block text-uppercase">RESOLVED</span><br>
                                        <span class="text-warning fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">NEEDS PAYMENT</span>
                                    <?php elseif ($req['status'] === 'in_progress' || $req['status'] === 'deployed'): ?>
                                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 px-2 py-1 mb-1 d-inline-block text-uppercase">IN PROGRESS</span><br>
                                        <span class="text-secondary" style="font-size: 0.7rem; letter-spacing: 1px;">AWAITING RESOLUTION</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-50 px-2 py-1 mb-1 d-inline-block text-uppercase">PENDING</span><br>
                                        <span class="text-secondary" style="font-size: 0.7rem; letter-spacing: 1px;">AWAITING RESOLUTION</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 text-end">
                                    <?php if ($req['status'] === 'completed'): ?>
                                        <button onclick="openInvoiceModal(<?= $req['request_id']; ?>)" class="btn btn-sm btn-warning font-montserrat fw-bold text-uppercase text-dark px-3 py-1 shadow-sm">
                                            Bill Now
                                        </button>
                                    <?php else: ?>
                                        <button disabled class="btn btn-sm btn-outline-secondary font-montserrat fw-bold text-uppercase px-3 py-1 border-opacity-25" style="opacity: 0.5;">
                                            Hold Billing
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invoices Master Ledger -->
    <div class="d-flex align-items-center mb-3 gap-2">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="text-info" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <h5 class="font-montserrat fw-bold text-white text-uppercase mb-0">Invoices Master Ledger</h5>
    </div>

    <div class="card glass-card backdrop-blur-2xl border-secondary border-opacity-25 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 font-cascadia align-middle">
                <thead class="border-bottom border-secondary border-opacity-50 text-uppercase letter-spacing-wide" style="font-size: 0.8rem;">
                    <tr>
                        <th class="py-3 px-4 text-info">Invoice #</th>
                        <th class="py-3 px-4">Client</th>
                        <th class="py-3 px-4">Vessel</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Method / Ref</th>
                        <th class="py-3 px-4 text-end">Amount</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">No invoices issued yet. Click "Bill Now" on a completed operation above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr class="border-bottom border-secondary border-opacity-25">
                                <td class="px-4 text-white fw-bold">
                                    INV-<?= str_pad($inv['invoice_id'], 5, '0', STR_PAD_LEFT); ?>
                                    <div class="small text-secondary fw-normal">REQ-<?= str_pad($inv['request_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td class="px-4">
                                    <span class="text-white"><?= htmlspecialchars($inv['client_name']); ?></span>
                                    <div class="small text-secondary"><?= htmlspecialchars($inv['client_email']); ?></div>
                                </td>
                                <td class="px-4 text-info fw-bold"><?= htmlspecialchars($inv['vessel_name']); ?></td>
                                <td class="px-4 text-secondary small"><?= date('M d, Y', strtotime($inv['created_at'])); ?></td>
                                <td class="px-4">
                                    <span class="text-white"><?= htmlspecialchars($inv['payment_method'] ?? 'N/A'); ?></span>
                                    <?php if (!empty($inv['reference_number'])): ?>
                                        <div class="small text-secondary font-monospace"><?= htmlspecialchars($inv['reference_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 text-end font-montserrat fw-bold text-white">
                                    <?= htmlspecialchars($inv['currency']) . ' ' . number_format($inv['amount'], 2); ?>
                                </td>
                                <td class="px-4 text-center">
                                    <?php if ($inv['payment_status'] === 'paid'): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-2 text-uppercase">Paid</span>
                                    <?php elseif ($inv['payment_status'] === 'failed'): ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2 text-uppercase">Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-2 text-uppercase">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 text-center">
                                    <!-- Action Dropdown for Manual Overrides -->
                                    <form method="POST" action="billing.php" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="invoice_id" value="<?= $inv['invoice_id']; ?>">
                                        
                                        <?php if ($inv['payment_status'] === 'unpaid'): ?>
                                            <input type="hidden" name="payment_status" value="paid">
                                            <input type="hidden" name="payment_method" value="Cash / Manual Admin">
                                            <button type="submit" class="btn btn-sm btn-outline-success font-montserrat fw-bold text-uppercase py-1 px-2" style="font-size: 0.7rem;">
                                                Mark Paid
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="payment_status" value="unpaid">
                                            <button type="submit" class="btn btn-sm btn-outline-warning font-montserrat fw-bold text-uppercase py-1 px-2" style="font-size: 0.7rem;">
                                                Mark Unpaid
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Generate New Invoice -->
    <div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border border-secondary text-white">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title font-montserrat text-uppercase fw-bold text-info">Issue New Client Invoice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="billing.php">
                    <div class="modal-body font-cascadia">
                        <input type="hidden" name="action" value="create_invoice">
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary small text-uppercase fw-bold">Select Unbilled Request</label>
                            <select id="request_dropdown" name="request_id" class="form-select bg-black text-white border-secondary" required>
                                <?php if (empty($unbilled_requests)): ?>
                                    <option value="" disabled selected>No unbilled requests found</option>
                                <?php else: ?>
                                    <option value="" disabled selected>Choose a request...</option>
                                    <?php foreach ($unbilled_requests as $unb): ?>
                                        <option value="<?= $unb['request_id']; ?>" <?= $unb['status'] === 'completed' ? 'class="text-warning fw-bold bg-dark"' : ''; ?>>
                                            <?= $unb['status'] === 'completed' ? '🚨 [NEEDS PAYMENT] ' : ''; ?>
                                            REQ-<?= str_pad($unb['request_id'], 4, '0', STR_PAD_LEFT); ?> | <?= htmlspecialchars($unb['vessel_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-8 mb-3">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control bg-black text-white border-secondary" placeholder="500.00" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Currency</label>
                                <select name="currency" class="form-select bg-black text-white border-secondary">
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary font-montserrat text-uppercase fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info font-montserrat text-uppercase fw-bold text-dark" <?= empty($unbilled_requests) ? 'disabled' : ''; ?>>
                            Generate Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script to link 'Bill Now' button directly to the invoice generator -->
    <script>
        function openInvoiceModal(requestId) {
            document.getElementById('request_dropdown').value = requestId;
            var modal = new bootstrap.Modal(document.getElementById('createInvoiceModal'));
            modal.show();
        }
    </script>
</body>
</html>