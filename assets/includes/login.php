<?php
session_start();
require __DIR__ . '/../../db/db.php';

// 1. CSRF Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$is_logged_in = isset($_SESSION['user_id']);

// Capture Flash Messages (like success messages from register.php)
$sys_msg = $_SESSION['sys_msg'] ?? '';
$sys_msg_type = $_SESSION['sys_msg_type'] ?? '';
unset($_SESSION['sys_msg'], $_SESSION['sys_msg_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_logged_in) {
    
    // CSRF Token Validation
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $message = "Security token validation failed. Unauthorized request intercepted.";
    } else {
        // Collect raw input
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; 

        if (empty($email) || empty($password)) {
            $message = "Invalid email or password.";
        } else {
            try {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // 2. Query matches database schema
                $sql = "SELECT user_id, full_name, password_hash, role FROM users WHERE email = :email AND is_active = 1 LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':email', $email);
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // 3. Verify password securely against the hash
                if ($user && password_verify($password, $user['password_hash'])) {
                    
                    // Prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables securely
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = strtolower($user['role']); // Standardized to lowercase
                    
                    unset($_SESSION['csrf_token']);
                    
                    // 4. SMART REDIRECT LOGIC
                    if ($_SESSION['role'] === 'admin') {
                        header("Location: /apexx_marine/assets/admin/admin.php");
                    } elseif ($_SESSION['role'] === 'client') {
                        header("Location: /apexx_marine/assets/includes/login.php");
                    } else {
                        header("Location: /apexx_marine/index.php"); // Fallback for standard users/engineers
                    }
                    exit();
                    
                } else {
                    $message = "Invalid email or password.";
                }
            } catch (PDOException $e) {
                // Displays exact database error if the connection fails
                $message = "CRITICAL DB ERROR: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex Marine | Terminal Access</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">

    <style>
        /* Precision UI Resizing & Alignment (Matches Register UI) */
        .glass-card {
            background: rgba(13, 27, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .tactical-input-container {
            position: relative;
            margin-bottom: 0.5rem;
        }
        .tactical-input-container .input-icon-wrapper {
            position: absolute;
            top: 50%;
            left: 1.25rem;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tactical-input {
            height: 3.5rem !important; 
            padding-left: 3.5rem !important; 
            background-color: rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            transition: all 0.3s ease;
        }
        .tactical-input:focus {
            background-color: rgba(0, 0, 0, 0.4) !important;
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25) !important;
        }
        .tactical-input::placeholder {
            color: rgba(255, 255, 255, 0.3) !important;
        }
        .text-gradient-ocean {
            background: linear-gradient(90deg, #38bdf8, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 d-flex flex-column align-items-center justify-content-center p-3 position-relative bg-grid-pattern overflow-hidden font-cascadia">

    <a href="/apexx_marine/index.php" class="position-absolute top-0 start-0 m-4 text-brand-steel text-decoration-none hover-white d-flex align-items-center gap-2 z-3 fs-7 transition-all">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Return to Base
    </a>

    <div class="position-absolute top-50 start-50 translate-middle glow-blob glow-blob-ocean" style="width: 700px; height: 700px; z-index: 0; opacity: 0.6;"></div>

    <!-- ADJUSTED: max-width increased to 480px, spacing tightened -->
    <div class="card glass-card backdrop-blur-2xl rounded-2rem shadow-lg position-relative z-1 w-100 border-0 text-white mt-3" style="max-width: 480px;">
        
        <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 text-center position-relative overflow-hidden pt-4 pb-3 px-4 px-sm-5">
            <svg class="position-absolute pe-none" style="top: -2.5rem; right: -2.5rem; width: 10rem; height: 10rem; color: rgba(255, 255, 255, 0.03); transform: rotate(12deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            
            <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-2 shadow-sm position-relative z-1 border border-info border-opacity-25" style="background: rgba(14, 165, 233, 0.1);">
                <span class="rounded-circle bg-brand-ocean me-2" style="width: 6px; height: 6px; box-shadow: 0 0 8px rgba(14,165,233,0.8);"></span>
                <span class="font-cascadia fw-bold text-brand-ocean text-uppercase letter-spacing-widest" style="font-size: 10px;">Apex Marine</span>
            </div>
            
            <h2 class="font-montserrat fw-bolder text-uppercase text-white letter-spacing-wide position-relative z-1 mb-0 fs-4">
                Terminal <br><span class="text-gradient-ocean">Access</span>
            </h2>
            <p class="text-secondary font-cascadia mt-2 mb-0" style="font-size: 0.85rem;">Establish secure connection.</p>
        </div>

        <div class="card-body p-4">
            
            <?php if ($is_logged_in): ?>
                
                <!-- ALREADY LOGGED IN STATE -->
                <div class="text-center">
                    
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <!-- ADMIN WELCOME UI -->
                        <div class="alert-terminal-success font-cascadia p-4 mb-4 shadow-sm d-flex flex-column text-warning border border-warning border-opacity-25 rounded-3" style="background: rgba(245, 158, 11, 0.05);">
                            <svg width="48" height="48" class="mx-auto mb-3 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="fw-bold fs-5 mb-2 font-montserrat text-uppercase">Admin Override</span>
                            <span class="text-brand-steel fs-7">Welcome Commander, <?= htmlspecialchars($_SESSION['full_name']); ?>. <br>System privileges: <strong>ELEVATED</strong></span>
                        </div>

                        <a href="/apexx_marine/assets/admin/admin.php" class="btn btn-warning w-100 py-2 rounded-3 font-montserrat fw-bold text-uppercase fs-7 shadow-sm mb-3 text-dark">
                            Enter Admin Command
                        </a>
                        
                    <?php elseif ($_SESSION['role'] === 'client'): ?>
                        <!-- CLIENT WELCOME UI -->
                        <div class="alert-terminal-success font-cascadia p-4 mb-4 shadow-sm d-flex flex-column text-info border border-info border-opacity-25 rounded-3" style="background: rgba(14, 165, 233, 0.05);">
                            <svg width="48" height="48" class="mx-auto mb-3 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="fw-bold fs-5 mb-2 font-montserrat text-uppercase">Client Portal</span>
                            <span class="text-brand-steel fs-7">Welcome back, <?= htmlspecialchars($_SESSION['full_name']); ?>. <br>System privileges: <strong>AUTHORIZED</strong></span>
                        </div>

                        <a href="/apexx_marine/index.php" class="btn btn-info w-100 py-2 rounded-3 font-montserrat fw-bold text-uppercase fs-7 shadow-sm mb-3 text-dark">
                            Access Operations Tracker
                        </a>

                    <?php else: ?>
                        <!-- STANDARD USER WELCOME UI -->
                        <div class="alert-terminal-success font-cascadia p-4 mb-4 shadow-sm d-flex flex-column text-success border border-success border-opacity-25 rounded-3" style="background: rgba(16, 185, 129, 0.05);">
                            <svg width="48" height="48" class="mx-auto mb-3 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="fw-bold fs-5 mb-2 font-montserrat text-uppercase">Connection Active</span>
                            <span class="text-brand-steel fs-7">Welcome back, <?= htmlspecialchars($_SESSION['full_name']); ?>. <br>Your clearance level: <strong><?= strtoupper(htmlspecialchars($_SESSION['role'])); ?></strong></span>
                        </div>

                        <a href="/apexx_marine/index.php" class="btn btn-brand-ocean w-100 py-2 rounded-3 font-montserrat fw-bold text-uppercase fs-7 shadow-sm mb-3">
                            Proceed to Dashboard
                        </a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 px-4 py-2 font-montserrat fw-bold text-uppercase d-inline-flex align-items-center justify-content-center shadow-sm transition-all hover-white border-opacity-50">
                        <svg class="me-2" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Terminate Session
                    </a>
                </div>

            <?php else: ?>

                <!-- SYSTEM SUCCESS ALERTS (From Register) -->
                <?php if (!empty($sys_msg)): ?>
                    <div class="alert-terminal-success font-cascadia p-3 mb-4 shadow-sm border border-success border-opacity-50 rounded-3 bg-success bg-opacity-10">
                        <div class="d-flex align-items-start text-white" style="font-size: 0.85rem; line-height: 1.4;">
                            <span class="me-2 text-success fw-bold mt-1">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                            </span>
                            <span><?= htmlspecialchars($sys_msg) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- LOGIN ERROR ALERTS -->
                <?php if (!empty($message)): ?>
                    <div class="alert-terminal-error font-cascadia p-3 mb-4 shadow-sm border border-danger border-opacity-50 rounded-3 bg-danger bg-opacity-10">
                        <div class="d-flex align-items-start text-white" style="font-size: 0.85rem; line-height: 1.4;">
                            <span class="me-2 text-danger fw-bold mt-1">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </span>
                            <span><?= htmlspecialchars($message) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- LOGIN FORM -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-flex flex-column gap-2">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <div class="tactical-input-container">
                        <span class="input-icon-wrapper">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </span>
                        <input type="email" name="email" required placeholder="Secure Email" class="form-control tactical-input font-cascadia fs-7 shadow-none" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="tactical-input-container">
                        <span class="input-icon-wrapper">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" name="password" required placeholder="Access Code" class="form-control tactical-input font-cascadia fs-7 shadow-none">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-1 mb-2 px-1 small font-cascadia">
                        <div class="form-check m-0">
                            <input class="form-check-input bg-transparent border-secondary" type="checkbox" id="rememberMe" name="remember_me">
                            <label class="form-check-label text-brand-steel" for="rememberMe" style="font-size: 0.8rem;">
                                Remember session
                            </label>
                        </div>
                        <a href="#" class="text-brand-ocean fw-bold text-decoration-none hover-white transition-all" style="font-size: 0.8rem;">Forgot key?</a>
                    </div>

                    <button type="submit" class="btn btn-brand-ocean w-100 mt-3 py-2 rounded-3 font-montserrat fw-bold text-uppercase fs-7 d-flex align-items-center justify-content-center shadow-lg transition-all" style="letter-spacing: 1px;">
                        <span>Authenticate</span>
                        <svg class="ms-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                    
                </form>

            <?php endif; ?>
        </div>
        
        <?php if (!$is_logged_in): ?>
            <!-- Footer / Navigation -->
            <div class="card-footer bg-transparent border-top border-secondary border-opacity-25 text-center py-3">
                <p class="small font-cascadia text-brand-steel mb-0">
                    Require clearance? <br>
                    <a href="register.php" class="text-brand-ocean fw-bold text-decoration-none hover-white d-inline-block mt-1 transition-all">Enlist here &rarr;</a>
                </p>
            </div>
        <?php endif; ?>
        
    </div>
    
    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>