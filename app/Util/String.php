<?php namespace App\Util;

class String {

	/**
	 * Truncate a string to a given length
	 * @param string $str
	 * @param int $len
	 * @return string
	 */
	public static function limitLength($str, $len = 80) {
		if ( strlen($str) > $len ) {
			return substr($str, 0, $len - 1) . '…';
		}
		return $str;
	}

	/**
	 * Escape meta-characters used in regular expressions
	 * @param string $string
	 * @return string
	 */
	public static function prepareStringForPreg($string) {
		return strtr($string, [
			// in a regexp a backslash can be escaped with four backslashes - \\\\
			'\\' => '\\\\\\\\',
			'^' => '\^',
			'$' => '\$',
			'.' => '\.',
			'[' => '\[',
			']' => '\]',
			'|' => '\|',
			'(' => '\(',
			')' => '\)',
			'?' => '\?',
			'*' => '\*',
			'+' => '\+',
			'{' => '\{',
			'}' => '\}',
			'-' => '\-',
		]);
	}

	private static $allowableTags = ['em', 'strong'];

	/**
	 * @param string $text
	 * @return string
	 */
	public static function escapeInput($text) {
		$text = self::myhtmlentities($text);
		$repl = [];
		foreach (self::$allowableTags as $allowable) {
			$repl["&lt;$allowable&gt;"] = "<$allowable>";
			$repl["&lt;/$allowable&gt;"] = "</$allowable>";
		}
		$text = strtr($text, $repl);
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function myhtmlentities( $text ) {
		return htmlentities( $text, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function myhtmlspecialchars( $text ) {
		return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public static function fixEncoding($string) {
		if ('UTF-8' != ($enc = mb_detect_encoding($string, 'UTF-8, Windows-1251'))) {
			$string = iconv($enc, 'UTF-8', $string);
		}
		return $string;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function prettifyInput($text) {
		$patterns = [
			'/\n([^\n]*)/' => "<p>$1</p>\n",
			'!\[url=([^]]+)\]([^]]+)\[/url\]!' => '<a href="$1">$2</a>',
		];
		$patternsWithCallbacks = [
			// link in brackets
			'!(?<=[\s>])\((http://[^]\s,<]+)\)!' => function($m) {
				return '(<a href="'.$m[1].'" title="'.urldecode($m[1]).'">'.urldecode($m[1]).'</a>)';
			},
			'!(?<=[\s>])(http://[^]\s,<]+)!' => function($m) {
				return '<a href="'.$m[1].'" title="'.urldecode($m[1]).'">'.urldecode($m[1]).'</a>';
			},
		];
		$text = "\n$text";
		foreach ($patternsWithCallbacks as $pattern => $callback) {
			$text = preg_replace_callback($pattern, $callback, $text);
		}
		$text = preg_replace(array_keys($patterns), array_values($patterns), $text);

		return $text;
	}

	/**
	 * @param string $name
	 * @return array
	 */
	public static function splitPersonName($name) {
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);

		if ( ! isset($m[2]) ) {
			return ['firstname' => $name];
		}

		return [
			'firstname' => $m[1] . (@$m[3]),
			'lastname' => $m[2]
		];
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public static function getMachinePersonName($name) {
		$parts = self::splitPersonName($name);
		$machineName = isset($parts['lastname'])
			? $parts['lastname'] . ', ' . $parts['firstname']
			: $parts['firstname'];

		return $machineName;
	}

	/**
	 * Create a slug from a given string
	 * @param string $name
	 * @param int $maxlength
	 * @return string
	 */
	public static function slugify($name, $maxlength = 60) {
		$name = strtr($name, [
			'²' => '2', '°' => 'deg',
		]);
		$name = Char::cyr2lat($name);
		$name = self::removeDiacritics($name);
		$name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $name);
		$name = strtolower($name);
		$name = preg_replace('/[^a-z\d]/', '-', $name);
		$name = preg_replace('/--+/', '-', $name);
		$name = rtrim(substr($name, 0, $maxlength), '-');

		return $name;
	}

	/**
	 * Remove diacritic characters from a latin string
	 * Never run this function on a string with cyrillic letters: they all get converted to "Y".
	 * @param string $string
	 * @return string
	 */
	public static function removeDiacritics($string) {
		return strtr(utf8_decode($string),
			utf8_decode(
			'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
			'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
	}

	/**
	 * Replace some characters and generally prettify content
	 * @param string $cont
	 * @return string
	 */
	public static function my_replace($cont) {
		$chars = ["\r" => '',
			'„' => '"', '“' => '"', '”' => '"', '«' => '"', '»' => '"', '&quot;' => '"',
			'&bdquo;' => '"', '&ldquo;' => '"', '&rdquo;' => '"', '&laquo;' => '"',
			'&raquo;' => '"', '&#132;' => '"', '&#147;' => '"', '&#148;' => '"',
			'&lt;' => '&amp;lt;', '&gt;' => '&amp;gt;', '&nbsp;' => '&amp;nbsp;',
			"'" => '’', '...' => '…',
			'</p>' => '', '</P>' => '',
			'<p>' => "\n\t", '<P>' => "\n\t",
		];
		$reg_chars = [
			'/(\s|&nbsp;)(-|–|­){1,2}(\s)/' => '$1—$3', # mdash
			'/([\s(][\d,.]*)-([\d,.]+[\s)])/' => '$1–$2', # ndash между цифри
			'/(\d)x(\d)/' => '$1×$2', # знак за умножение
			'/\n +/' => "\n\t", # абзаци
			'/(?<!\n)\n\t\* \* \*\n(?!\n)/' => "\n\n\t* * *\n\n",
		];

		$cont = preg_replace('/([\s(]\d+ *)-( *\d+[\s),.])/', '$1–$2', "\n".$cont);
		$cont = str_replace(array_keys($chars), array_values($chars), $cont);
		$cont = preg_replace(array_keys($reg_chars), array_values($reg_chars), $cont);

		# кавички
		$qreg = '/(?<=[([\s|\'"_\->\/])"(\S?|\S[^"]*[^\s"([])"/m';
		$i = 0;
		$maxIters = 6;
		while ( strpos($cont, '"') !== false ) {
			if ( ++$i > $maxIters ) {
				self::log_error("ВЕРОЯТНА ГРЕШКА: Повече от $maxIters итерации при вътрешните кавички.");
				break;
			}
			$cont = preg_replace_callback($qreg, function($matches) {
				return '„'. strtr($matches[1], ['„'=>'«', '“'=>'»', '«'=>'„', '»'=>'“']) .'“';
			}, $cont);
		}

		return ltrim($cont, "\n");
	}

	/**
	 * Create an acronym from a given name
	 * @param string $name
	 * @return string
	 */
	public static function createAcronym($name) {
		if (preg_match_all('/ ([a-zA-Zа-яА-Я\d])/u', ' '.$name, $matches)) {
			$acronym = implode('', $matches[1]);
			return Char::mystrtoupper($acronym);
		}
		return null;
	}

	private static function log_error($s, $loud = false) {
		if ($loud) {
			echo $s."\n";
		}
	}
}
