<?php namespace App\Util;

class Char {

	private static
		$cyrUppers = 'А Б В Г Д Е Ж З И Й К Л М Н О П Р С Т У Ф Х Ц Ч Ш Щ Ъ Ю Я',
		$cyrLowers = 'а б в г д е ж з и й к л м н о п р с т у ф х ц ч ш щ ъ ю я',
		$cyrlats = [
			'щ' => 'sht', 'ш' => 'sh', 'ю' => 'ju', 'я' => 'ja', 'ч' => 'ch',
			'ц' => 'ts',
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
			'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j',
			'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
			'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
			'ф' => 'f', 'х' => 'h', 'ъ' => 'y', 'ь' => 'x',

			'Щ' => 'Sht', 'Ш' => 'Sh', 'Ю' => 'Ju', 'Я' => 'Ja', 'Ч' => 'Ch',
			'Ц' => 'Ts',
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
			'Е' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'J',
			'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
			'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
			'Ф' => 'F', 'Х' => 'H', 'Ъ' => 'Y', 'Ь' => 'X',

			'„' => ',,', '“' => '"', '«' => '<', '»' => '>',
			' — ' => ' - ', '–' => '-',
			'№' => 'No.', '…' => '...', '’' => '\''
		];

	public static function mystrtolower($s) {
		return str_replace(explode(' ', self::$cyrUppers), explode(' ', self::$cyrLowers), $s);
	}

	/**
	 * @param string $s
	 */
	public static function mystrtoupper($s) {
		return str_replace(explode(' ', self::$cyrLowers), explode(' ', self::$cyrUppers), $s);
	}

	public static function myucfirst($s) {
		$ls = '#'. strtr(self::$cyrLowers, [' ' => ' #']);
		return str_replace(explode(' ', $ls), explode(' ', self::$cyrUppers), '#'.$s);
	}

	public static function cyr2lat($s) {
		return strtr($s, self::$cyrlats);
	}

	public static function lat2cyr($s) {
		return strtr($s, array_flip(self::$latcyrs));
	}

	public static function getCyrUppers($asArray = true) {
		return $asArray ? explode(' ', self::$cyrUppers) : self::$cyrUppers;
	}

	public static function getCyrLowers($asArray = true) {
		return $asArray ? explode(' ', self::$cyrLowers) : self::$cyrLowers;
	}

	/**
	 * Копира някои кирилски букви от местата им според cp866 на местата им
	 * според нестандартното досовско кирилско кодиране MIK.
	 * В крайна сметка въпросните букви ще се намират по два пъти в новополученото
	 * кодиране, което означава, че кирилицата ще се вижда хем при cp866, хем при MIK.
	 * Въобще не прави пълно прекодиране между двете кодови таблици.
	 */
	public static function cp8662mik($s) {
		return strtr($s, [
			chr(0xB0) => chr(0xE0),
			chr(0xB1) => chr(0xE1),
			chr(0xB2) => chr(0xE2),
			chr(0xB3) => chr(0xE3),
			chr(0xB4) => chr(0xE4),
			chr(0xB5) => chr(0xE5),
			chr(0xB6) => chr(0xE6),
			chr(0xB7) => chr(0xE7),
			chr(0xB8) => chr(0xE8),
			chr(0xB9) => chr(0xE9),
			chr(0xBA) => chr(0xEA),
			chr(0xBB) => chr(0xEB),
			chr(0xBC) => chr(0xEC),
			chr(0xBD) => chr(0xED),
			chr(0xBE) => chr(0xEE),
			chr(0xBF) => chr(0xEF)
			]
		);
	}
}
