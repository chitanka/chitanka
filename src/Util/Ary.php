<?php namespace App\Util;

class Ary {

	/**
	* @param string $key Key
	* @param array $data Associative array
	* @param mixed $defKey Default key
	* @return mixed $key if it exists as key in $data, otherwise $defKey
	*/
	public static function normKey($key, $data, $defKey = '') {
		return array_key_exists($key, $data) ? $key : $defKey;
	}

	/**
	 * @param string[] $arr1
	 * @param string[] $arr2
	 */
	public static function cartesianProduct($arr1, $arr2) {
		$prod = [];
		foreach ($arr1 as $val1) {
			foreach ($arr2 as $val2) {
				$prod[] = $val1 . $val2;
			}
		}
		return $prod;
	}

}
