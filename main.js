/**
 * Main JavaScript file for Mahadev Tent House Website
 * Contains core functionality for navigation, forms, gallery, and animations
 */

// ===== CONFIGURATION =====
const CONFIG = {
    animationDelay: 100,
    scrollOffset: 80,
    modalZIndex: 1000,
    debounceDelay: 300
};

// ===== UTILITY FUNCTIONS =====
const Utils = {
    /**
     * Debounce function to limit function calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function to limit function calls
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Check if element is in viewport
     */
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    /**
     * Smooth scroll to element
     */
    smoothScrollTo(element, offset = CONFIG.scrollOffset) {
        const elementPosition = element.offsetTop;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
};

// ===== NAVIGATION MODULE =====
const Navigation = {
    init() {
        this.setupSmoothScrolling();
        this.setupActiveNavigation();
        this.setupMobileMenu();
    },

    /**
     * Setup smooth scrolling for anchor links
     */
    setupSmoothScrolling() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        
        anchorLinks.forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                
                const targetId = anchor.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    Utils.smoothScrollTo(targetElement);
                    
                    // Update URL without jumping
                    history.pushState(null, null, targetId);
                    
                    // Close mobile menu if open
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse.classList.contains('show')) {
                        bootstrap.Collapse.getInstance(navbarCollapse).hide();
                    }
                }
            });
        });
    },

    /**
     * Setup active navigation highlighting
     */
    setupActiveNavigation() {
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link[href^="#"]');
        const sections = document.querySelectorAll('section[id]');

        const updateActiveNav = Utils.throttle(() => {
            let currentSection = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - CONFIG.scrollOffset;
                const sectionHeight = section.offsetHeight;
                
                if (window.pageYOffset >= sectionTop && 
                    window.pageYOffset < sectionTop + sectionHeight) {
                    currentSection = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${currentSection}`) {
                    link.classList.add('active');
                }
            });
        }, 100);

        window.addEventListener('scroll', updateActiveNav);
        updateActiveNav(); // Run once on load
    },

    /**
     * Setup mobile menu functionality
     */
    setupMobileMenu() {
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');

        if (navbarToggler && navbarCollapse) {
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!navbarToggler.contains(e.target) && 
                    !navbarCollapse.contains(e.target) &&
                    navbarCollapse.classList.contains('show')) {
                    bootstrap.Collapse.getInstance(navbarCollapse).hide();
                }
            });
        }
    }
};

// ===== GALLERY MODULE =====
const Gallery = {
    init() {
        this.setupGalleryModal();
        this.setupLazyLoading();
    },

    /**
     * Setup gallery modal functionality
     */
    setupGalleryModal() {
        const galleryImages = document.querySelectorAll('.gallery-img');
        
        galleryImages.forEach(img => {
            img.addEventListener('click', (e) => {
                this.createModal(e.target);
            });

            // Add keyboard support
            img.setAttribute('tabindex', '0');
            img.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.createModal(e.target);
                }
            });
        });
    },

    /**
     * Create and display image modal
     */
    createModal(imgElement) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'gallery-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: ${CONFIG.modalZIndex};
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            position: relative;
            max-width: 90%;
            max-height: 90%;
            animation: modalZoom 0.3s ease;
        `;

        // Create image
        const modalImg = document.createElement('img');
        modalImg.src = imgElement.src;
        modalImg.alt = imgElement.alt;
        modalImg.style.cssText = `
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        `;

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = `
            position: absolute;
            top: -40px;
            right: -40px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        `;

        // Add modal animation keyframes
        if (!document.querySelector('#modal-animations')) {
            const style = document.createElement('style');
            style.id = 'modal-animations';
            style.textContent = `
                @keyframes modalZoom {
                    from { transform: scale(0.5); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
                @keyframes modalZoomOut {
                    from { transform: scale(1); opacity: 1; }
                    to { transform: scale(0.5); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        // Assemble modal
        modalContent.appendChild(modalImg);
        modalContent.appendChild(closeBtn);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Show modal with animation
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
        });

        // Close modal function
        const closeModal = () => {
            modal.style.opacity = '0';
            modalContent.style.animation = 'modalZoomOut 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(modal)) {
                    document.body.removeChild(modal);
                }
            }, 300);
        };

        // Event listeners
        modal.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        
        // Prevent modal close when clicking on image
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Keyboard support
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleKeyDown);
            }
        };
        document.addEventListener('keydown', handleKeyDown);

        // Focus management
        closeBtn.focus();
    },

    /**
     * Setup lazy loading for gallery images
     */
    setupLazyLoading() {
        const images = document.querySelectorAll('.gallery-img');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('fade-in');
                        observer.unobserve(img);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            images.forEach(img => imageObserver.observe(img));
        }
    }
};

// ===== CONTACT FORM MODULE (REMOVED - NO LONGER NEEDED) =====
// Contact form functionality has been removed as requested
// Only contact information display remains

// ===== ANIMATIONS MODULE =====
const Animations = {
    init() {
        this.setupScrollAnimations();
        this.setupCounterAnimations();
    },

    /**
     * Setup scroll-triggered animations
     */
    setupScrollAnimations() {
        const animatedElements = document.querySelectorAll('.service-card, .testimonial-card, section');
        
        if ('IntersectionObserver' in window) {
            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            animatedElements.forEach(element => {
                animationObserver.observe(element);
            });
        }
    },

    /**
     * Setup counter animations (if you have any stats)
     */
    setupCounterAnimations() {
        const counters = document.querySelectorAll('.counter');
        
        if (counters.length > 0 && 'IntersectionObserver' in window) {
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            counters.forEach(counter => counterObserver.observe(counter));
        }
    },

    /**
     * Animate counter numbers
     */
    animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };

        updateCounter();
    }
};

// ===== PERFORMANCE MODULE =====
const Performance = {
    init() {
        this.optimizeImages();
        this.setupServiceWorker();
    },

    /**
     * Optimize image loading
     */
    optimizeImages() {
        const images = document.querySelectorAll('img');
        
        images.forEach(img => {
            // Add loading="lazy" if not already present
            if (!img.hasAttribute('loading')) {
                img.setAttribute('loading', 'lazy');
            }
            
            // Add error handling
            img.addEventListener('error', () => {
                console.warn(`Failed to load image: ${img.src}`);
                // Optionally set a fallback image
                // img.src = 'path/to/fallback-image.jpg';
            });
        });
    },

    /**
     * Setup service worker for caching (optional)
     */
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    }
};

// ===== MAIN INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all modules (Contact Form module removed)
    Navigation.init();
    Gallery.init();
    Animations.init();
    Performance.init();
    
    console.log('🏠 Mahadev Tent House website initialized successfully!');
});

// ===== WINDOW LOAD EVENT =====
window.addEventListener('load', () => {
    // Hide loading spinner if exists
    const loader = document.querySelector('.page-loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 300);
    }
});

// ===== ERROR HANDLING =====
window.addEventListener('error', (e) => {
    console.error('JavaScript error:', e.error);
    // Optionally send error to analytics or error reporting service
});

// Export modules for potential external use (Contact Form module removed)
window.MahadevTentHouse = {
    Navigation,
    Gallery,
    Animations,
    Performance,
    Utils
};