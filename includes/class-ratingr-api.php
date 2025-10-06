<?php
/**
 * API functionality for ratingr plugin
 */
class ratingr_API {
    /**
     * Instance of this class
     *
     * @var ratingr_API
     */
    private static $instance;
    
    /**
     * Get instance of this class
     *
     * @return ratingr_API
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_ratingr_submit_rating', array($this, 'handle_rating_submission'));
        add_action('wp_ajax_nopriv_ratingr_submit_rating', array($this, 'handle_rating_submission'));
    }
    
    /**
     * Handle rating submission
     */
    public function handle_rating_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ratingr_rating_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed.', 'ratingr')
            ));
        }
        
        // Get and validate post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid post ID.', 'ratingr')
            ));
        }
        
        // Get and validate rating
        $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0;
        if ($rating < 0 || $rating > 5 || ($rating * 2) % 1 !== 0) {
            wp_send_json_error(array(
                'message' => __('Invalid rating value. Must be between 0 and 5 in 0.5 increments.', 'ratingr')
            ));
        }
        
        // Check if user already rated
        $user_id = get_current_user_id();
        $ip_address = ratingr_Rating::get_instance()->get_user_ip();
        $cookie_id = isset($_COOKIE['ratingr_user_id']) ? sanitize_text_field($_COOKIE['ratingr_user_id']) : '';
        
        if (RATINGR_DB::user_already_rated($post_id, $user_id, $ip_address, $cookie_id)) {
            wp_send_json_error(array(
                'message' => __('You have already rated this post.', 'ratingr')
            ));
        }
        
        // Save rating
        $result = RATINGR_DB::save_rating($post_id, $rating, $user_id, $ip_address, $cookie_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        // Get updated stats
        $stats = RATINGR_DB::get_stats($post_id);
        
        // Set cookie for non-logged in users
        if (!$user_id && !$cookie_id) {
            $new_cookie_id = wp_generate_uuid4();
            setcookie('ratingr_user_id', $new_cookie_id, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl());
        }
        
        // Return success response
        wp_send_json_success(array(
            'message' => __('Rating submitted successfully!', 'ratingr'),
            'post_id' => $post_id,
            'average_rating' => $stats['average_rating'],
            'total_votes' => $stats['total_votes']
        ));
    }
}