<?php namespace App\Util;

class Datetime {
	static public function endOfMonth($month) {
		list($y, $m) = explode('-', $month);
		$lastday = $m == 2
			? ($y % 4 ? 28 : ($y % 100 ? 29 : ($y % 400 ? 28 : 29)))
			: (($m - 1) % 7 % 2 ? 30 : 31);

		return "$month-$lastday 23:59:59";
	}

}
