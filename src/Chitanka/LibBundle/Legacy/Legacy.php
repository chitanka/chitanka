<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Util\Ary;

class Legacy
{

	private static
		$months = array(
			1 => 'Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни',
			'Юли', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'
		);



	private static $types = array(
		// code => array(singular, plural, sing. article, pl. article)
		'anecdote' => array('Анекдот', 'Анекдоти', 'анекдота', 'анекдотите'),
		'fable' => array('Басня', 'Басни', 'баснята', 'басните'),
		'biography' => array('Биография', 'Биографии', 'биографията', 'биографиите'),
		'docu' => array('Документалистика', 'Документалистика', 'книгата', 'книгите'),
		'essay' => array('Есе', 'Есета', 'есето', 'есетата'),
		'interview' => array('Интервю', 'Интервюта', 'интервюто', 'интервютата'),
		'gamebook' => array('Книга игра', 'Книги игри', 'книгата игра', 'книгите игри'),
		'comedy' => array('Комедия', 'Комедии', 'комедията', 'комедиите'),
		'memo' => array('Мемоари/спомени', 'Мемоари/спомени', 'творбата', 'творбите'),
		'science' => array('Научно', 'Научни', 'научната творба', 'научните творби'),
		'novelette' => array('Новела', 'Новели', 'новелата', 'новелите'),
		'ocherk' => array('Очерк', 'Очерци', 'очерка', 'очерците'),
		'shortstory' => array('Разказ', 'Разкази', 'разказа', 'разказите'),
		'review' => array('Рецензия', 'Рецензии', 'рецензията', 'рецензиите'),
		'novel' => array('Роман', 'Романи', 'романа', 'романите'),
		#'parable' => array('Парабола', 'Параболи', 'параболата', 'параболите'),
		'play' => array('Пиеса', 'Пиеси', 'пиесата', 'пиесите'),
		'letter' => array('Писмо', 'Писма', 'писмото', 'писмата'),
		'poetry' => array('Поезия', 'Поезия', 'поетичната творба', 'поетичните творби'),
		'poem' => array('Поема', 'Поеми', 'поемата', 'поемите'),
		'novella' => array('Повест', 'Повести', 'повестта', 'повестите'),
		'outro' => array('Послеслов', 'Послеслови', 'послеслова', 'послесловите'),
		'intro' => array('Предговор', 'Предговори', 'предговора', 'предговорите'),
		'tale' => array('Приказка', 'Приказки', 'приказката', 'приказките'),
		'pritcha' => array('Притча', 'Притчи', 'притчата', 'притчите'),
		'travelnotes' => array('Пътепис', 'Пътеписи', 'пътеписа', 'пътеписите'),
		'speech' => array('Реч', 'Речи', 'речта', 'речите'),
		'article' => array('Статия', 'Статии', 'статията', 'статиите'),
		'prosepoetry' => array('Лирика в проза', 'Лирика в проза', 'стихотворението', 'стихотворенията'),
		'screenplay' => array('Сценарий', 'Сценарии', 'сценария', 'сценариите'),
		'tragedy' => array('Трагедия', 'Трагедии', 'трагедията', 'трагедиите'),
		'textbook' => array('Учебник', 'Учебници', 'учебника', 'учебниците'),
		'feuilleton' => array('Фейлетон', 'Фейлетони', 'фейлетона', 'фейлетоните'),
		'haiku' => array('Хайку', 'Хайку', 'поетичната творба', 'поетичните творби'),
		'other' => array('Разни', 'Разни', 'творбата', 'творбите'),
	);

	public static function workType($code, $singular = true) {
		if ( !array_key_exists($code, self::$types) ) return '';
		return $singular ? self::$types[$code][0] : self::$types[$code][1];
	}

	public static function workTypeArticle($code, $singular = true) {
		if ( !array_key_exists($code, self::$types) ) return '';
		return $singular ? self::$types[$code][2] : self::$types[$code][3];
	}

	public static function workTypes($singular = true) {
		$ntypes = array();
		foreach (self::$types as $code => $name) {
			$ntypes[$code] = $singular ? self::$types[$code][0] : self::$types[$code][1];
		}
		return $ntypes;
	}

	private static $picTypes = array(
		'magazine' => 'Списание'
	);
	public static function picType($code) {
		if ( !array_key_exists($code, self::$picTypes) ) return '';
		return self::$picTypes[$code];
	}


	private static $seriesTypes = array(
		// code => array(singular, plural, sing. article, pl. article)
		'newspaper' => array('вестник', 'вестници', 'вестника', 'вестниците'),
		'series' => array('поредица', 'поредици', 'поредицата', 'поредиците'),
		'collection' => array('сборник', 'сборници', 'сборника', 'сборниците'),
		'poetry' => array('стихосбирка', 'стихосбирки', 'стихосбирката', 'стихосбирките'),
	);

	private static $pseudoSeries = array('collection', 'poetry');

	public static function seriesSuffix($code) {
		return $code == 'series' || empty(self::$seriesTypes[$code][0])
			? ''
			: ' ('. self::$seriesTypes[$code][0] .')';
	}

	public static function seriesType($code, $singular = true) {
		if ( !array_key_exists($code, self::$seriesTypes) ) return '';
		return $singular ? self::$seriesTypes[$code][0] : self::$seriesTypes[$code][1];
	}

	public static function seriesTypeArticle($code, $singular = true) {
		if ( !array_key_exists($code, self::$seriesTypes) ) return '';
		return $singular ? self::$seriesTypes[$code][2] : self::$seriesTypes[$code][3];
	}


	public static function isPseudoSeries($type) {
		return in_array($type, self::$pseudoSeries);
	}


	public static function humanDate($isodate = '')
	{
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
			list($h, $i, $s) = explode(':', $his);
			$hours = " в $h:$i";
		}

		list($y, $m, $d) = explode('-', $ymd);

		return ltrim($d, '0') .' '. Char::mystrtolower(self::monthName($m)) .' '. $y . $hours;
	}


	public static function fillOnEmpty(&$var, $value) {
		if ( empty($var) ) {
			$var = $value;
		}
	}

	public static function fillOnNull(&$var, $value) {
		if ( is_null($var) ) {
			$var = $value;
		}
	}

	public static function monthName($m, $asUpper = true) {
		$name = self::$months[(int)$m];

		return $asUpper ? $name : Char::mystrtolower($name);
	}

	public static function header_encode($header)
	{
		return '=?utf-8?B?'.base64_encode($header).'?=';
	}


	/**
	* @param $val Value
	* @param $data Associative array
	* @param $defVal Default value
	* @return $val if it exists in $data, otherwise $defVal
	*/
	public static function normVal($val, $data, $defVal = null) {
		self::fillOnNull($defVal, @$data[0]);
		return in_array($val, $data) ? $val : $defVal;
	}


	private static $regPatterns = array(
		'/\[\[(.+)\|(.+)\]\]/Us' => '<a href="$1" title="$1 — $2">$2</a>',
		'#(?<=[\s>])(\w+://[^])\s"<]+)([^])\s"<,.;!?])#' => '<a href="$1$2" title="$1$2">$1$2</a>',
	);
	public static function wiki2html($s) {
		$s = preg_replace(array_keys(self::$regPatterns), array_values(self::$regPatterns), $s);

		return $s;
	}


	private static $templates = array(
		'{SITENAME}' => '{SITENAME}',
	);

	public static function expandTemplates($s) {
		return strtr($s, self::$templates);
	}
	public static function addTemplate($key, $val) {
		self::$templates['{'.$key.'}'] = $val;
	}


	/**
	* Never run this function on a string with cyrillic letters: they all get converted to "Y"
	*/
	public static function removeDiacritics($s) {
		return strtr(utf8_decode($s),
			utf8_decode(
			'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
			'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
	}


	/** bytes to kibibytes */
	public static function int_b2k($bytes) {
		$k = $bytes >> 10; // divide by 2^10 w/o rest
		return $k > 0 ? $k : 1;
	}
	/** bytes to mebibytes */
	public static function int_b2m($bytes) {
		$m = $bytes >> 20; // divide by 2^20 w/o rest
		return $m > 0 ? $m : 1;
	}

	/** bytes to gibibytes */
	public static function int_b2g($bytes) {
		$m = $bytes >> 30; // divide by 2^30 w/o rest
		return $m > 0 ? $m : 1;
	}

	/** bytes to human readable */
	public static function int_b2h($bytes) {
		if ( $bytes < ( 1 << 10 ) ) return $bytes . ' B';
		if ( $bytes < ( 1 << 20 ) ) return self::int_b2k( $bytes ) . ' KiB';
		if ( $bytes < ( 1 << 30 ) ) return self::int_b2m( $bytes ) . ' MiB';
		return self::int_b2g( $bytes ) . ' GiB';
	}

	/**
		Convert a php.ini value to an integer
		(copied from php.net)
	*/
	public static function ini_bytes($val) {
		$val = trim($val);
		$last = strtolower($val{strlen($val)-1});
		switch ($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/**
		Removes trailing zeros after the decimal sign
	*/
	public static function rmTrailingZeros($number, $decPoint = ',') {
		$number = rtrim($number, '0');
		$number = rtrim($number, $decPoint); // remove the point too
		return $number;
	}


	public static function getMaxUploadSizeInMiB() {
		return self::int_b2m( self::ini_bytes( ini_get('upload_max_filesize') ) );
	}


	public static function chooseGrammNumber($num, $sing, $plur, $null = '') {
		settype($num, 'int');
		if ($num > 1) {
			return $plur;
		} else if ($num == 1) {
			return $sing;
		} else {
			return empty($null) ? $plur : $null;
		}
	}


	public static function isUrl($string)
	{
		return strpos($string, 'http://') === 0;
	}


	public static function getAcronym($words) {
		$acronym = '';
		$words = preg_replace('/[^a-zA-Z\d ]/', '', $words);
		foreach ( explode(' ', $words) as $word ) {
			$acronym .= empty($word) ? '' : $word[0];
		}
		return strtoupper($acronym);
	}


	public static function extract2object($assocArray, &$object) {
		foreach ( (array) $assocArray as $key => $val ) {
			if ( ctype_alnum($key[0]) ) {
				$object->$key = $val;
			}
		}
	}


	private static $contentDirs = array(
		'text' => 'content/text/',
		'text-info' => 'content/text-info/',
		'text-anno' => 'content/text-anno/',
		'user' => 'content/user/',
		'sandbox' => 'content/user/sand/',
		'info' => 'content/info/',
		'img' => 'content/img/',
		'cover' => 'content/cover/',
		'book' => 'content/book/',
		'book-anno' => 'content/book-anno/',
		'book-info' => 'content/book-info/',
		'book-img' => 'content/book-img/',
		'book-cover' => 'thumb/book-cover/',
		'pic' => 'content/pic/',
	);

	public static function getContentFile($key, $num) {
		$file = __DIR__ .'/../../../../web/'. self::getContentFilePath($key, $num);
		if ( file_exists($file) ) {
			return file_get_contents($file);
		}

		return false;
	}

	public static function getContentFilePath($key, $num, $full = true) {
		$pref = Ary::arrVal(self::$contentDirs, $key, $key .'/');
		return $pref . self::makeContentFilePath($num, $full);
	}

	// use this for sfbzip too
	public static function makeContentFilePath($num, $full = true) {
		$realnum = $num;
		$num = (int) $num;
		$word = 4; // a word is four bytes long
		$bin_in_hex = 4; // one hex character corresponds to four binary digits
		$path = str_repeat('+/', $num >> ($word * $bin_in_hex));
		$hex = str_pad(dechex($num), $word, '0', STR_PAD_LEFT);
		$hex = substr($hex, -$word); // take last $word characters
		$path .= substr($hex, 0, 2) . '/';
		if ($full) {
			$path .= $realnum;
		}

		return $path;
	}


	/** Handles only JPEG */
	public static function genThumbnail($filename, $width = 250)
	{
		if ( ! preg_match('/\.jpe?g$/', $filename) ) {
			return $filename;
		}

		list($width_orig, $height_orig) = getimagesize($filename);
		if ($width_orig < $width) {
			return $filename;
		}

		$height = $width * $height_orig / $width_orig;

		$image_p = imagecreatetruecolor($width, $height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

		$temp = tempnam(Setup::setting('tmp_dir'), 'thumb-') . basename($filename);
		imagejpeg($image_p, $temp, 80);

		return $temp;
	}



	public static function getFromUrl($url, $postData = array())
	{
		$ch = curl_init();

		$options = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,    // return content
			CURLOPT_HEADER         => false,   // don't return headers
			CURLOPT_CONNECTTIMEOUT => 10,      // timeout on connect
			CURLOPT_TIMEOUT        => 10,      // timeout on response
			CURLOPT_USERAGENT      => 'Mylib (http://chitanka.info)',
		);
		if ( ! empty($postData)) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $postData;
		}

		curl_setopt_array($ch, $options);
		$contents = curl_exec($ch);

		curl_close($ch);

		return $contents;
	}


	public static function getFromUrlOrCache($url, $cacheTime = 0)
	{
		$id = md5($url);
		$action = 'url';

		if ( $cacheTime && CacheManager::cacheExists($action, $id, $cacheTime) ) {
			return CacheManager::getCache($action, $id);
		}

		$content = self::getFromUrl($url);
		if ( empty($content) ) {
			return '';
		}

		return CacheManager::setCache($action, $id, $content);
	}


	public static function getMwContent($url)
	{
		$id = md5($url);
		$action = 'info';

		if ( CacheManager::cacheExists($action, $id, $days = 7) ) {
			return CacheManager::getCache($action, $id);
		}

		$content = self::getFromUrl("$url?action=render");
		if ( empty($content) ) {
			return '';
		}
		$content = self::processMwContent($content, $url);

		return CacheManager::setCache($action, $id, $content);
	}


	protected static function processMwContent($content, $url)
	{
		$up = parse_url($url);
		$server = "$up[scheme]://$up[host]";
		$content = strtr($content, array(
			'&nbsp;' => '&#160;',
			' href="/wiki/' => ' href="'.$server.'/wiki/',
		));
		$patterns = array(
			'/rel="[^"]+"/' => '',
			// images
			'| src="/|' => " src=\"$server/",
		);
		$content = preg_replace(array_keys($patterns), array_values($patterns), $content);

		$content = sprintf('<div class="editsection">[<a href="%s?action=edit" title="Редактиране на статията">±</a>]</div>', $url) . $content;

		return $content;
	}


	/**
		Validates an e-mail address.
		Regexps are taken from http://www.iki.fi/markus.sipila/pub/emailvalidator.php
		(author: Markus Sipilä, version: 1.0, 2006-08-02)

		@param string $input E-mail address to be validated
		@return int 1 if valid, 0 if not valid, -1 if valid but strange
	*/
	public static function validateEmailAddress($input, $allowEmpty = true) {
		if ( empty($input) ) {
			return $allowEmpty ? 1 : 0;
		}
		$ct = '[a-zA-Z0-9-]';
		$cn = '[a-zA-Z0-9_+-]';
		$cr = '[a-zA-Z0-9,!#$%&\'\*+\/=?^_`{|}~-]';
		$normal = "/^$cn+(\.$cn+)*@$ct+(\.$ct+)*\.([a-z]{2,4})$/";
		$rare   = "/^$cr+(\.$cr+)*@$ct+(\.$ct+)*\.([a-z]{2,})$/";
		if ( preg_match($normal, $input) ) { return 1; }
		if ( preg_match($rare, $input) ) { return -1; }
		return 0;
	}


	public static function sha1_loop($pass, $loops = 1) {
		for ($i=0; $i < $loops; $i++) {
			$pass = sha1($pass);
		}

		return $pass;
	}

}
