<?php
/**
 * Plugin Name: Ratingr
 * Plugin URI: https://butialabs.com
 * Description: Rate posts with a 5-star system
 * Version: 1.0.0
 * Author: Butiá Labs
 * Author URI: https://butialabs.com
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Tested up to: 6.7
 * License: GPL v2 or later
 * Network: true
 * Text Domain: shortcodr
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('RATINGR_VERSION', '1.0.0');
define('RATINGR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RATINGR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once RATINGR_PLUGIN_DIR . 'includes/class-ratingr-db.php';
require_once RATINGR_PLUGIN_DIR . 'includes/class-ratingr-rating.php';
require_once RATINGR_PLUGIN_DIR . 'includes/class-ratingr-api.php';
require_once RATINGR_PLUGIN_DIR . 'admin/class-ratingr-admin.php';

// Activation hook
register_activation_hook(__FILE__, 'ratingr_activate_plugin');

/**
 * Plugin activation handler
 * Handles both single site and multisite network activation
 */
function ratingr_activate_plugin($network_wide) {
    if (is_multisite() && $network_wide) {
        // Network activation - activate for all sites
        RATINGR_DB::network_activate();
    } else {
        // Single site activation
        RATINGR_DB::activate();
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, array('RATINGR_DB', 'deactivate'));

// Handle new sites added to multisite network
add_action('wpmu_new_blog', 'ratingr_activate_new_site', 10, 6);

/**
 * Activate plugin for a new site in multisite network
 */
function ratingr_activate_new_site($blog_id, $user_id, $domain, $path, $site_id, $meta) {
    if (is_plugin_active_for_network('ratingr/ratingr.php')) {
        switch_to_blog($blog_id);
        RATINGR_DB::activate();
        restore_current_blog();
    }
}

// Initialize the plugin
function ratingr_init() {
    // Initialize classes
    ratingr_Rating::get_instance();
    ratingr_API::get_instance();
    
    // Initialize admin class if in admin area
    if (is_admin()) {
        ratingr_Admin::get_instance();
    }
}
add_action('plugins_loaded', 'ratingr_init');

/**
 * Template function to display rating component
 *
 * @param int $post_id Optional. Post ID to display rating for. Default is current post.
 * @return void
 */
function ratingr() {
    $post_id = get_the_ID();
    echo ratingr_Rating::get_instance()->display_rating($post_id);
}