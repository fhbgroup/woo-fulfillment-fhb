<?php

namespace Kika\Api;


class ProductApi
{

	/** @var RestApi */
	private $api;


	public function __construct(RestApi $api)
	{
		$this->api = $api;
	}


	public function create(array $data)
	{
		return $this->api->post('products', $data);
	}


	public function update($id, array $data)
	{
		return $this->api->patch("products?id=$id", $data);
	}


	public function read($id)
	{
		return $this->api->get("products?id=$id");
	}


	public function readAll($page=1)
	{
		return $this->api->get("products?page=$page");
	}

}