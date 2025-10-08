<?php

if (!defined('WPINC')) {
    die;
}

global $wpdb;

$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'total_votes';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

$per_page = 50;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$valid_orderby = array('total_votes', 'average_rating');
$valid_order = array('ASC', 'DESC');

if (!in_array($orderby, $valid_orderby)) {
    $orderby = 'total_votes';
}

if (!in_array($order, $valid_order)) {
    $order = 'DESC';
}

$stats_table = $wpdb->prefix . 'ratingr_stats';

$count_query = "SELECT COUNT(*)
                FROM {$stats_table} s
                JOIN {$wpdb->posts} p ON s.post_id = p.ID
                WHERE p.post_status = 'publish'";
$total_items = $wpdb->get_var($count_query);
$total_pages = ceil($total_items / $per_page);

$query = $wpdb->prepare(
    "SELECT s.post_id, s.average_rating, s.total_votes, p.post_title
    FROM {$stats_table} s
    JOIN {$wpdb->posts} p ON s.post_id = p.ID
    WHERE p.post_status = 'publish'
    ORDER BY %s %s
    LIMIT %d OFFSET %d",
    $orderby,
    $order,
    $per_page,
    $offset
);

$query = str_replace("'$orderby'", $orderby, $query);
$query = str_replace("'$order'", $order, $query);

$posts = $wpdb->get_results($query);

$opposite_order = ($order === 'ASC') ? 'DESC' : 'ASC';
?>

<div class="wrap">
    <h1><?php _e('Ratings', 'ratingr'); ?></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary">
                    <?php _e('Post', 'ratingr'); ?>
                </th>
                <th scope="col" class="manage-column sorted <?php echo $orderby === 'average_rating' ? strtolower($order) : 'desc'; ?>">
                    <a href="<?php echo add_query_arg(array('orderby' => 'average_rating', 'order' => $orderby === 'average_rating' ? $opposite_order : 'DESC')); ?>">
                        <span><?php _e('Average Rating', 'ratingr'); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-votes sorted <?php echo $orderby === 'total_votes' ? strtolower($order) : 'desc'; ?>">
                    <a href="<?php echo add_query_arg(array('orderby' => 'total_votes', 'order' => $orderby === 'total_votes' ? $opposite_order : 'DESC')); ?>">
                        <span><?php _e('Total Votes', 'ratingr'); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
            </tr>
        </thead>
        
        <tbody>
            <?php if (empty($posts)) : ?>
                <tr>
                    <td colspan="3"><?php _e('No posts found.', 'ratingr'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($posts as $post) : ?>
                    <tr>
                        <td class="title column-title column-primary">
                            <strong>
                                <a href="<?php echo get_permalink($post->post_id); ?>" target="_blank">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo get_edit_post_link($post->post_id); ?>">
                                        <?php _e('Edit', 'ratingr'); ?>
                                    </a>
                                </span>
                                |
                                <span class="view">
                                    <a href="<?php echo get_permalink($post->post_id); ?>" target="_blank">
                                        <?php _e('View', 'ratingr'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="rating column-rating">
                            <?php
                            $rating = floatval($post->average_rating);
                            $full_stars = floor($rating);
                            $half_star = ($rating - $full_stars) >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

                            echo '<span style="text-wrap: nowrap;">';
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<span class="dashicons dashicons-star-filled" style="color: #ffb900;"></span>';
                            }
                            
                            if ($half_star) {
                                echo '<span class="dashicons dashicons-star-half" style="color: #ffb900;"></span>';
                            }
                            
                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<span class="dashicons dashicons-star-empty" style="color: #ffb900;"></span>';
                            }

                            echo '</span>';
                            
                            echo ' <span class="rating-value" style="display: block;">(' . number_format($rating, 1) . '/5)</span>';
                            ?>
                        </td>
                        <td class="votes column-votes">
                            <?php echo intval($post->total_votes); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-title column-primary">
                    <?php _e('Post Title', 'ratingr'); ?>
                </th>
                <th scope="col" class="manage-column column-rating sortable <?php echo $orderby === 'average_rating' ? strtolower($order) : 'desc'; ?>">
                    <a href="<?php echo add_query_arg(array('orderby' => 'average_rating', 'order' => $orderby === 'average_rating' ? $opposite_order : 'DESC')); ?>">
                        <span><?php _e('Average Rating', 'ratingr'); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-votes sortable <?php echo $orderby === 'total_votes' ? strtolower($order) : 'desc'; ?>">
                    <a href="<?php echo add_query_arg(array('orderby' => 'total_votes', 'order' => $orderby === 'total_votes' ? $opposite_order : 'DESC')); ?>">
                        <span><?php _e('Total Votes', 'ratingr'); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
            </tr>
        </tfoot>
    </table>
    
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_items, 'ratingr'), number_format_i18n($total_items)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    if ($current_page > 1) {
                        echo '<a class="first-page button" href="' . add_query_arg('paged', 1) . '"><span class="screen-reader-text">' . __('First page', 'ratingr') . '</span><span aria-hidden="true">&laquo;</span></a>';
                    } else {
                        echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('First page', 'ratingr') . '</span><span aria-hidden="true">&laquo;</span></span>';
                    }
                    
                    if ($current_page > 1) {
                        echo '<a class="prev-page button" href="' . add_query_arg('paged', max(1, $current_page - 1)) . '"><span class="screen-reader-text">' . __('Previous page', 'ratingr') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                    } else {
                        echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Previous page', 'ratingr') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
                    }
                    ?>
                    
                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'ratingr'); ?></label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $current_page; ?>" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> <?php _e('of', 'ratingr'); ?> <span class="total-pages"><?php echo $total_pages; ?></span></span>
                    </span>
                    
                    <?php
                    if ($current_page < $total_pages) {
                        echo '<a class="next-page button" href="' . add_query_arg('paged', min($total_pages, $current_page + 1)) . '"><span class="screen-reader-text">' . __('Next page', 'ratingr') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                    } else {
                        echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Next page', 'ratingr') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
                    }
                    
                    if ($current_page < $total_pages) {
                        echo '<a class="last-page button" href="' . add_query_arg('paged', $total_pages) . '"><span class="screen-reader-text">' . __('Last page', 'ratingr') . '</span><span aria-hidden="true">&raquo;</span></a>';
                    } else {
                        echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Last page', 'ratingr') . '</span><span aria-hidden="true">&raquo;</span></span>';
                    }
                    ?>
                </span>
            </div>
            <br class="clear">
        </div>
    <?php endif; ?>
</div>