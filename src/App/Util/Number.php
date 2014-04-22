<?php
namespace App\Util;

class Number
{
	static public function normInt($val, $max, $min = 1)
	{
		if ($val > $max) {
			$val = $max;
		} else if ($val < $min) {
			$val = $min;
		}

		return (int) $val;
	}

	static public function formatNumber($num, $decPl = 2, $decPoint = ',', $tousandDelim = ' ')
	{
		$result = number_format($num, $decPl, $decPoint, $tousandDelim);
		if ($decPoint == ',' && $num < 10000) {
			// bulgarian extra rule: put a $tousandDelim only after 9999
			$result = preg_replace('/^(\d) (\d\d\d)/', '$1$2', $result);
		}

		return $result;
	}
}
