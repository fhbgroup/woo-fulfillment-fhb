<?php

/**
 * @package Fhb Kika API
 * @author Fhb
 *
 * @wordpress-plugin
 * Plugin Name: FHB Kika API - Approve products
 * Plugin URI: http://www.fhb.sk/
 * Description: Woocommerce integration for FHB fulfillment system - Approve products
 * Version: 3.16XX
 * Text Domain: woo-fulfillment-fhb
 * Domain Path: /languages
 */

// Approve products - possibility to set product "autoexport" property (allowed / disabled)
// when disabled, order will not be exported automatically - status skipped will be set to order
// orders with disabled products can be exported manually, or by bulk export action (on order overview page)

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

$orders = new Orders($orderApi, $orderRepo, $parcelServiceRepo, $productRepo);
$products = new Products($productApi, $productRepo, get_option('kika_sandbox'));
new SettingPanel($parcelServiceRepo);


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


add_filter( 'manage_edit-product_columns', function($columns) {
    $columns[ProductRepo::AUTOEXPORT_KEY] = 'FHB Autoexport';
    return $columns;
}, 20);

add_filter('bulk_actions-edit-shop_order', function($actions) {
	$actions['fhb-bulk-export'] = "FHB Bulk export";
	$actions['fhb-export-job'] = "FHB Export job";
	return $actions;
}, 20, 1);


add_filter('handle_bulk_actions-edit-shop_order', function($redirect_to, $action, $post_ids) use ($orders) {
	if($action != 'fhb-bulk-export' && $action != 'fhb-export-job') {
		return $redirect_to;
	}
	if($action == "fhb-bulk-export"){
		$orders->bulkExport($post_ids);
	} elseif($action == "fhb-export-job") {
		$orders->jobExport(false);
	}

	return $redirect_to;
}, 20, 3);


add_filter('bulk_actions-edit-product', function($actions) {
	$actions['fhb-auto-export-true'] = "FHB Autoexport Allow";
	$actions['fhb-auto-export-false'] = "FHB Autoexport Disable";
	//$actions['fhb-auto-export-get'] = "FHB Autoexport GET"; // debugging
	return $actions;
}, 20, 1);


add_filter('handle_bulk_actions-edit-product', function($redirect_to, $action, $post_ids) use ($products, $productRepo) {
	if($action != 'fhb-auto-export-true' && $action != 'fhb-auto-export-false' && $action != 'fhb-auto-export-get') {
		return $redirect_to;
	}

	if($action == 'fhb-auto-export-true') {
		$products->setAutoExport($post_ids, "allowed");
	} elseif($action == 'fhb-auto-export-false') {
		$products->setAutoExport($post_ids, "disabled");
	} elseif($action == 'fhb-auto-export-get') { // debugging only
		$prods = $productRepo->getAutoDisabledProducts();
		error_log("disabled products");
		error_log(serialize($prods));
	}

	return $redirect_to;
}, 20, 3);


add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
	echo get_post_meta($post_id, $column, true);
}, 10, 2);


add_action('manage_product_posts_custom_column', function($column, $post_id) {
    if($column == ProductRepo::AUTOEXPORT_KEY) {
        echo get_post_meta($post_id, $column, true);
    }
}, 10, 2);


add_action( 'plugins_loaded', function() {
		load_plugin_textdomain( 'woocommerce-fhb-api', FALSE, basename( dirname( 	__FILE__ ) ) . '/languages/' );
	} 
);