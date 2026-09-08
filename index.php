<?php
session_start();
include 'assets/includes/header.php';
?>

<!-- Section 1: Hero -->
<header class="hero-section text-center">
    <div class="hero-overlay"></div>
    
    <div class="container position-relative z-1 d-flex flex-column align-items-center">

        <div class="glass-pill d-inline-flex align-items-center px-4 py-2 rounded-pill mb-4">
            <span class="rounded-circle bg-brand-blue me-2" style="width: 8px; height: 8px;"></span>
            <span class="text-white text-uppercase font-bold letter-spacing-widest" style="font-size: 0.75rem;">Apex Marine</span>
        </div>

        <h1 class="display-3 font-montserrat fw-bolder text-uppercase text-white mb-4">
            Heavy-Duty Marine <br class="d-none d-md-block">
            <span class="text-gradient-blue">Engineering & Maintenance</span>
        </h1>
        
        <p class="lead text-light mb-5 mx-auto" style="max-width: 700px;">
            Executed by highly trained marine engineers and dedicated crews to keep your vessel moving at peak operational capacity.
        </p>
        
        <a href="pages/contact.php" class="btn btn-brand-blue font-montserrat fw-bold text-uppercase py-3 px-5 rounded-3 letter-spacing-widest d-inline-flex align-items-center">
            Request Immediate Service
            <svg class="ms-3" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
        </a>
    </div>
</header>

<!-- Section 2: Core Competencies -->
<section class="py-section bg-brand-navy position-relative overflow-hidden">
    <div class="glow-blob glow-blob-blue top-0 start-50 translate-middle-x" style="width: 800px; height: 400px;"></div>

    <div class="container position-relative z-1">
        
        <div class="text-center mx-auto mb-5" style="max-width: 800px;">
            <div class="glass-pill d-inline-flex align-items-center px-3 py-1 rounded-pill mb-3">
                <span class="rounded-circle bg-brand-caution me-2" style="width: 6px; height: 6px;"></span>
                <span class="text-white text-uppercase font-montserrat fw-bold letter-spacing-widest" style="font-size: 0.7rem;">Core Competencies</span>
            </div>
            <h2 class="font-montserrat fw-bold text-white text-uppercase mb-4 display-6">Excellence in Maritime Engineering</h2>
        </div>

        <div class="row g-4 g-lg-5">
            <!-- Feature Card 1 -->
            <div class="col-md-6">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue" style="width: 56px; height: 56px; background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2);">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <span class="font-cascadia fw-bold text-secondary letter-spacing-widest">01</span>
                    </div>
                    <h3 class="h4 text-white font-montserrat fw-bold text-uppercase mb-3">Heavy-Duty Ship Repairs</h3>
                    <p class="text-brand-steel mb-0">Comprehensive structural and mechanical repairs engineered to restore performance and guarantee long-term vessel integrity.</p>
                </div>
            </div>

            <!-- Feature Card 2 -->
            <div class="col-md-6">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3 text-brand-blue" style="width: 56px; height: 56px; background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2);">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <span class="font-cascadia fw-bold text-secondary letter-spacing-widest">02</span>
                    </div>
                    <h3 class="h4 text-white font-montserrat fw-bold text-uppercase mb-3">Trust & Reliability</h3>
                    <p class="text-brand-steel mb-0">Providing highly efficient services through a seamlessly organized operation. Our in-house marine engineers and skilled crews instill immediate confidence.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 3: Core Services -->
<section class="py-section bg-brand-dark position-relative border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
    <div class="container position-relative z-1">
        
        <div class="text-center mx-auto mb-5 pb-3" style="max-width: 800px;">
            <span class="text-brand-blue fw-bold letter-spacing-widest text-uppercase" style="font-size: 0.85rem;">What We Do</span>
            <h2 class="font-montserrat display-6 fw-bold text-white text-uppercase mt-2">Our Core Services</h2>
            <p class="text-brand-steel mt-3 fs-6">Specialized marine repair and maintenance operations executed by certified engineers, designed to restore, maintain, and optimize your vessel's critical systems.</p>
        </div>

        <div class="row g-4 g-lg-5">
            
            <!-- Service Card 1 -->
            <div class="col-md-4">
                <a href="/apexx_marine/pages/services.php" class="card service-card text-decoration-none h-100">
                    <div class="img-wrapper">
                        <div class="img-overlay"></div>
                        <img src="assets/images/solutions.jpg" alt="Marine Engineering">
                    </div>
                    <div class="card-body p-4 p-lg-5 d-flex flex-column">
                        <h4 class="card-title text-white font-montserrat fw-bold mb-3 fs-5 text-uppercase">Marine Engineering</h4>
                        <p class="card-text text-brand-steel mb-4 flex-grow-1" style="font-size: 0.9rem;">Expert technical diagnostics, complex system troubleshooting, and customized engineering interventions.</p>
                        
                        <div class="d-flex align-items-center text-brand-blue text-uppercase fw-bold letter-spacing-wide learn-more" style="font-size: 0.8rem;">
                            <span>Learn More</span>
                            <svg class="ms-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Service Card 2 -->
            <div class="col-md-4">
                <a href="/apexx_marine/pages/services.php" class="card service-card text-decoration-none h-100">
                    <div class="img-wrapper">
                        <div class="img-overlay"></div>
                        <img src="/apexx_marine/assets/images/maintenancee.jpg" alt="Preventative Maintenance">
                    </div>
                    <div class="card-body p-4 p-lg-5 d-flex flex-column">
                        <h4 class="card-title text-white font-montserrat fw-bold mb-3 fs-5 text-uppercase">Preventative Maintenance</h4>
                        <p class="card-text text-brand-steel mb-4 flex-grow-1" style="font-size: 0.9rem;">Proactive, scheduled inspection and maintenance engineered to extend the operational lifecycle of your vessel.</p>
                        
                        <div class="d-flex align-items-center text-brand-blue text-uppercase fw-bold letter-spacing-wide learn-more" style="font-size: 0.8rem;">
                            <span>Learn More</span>
                            <svg class="ms-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Service Card 3 -->
            <div class="col-md-4">
                <a href="/apexx_marine/pages/services.php" class="card service-card text-decoration-none h-100">
                    <div class="img-wrapper">
                        <div class="img-overlay"></div>
                        <img src="/apexx_marine/assets/images/workflowww.jpg" alt="Seamless Operation">
                    </div>
                    <div class="card-body p-4 p-lg-5 d-flex flex-column">
                        <h4 class="card-title text-white font-montserrat fw-bold mb-3 fs-5 text-uppercase">Seamless Operation</h4>
                        <p class="card-text text-brand-steel mb-4 flex-grow-1" style="font-size: 0.9rem;">Modern workflows, rapid response times, and efficient logistics. We streamline every project phase.</p>
                        
                        <div class="d-flex align-items-center text-brand-blue text-uppercase fw-bold letter-spacing-wide learn-more" style="font-size: 0.8rem;">
                            <span>Learn More</span>
                            <svg class="ms-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</section>

<!-- Section 4: The Apex Advantage -->
<section class="py-section bg-brand-navy position-relative overflow-hidden border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
    <!-- Ambient Background Glow -->
    <div class="glow-blob glow-blob-ocean top-0 start-50 translate-middle-x" style="width: 800px; height: 400px; opacity: 0.5;"></div>

    <div class="container position-relative z-1">
        
        <!-- Centered Headline -->
        <div class="text-center mx-auto mb-5 pb-2" style="max-width: 800px;">
            <span class="text-brand-ocean fw-bold letter-spacing-widest text-uppercase" style="font-size: 0.85rem;">The Apex Advantage</span>
            <h2 class="font-montserrat display-5 fw-bolder text-white text-uppercase mt-2">Why Choose Apex?</h2>
        </div>

        <!-- Features Grid -->
        <div class="row g-4 g-lg-5 justify-content-center">
            
            <!-- Feature 1 -->
            <div class="col-md-6 col-lg-5">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 feature-item d-flex align-items-start">
                    <div class="icon-box flex-shrink-0" style="background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2); color: var(--brand-blue); width: 60px; height: 60px;">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="ms-4 mt-1">
                        <h4 class="text-white font-montserrat fw-bold mb-3 fs-5 text-uppercase">Expert Personnel</h4>
                        <p class="text-brand-steel mb-0" style="font-size: 0.95rem; line-height: 1.7;">
                            Your vessel is serviced directly by our dedicated team of certified marine engineers and skilled crews. We guarantee precision and uncompromising quality on every job.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-md-6 col-lg-5">
                <div class="glass-card p-4 p-md-5 rounded-4 h-100 feature-item d-flex align-items-start">
                    <div class="icon-box flex-shrink-0" style="background: rgba(46, 107, 156, 0.1); border: 1px solid rgba(46, 107, 156, 0.2); color: var(--brand-blue); width: 60px; height: 60px;">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div class="ms-4 mt-1">
                        <h4 class="text-white font-montserrat fw-bold mb-3 fs-5 text-uppercase">Proven Reliability</h4>
                        <p class="text-brand-steel mb-0" style="font-size: 0.95rem; line-height: 1.7;">
                            Built on a foundation of trust, our heavy-duty standards mean every repair is engineered to last, minimizing your downtime and maximizing fleet performance.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<?php include 'assets/includes/footer.php'; ?>