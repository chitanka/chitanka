<?php namespace App\Twig;

use App\Entity\Text;
use App\Service\ContentService;
use App\Util\Number;
use App\Util\Stringy;
use Sfblib\XmlElement;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class Extension extends AbstractExtension {

	private $bibliomanUrl;

	public function __construct($bibliomanUrl) {
		$this->bibliomanUrl = $bibliomanUrl;
	}

	/** {@inheritdoc} */
	public function getName() {
		return 'chitanka';
	}

	/** {@inheritdoc} */
	public function getFunctions() {
		return [
			new TwigFunction('anchor_name', [$this, 'getAnchorName']),
			new TwigFunction('cover', [$this, 'getCover']),
			new TwigFunction('biblioman_url', [$this, 'getBibliomanUrl']),
		];
	}

	/** {@inheritdoc} */
	public function getFilters() {
		return [
			new TwigFilter('rating_class', [$this, 'getRatingClass']),
			new TwigFilter('rating_format', [$this, 'formatRating']),
			new TwigFilter('name_format', [$this, 'formatPersonName']),
			new TwigFilter('acronym', 'App\Util\Stringy::createAcronym'),
			new TwigFilter('first_char', [$this, 'getFirstChar']),
			new TwigFilter('email', [$this, 'obfuscateEmail']),
			new TwigFilter('doctitle', [$this, 'getDocTitle']),
			new TwigFilter('lower', [$this, 'strtolower']),
			new TwigFilter('json', 'json_encode'),
			new TwigFilter('repeat', [$this, 'repeatString']),
			new TwigFilter('join_lists', [$this, 'joinLists']),
			new TwigFilter('humandate', 'App\Util\Date::humanDate'),
			new TwigFilter('nl2br', [$this, 'nl2br'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
			new TwigFilter('dot2br', [$this, 'dot2br']),
			new TwigFilter('user_markup', [$this, 'formatUserMarkup']),
			new TwigFilter('striptags', 'strip_tags'),
			new TwigFilter('domain', [$this, 'getDomain']),
			new TwigFilter('link', [$this, 'formatLinks']),
			new TwigFilter('encoding', [$this, 'changeEncoding']),
			new TwigFilter('url_decode', 'rawurldecode'),
			new TwigFilter('qrcode', [$this, 'getQrCode']),
			new TwigFilter('put_text_in_template', [$this, 'putTextInBookTemplate']),
		];
	}

	/** {@inheritdoc} */
	public function getTests() {
		return [
			new TwigTest('url', [$this, 'isUrl']),
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

	public function obfuscateEmail(string $htmlContent): string {
		return preg_replace_callback('/([\w._+-]+)@(\w+\.\w+)/', function($matches) {
			$name = $matches[1];
			$provider = $matches[2];
			$encodeHtml = function($string) {
				return implode('', array_map(function($char) { return '&#'.ord($char).';'; }, str_split($string)));
			};
			$encodeJs = function($string) {
				return implode('', array_map(function($char) { return '\x'.dechex(ord($char)); }, str_split($string)));
			};
			$id = 'contactAddress_'.uniqid();
			return <<<CODE
<span id="$id">{$encodeHtml($name)}&#160;<span title="при сървъра">(при)</span>&#160;{$encodeHtml($provider)}</span><script>
	var __a__ = ((n, s, o) => [n, s, o].join('~'))('recipient', 'at', 'postoffice').replace('recipient', '{$encodeJs($name)}').replace('~at~', String.fromCharCode(Math.pow(8, 2))).replace('postoffice', '{$encodeJs($provider)}');
	document.getElementById('$id').innerHTML = '<b><'+'a hr'+'ef="ma'+('il')+('to')+(':')+__a__+'">'+__a__+'<'+'/'+'a></b>';
</script>
CODE;
		}, $htmlContent);
	}

	/**
	 * @param string $title
	 * @return string
	 */
	public function getDocTitle($title) {
		$title = strtr($title, [
			'<br>' => ' — ',
			'&amp;' => '&', // will be escaped afterwards by Twig
			'</div>' => '|',
		]);
		$title = strip_tags($title);
		$title = preg_replace('/\s\s+/', ' ', $title);
		$title = str_replace('| |', '|', $title);
		$title = trim($title, " |\n");
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
		$regexpWithCustomTitle = "/\{(text|file):$textId(-[^|}]+)?\|(.+)\}/";
		if (preg_match($regexpWithCustomTitle, $template, $matches)) {
			$template = preg_replace($regexpWithCustomTitle, str_replace('TEXT_TITLE', $matches[3], $htmlTextView), $template);
		}
		$regexpNormal = "/\{(text|file):$textId(-.+)?\}/";
		$template = preg_replace($regexpNormal, str_replace('TEXT_TITLE', $text->getTitle(), $htmlTextView), $template, 1);
		// remove other occurences, e.g. cases where a {title:X} and a {file:X} are present
		$template = preg_replace($regexpNormal, '', $template);
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
		return ContentService::getCover($id, $width, $format);
	}

	public function getBibliomanUrl($bookId) {
		return str_replace('$1', $bookId, $this->bibliomanUrl);
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
		$value = str_replace('; ', ' • ', $value);
		return preg_replace('/\. (?=[A-ZА-Я])/u', ' • ', $value);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public function formatUserMarkup($content) {
		return Stringy::prettifyInput(Stringy::escapeInput($content));
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
	 * @param string $url
	 * @param int $width
	 * @return string
	 */
	public function getQrCode($url, $width = 100) {
		return "//chart.googleapis.com/chart?cht=qr&chs={$width}x{$width}&chld=H|0&chl=". urlencode($url);
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
		$formattedText = $text;
		$formattedText = preg_replace('/\[\[(.+)\|(.+)\]\]/Us', '<a href="$1">$2</a>', $formattedText);
		$formattedText = preg_replace_callback('|(?<!")https?://\S+[^,.\s]|', function ($m) {
			return "<a href=\"$m[0]\">". $this->getDomain($m[0]) .'</a>';
		}, $formattedText);
		return $formattedText;
	}

	/**
	 * @param string $string
	 * @return bool
	 */
	public function isUrl($string) {
		return strpos($string, 'http') === 0;
	}
}
