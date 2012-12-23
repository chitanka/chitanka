<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\Number;
use Chitanka\LibBundle\Util\String;

abstract class Page
{

	const
		FF_ACTION = 'action',
		FF_QUERY = 'q',
		FF_TEXT_ID = 'id',
		FF_CHUNK_ID = 'part',
		FF_LIMIT = 'plmt',
		FF_OFFSET = 'page',
		FF_SORTBY = 'sortby',
		FF_CQUESTION = 'captchaQuestion',
		FF_CQUESTION_T = 'captchaQuestionT',
		FF_CANSWER = 'captchaAnswer',
		FF_CTRIES = 'captchaTries',

		// do not save any changed page settings
		FF_SKIP_SETTINGS = 'sss';

	public
		$redirect = '',
		$inlineJs = '';

	protected
		$action = '',
		$title,
		$outencoding,
		$contentType,
		$request,
		$user,
		$db,
		$content,
		$messages,
		$fullContent,
		$outputLength,
		$allowCaching,

		$query_escapes = array(
			'"' => '', '\'' => '’'
		),

		$llimit = 0, $loffset = 0,
		$maxCaptchaTries = 2, $defListLimit = 10, $maxListLimit = 50,

		$includeFirstHeading            = true,
		$includeJumptoLinks             = true,
		$includeOpenSearch              = false,#true, // TODO
		$includeNavigation              = true,
		$includeNavigationLinks         = true,
		$includeNavigationExtraLinks    = true,
		$includePersonalTools           = true,
		$includeSearch                  = true,
		$includeFooter                  = true,
		$includeDataSuggestionLinks     = true,
		$includeDownloadLinks           = true,
		$includeMultiDownloadForm       = true,
		$includeUserLinks               = true,
		$includeFeedLinks               = true,
		$includeFilters                 = true,
		$includeCommentLinkByNone       = true,
		$includeRatingLinkByNone        = true,
		$includeInfoLinkByNone          = true,
		$sendFiles                      = true;


	public function __construct($fields)
	{
		foreach ($fields as $f => $v) {
			$this->$f = $v;
		}

		$this->save_settings = $this->request->value(self::FF_SKIP_SETTINGS, 1);

		$this->inencoding = 'utf-8';
		$this->doIconv = true;
		$this->allowCaching = true;
		$this->encfilter = '';
		// TODO
		$this->root = '/';
		$this->rootPage = $this->root;
		$this->rootd = $this->root;
		$this->sitename = Setup::setting('sitename');

		$this->messages = $this->content = $this->fullContent = '';
		$this->contentType = 'text/html';

		$this->isMobile = $this->request->value('mobile', 0) == 1;

		$this->outputDone = false;
		$this->title = $this->sitename;

		if ( $this->isMobile ) {
			if ( $this->action != 'main' ) {
				$this->includeNavigation = false;
			}
			$this->includeNavigationLinks      = false;
			$this->includeNavigationExtraLinks = false;
			$this->includePersonalTools        = false;
		}
	}

	/**
		Generate page content according to submission type (POST or GET).
	*/
	public function execute() {
		$this->content = $this->request->wasPosted()
			? $this->processSubmission()
			: $this->buildContent();
		return $this->content;
	}


	public function title() {
		return $this->title;
	}

	public function content() {
		return $this->content;
	}

	public function get($field) {
		return isset($this->$field) ? $this->$field : null;
	}

	public function set($field, $value) {
		$this->$field = $value;
	}

	public function setFields($data) {
		foreach ((array) $data as $field => $value) {
			$this->$field = $value;
		}
	}

	/**
		@param $message
		@param $isError
	*/
	public function addMessage($message, $isError = false) {
		$class = $isError ? 'error' : 'notice';

		$this->controller->get('request')->getSession()->setFlash($class, $message);
	}


	protected function addJs($js) {
		$this->inlineJs .= $js . "\n";
	}


	/** TODO replace */
	public function addRssLink($title = null, $actionOrUrl = null) {
		return;

		Legacy::fillOnEmpty($title, $this->title());
		$title = strip_tags($title);

		if ( Legacy::isUrl($actionOrUrl) ) {
			$url = $actionOrUrl;
		} else {
			Legacy::fillOnEmpty($actionOrUrl, $this->action);
			$params = array(
				self::FF_ACTION => 'feed',
				'obj'           => $actionOrUrl,
			);
			$url = '';#url($params, 2);
		}
		$feedlink = <<<EOS

	<link rel="alternate" type="application/rss+xml" title="RSS 2.0 — $title" href="$url" />
EOS;
		$this->addHeadContent($feedlink);
	}


	public function getInlineRssLink($route, $data = array(), $title = null) {
		Legacy::fillOnEmpty($title, $this->title());

		$link = sprintf('<div class="feed-standalone"><a href="%s" title="RSS 2.0 — %s" rel="feed"><span>RSS</span></a></div>', $this->controller->generateUrl($route, $data), $title);

		return $link;
	}


	public function addScript($file, $debug = false) {
	}


	public function allowCaching() {
		return $this->allowCaching;
	}

	/**
		Output page content.
	*/
	public function output() {
		if ( $this->outputDone ) { // already outputted
			return;
		}
		if ( empty($this->fullContent) ) {
			$this->getFullContent();
		}
		print $this->fullContent;
	}


	public function isValidEncoding($enc) {
		return @iconv($this->inencoding, $enc, '') !== false;
	}

	/**
		Build full page content.
		@return string
	*/
	public function getFullContent()
	{
		$this->messages = empty( $this->messages ) ? ''
			: "<div id='messages'>\n$this->messages\n</div>";

		$this->fullContent = $this->messages . $this->content;
		unset($this->content); // free some memory

		$this->addTemplates();
		$this->fullContent = Legacy::expandTemplates($this->fullContent);

		return $this->fullContent;
	}


	private function getFirstHeading()
	{
		return empty($this->title) ? $this->sitename : $this->title;
	}

	public function getOpenSearch()
	{
		$opensearch = '';
		if  ( array_key_exists($this->action, $this->searchOptions) ) {
			$opensearch = "\n\t" . $this->out->xmlElement('link', null, array(
				'rel' => 'search',
				'type' => 'application/opensearchdescription+xml',
				'href' => 'action=opensearchdesc',
				'title' => "$this->sitename ({$this->searchOptions[$this->action]})"

			));
		}
		return $opensearch;
	}


	/**
		Process POST Forms if there are any.
		Override this function if your page contains POST Forms.
	*/
	protected function processSubmission() {
		return $this->buildContent();
	}

	/**
		Create page content.
		Override this function to include content in your page.
	*/
	protected function buildContent() {
		return '';
	}


	protected function addTemplates() {
		Legacy::addTemplate('ROOT', $this->root);
		Legacy::addTemplate('DOCROOT', $this->rootd.'/');
		Legacy::addTemplate('SITENAME', $this->sitename);
	}


	protected function makeSimpleTextLink(
		$title, $textId, $chunkId = 1, $linktext = '',
		$attrs = array(), $data = array(), $params = array()
	) {
		$p = array(
			self::FF_TEXT_ID => $textId,
			//'slug' => $this->out->slugify(preg_replace('/^(\d+\.)+ /', '', $title)),
		);
		if ($chunkId != 1) {
			$p[self::FF_CHUNK_ID] = $chunkId;
		}
		if ( empty($linktext) ) {
			$linktext = '<em>'. $title .'</em>';
		}

		$attrs['class'] = ltrim(@$attrs['class'] . " text text-$textId");
		$attrs['href'] = $this->controller->generateUrl('text_show_part', $p);

		return $this->out->xmlElement('a', $linktext, $attrs);
	}


	protected function makeTextLinkWithAuthor($work) {
		return '„' . $this->makeSimpleTextLink($work->getTitle(), $work->getId()) . '“'
			. $this->makeFromAuthorSuffix($work);
	}


	public function makeAuthorLink(
		$name, $sortby='first', $pref='', $suf='', $query=array()
	) {
		$name = rtrim($name, ',');
		if ( empty($name) ) {
			return '';
		}
		settype($query, 'array');
		$o = '';
		foreach ( explode(',', $name) as $lname ) {
			$text = empty($sortby)
				? 'Произведения от ' . $name
				: $this->formatPersonName($lname, $sortby);
			$lname = str_replace('.', '', $lname);
			$link = strpos($lname, '/') !== false // contains not allowed chars
				? $lname
				: sprintf('<a href="%s">%s</a>', $this->controller->generateUrl('person_show', array('slug' => trim($lname))+$query), $text);
			$o .= ', ' . $pref . $link . $suf;
		}
		return substr($o, 2);
	}


	public function makeFromAuthorSuffix($text) {
		if ( is_array($text) ) {
			if ( isset($text['author']) && trim($text['author'], ', ') != '' ) {
				return ' от '.$text['author'];
			}
		} else {
			$authors = array();
			foreach ($text->getAuthors() as $author) {
				if (is_array($author)) {
					$slug = $author['slug'];
					$name = $author['name'];
				} else {
					$slug = $author->getSlug();
					$name = $author->getName();
				}
				$authors[] = sprintf('<a href="%s">%s</a>', $this->controller->generateUrl('person_show', array('slug' => $slug)), $name);
			}
			if (! empty($authors)) {
				return ' от '. implode(', ', $authors);
			}
		}

		return '';
	}

	protected function makeUserLink($name) {
		return sprintf('<a href="%s" title="Към личната страница на %s">%s</a>', $this->controller->generateUrl('user_show', array('username' => $name)), $name, $name);
	}

	protected function makeUserLinkWithEmail($username, $email, $allowemail) {
		$mlink = '';
		if ( ! empty($email) && $allowemail) {
			$mlink = sprintf('<a href="%s" class="email" title="Пращане на писмо на %s"><span>Е-поща</span></a>',
				$this->controller->generateUrl('user_email', array('username' => $username)),
				String::myhtmlentities($username));
		}
		return $this->makeUserLink($username) .' '. $mlink;
	}


	protected function formatPersonName($name, $sortby = 'first') {
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		if ( !isset($m[2]) ) { return $name; }
		$last = "<span class='lastname'>$m[2]</span>";
		$m3 = isset($m[3]) ? $m[3] : '';
		return $sortby == 'last' ? $last.', '.$m[1].$m3 : $m[1].' '.$last.$m3;
	}


	public function initPaginationFields() {
		$this->lpage = (int) $this->request->value( self::FF_OFFSET, 1 );
		$this->llimit = (int) $this->read_save_request_value(
			self::FF_LIMIT, 'llimit', $this->defListLimit );
		$this->llimit = Number::normInt( $this->llimit, $this->maxListLimit );

		$this->loffset = ($this->lpage - 1) * $this->llimit;
	}


	/**
		See Request::value()
	*/
	protected function read_save_request_value( $param, $page_field,
				$default = null, $paramno = null, $allowed = null ) {

		if ( ! empty( $this->$page_field ) ) {
			$default = $this->$page_field;
		}
		$value = $this->request->value( $param, $default, $paramno, $allowed );

		if ( $value != $default && $this->save_settings ) {
			$fields = array( $page_field => $value );
			$this->user->setPageFields( $this->action, $fields );
		}
		return $value;
	}


	protected function verifyCaptchaAnswer($showWarning = false,
			$_question = null, $_answer = null) {
		if ( !$this->showCaptchaToUser() ) {
			return true;
		}
		$this->captchaTries++;
		Legacy::fillOnEmpty($_question, $this->captchaQuestion);
		Legacy::fillOnEmpty($_answer, $this->captchaAnswer);
		$res = $this->db->select(DBT_QUESTION, array('id' => $_question));
		if ( $this->db->numRows($res) == 0 ) { // invalid question
			return false;
		}
		$row = $this->db->fetchAssoc($res);
		$answers = explode(',', $row['answers']);
		$_answer = trim($_answer);
		foreach ($answers as $answer) {
			if ($_answer == $answer) {
				$this->user->setIsHuman(true);
				return true;
			}
		}
		if ($showWarning) {
			$this->addMessage($this->makeCaptchaWarning(), true);
		}
		$this->logFailedCaptcha("$row[question] [$row[answers]] -> \"$_answer\"");

		return false;
	}

	protected function makeCaptchaQuestion() {
		if ( !$this->showCaptchaToUser() ) {
			return '';
		}
		if ( empty($this->captchaQuestion) ) {
			extract( $this->db->getRandomRow(DBT_QUESTION) );
		} else {
			$id = $this->captchaQuestion;
			$question = $this->captchaQuestionT;
		}
		$qid = $this->out->hiddenField(self::FF_CQUESTION, $id);
		$qt = $this->out->hiddenField(self::FF_CQUESTION_T, $question);
		$tr = $this->out->hiddenField(self::FF_CTRIES, $this->captchaTries);
		$q = $this->out->label($question, self::FF_CANSWER);
		$answer = $this->out->textField(self::FF_CANSWER, '', $this->captchaAnswer, 30, 60, 20);

		return $qid . $qt . $tr . $q .' '. $answer .'<br>';
	}

	protected function initCaptchaFields() {
		$this->captchaQuestion = (int) $this->request->value(self::FF_CQUESTION, 0);
		$this->captchaQuestionT = $this->request->value(self::FF_CQUESTION_T);
		$this->captchaAnswer = $this->request->value(self::FF_CANSWER);
		$this->captchaTries = (int) $this->request->value(self::FF_CTRIES, 0);
	}

	protected function clearCaptchaQuestion() {
		$this->captchaQuestion = 0;
		$this->captchaQuestionT = $this->captchaAnswer = '';
		$this->captchaTries = 0;
	}

	protected function makeCaptchaWarning() {
		if ( $this->hasMoreCaptchaTries() ) {
			$rest = $this->maxCaptchaTries - $this->captchaTries;
			$tries = Legacy::chooseGrammNumber($rest, 'един опит', $rest.' опита');
			return "Отговорили сте грешно на въпроса „{$this->captchaQuestionT}“. Имате право на още $tries.";
		}
		return "Вече сте направили $this->maxCaptchaTries неуспешни опита да отговорите на въпроса „{$this->captchaQuestionT}“. Нямате право на повече.";
	}

	protected function hasMoreCaptchaTries() {
		return $this->captchaTries < $this->maxCaptchaTries;
	}

	protected function showCaptchaToUser() {
		return $this->user->isAnonymous() && !$this->user->isHuman();
	}

	private function logFailedCaptcha($msg) {
		file_put_contents(__DIR__."/../../../../app/logs/failed_captcha.log", "$msg\n", FILE_APPEND);
	}

	protected function getFreeId($dbtable) {
		return $this->db->autoIncrementId($dbtable);
	}

	protected function addUrlQuery($args) {
		return $this->out->addUrlQuery($this->request->requestUri(), $args);
	}


	protected function sendFile($file)
	{
		$this->outputLength = filesize($file);
		if ($this->sendFiles) {
			header('Location: '. $this->rootd . '/' .  $file);
		} else {
			$this->fullContent = file_get_contents($file);
		}
		$this->outputDone = true;
	}

}
