/**
 * ratingr Rating Component
 */
(function () {
    'use strict';

    // Main ratingr object
    const ratingr = {

        // Configuration
        config: {
            ajaxUrl: ratingr_params.ajax_url,
            nonce: ratingr_params.nonce,
            canRate: ratingr_params.can_rate,
            alreadyRated: ratingr_params.already_rated,
            ratingTexts: ratingr_params.rating_texts,
            halfStarEnabled: true,
            animationSpeed: 200
        },

        // Initialize all rating components on the page
        init: function () {
            // Find all rating components
            const ratingComponents = document.querySelectorAll('.ratingr-rating');

            // Initialize each component
            ratingComponents.forEach(component => {
                this.initializeComponent(component);
            });
        },

        // Initialize a single rating component
        initializeComponent: function (component) {
            const postId = component.dataset.postId;
            const starsContainer = component.querySelector('.ratingr-stars-container');
            const hoverStars = component.querySelectorAll('.ratingr-stars-hover i');
            const ratingMessage = component.querySelector('.ratingr-rating-message');

            // Skip interactive features if user already rated or can't rate
            if (this.config.alreadyRated.includes(postId) || !this.config.canRate) {
                component.classList.add('ratingr-already-rated');
                component.setAttribute('title', 'You have already rated this post');
                return;
            }

            // Make component interactive
            component.classList.add('ratingr-interactive');

            // Set up hover interactions
            this.setupHoverInteractions(component, hoverStars);

            // Set up click interactions
            this.setupClickInteractions(component, hoverStars, postId, ratingMessage);

            // Set up keyboard interactions for accessibility
            this.setupKeyboardInteractions(component, hoverStars, postId, ratingMessage);
        },

        // Set up hover interactions
        setupHoverInteractions: function (component, hoverStars) {
            const starsContainer = component.querySelector('.ratingr-stars-container');

            // Mouse enter container
            starsContainer.addEventListener('mouseenter', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.add('ratingr-hovering');
            });

            // Mouse leave container
            starsContainer.addEventListener('mouseleave', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.remove('ratingr-hovering');
                this.resetStarHighlights(hoverStars);
            });

            // Hover over individual stars
            hoverStars.forEach(star => {
                star.addEventListener('mousemove', (e) => {
                    if (component.classList.contains('ratingr-already-rated')) return;

                    const starValue = parseFloat(star.dataset.value);
                    const rect = star.getBoundingClientRect();
                    const starWidth = rect.width;
                    const mouseX = e.clientX - rect.left;

                    // Determine if we're on the left half (for half-star ratings)
                    let rating = starValue;
                    if (this.config.halfStarEnabled && mouseX < starWidth / 2) {
                        rating -= 0.5;
                    }

                    this.updateStarHighlights(hoverStars, rating);
                });
            });
        },

        // Set up click interactions
        setupClickInteractions: function (component, hoverStars, postId, ratingMessage) {
            hoverStars.forEach(star => {
                star.addEventListener('click', (e) => {
                    if (component.classList.contains('ratingr-already-rated')) return;

                    const starValue = parseFloat(star.dataset.value);
                    const rect = star.getBoundingClientRect();
                    const starWidth = rect.width;
                    const mouseX = e.clientX - rect.left;

                    // Determine if we're on the left half (for half-star ratings)
                    let rating = starValue;
                    if (this.config.halfStarEnabled && mouseX < starWidth / 2) {
                        rating -= 0.5;
                    }

                    // Submit the rating
                    this.submitRating(component, postId, rating, ratingMessage);
                });
            });
        },

        // Set up keyboard interactions for accessibility
        setupKeyboardInteractions: function (component, hoverStars, postId, ratingMessage) {
            const starsContainer = component.querySelector('.ratingr-stars-container');

            // Make stars container focusable
            starsContainer.setAttribute('tabindex', '0');
            starsContainer.setAttribute('role', 'slider');
            starsContainer.setAttribute('aria-label', 'Rating');
            starsContainer.setAttribute('aria-valuemin', '0');
            starsContainer.setAttribute('aria-valuemax', '5');
            starsContainer.setAttribute('aria-valuenow', '0');
            starsContainer.setAttribute('aria-valuetext', 'No rating');

            let currentRating = 0;

            // Focus events
            starsContainer.addEventListener('focus', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.add('ratingr-focus');
            });

            starsContainer.addEventListener('blur', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.remove('ratingr-focus');
                this.resetStarHighlights(hoverStars);
            });

            // Keyboard navigation
            starsContainer.addEventListener('keydown', (e) => {
                if (component.classList.contains('ratingr-already-rated')) return;

                switch (e.key) {
                    case 'ArrowRight':
                    case 'ArrowUp':
                        e.preventDefault();
                        currentRating = Math.min(5, currentRating + (this.config.halfStarEnabled ? 0.5 : 1));
                        break;

                    case 'ArrowLeft':
                    case 'ArrowDown':
                        e.preventDefault();
                        currentRating = Math.max(0, currentRating - (this.config.halfStarEnabled ? 0.5 : 1));
                        break;

                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        if (currentRating > 0) {
                            this.submitRating(component, postId, currentRating, ratingMessage);
                        }
                        return;

                    default:
                        return;
                }

                this.updateStarHighlights(hoverStars, currentRating);
                // Update ARIA attributes
                starsContainer.setAttribute('aria-valuenow', currentRating);
                starsContainer.setAttribute('aria-valuetext', currentRating + ' out of 5 stars');
            });
        },

        // Update star highlights based on rating
        updateStarHighlights: function (stars, rating) {
            stars.forEach((star, index) => {
                const starValue = index + 1;

                if (starValue <= rating) {
                    // Full star
                    star.className = 'icon icon--star-fill';
                } else if (starValue - 0.5 <= rating) {
                    // Half star
                    star.className = 'icon icon--star-half';
                } else {
                    // Empty star
                    star.className = 'icon icon--star';
                }
            });
        },

        // Reset star highlights
        resetStarHighlights: function (stars) {
            stars.forEach(star => {
                star.className = 'icon icon--star';
            });
        },

        // Submit rating via fetch API
        submitRating: function (component, postId, rating, messageElement) {
            // Prepare data
            const formData = new FormData();
            formData.append('action', 'ratingr_submit_rating');
            formData.append('post_id', postId);
            formData.append('rating', rating);
            formData.append('nonce', this.config.nonce);

            component.classList.add('ratingr-loading');

            // Send fetch request
            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(response => {
                // Mark component as already rated
                component.classList.add('ratingr-already-rated');
                component.classList.remove('ratingr-loading');
                
                // Update the rating display with new data
                this.updateRatingDisplay(component, response.data);
            })
            .catch(error => {
                console.error('Error submitting rating:', error);
                component.classList.remove('ratingr-loading');
                
                // Show error message if available
                if (messageElement) {
                    messageElement.textContent = 'Error submitting rating. Please try again.';
                    messageElement.classList.add('ratingr-error');
                }
            });
        },

        // Update rating display with new data
        updateRatingDisplay: function (component, data) {
            const foreground = component.querySelector('.ratingr-stars-foreground');
            const ratingValue = component.querySelector('.ratingr-rating-value');
            const ratingCount = component.querySelector('.ratingr-rating-count');

            // Update width of foreground stars (percentage)
            const percentage = (data.average_rating / 5) * 100;
            foreground.style.width = percentage + '%';

            // Update rating text
            ratingValue.textContent = '(' + data.average_rating + '/5)';

            // Update vote count
            const voteText = data.total_votes === 1 ? 'voto' : 'votos';
            ratingCount.textContent = data.total_votes + ' ' + voteText;

            // Remove any preview elements
            const preview = component.querySelector('.ratingr-rating-preview');
            if (preview) {
                preview.remove();
            }
        }
    };

    // Initialize when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        ratingr.init();
    });

})();