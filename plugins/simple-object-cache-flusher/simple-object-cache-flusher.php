<?php
/**
 * Plugin Name: Simple Object Cache Flusher
 * Description: Adds an admin menu with backend info and a button to flush only this site's object cache (Memcached) using WP_CACHE_KEY_SALT.
 * Version: 1.3
 * Author: Mainul Hasan
 * Author URI: https://www.webdevstory.com/
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-object-cache-flusher
 * Domain Path: /languages
 */

add_action('admin_menu', function () {
    add_management_page(
        __('Flush Object Cache', 'simple-object-cache-flusher'),
        __('Flush Object Cache', 'simple-object-cache-flusher'),
        'manage_options',
        'flush-object-cache',
        'socf_admin_page'
    );
});

function socf_admin_page()
{
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

    if (isset($_POST['socf_flush'])) {
    check_admin_referer('socf_flush_cache', 'socf_nonce');

    $prefix = defined('WP_CACHE_KEY_SALT') ? WP_CACHE_KEY_SALT : '';
    $deleted = 0;
    $error_msg = '';

    if ($prefix && class_exists('Memcached')) {
        $host = apply_filters('socf_memcached_host', '127.0.0.1');
        $port = apply_filters('socf_memcached_port', 11211);
        $mem = new Memcached();
        $mem->addServer($host, $port);

        if (method_exists($mem, 'getAllKeys')) {
            $all_keys = $mem->getAllKeys();
            if (is_array($all_keys)) {
                foreach ($all_keys as $key) {
                    if (strpos($key, $prefix) === 0) {
                        if ($mem->delete($key)) {
                            $deleted++;
                        }
                    }
                }
            }
        } else {
            $error_msg = 'Your Memcached extension does not support key enumeration (getAllKeys). Partial flush not possible.';
        }
    }

    if ($deleted > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
            esc_html__('✅ Flushed ' . $deleted . ' object cache keys using WP_CACHE_KEY_SALT.', 'simple-object-cache-flusher') .
            '</p></div>';
    } else {
        echo '<div class="notice notice-warning is-dismissible"><p>' .
            esc_html__('⚠️ No matching keys deleted. Either WP_CACHE_KEY_SALT is not set, or key listing is unsupported. ', 'simple-object-cache-flusher') .
            esc_html($error_msg) .
            '</p></div>';
    }
}

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Flush Object Cache', 'simple-object-cache-flusher'); ?></h1>
        <p><strong><?php esc_html_e('Backend detected:', 'simple-object-cache-flusher'); ?></strong> <?php echo esc_html($cache_backend); ?></p>
        <form method="post">
            <?php wp_nonce_field('socf_flush_cache', 'socf_nonce'); ?>
            <p>
                <input type="submit" name="socf_flush" class="button button-primary"
                       value="<?php esc_attr_e('Flush Object Cache Now', 'simple-object-cache-flusher'); ?>"/>
            </p>
        </form>
        <p><?php esc_html_e("This will flush the Memcached object cache if available on your server. Tries to delete only this site's keys if salt is defined.", 'simple-object-cache-flusher'); ?></p>
        <?php if ($cache_backend === __('Not detected / Disabled', 'simple-object-cache-flusher')) : ?>
            <p style="color: red;"><?php esc_html_e('No object cache backend detected. You may not be using object caching.', 'simple-object-cache-flusher'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

add_action('plugins_loaded', function () {
    load_plugin_textdomain('simple-object-cache-flusher', false, dirname(plugin_basename(__FILE__)) . '/languages');
});