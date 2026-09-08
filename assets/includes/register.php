<?php
session_start();

// Connect to the Database
require __DIR__ . '/../../db/db.php';

// CSRF Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // CSRF Token Validation
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $errors[] = "Security token validation failed. Unauthorized request intercepted.";
    } else {
        // Collect & validate input
        $name = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Basic Formatting Validation
        if (empty($name)) {
            $errors[] = "Personnel Name is required.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Enter a valid, secure email address.";
        }
        
        // Strict Password Complexity Validation
        if (empty($password)) {
            $errors[] = "Access Code (Password) is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Access Code must be at least 8 characters.";
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
            $errors[] = "Access Code must contain at least one letter, one number, and one special character.";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Access Codes do not match.";
        }

        // Duplicate Verification in the Database
        if (empty($errors)) {
            try {
                // Check if the Email is already taken
                $stmtEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
                $stmtEmail->execute([':email' => $email]);
                if ($stmtEmail->fetch()) {
                    $errors[] = "This Secure Email is already registered in the system.";
                }

                // Check if the Name is already taken
                $stmtName = $pdo->prepare("SELECT user_id FROM users WHERE full_name = :name LIMIT 1");
                $stmtName->execute([':name' => $name]);
                if ($stmtName->fetch()) {
                    $errors[] = "This Personnel Name is already assigned to an existing operative.";
                }

            } catch (PDOException $e) {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }

        // Direct Registration Processing (Verification Removed)
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Hash the complex password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
                
                // Insert the new Client into the database
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (:name, :email, :pass, 'client', 1)");
                $stmt->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':pass'  => $hashed_password
                ]);

                $pdo->commit();
                
                // Clear CSRF so it resets on the next page
                unset($_SESSION['csrf_token']);
                
                // Pass a success message to the login screen
                $_SESSION['sys_msg'] = "Registration successful. You may now authenticate your session.";
                $_SESSION['sys_msg_type'] = "success";
                
                header("Location: /apexx_marine/assets/includes/login.php");
                exit();
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = "System Error: Unable to complete registration.";
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
    <title>Request Clearance | Apex Marine</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">
</head>
<body class="bg-brand-dark text-brand-steel min-vh-100 d-flex flex-column align-items-center justify-content-center p-3 position-relative bg-grid-pattern overflow-hidden font-cascadia">

    <a href="/apexx_marine/index.php" class="position-absolute top-0 start-0 m-4 text-brand-steel text-decoration-none hover-white d-flex align-items-center gap-2 z-3 fs-7 transition-all">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Return to Base
    </a>

    <div class="position-absolute top-50 start-50 translate-middle glow-blob glow-blob-ocean" style="width: 700px; height: 700px; z-index: 0;"></div>

    <div class="card glass-card backdrop-blur-2xl rounded-2rem shadow-lg position-relative z-1 w-100 border-0 text-white mt-3" style="max-width: 440px;">
        
        <!-- REDUCED PADDING: pt-5 pb-4 is now pt-4 pb-3 -->
        <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 text-center position-relative overflow-hidden pt-4 pb-3 px-4">
            <svg class="position-absolute pe-none" style="top: -2.5rem; right: -2.5rem; width: 10rem; height: 10rem; color: rgba(255, 255, 255, 0.03); transform: rotate(12deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            
            <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-2 shadow-sm position-relative z-1">
                <span class="rounded-circle bg-brand-ocean me-2" style="width: 6px; height: 6px; box-shadow: 0 0 8px rgba(14,165,233,0.8);"></span>
                <span class="font-cascadia fw-bold text-brand-ocean text-uppercase letter-spacing-widest" style="font-size: 10px;">Apex Marine</span>
            </div>
            
            <h2 class="font-montserrat fw-bolder text-uppercase text-white letter-spacing-wide position-relative z-1 mb-0 fs-4">
                Request <br><span class="text-gradient-ocean">Clearance</span>
            </h2>
            <p class="text-secondary font-cascadia mt-2 mb-0" style="font-size: 0.75rem;">Initialize your organizational profile.</p>
        </div>

        <!-- REDUCED PADDING: p-4 p-sm-5 is now just p-4 -->
        <div class="card-body p-4">
            
            <?php if (!empty($errors)): ?>
                <div class="alert-terminal-error font-cascadia p-3 mb-3 shadow-sm border border-danger border-opacity-50 rounded-3 bg-danger bg-opacity-10">
                    <?php foreach ($errors as $e): ?>
                        <div class="d-flex align-items-start mb-1 text-white" style="font-size: 0.8rem;">
                            <span class="me-2 text-danger fw-bold">&gt;</span>
                            <span><?php echo htmlspecialchars($e); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- REDUCED GAP: gap-3 is now gap-2 -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-flex flex-column gap-2">
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary z-1">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required placeholder="Full Name or Organization" class="tactical-input font-cascadia fs-7 ps-5 position-relative py-2">
                </div>
                
                <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary z-1">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Secure Email" class="tactical-input font-cascadia fs-7 ps-5 position-relative py-2">
                </div>

                <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary z-1">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </span>
                    <input type="password" name="password" required placeholder="Create Access Code" class="tactical-input font-cascadia fs-7 ps-5 position-relative py-2">
                </div>
                
                <!-- Fixed Helper Text spacing -->
                <div class="text-secondary font-cascadia" style="font-size: 0.65rem; margin-top: -2px; margin-left: 5px; line-height: 1.2;">
                    Must contain letters, numbers, and special characters (Min 8).
                </div>

                <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary z-1">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </span>
                    <input type="password" name="confirm_password" required placeholder="Confirm Access Code" class="tactical-input font-cascadia fs-7 ps-5 position-relative py-2">
                </div>

                <!-- REDUCED PADDING: py-3 is now py-2 -->
                <button type="submit" class="btn btn-brand-ocean w-100 mt-2 py-2 rounded-3 font-montserrat fw-bold text-uppercase fs-7 shadow-sm">
                    Initialize Profile
                </button>
                
            </form>
        </div>
        
        <!-- REDUCED PADDING: py-4 is now py-3 -->
        <div class="card-footer bg-transparent border-top border-secondary border-opacity-25 text-center py-3">
            <p class="small font-cascadia text-brand-steel mb-0">
                Already have clearance? <br>
                <a href="login.php" class="text-brand-ocean fw-bold text-decoration-none hover-white d-inline-block mt-1">Authenticate here &rarr;</a>
            </p>
        </div>
        
    </div>

    <script src="/apexx_marine/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>