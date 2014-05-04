<?php namespace App\Twig;

use App\Entity\Text;
use App\Util\Number;
use App\Util\Char;
use App\Util\String;
use App\Legacy\Legacy;
use Sfblib\XmlElement;

class Extension extends \Twig_Extension {

	public function getName() {
		return 'chitanka';
	}

	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction('anchor_name', array($this, 'getAnchorName')),
			new \Twig_SimpleFunction('cover', array($this, 'getCover')),
		);
	}

	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('rating_class', array($this, 'getRatingClass')),
			new \Twig_SimpleFilter('rating_format', array($this, 'formatRating')),
			new \Twig_SimpleFilter('name_format', array($this, 'formatPersonName')),
			new \Twig_SimpleFilter('acronym', array($this, 'getAcronym')),
			new \Twig_SimpleFilter('first_char', array($this, 'getFirstChar')),
			new \Twig_SimpleFilter('email', array($this, 'obfuscateEmail')),
			new \Twig_SimpleFilter('doctitle', array($this, 'getDocTitle')),
			new \Twig_SimpleFilter('lower', array($this, 'strtolower')),
			new \Twig_SimpleFilter('json', array($this, 'getJson')),
			new \Twig_SimpleFilter('repeat', array($this, 'repeatString')),
			new \Twig_SimpleFilter('join_lists', array($this, 'joinLists')),
			new \Twig_SimpleFilter('humandate', array($this, 'getHumanDate')),
			new \Twig_SimpleFilter('nl2br', array($this, 'nl2br'), array('pre_escape' => 'html', 'is_safe' => array('html'))),
			new \Twig_SimpleFilter('dot2br', array($this, 'dot2br')),
			new \Twig_SimpleFilter('user_markup', array($this, 'formatUserMarkup')),
			new \Twig_SimpleFilter('striptags', array($this, 'stripTags')),
			new \Twig_SimpleFilter('domain', array($this, 'getDomain')),
			new \Twig_SimpleFilter('link', array($this, 'formatLinks')),
			new \Twig_SimpleFilter('encoding', array($this, 'changeEncoding')),
			new \Twig_SimpleFilter('urlencode', array($this, 'getUrlEncode')),
			new \Twig_SimpleFilter('qrcode', array($this, 'getQrCode')),
			new \Twig_SimpleFilter('put_text_in_template', array($this, 'putTextInBookTemplate')),
		);
	}

	public function getTests() {
		return array(
			new \Twig_SimpleTest('url', array($this, 'isUrl')),
		);
	}

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
		return 0;
	}

	public function formatRating($rating) {
		return Legacy::rmTrailingZeros( Number::formatNumber($rating, 1) );
	}

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

	public function getAcronym($title) {
		$letters = preg_match_all('/ ([a-zA-Zа-яА-Я\d])/u', ' '.$title, $matches);
		$acronym = implode('', $matches[1]);

		return Char::mystrtoupper($acronym);
	}

	public function getFirstChar($string) {
		return mb_substr($string, 0, 1, 'UTF-8');
	}

	public function strtolower($string) {
		return mb_strtolower($string, 'UTF-8');
	}

	public function getJson($content) {
		return json_encode($content);
	}

	public function obfuscateEmail($email) {
		return strtr($email,
			array('@' => '&#160;<span title="при сървъра">(при)</span>&#160;'));
	}

	public function getDocTitle($title) {
		$title = preg_replace('/\s\s+/', ' ', $title);
		$title = strtr($title, array(
			'<br>' => ' — ',
			'&amp;' => '&', // will be escaped afterwards by Twig
		));
		$title = trim(strip_tags($title));

		return $title;
	}

	public function repeatString($string, $count) {
		return str_repeat($string, $count);
	}

	public function putTextInBookTemplate($template, Text $text, $htmlTextView) {
		$textId = $text->getId();
		$regexp = "/\{(text|file):$textId(-[^|}]+)?\|(.+)\}/";
		if (preg_match($regexp, $template, $matches)) {
			$template = preg_replace($regexp, str_replace('TEXT_TITLE', $matches[3], $htmlTextView), $template);
		}
		$template = preg_replace("/\{(text|file):$textId(-.+)?\}/", str_replace('TEXT_TITLE', $text->getTitle(), $htmlTextView), $template);

		return $template;
	}

	public function joinLists($string) {
		return preg_replace('|</ul>\n<ul[^>]*>|', "\n", $string);
	}

	public function getHumanDate($date) {
		return Legacy::humanDate($date);
	}

	private $_xmlElementCreator = null;

	/**
	* Generate an anchor name for a given string.
	*
	* @param string  $text    A string
	* @param bool $unique  Always generate a unique name
	*                         (consider all previously generated names)
	*/
	public function getAnchorName($text, $unique = true) {
		if (is_null($this->_xmlElementCreator)) {
			$this->_xmlElementCreator = new XmlElement;
		}

		return $this->_xmlElementCreator->getAnchorName($text, $unique);
	}

	public function getCover($id, $width = 200, $format = 'jpg') {
		return Legacy::getContentFilePath('book-cover', $id) . ".$width.$format";
	}

	public function nl2br($value, $sep = '<br>') {
		return str_replace("\n", $sep."\n", $value);
	}

	public function dot2br($value) {
		return preg_replace('/\. (?=[A-ZА-Я])/u', "<br>\n", $value);
	}

	public function formatUserMarkup($content) {
		return String::pretifyInput(String::escapeInput($content));
	}

	public function stripTags($content) {
		return strip_tags($content);
	}

	public function changeEncoding($string, $encoding) {
		return iconv('UTF-8', $encoding, $string);
	}

	public function getUrlEncode($string) {
		return urlencode($string);
	}

	public function getQrCode($url, $width = 100) {
		return "http://chart.apis.google.com/chart?cht=qr&chs={$width}x{$width}&chld=H|0&chl=". urlencode($url);
	}

	public function getDomain($url) {
		return parse_url($url, PHP_URL_HOST);
	}

	// TODO unit test

	/**
	 * @param string $text
	 */
	public function formatLinks($text) {
		$patterns = array(
			'/\[\[(.+)\|(.+)\]\]/Us' => '<a href="$1">$2</a>',
			'|(?<!")https?://\S+[^,.\s]|e' => "'<a href=\"$0\">'.\$this->getDomain('$0', '$2').'</a>'",
		);
		return preg_replace(array_keys($patterns), array_values($patterns), $text);
	}

	public function isUrl($string) {
		return strpos($string, 'http') === 0;
	}
}
