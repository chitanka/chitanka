<?php namespace App\Legacy;

use App\Util\Number;
use App\Util\String;
use App\Util\Char;

abstract class Page {

	const FF_ACTION = 'action';
	const FF_QUERY = 'q';
	const FF_TEXT_ID = 'id';
	const FF_CHUNK_ID = 'part';
	const FF_LIMIT = 'plmt';
	const FF_OFFSET = 'page';
	const FF_CQUESTION = 'captchaQuestion';
	const FF_CQUESTION_T = 'captchaQuestionT';
	const FF_CANSWER = 'captchaAnswer';
	const FF_CTRIES = 'captchaTries';

	public $redirect = '';
	public $inlineJs = '';

	/** @var string */
	protected $root;
	/** @var string */
	protected $sitename;
	/** @var string */
	protected $action = '';
	/** @var string */
	protected $title;
	/** @var Request */
	protected $request;
	/** @var \App\Entity\User */
	protected $user;
	/** @var mlDatabase */
	protected $db;
	/** @var string */
	protected $content;
	/** @var string */
	protected $messages;
	/** @var OutputMaker */
	protected $out;
	/** @var \App\Controller\Controller */
	protected $controller;
	/** @var \Symfony\Component\DependencyInjection\Container */
	protected $container;
	/** @var \Symfony\Component\HttpFoundation\Request */
	protected $sfrequest;
	/** @var string */
	protected $logDir;

	protected $lpage = 1;
	protected $llimit = 0;
	protected $loffset = 0;
	protected $maxCaptchaTries = 2;
	protected $defListLimit = 10;
	protected $maxListLimit = 50;

	protected $includeUserLinks = true;

	private $templates = [];
	private $captchaTries;
	private $captchaQuestion;
	private $captchaAnswer;
	private $captchaQuestionT;

	public function __construct(array $fields) {
		foreach ($fields as $f => $v) {
			$this->$f = $v;
		}

		$this->root = '/';
		$this->sitename = $this->container->getParameter('sitename');
		$this->messages = $this->content = '';
		$this->title = $this->sitename;
	}

	/** Generate page content according to submission type (POST or GET). */
	public function execute() {
		return $this->content = $this->request->wasPosted() ? $this->processSubmission() : $this->buildContent();
	}

	public function title() {
		return $this->title;
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
	protected function addMessage($message, $isError = false) {
		$class = $isError ? 'error' : 'notice';

		if ($this->sfrequest->isXmlHttpRequest()) {
			header("X-Message-$class: ".rawurlencode($message));
		} else {
			$this->sfrequest->getSession()->getFlashBag()->set($class, $message);
		}
	}

	protected function addJs($js) {
		$this->inlineJs .= $js . "\n";
	}

	protected function getInlineRssLink($route, $data = []) {
		return sprintf('<div class="feed-standalone"><a href="%s" title="RSS 2.0 — %s" rel="feed"><span class="fa fa-rss"></span> <span>RSS</span></a></div>', $this->controller->generateUrl($route, $data), $this->title);
	}

	/**
	 * Build full page content.
	 * @return string
	 */
	public function getFullContent() {
		$this->messages = empty($this->messages) ? '' : "<div id='messages'>\n$this->messages\n</div>";
		$this->templates['{SITENAME}'] = $this->sitename;
		return strtr($this->messages . $this->content, $this->templates);
	}

	/**
	 * Process POST Forms if there are any.
	 * Override this function if your page contains POST Forms.
	 */
	protected function processSubmission() {
		return $this->buildContent();
	}

	/**
	 * Create page content.
	 * Override this function to include content in your page.
	 */
	protected function buildContent() {
		return '';
	}

	protected function makeSimpleTextLink($title, $textId) {
		return $this->out->xmlElement('a', '<em>'. $title .'</em>', [
			'class' => "text text-$textId",
			'href' => $this->controller->generateUrl('text_show_part', [self::FF_TEXT_ID => $textId]),
		]);
	}

	protected function makeAuthorLink($name) {
		$name = rtrim($name, ',');
		if (empty($name)) {
			return '';
		}
		$o = '';
		foreach (explode(',', $name) as $lname) {
			$text = $this->formatPersonName($lname);
			$lname = str_replace('.', '', $lname);
			$link = strpos($lname, '/') !== false // contains not allowed chars
				? $lname
				: sprintf('<a href="%s">%s</a>', $this->controller->generateUrl('person_show', ['slug' => trim($lname)]), $text);
			$o .= ', ' . $link;
		}
		return substr($o, 2);
	}

	protected function makeFromAuthorSuffix($text) {
		if ( is_array($text) ) {
			if ( isset($text['author']) && trim($text['author'], ', ') != '' ) {
				return ' от '.$text['author'];
			}
		} else {
			$authors = [];
			foreach ($text->getAuthors() as $author) {
				if (is_array($author)) {
					$slug = $author['slug'];
					$name = $author['name'];
				} else {
					$slug = $author->getSlug();
					$name = $author->getName();
				}
				$authors[] = sprintf('<a href="%s">%s</a>', $this->controller->generateUrl('person_show', ['slug' => $slug]), $name);
			}
			if (! empty($authors)) {
				return ' от '. implode(', ', $authors);
			}
		}

		return '';
	}

	protected function makeUserLink($name) {
		return sprintf('<a href="%s" class="user" title="Към личната страница на %s">%s</a>', $this->controller->generateUrl('user_show', ['username' => $name]), $name, $name);
	}

	protected function makeUserLinkWithEmail($username, $email, $allowemail) {
		$mlink = '';
		if ( ! empty($email) && $allowemail) {
			$mlink = sprintf('<a href="%s" title="Пращане на писмо на %s"><span class="fa fa-envelope-o"></span><span class="sr-only">Е-поща</span></a>',
				$this->controller->generateUrl('email_user', ['username' => $username]),
				String::myhtmlentities($username));
		}
		return $this->makeUserLink($username) .' '. $mlink;
	}

	private function formatPersonName($name, $sortby = 'first') {
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		if ( !isset($m[2]) ) { return $name; }
		$last = "<span class='lastname'>$m[2]</span>";
		$m3 = isset($m[3]) ? $m[3] : '';
		return $sortby == 'last' ? $last.', '.$m[1].$m3 : $m[1].' '.$last.$m3;
	}

	protected function initPaginationFields() {
		$this->lpage = (int) $this->request->value( self::FF_OFFSET, 1 );
		$this->llimit = (int) $this->request->value(self::FF_LIMIT, $this->defListLimit );
		$this->llimit = Number::normInt( $this->llimit, $this->maxListLimit );
		$this->loffset = ($this->lpage - 1) * $this->llimit;
	}

	protected function verifyCaptchaAnswer($showWarning = false, $_question = null, $_answer = null) {
		if ( !$this->showCaptchaToUser() ) {
			return true;
		}
		$this->captchaTries++;
		if (empty($_question)) { $_question = $this->captchaQuestion; }
		if (empty($_answer)) { $_answer = $this->captchaAnswer; }
		$res = $this->db->select(DBT_QUESTION, ['id' => $_question]);
		if ( $this->db->numRows($res) == 0 ) { // invalid question
			return false;
		}
		$row = $this->db->fetchAssoc($res);
		$answers = explode(',', $row['answers']);
		$_answer = Char::mystrtolower(trim($_answer));
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
		if (!$this->showCaptchaToUser()) {
			return '';
		}
		if ( empty($this->captchaQuestion) ) {
			$row = $this->db->getRandomRow(DBT_QUESTION);
			$id = $row['id'];
			$question = $row['question'];
		} else {
			$id = $this->captchaQuestion;
			$question = $this->captchaQuestionT;
		}
		$qid = $this->out->hiddenField(self::FF_CQUESTION, $id);
		$qt = $this->out->hiddenField(self::FF_CQUESTION_T, $question);
		$tr = $this->out->hiddenField(self::FF_CTRIES, $this->captchaTries);
		$q = '<label for="'.self::FF_CANSWER.'" class="control-label">'.$question.'</label>';
		$answer = $this->out->textField(self::FF_CANSWER, '', $this->captchaAnswer, 30, 60, 0, '', ['class' => 'form-control']);

		return '<div>' . $qid . $qt . $tr . $q .' '. $answer . '</div>';
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

	private function makeCaptchaWarning() {
		if ( $this->hasMoreCaptchaTries() ) {
			$rest = $this->maxCaptchaTries - $this->captchaTries;
			return "Отговорили сте грешно на въпроса „{$this->captchaQuestionT}“. Имате право на още ".($rest == 1 ? 'един опит' : "$rest опита").'.';
		}
		return "Вече сте направили $this->maxCaptchaTries неуспешни опита да отговорите на въпроса „{$this->captchaQuestionT}“. Нямате право на повече.";
	}

	protected function hasMoreCaptchaTries() {
		return $this->captchaTries < $this->maxCaptchaTries;
	}

	private function showCaptchaToUser() {
		return $this->user->isAnonymous() && !$this->user->isHuman();
	}

	private function logFailedCaptcha($msg) {
		file_put_contents($this->logDir."/failed_captcha.log", date('Y-m-d H:i:s').": $msg\n", FILE_APPEND);
	}

}
