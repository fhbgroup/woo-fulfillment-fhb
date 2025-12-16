<?php

/**
 * @package Fhb Kika API
 * @author Fhb
 *
 * @wordpress-plugin
 * Plugin Name: Kika API
 * Plugin URI: http://www.fhb.sk/
 * Description: Woocommerce integrácia na fullfilment systém KIKA
 * Version: 3.27
 * Text Domain: woo-fulfillment-fhb
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define('KIKA_PLUGIN_URL', plugin_dir_url( __FILE__));

register_activation_hook(__FILE__, function() {
	update_option('kika_method_cod', true);
	update_option('kika_status_delete', ['wc-cancelled', 'wc-failed']);
});

register_deactivation_hook(__FILE__, function() {
    if (wp_next_scheduled('wp_job_fhb_kika_export_order')) {
        wp_clear_scheduled_hook('wp_job_fhb_kika_export_order');
    }
});

add_action('admin_enqueue_scripts', function() {
	wp_enqueue_script('fhb-kika-api-js', plugins_url('assets/fhb-kika.js', __FILE__), array('jquery'));
	wp_enqueue_style('fhb-kika-api-css', plugins_url('assets/fhb-kika.css', __FILE__));
});

require_once('api/RestApi.php');
require_once('api/OrderApi.php');
require_once('api/ProductApi.php');
require_once('api/InfoApi.php');
require_once('repositories/ProductRepo.php');
require_once('repositories/OrderRepo.php');
require_once('repositories/ParcelServiceRepo.php');
require_once('SettingPanel.php');
require_once('Orders.php');
require_once('Products.php');
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

use Kika\Api\RestApi;
use Kika\Api\OrderApi;
use Kika\Api\ProductApi;
use Kika\Api\InfoApi;
use Kika\SettingPanel;
use Kika\Products;
use Kika\Orders;
use Kika\Repositories\ProductRepo;
use Kika\Repositories\OrderRepo;
use Kika\Repositories\ParcelServiceRepo;


$apiId = get_option('kika_appid');
$secret = get_option('kika_secret');

$restApi = new RestApi($apiId, $secret);

if (get_option('kika_sandbox')) {
	$restApi->setEndpoint('https://system-dev.fhb.sk/api/v2');
	//$restApi->setEndpoint('localhost/kika-system/api/v2');
}

$productApi = new ProductApi($restApi);
$orderApi = new OrderApi($restApi);
$infoApi = new InfoApi($restApi);

$productRepo = new ProductRepo();
$parcelServiceRepo = new ParcelServiceRepo($infoApi);
$orderRepo = new OrderRepo($parcelServiceRepo);

$orders = new Orders($orderApi, $orderRepo, $parcelServiceRepo);
new Products($productApi, $productRepo, get_option('kika_sandbox'));
new SettingPanel($parcelServiceRepo);


// legacy order table
add_filter( 'manage_edit-shop_order_columns', function($columns) {
	$new_columns = array();
	foreach ( $columns as $column_name => $column_info ) {
		$new_columns[ $column_name ] = $column_info;
		if ( 'order_total' === $column_name ) {
			$new_columns[OrderRepo::STATUS_KEY] = 'Kika API';
		}
	}
	return $new_columns;
}, 20);


// HSPOS order table
add_filter(
	'woocommerce_shop_order_list_table_columns',
	function ($columns) {
		$columns[ OrderRepo::STATUS_KEY ] = 'Kika API';
		return $columns;
	}
);


// legacy bulk action
add_filter('bulk_actions-edit-shop_order', function($actions) {
	$actions['fhb-bulk-export'] = "FHB Bulk export";
	return $actions;
}, 20, 1);


// HPOS bulk action
add_filter('bulk_actions-woocommerce_page_wc-orders', function($actions) {
    $actions['fhb-bulk-export'] = "FHB Bulk export";
    return $actions;
}, 20, 1);


// legacy bulk action handler
add_filter('handle_bulk_actions-edit-shop_order', function($redirect_to, $action, $post_ids) use ($orders) {
	if($action != 'fhb-bulk-export') {
		return $redirect_to;
	}

	$orders->bulkExport($post_ids);

	return $redirect_to;
}, 20, 3);


// HPOS bulk action handler
add_filter('handle_bulk_actions-woocommerce_page_wc-orders', function($redirect_to, $action, $order_ids) use ($orders) {
    if($action != 'fhb-bulk-export') {
        return $redirect_to;
    }

    $orders->bulkExport($order_ids);

    return $redirect_to;
}, 20, 3);


// legacy order table col rendering
add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
	if($column == OrderRepo::STATUS_KEY) {
		echo get_post_meta($post_id, $column, true);
	}
}, 10, 2);


// hspos order table col rendering
add_action(
	'woocommerce_shop_order_list_table_custom_column',
	function ($column, $order) {
		if ($column === OrderRepo::STATUS_KEY) {
			echo esc_html(
				$order->get_meta(OrderRepo::STATUS_KEY)
			);
		}
	},
	10,
	2
);


add_action( 'plugins_loaded', function() {
		load_plugin_textdomain( 'woocommerce-fhb-api', FALSE, basename( dirname( 	__FILE__ ) ) . '/languages/' );
	} 
);