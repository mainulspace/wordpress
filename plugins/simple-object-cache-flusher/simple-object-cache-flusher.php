<?php
/**
 * Plugin Name: Simple Object Cache Flusher
 * Description: Adds an admin menu with backend info and a button to flush the object cache (Memcached, Redis, etc.).
 * Version: 1.1
 * Author: Mainul Hasan
 * Author URI: https://www.webdevstory.com/
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-object-cache-flusher
 * Domain Path: /languages
 */

add_action('admin_menu', function() {
    add_management_page(
        __('Flush Object Cache', 'simple-object-cache-flusher'),
        __('Flush Object Cache', 'simple-object-cache-flusher'),
        'manage_options',
        'flush-object-cache',
        'socf_admin_page'
    );
});

function socf_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Not allowed.', 'simple-object-cache-flusher'));
    }

    $cache_backend = __('Unknown', 'simple-object-cache-flusher');
    if (file_exists(WP_CONTENT_DIR . '/object-cache.php')) {
        $file = file_get_contents(WP_CONTENT_DIR . '/object-cache.php');
        if (stripos($file, 'memcached') !== false) {
            $cache_backend = 'Memcached';
        } elseif (stripos($file, 'redis') !== false) {
            $cache_backend = 'Redis';
        } elseif (stripos($file, 'APC') !== false) {
            $cache_backend = 'APC';
        } else {
            $cache_backend = __('Custom/Other', 'simple-object-cache-flusher');
        }
    } else {
        $cache_backend = __('Not detected / Disabled', 'simple-object-cache-flusher');
    }

    // Handle flush and verification
    if (isset($_POST['socf_flush']) && function_exists('wp_cache_flush')) {
            // Set test key
        wp_cache_set('socf_test_key', 'temp', 'default');

            // Flush cache
        wp_cache_flush();

            // Verify test key is gone
        $still_exists = wp_cache_get('socf_test_key', 'default');

            // Display appropriate message
        if ($still_exists === false) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Object cache flushed and verified successfully.</p></div>';
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ Cache flush attempted, but verification failed. Your cache backend may not support flush or is persisting values.</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Flush Object Cache', 'simple-object-cache-flusher'); ?></h1>
        <p><strong><?php esc_html_e('Backend detected:', 'simple-object-cache-flusher'); ?></strong> <?php echo esc_html($cache_backend); ?></p>
        <form method="post">
            <?php wp_nonce_field('socf_flush_cache', 'socf_nonce'); ?>
            <p>
                <input type="submit" name="socf_flush" class="button button-primary" value="<?php esc_attr_e('Flush Object Cache Now', 'simple-object-cache-flusher'); ?>" />
            </p>
        </form>
        <p><?php esc_html_e('This will clear Memcached/Redis/other object cache for this WordPress site.', 'simple-object-cache-flusher'); ?></p>
        <?php if ($cache_backend === __('Not detected / Disabled', 'simple-object-cache-flusher')) : ?>
            <p style="color: red;"><?php esc_html_e('No object cache backend detected. You may not be using object caching.', 'simple-object-cache-flusher'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

add_action('plugins_loaded', function() {
    load_plugin_textdomain('simple-object-cache-flusher', false, dirname(plugin_basename(__FILE__)) . '/languages');
});