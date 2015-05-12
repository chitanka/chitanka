<?php namespace App\Util;

class Language {
	private static $langs = [
		'' => '(Неизвестен)',
		'sq' => 'Албански',
		'en' => 'Английски',
		'ar' => 'Арабски',
		'hy' => 'Арменски',
		'be' => 'Белоруски',
		'bg' => 'Български',
		'byz' => 'Византийски',
		'el' => 'Гръцки',
		'da' => 'Датски',
		'he' => 'Иврит',
		'is' => 'Исландски',
		'es' => 'Испански',
		'it' => 'Италиански',
		'zh' => 'Китайски',
		'ko' => 'Корейски',
		'la' => 'Латински',
		'de' => 'Немски',
		'no' => 'Норвежки',
		'oc' => 'Окситански',
		'fa' => 'Персийски',
		'pl' => 'Полски',
		'pt' => 'Португалски',
		'ro' => 'Румънски',
		'ru' => 'Руски',
		'sa' => 'Санскрит',
		'sk' => 'Словашки',
		'sl' => 'Словенски',
		'gmh' => 'Средновисоконемски',
		'sr' => 'Сръбски',
		'chu' => 'Старобългарски',
		'grc' => 'Старогръцки',
		'iso' => 'Староисландски',
		'fro' => 'Старофренски',
		'hr' => 'Хърватски',
		'tr' => 'Турски',
		'hu' => 'Унгарски',
		'fi' => 'Фински',
		'fr' => 'Френски',
		'hi' => 'Хинди',
		'nl' => 'Холандски',
		'cs' => 'Чешки',
		'sv' => 'Шведски',
		'jp' => 'Японски',
		'mul' => '(Многоезично)',
	];

	public static function getLangs() {
		return self::$langs;
	}

	/** TODO remove */
	public static function langName($code, $asUpper = true) {
		if ( !array_key_exists($code, self::$langs) ) return '';
		$name = self::$langs[$code];
		return $asUpper ? $name : Char::mystrtolower($name);
	}
}
