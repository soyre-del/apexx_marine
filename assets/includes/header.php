<?php
// Securely start the session only if one hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Check if the user is authenticated by looking for their user_id in the session
$is_logged_in = isset($_SESSION['user_id']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex Marine | Heavy-Duty Engineering & Maintenance</title>

    <!-- Absolute paths for CSS (so it works on all pages) -->
    <link rel="stylesheet" href="/apexx_marine/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/apexx_marine/assets/css/custom-bootstrap.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- jsvectormap css -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css" />
    
    <!-- jsvectormap js -->
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
</head>
<body class="bg-brand-navy text-brand-steel">

<!-- Bootstrap Navigation Bar -->
<nav id="mainNav" class="navbar navbar-expand-lg sticky-top shadow-lg" style="background-color: rgba(13, 42, 74, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.05); transition: opacity 0.4s ease, visibility 0.4s ease;">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand py-2" href="/apexx_marine/index.php">
            <img src="/apexx_marine/assets/images/Logo.png" alt="Apex Marine" height="60">
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center font-montserrat fw-bold text-uppercase letter-spacing-wide" style="font-size: 0.85rem;">
                
                <li class="nav-item mx-2">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'text-white' : 'text-brand-steel hover-white' ?>" href="/apexx_marine/index.php">Home</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= ($current_page == 'aboutUs.php') ? 'text-white' : 'text-brand-steel hover-white' ?>" href="/apexx_marine/pages/aboutUs.php">About</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= ($current_page == 'services.php') ? 'text-white' : 'text-brand-steel hover-white' ?>" href="/apexx_marine/pages/services.php">Services</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link <?= ($current_page == 'contact.php') ? 'text-white' : 'text-brand-steel hover-white' ?>" href="/apexx_marine/pages/contact.php">Contact</a>
                </li>
                
                <!-- DYNAMIC AUTHENTICATION BUTTONS -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <!-- USER IS LOGGED IN: Smart Dashboard Routing -->
                    <li class="nav-item ms-lg-4 mt-3 mt-lg-0">
                        <?php if ($_SESSION['role'] === 'client'): ?>
                            <!-- CLIENT: Track My Vessel (UPDATED PATH) -->
                            <a class="btn btn-warning rounded-3 px-4 py-2 font-montserrat fw-bold text-uppercase text-dark d-inline-flex align-items-center transition-all shadow-sm" href="/apexx_marine/assets/client/status.php">
                                <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Track My Vessel
                            </a>

                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <!-- ADMIN: Command Center -->
                            <a class="btn btn-danger rounded-3 px-4 py-2 font-montserrat fw-bold text-uppercase d-inline-flex align-items-center transition-all shadow-sm" href="/apexx_marine/assets/admin/admin.php">
                                <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                Command Center
                            </a>

                        <?php else: ?>
                            <!-- ENGINEER: Field Dashboard -->
                            <a class="btn btn-info rounded-3 px-4 py-2 font-montserrat fw-bold text-uppercase text-dark d-inline-flex align-items-center transition-all shadow-sm" href="/apexx_marine/assets/engineer/eng.php">
                                <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Field Dashboard
                            </a>
                        <?php endif; ?>
                    </li>

                    <!-- LOG OUT BUTTON -->
                    <li class="nav-item ms-lg-2 mt-3 mt-lg-0">
                        <a class="btn btn-outline-danger rounded-3 px-4 py-2 font-montserrat fw-bold text-uppercase d-inline-flex align-items-center transition-all hover-white border-opacity-50" href="/apexx_marine/assets/includes/logout.php">
                            <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Log Out
                        </a>
                    </li>

                <?php else: ?>
                    
                    <!-- USER IS NOT LOGGED IN: Show Log In & Sign Up buttons -->
                    <li class="nav-item ms-lg-4 mt-3 mt-lg-0">
                        <a class="nav-link text-white px-3 py-2 font-montserrat fw-bold text-uppercase d-inline-flex align-items-center" href="/apexx_marine/assets/includes/login.php">
                            <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Log In
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2 mt-3 mt-lg-0">
                        <a class="btn btn-brand-blue rounded-3 px-4 py-2 font-montserrat fw-bold text-uppercase d-inline-flex align-items-center" href="/apexx_marine/assets/includes/register.php" style="background-color: #0ea5e9; color: #fff; border: none;">
                            <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Sign Up
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
    
    <script src="/apexx_marine/assets/js/dissolve.js"></script>
</body>
</html>