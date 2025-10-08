<?php

if (!defined('WPINC')) {
    die;
}

if (!current_user_can('manage_options')) {
    return;
}

if (isset($_POST['ratingr_settings_nonce']) && wp_verify_nonce($_POST['ratingr_settings_nonce'], 'ratingr_settings')) {
    update_option('ratingr_disable_default_css', isset($_POST['ratingr_disable_default_css']) ? 1 : 0);
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'ratingr') . '</p></div>';
}

$disable_css = get_option('ratingr_disable_default_css', false);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('ratingr_settings', 'ratingr_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="ratingr_disable_default_css"><?php _e('Disable Default CSS', 'ratingr'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php _e('Disable Default CSS', 'ratingr'); ?></span>
                            </legend>
                            <label for="ratingr_disable_default_css">
                                <input name="ratingr_disable_default_css" type="checkbox" id="ratingr_disable_default_css" value="1" <?php checked($disable_css, 1); ?>>
                                <?php _e('Check this box to disable the default rating styles and use your own CSS.', 'ratingr'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Settings', 'ratingr')); ?>
    </form>
</div>