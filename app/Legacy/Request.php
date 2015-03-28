<?php namespace App\Legacy;

use App\Util\Ary;

class Request {

	const PARAM_SEPARATOR = '=';

	private $cookiePath = '/';
	private $cookieExp;
	private $params = [];

	public function __construct() {
		/** Timelife for cookies */
		$this->cookieExp = time() + 86400 * 30; // 30 days
	}

	/**
		Fetch a field value from the request.
		Return default value if $name isn’t set in the request, or if $allowed
		is an array and does not contain $name as a key.
		@param string $name
		@param mixed $default
		@param int $paramno
		@param array $allowed Associative array
	*/
	public function value($name, $default = null, $paramno = null, $allowed = null) {
		if ( isset($_REQUEST[$name]) ) {
			$val = $_REQUEST[$name];
		} else if ( is_null($paramno) ) {
			return $default;
		} else if ( isset($this->params[$paramno]) ) {
			$val = $_REQUEST[$name] = $_GET[$name] = $this->params[$paramno];
		} else {
			return $default;
		}
		return is_array($allowed) ? Ary::normKey($val, $allowed, $default) : $val;
	}

	public function checkbox($name, $dims = null) {
		if ( !isset($_REQUEST[$name]) ) {
			return false;
		}
		$val = $_REQUEST[$name];
		if ( is_array($dims) && !empty($dims) ) {
			foreach ($dims as $dim) { $val = $val[$dim]; }
		}
		return $val == 'on';
	}

	/** @return bool */
	public function wasPosted() {
		return @$_SERVER['REQUEST_METHOD'] == 'POST';
	}

	public function referer() {
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}

	private function serverPlain() {
		return $_SERVER['SERVER_NAME'];
	}

	/**
	*/
	public function fileName($name) {
		if( !isset( $_FILES[$name] ) ) { return null; }
		return $_FILES[$name]['name'];
	}

	/**
	 */
	public function fileTempName($name) {
		if ( !isset($_FILES[$name]) ) { return null; }
		if ( $_FILES[$name]['error'] !== 0 ) { return false; }
		return $_FILES[$name]['tmp_name'];
	}

	public function setCookie($name, $value, $expire = null, $multiDomain = true) {
		if ($multiDomain) {
			setcookie($name, $value, ($expire ?: $this->cookieExp), $this->cookiePath, '.'.$this->serverPlain(), false, true);
		}
	}

	public function deleteCookie($name, $multiDomain = true) {
		if ($multiDomain) {
			setcookie($name, '', time() - 86400, $this->cookiePath, '.'.$this->serverPlain());
		}
	}

}
