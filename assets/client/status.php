<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: /apexx_marine/assets/includes/login.php");
    exit();
}

require __DIR__ . '/../../db/db.php'; 
$client_id = $_SESSION['user_id'];

try {
    // 1. Fetch Client's Requests
    $stmt = $pdo->prepare("SELECT request_id, vessel_name, status, requested_at FROM dispatch_requests WHERE client_id = ? ORDER BY requested_at DESC");
    $stmt->execute([$client_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Timeline Updates for these requests
    $updates = [];
    if (!empty($requests)) {
        $req_ids = array_column($requests, 'request_id');
        $in_query = implode(',', array_fill(0, count($req_ids), '?'));
        
        $upd_stmt = $pdo->prepare("SELECT request_id, status_milestone, detailed_message, logged_at FROM operation_updates WHERE request_id IN ($in_query) ORDER BY logged_at DESC");
        $upd_stmt->execute($req_ids);
        
        // Group updates by request_id
        foreach ($upd_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $updates[$row['request_id']][] = $row;
        }
    }
} catch (PDOException $e) {
    $requests = [];
    $updates = [];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Client Operations | Apex Marine</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 bg-grid-pattern font-cascadia">

    <!-- Minimalist Client Navigation Bar -->
    <nav class="navbar shadow-lg px-4 py-3" style="background-color: rgba(13, 42, 74, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(14, 165, 233, 0.2);">
        <div class="container-fluid">
            
            <!-- Left: Identity -->
            <div class="d-flex align-items-center gap-3">
                <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill border border-info border-opacity-25">
                    <span class="rounded-circle bg-info me-2" style="width: 6px; height: 6px; box-shadow: 0 0 10px rgba(14, 165, 233, 0.8); animation: pulse 2s infinite;"></span>
                    <span class="fw-bold text-info text-uppercase letter-spacing-widest" style="font-size: 10px;">CLIENT_PORTAL</span>
                </div>
                <h5 class="font-montserrat fw-bolder text-white mb-0 text-uppercase letter-spacing-wide">Apex Marine</h5>
            </div>

            <!-- Right: Controls -->
            <div class="d-flex gap-4 align-items-center">
                
                <!-- Action Links -->
                <div class="d-flex gap-2">
                    <a href="" class="btn btn-sm btn-outline-info font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh Feed
                    </a>
                    <a href="/apexx_marine/pages/contact.php" class="btn btn-sm btn-outline-light font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        New Dispatch
                    </a>
                    <a href="/apexx_marine/index.php" class="btn btn-sm btn-outline-light font-montserrat fw-bold text-uppercase d-flex align-items-center gap-2 rounded-3 px-3 py-2 border-opacity-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Home
                    </a>
                </div>

                <!-- Subtle Vertical Divider -->
                <div class="vr bg-secondary opacity-25" style="width: 1px; height: 24px;"></div>

                <!-- User Profile & Log Out -->
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary small font-cascadia">
                        ID: <strong class="text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Client'); ?></strong>
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
    <div class="container p-4 p-md-5">
        <div class="row g-4">
            <div class="col-12 mb-2">
                <h4 class="font-montserrat fw-bold text-secondary text-uppercase mb-0">Active Dispatch Monitoring</h4>
            </div>

            <?php if(empty($requests)): ?>
                <div class="col-12 text-center py-5">
                    <svg width="64" height="64" class="mx-auto mb-3 text-secondary opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h5 class="text-white font-montserrat fw-bold text-uppercase">No Active Operations</h5>
                    <p class="text-secondary font-cascadia">You currently have no vessels requiring assistance.</p>
                    <a href="/apexx_marine/pages/contact.php" class="btn btn-info font-montserrat fw-bold text-uppercase mt-3 text-dark px-4 shadow-sm">Initialize Request</a>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="col-12">
                        <div class="card glass-card backdrop-blur-2xl border-0 shadow-lg p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                                <h4 class="text-white font-montserrat fw-bold text-uppercase m-0 d-flex align-items-center gap-2">
                                    <svg width="24" height="24" class="text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    <?= htmlspecialchars($req['vessel_name']); ?> 
                                    <span class="fs-6 text-secondary font-cascadia">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </h4>
                                
                                <?php 
                                    $badgeClass = 'bg-secondary';
                                    if ($req['status'] === 'pending') $badgeClass = 'bg-warning text-dark';
                                    if ($req['status'] === 'deployed') $badgeClass = 'bg-info text-dark';
                                    if ($req['status'] === 'in_progress') $badgeClass = 'bg-primary';
                                    if ($req['status'] === 'resolved') $badgeClass = 'bg-success';
                                    if ($req['status'] === 'cancelled') $badgeClass = 'bg-danger';
                                ?>
                                <span class="badge <?= $badgeClass; ?> text-uppercase px-3 py-2 font-montserrat letter-spacing-wide shadow-sm" style="font-size: 0.75rem;">
                                    Status: <?= htmlspecialchars($req['status']); ?>
                                </span>
                            </div>
                            
                            <h6 class="text-brand-steel font-montserrat fw-bold text-uppercase mt-2">Live Timeline Log</h6>
                            
                            <?php if (empty($updates[$req['request_id']])): ?>
                                <div class="bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-3 p-3 mt-3">
                                    <p class="text-secondary small mb-0 font-cascadia">Awaiting Central Command to assign specialized personnel. No updates recorded yet.</p>
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush mt-3" style="background: transparent;">
                                    <?php foreach ($updates[$req['request_id']] as $log): ?>
                                        <li class="list-group-item bg-dark bg-opacity-25 border border-secondary border-opacity-25 rounded-3 mb-2 px-3 py-3">
                                            <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                                <strong class="text-info text-uppercase font-montserrat" style="font-size: 0.85rem; letter-spacing: 1px;">
                                                    <span class="text-white me-1">&gt;</span> <?= htmlspecialchars($log['status_milestone']); ?>
                                                </strong>
                                                <small class="text-secondary font-cascadia" style="font-size: 0.75rem;">
                                                    <?= date('M d, Y - H:i', strtotime($log['logged_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-0 text-brand-steel font-cascadia" style="font-size: 0.85rem;">
                                                <?= nl2br(htmlspecialchars($log['detailed_message'])); ?>
                                            </p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(14, 165, 233, 0); }
            100% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
        }
        .hover-white:hover { color: white !important; }
    </style>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>