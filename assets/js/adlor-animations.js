/**
 * ADLOR Animation System
 * Comprehensive animation library for the entire ADLOR system
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all animations
    initializeAnimations();
    createFloatingParticles();
    setupIntersectionObserver();
    addHoverEffects();
    addPageSpecificAnimations();
    addLoadingAnimations();
    addInteractiveEffects();
});

/**
 * Initialize basic animations for common elements
 */
function initializeAnimations() {
    // Add fade-in animation to main content
    const mainContent = document.querySelector('main, .container, .page-content');
    if (mainContent) {
        mainContent.classList.add('animate-fade-in-up');
    }
    
    // Add stagger animation to dashboard cards
    const dashboardCards = document.querySelectorAll('.card, .dashboard-card, .stat-card');
    dashboardCards.forEach((card, index) => {
        card.classList.add('animate-fade-in-up');
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Navigation animations removed as requested
    // Explicitly remove any animations from navigation elements
    const navElements = document.querySelectorAll('.navbar, .navbar *, .nav-link, .dropdown, .dropdown *, .mobile-menu, .mobile-menu *');
    navElements.forEach(element => {
        element.style.animation = 'none';
        element.style.transform = 'none';
        element.style.transition = 'color 0.2s ease, background 0.2s ease, opacity 0.2s ease';
    });
}

/**
 * Create floating particles background
 */
function createFloatingParticles() {
    // Only add particles if not already present
    if (document.querySelector('.floating-particles')) {
        return;
    }

    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'floating-particles';
    particlesContainer.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    `;

    // Create 12 particles
    for (let i = 0; i < 12; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';

        // Random positioning and animation delay
        particle.style.cssText = `
            position: absolute;
            width: ${2 + Math.random() * 4}px;
            height: ${2 + Math.random() * 4}px;
            background: rgba(0, 255, 204, ${0.3 + Math.random() * 0.5});
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            animation: float ${4 + Math.random() * 4}s ease-in-out infinite;
            animation-delay: ${Math.random() * 6}s;
        `;

        particlesContainer.appendChild(particle);
    }

    document.body.appendChild(particlesContainer);
}

/**
 * Add hover effects to interactive elements
 */
function addHoverEffects() {
    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn, button');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.2)';
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Add hover effects to cards
    const cards = document.querySelectorAll('.card, .feature-card, .admin-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.15)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Add page-specific animations
 */
function addPageSpecificAnimations() {
    const currentPage = window.location.pathname;

    // QR code page animations
    if (currentPage.includes('qr')) {
        const qrElements = document.querySelectorAll('.qr-code, .qr-container');
        qrElements.forEach(element => {
            element.style.animation = 'scaleIn 0.8s ease-out, glow 2s ease-in-out infinite alternate';
        });
    }

    // Dashboard animations
    if (currentPage.includes('dashboard')) {
        const widgets = document.querySelectorAll('.dashboard-widget, .stat-card');
        widgets.forEach((widget, index) => {
            widget.style.animation = `slideInUp 1s ease-out ${index * 0.2}s both`;
        });
    }
}

/**
 * Add loading animations
 */
function addLoadingAnimations() {
    // Show loading animation for forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('input[type="submit"], button[type="submit"]');
            if (submitBtn) {
                submitBtn.style.animation = 'pulse 2s ease-in-out infinite';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds (fallback)
                setTimeout(() => {
                    submitBtn.style.animation = '';
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
}

/**
 * Set up scroll-triggered animations using Intersection Observer
 */
function setupScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                
                // Add appropriate animation based on element type
                if (element.classList.contains('table')) {
                    animateTableRows(element);
                } else if (element.classList.contains('alert')) {
                    element.classList.add('animate-slide-in-down');
                } else if (element.classList.contains('modal')) {
                    element.classList.add('animate-scale-in');
                } else {
                    element.classList.add('animate-fade-in-up');
                }
                
                observer.unobserve(element);
            }
        });
    }, observerOptions);
    
    // Observe elements that should animate on scroll
    const elementsToAnimate = document.querySelectorAll(
        '.card:not(.animate-fade-in-up), .table, .alert, .form-group, .btn-group, .list-group'
    );
    
    elementsToAnimate.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Add page load animations
 */
function addPageLoadAnimations() {
    // Animate page title
    const pageTitle = document.querySelector('h1, .page-title');
    if (pageTitle) {
        pageTitle.classList.add('animate-fade-in-left');
    }
    
    // Animate breadcrumbs
    const breadcrumbs = document.querySelector('.breadcrumb');
    if (breadcrumbs) {
        breadcrumbs.classList.add('animate-fade-in-right');
        breadcrumbs.style.animationDelay = '0.2s';
    }
    
    // Animate QR codes
    const qrCodes = document.querySelectorAll('.qr-code, .qr-container');
    qrCodes.forEach(qr => {
        qr.classList.add('animate-scale-in');
        qr.style.animationDelay = '0.5s';
    });
}

/**
 * Add form animations
 */
function addFormAnimations() {
    // Animate form groups
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        group.classList.add('animate-fade-in-up');
        group.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Add focus animations to form controls
    const formControls = document.querySelectorAll('.form-control, .form-input');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'all 0.3s ease';
        });
        
        control.addEventListener('blur', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * Add button animations
 */
function addButtonAnimations() {
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        // Add hover effect
        button.addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
        
        // Add click animation
        button.addEventListener('click', function() {
            if (!this.disabled) {
                this.classList.add('animate-pulse');
                setTimeout(() => {
                    this.classList.remove('animate-pulse');
                }, 600);
            }
        });
    });
}

/**
 * Animate table rows with stagger effect
 */
function animateTableRows(table) {
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        row.classList.add('stagger-item');
        row.style.animationDelay = `${index * 0.05}s`;
    });
}

/**
 * Add success animation to elements
 */
function addSuccessAnimation(element) {
    element.classList.add('success-state');
    setTimeout(() => {
        element.classList.remove('success-state');
    }, 800);
}

/**
 * Add error animation to elements
 */
function addErrorAnimation(element) {
    element.classList.add('error-state');
    setTimeout(() => {
        element.classList.remove('error-state');
    }, 600);
}

/**
 * Show loading animation
 */
function showLoadingAnimation(element) {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.style.display = 'inline-block';
    spinner.style.marginLeft = '10px';
    element.appendChild(spinner);
    return spinner;
}

/**
 * Hide loading animation
 */
function hideLoadingAnimation(spinner) {
    if (spinner && spinner.parentNode) {
        spinner.parentNode.removeChild(spinner);
    }
}

// Export functions for global use
window.ADLORAnimations = {
    addSuccessAnimation,
    addErrorAnimation,
    showLoadingAnimation,
    hideLoadingAnimation,
    animateTableRows
};
