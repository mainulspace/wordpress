<?php

/**
 * Plugin Name: WP_Site Vendor Tasks
 * Plugin URI: https://github.com/mmainulhasan/wordpress
 * Description: Perform all a set of vendor tasks
 * Version: 0.1
 * Author:  Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/mmainulhasan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
require_once ABSPATH . 'vendor/constants.php';

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    // Update vendor order
    add_action( 'woocommerce_process_shop_order_meta', 'update_vendor_order' );

    function update_vendor_order( $order_id ){
        global $vendor_db_link;
        // First check this is a vendor order
        $vendor_order_id = get_post_meta($order_id, '_vendor_order_id', true);
        if(!empty($vendor_order_id)) {
            $order = wc_get_order($order_id);
            $fedex_hold_loc = 'fedex hold location';
            $fedex_hold_station = '0';

            if ($vendor_order_id) {
                $ship_via_type = $order->get_shipping_method();
                // vendor Order Update Query
                $order_sql = "UPDATE `orders` SET ";
                $order_sql .= "LastOrderUpdate = NOW(), OrderStatus = '{$vendor_db_link->real_escape_string($_POST['_vendor_order_status'])}', ShiptoCompany = '{$vendor_db_link->real_escape_string($_POST['_shipping_company'])}', ShipToFirstName = '{$vendor_db_link->real_escape_string($_POST['_shipping_first_name'])}', ShipToLastName = '{$vendor_db_link->real_escape_string($_POST['_shipping_last_name'])}', ShipToAddress1 = '{$vendor_db_link->real_escape_string($_POST['_shipping_address_1'])}', ShipToAddress2 = '{$vendor_db_link->real_escape_string($_POST['_shipping_address_2'])}', ShipToCity = '{$vendor_db_link->real_escape_string($_POST['_shipping_city'])}', ";
                $order_sql .= "ShipToState = '{$vendor_db_link->real_escape_string($_POST['_shipping_state'])}', ShipToZipCode = '{$vendor_db_link->real_escape_string($_POST['_shipping_postcode'])}', ShipToCountry = '{$vendor_db_link->real_escape_string($_POST['_shipping_country'])}', CustomerPhone = '{$vendor_db_link->real_escape_string($_POST['_billing_phone'])}', CustomeEmail = '{$vendor_db_link->real_escape_string($_POST['_billing_email'])}', ";
                if(strpos(strtolower($ship_via_type), strtolower($fedex_hold_loc)) !== false){
                    $fedex_hold_station = '1';
                    $ship_via_type = 'FEDEX STANDARD OVERNIGHT';
                }
                if(strpos(strtolower($ship_via_type), 'personal location') !== false){
                    $ship_via_type = 'FEDEX PRIORITY OVERNIGHT';
                }
                if(!empty($_POST['acf'][IS_FEDEX_HOLD_KEY]) && $_POST['acf'][IS_FEDEX_HOLD_KEY] == 'yes'){
                    $fedex_hold_station = '1';
                }elseif (!empty($_POST['acf'][IS_FEDEX_HOLD_KEY]) && $_POST['acf'][IS_FEDEX_HOLD_KEY] == 'no'){
                    $fedex_hold_station = '0';
                }

                if(!empty($_POST['acf'][IS_SATURDAY_PRIORITY_KEY]) && $_POST['acf'][IS_SATURDAY_PRIORITY_KEY] == 'yes'){
                    $ship_via_type = 'Saturday Priority Overnight Service';
                }

                $order_sql .= "ShipViaType = '{$ship_via_type}', OrderTotal = '{$order->get_subtotal()}', ";
                $order_sql .= "FedExHoldStation = $fedex_hold_station, FedExLocaionName = 'FedEx Ship Center', FedExLocaionType = 'Stn/WSC', FedExLocationAddress1 = '{$vendor_db_link->real_escape_string($_POST['_shipping_address_1'])}', FedExLocaionCity = '{$vendor_db_link->real_escape_string($_POST['_shipping_city'])}', FedExLocaionState = '{$vendor_db_link->real_escape_string($_POST['_shipping_state'])}', FedExLocaionZipCode = '{$vendor_db_link->real_escape_string($_POST['_shipping_postcode'])}' ";
                $order_sql .= "WHERE OrderId = '{$vendor_order_id}' AND swfOrderId = '{$order_id}' AND StoreId = 2";

                $vendor_db_link->query($order_sql);
                $order->update_meta_data('_vendor_order_update', $vendor_order_id.'-Successfully order updated');
                $order->save_meta_data();
                $order_products_sql = '';
                $order_total = 0;
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product = $item->get_product();
                    $sku = $product->get_sku();
                    $sku_size_arr = explode("-", $sku);

                    $check_vendor_product = trim($product->get_meta('vendor_inventory_type'));
                    if(!empty($check_vendor_product)) {
                        $pack_sku = '';
                        $bundle_product = $product->get_meta('woosb_ids');
                        if(!empty($bundle_product)){
                            continue;
                        }
                        $total_price = $item->get_total();
                        $order_total += $total_price;
                        $bundle_pack_sku = !empty($item->get_meta('_woosb_parent_id')) ? $item->get_meta('_woosb_parent_id') : '';
                        if(!empty($bundle_pack_sku)){
                            $pack_sku = 'BUNDLE-'.$bundle_pack_sku;
                        }
                        $product_size =  empty($sku_size_arr[1]) ? '' : $sku_size_arr[1];
                        // Check this product exists in the shared db
                        $check_product_exists_sql = "SELECT * FROM orderproducts WHERE OrderId = {$vendor_order_id} AND Sku = {$sku_size_arr[0]} AND `Size` = '{$sku_size_arr[1]}'";
                        $result = $vendor_db_link->query($check_product_exists_sql);
                        if($result->num_rows > 0){
                            $order_products_sql .= "UPDATE `orderproducts` SET ";
                            $order_products_sql .= "Price = '{$total_price}', QtyOrder = '{$item->get_quantity()}', PackSku = '{$pack_sku}', DateModified =  NOW() ";
                            $order_products_sql .= "WHERE OrderId = '{$vendor_order_id}' AND Sku = '{$sku_size_arr[0]}' AND Size = '{$product_size}';";
                        }else{
                            $order_products_sql .= "INSERT INTO `orderproducts` (";
                            $order_products_sql .= "OrderId, Sku, Size, Price, QtyOrder, PackSku, DateInserted";
                            $order_products_sql .= ") VALUES (";
                            $order_products_sql .= "'{$vendor_order_id}', '{$sku_size_arr[0]}', '{$product_size}', '{$total_price}',";
                            $order_products_sql .= "'{$item->get_quantity()}', '{$pack_sku}', NOW()); ";
                        }

                    }
                }
                $vendor_db_link->multi_query($order_products_sql);
            }
        }
        $vendor_db_link->close();
    }

    add_action('woocommerce_before_delete_order_item', 'remove_item_from_vendor_shared_db');

    function remove_item_from_vendor_shared_db($item_id){
        global $vendor_db_link;
        $order_id = wc_get_order_id_by_order_item_id( $item_id );
        $order = wc_get_order($order_id);

        // Find vendor order id
        $vendor_order_id = get_post_meta($order_id, '_vendor_order_id', true);
        if($order){
            $item = $order->get_item($item_id);
            $product = new WC_Product($item['product_id']);
            $sku_size_arr = explode("-", $product->get_sku());
            $vendor_db_link->query("DELETE FROM orderproducts WHERE OrderId = '{$vendor_order_id}' AND Sku = '{$sku_size_arr[0]}' AND `Size` = '{$sku_size_arr[1]}'");
        }

        return true;
    }

    // Update vendor order status from woocommerce end
    /**
     * Add dropdown for select vendor order status
     */
    add_action( 'woocommerce_admin_order_data_after_order_details', 'woo_add_custom_general_fields_vendor_order_status' );
    function woo_add_custom_general_fields_vendor_order_status($order) {
        // First check this is a vendor order
        $vendor_order_id = get_post_meta($order->get_id(), '_vendor_order_id', true);
        if(!empty($vendor_order_id)) {
            // Get the current vendor order status
            $current_status = get_post_meta( $order->get_id(), '_vendor_order_status', true );
            echo '<div class="options_group">';
            // vendor Order Status 1.0,2.0,2.9,3.0,4.0,5.0
            woocommerce_wp_select( array( // Text Field type
                'id'          => '_vendor_order_status',
                'label'       => __( 'Change vendor Order Status', 'woocommerce' ),
                'value' => $current_status,
                'options'     => array(
                    ''        => __( 'Select vendor Order Status', 'woocommerce' ),
                    '1.0'    => __('1.0', 'woocommerce' ),
                    '2.0' => __('2.0', 'woocommerce' ),
                    '2.9' => __('2.9', 'woocommerce' ),
                    '3.0' => __('3.0', 'woocommerce' ),
                    '4.0' => __('4.0', 'woocommerce' ),
                    '5.0' => __('5.0', 'woocommerce' ),
                )
            ) );

            echo '</div>';
        }
    }

    add_action( 'woocommerce_process_shop_order_meta', 'update_vendor_order_status' );

    function update_vendor_order_status( $order_id ){
        global $vendor_db_link;
        // First check this is a vendor order
        $vendor_order_id = get_post_meta($order_id, '_vendor_order_id', true);
        if(!empty($vendor_order_id)) {
            if(in_array($_POST['_vendor_order_status'], array(1.0, 2.0, 2.9, 3.0, 4.0, 5.0))){
                update_post_meta( $order_id, '_vendor_order_status', wc_clean( $_POST['_vendor_order_status'] ) );

                // Update vendor shared database status
                $vendor_order_update_sql = "UPDATE orders SET OrderStatus = '{$_POST['_vendor_order_status']}' ";
                $vendor_order_update_sql .= "WHERE OrderId = {$vendor_order_id} AND StoreId = 2";
                $vendor_db_link->query($vendor_order_update_sql);
                $vendor_db_link->close();
            }
        }
    }

    // vendor Status Update Bulk Orders
    add_filter('bulk_actions-edit-shop_order', 'vendor_status_as_options_bulk_actions_edit_order', 500, 1);
    /**
     * @param $actions
     * @return mixed
     * Add vendor statuses '1.0, 2.0, 2.9, 3.0, 4.0, 5.0' as dropdown options on bulk actions dropdown
     */
    function vendor_status_as_options_bulk_actions_edit_order($actions)
    {
        $actions['1.0'] = __('1.0', 'woocommerce');
        $actions['2.0'] = __('2.0', 'woocommerce');
        $actions['2.9'] = __('2.9', 'woocommerce');
        $actions['3.0'] = __('3.0', 'woocommerce');
        $actions['4.0'] = __('4.0', 'woocommerce');
        $actions['5.0'] = __('5.0', 'woocommerce');
        return $actions;
    }

    add_filter( 'handle_bulk_actions-edit-shop_order', 'update_vendor_status_orders_edit_shop_order', 10, 3 );
    /**
     * @param $redirect_to
     * @param $action
     * @param $post_ids
     * @return mixed|string
     * Will update vendor status on both woocommerce and vendor shared db end
     */
    function update_vendor_status_orders_edit_shop_order( $redirect_to, $action, $post_ids ) {
        // Check action has vendor status
        if ( !in_array($action, array('1.0', '2.0', '2.9', '3.0', '4.0', '5.0')))
            return $redirect_to; // Exit

        global $vendor_db_link;
        $processed_ids = array();
        $vendor_order_update_sql = '';
        foreach ( $post_ids as $post_id ) {
            $order = wc_get_order($post_id);
            $order_id = $order->get_id();
            // First check this is a vendor order
            $vendor_order_id = get_post_meta($order_id, '_vendor_order_id', true);
            if (!empty($vendor_order_id)) {
                update_post_meta($order_id, '_vendor_order_status', wc_clean($action));
                // Update vendor shared database status
                $vendor_order_update_sql .= "UPDATE orders SET OrderStatus = '{$action}' ";
                $vendor_order_update_sql .= "WHERE OrderId = {$vendor_order_id} AND StoreId = 2;";
                $processed_ids[] = $post_id;
            }
        }
        // execute the query for updating vendor shared db orders
        if(!empty($vendor_order_update_sql)){
            $vendor_db_link->multi_query($vendor_order_update_sql);
        }
        $vendor_db_link->close();
        return $redirect_to = add_query_arg( array(
            'vendor_status_update' => $action,
            'processed_count' => count( $processed_ids ),
            'processed_ids' => implode( ',', $processed_ids ),
        ), $redirect_to );
    }

    add_action( 'admin_notices', 'vendor_status_update_admin_notice' );
    /**
     *  The results notice from bulk action on orders
     */
    function vendor_status_update_admin_notice() {
        if ( empty( $_REQUEST['vendor_status_update'] ) ) return; // Exit

        $count = intval( $_REQUEST['processed_count'] );

        printf( '<div id="message" class="updated fade"><p>' .
            _n( "Processed %s Order for vendor Status Update to status {$_REQUEST['vendor_status_update']}",
                "Processed %s Orders for vendor Status Update to status {$_REQUEST['vendor_status_update']}",
                $count,
                'vendor_status_update'
            ) . '</p></div>', $count );
    }

    // Sync woocommerce order with vendor DB
    add_action('woocommerce_thankyou', 'wp_submit_order_to_vendor', 10, 1);
    function wp_submit_order_to_vendor($order_id)
    {
        global $wpdb, $vendor_db_link;

        $order = wc_get_order($order_id);

        $fedex_hold_loc = 'fedex hold location';
        $fedex_hold_station = '0';
        $fedex_hold_loc_meta = 'fedex_hold_location';
        $fedex_hold_loc_add1 = '';
        $fedex_hold_loc_city = '';
        $fedex_hold_loc_state = '';
        $fedex_hold_loc_zip_code = '';

        // Make sure, order is sent to vendor only once. Do not send multiple request on page reload.
        $vendor_order_id_exists = boolval(get_post_meta($order_id, '_vendor_order_id', true));
        $vendor_product_exists_order = false;
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $check_vendor_product = $product->get_meta('vendor_inventory_type');
            if(!empty($check_vendor_product)) {
                $vendor_product_exists_order = true;
                break;
            }
        }
        if (!$vendor_order_id_exists && $vendor_product_exists_order) {

            $ship_via_type = $vendor_db_link->real_escape_string($order->get_shipping_method());
            // vendor Order Insert Query
            $order_sql = "INSERT INTO `orders` (";
            $order_sql .= "OrderDate, OrderStatus, ShiptoCompany, ShipToFirstName, ShipToLastName, ShipToAddress1, ShipToAddress2, ShipToCity, ";
            $order_sql .= "ShipToState, ShipToZipCode, ShipToCountry, CustomerPhone, CustomeEmail, ShipViaType, ";
            $order_sql .= "OrderTotal, FedExHoldStation, FedExLocaionName, FedExLocaionType, FedExLocationAddress1, FedExLocaionCity, FedExLocaionState, FedExLocaionZipCode, ";
            $order_sql .= "swfOrderId, StoreId";
            $order_sql .= ") VALUES (";
            $order_sql .= "NOW(), '1.0', '{$vendor_db_link->real_escape_string($order->get_shipping_company())}', '{$vendor_db_link->real_escape_string($order->get_shipping_first_name())}','{$vendor_db_link->real_escape_string($order->get_shipping_last_name())}',";
            $order_sql .= "'{$vendor_db_link->real_escape_string($order->get_shipping_address_1())}', '{$vendor_db_link->real_escape_string($order->get_shipping_address_2())}', '{$vendor_db_link->real_escape_string($order->get_shipping_city())}', '{$vendor_db_link->real_escape_string($order->get_shipping_state())}',";
            $order_sql .= "'{$vendor_db_link->real_escape_string($order->get_shipping_postcode())}', '{$vendor_db_link->real_escape_string($order->get_shipping_country())}', '{$vendor_db_link->real_escape_string($order->get_billing_phone())}', '{$vendor_db_link->real_escape_string($order->get_billing_email())}',";
            if(strpos(strtolower($ship_via_type), strtolower($fedex_hold_loc)) !== false){
                $hold_loc_address = $order->get_meta($fedex_hold_loc_meta);
                $fedex_hold_loc_arr = explode(',', $hold_loc_address);
                $fedex_hold_loc_add1 = $fedex_hold_loc_arr[0];
                $fedex_hold_loc_city = $fedex_hold_loc_arr[1];
                $fedex_hold_loc_state = $fedex_hold_loc_arr[2];
                $fedex_hold_loc_zip_code = $fedex_hold_loc_arr[3];
                $fedex_hold_station = '1';
                $ship_via_type = 'FEDEX STANDARD OVERNIGHT';
            }
            if(strpos(strtolower($ship_via_type), 'personal location') !== false){
                $ship_via_type = 'FEDEX PRIORITY OVERNIGHT';
            }
            $order_sql .= "'{$ship_via_type}', '{$order->get_total()}', ";
            $order_sql .= "$fedex_hold_station, 'FedEx Ship Center', 'Stn/WSC', '{$fedex_hold_loc_add1}', '{$fedex_hold_loc_city}', '{$fedex_hold_loc_state}', '{$fedex_hold_loc_zip_code}', ";
            $order_sql .= "'{$order->get_id()}', 2)";
            if ($vendor_db_link->query($order_sql) === TRUE) {
                $last_vendor_order_id = $vendor_db_link->insert_id;
            } else {
                $last_vendor_order_id = '';
                echo '<p style="color:red">Something goes wrong. Please check your log file.</p>';
                vendor_error_log("Error: " . $order_sql . "\n" . $vendor_db_link->error);
            }

            if (empty($last_vendor_order_id)) {
                $wpdb->insert('r4_vendor_failed_orders', array('wc_order_id' => $order_id));
            } else {
                $order->update_meta_data('_vendor_order_response', $last_vendor_order_id.'-Successfully order inserted');
                $order->update_meta_data('_vendor_order_id', $last_vendor_order_id);
                $order->update_meta_data('_vendor_order_status', '1.0');
                $order->save_meta_data();
            }

            // Get and Loop Over Order Items
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $item->get_product();
                $sku = $product->get_sku();
                $sku_size_arr = explode("-", $sku);

                $check_vendor_product = trim($product->get_meta('vendor_inventory_type'));
                if(!empty($check_vendor_product)) {
                    $pack_sku = '';
                    $bundle_product = $product->get_meta('woosb_ids');
                    if(!empty($bundle_product)){
                        continue;
                    }
                    $total_price = $item->get_total();
                    $bundle_pack_sku = !empty($item->get_meta('_woosb_parent_id')) ? $item->get_meta('_woosb_parent_id') : '';
                    if(!empty($bundle_pack_sku)){
                        $pack_sku = 'BUNDLE-'.$bundle_pack_sku;
                    }
                    $product_size =  empty($sku_size_arr[1]) ? '' : $sku_size_arr[1];
                    $order_products_sql = "INSERT INTO `orderproducts` (";
                    $order_products_sql .= "OrderId, Sku, Size, Price, QtyOrder, PackSku, DateInserted";
                    $order_products_sql .= ") VALUES (";
                    $order_products_sql .= "'{$last_vendor_order_id}', '{$sku_size_arr[0]}', '{$product_size}', '{$total_price}',";
                    $order_products_sql .= "'{$item->get_quantity()}', '{$pack_sku}', NOW()); ";
                    $vendor_db_link->query($order_products_sql);
                }
            }
        }
    }

    add_action('woocommerce_admin_order_data_after_billing_address', 'wp_custom_billing_fields_display_admin_order_meta', 10, 1);
    function wp_custom_billing_fields_display_admin_order_meta($order)
    {
        $vendor_order_id = get_post_meta($order->get_ID(), '_vendor_order_id', true);
        if(!empty($vendor_order_id)){
            echo '<p><strong>vendor Order ID:</strong><br>' . $vendor_order_id . '</p>';
        }
    }

    add_action('woocommerce_after_checkout_form', 'wp_action_after_checkout_form_simplified');
    function wp_action_after_checkout_form_simplified()
    {
        global $vendor_db_link;

        // get r4l cart items
        $wpCartProducts = wc()->cart->get_cart_contents();

        // Handle: items missing in vendor cart
        $items_to_insert = array();
        foreach ($wpCartProducts as $wpCartProduct) {
            if ($wpCartProduct['variation_id']) {
                $wcProduct = wc_get_product($wpCartProduct['variation_id']);
            } else {
                $wcProduct = wc_get_product($wpCartProduct['product_id']);
            }

            $product_sku = $wcProduct->get_sku();
            $item_qty = $wpCartProduct['quantity'];
            $vendor_inventory_type = $wcProduct->get_meta('vendor_inventory_type');

            $vendor_products_sql = "SELECT * FROM products ";
            $vendor_products_sql .= "WHERE `SWF-drop_shipper_sku` IS NOT NULL ";
            $vendor_products_sql .= " AND `SWF-drop_shipper_sku` = '{$product_sku}'";

            if ($result = $vendor_db_link -> query($vendor_products_sql)) {
                while ($product = $result->fetch_object()) {
                    if(!empty($vendor_inventory_type)){
                        if ($vendor_inventory_type == 'managed' || $vendor_inventory_type == 'live') {
                            if ($product->QtyAvail < $item_qty) {
                                wc_add_notice("{$wcProduct->get_title()} does not have enough stock. Only {$product->QtyAvail} quantity is available. Please remove this item from your cart or update your quantity, then proceed to checkout.", 'error');
                                $wcProduct->set_stock_quantity($product->QtyAvail);
                                if ($product->QtyAvail <= 0) {
                                    $wcProduct->set_catalog_visibility('hidden');
                                }
                                $wcProduct->save();
                                wp_redirect(wc_get_cart_url());
                                exit;
                            }
                        }
                    } // endif
                } // endwhile
                $result -> free_result();
            } // endif
        }

    }
}
