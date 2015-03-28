<?php namespace App\Util;

class Date {

	private static $months = [
		1 => 'Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни',
		'Юли', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'
	];

	public static function endOfMonth($month) {
		list($y, $m) = explode('-', $month);
		$lastday = $m == 2
			? ($y % 4 ? 28 : ($y % 100 ? 29 : ($y % 400 ? 28 : 29)))
			: (($m - 1) % 7 % 2 ? 30 : 31);

		return "$month-$lastday 23:59:59";
	}

	/**
	 * @param string|\DateTime $isodate
	 * @return string
	 */
	public static function humanDate($isodate = '') {
		$format = 'Y-m-d H:i:s';
		if ( empty($isodate) ) {
			$isodate = date($format);
		} else if ($isodate instanceof \DateTime) {
			$isodate = $isodate->format($format);
		}

		if ( strpos($isodate, ' ') === false ) { // no hours
			$ymd = $isodate;
			$hours = '';
		} else {
			list($ymd, $his) = explode(' ', $isodate);
			list($h, $i) = explode(':', $his);
			$hours = " в $h:$i";
		}

		list($y, $m, $d) = explode('-', $ymd);

		return ltrim($d, '0') .' '. Char::mystrtolower(self::$months[(int) $m]) .' '. $y . $hours;
	}

}
