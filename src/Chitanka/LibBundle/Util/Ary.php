<?php
namespace Chitanka\LibBundle\Util;

class Ary
{
	/**
	* @param $key Key
	* @param $data Associative array
	* @param $defKey Default key
	* @return $key if it exists as key in $data, otherwise $defKey
	*/
	public static function normKey($key, $data, $defKey = '') {
		return array_key_exists($key, $data) ? $key : $defKey;
	}


	public static function arrVal($arr, $key, $defVal = null) {
		return array_key_exists($key, $arr) ? $arr[$key] : $defVal;
	}

	public static function cartesianProduct($arr1, $arr2) {
		$prod = array();
		foreach ($arr1 as $val1) {
			foreach ($arr2 as $val2) {
				$prod[] = $val1 . $val2;
			}
		}
		return $prod;
	}

}
