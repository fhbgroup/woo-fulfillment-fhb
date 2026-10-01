<?php

namespace Kika\Api\V3;


class ProductApi
{

	/** @var RestApi */
	private $api;


	public function __construct(RestApi $api)
	{
		$this->api = $api;
	}


	public function getEndpoint()
	{
		return $this->api->getEndpoint();
	}


	/** @return object {page, total, products[]} - 250 products per page */
	public function readAll($page = 1)
	{
		return $this->api->get('product/all?page=' . (int) $page);
	}

}
