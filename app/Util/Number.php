<?php namespace App\Util;

class Number {

	private static $phpIniSuffixValues = [
		// suffix => multiplier as bit shift, e.g. 1k = 1 << 10
		'k' => 10,
		'm' => 20,
		'g' => 30,
	];

	public static function normInt($val, $max, $min = 1) {
		if ($val > $max) {
			$val = $max;
		} else if ($val < $min) {
			$val = $min;
		}

		return (int) $val;
	}

	public static function formatNumber($num, $decPl = 2, $decPoint = ',', $tousandDelim = ' ') {
		$result = number_format($num, $decPl, $decPoint, $tousandDelim);
		if ($decPoint == ',' && $num < 10000) {
			// bulgarian extra rule: put a $tousandDelim only after 9999
			$result = preg_replace('/^(\d) (\d\d\d)/', '$1$2', $result);
		}

		return $result;
	}

	/**
	 * Removes trailing zeros after the decimal sign
	 * @param string $number
	 * @param string $decPoint
	 * @return string
	 */
	public static function rmTrailingZeros($number, $decPoint = ',') {
		$number = rtrim($number, '0');
		$number = rtrim($number, $decPoint); // remove the point too
		return $number;
	}


	/**
	 * Convert a php.ini value to an integer
	 * @param string $val
	 * @return int
	 */
	public static function iniBytes($val) {
		$val = trim($val);
		$lastChar = strtolower($val[strlen($val)-1]);
		if (isset(self::$phpIniSuffixValues[$lastChar])) {
			return $val << self::$phpIniSuffixValues[$lastChar];
		}
		return $val;
	}

	/**
	 * bytes to human readable
	 * @param int $bytes
	 * @return string
	 */
	public static function int_b2h($bytes) {
		if ( $bytes < ( 1 << 10 ) ) {
			return $bytes . ' B';
		}
		if ( $bytes < ( 1 << 20 ) ) {
			return self::int_b2k( $bytes ) . ' KiB';
		}
		if ( $bytes < ( 1 << 30 ) ) {
			return self::int_b2m( $bytes ) . ' MiB';
		}
		return self::int_b2g( $bytes ) . ' GiB';
	}

	/**
	 * bytes to kibibytes
	 * @param int $bytes
	 * @return int
	 */
	public static function int_b2k($bytes) {
		$k = $bytes >> 10; // divide by 2^10 w/o rest
		return $k > 0 ? $k : 1;
	}
	/**
	 * bytes to mebibytes
	 * @param int $bytes
	 * @return int
	 */
	public static function int_b2m($bytes) {
		$m = $bytes >> 20; // divide by 2^20 w/o rest
		return $m > 0 ? $m : 1;
	}

	/**
	 * bytes to gibibytes
	 * @param int $bytes
	 * @return int
	 */
	public static function int_b2g($bytes) {
		$m = $bytes >> 30; // divide by 2^30 w/o rest
		return $m > 0 ? $m : 1;
	}
}
