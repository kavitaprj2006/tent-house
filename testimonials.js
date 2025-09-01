/**
 * Testimonials Module for Mahadev Tent House Website
 * Handles testimonial display, submission, and star rating functionality
 */

class TestimonialsManager {
    constructor() {
        this.testimonialList = document.getElementById("testimonial-list");
        this.form = document.getElementById("testimonial-form");
        this.stars = document.querySelectorAll("#star-rating span");
        this.ratingInput = document.getElementById("rating");
        this.selectedRating = 0;
        
        // Sample data for fallback only (not used if API works)
        this.fallbackTestimonials = [
            {
                id: 1,
                name: "Rajesh Kumar",
                rating: 5,
                message: "Excellent service for our wedding! The tent decoration was beautiful and the team was very professional. Highly recommended!",
                created_at: "2024-01-15T10:30:00Z"
            },
            {
                id: 2,
                name: "Priya Sharma", 
                rating: 5,
                message: "Amazing work for our daughter's birthday party. The lighting and decorations exceeded our expectations. Thank you Mahadev Tent House!",
                created_at: "2024-01-10T14:45:00Z"
            }
        ];
        
        this.init();
    }

    /**
     * Initialize testimonials functionality
     */
    init() {
        this.setupEventListeners();
        this.loadTestimonials();
        this.setupStarRating();
        this.setupFormValidation();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        if (this.form) {
            this.form.addEventListener("submit", (e) => this.handleFormSubmission(e));
        }

        // Setup star rating clicks
        this.stars.forEach(star => {
            star.addEventListener("click", (e) => this.handleStarClick(e));
            star.addEventListener("mouseover", (e) => this.handleStarHover(e));
        });

        // Reset star display on mouse leave
        const starContainer = document.getElementById("star-rating");
        if (starContainer) {
            starContainer.addEventListener("mouseleave", () => this.updateStarDisplay());
        }
    }

    /**
     * Setup star rating functionality
     */
    setupStarRating() {
        this.stars.forEach((star, index) => {
            star.setAttribute('tabindex', '0');
            star.setAttribute('role', 'button');
            star.setAttribute('aria-label', `Rate ${index + 1} star${index > 0 ? 's' : ''}`);
            
            // Keyboard support
            star.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.handleStarClick(e);
                }
            });
        });
    }

    /**
     * Handle star click
     */
    handleStarClick(e) {
        const starValue = parseInt(e.target.getAttribute("data-value"));
        this.selectedRating = starValue;
        this.ratingInput.value = starValue;
        this.updateStarDisplay();
        
        // Provide feedback
        this.showRatingFeedback(starValue);
    }

    /**
     * Handle star hover
     */
    handleStarHover(e) {
        const starValue = parseInt(e.target.getAttribute("data-value"));
        this.highlightStars(starValue);
    }

    /**
     * Highlight stars up to given value
     */
    highlightStars(rating) {
        this.stars.forEach(star => {
            const starValue = parseInt(star.getAttribute("data-value"));
            star.style.color = starValue <= rating ? "gold" : "#ddd";
        });
    }

    /**
     * Update star display based on selected rating
     */
    updateStarDisplay() {
        this.highlightStars(this.selectedRating);
    }

    /**
     * Show rating feedback
     */
    showRatingFeedback(rating) {
        const messages = {
            1: "We appreciate your feedback and will work to improve!",
            2: "Thank you for your feedback. We'll do better next time!",
            3: "Thanks for your review! We're glad we could help.",
            4: "Great to hear you had a good experience with us!",
            5: "Wonderful! Thank you for the excellent rating!"
        };

        const feedback = document.createElement('div');
        feedback.className = 'rating-feedback';
        feedback.style.cssText = `
            position: absolute;
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 100;
            text-align: center;
            width: 100%;
        `;
        feedback.textContent = messages[rating];

        const starContainer = document.getElementById("star-rating");
        const existingFeedback = starContainer.querySelector('.rating-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        starContainer.style.position = 'relative';
        starContainer.appendChild(feedback);

        // Animate in
        requestAnimationFrame(() => {
            feedback.style.opacity = '1';
            feedback.style.transform = 'translateY(0)';
        });

        // Remove after 3 seconds
        setTimeout(() => {
            if (feedback.parentNode) {
                feedback.style.opacity = '0';
                feedback.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    if (feedback.parentNode) {
                        feedback.remove();
                    }
                }, 300);
            }
        }, 3000);
    }

    /**
     * Setup form validation
     */
    setupFormValidation() {
        const nameInput = document.getElementById('name');
        const messageInput = document.getElementById('message');

        if (nameInput) {
            nameInput.addEventListener('input', () => this.validateName(nameInput.value));
        }

        if (messageInput) {
            messageInput.addEventListener('input', () => this.validateMessage(messageInput.value));
        }
    }

    /**
     * Validate name input
     */
    validateName(name) {
        const isValid = name.trim().length >= 2;
        const nameInput = document.getElementById('name');
        
        if (!isValid && name.length > 0) {
            nameInput.classList.add('is-invalid');
            this.showFieldError(nameInput, 'Name must be at least 2 characters long');
        } else {
            nameInput.classList.remove('is-invalid');
            this.clearFieldError(nameInput);
        }
        
        return isValid;
    }

    /**
     * Validate message input
     */
    validateMessage(message) {
        const isValid = message.trim().length >= 10;
        const messageInput = document.getElementById('message');
        
        if (!isValid && message.length > 0) {
            messageInput.classList.add('is-invalid');
            this.showFieldError(messageInput, 'Message must be at least 10 characters long');
        } else {
            messageInput.classList.remove('is-invalid');
            this.clearFieldError(messageInput);
        }
        
        return isValid;
    }

    /**
     * Show field error
     */
    showFieldError(field, message) {
        let errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }

    /**
     * Clear field error
     */
    clearFieldError(field) {
        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    }

    /**
     * Handle form submission
     */
    handleFormSubmission(e) {
        e.preventDefault();
        
        const name = document.getElementById("name").value.trim();
        const rating = this.ratingInput.value;
        const message = document.getElementById("message").value.trim();

        // Validate form
        if (!this.validateForm(name, rating, message)) {
            return;
        }

        // Show loading state
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.innerHTML = '<span class="loading"></span> Submitting...';
        submitBtn.disabled = true;

        // Prepare form data
        const formData = {
            name: name,
            rating: parseInt(rating),
            message: message
        };

        // Submit review (replace with actual API call)
        this.submitReview(formData)
            .then(response => {
                if (response.success) {
                    this.showNotification('success', 'Thank you for your review! It has been submitted successfully.');
                    this.resetForm();
                    this.loadTestimonials(); // Reload testimonials
                } else {
                    throw new Error('Submission failed');
                }
            })
            .catch(error => {
                console.error('Error submitting review:', error);
                this.showNotification('error', 'Sorry, there was an error submitting your review. Please try again.');
            })
            .finally(() => {
                // Reset button
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    }

    /**
     * Validate form data
     */
    validateForm(name, rating, message) {
        const errors = [];

        if (!name) {
            errors.push('Name is required');
        } else if (name.length < 2) {
            errors.push('Name must be at least 2 characters long');
        }

        if (!rating) {
            errors.push('Please select a star rating');
            this.highlightStarError();
        }

        if (!message) {
            errors.push('Message is required');
        } else if (message.length < 10) {
            errors.push('Message must be at least 10 characters long');
        }

        if (errors.length > 0) {
            this.showNotification('error', errors.join('<br>'));
            return false;
        }

        return true;
    }

    /**
     * Highlight star rating error
     */
    highlightStarError() {
        const starContainer = document.getElementById("star-rating");
        starContainer.style.animation = 'shake 0.5s ease-in-out';
        
        setTimeout(() => {
            starContainer.style.animation = '';
        }, 500);

        // Add shake animation if not exists
        if (!document.querySelector('#shake-animation')) {
            const style = document.createElement('style');
            style.id = 'shake-animation';
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Submit review to actual backend API
     */
    async submitReview(formData) {
        try {
            const response = await fetch('php/savereview.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    name: formData.name,
                    rating: formData.rating,
                    message: formData.message
                })
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                return { success: true, message: data.message };
            } else {
                throw new Error(data.message || 'Submission failed');
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            throw error;
        }
    }

    /**
     * Load and display testimonials from backend
     */
    async loadTestimonials() {
        try {
            // Show loading state
            if (this.testimonialList) {
                this.testimonialList.innerHTML = '<div class="text-center p-4"><div class="loading"></div> Loading testimonials...</div>';
            }

            const testimonials = await this.fetchTestimonials();
            this.displayTestimonials(testimonials);
            
        } catch (error) {
            console.error('Error loading testimonials:', error);
            
            // Fall back to sample data if API fails
            console.log('API failed, using sample testimonials...');
            this.displayTestimonials(this.fallbackTestimonials);
        }
    }

    /**
     * Fetch testimonials from backend API
     */
    async fetchTestimonials() {
        try {
            const response = await fetch('php/getreviews.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const testimonials = await response.json();
            return Array.isArray(testimonials) ? testimonials : [];
            
        } catch (error) {
            console.error('Error fetching testimonials:', error);
            throw error;
        }
    }

    /**
     * Display testimonials
     */
    displayTestimonials(testimonials) {
        if (!this.testimonialList) return;

        this.testimonialList.innerHTML = "";
        
        if (testimonials.length === 0) {
            this.testimonialList.innerHTML = '<div class="text-center p-4 text-muted">No testimonials yet. Be the first to leave a review!</div>';
            return;
        }

        testimonials.forEach((testimonial, index) => {
            const testimonialElement = this.createTestimonialElement(testimonial);
            
            // Add staggered animation
            testimonialElement.style.opacity = '0';
            testimonialElement.style.transform = 'translateY(20px)';
            
            this.testimonialList.appendChild(testimonialElement);
            
            // Animate in with delay
            setTimeout(() => {
                testimonialElement.style.transition = 'all 0.5s ease';
                testimonialElement.style.opacity = '1';
                testimonialElement.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    /**
     * Create testimonial HTML element
     */
    createTestimonialElement(testimonial) {
        const div = document.createElement("div");
        div.className = "testimonial-card";
        
        const stars = "★".repeat(testimonial.rating) + "☆".repeat(5 - testimonial.rating);
        const formattedDate = new Date(testimonial.created_at).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong class="testimonial-name">${this.escapeHtml(testimonial.name)}</strong>
                <span class="testimonial-rating" style="color: gold; font-size: 18px;" title="${testimonial.rating} out of 5 stars">${stars}</span>
            </div>
            <p class="testimonial-message mb-2">${this.escapeHtml(testimonial.message)}</p>
            <small class="testimonial-date text-muted">
                <i class="fas fa-clock me-1"></i>
                ${formattedDate}
            </small>
        `;

        return div;
    }

    /**
     * Reset form
     */
    resetForm() {
        if (this.form) {
            this.form.reset();
            this.selectedRating = 0;
            this.ratingInput.value = '';
            this.updateStarDisplay();
            
            // Clear any validation errors
            const invalidInputs = this.form.querySelectorAll('.is-invalid');
            invalidInputs.forEach(input => {
                input.classList.remove('is-invalid');
                this.clearFieldError(input);
            });
        }
    }

    /**
     * Show notification
     */
    showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'} me-2"></i>
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 150);
            }
        }, 5000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }

    /**
     * Get testimonials count
     */
    getTestimonialsCount() {
        return this.mockTestimonials.length;
    }

    /**
     * Get average rating
     */
    getAverageRating() {
        if (this.mockTestimonials.length === 0) return 0;
        
        const totalRating = this.mockTestimonials.reduce((sum, testimonial) => sum + testimonial.rating, 0);
        return (totalRating / this.mockTestimonials.length).toFixed(1);
    }
}

// Initialize testimonials when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.testimonialsManager = new TestimonialsManager();
    console.log('⭐ Testimonials module initialized successfully!');
});

// Export for external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TestimonialsManager;
}