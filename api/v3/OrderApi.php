<?php

namespace Kika\Api\V3;


class OrderApi
{

	/** @var RestApi */
	private $api;


	public function __construct(RestApi $api)
	{
		$this->api = $api;
	}


	public function read($id)
	{
		$data = $this->api->get('order?id=' . urlencode($id));
		return new Order($data);
	}


	public function create(Order $order)
	{
		$data = $this->api->post('order', $order->toArray());
		return new Order($data);
	}

}
