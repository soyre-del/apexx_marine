<?php
session_start();
if (!isset($_GET['ref'])) {
    // Redirects back to the billing dashboard if the page is accessed directly without a reference
    header("Location: /apexx_marine/assets/client/billing/billing.php");
    exit();
}

$raw_ref = htmlspecialchars($_GET['ref']);

// Transform a plain number (e.g., "1") into a professional format (e.g., "APX-TXN-000001")
if (is_numeric($raw_ref)) {
    $formatted_ref = 'APX-TXN-' . str_pad($raw_ref, 6, '0', STR_PAD_LEFT);
} else {
    $formatted_ref = $raw_ref;
}

// Generate the current date and time for the receipt
$current_date = date('F j, Y, g:i A');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    
    <!-- Print Styles to ensure it looks good if the user prints the receipt -->
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff !important; color: #000000 !important; }
            .bg-dark, .bg-black { background-color: #f8f9fa !important; border-color: #dee2e6 !important; }
            .text-white { color: #000000 !important; }
            .text-info { color: #0056b3 !important; }
            .border-success { border-color: #198754 !important; }
        }
    </style>
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center min-vh-100" style="font-family: 'Montserrat', sans-serif;">
    
    <div class="text-center p-5 rounded-4 border border-success shadow-lg" style="max-width: 500px; background-color: #0b0f19;">
        
        <svg width="80" height="80" class="text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        
        <h2 class="text-uppercase fw-bold text-success">Payment Successful</h2>
        <p class="mt-2 text-secondary">Your transaction has been securely recorded in the Apex Marine database.</p>
        
        <!-- Receipt Details Block -->
        <div class="bg-dark border border-secondary border-opacity-25 p-4 rounded text-start mt-4 mb-4 shadow-sm">
            
            <div class="d-flex justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.8rem;">Date & Time</span>
                <span class="fw-bold" style="font-size: 0.85rem;"><?= $current_date; ?></span>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.8rem;">Reference ID</span>
                <span class="fs-5 fw-bold font-monospace text-info"><?= $formatted_ref; ?></span>
            </div>
            
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-secondary w-50 fw-bold text-uppercase py-2 d-flex align-items-center justify-content-center gap-2">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print
            </button>
            <a href="/apexx_marine/assets/client/status.php" class="btn btn-info w-50 fw-bold text-uppercase py-2 text-dark">
                Dashboard
            </a>
        </div>
        
    </div>
    
</body>
</html>