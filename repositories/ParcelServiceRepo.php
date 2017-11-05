<?php

namespace Kika\Repositories;

use Kika\Api\InfoApi;
use Kika\Api\RestApiException;


class ParcelServiceRepo
{

	const KEY = 'kika_parcel_services';
	const EXPIRE_KEY = 'kika_parcel_services_expired';

	/** @var InfoApi */
	private $infoApi;


	public function __construct(InfoApi $infoApi)
	{
		$this->infoApi = $infoApi;
	}


	public function fetch()
	{
		$services = get_option(self::KEY);

		if (!$services or get_option(self::EXPIRE_KEY) < time()) {
			$services = $this->download();
		}

		return $services;
	}


	private function download()
	{
		try {
			$result = $this->infoApi->getParcelServices();
			$services = isset($result->_embedded->services) ? (array)$result->_embedded->services : [];

			if (count($services)) {
				update_option(self::KEY, $services);
				update_option(self::EXPIRE_KEY, time() + (60*60*24*7));
			}

		} catch (RestApiException $e) {
			$services = get_option(self::KEY, []);
		}

		return $services;
	}

}
