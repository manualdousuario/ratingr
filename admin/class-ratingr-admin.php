<?php
/**
 * Admin functionality for ratingr plugin
 */
class ratingr_Admin {
    /**
     * Instance of this class
     *
     * @var ratingr_Admin
     */
    private static $instance;
    
    /**
     * Get instance of this class
     *
     * @return ratingr_Admin
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on ratingr admin pages
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
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Ratingr', 'ratingr'),         // Page title
            __('Ratingr', 'ratingr'),         // Menu title
            'manage_options',                 // Capability
            'ratingr-posts-rating',              // Menu slug
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
            'ratingr-posts-rating',                      // Parent slug
            __('Settings', 'ratingr'), // Page title
            __('Settings', 'ratingr'), // Menu title
            'manage_options',                 // Capability
            'ratingr-settings',                  // Menu slug
            array($this, 'render_settings_page') // Callback function
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('ratingr_settings', 'ratingr_disable_default_css', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
    }
    
    /**
     * Sanitize checkbox value
     */
    public function sanitize_checkbox($value) {
        return $value ? 1 : 0;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-header.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-footer.php';
    }
    
    /**
     * Render posts rating page
     */
    public function render_posts_rating_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-header.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-ratings-list.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ratingr-admin-footer.php';
    }
}