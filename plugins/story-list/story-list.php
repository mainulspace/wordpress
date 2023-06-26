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

// Save top stories AJAX request
add_action('wp_ajax_top-stories-sort', 'sort_top_stories');

function sort_top_stories() {
    global $wpdb;

    if (!empty($_POST['action'])) {
        $data = array_map('sanitize_text_field', $_POST['sort']);

        // Remove old values
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = %s", 'top_stories'));
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = %s", 'top_stories_sort_order'));

        foreach ($data as $k => $v) {
            $id = ltrim($v, 'post-'); // Trim the "post-" prefix from the ID
            $index = ($k + 1); // Make sure our sorting index starts at #1

            // Update
            update_post_meta($id, 'top_stories', 1);
            update_post_meta($id, 'top_stories_sort_order', $index);
        }
    }

    exit();
}

// Featured stories sort
add_action('wp_ajax_featured-stories-sort', 'sort_featured_stories');

function sort_featured_stories() {
    global $wpdb;

    if (!empty($_POST['action'])) {
        $data = array_map('sanitize_text_field', $_POST['sort']);

        // Remove old values
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = %s", 'featured_stories'));
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = %s", 'featured_stories_sort_order'));

        foreach ($data as $k => $v) {
            $id = ltrim($v, 'post-'); // Trim the "post-" prefix from the ID
            $index = ($k + 1); // Make sure our sorting index starts at #1

            // Update
            update_post_meta($id, 'featured_stories', 1);
            update_post_meta($id, 'featured_stories_sort_order', $index);
        }
    }

    exit();
}

// Worth reading sort
add_action('wp_ajax_worth-reading-sort', 'sort_worth_reading');

function sort_worth_reading() {
    global $wpdb;

    if (!empty($_POST['action'])) {
        $data = array_map('sanitize_text_field', $_POST['sort']);

        // Remove old values
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = %s", 'worth_reading'));
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = %s", 'worth_reading_stories_sort_order'));

        foreach ($data as $k => $v) {
            $id = ltrim($v, 'post-'); // Trim the "post-" prefix from the ID
            $index = ($k + 1); // Make sure our sorting index starts at #1

            // Update
            update_post_meta($id, 'worth_reading', 1);
            update_post_meta($id, 'worth_reading_stories_sort_order', $index);
        }
    }

    exit();
}

// Latest story sort
add_action('wp_ajax_latest-story-sort', 'sort_latest_story');

function sort_latest_story() {
    global $wpdb;

    if (!empty($_POST['action'])) {
        $data = array_map('sanitize_text_field', $_POST['sort']);

        // Remove old values
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = %s", 'latest_story'));
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = %s", 'latest_stories_sort_order'));

        foreach ($data as $k => $v) {
            $id = ltrim($v, 'post-'); // Trim the "post-" prefix from the ID
            $index = ($k + 1); // Make sure our sorting index starts at #1

            // Update
            update_post_meta($id, 'latest_story', 1);
            update_post_meta($id, 'latest_stories_sort_order', $index);
        }
    }

    exit();
}

// Remove old values
function remove_all_sort_values($stories, $sort_order) {
    global $wpdb;

    $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 15 WHERE meta_key = %s", $stories));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = %s", $sort_order));
}