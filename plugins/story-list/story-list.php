<?php
/**
 * Plugin Name: Story List
 * Plugin URI: https://github.com/mmainulhasan/wordpress
 * Version: 1.0
 * Author: Mohammad Mainul Hasan
 * Description: Custom post order management plugin.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/mmainulhasan
 */

define('PLUGIN_PATH', plugin_dir_path(__FILE__));

// Hook for adding admin menus
add_action('admin_menu', 'story_sort');
function story_sort() {
    add_submenu_page('edit.php', 'Manage Contents', 'Manage Contents', 'moderate_comments', 'manage-story-list', 'manage_story_list');
}

function manage_story_list() {
    include(PLUGIN_PATH . 'templates/story-list-view.php');
}

// CSS and JavaScript include
add_action('admin_enqueue_scripts', 'add_stylesheets_and_scripts_to_story');

/**
 * Add stylesheets and scripts to the page
 */
function add_stylesheets_and_scripts_to_story($page) {
    if ('edit.php' === $page && isset($_GET['page']) && 'manage-story-list' === $_GET['page']) {
        wp_enqueue_style('story-style', plugins_url('assets/style_admin.css', __FILE__));
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('list-min', plugins_url('assets/list-min.js', __FILE__), array(), '', true);
        wp_enqueue_script('story-js', plugins_url('assets/story-js.js', __FILE__), array(), '', true);
    }
}

// Sort stories AJAX request
add_action('wp_ajax_sort-stories', 'sort_stories');

function sort_stories() {
    global $wpdb;

    if (!empty($_POST['action'])) {
        $data = array_map('sanitize_text_field', $_POST['sort']);
        $story_type = $_POST['story_type'];

        // Remove old values
        remove_all_sort_values($story_type, $wpdb);

        foreach ($data as $k => $v) {
            $id = ltrim($v, 'post-'); // Trim the "post-" prefix from the ID
            $index = ($k + 1); // Make sure our sorting index starts at #1

            // Update
            update_post_meta($id, $story_type, 1);
            update_post_meta($id, "{$story_type}_sort_order", $index);
        }
    }

    exit();
}

// Remove old values
function remove_all_sort_values($story_type, $wpdb) {
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = %s", $story_type));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = %s", "{$story_type}_sort_order"));
}