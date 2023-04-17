<?php

namespace Kika;

use Kika\Api\OrderApi;
use Kika\Api\RestApiException;
use Kika\Repositories\OrderRepo;
use Kika\Repositories\ParcelServiceRepo;
use WC_Order;


class Orders
{

	/** @var OrderApi */
	private $orderApi;

	/** @var OrderRepo */
	private $orderRepo;

	/** @var ParcelServiceRepo */
	private $parcelServiceRepo;

    /** @var array */
    private $ignoreCountries;


	public function __construct(OrderApi $orderApi, OrderRepo $orderRepo, ParcelServiceRepo $parcelServiceRepo)
	{
		$this->orderApi = $orderApi;
		$this->orderRepo = $orderRepo;
		$this->parcelServiceRepo = $parcelServiceRepo;
		$this->ignoreCountries = explode(',', strtolower(get_option('kika_ignore_countries', null)));

		add_action('admin_menu', [$this, 'addMenuItems']);
		add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
		add_action('wp_ajax_fhb_kika_export_orders', [$this, 'export']);
		add_action('wp_job_fhb_kika_export_order', [$this, 'jobExport']);
		add_action('wp_ajax_fhb_kika_export_order', [$this, 'exportSingle']);
		add_action('init', [$this, 'notification'], 10000);

		foreach(get_option('kika_status_delete', []) as $status) {
			$action = 'woocommerce_order_status_' . preg_replace('~^wc-~', '', $status);
			add_action($action, [$this, 'delete']);
		}
	}


	public function addMenuItems()
	{
		add_menu_page('FHB Kika API', 'FHB Kika API', 'manage_options', 'kika-api', [$this, 'render'], 'dashicons-migrate');
		add_submenu_page('kika-api', __('Orders', 'woocommerce-fhb-api'), __('Orders', 'woocommerce-fhb-api'), 'manage_options', 'kika-api', [$this, 'render']);
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

		if (!wp_verify_nonce( $_GET['nonce'], 'kika-api-verify') or !current_user_can( 'edit_others_posts')) {
			$this->returnNoPermission();
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


    public function jobExport()
    {
        set_time_limit(0);
        $export = time();
        $orders = $this->orderRepo->fetchForExport($export, 200);

        $this->exportOrders($orders, $export);
        wp_die();
    }


	public function exportSingle()
	{
		$id = (int)$_GET['order'];

		if (!wp_verify_nonce( $_GET['nonce'], 'kika-api-verify') or !current_user_can( 'edit_others_posts')) {
			$this->returnNoPermission();
		}

		$order = $this->orderRepo->fetchById($id);
		if(!empty($_GET['cod'])) {
			$order['cod'] = floatval($_GET['cod']);
		}
		if(!empty($_GET['service'])) {
			$order['parcelService'] = sanitize_text_field($_GET['service']);
		}

		$logs = $this->exportOrders([$order], time(), true);

		$result = [
			'snippets' => [
				'logs' => $logs ? join('<br>', $logs) . '<br>' : '<span class="log-error">' . __('Order exported', 'woocommerce-fhb-api') . '</span>',
			],
		];

		echo json_encode($result);
		wp_die();
	}


	public function bulkExport($post_ids)
	{
		$data = [];

		foreach ($post_ids as $post_id) {

			$exported = get_post_meta($post_id, OrderRepo::STATUS_KEY, true) == OrderRepo::STATUS_SYNCED;
			if($exported)
				continue;

			$order = new WC_Order($post_id);
			$orderData = $this->orderRepo->prepareData($order);
			
			$index = $orderData['name'] . '-' . $orderData['city'] . '-' . $orderData['email'];
			if(isset($data[$index])) {
				$orderData = $this->orderRepo->groupOrders($data[$index], $orderData);
			}
			$data[$index] = $orderData;
		}

		if(count($data)) {
			$this->exportOrders($data, time());
		}		
	}


	public function getOrder($id)
	{
		if (get_post_meta($id, OrderRepo::STATUS_KEY, true) != OrderRepo::STATUS_SYNCED) {
			return;
		}

		$exportId = get_post_meta($id, OrderRepo::API_ID_KEY, true);

		try {
			$order = $this->orderApi->read($exportId ? $exportId : $id);
			return $order;
		} catch(RestApiException $e) {
			return;
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

			if(isset($_GET['order'])) {
				$ids = [(int) $_GET['order']];
			} elseif(isset($_GET['orders'])) {
				$ids = array_map('intval', explode(',', $_GET['orders']));
			}


			$type = sanitize_text_field($_GET['type']);
			$token = $_GET['token'];

			$status = get_option("kika_notify_$type");

			foreach($ids as $orderId) {

				if (get_post_meta($orderId, OrderRepo::TOKEN_KEY, true) != $token) {
					header('HTTP/1.0 403 Forbidden');
					exit;
				}

				$order = wc_get_order($orderId);


				if ($order and $status) {
					$order->update_status($status, '', true);
				}

				if($type == 'sent') {
					$trackingUrls = [];
					$kikaOrder = $this->getOrder($orderId);

					if($kikaOrder->status !== 'sent') {
						continue;
					}

					if(isset($kikaOrder->_embedded->trackingNumber)) {
						$trackings = $kikaOrder->_embedded->trackingNumber;
					} else {
						continue;
					}

					$msg = __('Order was sent with tracking number ', 'woocommerce-fhb-api');
					if(isset($kikaOrder->_embedded->trackingLink)) {
						$trackingLinks = [];
						foreach ($trackings  as $i => $track) {
							$link = '<a href="' . $kikaOrder->_embedded->trackingLink[$i] . '">' . $trackings[$i] . '</a>';
							$msg .= ' ' . $link;
							$trackingLinks[] = $link;
 						}

					} else {
						$msg .= implode(',', $trackings);
					}

					$msg .= ".";

					$order->add_order_note($msg, true);

					update_post_meta($order->get_id(), OrderRepo::TRACKING_NUMBER_KEY, implode(',', $trackings));
					
					$parcelServices = $this->parcelServiceRepo->fetch();
					$assocParcelServices = array_combine(array_column($parcelServices, 'code'), array_column($parcelServices, 'name'));

					if(isset($assocParcelServices[$kikaOrder->parcelService])) {
						update_post_meta($order->get_id(), OrderRepo::CARRIER_KEY, $assocParcelServices[$kikaOrder->parcelService]);
					}
					
					if(isset($trackingLinks)) {
						update_post_meta($order->get_id(), OrderRepo::TRACKING_LINK_KEY, implode(',', $trackingLinks));
					}

				}
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
			$exportId = $prefix ? $prefix . '-' . $id : $id;
			$order['id'] = $exportId;
			$order['variableSymbol'] = $prefix ? $prefix . $order['variableSymbol'] : $order['variableSymbol'];


			if(isset($order['groupedIds'])) {
				$ids = $order['groupedIds'];
			} else {
				$ids = [$id];
			}
            unset($order['groupedIds']);


			$this->bulk_update_post_meta($ids, OrderRepo::EXPORT_KEY, $export);

			$token = md5(wp_generate_password(10, true, true));
			$url = home_url() . "/?action=kika-notification&token=$token";

			if(count($ids) == 1) {
				$url .= "&order=" . $ids[0];
			} else {
				$url .= "&orders=" . implode(',', $ids);
			}


			$order['_embedded']['notifyLinks'][] = [
				'confirmed' => "$url&type=confirmed",
				'sent' =>  "$url&type=sent",
				'delivered' =>  "$url&type=delivered",
				'returned' =>  "$url&type=returned",
			];

			if (empty($order['_embedded']['items'])) {
                $this->bulk_update_post_meta($ids, OrderRepo::STATUS_KEY, OrderRepo::STATUS_SKIPPED);
                $logs[] = '<span class="log-error">Order has no products, order skipped.</span>';
			    continue;
            }

            if (in_array($order['country'], $this->ignoreCountries)) {
            	$this->bulk_update_post_meta($ids, OrderRepo::STATUS_KEY, OrderRepo::STATUS_SKIPPED);
            	$logs[] = '<span class="log-error">Order country not allowed, order skipped.</span>';
            	continue;
            }


			try {
				$this->orderApi->create($order);
				$this->bulk_update_post_meta($ids, OrderRepo::STATUS_KEY, OrderRepo::STATUS_SYNCED);
				$this->bulk_update_post_meta($ids, OrderRepo::TOKEN_KEY, $token);
				$this->bulk_update_post_meta($ids, OrderRepo::API_ID_KEY, $exportId);

			} catch (RestApiException $e) {
				$this->bulk_update_post_meta($ids, OrderRepo::STATUS_KEY, OrderRepo::STATUS_ERROR);
				$this->bulk_update_post_meta($ids, OrderRepo::ERROR_KEY, "Api ID: $exportId. " . $e->getMessage());
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

    private function returnNoPermission()
    {
        $result = [
            'snippets' => [
                'logs' => '<span class="log-error">You have no permissions for this task.</span>',
            ],
        ];
		echo json_encode($result);
		wp_die();
    }


    private function bulk_update_post_meta($ids, $key, $value)
    {
    	foreach ($ids as $id) {
    		update_post_meta($id, $key, $value);
    	}
    }

}