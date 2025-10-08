<?php

class ratingr_Rating {
    private static $instance;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
    }
    
    public function register_assets() {
        add_filter('ratingr_params', function($params) {
            return array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ratingr_rating_nonce'),
                'can_rate' => true,
                'already_rated' => array(),
                'rating_texts' => array(
                    'error' => __('Error submitting rating', 'ratingr'),
                    'success' => __('Rating submitted successfully', 'ratingr'),
                    'already_rated' => __('You have already rated this post', 'ratingr')
                )
            );
        });
    }
    
    public function display_rating($post_id) {
        $disable_css = get_option('ratingr_disable_default_css', false);
        if (!$disable_css) {
            wp_enqueue_style('ratingr-style');
        }
        
        $stats = RATINGR_DB::get_stats($post_id);
        
        $average_rating = isset($stats['average_rating']) ? $stats['average_rating'] : 0;
        $total_votes = isset($stats['total_votes']) ? $stats['total_votes'] : 0;
        
        if ($total_votes > 0 && $average_rating < 1) {
            $average_rating = 1;
        }
        
        $percentage = ($average_rating / 5) * 100;
        
        $user_can_rate = $this->user_can_rate($post_id);
        $user_already_rated = $this->user_already_rated($post_id);
        
        if ($user_already_rated) {
            $html = '<div class="ratingr-rating ratingr-already-rated" data-post-id="' . esc_attr($post_id) . '"';
        } else {
            $html = '<div class="ratingr-rating" data-post-id="' . esc_attr($post_id) . '"';           
        }
        
        if ($user_already_rated) {
            $html .= ' title="' . esc_attr__('You have already rated this post', 'ratingr') . '"';
        }
        
        $html .= '>';
        
        $html .= '<div class="ratingr-stars-container">';
        
        $html .= '<div class="ratingr-stars-background">';
        for ($i = 0; $i < 5; $i++) {
            $html .= '<i class="icon icon--star"></i>';
        }
        $html .= '</div>';
        
        $html .= '<div class="ratingr-stars-foreground" style="width: ' . esc_attr($percentage) . '%">';
        for ($i = 0; $i < 5; $i++) {
            $html .= '<i class="icon icon--star-fill"></i>';
        }
        $html .= '</div>';
        
        if ($user_can_rate && !$user_already_rated) {
            $html .= '<div class="ratingr-stars-hover">';
            for ($i = 1; $i <= 5; $i++) {
                $html .= '<i class="icon icon--star" data-value="' . esc_attr($i) . '"></i>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '<div class="ratingr-rating-info">';
        $html .= '<span class="ratingr-rating-value">(' . esc_html(number_format($average_rating, 1)) . '/5)</span> ';
        
        $vote_text = (int)$total_votes === 1 ? __('vote', 'ratingr') : __('votes', 'ratingr');
        $html .= '<span class="ratingr-rating-count">' . esc_html($total_votes) . ' ' . esc_html($vote_text) . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="ratingr-rating-message" aria-live="polite"></div>';
        
        $html .= '</div>';
        
        $ratingr_params = apply_filters('ratingr_params', array());
        
        $html .= '<script>var ratingr_params = ' . json_encode($ratingr_params) . ';</script>';
        
        $ratingr_js = RATINGR_PLUGIN_DIR . '/dist/js/ratingr.min.js';
        if (file_exists($ratingr_js)) {
            $ratingr_js = file_get_contents($ratingr_js);
            $html .= '<script>' . $ratingr_js . '</script>';
        }
        
        return apply_filters('ratingr_rating_html', $html, $post_id, $stats);
    }
    
    public function user_can_rate($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        if ($this->user_already_rated($post_id)) {
            return false;
        }
        
        return apply_filters('ratingr_user_can_rate', true, $post_id);
    }
    
    public function user_already_rated($post_id) {
        $user_id = get_current_user_id();
        
        $ip_address = $this->get_user_ip();
        
        $cookie_id = isset($_COOKIE['ratingr_user_id']) ? sanitize_text_field($_COOKIE['ratingr_user_id']) : '';
        
        return RATINGR_DB::user_already_rated($post_id, $user_id, $ip_address, $cookie_id);
    }
    
    public function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    public function get_rating_data($post_id) {
        $stats = RATINGR_DB::get_stats($post_id);
        
        $average_rating = isset($stats['average_rating']) ? $stats['average_rating'] : 0;
        $total_votes = isset($stats['total_votes']) ? $stats['total_votes'] : 0;
        
        if ($total_votes > 0 && $average_rating < 1) {
            $average_rating = 1;
        }
        
        return array(
            'average_rating' => $average_rating,
            'total_votes' => $total_votes
        );
    }
    
    public function get_average_rating($post_id) {
        $data = $this->get_rating_data($post_id);
        $average_rating = $data['average_rating'];
        
        if ($data['total_votes'] > 0 && $average_rating < 1) {
            $average_rating = 1;
        }
        
        return $average_rating;
    }
    
    public function get_total_votes($post_id) {
        $data = $this->get_rating_data($post_id);
        return $data['total_votes'];
    }
}