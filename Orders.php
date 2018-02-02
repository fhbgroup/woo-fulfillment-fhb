<?php

namespace Kika;

use Kika\Api\OrderApi;
use Kika\Api\RestApiException;
use Kika\Repositories\OrderRepo;
use Kika\Repositories\ParcelServiceRepo;


class Orders
{

	/** @var OrderApi */
	private $orderApi;

	/** @var OrderRepo */
	private $orderRepo;

	/** @var ParcelServiceRepo */
	private $parcelServiceRepo;


	public function __construct(OrderApi $orderApi, OrderRepo $orderRepo, ParcelServiceRepo $parcelServiceRepo)
	{
		$this->orderApi = $orderApi;
		$this->orderRepo = $orderRepo;
		$this->parcelServiceRepo = $parcelServiceRepo;

		add_action('admin_menu', [$this, 'addMenuItems']);
		add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
		add_action('wp_ajax_fhb_kika_export_orders', [$this, 'export']);
		add_action('wp_ajax_fhb_kika_export_order', [$this, 'exportSingle']);
		add_action('woocommerce_thankyou', [$this, 'exportAfterCreate']);
		add_action('init', [$this, 'notification'], 10000);

		foreach(get_option('kika_status_delete', []) as $status) {
			$action = 'woocommerce_order_status_' . preg_replace('~^wc-~', '', $status);
			add_action($action, [$this, 'delete']);
		}
	}


	public function addMenuItems()
	{
		add_menu_page('FHB Kika API', 'FHB Kika API', 'manage_options', 'kika-api', [$this, 'render'], 'dashicons-migrate');
		add_submenu_page('kika-api', 'Objednávky', 'Objednávky', 'manage_options', 'kika-api', [$this, 'render']);
	}


	function addMetaBoxes()
	{
		add_meta_box('wc_fhb_kika_order', 'FHB Kika API', [$this, 'renderBox'], 'shop_order', 'side');
	}


	public function renderBox()
	{
		global $post_id;
		$nonce = wp_create_nonce('kika-api-verify');
		$exported = get_post_meta($post_id, OrderRepo::STATUS_KEY, true) == OrderRepo::STATUS_SYNCED;
		$order = wc_get_order($post_id);
		$services = $this->parcelServiceRepo->fetch();
		require 'templates/orderBox.php';
	}


	public function render()
	{
		$export = time();
		$nonce = wp_create_nonce('kika-api-verify');
		$stats = $this->createStats();
		require 'templates/orders.php';
	}


	public function export()
	{
		set_time_limit(0);
		$export = (int)$_GET['export'];

		if (!wp_verify_nonce( $_GET['nonce'], 'kika-api-verify') or !current_user_can( 'manage_options')) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		$orders = $this->orderRepo->fetchForExport($export);

		if (!$orders) {
			header('HTTP/1.0 404 Orders not found.');
			exit;
		}

		$logs = $this->exportOrders($orders, $export);

		$result = [
			'snippets' => [
				'stats' => $this->createStats(),
				'logs' => $logs ? join('<br>', $logs) . '<br>' : '',
			],
		];

		sleep(2);
		echo json_encode($result);
		wp_die();
	}


	public function exportSingle()
	{
		$id = (int)$_GET['order'];
		$cod = floatval($_GET['cod']);
		$service = sanitize_text_field($_GET['service']);

		if (!wp_verify_nonce( $_GET['nonce'], 'kika-api-verify') or !current_user_can( 'manage_options')) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		$order = $this->orderRepo->fetchById($id);
		$logs = $this->exportOrders([$order], time(), true);

		$result = [
			'snippets' => [
				'logs' => $logs ? join('<br>', $logs) . '<br>' : '<span class="log-error">Objednávka exportovaná</span>',
			],
		];

		echo json_encode($result);
		wp_die();
	}


	public function exportAfterCreate($id)
	{
		if (get_option('kika_order_send') and !get_post_meta($id, OrderRepo::STATUS_KEY, true)) {
			$order = $this->orderRepo->fetchById($id);
			$this->exportOrders([$order], time(), true);
		}
	}


	public function delete($id)
	{
		if (get_post_meta($id, OrderRepo::STATUS_KEY, true) != OrderRepo::STATUS_SYNCED) {
			return;
		}

		$exportId = get_post_meta($id, OrderRepo::API_ID_KEY, true);

		try {
			$this->orderApi->delete($exportId ? $exportId : $id);
			update_post_meta($id, OrderRepo::STATUS_KEY,  OrderRepo::STATUS_DELETED);
		} catch (RestApiException $e) {
			update_post_meta($id, OrderRepo::STATUS_KEY, OrderRepo::STATUS_ERROR);
			update_post_meta($id, OrderRepo::ERROR_KEY, "Api ID: $exportId. " . $e->getMessage());
		}
	}


	public function notification()
	{
		if (isset($_GET['action']) and $_GET['action'] == 'kika-notification') {

			$id = (int)$_GET['order'];
			$type = sanitize_text_field($_GET['type']);
			$token = $_GET['token'];

			if (get_post_meta($id, OrderRepo::TOKEN_KEY, true) != $token) {
				header('HTTP/1.0 403 Forbidden');
				exit;
			}

			$status = get_option("kika_notify_$type");
			$order = wc_get_order($id);

			if ($order and $status) {
				$order->update_status($status, '', true);
			}

			exit;
		}
	}


	private function createErrorMessage(RestApiException $e, $order, $single = false)
	{
		$message = '<span class="log-error">' . $e->getMessage() . '</span>';
		return !$single ? $order['name'] . ' .... ' . $message : $message;
	}


	private function exportOrders($orders, $export, $single = false)
	{
		$logs = [];
		$prefix = get_option('kika_prefix');

		foreach ($orders as $order) {

			$id = $order['id'];
			$exportId = $prefix ? "$prefix-$id" : $id;
			$order['id'] = $exportId;

			update_post_meta($id, OrderRepo::EXPORT_KEY, $export);

			$token = md5(wp_generate_password(10, true, true));
			$url = home_url() . "?action=kika-notification&order=$id&token=$token";

			$order['_embedded']['notifyLinks'][] = [
				'confirmed' => "$url&type=confirmed",
				'sent' =>  "$url&type=sent",
				'delivered' =>  "$url&type=delivered",
				'returned' =>  "$url&type=returned",
			];

			try {
				$this->orderApi->create($order);
				update_post_meta($id, OrderRepo::STATUS_KEY, OrderRepo::STATUS_SYNCED);
				update_post_meta($id, OrderRepo::TOKEN_KEY, $token);
				update_post_meta($id, OrderRepo::API_ID_KEY, $exportId);

			} catch (RestApiException $e) {
				update_post_meta($id, OrderRepo::STATUS_KEY, OrderRepo::STATUS_ERROR);
				update_post_meta($id, OrderRepo::ERROR_KEY, "Api ID: $exportId. " . $e->getMessage());
				$logs[] = $this->createErrorMessage($e, $order, $single);
			}
		}

		return $logs;
	}


	private function createStats()
	{
		$count = $this->orderRepo->count();
		$countError = $this->orderRepo->countError();
		$countSynced = $this->orderRepo->countSynced();
		ob_start();
		require 'templates/orderStats.php';
		return ob_get_clean();
	}

}