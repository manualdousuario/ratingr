<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    ratingr
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete plugin data for a single site.
 */
function ratingr_delete_single_site_data() {
    global $wpdb;
    
    // Delete plugin tables
    $tables = array(
        $wpdb->prefix . 'ratingr_ratings',
        $wpdb->prefix . 'ratingr_stats',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete plugin options
    delete_option('ratingr_db_version');
    delete_option('ratingr_disable_default_css');
    
    // Delete any transients we might have created
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ratingr_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ratingr_%'");
}

/**
 * Delete plugin data from all sites in a network.
 */
function ratingr_delete_network_data() {
    global $wpdb;
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    // Delete data for each blog
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        ratingr_delete_single_site_data();
        restore_current_blog();
    }
    
    // Delete network-wide options if any
    delete_site_option('ratingr_network_version');
}

// Check if this is a multisite uninstall
if (is_multisite()) {
    ratingr_delete_network_data();
} else {
    ratingr_delete_single_site_data();
}

// Clear any cached data
wp_cache_flush();