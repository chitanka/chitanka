<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Util\Number;

class FeedPage extends Page {

	protected
		$action = 'feed',
		$validObjs = array('work'),
		$defObj = 'work', $defFeedType = 'rss', $defListLimit = 25,
		$maxListLimit = 200,

		/** See http://validator.w3.org/feed/docs/warning/SecurityRisk.html */
		$dangerous_desc_tags = array(
			'comment',
			'embed',
			'link',
			'listing',
			'meta',
			'noscript',
			'object',
			'plaintext',
			'script',
			'xmp',
		)
	;


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Зоб за новинарски четци';
		$this->feedDescription = 'Универсална електронна библиотека';
		$this->server = $this->request->server();
		$this->root = $this->server . $this->root;
		$this->contentType = 'application/rss+xml';
		$this->obj = Legacy::normVal(
			$this->request->value('type', $this->defObj),
			$this->validObjs, $this->defObj);
		$this->llimit = Number::normInt(
			(int) $this->request->value('count', $this->defListLimit),
			$this->maxListLimit);
		$this->feedtype = 'rss';
		$this->langCode = 'bg';
	}


	public function title() {
		return "$this->title — $this->sitename";
	}


	protected function buildContent() {
		$ftPref = ucfirst($this->feedtype);
		$myfields = array('root' => $this->root);
		$pagename = '';
		switch ($this->obj) {
			case 'work':
				$makeItemFunc = 'make'. $ftPref . ucfirst($this->obj) .'Item';
				$pagename = $this->obj;
				$myfields['objId'] = 0;
				break;
		}
		$bufferq = false;
		if ($this->obj == 'work') {
			$bufferq = true;
			$myfields['showProgressbar'] = false;
		}
		$this->basepage = Setup::getPage(ucfirst($pagename), $this->controller, $this->container, false);
		$this->basepage->setFields($myfields);
		$makeFunc = 'make'.$ftPref.'Feed';
		$q = $this->basepage->makeSqlQuery($this->llimit, 0, 'DESC');
		$this->title = $this->basepage->title();

		$this->fullContent = $this->$makeFunc($q, $makeItemFunc, $bufferq);

		$this->addTemplates();
		$feed = Legacy::expandTemplates($this->fullContent);
		header("Content-Type: $this->contentType; charset=UTF-8");
		header('Content-Length: '. strlen($feed));
		echo <<<FEED
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
	$feed
</channel>
</rss>
FEED;
		exit;

		return '';
	}


	protected function makeRssFeed($query, $makeItemFunc, $bufferq) {
		$request_uri = $this->request->requestUri(true);
		$ch =
			$this->makeXmlElement('title', $this->title() ) .
			$this->makeXmlElement('link', $this->server) .
			$this->makeXmlElement('description', $this->feedDescription) .
			$this->makeXmlElement('language', $this->langCode) .
			$this->makeXmlElement('lastBuildDate', $this->makeRssDate()) .
			$this->makeXmlElement('generator', 'mylib') .
			$this->db->iterateOverResult($query, $makeItemFunc, $this, $bufferq);
		return <<<EOS
	$ch
	<atom:link href="$request_uri" rel="self" type="application/rss+xml" />
EOS;
	}


	public function makeRssWorkItem($dbrow) {
		extract($dbrow);
		$dbrow['showtitle'] = $dbrow['showtime'] = false;
		$dbrow['expandinfo'] = true;
		$description = $this->basepage->makeWorkListItem($dbrow, false);
		$time = $date;
		$link = $this->controller->generateUrl('workroom', array(), true) . "#e$id";
		$guid = "$link-$status-$progress";
		if ( $type == 1 && $status >= WorkPage::MAX_SCAN_STATUS ) {
			$guid .= '-'. $this->formatDateForGuid($date);
		}
		$creator = $username;
		$data = compact('title', 'link', 'time', 'guid', 'description', 'creator');

		return $this->makeRssItem($data);
	}


	public function makeRssItem($data) {
		extract($data);
		if ( empty($title) ) $title = strtr($time, array(' 00:00:00' => ''));
		if (empty($creator)) $creator = $this->sitename;
		// unescape escaped ampersands to prevent double escaping them later
		$link = strtr($link, array('&amp;' => '&'));
		if (empty($guid)) $guid = $link;
		$src = empty($source) || strpos($source, 'http') === false ? '' : $source;
		$lvl = 2;
		$description = str_replace('href="/', 'href="'.$this->server.'/', $description);

		return "\n\t<item>".
			$this->makeXmlElement('title', strip_tags($title), $lvl) .
			$this->makeXmlElement('dc:creator', $creator, $lvl) .
			$this->makeXmlElement('link', $link, $lvl) .
			$this->makeXmlElement('pubDate', $this->makeRssDate($time), $lvl) .
			$this->makeXmlElement('guid', $guid, $lvl) .
			$this->makeXmlElement('description',
				$this->escape_element( $description ), $lvl) .
			$this->makeXmlElement('source', $src, $lvl, array('url'=>$src)) .
			"\n\t</item>";
	}


	protected function makeXmlElement($name, $content, $level = 1, $attrs = array()) {
		if ( empty($content) ) {
			return '';
		}
		$content = String::myhtmlspecialchars($content);
		$elem = $this->out->xmlElement($name, $content, $attrs);
		return "\n". str_repeat("\t", $level) . $elem;
	}


	protected function makeRssDate($isodate = NULL) {
		$format = 'r';
		return empty($isodate) ? date($format) : date($format, strtotime($isodate));
	}


	protected function formatDateForGuid($date) {
		return strtr($date, ' :', '__');
	}


	/**
	* Remove dangerous elements along with their content
	*/
	protected function escape_element( $desc ) {
		if ( ! isset( $this->_esc_desc_re ) ) {
			$re = '';
			foreach ( $this->dangerous_desc_tags as $tag ) {
				$re .= "|<$tag.+</$tag>";
			}
			$this->_esc_desc_re = '!' . ltrim($re, '|') . '!U';
		}
		return preg_replace( $this->_esc_desc_re, '', $desc );
	}
}
