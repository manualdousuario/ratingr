<?php
/**
 * Rating functionality for ratingr plugin
 */
class ratingr_Rating {
    /**
     * Instance of this class
     *
     * @var ratingr_Rating
     */
    private static $instance;
    
    /**
     * Get instance of this class
     *
     * @return ratingr_Rating
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
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        
        // Schema markup is now added directly in the display_rating method
    }
    
    /**
     * Register scripts and styles
     */
    public function register_assets() {
        add_filter('ratingr_params', function($params) {
            return array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ratingr_rating_nonce'),
                'can_rate' => true, // Will be filtered later
                'already_rated' => array(), // Will be filled with post IDs user has rated
                'rating_texts' => array(
                    'error' => __('Error submitting rating', 'ratingr'),
                    'success' => __('Rating submitted successfully', 'ratingr'),
                    'already_rated' => __('You have already rated this post', 'ratingr')
                )
            );
        });
    }
    
    /**
     * Display rating component
     *
     * @param int $post_id Post ID
     * @return string HTML for rating component
     */
    public function display_rating($post_id) {
        // Enqueue styles only if not disabled
        $disable_css = get_option('ratingr_disable_default_css', false);
        if (!$disable_css) {
            wp_enqueue_style('ratingr-style');
        }
        
        // Get rating stats
        $stats = RATINGR_DB::get_stats($post_id);
        
        // Default values if no ratings yet
        $average_rating = isset($stats['average_rating']) ? $stats['average_rating'] : 0;
        $total_votes = isset($stats['total_votes']) ? $stats['total_votes'] : 0;
        
        // Ensure rating value is not less than 1 (only if there are votes)
        if ($total_votes > 0 && $average_rating < 1) {
            $average_rating = 1;
        }
        
        // Calculate percentage for CSS width
        $percentage = ($average_rating / 5) * 100;
        
        // Determine if user can rate
        $user_can_rate = $this->user_can_rate($post_id);
        $user_already_rated = $this->user_already_rated($post_id);
        
        // Start HTML output
        if ($user_already_rated) {
            $html = '<div class="ratingr-rating ratingr-already-rated" data-post-id="' . esc_attr($post_id) . '"';
        } else {
            $html = '<div class="ratingr-rating" data-post-id="' . esc_attr($post_id) . '"';           
        }
        
        // Add title attribute if already rated
        if ($user_already_rated) {
            $html .= ' title="' . esc_attr__('You have already rated this post', 'ratingr') . '"';
        }
        
        $html .= '>';
        
        // Stars container
        $html .= '<div class="ratingr-stars-container">';
        
        // Background stars (empty)
        $html .= '<div class="ratingr-stars-background">';
        for ($i = 0; $i < 5; $i++) {
            $html .= '<i class="icon icon--star"></i>';
        }
        $html .= '</div>';
        
        // Foreground stars (filled based on rating)
        $html .= '<div class="ratingr-stars-foreground" style="width: ' . esc_attr($percentage) . '%">';
        for ($i = 0; $i < 5; $i++) {
            $html .= '<i class="icon icon--star-fill"></i>';
        }
        $html .= '</div>';
        
        // Interactive hover stars (only if user can rate)
        if ($user_can_rate && !$user_already_rated) {
            $html .= '<div class="ratingr-stars-hover">';
            for ($i = 1; $i <= 5; $i++) {
                $html .= '<i class="icon icon--star" data-value="' . esc_attr($i) . '"></i>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>'; // End stars container
        
        // Rating info
        $html .= '<div class="ratingr-rating-info">';
        $html .= '<span class="ratingr-rating-value">(' . esc_html(number_format($average_rating, 1)) . '/5)</span> ';
        
        // Vote text (singular/plural)
        $vote_text = (int)$total_votes === 1 ? __('vote', 'ratingr') : __('votes', 'ratingr');
        $html .= '<span class="ratingr-rating-count">' . esc_html($total_votes) . ' ' . esc_html($vote_text) . '</span>';
        $html .= '</div>';
        
        // Message container for AJAX responses
        $html .= '<div class="ratingr-rating-message" aria-live="polite"></div>';
        
        $html .= '</div>'; // End rating container
        
        // Schema markup is now handled by yoast.php integration

        // Get ratingr_params data
        $ratingr_params = apply_filters('ratingr_params', array());
        
        // Output ratingr_params as a global JS variable
        $html .= '<script>var ratingr_params = ' . json_encode($ratingr_params) . ';</script>';
        
        // Load the JS file directly
        $ratingr_js = RATINGR_PLUGIN_DIR . '/dist/js/ratingr.min.js';
        if (file_exists($ratingr_js)) {
            $ratingr_js = file_get_contents($ratingr_js);
            $html .= '<script>' . $ratingr_js . '</script>';
        }
        
        // Apply filters and return
        return apply_filters('ratingr_rating_html', $html, $post_id, $stats);
    }
    
    /**
     * Check if user can rate a post
     *
     * @param int $post_id Post ID
     * @return bool True if user can rate, false otherwise
     */
    public function user_can_rate($post_id) {
        // Check if post exists and is published
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        // Check if user already rated
        if ($this->user_already_rated($post_id)) {
            return false;
        }
        
        // Allow filtering
        return apply_filters('ratingr_user_can_rate', true, $post_id);
    }
    
    /**
     * Check if user already rated a post
     *
     * @param int $post_id Post ID
     * @return bool True if user already rated, false otherwise
     */
    public function user_already_rated($post_id) {
        // Get user ID if logged in
        $user_id = get_current_user_id();
        
        // Get IP address
        $ip_address = $this->get_user_ip();
        
        // Get cookie ID if set
        $cookie_id = isset($_COOKIE['ratingr_user_id']) ? sanitize_text_field($_COOKIE['ratingr_user_id']) : '';
        
        // Check if user already rated
        return RATINGR_DB::user_already_rated($post_id, $user_id, $ip_address, $cookie_id);
    }
    
    /**
     * Get user IP address
     *
     * @return string IP address
     */
    public function get_user_ip() {
        $ip = '';
        
        // Check for proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Get rating data for a post
     *
     * @param int $post_id Post ID
     * @return array Array with average_rating and total_votes
     */
    public function get_rating_data($post_id) {
        $stats = RATINGR_DB::get_stats($post_id);
        
        $average_rating = isset($stats['average_rating']) ? $stats['average_rating'] : 0;
        $total_votes = isset($stats['total_votes']) ? $stats['total_votes'] : 0;
        
        // Ensure rating value is not less than 1 (only if there are votes)
        if ($total_votes > 0 && $average_rating < 1) {
            $average_rating = 1;
        }
        
        return array(
            'average_rating' => $average_rating,
            'total_votes' => $total_votes
        );
    }
    
    /**
     * Get average rating for a post
     *
     * @param int $post_id Post ID
     * @return float Average rating
     */
    public function get_average_rating($post_id) {
        $data = $this->get_rating_data($post_id);
        $average_rating = $data['average_rating'];
        
        // Ensure rating value is not less than 1 (only if there are votes)
        if ($data['total_votes'] > 0 && $average_rating < 1) {
            $average_rating = 1;
        }
        
        return $average_rating;
    }
    
    /**
     * Get total votes for a post
     *
     * @param int $post_id Post ID
     * @return int Total votes
     */
    public function get_total_votes($post_id) {
        $data = $this->get_rating_data($post_id);
        return $data['total_votes'];
    }
}