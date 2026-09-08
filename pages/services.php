<?php
session_start();
include '../assets/includes/header.php';
?>
<!-- SERVICES PAGE CONTENT -->

<!-- 1. Page Header -->
<header class="pt-5 pb-4 bg-brand-dark position-relative overflow-hidden mt-5 border-0">
    <!-- Added border-radius and blur to eliminate the hard, flat bottom edge -->
    <div class="position-absolute top-0 start-50 translate-middle-x glow-blob glow-blob-ocean" style="width: 800px; height: 400px; opacity: 0.5; border-radius: 50%; filter: blur(80px); z-index: 0;"></div>
    
    <div class="container position-relative z-1 text-center pt-5 pb-5">
        <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-3" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
            <span class="rounded-circle bg-brand-ocean me-2" style="width: 6px; height: 6px;"></span>
            <span class="text-white text-uppercase font-montserrat fw-bold letter-spacing-widest" style="font-size: 0.75rem;">Comprehensive Solutions</span>
        </div>
        
        <h1 class="display-3 font-montserrat fw-bolder text-uppercase text-white mb-4">
            Our Core <span class="text-brand-ocean">Services</span>
        </h1>
        
        <p class="lead text-brand-steel mx-auto fw-light" style="max-width: 850px; font-size: 1.2rem;">
            From critical mid-ocean emergency interventions to strategic dry-dock planning. We provide specialized, Class-approved marine repair and maintenance operations executed by certified OEM-trained engineers.
        </p>
    </div>
</header>

<!-- 2. Technical Disciplines (Grid Layout for Breadth) -->
<section class="py-5 bg-brand-navy position-relative border-top border-secondary border-opacity-10">
    <div class="container pt-4">
        <div class="row mb-5 text-center">
            <div class="col-12">
                <h3 class="font-montserrat fw-bold text-white text-uppercase mb-2">Technical Disciplines</h3>
                <p class="text-brand-steel">Comprehensive coverage across all critical vessel systems.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Discipline 1 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 bg-transparent border border-secondary border-opacity-25 rounded-4 p-4 hover-lift" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="mb-4 text-brand-ocean">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    </div>
                    <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6 mb-3">Propulsion & Machinery</h5>
                    <p class="text-brand-steel fw-light" style="font-size: 0.85rem;">2-stroke/4-stroke main engine overhauls, turbocharger servicing, alignment, and heavy machinery reconditioning.</p>
                </div>
            </div>
            
            <!-- Discipline 2 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 bg-transparent border border-secondary border-opacity-25 rounded-4 p-4 hover-lift" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="mb-4 text-brand-caution">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6 mb-3">Electrical & Automation</h5>
                    <p class="text-brand-steel fw-light" style="font-size: 0.85rem;">PLC programming, main switchboard diagnostics, alarm monitoring systems, and thermographic surveys.</p>
                </div>
            </div>

            <!-- Discipline 3 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 bg-transparent border border-secondary border-opacity-25 rounded-4 p-4 hover-lift" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="mb-4 text-white">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6 mb-3">Hydraulics & Deck Gear</h5>
                    <p class="text-brand-steel fw-light" style="font-size: 0.85rem;">Mooring winches, cargo cranes, steering gear overhaul, and high-pressure hose fabrication and testing.</p>
                </div>
            </div>

            <!-- Discipline 4 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 bg-transparent border border-secondary border-opacity-25 rounded-4 p-4 hover-lift" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="mb-4 text-brand-ocean">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6 mb-3">Hull & Steel Fabrication</h5>
                    <p class="text-brand-steel fw-light" style="font-size: 0.85rem;">Class-approved welding (ABS/DNV), steel plate renewal, pipe fabrication (CuNi, Stainless), and NDT testing.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Marine Engineering Section (Alternating Layout) -->
<section class="py-section bg-brand-dark position-relative border-top border-secondary border-opacity-10" id="engineering" style="padding-top: 6rem; padding-bottom: 6rem;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="position-relative rounded-4 overflow-hidden shadow-lg border border-secondary border-opacity-25" style="aspect-ratio: 4/3;">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-25 z-1"></div>
                    <img src="../assets/images/solutions.jpg" alt="Marine Engineering" class="img-fluid w-100 h-100 object-fit-cover position-relative z-0">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 text-brand-ocean mb-4" style="width: 56px; height: 56px; background: rgba(0, 210, 255, 0.1); border: 1px solid rgba(0, 210, 255, 0.2);">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                </div>
                <h2 class="font-montserrat fw-bold text-white text-uppercase mb-3">Marine <span class="text-brand-ocean">Engineering</span></h2>
                <p class="text-brand-steel fw-light lh-lg mb-4 fs-6">
                    When critical propulsion or auxiliary systems fail, our certified mechanics and automation experts intervene to restore full operability under the most demanding conditions. We go beyond parts replacement, focusing on root-cause analysis to prevent recurrent failures.
                </p>
                <div class="row mt-4">
                    <div class="col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <svg class="text-brand-ocean me-2 mt-1 flex-shrink-0" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-brand-steel font-montserrat fw-bold" style="font-size: 0.9rem;">Crankshaft Deflection & Alignment</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <svg class="text-brand-ocean me-2 mt-1 flex-shrink-0" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-brand-steel font-montserrat fw-bold" style="font-size: 0.9rem;">Governor & Actuator Calibration</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <svg class="text-brand-ocean me-2 mt-1 flex-shrink-0" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-brand-steel font-montserrat fw-bold" style="font-size: 0.9rem;">Fuel Injection Timing & Overhaul</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="d-flex align-items-start">
                            <svg class="text-brand-ocean me-2 mt-1 flex-shrink-0" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-brand-steel font-montserrat fw-bold" style="font-size: 0.9rem;">Purifier & Separator Rebuilds</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. Preventative Maintenance Section -->
<section class="py-section bg-brand-navy position-relative" id="maintenance" style="padding-top: 6rem; padding-bottom: 6rem;">
    <div class="glow-blob glow-blob-blue bottom-0 start-0 translate-middle-y" style="width: 600px; height: 600px; opacity: 0.2;"></div>
    <div class="container position-relative z-1">
        <div class="row align-items-center g-5 flex-lg-row-reverse">
            <div class="col-lg-6">
                <div class="position-relative rounded-4 overflow-hidden shadow-lg border border-secondary border-opacity-25" style="aspect-ratio: 4/3;">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-25 z-1"></div>
                    <img src="../assets/images/maintenancee.jpg" alt="Preventative Maintenance" class="img-fluid w-100 h-100 object-fit-cover position-relative z-0">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 text-brand-caution mb-4" style="width: 56px; height: 56px; background: rgba(255, 204, 0, 0.1); border: 1px solid rgba(255, 204, 0, 0.2);">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <h2 class="font-montserrat fw-bold text-white text-uppercase mb-3">Preventative <span class="text-brand-caution">Maintenance</span></h2>
                <p class="text-brand-steel fw-light lh-lg mb-4 fs-6">
                    Shift the paradigm from reactive repair to strategic prevention. Our preventative maintenance programs are designed to satisfy Class requirements, optimize fuel consumption, and eliminate costly off-hire periods before they occur.
                </p>
                <div class="bg-brand-dark rounded-4 p-4 border border-secondary border-opacity-25 mb-4">
                    <h5 class="text-white font-montserrat fw-bold fs-6 mb-3">Condition-Based Monitoring (CBM)</h5>
                    <p class="text-brand-steel fw-light mb-0" style="font-size: 0.9rem;">
                        We utilize advanced thermography, vibration analysis, and lube oil particulate testing to chart degradation curves, allowing us to predict and schedule bearing or seal replacements precisely when needed—maximizing component lifespan.
                    </p>
                </div>
                <ul class="list-unstyled text-brand-steel fw-light lh-lg">
                    <li class="mb-3 d-flex align-items-start">
                        <svg class="text-brand-caution me-3 mt-1 flex-shrink-0" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div><strong class="text-white font-montserrat">Dry-Dock Preparation:</strong> Comprehensive pre-docking inspections, definitive scope-of-work formulation, and parts pre-ordering logistics.</div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <svg class="text-brand-caution me-3 mt-1 flex-shrink-0" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div><strong class="text-white font-montserrat">Lifecycle Management:</strong> Scheduled megger testing, safety valve recertification, and heat exchanger chemical cleaning.</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- 5. Seamless Operation & Global Hubs Section -->
<section class="py-section bg-brand-dark position-relative border-top border-bottom border-secondary border-opacity-10" id="operation" style="padding-top: 6rem; padding-bottom: 6rem;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="position-relative rounded-4 overflow-hidden shadow-lg border border-secondary border-opacity-25" style="aspect-ratio: 4/3;">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-25 z-1"></div>
                    <img src="../assets/images/workflowww.jpg" alt="Seamless Operation" class="img-fluid w-100 h-100 object-fit-cover position-relative z-0">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 text-white mb-4" style="width: 56px; height: 56px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h2 class="font-montserrat fw-bold text-white text-uppercase mb-3">Seamless <span class="text-brand-steel">Operation</span></h2>
                <p class="text-brand-steel fw-light lh-lg mb-4 fs-6">
                    Modern workflows, rapid response times, and efficient logistics. We streamline every project phase so your crew can focus on navigation and cargo. Our riding squads are pre-cleared and deploy globally with specialized tooling to conduct mid-voyage repairs without interrupting transit.
                </p>
                
                <h5 class="text-white font-montserrat fw-bold fs-6 mt-4 mb-3">Global Service Network</h5>
                <ul class="list-unstyled text-brand-steel fw-light lh-lg">
                    <li class="mb-3 d-flex align-items-start">
                        <svg class="text-brand-ocean me-3 mt-1 flex-shrink-0" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div>
                            <strong class="text-white font-montserrat letter-spacing-wide">APAC Hub:</strong> 
                            Singapore & Shanghai <span class="text-brand-ocean opacity-75 ms-1" style="font-size: 0.9em;">(Rapid Deployment Zone)</span>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <svg class="text-brand-ocean me-3 mt-1 flex-shrink-0" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div>
                            <strong class="text-white font-montserrat letter-spacing-wide">EMEA Hub:</strong> 
                            Rotterdam, Dubai & Cape Town
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <svg class="text-brand-ocean me-3 mt-1 flex-shrink-0" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div>
                            <strong class="text-white font-montserrat letter-spacing-wide">Americas Hub:</strong> 
                            Houston & Panama City
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- 6. Our Process -->
<section class="py-5 bg-brand-navy border-bottom border-secondary border-opacity-10">
    <div class="container py-5">
        <div class="text-center mx-auto mb-5 pb-3" style="max-width: 800px;">
            <h2 class="font-montserrat fw-bold text-white text-uppercase mb-3">The Execution Protocol</h2>
            <p class="text-brand-steel fw-light">How we manage complex technical interventions from notification to Class approval.</p>
        </div>

        <div class="row g-4 relative">
            <!-- Connecting Line (Desktop Only) -->
            <div class="col-12 d-none d-lg-block position-absolute top-50 start-0 translate-middle-y z-0" style="height: 2px; background: linear-gradient(90deg, rgba(13,42,74,0) 0%, rgba(135,148,162,0.2) 50%, rgba(13,42,74,0) 100%);"></div>

            <!-- Step 1 -->
            <div class="col-md-6 col-lg-3 text-center position-relative z-1">
                <div class="bg-brand-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-4 border border-secondary border-opacity-25" style="width: 80px; height: 80px;">
                    <span class="font-montserrat fw-bold text-brand-ocean fs-4">01</span>
                </div>
                <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6">Diagnosis</h5>
                <p class="text-brand-steel fw-light fs-7 px-3">Remote assessment and root-cause analysis by our senior technical superintendents.</p>
            </div>

            <!-- Step 2 -->
            <div class="col-md-6 col-lg-3 text-center position-relative z-1">
                <div class="bg-brand-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-4 border border-secondary border-opacity-25" style="width: 80px; height: 80px;">
                    <span class="font-montserrat fw-bold text-brand-caution fs-4">02</span>
                </div>
                <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6">Mobilization</h5>
                <p class="text-brand-steel fw-light fs-7 px-3">Dispatch of OEM-certified engineers, specialized tooling, and required spares via our global hubs.</p>
            </div>

            <!-- Step 3 -->
            <div class="col-md-6 col-lg-3 text-center position-relative z-1">
                <div class="bg-brand-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-4 border border-secondary border-opacity-25" style="width: 80px; height: 80px;">
                    <span class="font-montserrat fw-bold text-white fs-4">03</span>
                </div>
                <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6">Execution</h5>
                <p class="text-brand-steel fw-light fs-7 px-3">Precision repairs conducted dockside, at anchorage, or mid-voyage by our riding squads.</p>
            </div>

            <!-- Step 4 -->
            <div class="col-md-6 col-lg-3 text-center position-relative z-1">
                <div class="bg-brand-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-4 border border-secondary border-opacity-25" style="width: 80px; height: 80px;">
                    <span class="font-montserrat fw-bold text-brand-ocean fs-4">04</span>
                </div>
                <h5 class="font-montserrat fw-bold text-white text-uppercase fs-6">Class Approval</h5>
                <p class="text-brand-steel fw-light fs-7 px-3">Sea trials, comprehensive reporting, and final sign-off by IACS classification societies.</p>
            </div>
        </div>
    </div>
</section>

<!-- 7. Call to Action -->
<section class="py-section bg-brand-dark position-relative overflow-hidden text-center" style="padding-top: 5rem; padding-bottom: 5rem;">
    <div class="glow-blob glow-blob-ocean top-50 start-50 translate-middle" style="width: 800px; height: 400px; opacity: 0.3;"></div>
    
    <div class="container position-relative z-1">
        <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4 display-6">Minimize Downtime. Maximize Output.</h2>
        <p class="text-brand-steel mb-5 mx-auto fw-light" style="max-width: 650px; font-size: 1.1rem;">
            Whether you require an immediate mid-ocean intervention or are formulating specifications for an upcoming dry-dock, connect with our global operations center for a rapid technical assessment.
        </p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
            <a href="contact.php" class="btn btn-brand-blue font-montserrat fw-bold text-uppercase py-3 px-5 rounded-3 letter-spacing-wide d-inline-flex align-items-center justify-content-center shadow-lg" style="transition: all 0.3s ease;">
                Request Service Dispatch
                <svg class="ms-3" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
            <a href="contact.php" class="btn btn-outline-secondary text-white font-montserrat fw-bold text-uppercase py-3 px-5 rounded-3 letter-spacing-wide d-inline-flex align-items-center justify-content-center border-opacity-50" style="transition: all 0.3s ease;">
                Contact Engineering
            </a>
        </div>
    </div>
</section>

<?php include '../assets/includes/footer.php'; ?>