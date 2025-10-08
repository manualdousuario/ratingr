<?php

class RATINGR_DB {
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
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
        
        add_option('ratingr_db_version', RATINGR_VERSION);
    }
    
    public static function network_activate() {
        global $wpdb;
        
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::activate();
            restore_current_blog();
        }
        
        add_site_option('ratingr_network_version', RATINGR_VERSION);
    }
    
    public static function deactivate() {
    }
    
    public static function save_rating($post_id, $rating, $user_id = 0, $ip_address = '', $cookie_id = '') {
        global $wpdb;
        
        if ($rating < 0 || $rating > 5 || ($rating * 2) % 1 !== 0) {
            return new WP_Error('invalid_rating', __('Invalid rating value', 'ratingr'));
        }
        
        if (!get_post($post_id)) {
            return new WP_Error('invalid_post', __('Invalid post ID', 'ratingr'));
        }
        
        $now = current_time('mysql');
        
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
        
        $rating_id = $wpdb->insert_id;
        
        self::update_stats($post_id);
        
        return $rating_id;
    }
    
    public static function update_stats($post_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as total FROM {$wpdb->prefix}ratingr_ratings WHERE post_id = %d",
            $post_id
        ));
        
        if (!$stats) {
            return false;
        }
        
        $average = round($stats->average * 2) / 2;
        $total = (int) $stats->total;
        
        $now = current_time('mysql');
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}ratingr_stats WHERE post_id = %d",
            $post_id
        ));
        
        if ($exists) {
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
    
    public static function user_already_rated($post_id, $user_id = 0, $ip_address = '', $cookie_id = '') {
        global $wpdb;
        
        $conditions = array();
        $values = array($post_id);
        
        if ($user_id) {
            $conditions[] = 'user_id = %d';
            $values[] = $user_id;
        }
        
        if ($ip_address) {
            $conditions[] = 'ip_address = %s';
            $values[] = $ip_address;
        }
        
        if ($cookie_id) {
            $conditions[] = 'cookie_id = %s';
            $values[] = $cookie_id;
        }
        
        if (empty($conditions)) {
            return false;
        }
        
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}ratingr_ratings WHERE post_id = %d AND (";
        $query .= implode(' OR ', $conditions);
        $query .= ')';
        
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        return $count > 0;
    }
    
    public static function get_stats($post_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ratingr_stats WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$stats) {
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