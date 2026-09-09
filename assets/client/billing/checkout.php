<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/apexx_marine/db/db.php';

// Ensure client is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Handle the Form Submission (The "Payment")
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inv_id = (int)$_POST['invoice_id'];
    $method = htmlspecialchars($_POST['payment_method']);
    // Generate a fake receipt/reference number if they didn't provide one
    $ref_num = !empty($_POST['reference_number']) ? htmlspecialchars($_POST['reference_number']) : 'TXN-' . strtoupper(uniqid());

    // Update the database to mark as paid
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET payment_status = 'paid', payment_method = :method, reference_number = :ref 
        WHERE invoice_id = :inv_id AND client_id = :client_id
    ");
    $stmt->execute([
        ':method' => $method,
        ':ref' => $ref_num,
        ':inv_id' => $inv_id,
        ':client_id' => $_SESSION['user_id']
    ]);

    // Redirect to success page
    header("Location: /apexx_marine/assets/client/payment_success.php?ref=" . $ref_num);
    exit();
}

// Fetch Invoice Details for the display
$stmt = $pdo->prepare("
    SELECT i.amount, i.currency, d.vessel_name, d.request_id 
    FROM invoices i 
    JOIN dispatch_requests d ON i.request_id = d.request_id 
    WHERE i.invoice_id = :inv_id AND i.client_id = :client_id AND i.payment_status = 'unpaid'
");
$stmt->execute([':inv_id' => $invoice_id, ':client_id' => $_SESSION['user_id']]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("<div style='color:white; background:#111; padding:20px; font-family:sans-serif;'>Invoice not found or already paid. <a href='status.php' style='color:#0dcaf0;'>Return to Dashboard</a></div>");
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center min-vh-100 p-3" style="font-family: 'Montserrat', sans-serif;">

    <div class="card bg-black border border-secondary border-opacity-50 shadow-lg" style="max-width: 500px; width: 100%;">
        <div class="card-header border-bottom border-secondary border-opacity-50 p-4 text-center">
            <h4 class="text-uppercase fw-bold text-info m-0">Apex Marine Payment Gateway</h4>
            <span class="badge bg-warning text-dark mt-2">SIMULATED ENVIRONMENT</span>
        </div>
        
        <div class="card-body p-4">
            <div class="mb-4 text-center">
                <p class="text-secondary mb-1">Total Due</p>
                <h1 class="fw-bold mb-0 text-success">
                    <?= htmlspecialchars($invoice['currency']) . ' ' . number_format($invoice['amount'], 2); ?>
                </h1>
                <p class="small text-secondary mt-2">Service Request REQ-<?= str_pad($invoice['request_id'], 4, '0', STR_PAD_LEFT); ?> <br> Vessel: <?= htmlspecialchars($invoice['vessel_name']); ?></p>
            </div>

            <form method="POST" action="checkout.php">
                <input type="hidden" name="invoice_id" value="<?= $invoice_id; ?>">
                
                <div class="mb-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Payment Method</label>
                    <select name="payment_method" class="form-select bg-dark text-white border-secondary" required>
                        <option value="Bank Transfer">Direct Bank Transfer</option>
                        <option value="Credit Card">Credit/Debit Card</option>
                        <option value="GCash">GCash / E-Wallet</option>
                        <option value="Cash">Cash to Engineer</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Account / Reference Number (Optional)</label>
                    <input type="text" name="reference_number" class="form-control bg-dark text-white border-secondary" placeholder="e.g. 1234-5678-9000">
                    <div class="form-text text-secondary" style="font-size: 0.75rem;">Leave blank to auto-generate a transaction ID.</div>
                </div>

                <button type="submit" class="btn btn-info w-100 fw-bold text-uppercase py-2">
                    Confirm & Submit Payment
                </button>
                <a href="payment_checkout.php" class="btn btn-outline-secondary w-100 fw-bold text-uppercase py-2 mt-2">
                    Cancel
                </a>
            </form>
        </div>
    </div>

</body>
</html>