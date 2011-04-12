<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\Ary;

class Request {

	const
		STANDARD_PORT = 80,
		PARAM_SEPARATOR = '=',
		ONEDAYSECS = 86400; // 60*60*24 - number of seconds in a day

	protected
		$cookiePath = '/',
		$bots = array('bot', 'search', 'crawl', 'spider', 'fetch', 'reader',
			'subscriber', 'google', 'rss'),
		// hash of the request
		$hash;


	public function __construct() {
		/** Timelife for cookies */
		$this->cookieExp = time() + self::ONEDAYSECS * 30; // 30 days
		$this->ua = strtolower(@$_SERVER['HTTP_USER_AGENT']);
	}


	public function action() {
		return $this->action;
	}


	/**
		Fetch a field value from the request.
		Return default value if $name isn’t set in the request, or if $allowed
		is an array and does not contain $name as a key.
		@param $name
		@param $default
		@param $paramno
		@param $allowed Associative array
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

	public function setValue($name, $value) {
		$_REQUEST[$name] = $_GET[$name] = $value;
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


	public function isBotRequest() {
		foreach ($this->bots as $bot) {
			if ( strpos($this->ua, $bot) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
		Tests whether a given set of parameters corresponds to the GET request.

		@param $reqData Associative array
		@return bool
	*/
	public function isCurrentRequest($reqData) {
		if (	!is_array($reqData) ||
				count(array_diff_assoc($_GET, $reqData)) > 0 ||
				count(array_diff_assoc($reqData, $_GET)) > 0 ) {
			return false;
		}
		foreach ($_GET as $param => $val) {
			if ($reqData[$param] != $val) {
				return false;
			}
		}
		return true;
	}

	public function referer() {
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}


	public function serverPlain() {
		return $_SERVER['SERVER_NAME'];
	}

	public function server() {
		$s = @$_SERVER['HTTPS'] != 'off' ? 'http' : 'https';
		$s .= '://' . $_SERVER['SERVER_NAME'];
		if ( $_SERVER['SERVER_PORT'] != self::STANDARD_PORT ) {
			$s .= ':' . $_SERVER['SERVER_PORT'];
		}
		return $s;
	}


	public function requestUri( $absolute = false ) {
		$uri = $absolute ? $this->server() : '';
		$uri .= $_SERVER['REQUEST_URI'];
		return $uri;
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


	public function getParams()
	{
		return $this->params;
	}

	public function hash() {
		if ( empty($this->hash) ) {
			ksort($_GET);
			$this->hash = md5( serialize($_GET + $this->params) );
		}
		return $this->hash;
	}


	public function setCookie($name, $value, $expire = null, $multiDomain = true) {
		if (is_null($expire)) $expire = $this->cookieExp;

		//setcookie($name, $value, $expire, $this->cookiePath);
		if ($multiDomain) {
			setcookie($name, $value, $expire, $this->cookiePath, '.'.$this->serverPlain());
		}
	}


	public function deleteCookie($name, $multiDomain = true) {
		setcookie($name, '', time() - 3600, $this->cookiePath);
		if ($multiDomain) {
			setcookie($name, '', time() - 3600, $this->cookiePath, '.'.$this->serverPlain());
		}
	}


	public function makeInputFieldsForGetVars($exclude = array()) {
		$c = '';
		foreach ($_GET as $name => $value) {
			if ( in_array($name, $exclude) || is_numeric($name) ) {
				continue;
			}
			$c .= "<input type='hidden' name='$name' value='$value' />\n";
		}
		return $c;
	}


	public static function isXhr()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	public static function isAjax()
	{
		return self::isXhr();
	}


	public function isMSIE() {
		return strpos($this->ua, 'msie') !== false;
	}


	public function isCompleteSubmission() {
		return $this->value('submitButton') !== null;
	}

}
