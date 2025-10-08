(function () {
    'use strict';

    const ratingr = {

        config: {
            ajaxUrl: ratingr_params.ajax_url,
            nonce: ratingr_params.nonce,
            canRate: ratingr_params.can_rate,
            alreadyRated: ratingr_params.already_rated,
            ratingTexts: ratingr_params.rating_texts,
            halfStarEnabled: true,
            animationSpeed: 200
        },

        init: function () {
            const ratingComponents = document.querySelectorAll('.ratingr-rating');

            ratingComponents.forEach(component => {
                this.initializeComponent(component);
            });
        },

        initializeComponent: function (component) {
            const postId = component.dataset.postId;
            const starsContainer = component.querySelector('.ratingr-stars-container');
            const hoverStars = component.querySelectorAll('.ratingr-stars-hover i');
            const ratingMessage = component.querySelector('.ratingr-rating-message');

            if (this.config.alreadyRated.includes(postId) || !this.config.canRate) {
                component.classList.add('ratingr-already-rated');
                component.setAttribute('title', 'You have already rated this post');
                return;
            }

            component.classList.add('ratingr-interactive');

            this.setupHoverInteractions(component, hoverStars);

            this.setupClickInteractions(component, hoverStars, postId, ratingMessage);

            this.setupKeyboardInteractions(component, hoverStars, postId, ratingMessage);
        },

        setupHoverInteractions: function (component, hoverStars) {
            const starsContainer = component.querySelector('.ratingr-stars-container');

            starsContainer.addEventListener('mouseenter', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.add('ratingr-hovering');
            });

            starsContainer.addEventListener('mouseleave', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.remove('ratingr-hovering');
                this.resetStarHighlights(hoverStars);
            });

            hoverStars.forEach(star => {
                star.addEventListener('mousemove', (e) => {
                    if (component.classList.contains('ratingr-already-rated')) return;

                    const starValue = parseFloat(star.dataset.value);
                    const rect = star.getBoundingClientRect();
                    const starWidth = rect.width;
                    const mouseX = e.clientX - rect.left;

                    let rating = starValue;
                    if (this.config.halfStarEnabled && mouseX < starWidth / 2) {
                        rating -= 0.5;
                    }

                    this.updateStarHighlights(hoverStars, rating);
                });
            });
        },

        setupClickInteractions: function (component, hoverStars, postId, ratingMessage) {
            hoverStars.forEach(star => {
                star.addEventListener('click', (e) => {
                    if (component.classList.contains('ratingr-already-rated')) return;

                    const starValue = parseFloat(star.dataset.value);
                    const rect = star.getBoundingClientRect();
                    const starWidth = rect.width;
                    const mouseX = e.clientX - rect.left;

                    let rating = starValue;
                    if (this.config.halfStarEnabled && mouseX < starWidth / 2) {
                        rating -= 0.5;
                    }

                    this.submitRating(component, postId, rating, ratingMessage);
                });
            });
        },

        setupKeyboardInteractions: function (component, hoverStars, postId, ratingMessage) {
            const starsContainer = component.querySelector('.ratingr-stars-container');

            starsContainer.setAttribute('tabindex', '0');
            starsContainer.setAttribute('role', 'slider');
            starsContainer.setAttribute('aria-label', 'Rating');
            starsContainer.setAttribute('aria-valuemin', '0');
            starsContainer.setAttribute('aria-valuemax', '5');
            starsContainer.setAttribute('aria-valuenow', '0');
            starsContainer.setAttribute('aria-valuetext', 'No rating');

            let currentRating = 0;

            starsContainer.addEventListener('focus', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.add('ratingr-focus');
            });

            starsContainer.addEventListener('blur', () => {
                if (component.classList.contains('ratingr-already-rated')) return;
                component.classList.remove('ratingr-focus');
                this.resetStarHighlights(hoverStars);
            });

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
                starsContainer.setAttribute('aria-valuenow', currentRating);
                starsContainer.setAttribute('aria-valuetext', currentRating + ' out of 5 stars');
            });
        },

        updateStarHighlights: function (stars, rating) {
            stars.forEach((star, index) => {
                const starValue = index + 1;

                if (starValue <= rating) {
                    star.className = 'icon icon--star-fill';
                } else if (starValue - 0.5 <= rating) {
                    star.className = 'icon icon--star-half';
                } else {
                    star.className = 'icon icon--star';
                }
            });
        },

        resetStarHighlights: function (stars) {
            stars.forEach(star => {
                star.className = 'icon icon--star';
            });
        },

        submitRating: function (component, postId, rating, messageElement) {
            const formData = new FormData();
            formData.append('action', 'ratingr_submit_rating');
            formData.append('post_id', postId);
            formData.append('rating', rating);
            formData.append('nonce', this.config.nonce);

            component.classList.add('ratingr-loading');

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
                component.classList.add('ratingr-already-rated');
                component.classList.remove('ratingr-loading');
                
                this.updateRatingDisplay(component, response.data);
            })
            .catch(error => {
                console.error('Error submitting rating:', error);
                component.classList.remove('ratingr-loading');
                
                if (messageElement) {
                    messageElement.textContent = 'Error submitting rating. Please try again.';
                    messageElement.classList.add('ratingr-error');
                }
            });
        },

        updateRatingDisplay: function (component, data) {
            const foreground = component.querySelector('.ratingr-stars-foreground');
            const ratingValue = component.querySelector('.ratingr-rating-value');
            const ratingCount = component.querySelector('.ratingr-rating-count');

            const percentage = (data.average_rating / 5) * 100;
            foreground.style.width = percentage + '%';

            ratingValue.textContent = '(' + data.average_rating + '/5)';

            const voteText = data.total_votes === 1 ? 'voto' : 'votos';
            ratingCount.textContent = data.total_votes + ' ' + voteText;

            const preview = component.querySelector('.ratingr-rating-preview');
            if (preview) {
                preview.remove();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        ratingr.init();
    });

})();