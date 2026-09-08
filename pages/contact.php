<?php
session_start();
// Ensure this path perfectly points to your db.php file
require __DIR__ . '/../db/db.php'; 

$form_status = "";
$error_status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SECURITY PRECAUTION: Double-check on the backend that they are actually logged in
    if (isset($_SESSION['user_id'])) {
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1. Collect and format basic inputs
            $client_id = $_SESSION['user_id'];
            $vessel_name = trim($_POST['vessel_name']);
            $imo_number = trim($_POST['imo_number']);
            $vessel_type = trim($_POST['vessel_type']);
            $company = trim($_POST['company'] ?? '');
            $contact_person = trim($_POST['contact_person']);
            
            // Combine country code and phone number
            $country_code = trim($_POST['country_code'] ?? '');
            $phone_number = trim($_POST['phone_number'] ?? '');
            $contact_phone = $country_code . ' ' . $phone_number;
            
            $eta_date = $_POST['eta_date'];
            $is_urgent = isset($_POST['urgent']) ? 1 : 0; // Checkbox returns boolean 1 or 0
            $location_id = (int) $_POST['location']; // Now matches the DB IDs
            $service_type = trim($_POST['service_type']);
            $description = trim($_POST['description']);
            
            // 2. Handle File Attachment Uploads
            $attachment_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                
                $upload_dir = __DIR__ . '/../assets/uploads/';
                // Create the uploads folder automatically if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Extract file extension and validate security
                $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
                
                if (in_array($file_ext, $allowed_exts)) {
                    // Generate a random, unique name to prevent overwriting files
                    $new_filename = uniqid('dispatch_') . '.' . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
                        $attachment_path = '/apexx_marine/assets/uploads/' . $new_filename;
                    }
                } else {
                    $error_status = "Upload failed: Only JPG, PNG, and PDF files are permitted.";
                }
            }

            // 3. Database Insertion (If no file upload errors occurred)
            if (empty($error_status)) {
                $sql = "INSERT INTO dispatch_requests 
                        (client_id, vessel_name, imo_number, vessel_type, company, contact_person, contact_phone, eta_date, service_type, is_urgent, location_id, description, attachment_path, status, is_active) 
                        VALUES 
                        (:client_id, :vessel_name, :imo_number, :vessel_type, :company, :contact_person, :contact_phone, :eta_date, :service_type, :is_urgent, :location_id, :description, :attachment_path, 'pending', 1)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':client_id' => $client_id,
                    ':vessel_name' => $vessel_name,
                    ':imo_number' => $imo_number,
                    ':vessel_type' => $vessel_type,
                    ':company' => $company,
                    ':contact_person' => $contact_person,
                    ':contact_phone' => $contact_phone,
                    ':eta_date' => $eta_date,
                    ':service_type' => $service_type,
                    ':is_urgent' => $is_urgent,
                    ':location_id' => $location_id,
                    ':description' => $description,
                    ':attachment_path' => $attachment_path
                ]);

                $form_status = "Dispatch request for " . htmlspecialchars($vessel_name) . " successfully transmitted to Central Command.";
            }

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_status = "Database Error: Ensure you ran the SQL script to create the Service Locations.";
            } else {
                $error_status = "System Error: " . $e->getMessage();
            }
        }
    } else {
        $error_status = "Clearance denied: You must authenticate before submitting.";
    }
}
include '../assets/includes/header.php';
?>

<main class="flex-grow-1 position-relative overflow-hidden py-5">
    
    <!-- Ambient Glows -->
    <div class="glow-blob glow-blob-blue pointer-events-none" style="top: -100px; left: -100px; width: 400px; height: 400px;"></div>
    <div class="glow-blob glow-blob-ocean pointer-events-none" style="bottom: -10%; right: -5%; width: 500px; height: 500px;"></div>

    <div class="container position-relative z-1 pt-4">
        
        <!-- Page Title Row -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-2">
                    <span class="rounded-circle bg-brand-blue me-2" style="width: 8px; height: 8px; box-shadow: 0 0 10px #3b82f6;"></span>
                    <span class="text-brand-blue font-montserrat fw-bold text-uppercase letter-spacing-widest" style="font-size: 0.8rem;">Reach Out</span>
                </div>
                <h1 class="text-white font-montserrat fw-bolder text-uppercase letter-spacing-wide display-5 mb-3">Contact Operations</h1>
                <p class="text-secondary fw-light" style="font-size: 1.1rem; max-width: 600px;">
                    Whether it's an emergency dispatch or scheduling routine heavy-duty maintenance, our engineering team is ready to deploy.
                </p>
            </div>
        </div>

        <div class="row g-5">
            
            <!-- LEFT COLUMN: Contact Info & Hubs -->
            <div class="col-lg-4">
                <div class="glass-card p-4 p-lg-5 h-100 d-flex flex-column gap-4" style="border-radius: 2rem !important;">
                    
                    <div>
                        <h4 class="text-white font-montserrat fw-bold text-uppercase letter-spacing-widest mb-4" style="font-size: 0.9rem;">Global Operations</h4>
                        
                        <!-- Global Dispatch Email -->
                        <div class="d-flex align-items-center mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                            <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue me-3 flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05);">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="text-secondary text-uppercase letter-spacing-wide mb-1" style="font-size: 0.7rem;">Central Dispatch Email</span>
                                <span class="font-cascadia text-white fw-bold" style="font-size: 0.85rem;">dispatch@apexmarine.com.ph</span>
                            </div>
                        </div>

                        <!-- Global Hubs Container -->
                        <div class="d-flex flex-column gap-4">
                            
                            <!-- Philippines (HQ) -->
                            <div>
                                <div class="d-flex align-items-center mb-2">
                                    <svg class="text-brand-ocean me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    <span class="text-white font-montserrat fw-bold text-uppercase letter-spacing-widest" style="font-size: 0.75rem;">Philippines (HQ)</span>
                                </div>
                                <div class="d-flex flex-column ps-3 border-start border-brand-blue ms-1">
                                    <span class="text-secondary text-uppercase letter-spacing-wide mb-1" style="font-size: 0.65rem;">Negros Occidental, Hinoba-an</span>
                                    <span class="font-cascadia text-brand-blue" style="font-size: 0.85rem;">+63 917 000 1234</span>
                                </div>
                            </div>

                            <!-- APAC Hub -->
                            <div>
                                <div class="d-flex align-items-center mb-2">
                                    <svg class="text-brand-ocean me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-white font-montserrat fw-bold text-uppercase letter-spacing-widest" style="font-size: 0.75rem;">APAC Hub</span>
                                    <span class="badge bg-brand-caution text-brand-navy ms-2" style="font-size: 0.55rem; padding: 0.35em 0.5em;">RAPID DEPLOYMENT</span>
                                </div>
                                <div class="d-flex flex-column gap-3 ps-3 border-start border-secondary border-opacity-25 ms-1">
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Singapore</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+65 6000 8888</span>
                                    </div>
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Shanghai, China</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+86 21 5555 0000</span>
                                    </div>
                                </div>
                            </div>

                            <!-- EMEA Hub -->
                            <div>
                                <div class="d-flex align-items-center mb-2">
                                    <svg class="text-brand-ocean me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-white font-montserrat fw-bold text-uppercase letter-spacing-widest" style="font-size: 0.75rem;">EMEA Hub</span>
                                </div>
                                <div class="d-flex flex-column gap-3 ps-3 border-start border-secondary border-opacity-25 ms-1">
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Rotterdam, Netherlands</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+31 10 123 4567</span>
                                    </div>
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Dubai, UAE</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+971 4 123 4567</span>
                                    </div>
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Cape Town, South Africa</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+27 21 123 4567</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Americas Hub -->
                            <div>
                                <div class="d-flex align-items-center mb-2">
                                    <svg class="text-brand-ocean me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-white font-montserrat fw-bold text-uppercase letter-spacing-widest" style="font-size: 0.75rem;">Americas Hub</span>
                                </div>
                                <div class="d-flex flex-column gap-3 ps-3 border-start border-secondary border-opacity-25 ms-1">
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Houston, USA</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+1 (713) 555-0100</span>
                                    </div>
                                    <div>
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1 d-block" style="font-size: 0.65rem;">Panama City, Panama</span>
                                        <span class="font-cascadia text-brand-steel" style="font-size: 0.85rem;">+507 200-0000</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Detailed Digital Channels -->
                        <div class="mt-4 pt-4 border-top border-secondary border-opacity-25">
                            <span class="text-secondary text-uppercase letter-spacing-wide mb-4 d-block" style="font-size: 0.7rem;">Digital Channels</span>
                            
                            <div class="d-flex flex-column gap-3">
                                <!-- WhatsApp / Viber -->
                                <a href="#" class="d-flex align-items-center text-decoration-none social-row">
                                    <div class="social-icon-box d-flex align-items-center justify-content-center rounded-3 text-brand-blue me-3 flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05);">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1" style="font-size: 0.65rem;">WhatsApp / Viber</span>
                                        <span class="font-cascadia text-brand-steel handle-text transition" style="font-size: 0.85rem;">+63 917 000 1234</span>
                                    </div>
                                </a>

                                <!-- LinkedIn -->
                                <a href="#" class="d-flex align-items-center text-decoration-none social-row">
                                    <div class="social-icon-box d-flex align-items-center justify-content-center rounded-3 text-brand-blue me-3 flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05);">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"></path><circle cx="4" cy="4" r="2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle></svg>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1" style="font-size: 0.65rem;">LinkedIn</span>
                                        <span class="font-cascadia text-brand-steel handle-text transition" style="font-size: 0.85rem;">Apex Marine Engineering</span>
                                    </div>
                                </a>

                                <!-- Facebook -->
                                <a href="#" class="d-flex align-items-center text-decoration-none social-row">
                                    <div class="social-icon-box d-flex align-items-center justify-content-center rounded-3 text-brand-blue me-3 flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05);">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path></svg>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1" style="font-size: 0.65rem;">Facebook</span>
                                        <span class="font-cascadia text-brand-steel handle-text transition" style="font-size: 0.85rem;">@ApexMarinePH</span>
                                    </div>
                                </a>

                                <!-- Instagram -->
                                <a href="#" class="d-flex align-items-center text-decoration-none social-row">
                                    <div class="social-icon-box d-flex align-items-center justify-content-center rounded-3 text-brand-blue me-3 flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05);">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></rect><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></line></svg>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-secondary text-uppercase letter-spacing-wide mb-1" style="font-size: 0.65rem;">Instagram</span>
                                        <span class="font-cascadia text-brand-steel handle-text transition" style="font-size: 0.85rem;">@apexmarine_ph</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                    </div>

                    <!-- Operational Status -->
                    <div class="mt-auto pt-4 border-top border-secondary border-opacity-25">
                        <div class="d-flex align-items-center text-secondary mb-2">
                            <svg class="me-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="font-montserrat text-uppercase fw-bold letter-spacing-wide" style="font-size: 0.75rem;">Status</span>
                        </div>
                        <div class="d-flex align-items-center text-success font-cascadia fw-bold" style="font-size: 0.85rem;">
                            <span class="rounded-circle bg-success me-2" style="width: 6px; height: 6px; box-shadow: 0 0 8px #198754;"></span>
                            Online & Operating 24/7
                        </div>
                    </div>

                </div>
            </div>
            
            <!-- RIGHT COLUMN: Dispatch Form -->
            <div class="col-lg-8">
                <div class="glass-card p-4 p-md-5 position-relative overflow-hidden" style="border-radius: 2rem !important;">
                    
                    <div class="position-absolute top-0 end-0 rounded-circle" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(14,165,233,0.1) 0%, transparent 70%); pointer-events: none;"></div>
                    
                    <div class="position-relative z-1">
                        
                        <div class="d-flex align-items-center mb-5 pb-4 border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
                            <svg width="28" height="28" class="text-brand-ocean me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <h3 class="font-montserrat fw-bolder text-white text-uppercase mb-0 fs-3 tracking-wide">Request Dispatch</h3>
                        </div>

                        <!-- Success Alert -->
                        <?php if($form_status): ?>
                            <div class="alert text-white border-0 d-flex align-items-center mb-4 rounded-3" role="alert" style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3) !important;">
                                <svg width="20" height="20" class="me-3 text-brand-active" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div><?php echo $form_status; ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Backend Security Alert -->
                        <?php if($error_status): ?>
                            <div class="alert text-white border-0 d-flex align-items-center mb-4 rounded-3" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3) !important;">
                                <svg width="20" height="20" class="me-3 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div><?php echo $error_status; ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- THE FORM -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            
                            <!-- 1. Vessel Identification Row -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Vessel Name</label>
                                    <input type="text" name="vessel_name" class="form-control custom-input rounded-3 shadow-none" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">IMO Number</label>
                                    <input type="text" name="imo_number" class="form-control custom-input rounded-3 shadow-none" placeholder="e.g., 9876543" required>
                                </div>
                            </div>
                            
                            <!-- 2. Vessel Details Row -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Vessel Type</label>
                                    <select name="vessel_type" class="form-select custom-input rounded-3 shadow-none text-brand-steel" required>
                                        <option value="" style="background-color: #0d1b2a; color: #a9b3c1;" selected disabled>Select Class</option>
                                        <option value="Bulk Carrier" style="background-color: #0d1b2a; color: #ffffff;">Bulk Carrier</option>
                                        <option value="Container Ship" style="background-color: #0d1b2a; color: #ffffff;">Container Ship</option>
                                        <option value="Oil/Chemical Tanker" style="background-color: #0d1b2a; color: #ffffff;">Oil/Chemical Tanker</option>
                                        <option value="Offshore/Tug" style="background-color: #0d1b2a; color: #ffffff;">Offshore / Tug</option>
                                        <option value="Yacht/Passenger" style="background-color: #0d1b2a; color: #ffffff;">Yacht / Passenger</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Company</label>
                                    <input type="text" name="company" class="form-control custom-input rounded-3 shadow-none">
                                </div>
                            </div>
                            
                            <!-- 3. Contact Row -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control custom-input rounded-3 shadow-none" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Direct Phone / Sat Phone</label>
                                    <div class="input-group">
                                        <!-- Country Code Dropdown -->
                                        <select id="countryCodeSelect" name="country_code" class="form-select custom-input text-brand-steel shadow-none border-end-0" style="max-width: 130px; border-top-left-radius: 0.5rem; border-bottom-left-radius: 0.5rem;" required>
                                            <option value="" style="background-color: #0d1b2a; color: #a9b3c1;" selected disabled>Code</option>
                                            <option value="+63" style="background-color: #0d1b2a; color: #ffffff;">+63 (PH)</option>
                                            <option value="+1" style="background-color: #0d1b2a; color: #ffffff;">+1 (US/CA)</option>
                                            <option value="+65" style="background-color: #0d1b2a; color: #ffffff;">+65 (SG)</option>
                                            <option value="+86" style="background-color: #0d1b2a; color: #ffffff;">+86 (CN)</option>
                                            <option value="+31" style="background-color: #0d1b2a; color: #ffffff;">+31 (NL)</option>
                                            <option value="+971" style="background-color: #0d1b2a; color: #ffffff;">+971 (UAE)</option>
                                            <option value="+27" style="background-color: #0d1b2a; color: #ffffff;">+27 (ZA)</option>
                                            <option value="+507" style="background-color: #0d1b2a; color: #ffffff;">+507 (PA)</option>
                                            <option value="+44" style="background-color: #0d1b2a; color: #ffffff;">+44 (UK)</option>
                                            <option value="Sat" style="background-color: #0d1b2a; color: #0ea5e9;">SAT</option>
                                        </select>
                                        
                                        <!-- Actual Phone Number Input -->
                                        <input id="phoneNumberInput" type="tel" name="phone_number" class="form-control custom-input shadow-none" style="border-top-right-radius: 0.5rem; border-bottom-right-radius: 0.5rem;" placeholder="Select country code..." required>
                                    </div>
                                </div>
                            </div>

                            <!-- JavaScript for Dynamic Phone Formatting -->
                            <script src="/apexx_marine/assets/js/phoneformat.js"></script>
                            
                            <!-- 4. Scheduling & Urgency Row -->
                            <div class="row g-4 mb-4">
                                <style>
                                    .custom-date-picker { color-scheme: dark; }
                                    .custom-date-picker::-webkit-calendar-picker-indicator {
                                        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%230ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>');
                                        cursor: pointer; opacity: 0.9; transition: opacity 0.2s ease;
                                    }
                                    .custom-date-picker::-webkit-calendar-picker-indicator:hover { opacity: 1; }
                                </style>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Vessel ETA at Location</label>
                                    <input type="date" name="eta_date" class="form-control custom-input rounded-3 shadow-none text-brand-steel custom-date-picker" required>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <label class="custom-input urgent-label d-flex align-items-center w-100 rounded-3 mb-0" style="height: 50px; cursor: pointer;">
                                        <input class="form-check-input mt-0 me-3 shadow-none" type="checkbox" name="urgent" style="width: 1.1rem; height: 1.1rem; border-color: var(--brand-caution); background-color: transparent;">
                                        <span class="text-uppercase text-white fw-bold font-montserrat letter-spacing-wide pt-1" style="font-size: 0.75rem;">
                                            Urgent Request
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- 5. Location and Service Type Row -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Service Location</label>
                                    <!-- LOCATION IDs MAPPED TO DB -->
                                    <select name="location" class="form-select custom-input rounded-3 shadow-none text-brand-steel" required>
                                        <option value="" style="background-color: #0d1b2a; color: #a9b3c1;" selected disabled>Select Operational Hub</option>
                                        <optgroup label="APAC Hub (Rapid Deployment)" style="background-color: #0d1b2a; color: #ffffff;">
                                            <option value="1" style="background-color: #0d1b2a; color: #a9b3c1;">Singapore</option>
                                            <option value="2" style="background-color: #0d1b2a; color: #a9b3c1;">Shanghai, China</option>
                                        </optgroup>
                                        <optgroup label="EMEA Hub" style="background-color: #0d1b2a; color: #ffffff;">
                                            <option value="3" style="background-color: #0d1b2a; color: #a9b3c1;">Rotterdam, Netherlands</option>
                                            <option value="4" style="background-color: #0d1b2a; color: #a9b3c1;">Dubai, UAE</option>
                                            <option value="5" style="background-color: #0d1b2a; color: #a9b3c1;">Cape Town, South Africa</option>
                                        </optgroup>
                                        <optgroup label="Americas Hub" style="background-color: #0d1b2a; color: #ffffff;">
                                            <option value="6" style="background-color: #0d1b2a; color: #a9b3c1;">Houston, USA</option>
                                            <option value="7" style="background-color: #0d1b2a; color: #a9b3c1;">Panama City, Panama</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Technical Discipline</label>
                                    <select name="service_type" class="form-select custom-input rounded-3 shadow-none text-brand-steel" required>
                                        <option value="" style="background-color: #0d1b2a; color: #a9b3c1;" selected disabled>Select Required Service</option>
                                        <option value="Propulsion & Machinery" style="background-color: #0d1b2a; color: #ffffff;">Propulsion & Machinery</option>
                                        <option value="Electrical & Automation" style="background-color: #0d1b2a; color: #ffffff;">Electrical & Automation</option>
                                        <option value="Hydraulics & Deck Gear" style="background-color: #0d1b2a; color: #ffffff;">Hydraulics & Deck Gear</option>
                                        <option value="Hull & Steel Fabrication" style="background-color: #0d1b2a; color: #ffffff;">Hull & Steel Fabrication</option>
                                        <option value="Preventative Maintenance" style="background-color: #0d1b2a; color: #ffffff;">Preventative Maintenance</option>
                                        <option value="General Consultation" style="background-color: #0d1b2a; color: #a9b3c1;">Other / General Consultation</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 6. File Attachments -->
                            <div class="mb-4">
                                <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Attach Photos / Reports (Optional)</label>
                                <input class="form-control custom-input rounded-3 shadow-none text-brand-steel" type="file" name="attachment" accept="image/png, image/jpeg, application/pdf">
                            </div>

                            <!-- 7. Description Box -->
                            <div class="mb-5">
                                <label class="form-label text-uppercase text-brand-steel fw-bold letter-spacing-widest mb-2" style="font-size: 0.65rem;">Description</label>
                                <textarea name="description" rows="4" class="form-control custom-input rounded-3 shadow-none" required placeholder="Provide details about the required maintenance or repair..."></textarea>
                            </div>
                            
                            <!-- 8. DYNAMIC BUTTON AREA -->
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <!-- User is Logged In: Show normal Submit Button -->
                                <button type="submit" class="btn btn-brand-caution w-100 py-3 rounded-3 font-montserrat fw-bolder text-uppercase letter-spacing-widest">
                                    Submit Request
                                </button>
                            <?php else: ?>
                                <!-- User is NOT Logged In: Show Locked Status and Login Links -->
                                <div class="mt-4 p-4 rounded-3 border border-secondary border-opacity-25 text-center" style="background: rgba(0,0,0,0.2);">
                                    <div class="d-flex align-items-center justify-content-center mb-4">
                                        <svg width="24" height="24" class="text-brand-ocean me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        <span class="text-brand-steel font-cascadia fs-6 fw-bold">Clearance required to submit dispatch</span>
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                                        <a href="../assets/includes/login.php" class="btn btn-brand-ocean py-2 px-4 rounded-3 font-montserrat fw-bold text-uppercase fs-7 shadow-sm">
                                            Authenticate
                                        </a>
                                        <a href="../assets/includes/register.php" class="btn btn-outline-secondary py-2 px-4 rounded-3 font-montserrat fw-bold text-uppercase fs-7 text-white transition-all">
                                            Enlist Now
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../assets/includes/footer.php'; ?>