<?php
/**
 * Plugin Name: WP Admin Order Table Customization
 * Plugin URI:
 * Description: Add new columns in order table
 * Version: 1.0
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/mmainulhasan/wordpress
 */

add_filter('manage_edit-shop_order_columns', 'wp_order_table_columns');
function wp_order_table_columns($columns)
{
    $updated_columns = array();

    foreach ($columns as $column_name => $column_info) {
        $updated_columns[$column_name] = $column_info;
    }

    $updated_columns['delivery_date'] = 'Delivery Date';
    $updated_columns['vendor_order_status'] = 'Vendor Status';

    return $updated_columns;
}

add_action('manage_shop_order_posts_custom_column', 'wp_order_table_columns_content', 10, 2);
function wp_order_table_columns_content($column_name, $post_id)
{
    if ('delivery_date' == $column_name) {
        // Get delivery date from post meta
        $delivery_date = get_post_meta($post_id, 'vendor_delivery_date', true);
        if (!empty($delivery_date)) {
            echo date('M d, Y', strtotime($delivery_date));
        }
        return;
    }

    if ('vendor_order_status' == $column_name) {
        // Get Vendor Order Status from post meta
        $vendor_order_status = get_post_meta($post_id, '_vendor_order_status', true);
        if (!empty($vendor_order_status)) {
            echo $vendor_order_status;
        }
        return;
    }
}

add_filter('manage_edit-shop_order_sortable_columns', 'wp_order_table_sortable_columns');
function wp_order_table_sortable_columns($sortable_columns)
{
    $sortable_columns['delivery_date'] = 'delivery_date';
    $sortable_columns['vendor_order_status'] = 'vendor_order_status';

    return $sortable_columns;
}

add_action('pre_get_posts', 'wp_order_table_columns_query_mods');
function wp_order_table_columns_query_mods($query)
{
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('delivery_date' == $orderby) {
        $query->set('meta_key', 'vendor_delivery_date');
        $query->set('orderby', 'meta_value');
    }

    if ('vendor_order_status' == $orderby) {
        $query->set('meta_key', '_vendor_order_status');
        $query->set('orderby', 'meta_value');
    }
}