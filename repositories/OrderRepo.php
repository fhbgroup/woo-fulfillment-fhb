<?php

namespace Kika\Repositories;

use WC_Order;
use WC_Order_Item_Shipping;
use WP_Query;


class OrderRepo
{

	const STATUS_KEY = 'fhb-api-status';
	const ERROR_KEY = 'fhb-api-error';
	const EXPORT_KEY = 'fhb-api-export';
	const API_ID_KEY = 'fhb-api-id';
	const TOKEN_KEY = 'fhb-api-token';
	const WOO_CARRIER_KEY = 'fhb-woo-carrier';
	const TRACKING_NUMBER_KEY = '_fhb-api-tracking-number';
	const TRACKING_LINK_KEY = '_fhb-api-tracking-link';
	const CARRIER_KEY = '_fhb-api-carrier';
	const DELIVERY_POINT_CODE_KEY = '_fhb-delivery-point-code';
	const STATUS_SYNCED = 'synced';
	const STATUS_ERROR = 'error';
	const STATUS_DELETED = 'deleted';
	const STATUS_SKIPPED = 'skipped';

	private $wpdb;
    private $invoice_prefix;
    private $invoice_field;
	private $productIgnoredPrefix;

    /** @var array */
    private $deliveryServiceMapping;

    /** @var boolean */
    private $groupOrders;


    public function __construct()
    {
    	global $wpdb;
    	$this->wpdb = $wpdb;
        $deliveryMapping = get_option('kika_delivery_service_mapping');
		$this->deliveryServiceMapping = unserialize($deliveryMapping);

        $this->invoice_prefix = get_option('kika_invoice_prefix', null);
        $this->invoice_field = get_option('kika_invoice_field', null);
        $this->productIgnoredPrefix = get_option('kika_ignore_product_prefix', null);
		$this->groupOrders = get_option('kika_group_orders');
    }


	public function fetch($args)
	{
		$loop = new WP_Query($args);
		$data = [];

		while ($loop->have_posts()) {
			$loop->the_post();
			$order = new WC_Order(get_the_ID());

			$orderData = $this->prepareData($order);

			if(!$this->groupOrders) {
				$data[] = $orderData;
				continue;
			}

			$index = $orderData['name'] . '-' . $orderData['city'] . '-' . $orderData['email'];
			if(isset($data[$index])) {
				$orderData = $this->groupOrders($data[$index], $orderData);
			}
			$data[$index] = $orderData;
		};

		wp_reset_postdata();
		return $data;
	}


	public function fetchById($id)
	{
		$order = wc_get_order($id);
		return $this->prepareData($order);
	}


	public function fetchForExport($export, $limit = 20)
	{
		$args = [
			'post_type'   => 'shop_order',
			'posts_per_page' => $limit,
			'post_status' => ['wc-processing'],

			'date_query' => [
				'before' => '-10 minutes',
				'after' => '-14 days',
			],

			'meta_query' => [
				'relation' => 'AND',

				[
					'relation' => 'OR',
					[
						'key' => self::STATUS_KEY,
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => self::STATUS_KEY,
						'compare' => 'NOT IN',
						'value' => [self::STATUS_SYNCED, self::STATUS_SKIPPED],
					],
				],

				[
					'relation' => 'OR',
					[
						'key' => self::EXPORT_KEY,
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => self::EXPORT_KEY,
						'compare' => '!=',
						'value' => $export,
					],
				],
			]
		];

		return $this->fetch($args);
	}


	public function count()
	{
		$args = [
			'post_type' => 'shop_order',
			'post_status' => ['wc-processing'],
		];

		$loop = new WP_Query($args);
		return $loop->found_posts;
	}


	public function countByStatus($status)
	{
		$args = [
			'post_type' => 'shop_order',
			'post_status' => ['wc-processing'],

			'meta_query' => [
				[
					[
						'key' => self::STATUS_KEY,
						'value' => $status,
					],
				],
			]
		];

		$loop = new WP_Query($args);
		return $loop->found_posts;
	}


	public function countSynced()
	{
		return $this->countByStatus(self::STATUS_SYNCED);
	}


	public function countError()
	{
		return $this->countByStatus(self::STATUS_ERROR);
	}


	public function prepareData(WC_Order $order)
	{
		$addrType = ($order->get_shipping_first_name()) ? 'shipping' : 'billing';

		$name = $order->{'get_'.$addrType.'_first_name'}() . ' ' . $order->{'get_'.$addrType.'_last_name'}();
		$name = ($order->{'get_'.$addrType.'_company'}()) ? $order->{'get_'.$addrType.'_company'}() . ' - ' . $name : $name;

		$street = $order->{'get_'.$addrType.'_address_1'}();
		$street .= $order->{'get_'.$addrType.'_address_2'}() ? ', ' . $order->{'get_'.$addrType.'_address_2'}() : '';
		$street .= $order->{'get_'.$addrType.'_state'}() ? ', ' . $order->{'get_'.$addrType.'_state'}() : '';

        if ($order->{$addrType.'_state'}) { //get state name instead of code
        	$state = WC()->countries->get_states($order->{$addrType.'_country'})[$order->{$addrType.'_state'}];
        	$province = html_entity_decode($state, ENT_QUOTES | ENT_XML1, 'UTF-8');
        	$city = $order->{'get_'.$addrType.'_city'}();
        	$postcode = $order->{$addrType.'_postcode'} ? $order->{$addrType.'_postcode'} : '00000';
        } else {
        	$province = null;
        	$city = $order->{'get_'.$addrType.'_city'}();
        	$postcode = $order->{'get_'.$addrType.'_postcode'}();
        }

        if ($this->invoice_field || $this->invoice_prefix) {
        	$invoiceLink = str_replace('{order_id}', (string) $order->get_id(), $this->invoice_prefix);
            $invoice = $this->invoice_field ? get_post_meta($order->get_id(), $this->invoice_field, true) : '';
            if ($invoice) {
                $invoiceLink .= $invoice;
            }
        }

        $shippingName = '';
        
		$deliveryPoint = $this->getPacketaPoint($order);

        foreach ($order->get_items('shipping') as $item) {
            if($item instanceof WC_Order_Item_Shipping) {
                $shippingName = $item->get_name();
                if (!$deliveryPoint) {
                	$deliveryPoint = $item->get_meta('zasilkovna-pickup-point-id');
                }
                break;
            }
        }

        if(!$deliveryPoint) {
        	$deliveryPoint = $this->getInPostPoint($order);
        }

        if(!$deliveryPoint) {
        	$deliveryPoint = $this->getCustomPoint($order);
        }

		if ($deliveryPoint) {
			$street .= ' (' . $deliveryPoint . ')';
		}

        $deliveryService = isset($this->deliveryServiceMapping[$shippingName]) ? $this->deliveryServiceMapping[$shippingName] : get_option('kika_service', null);
        //$deliveryService = $this->getMappedDeliveryService($shippingName) ?: get_option('kika_service', null);
        update_post_meta($order->get_id(), self::WOO_CARRIER_KEY, $shippingName);

        $data = [
			'id' => $order->get_id(),
			'variableSymbol' => $order->get_order_number(),
			'name' => $name,
			'email' => $order->get_billing_email(),
			'street' => $street,
			'country' => mb_strtolower($order->{'get_'.$addrType.'_country'}()),
			'city' => $city,
			'province' => $province,
			'psc' => $postcode,
			'phone' => $order->get_billing_phone() ? $order->get_billing_phone() : null,
			'invoiceLink' => isset($invoiceLink) ? $invoiceLink : '',
			'cod' => get_option('kika_method_' . $order->get_payment_method()) ? (float) $order->get_total() : 0,
			'parcelService' => $deliveryService,
			'note' => sprintf("WC carrier name: %s, order total: %.2f", $shippingName, $order->get_total()),
		];

		$items = $order->get_items();
		foreach ($items as $item_id => $item) {
			$product = $order->get_product_from_item($item);
            if($this->productIgnoredPrefix && substr( $product->get_sku(), 0, strlen($this->productIgnoredPrefix)) === $this->productIgnoredPrefix){
                continue;
            }
			$data['_embedded']['items'][] = [
				'id' => $product ? $product->get_sku() : null,
				'qty' => $item['qty'],
			];
		}

		return $data;
	}

	public function groupOrders($order1, $order2)
	{
		$order1['cod'] += $order2['cod'];

		foreach ($order2['_embedded']['items'] as $o2item) {
			foreach ($order1['_embedded']['items'] as &$o1item) {
				if($o1item['id'] == $o2item['id']) {
					$o1item['qty'] += $o2item['qty'];
					continue 2;
				}
			}
			$order1['_embedded']['items'][] = $o2item;
		}

		$order1['groupedIds'][] = $order1['id'];
		$order1['groupedIds'][] = $order2['id'];
		$order1['groupedIds'] = array_unique($order1['groupedIds']);

		return $order1;
	}


	public function getPacketaPoint(WC_Order $order)
	{
		$zasilkovna_id = null;

		// https://github.com/Zasilkovna/WooCommerce/issues/269
        if (in_array( 'packeta/packeta.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    		$packetaTable = $this->wpdb->prefix.'packetery_order';
			$sql = $this->wpdb->prepare("SELECT * FROM $packetaTable WHERE id = %s;", $order->get_id());
			$results = $this->wpdb->get_results($sql);

			foreach($results as $packetaOrder){
		    	$zasilkovna_id =  $packetaOrder->point_id;
			}
		}

		if($zasilkovna_id) return $zasilkovna_id;

		$zasilkovna_id = get_post_meta($order->get_id(), 'zasilkovna_id_pobocky', true);

		return $zasilkovna_id;
	}


	public function getInPostPoint(WC_Order $order)
	{
		return get_post_meta($order->get_id(), 'paczkomat_key', true);
	}


	public function getCustomPoint(WC_Order $order)
	{
		return get_post_meta($order->get_id(), self::DELIVERY_POINT_CODE_KEY, true);
	}

	
	public function getMappedDeliveryService($shippingName)
	{
		foreach ($this->deliveryServiceMapping as $mapName => $deliveryServiceId) {
			if(strpos($shippingName, $mapName) !== false) {
				return $deliveryServiceId;
			}
		}
		return false;
	}

}