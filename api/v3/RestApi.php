<?php

namespace Kika\Api\V3;

use Kika\Api\RestApiException;


class RestApi
{

	const S200_OK = 200;

	const GET = 'GET';
	const POST = 'POST';

	/** @var string */
	private $endpoint = 'https://api.fhb.sk/v3';

	/** @var string */
	private $appId;

	/** @var string */
	private $secret;

	/** @var string */
	private $token;


	public function __construct($appId, $secret)
	{
		$this->appId = $appId;
		$this->secret = $secret;
	}


	public function setEndpoint($endpoint)
	{
		$this->endpoint = $endpoint;
	}


	public function getEndpoint()
	{
		return $this->endpoint;
	}


	public function getToken()
	{
		if (!$this->token) {
			$this->token = $this->createToken();
		}

		return $this->token;
	}


	public function get($action)
	{
		return $this->call(self::GET, $action);
	}


	public function post($action, array $data)
	{
		return $this->call(self::POST, $action, $data);
	}


	private function call($method, $action, array $data = null)
	{
		$curl = $this->login($action);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

		if ($data) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		$json = json_decode($response);

		if ($httpCode != self::S200_OK) {
			$message = isset($json->message) ? $json->message : "Unknown error. Http code {$httpCode}.";
			throw new RestApiException($message, $httpCode);
		}

		return $json;
	}


	private function login($action)
	{
		$curl = $this->createCurl($action);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"X-Authentication-Simple: " . base64_encode($this->getToken())
		));
		return $curl;
	}


	private function createToken()
	{
		$data = [
			'app_id' => $this->appId,
			'secret' => $this->secret
		];

		$curl = $this->createCurl('login');
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		$json = json_decode($response);
		curl_close($curl);

		if ($httpCode != self::S200_OK) {
			$message = isset($json->message) ? $json->message : "Unknown error. Http code $httpCode.";
			throw new RestApiException($message, $httpCode);
		}

		return $json->token;
	}


	private function createCurl($action)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "{$this->endpoint}/{$action}");
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		return $curl;
	}

}
