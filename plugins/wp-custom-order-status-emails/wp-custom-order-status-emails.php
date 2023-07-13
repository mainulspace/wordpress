<?php
/**
 * Plugin Name: WP Site Custom Order Status Emails
 * Plugin URI: https://github.com/mmainulhasan/wordpress
 * Description: Sends email for custom order statuses
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/mmainulhasan
 */

use \WC_Email_Customer_Completed_Order;

add_action('woocommerce_order_status_changed', 'wp_order_status_changed');

function wp_order_status_changed(int $order_id): void
{
    $order = wc_get_order($order_id);
    $order_status = $order->get_status();

    if ($order_status !== 'shipped') {
        return; // No need to proceed further
    }

    $wc_emails = wc()->mailer()->get_emails();
    /** @var WC_Email_Customer_Completed_Order $customer_completed_order_email */
    $customer_completed_order_email = $wc_emails['WC_Email_Customer_Completed_Order'];
    $customer_completed_order_email->trigger($order_id);
}