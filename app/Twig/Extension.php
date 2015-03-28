<?php namespace App\Twig;

use App\Entity\Text;
use App\Service\ContentService;
use App\Util\Number;
use App\Util\String;
use Sfblib\XmlElement;

class Extension extends \Twig_Extension {

	/** {@inheritdoc} */
	public function getName() {
		return 'chitanka';
	}

	/** {@inheritdoc} */
	public function getFunctions() {
		return [
			new \Twig_SimpleFunction('anchor_name', [$this, 'getAnchorName']),
			new \Twig_SimpleFunction('cover', [$this, 'getCover']),
		];
	}

	/** {@inheritdoc} */
	public function getFilters() {
		return [
			new \Twig_SimpleFilter('rating_class', [$this, 'getRatingClass']),
			new \Twig_SimpleFilter('rating_format', [$this, 'formatRating']),
			new \Twig_SimpleFilter('name_format', [$this, 'formatPersonName']),
			new \Twig_SimpleFilter('acronym', 'App\Util\String::createAcronym'),
			new \Twig_SimpleFilter('first_char', [$this, 'getFirstChar']),
			new \Twig_SimpleFilter('email', [$this, 'obfuscateEmail']),
			new \Twig_SimpleFilter('doctitle', [$this, 'getDocTitle']),
			new \Twig_SimpleFilter('lower', [$this, 'strtolower']),
			new \Twig_SimpleFilter('json', 'json_encode'),
			new \Twig_SimpleFilter('repeat', [$this, 'repeatString']),
			new \Twig_SimpleFilter('join_lists', [$this, 'joinLists']),
			new \Twig_SimpleFilter('humandate', 'App\Util\Date::humanDate'),
			new \Twig_SimpleFilter('nl2br', [$this, 'nl2br'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
			new \Twig_SimpleFilter('dot2br', [$this, 'dot2br']),
			new \Twig_SimpleFilter('user_markup', [$this, 'formatUserMarkup']),
			new \Twig_SimpleFilter('striptags', 'strip_tags'),
			new \Twig_SimpleFilter('domain', [$this, 'getDomain']),
			new \Twig_SimpleFilter('link', [$this, 'formatLinks']),
			new \Twig_SimpleFilter('encoding', [$this, 'changeEncoding']),
			new \Twig_SimpleFilter('urlencode', [$this, 'getUrlEncode']),
			new \Twig_SimpleFilter('qrcode', [$this, 'getQrCode']),
			new \Twig_SimpleFilter('put_text_in_template', [$this, 'putTextInBookTemplate']),
		];
	}

	/** {@inheritdoc} */
	public function getTests() {
		return [
			new \Twig_SimpleTest('url', [$this, 'isUrl']),
		];
	}

	/**
	 * @param int $rating
	 * @return string
	 */
	public function getRatingClass($rating) {
		if ( $rating >= 5.6 ) return 'degree-360 gt-half';
		if ( $rating >= 5.2 ) return 'degree-330 gt-half';
		if ( $rating >= 4.8 ) return 'degree-300 gt-half';
		if ( $rating >= 4.4 ) return 'degree-270 gt-half';
		if ( $rating >= 4.0 ) return 'degree-240 gt-half';
		if ( $rating >= 3.6 ) return 'degree-210 gt-half';
		if ( $rating >= 3.2 ) return 'degree-180';
		if ( $rating >= 2.8 ) return 'degree-150';
		if ( $rating >= 2.4 ) return 'degree-120';
		if ( $rating >= 2.0 ) return 'degree-90';
		if ( $rating >= 1.5 ) return 'degree-60';
		if ( $rating >= 1.0 ) return 'degree-30';
		return '';
	}

	/**
	 * @param int $rating
	 * @return int
	 */
	public function formatRating($rating) {
		return Number::rmTrailingZeros(Number::formatNumber($rating, 1));
	}

	/**
	 * @param string $name
	 * @param string $sortby
	 * @return string
	 */
	public function formatPersonName($name, $sortby = 'first-name') {
		if (empty($name)) {
			return $name;
		}
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		if ( ! isset($m[2]) ) {
			return $name;
		}
		$last = "<span class=\"lastname\">$m[2]</span>";
		$m3 = isset($m[3]) ? $m[3] : '';

		return $sortby == 'last-name' ? $last.', '.$m[1].$m3 : $m[1].' '.$last.$m3;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function getFirstChar($string) {
		return mb_substr($string, 0, 1, 'UTF-8');
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function strtolower($string) {
		return mb_strtolower($string, 'UTF-8');
	}

	/**
	 * @param string $email
	 * @return string
	 */
	public function obfuscateEmail($email) {
		return strtr($email, ['@' => '&#160;<span title="при сървъра">(при)</span>&#160;']);
	}

	/**
	 * @param string $title
	 * @return string
	 */
	public function getDocTitle($title) {
		$title = preg_replace('/\s\s+/', ' ', $title);
		$title = strtr($title, [
			'<br>' => ' — ',
			'&amp;' => '&', // will be escaped afterwards by Twig
		]);
		$title = trim(strip_tags($title));

		return $title;
	}

	/**
	 * @param string $string
	 * @param int $count
	 * @return string
	 */
	public function repeatString($string, $count) {
		return str_repeat($string, $count);
	}

	/**
	 *
	 * @param string $template
	 * @param Text $text
	 * @param string $htmlTextView
	 * @return string
	 */
	public function putTextInBookTemplate($template, Text $text, $htmlTextView) {
		$textId = $text->getId();
		$regexp = "/\{(text|file):$textId(-[^|}]+)?\|(.+)\}/";
		if (preg_match($regexp, $template, $matches)) {
			$template = preg_replace($regexp, str_replace('TEXT_TITLE', $matches[3], $htmlTextView), $template);
		}
		$template = preg_replace("/\{(text|file):$textId(-.+)?\}/", str_replace('TEXT_TITLE', $text->getTitle(), $htmlTextView), $template);

		return $template;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function joinLists($string) {
		return preg_replace('|</ul>\n<ul[^>]*>|', "\n", $string);
	}

	private $_xmlElementCreator = null;

	/**
	 * Generate an anchor name for a given string.
	 *
	 * @param string $text  A string
	 * @param bool $unique  Always generate a unique name
	 *                      (consider all previously generated names)
	 */
	public function getAnchorName($text, $unique = true) {
		if (is_null($this->_xmlElementCreator)) {
			$this->_xmlElementCreator = new XmlElement;
		}

		return $this->_xmlElementCreator->getAnchorName($text, $unique);
	}

	/**
	 * @param int $id
	 * @param int $width
	 * @param string $format
	 * @return string
	 */
	public function getCover($id, $width = 200, $format = 'jpg') {
		return ContentService::getContentFilePath('book-cover', $id) . ".$width.$format";
	}

	/**
	 * @param string $value
	 * @param string $sep
	 * @return string
	 */
	public function nl2br($value, $sep = '<br>') {
		return str_replace("\n", $sep."\n", $value);
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public function dot2br($value) {
		return preg_replace('/\. (?=[A-ZА-Я])/u', "<br>\n", $value);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public function formatUserMarkup($content) {
		return String::prettifyInput(String::escapeInput($content));
	}

	/**
	 * @param string $string
	 * @param string $encoding
	 * @return string
	 */
	public function changeEncoding($string, $encoding) {
		return iconv('UTF-8', $encoding, $string);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function getUrlEncode($string) {
		return urlencode($string);
	}

	/**
	 * @param string $url
	 * @param int $width
	 * @return string
	 */
	public function getQrCode($url, $width = 100) {
		return "http://chart.apis.google.com/chart?cht=qr&chs={$width}x{$width}&chld=H|0&chl=". urlencode($url);
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public function getDomain($url) {
		return parse_url($url, PHP_URL_HOST);
	}

	// TODO unit test

	/**
	 * @param string $text
	 * @return string
	 */
	public function formatLinks($text) {
		$patterns = [
			'/\[\[(.+)\|(.+)\]\]/Us' => '<a href="$1">$2</a>',
			'|(?<!")https?://\S+[^,.\s]|e' => "'<a href=\"$0\">'.\$this->getDomain('$0', '$2').'</a>'",
		];
		return preg_replace(array_keys($patterns), array_values($patterns), $text);
	}

	/**
	 * @param string $string
	 * @return bool
	 */
	public function isUrl($string) {
		return strpos($string, 'http') === 0;
	}
}
