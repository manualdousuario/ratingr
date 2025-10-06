<?php
/**
 * Database operations for ratingr plugin
 */
class RATINGR_DB {
    /**
     * Activate the plugin - create database tables
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create ratings table
        $table_name = $wpdb->prefix . 'ratingr_ratings';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            rating float NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            cookie_id varchar(36) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY ip_address (ip_address),
            KEY cookie_id (cookie_id)
        ) $charset_collate;";
        
        // Create stats table
        $stats_table = $wpdb->prefix . 'ratingr_stats';
        $sql .= "CREATE TABLE $stats_table (
            post_id bigint(20) NOT NULL,
            average_rating float NOT NULL DEFAULT 0,
            total_votes int(11) NOT NULL DEFAULT 0,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set version in options
        add_option('ratingr_db_version', RATINGR_VERSION);
    }
    
    /**
     * Network activate the plugin - create database tables for all sites
     */
    public static function network_activate() {
        global $wpdb;
        
        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
        
        // Activate for each blog
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::activate();
            restore_current_blog();
        }
        
        // Set network-wide version option
        add_site_option('ratingr_network_version', RATINGR_VERSION);
    }
    
    /**
     * Deactivate the plugin - cleanup if needed
     */
    public static function deactivate() {
        // Cleanup tasks if needed
    }
    
    /**
     * Save a rating to the database
     *
     * @param int $post_id Post ID
     * @param float $rating Rating value (0-5)
     * @param int $user_id User ID (optional)
     * @param string $ip_address IP address (optional)
     * @param string $cookie_id Cookie ID (optional)
     * @return int|WP_Error Rating ID on success, WP_Error on failure
     */
    public static function save_rating($post_id, $rating, $user_id = 0, $ip_address = '', $cookie_id = '') {
        global $wpdb;
        
        // Validate rating value
        if ($rating < 0 || $rating > 5 || ($rating * 2) % 1 !== 0) {
            return new WP_Error('invalid_rating', __('Invalid rating value', 'ratingr'));
        }
        
        // Check if post exists
        if (!get_post($post_id)) {
            return new WP_Error('invalid_post', __('Invalid post ID', 'ratingr'));
        }
        
        // Current time
        $now = current_time('mysql');
        
        // Insert rating
        $result = $wpdb->insert(
            $wpdb->prefix . 'ratingr_ratings',
            array(
                'post_id' => $post_id,
                'rating' => $rating,
                'created_at' => $now,
                'updated_at' => $now,
                'user_id' => $user_id ?: null,
                'ip_address' => $ip_address ?: null,
                'cookie_id' => $cookie_id ?: null
            ),
            array('%d', '%f', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to save rating', 'ratingr'));
        }
        
        // Get rating ID
        $rating_id = $wpdb->insert_id;
        
        // Update stats
        self::update_stats($post_id);
        
        return $rating_id;
    }
    
    /**
     * Update rating statistics for a post
     *
     * @param int $post_id Post ID
     * @return bool True on success, false on failure
     */
    public static function update_stats($post_id) {
        global $wpdb;
        
        // Get average rating and total votes
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as total FROM {$wpdb->prefix}ratingr_ratings WHERE post_id = %d",
            $post_id
        ));
        
        if (!$stats) {
            return false;
        }
        
        $average = round($stats->average * 2) / 2; // Round to nearest 0.5
        $total = (int) $stats->total;
        
        // Current time
        $now = current_time('mysql');
        
        // Check if stats record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}ratingr_stats WHERE post_id = %d",
            $post_id
        ));
        
        if ($exists) {
            // Update existing record
            $result = $wpdb->update(
                $wpdb->prefix . 'ratingr_stats',
                array(
                    'average_rating' => $average,
                    'total_votes' => $total,
                    'last_updated' => $now
                ),
                array('post_id' => $post_id),
                array('%f', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $wpdb->prefix . 'ratingr_stats',
                array(
                    'post_id' => $post_id,
                    'average_rating' => $average,
                    'total_votes' => $total,
                    'last_updated' => $now
                ),
                array('%d', '%f', '%d', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Check if a user has already rated a post
     *
     * @param int $post_id Post ID
     * @param int $user_id User ID (optional)
     * @param string $ip_address IP address (optional)
     * @param string $cookie_id Cookie ID (optional)
     * @return bool True if user has already rated, false otherwise
     */
    public static function user_already_rated($post_id, $user_id = 0, $ip_address = '', $cookie_id = '') {
        global $wpdb;
        
        $conditions = array();
        $values = array($post_id);
        
        // Add user ID condition if provided
        if ($user_id) {
            $conditions[] = 'user_id = %d';
            $values[] = $user_id;
        }
        
        // Add IP address condition if provided
        if ($ip_address) {
            $conditions[] = 'ip_address = %s';
            $values[] = $ip_address;
        }
        
        // Add cookie ID condition if provided
        if ($cookie_id) {
            $conditions[] = 'cookie_id = %s';
            $values[] = $cookie_id;
        }
        
        // If no conditions, return false
        if (empty($conditions)) {
            return false;
        }
        
        // Build query
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}ratingr_ratings WHERE post_id = %d AND (";
        $query .= implode(' OR ', $conditions);
        $query .= ')';
        
        // Execute query
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        return $count > 0;
    }
    
    /**
     * Get rating statistics for a post
     *
     * @param int $post_id Post ID
     * @return array|false Stats array on success, false on failure
     */
    public static function get_stats($post_id) {
        global $wpdb;
        
        // Get stats from database
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ratingr_stats WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$stats) {
            // Return default values if no stats found
            return array(
                'post_id' => $post_id,
                'average_rating' => 0,
                'total_votes' => 0,
                'last_updated' => current_time('mysql')
            );
        }
        
        return $stats;
    }
}