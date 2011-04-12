<?php
namespace Chitanka\LibBundle\Legacy;

class CacheHolder {

	private
		$_bin = array();


	public function exists($key)
	{
		return array_key_exists($key, $this->_bin);
	}

	public function get($key)
	{
		return array_key_exists($key, $this->_bin)
			? $this->_bin[$key]
			: false;
	}

	public function set($key, $value)
	{
		return $this->_bin[$key] = $value;
	}

	public function clear()
	{
		$this->_bin = array();
	}
}