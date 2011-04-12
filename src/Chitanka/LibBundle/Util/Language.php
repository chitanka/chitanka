<?php
namespace Chitanka\LibBundle\Util;

class Language
{
	private static $langs = array(
		'' => '(Неизвестен)',
		'sq' => 'Албански',
		'en' => 'Английски',
		'ar' => 'Арабски',
		'hy' => 'Арменски',
		'bg' => 'Български',
		'el' => 'Гръцки',
		'da' => 'Датски',
		'he' => 'Иврит',
		'es' => 'Испански',
		'it' => 'Италиански',
		'zh' => 'Китайски',
		'ko' => 'Корейски',
		'de' => 'Немски',
		'no' => 'Норвежки',
		'fa' => 'Персийски',
		'pl' => 'Полски',
		'pt' => 'Португалски',
		'ro' => 'Румънски',
		'ru' => 'Руски',
		'sa' => 'Санскрит',
		'sk' => 'Словашки',
		'sl' => 'Словенски',
		'sr' => 'Сръбски',
		'chu' => 'Старобългарски',
		'grc' => 'Старогръцки',
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
	);

	static public function getLangs()
	{
		return self::$langs;
	}

	/** TODO remove */
	static public function langName($code, $asUpper = true) 
	{
		if ( !array_key_exists($code, self::$langs) ) return '';
		$name = self::$langs[$code];
		return $asUpper ? $name : Char::mystrtolower($name);
	}
}
