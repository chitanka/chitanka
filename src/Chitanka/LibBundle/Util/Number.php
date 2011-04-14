<?php
namespace Chitanka\LibBundle\Util;

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

	static public function formatNumber($num, $decPl = 2, $decPoint = ',', $tousandDelim = ' ') {
		return number_format($num, $decPl, $decPoint, $tousandDelim);
	}
}
