<?php

namespace Kika\Api\V3;


/**
 * Base for wrappers around a JSON API object - used both for reading a
 * decoded API response and for building a payload to send back. Named
 * getters/setters cover stable fields; get()/set()/raw() stay available
 * for anything not modeled, or added by the API later.
 */
abstract class View
{

	/** @var object */
	protected $data;


	public function __construct($data = null)
	{
		$this->data = $data ?: new \stdClass();
	}


	public function get($field, $default = null)
	{
		return property_exists($this->data, $field) ? $this->data->$field : $default;
	}


	public function set($field, $value)
	{
		$this->data->$field = $value;
		return $this;
	}


	public function raw()
	{
		return $this->data;
	}


	/**
	 * Plain array form of the wrapped data, suitable as a request payload.
	 * Set field values as scalars/arrays (not View instances) so this
	 * round-trips through JSON cleanly.
	 */
	public function toArray()
	{
		return json_decode(json_encode($this->data), true);
	}

}
