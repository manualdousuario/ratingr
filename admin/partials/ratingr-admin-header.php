<?php

if (!defined('WPINC')) {
    die;
}

$current_page = isset($_GET['page']) ? $_GET['page'] : 'ratingr-posts-rating';
?>

<div class="bl-admin-header">
    <div class="bl-admin-header-content">
        <div class="bl-branding">
            <span class="dashicons dashicons-star-filled"></span>
            <h1>Ratingr</h1>
        </div>
        
        <nav class="bl-admin-nav">
            <a href="<?php echo admin_url('admin.php?page=ratingr-posts-rating'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'ratingr-posts-rating') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span>
                <?php echo esc_html__('All Ratings', 'ratingr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ratingr-settings'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'ratingr-settings') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Settings', 'ratingr'); ?>
            </a>
        </nav>
    </div>
</div>