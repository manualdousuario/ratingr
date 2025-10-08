<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function ratingr_delete_single_site_data() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'ratingr_ratings',
        $wpdb->prefix . 'ratingr_stats',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    delete_option('ratingr_db_version');
    delete_option('ratingr_disable_default_css');
    
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ratingr_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ratingr_%'");
}

function ratingr_delete_network_data() {
    global $wpdb;
    
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        ratingr_delete_single_site_data();
        restore_current_blog();
    }
    
    delete_site_option('ratingr_network_version');
}

if (is_multisite()) {
    ratingr_delete_network_data();
} else {
    ratingr_delete_single_site_data();
}

wp_cache_flush();