<?php

namespace Kika\Api;


class InfoApi
{

	/** @var RestApi */
	private $api;


	public function __construct(RestApi $api)
	{
		$this->api = $api;
	}


	public function getParcelServices()
	{
		return $this->api->get('parcel-service');
	}

}