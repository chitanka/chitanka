<?php
namespace Chitanka\LibBundle\Util;

use Chitanka\LibBundle\Legacy\Legacy;

class String
{
	static public function limitLength($str, $len = 80) {
		if ( strlen($str) > $len ) {
			return substr($str, 0, $len - 1) . '…';
		}
		return $str;
	}

	/**
		Escape meta-characters used in regular expressions
	*/
	static public function prepareStringForPreg($string)
	{
		return strtr($string, array(
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
		));
	}


	static private $allowableTags = array('em', 'strong');

	static public function escapeInput($text) {
		$text = self::myhtmlentities($text);
		$repl = array();
		foreach (self::$allowableTags as $allowable) {
			$repl["&lt;$allowable&gt;"] = "<$allowable>";
			$repl["&lt;/$allowable&gt;"] = "</$allowable>";
		}
		$text = strtr($text, $repl);
		return $text;
	}


	static public function myhtmlentities( $text ) {
		return htmlentities( $text, ENT_QUOTES, 'UTF-8');
	}

	static public function myhtmlspecialchars( $text ) {
		return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
	}

	static public function pretifyInput($text) {
		$patterns = array(
			// link in brackets
			'!(?<=[\s>])\((http://[^]\s,<]+)\)!e' => "'(<a href=\"$1\" title=\"'.urldecode('$1').'\">'.urldecode('$1').'</a>)'",
			'!(?<=[\s>])(http://[^]\s,<]+)!e' => "'<a href=\"$1\" title=\"'.urldecode('$1').'\">'.urldecode('$1').'</a>'",
			'/\n([^\n]*)/' => "<p>$1</p>\n",
		);
		$text = preg_replace(array_keys($patterns), array_values($patterns), "\n$text");

		return $text;
	}


	static public function splitPersonName($name)
	{
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);

		if ( ! isset($m[2]) ) {
			return array('firstname' => $name);
		}

		return array(
			'firstname' => $m[1] . (@$m[3]),
			'lastname' => $m[2]
		);
	}

	static public function getMachinePersonName($name)
	{
		$parts = self::splitPersonName($name);
		$machineName = isset($parts['lastname'])
			? $parts['lastname'] . ', ' . $parts['firstname']
			: $parts['firstname'];

		return $machineName;
	}


	static public function slugify($name, $maxlength = 40)
	{
		$name = strtr($name, array(
			'²' => '2', '°' => 'deg',
		));
		$name = Char::cyr2lat($name);
		$name = Legacy::removeDiacritics($name);
		$name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $name);
		$name = strtolower($name);
		$name = preg_replace('/[^a-z\d]/', '-', $name);
		$name = preg_replace('/--+/', '-', $name);
		$name = rtrim(substr($name, 0, $maxlength), '-');

		return $name;
	}


	static public function cb_quotes($matches) {
		return '„'. strtr($matches[1], array('„'=>'«', '“'=>'»', '«'=>'„', '»'=>'“')) .'“';
	}

	static public function my_replace($cont) {
		$chars = array("\r" => '',
			'„' => '"', '“' => '"', '”' => '"', '«' => '"', '»' => '"', '&quot;' => '"',
			'&bdquo;' => '"', '&ldquo;' => '"', '&rdquo;' => '"', '&laquo;' => '"',
			'&raquo;' => '"', '&#132;' => '"', '&#147;' => '"', '&#148;' => '"',
			'&lt;' => '&amp;lt;', '&gt;' => '&amp;gt;', '&nbsp;' => '&amp;nbsp;',
			"'" => '’', '...' => '…',
			'</p>' => '', '</P>' => '',
			#"\n     " => "<p>", "\n" => ' ',
			'<p>' => "\n\t", '<P>' => "\n\t",
		);
		$reg_chars = array(
			'/(\s|&nbsp;)(-|–|­){1,2}(\s)/' => '$1—$3', # mdash
			'/([\s(][\d,.]*)-([\d,.]+[\s)])/' => '$1–$2', # ndash между цифри
			'/(\d)x(\d)/' => '$1×$2', # знак за умножение
			'/\n +/' => "\n\t", # абзаци
			'/(?<!\n)\n\t\* \* \*\n(?!\n)/' => "\n\n\t* * *\n\n",
		);

		$cont = preg_replace('/([\s(]\d+ *)-( *\d+[\s),.])/', '$1–$2', "\n".$cont);
		$cont = str_replace(array_keys($chars), array_values($chars), $cont);
		#$cont = html_entity_decode($cont, ENT_NOQUOTES, 'UTF-8');
		$cont = preg_replace(array_keys($reg_chars), array_values($reg_chars), $cont);

		# кавички
		$qreg = '/(?<=[([\s|\'"_\->\/])"(\S?|\S[^"]*[^\s"([])"/m';
		#$cont = preg_replace($qreg, '„$1“', $cont);
		$i = 0;
		$maxIters = 6;
		while ( strpos($cont, '"') !== false ) {
			if ( ++$i > $maxIters ) {
				self::log_error("ВЕРОЯТНА ГРЕШКА: Повече от $maxIters итерации при вътрешните кавички.");
				break;
			}
			$cont = preg_replace_callback($qreg, 'Chitanka\LibBundle\Util\String::cb_quotes', $cont);
		}

		return ltrim($cont, "\n");
	}


	static private function log_error($s, $loud = false) {
		#file_put_contents('./log/error', date('d-m-Y H:i:s'). "  $s\n", FILE_APPEND);
		if ($loud) { echo $s."\n"; }
	}
}
