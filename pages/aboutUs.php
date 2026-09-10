<?php
session_start();
include '../assets/includes/header.php';
?>

<style>
/* Core Map Container */
.map-operations-center {
    box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.8);
}

/* Holographic Grid Overlay */
.map-grid {
    background-image: linear-gradient(rgba(46, 107, 156, 0.1) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(46, 107, 156, 0.1) 1px, transparent 1px);
    background-size: 30px 30px;
    z-index: 1;
    pointer-events: none;
}

/* Vertical Scanner Animation */
.map-scanner {
    height: 4px;
    background: linear-gradient(90deg, transparent, var(--bs-brand-ocean, #2E6B9C), transparent);
    box-shadow: 0 0 15px var(--bs-brand-ocean, #2E6B9C);
    z-index: 2;
    opacity: 0.5;
    animation: scan 6s linear infinite;
}

@keyframes scan {
    0% { top: -10px; opacity: 0; }
    10% { opacity: 0.5; }
    90% { opacity: 0.5; }
    100% { top: 100%; opacity: 0; }
}

/* Hub Marker Elements */
.hub-marker {
    position: absolute;
    transform: translate(-50%, -50%);
    z-index: 10;
    pointer-events: auto;
    cursor: crosshair;
}

.hub-dot {
    width: 14px;
    height: 14px;
    background: var(--bs-brand-caution, #F59E0B);
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 15px rgba(245, 158, 11, 0.8);
    position: relative;
    z-index: 2;
    transition: transform 0.3s ease;
}

.hub-marker:hover .hub-dot {
    transform: scale(1.3);
    background: #fff;
    border-color: var(--bs-brand-caution, #F59E0B);
}

/* Radar Ping Animation */
.hub-ping {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    background: rgba(245, 158, 11, 0.4);
    border-radius: 50%;
    z-index: 1;
    animation: radarPing 2.5s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
}

@keyframes radarPing {
    0% { width: 14px; height: 14px; opacity: 1; }
    100% { width: 60px; height: 60px; opacity: 0; border: 1px solid rgba(245, 158, 11, 0.5); }
}

/* Glassmorphism Tooltip */
.hub-tooltip {
    position: absolute;
    bottom: 150%;
    left: 50%;
    transform: translate(-50%, 10px);
    width: 220px;
    background: rgba(13, 27, 42, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(46, 107, 156, 0.4);
    border-radius: 8px;
    padding: 12px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    z-index: 20;
    pointer-events: none;
}

.hub-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 6px;
    border-style: solid;
    border-color: rgba(46, 107, 156, 0.4) transparent transparent transparent;
}

.hub-marker:hover .hub-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translate(-50%, -5px);
}

.map-pulse-indicator {
    animation: indicatorPulse 2s infinite;
}
@keyframes indicatorPulse {
    0% { opacity: 1; }
    50% { opacity: 0.3; }
    100% { opacity: 1; }
}
</style>

<!-- ABOUT US PAGE CONTENT -->

<!-- 1. Page Header -->
<header class="pt-5 pb-4 bg-brand-dark position-relative overflow-hidden mt-5">
    <div class="glow-blob glow-blob-ocean top-0 start-50 translate-middle-x" style="width: 800px; height: 400px; opacity: 0.5;"></div>
    <div class="container position-relative z-1 text-center pt-5 pb-5">
        <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-3">
            <span class="rounded-circle bg-brand-ocean me-2" style="width: 6px; height: 6px;"></span>
            <span class="text-white text-uppercase font-montserrat fw-bold letter-spacing-widest" style="font-size: 0.75rem;">A Century of Maritime Excellence</span>
        </div>
        <h1 class="display-3 font-montserrat fw-bolder text-uppercase text-white mb-4">
            About <span class="text-brand-ocean">Apex Marine</span>
        </h1>
        <p class="lead text-brand-steel mx-auto fw-light" style="max-width: 750px; font-size: 1.2rem;">
            Pioneering heavy-duty maritime engineering, rapid global response, and uncompromising vessel maintenance since the dawn of modern commercial shipping.
        </p>
    </div>
</header>

<!-- 2. Key Statistics Banner -->
<div class="bg-brand-navy border-top border-bottom border-secondary border-opacity-10 py-4">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <h3 class="display-5 font-montserrat fw-bold text-white mb-1">110<span class="text-brand-ocean">+</span></h3>
                <span class="font-cascadia small text-brand-steel letter-spacing-wide">YEARS ACTIVE</span>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="display-5 font-montserrat fw-bold text-white mb-1">24<span class="text-brand-ocean">/7</span></h3>
                <span class="font-cascadia small text-brand-steel letter-spacing-wide">GLOBAL DISPATCH</span>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="display-5 font-montserrat fw-bold text-white mb-1">45<span class="text-brand-ocean">+</span></h3>
                <span class="font-cascadia small text-brand-steel letter-spacing-wide">MAJOR PORTS</span>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="display-5 font-montserrat fw-bold text-white mb-1">12<span class="text-brand-ocean">k</span></h3>
                <span class="font-cascadia small text-brand-steel letter-spacing-wide">VESSELS SERVICED</span>
            </div>
        </div>
    </div>
</div>

<!-- 3. Core Objective & Heritage Section -->
<section class="py-section bg-brand-dark position-relative border-bottom border-secondary border-opacity-10">
    <div class="glow-blob glow-blob-blue bottom-0 start-0 translate-middle-y" style="width: 600px; height: 600px; opacity: 0.3;"></div>
    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">
            <!-- Left: Text Content -->
            <div class="col-lg-6">
                <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-4">
                    <span class="rounded-circle bg-brand-active me-2" style="width: 6px; height: 6px;"></span>
                    <span class="text-white text-uppercase font-montserrat fw-bold letter-spacing-wide" style="font-size: 0.7rem;">Our Core Objective</span>
                </div>
                
                <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4 h1">
                    Forged in the <br> Deep Blue
                </h2>

                <div class="p-4 rounded-4 mb-4" style="background: rgba(46, 107, 156, 0.1); border-left: 4px solid var(--bs-brand-ocean);">
                    <p class="text-white fw-medium lh-lg mb-0" style="font-size: 1.1rem;">
                        To provide highly efficient, heavy-duty ship repairs through a modern, seamlessly organized operation. Executed by our highly trained marine engineers and dedicated crews, we ensure immediate trust and reliability for the maritime industry.
                    </p>
                </div>
                
                <p class="text-brand-steel fw-light lh-lg mb-4">
                    What began as a localized dockside repair shop has evolved into a global powerhouse in maritime diagnostics and mechanical overhauls, built entirely around the mandate to eliminate critical downtime for the global shipping industry.
                </p>
                <p class="text-brand-steel fw-light lh-lg mb-5">
                    We don't just repair systems; we optimize them. From legacy two-stroke engines to modern dual-fuel propulsion systems, our institutional knowledge ensures your fleet operates safely and in full compliance with international maritime laws.
                </p>

                <div class="d-flex align-items-center gap-4">
                    <div>
                        <h4 class="text-white font-montserrat fw-bold mb-1">ISO</h4>
                        <span class="text-brand-ocean font-cascadia small letter-spacing-wide">9001:2015</span>
                    </div>
                    <div style="width: 1px; height: 40px; background-color: rgba(255,255,255,0.1);"></div>
                    <div>
                        <h4 class="text-white font-montserrat fw-bold mb-1">IACS</h4>
                        <span class="text-brand-ocean font-cascadia small letter-spacing-wide">CERTIFIED TEAMS</span>
                    </div>
                </div>
            </div>

            <!-- Right: Image Card -->
            <div class="col-lg-6">
                <div class="service-card rounded-4 position-relative">
                    <div class="img-wrapper" style="aspect-ratio: 600 / 399; height: auto;">
                        <div class="img-overlay"></div>
                        <img src="../assets/images/founded.jpg" alt="founded in 1910" class="img-fluid w-100 h-100 object-fit-cover">
                    </div>
                    <div class="position-absolute bottom-0 start-0 p-4 z-3">
                        <div class="glass-card px-4 py-3 rounded-3 d-inline-block shadow-lg">
                            <span class="text-white font-montserrat fw-bold text-uppercase d-block mb-1">founded in 1910</span>
                            <span class="text-brand-caution font-cascadia small">over a century of excellence</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. Global Reach / Infrastructure -->
<section class="py-section bg-brand-navy position-relative">
    <div class="container">
        <div class="row align-items-center g-5 flex-lg-row-reverse">
            <div class="col-lg-6">
                <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4">Strategic Global<br><span class="text-brand-ocean">Infrastructure</span></h2>
                <p class="text-brand-steel fw-light lh-lg mb-4">
                    Vessels don't break down on a convenient schedule. That's why our infrastructure is designed for maximum agility. With strategic hubs located near the world's busiest shipping lanes, our riding squads are pre-staged with specialized tooling and ready to fly at a moment's notice to uphold our objective of immediate reliability.
                </p>
                <ul class="list-unstyled text-brand-steel fw-light lh-lg">
                    <li class="mb-3 d-flex align-items-center">
                        <svg class="text-brand-ocean me-3" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        <strong>APAC Hub:</strong>Singapore & Shanghai (Rapid Deployment Zone)
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <svg class="text-brand-ocean me-3" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        <strong>EMEA Hub:</strong>Rotterdam, Dubai & Cape Town
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <svg class="text-brand-ocean me-3" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        <strong>Americas Hub:</strong>Houston & Panama City
                    </li>
                </ul>
            </div>
            
            <div class="col-lg-6">
                <div class="glass-card p-4 rounded-4 text-center">
                    
                    <!-- PURE HTML/CSS Radar Map (Zero JS Dependencies) -->
                    <div class="map-operations-center position-relative w-100 rounded-4 overflow-hidden border border-secondary border-opacity-25" style="height: 400px; background-color: #05101c; z-index: 1;">
                        
                        <!-- CSS Tech Grid Background -->
                        <div class="map-grid position-absolute top-0 start-0 w-100 h-100"></div>
                        
                        <!-- Scanning Radar Overlay -->
                        <div class="map-scanner position-absolute top-0 start-0 w-100"></div>
                    
                        <!-- Hardcoded HTML Markers -->
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 5;">
                            
                            <!-- Singapore -->
                            <div class="hub-marker" style="top: 55%; left: 79%;">
                                <div class="hub-ping" style="animation-delay: 0s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">APAC Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Singapore</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Rapid Deployment Zone</p>
                                </div>
                            </div>

                            <!-- Shanghai -->
                            <div class="hub-marker" style="top: 35%; left: 83%;">
                                <div class="hub-ping" style="animation-delay: 0.3s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">APAC Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Shanghai</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Dry-Dock Facility</p>
                                </div>
                            </div>

                            <!-- Rotterdam -->
                            <div class="hub-marker" style="top: 25%; left: 51%;">
                                <div class="hub-ping" style="animation-delay: 0.6s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">EMEA Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Rotterdam</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Command Center</p>
                                </div>
                            </div>

                            <!-- Dubai -->
                            <div class="hub-marker" style="top: 42%; left: 65%;">
                                <div class="hub-ping" style="animation-delay: 0.9s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">EMEA Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Dubai</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Mechanical Overhaul</p>
                                </div>
                            </div>

                            <!-- Cape Town -->
                            <div class="hub-marker" style="top: 75%; left: 55%;">
                                <div class="hub-ping" style="animation-delay: 1.2s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">EMEA Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Cape Town</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Emergency Dispatch</p>
                                </div>
                            </div>

                            <!-- Houston -->
                            <div class="hub-marker" style="top: 40%; left: 23%;">
                                <div class="hub-ping" style="animation-delay: 1.5s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">Americas Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Houston</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Structural Repair</p>
                                </div>
                            </div>

                            <!-- Panama City -->
                            <div class="hub-marker" style="top: 52%; left: 28%;">
                                <div class="hub-ping" style="animation-delay: 1.8s;"></div>
                                <div class="hub-dot"></div>
                                <div class="hub-tooltip">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-2 mb-2">
                                        <span class="font-montserrat fw-bold text-uppercase" style="font-size: 0.75rem; color: #f59e0b;">Americas Hub</span>
                                        <div class="d-flex align-items-center">
                                            <span class="bg-success rounded-circle me-1" style="width:6px; height:6px; box-shadow: 0 0 5px #198754;"></span>
                                            <span class="text-white font-montserrat" style="font-size: 0.65rem;">ONLINE</span>
                                        </div>
                                    </div>
                                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.1rem;">Panama City</h5>
                                    <p class="mb-0" style="font-size: 0.8rem; color: #8a9bb0;">Mid-Voyage Interventions</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Map Legend/Overlay -->
                        <div class="position-absolute bottom-0 start-0 p-3 z-3" style="pointer-events: none;">
                            <div class="glass-card px-3 py-2 rounded-3 d-inline-flex align-items-center" style="background: rgba(13, 27, 42, 0.7); backdrop-filter: blur(8px); border: 1px solid rgba(46, 107, 156, 0.3);">
                                <span class="rounded-circle me-2 map-pulse-indicator" style="width: 8px; height: 8px; background-color: #2e6b9c;"></span>
                                <span class="text-secondary font-monospace small" style="letter-spacing: 1px; color: #8a9bb0 !important;">LIVE OFFLINE NETWORK</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- 5. Core Competencies Section (Apex Advantage) -->
<section class="py-section bg-brand-dark position-relative overflow-hidden">
    <div class="glow-blob glow-blob-ocean top-0 start-50 translate-middle-x" style="width: 800px; height: 400px; opacity: 0.4;"></div>
    
    <div class="container position-relative z-1">
        <div class="text-center mx-auto mb-5" style="max-width: 800px;">
            <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-3">
                <span class="rounded-circle bg-brand-caution me-2" style="width: 6px; height: 6px;"></span>
                <span class="text-white text-uppercase font-montserrat fw-bold letter-spacing-widest" style="font-size: 0.7rem;">Apex Advantage</span>
            </div>
            <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4 display-5">
                Excellence in <br class="d-none d-md-block" />
                <span class="text-brand-ocean">Maritime Engineering</span>
            </h2>
        </div>

        <div class="row g-4 g-lg-5">
            <!-- Feature Card 01 -->
            <div class="col-md-6">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue" style="width: 56px; height: 56px; background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2);">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <span class="font-cascadia fw-bold text-secondary letter-spacing-widest">01</span>
                    </div>
                    <h3 class="h4 text-white font-montserrat fw-bold text-uppercase mb-3">Heavy-Duty Repairs</h3>
                    <p class="text-brand-steel mb-0 fw-light lh-lg">Comprehensive structural and mechanical overhauls engineered to restore main propulsion performance, generator function, and guarantee long-term vessel integrity.</p>
                </div>
            </div>

            <!-- Feature Card 02 -->
            <div class="col-md-6">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue" style="width: 56px; height: 56px; background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2);">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <span class="font-cascadia fw-bold text-secondary letter-spacing-widest">02</span>
                    </div>
                    <h3 class="h4 text-white font-montserrat fw-bold text-uppercase mb-3">Seamless Operations</h3>
                    <p class="text-brand-steel mb-0 fw-light lh-lg">Executing efficiently organized maritime solutions where diagnostic and mechanical procedures adhere strictly to international SOLAS requirements and class society safety standards.</p>
                </div>
            </div>

            <!-- Feature Card 03 -->
            <div class="col-md-6">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue" style="width: 56px; height: 56px; background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2);">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="font-cascadia fw-bold text-secondary letter-spacing-widest">03</span>
                    </div>
                    <h3 class="h4 text-white font-montserrat fw-bold text-uppercase mb-3">Dedicated Crews</h3>
                    <p class="text-brand-steel mb-0 fw-light lh-lg">Our riding squads and highly trained marine engineers deploy globally in under 24 hours, minimizing off-hire time through agile, dockside, or mid-voyage interventions.</p>
                </div>
            </div>

            <!-- Feature Card 04 -->
            <div class="col-md-6">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue" style="width: 56px; height: 56px; background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2);">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                        </div>
                        <span class="font-cascadia fw-bold text-secondary letter-spacing-widest">04</span>
                    </div>
                    <h3 class="h4 text-white font-montserrat fw-bold text-uppercase mb-3">Systems & Automation</h3>
                    <p class="text-brand-steel mb-0 fw-light lh-lg">From power generation synchronization to pneumatic control repairs, ballast water treatment systems, and alarm monitoring, our crews possess deep multi-system expertise.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. Safety & HSEQ Culture -->
<section class="py-section bg-brand-navy position-relative border-top border-secondary border-opacity-10">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="font-montserrat fw-bold text-white text-uppercase">The Apex Standard</h2>
                <p class="text-brand-steel fw-light mx-auto mt-3" style="max-width: 600px;">Our commitment to safety and environmental stewardship is non-negotiable. Every project is executed with a "Zero Harm" philosophy to ensure immediate trust.</p>
            </div>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4">
                    <div class="text-brand-caution mb-3">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <h4 class="text-white font-montserrat fw-bold text-uppercase mb-3">Zero Harm Culture</h4>
                    <p class="text-brand-steel fw-light small">Protecting our personnel, your crew, and the vessel is our primary directive. Rigorous risk assessments precede every intervention.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border-start border-end border-secondary border-opacity-25">
                    <div class="text-brand-blue mb-3">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h4 class="text-white font-montserrat fw-bold text-uppercase mb-3">Eco-Compliance</h4>
                    <p class="text-brand-steel fw-light small">We actively assist fleets in meeting evolving EEXI, CII, and emissions regulations through engine optimizations and retrofit installations.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <div class="text-brand-ocean mb-3">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <h4 class="text-white font-montserrat fw-bold text-uppercase mb-3">Total Transparency</h4>
                    <p class="text-brand-steel fw-light small">Detailed reporting, honest diagnostics, and strict adherence to initial quotes. Integrity drives our partnerships.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. Final Call to Action (CTA) Section -->
<section class="py-section bg-brand-dark position-relative overflow-hidden text-center border-top border-secondary border-opacity-10">
    <div class="glow-blob glow-blob-ocean bottom-0 start-50 translate-middle-x" style="width: 800px; height: 400px; opacity: 0.6;"></div>
    
    <div class="container position-relative z-1 pt-4 pb-5">
        <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4 display-6">Ready to Secure Your Fleet?</h2>
        <p class="text-brand-steel mb-5 mx-auto fw-light" style="max-width: 600px; font-size: 1.1rem;">
            Whether you need emergency dockside repairs, a mid-ocean riding squad, or a scheduled dry-dock overhaul, our operations desk is standing by 24/7.
        </p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
            <a href="contact.php" class="btn btn-brand-blue font-montserrat fw-bold text-uppercase py-3 px-5 rounded-3 letter-spacing-wide d-inline-flex align-items-center justify-content-center">
                Contact Operations
                <svg class="ms-3" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
            <a href="services.php" class="btn btn-outline-secondary font-montserrat fw-bold text-white text-uppercase py-3 px-5 rounded-3 letter-spacing-wide d-inline-flex align-items-center justify-content-center">
                View Capabilities
            </a>
        </div>
    </div>
</section>
<?php include '../assets/includes/footer.php'; ?>