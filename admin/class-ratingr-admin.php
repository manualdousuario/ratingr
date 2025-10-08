<?php

class ratingr_Admin {
    private static $instance;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'ratingr') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ratingr-admin',
            plugin_dir_url(dirname(__FILE__)) . 'admin/dist/css/ratingr-admin.css',
            array(),
            RATINGR_VERSION,
            'all'
        );
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Ratingr', 'ratingr'),
            __('Ratingr', 'ratingr'),
            'manage_options',
            'ratingr-posts-rating',
            array($this, 'render_posts_rating_page'),
            'dashicons-star-filled',
            90
        );

        add_submenu_page(
            'ratingr-posts-rating',
            __('All Ratings', 'ratingr'),
            __('All Ratings', 'ratingr'),
            'manage_options',
            'ratingr-posts-rating',
            array($this, 'render_posts_rating_page')
        );
        
        add_submenu_page(
            'ratingr-posts-rating',
            __('Settings', 'ratingr'),
            __('Settings', 'ratingr'),
            'manage_options',
            'ratingr-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('ratingr_settings', 'ratingr_disable_default_css', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
    }
    
    public function sanitize_checkbox($value) {
        return $value ? 1 : 0;
    }
    
    public function render_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-header.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-footer.php';
    }
    
    public function render_posts_rating_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-header.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-ratings-list.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-footer.php';
    }
}