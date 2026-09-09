<?php
session_start();

// Security Gatekeeper
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

require __DIR__ . '/../../db/db.php';

$client_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Simulated Client Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_invoice') {
    $invoice_id = (int)$_POST['invoice_id'];
    $payment_method = htmlspecialchars($_POST['payment_method']);
    
    // Generate a mock reference number for the receipt
    $reference_no = 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 10));

    try {
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET payment_status = 'paid', payment_method = :method, reference_number = :ref 
            WHERE invoice_id = :inv_id AND client_id = :client_id AND payment_status = 'unpaid'
        ");
        $stmt->execute([
            ':method' => $payment_method,
            ':ref' => $reference_no,
            ':inv_id' => $invoice_id,
            ':client_id' => $client_id
        ]);

        if ($stmt->rowCount() > 0) {
            $message = "Payment successful! Reference: " . $reference_no;
        } else {
            $error = "Payment failed or invoice already paid.";
        }
    } catch (PDOException $e) {
        $error = "Transaction error: " . $e->getMessage();
    }
}

// Fetch Client's Invoices
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.invoice_id, i.amount, i.currency, i.payment_status, i.payment_method, i.reference_number, i.created_at,
            d.vessel_name, d.request_id, d.status AS operation_status
        FROM invoices i
        JOIN dispatch_requests d ON i.request_id = d.request_id
        WHERE i.client_id = ?
        ORDER BY i.payment_status DESC, i.created_at DESC
    ");
    $stmt->execute([$client_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unpaid for notification badge
    $unpaid_count = count(array_filter($invoices, fn($inv) => $inv['payment_status'] === 'unpaid'));

} catch (PDOException $e) {
    $invoices = [];
    $unpaid_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>My Billing | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-navy text-brand-steel min-vh-100 bg-grid-pattern font-cascadia p-4">

    <!-- Top Navigation Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-3">
            <a href="/apexx_marine/index.php" class="btn btn-outline-info text-uppercase font-montserrat fw-bold btn-sm rounded-3">&larr; Home</a>
            <h3 class="font-montserrat fw-bold text-white text-uppercase mb-0">Billing & Payments</h3>
        </div>
        <div class="text-white font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2">
            <span class="text-secondary small">Client:</span> <?= htmlspecialchars($_SESSION['full_name']); ?>
        </div>
    </div>

    <!-- Dynamic Notification for Needed Payments -->
    <?php if ($unpaid_count > 0): ?>
        <div class="alert bg-warning bg-opacity-10 border border-warning text-warning font-montserrat fw-bold d-flex align-items-center mb-4" role="alert">
            <svg class="me-3" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            ACTION REQUIRED: You have <?= $unpaid_count; ?> pending invoice(s) for completed operations. Please process payment to clear your account.
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show font-cascadia" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Invoices List -->
    <div class="row g-4">
        <?php if (empty($invoices)): ?>
            <div class="col-12 text-center py-5">
                <h5 class="text-secondary font-montserrat text-uppercase">No billing records found.</h5>
            </div>
        <?php else: ?>
            <?php foreach ($invoices as $inv): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card glass-card backdrop-blur-2xl border-secondary border-opacity-25 h-100 position-relative overflow-hidden">
                        
                        <!-- Status Ribbon -->
                        <?php if ($inv['payment_status'] === 'paid'): ?>
                            <div class="position-absolute top-0 end-0 bg-success text-white px-3 py-1 font-montserrat fw-bold text-uppercase small" style="border-bottom-left-radius: 8px;">Payment Done</div>
                        <?php else: ?>
                            <div class="position-absolute top-0 end-0 bg-warning text-dark px-3 py-1 font-montserrat fw-bold text-uppercase small" style="border-bottom-left-radius: 8px;">Payment Needed</div>
                        <?php endif; ?>

                        <div class="card-body p-4 mt-3">
                            <div class="text-secondary small font-monospace mb-1">INV-<?= str_pad($inv['invoice_id'], 5, '0', STR_PAD_LEFT); ?></div>
                            <h4 class="font-montserrat fw-bold text-white mb-3">
                                <?= htmlspecialchars($inv['currency']) . ' ' . number_format($inv['amount'], 2); ?>
                            </h4>

                            <ul class="list-unstyled font-cascadia small text-brand-steel mb-4 space-y-2">
                                <li><strong class="text-info">Vessel:</strong> <?= htmlspecialchars($inv['vessel_name']); ?></li>
                                <li><strong class="text-info">Job Ref:</strong> REQ-<?= str_pad($inv['request_id'], 4, '0', STR_PAD_LEFT); ?></li>
                                <li><strong class="text-info">Issued:</strong> <?= date('M d, Y', strtotime($inv['created_at'])); ?></li>
                                <?php if ($inv['payment_status'] === 'paid'): ?>
                                    <li><strong class="text-success">Method:</strong> <?= htmlspecialchars($inv['payment_method']); ?></li>
                                    <li><strong class="text-success">Txn Ref:</strong> <?= htmlspecialchars($inv['reference_number']); ?></li>
                                <?php endif; ?>
                            </ul>

                            <?php if ($inv['payment_status'] === 'unpaid'): ?>
                                <button class="btn btn-warning w-100 font-montserrat fw-bold text-uppercase text-dark rounded-3" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#payModal<?= $inv['invoice_id']; ?>">
                                    Secure Checkout &rarr;
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-success w-100 font-montserrat fw-bold text-uppercase rounded-3" disabled>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="me-2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> Settled
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Modal for this specific invoice -->
                <?php if ($inv['payment_status'] === 'unpaid'): ?>
                <div class="modal fade" id="payModal<?= $inv['invoice_id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-brand-dark border border-secondary text-white shadow-lg">
                            <div class="modal-header border-bottom border-secondary">
                                <h5 class="modal-title font-montserrat text-uppercase fw-bold text-warning">Process Payment</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="billing.php">
                                <div class="modal-body font-cascadia">
                                    <input type="hidden" name="action" value="pay_invoice">
                                    <input type="hidden" name="invoice_id" value="<?= $inv['invoice_id']; ?>">
                                    
                                    <div class="mb-4 text-center">
                                        <div class="text-secondary small text-uppercase">Total Due</div>
                                        <h2 class="font-montserrat fw-bold text-white mb-0">
                                            <?= htmlspecialchars($inv['currency']) . ' ' . number_format($inv['amount'], 2); ?>
                                        </h2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label text-secondary small text-uppercase fw-bold">Select Payment Method</label>
                                        <select name="payment_method" class="form-select bg-black text-white border-secondary" required>
                                            <option value="" disabled selected>Choose gateway...</option>
                                            <option value="Credit Card (Stripe)">Credit/Debit Card</option>
                                            <option value="PayPal">PayPal</option>
                                            <option value="Bank Wire Transfer">Bank Wire Transfer</option>
                                        </select>
                                    </div>
                                    <p class="text-secondary small mt-3" style="font-size: 11px;">
                                        * Clicking 'Authorize Payment' simulates a secure gateway transaction. 
                                    </p>
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <button type="button" class="btn btn-secondary font-montserrat text-uppercase fw-bold" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning font-montserrat text-uppercase fw-bold text-dark">
                                        Authorize Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>