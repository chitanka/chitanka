<?php namespace App\Legacy;

use App\Util\Date;
use App\Util\String;
use App\Entity\Text;
use App\Entity\TextComment;
use App\Pagination\Pager;

class CommentPage extends Page {

	protected
		$action = 'comment',
		$sortOrder = 'ASC',
		$wheres = [
			-1 => ['`is_shown`' => 0], // only hidden comments
			1 => ['`is_shown`' => 1], // only visible comments
			0 => '1'], // all comments
		$defListLimit = 20,
		$maxListLimit = 100,
		$maxCaptchaTries = 5,

		$includeCommentForm   = true,
		$includeViewTypeLinks = true;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Читателски коментари';
		$this->reader = $this->user->isAnonymous()
			? trim($this->request->value('reader'))
			: $this->user->userName();

		// used by the “view per user” mode
		$this->username = $this->request->value('username');

		$this->comment = $this->request->value('commenttext');
		$this->textId = (int) $this->request->value('id');
		$this->chunkId = (int) $this->request->value('chunkId', 1, 2);
		$this->replyto = (int) $this->request->value('replyto', 0);
		$this->initCaptchaFields();
		$this->showMode = 1; // only visible
		$this->putNr = false;
		$this->initDone = false;
		$this->initPaginationFields();
	}

	private function addTextFeedLinks() {
		if ( empty($this->work) ) {
			$url = $this->controller->generateUrl('texts_comments', ['_format' => 'rss']);
			$title = 'Читателски коментари';
		} else {
			$url = $this->controller->generateUrl('text_comments', ['id' => $this->textId, '_format' => 'rss']);
			$title = 'Читателски коментари за „' . strip_tags($this->work->getTitle()) . '“';
		}
	}

	protected function processSubmission() {
		if ( empty($this->reader) || empty($this->comment) ) {
			$this->addMessage('Попълнете всички полета!', true);
			if ($this->sfrequest->isXmlHttpRequest()) {
				return '';
			}
			return $this->buildContent();
		}
		if (strpos($this->comment, 'bg.shvoong.com') !== false) {
			$this->addMessage('Спама е забранен.', true);
			return $this->buildContent();
		}
		if ( !empty($this->textId) ) {
			$this->initData();
		}

		$this->comment = String::my_replace($this->comment);
		if ( $this->request->value('preview') != NULL ) {
			$this->addMessage('Това е само предварителен преглед. Мнението ви все още не е съхранено.');
			$response = $this->makeComment();
			if (!$this->sfrequest->isXmlHttpRequest()) {
				$response .= $this->makeForm();
			}
			return $response;
		}
		$showComment = 1;
		if ( !$this->verifyCaptchaAnswer(true) ) {
			if ( $this->hasMoreCaptchaTries() ) {
				return $this->makeForm();
			} else {
				$showComment = 0;
			}
		}
		$em = $this->controller->em();
		$comment = new TextComment;
		$comment->setText($em->find('App:Text', $this->textId));
		$comment->setRname($this->reader);
		$comment->setContent($this->comment);
		$comment->setContenthash(md5($this->comment));
		$comment->setTime(new \DateTime());
		$comment->setIp(@$_SERVER['REMOTE_ADDR']);
		$comment->setIsShown($showComment);
		if ($this->user->isAuthenticated()) {
			$comment->setUser($em->merge($this->user));
		}
		if ($this->replyto) {
			$comment->setReplyto($em->find('App:TextComment', $this->replyto));
		}
		$em->persist($comment);
		$em->flush();
		if ($showComment) {
			$this->db->query(sprintf('UPDATE %s SET comment_count = comment_count + 1 WHERE id = %d', DBT_TEXT, $this->textId));

			// TODO rewrite
			if (!preg_match('/^(127|192)/', $comment->getIp())) {
				$chatMsg = sprintf('Нов [url=http://chitanka.info/text/%d/comments#e%d]читателски коментар[/url] от [b]%s[/b] за „%s“', $this->textId, $comment->getId(), $this->reader, $this->work->getTitle());
				Legacy::getFromUrl('http://forum.chitanka.info/chat/post.php', ['m' => $chatMsg]);
			}
		}
		if (!$this->sfrequest->isXmlHttpRequest()) {
			$this->addMessage('Мнението ви беше получено.');
			if ( ! $showComment ) {
				$this->addMessage('Ще бъде показано след преглед от модератор.');
			}
		}
		$this->replyto = $this->comment = '';
		$this->clearCaptchaQuestion();
		return $this->buildContent();
	}

	protected function buildContent() {
		if ( empty($this->textId) ) {
			if ( !empty($this->username) ) {
				$this->wheres[$this->showMode][] = 'c.user_id IN ('
					. $this->db->selectQ(DBT_USER,
						['username' => $this->username],
						'id')
					. ')';
				$this->title .= ' от ' . $this->makeUserLink($this->username);
			}
			// a global RSS link should be added to the page

			return $this->makeAllComments($this->llimit, $this->loffset, 'DESC');
		}
		$this->initData();
		$this->addTextFeedLinks();

		return '<div id="comments-wrapper">'
			. $this->makeComments()
			. ($this->includeCommentForm ? $this->makeForm() : '')
			. '</div>';
	}

	private function makeComments() {
		$key = $this->wheres[$this->showMode];
		$key['c.text_id'] = $this->textId;
		if ( !empty($this->replyto) ) {
			$key['c.id'] = $this->replyto;
		}
		$qa = [
			'SELECT' => 'c.*, tr.rating, tr.date ratingdate',
			'FROM' => DBT_COMMENT .' c',
			'LEFT JOIN' => [
				DBT_TEXT_RATING .' tr' => 'tr.text_id = c.text_id AND tr.user_id = c.user_id',
			],
			'WHERE' => $key,
			'ORDER BY' => "`time` $this->sortOrder",
		];
		$sql = $this->db->extselectQ($qa);
		$this->comments = '';
		$this->acomments = $this->acommentsTree = $this->acommentsTmp = [];
		$this->curRowNr = 0;
		$results = $this->controller->em()->getConnection()->executeQuery($sql)->fetchAll();
		foreach ($results as $result) {
			$this->processCommentDbRow($result);
		}
		if ( empty($this->acomments) ) {
			return $this->includeCommentForm ? $this->makeNewCommentLink() : '';
		}
		// TODO правилна инициализация на дървото, ако се почва някъде от средата
		if ( empty($this->acommentsTree) ) {
			$this->acommentsTree = $this->acomments;
		}
		$this->putNr = true;
		$this->makeCommentsAsTree($this->acommentsTree);
		$newcommentlink = empty($this->replyto) && $this->includeCommentForm ? $this->makeNewCommentLink() : '';
		return
			$newcommentlink
			. '<div id="readercomments" style="clear:both">'. $this->comments . '</div>'
			. $newcommentlink;
	}

	private function makeNewCommentLink() {
		return '<p><a href="#postform" onclick="return initReply(0)">Пускане на нов коментар</a> ↓</p>';
	}

	private function processCommentDbRow($dbrow) {
		$id = $dbrow['id']; $replyto = $dbrow['replyto_id'];
		$dbrow['nr'] = ++$this->curRowNr;
		$this->acomments[$id] = $dbrow;
		if ( !isset($this->acommentsTmp[$id]) ) {
			$this->acommentsTmp[$id] = [];
		}
		if ( empty($replyto) || !array_key_exists($replyto, $this->acommentsTmp) ) {
			$this->acommentsTree[$id] = & $this->acommentsTmp[$id];
		} else {
			$this->acommentsTmp[$replyto][$id] = & $this->acommentsTmp[$id];
		}
	}

	private function makeCommentsAsTree($tree, $level = 0, $id = '') {
		$this->comments .= "\n<ul id='sublistof$id'>";
		foreach ($tree as $id => $subtree) {
			$this->comments .= isset($this->acomments[$id])
				? "<li id='it$id' class='lev$level deletable'>". $this->makeComment( $this->acomments[$id] )
				: '';
			if ( is_array($subtree) && !empty($subtree) ) {
				$this->makeCommentsAsTree($subtree, $level + 1, $id);
			}
			$this->comments .= "\n".'</li>';
		}
		$this->comments .= "\n".'</ul>';
	}

	/**
	 * @param array $fields Associative array containing following (optional)
	 *                      elements: rname, content, user_id, time, textId, textTitle, author, edit, showtime
	 * @return string
	 */
	private function makeComment($fields = []) {
		extract($fields);
		if ( empty($id) ) { $id = 0; }
		if ( empty($rname) ) { $rname = $this->reader; }
		if ( empty($content) ) { $content = $this->comment; }
		if ( empty($textId) ) { $textId = $this->textId; }

		$firstrow = $secondrow = '';
		if ( !isset($showtitle) || $showtitle ) { // show per default
			$rnameview = ! empty($user_id) && $this->includeUserLinks
				? $this->makeUserLink($rname)
				: $rname;
			$timev = !isset($showtime) || $showtime // show per default
				? ' <small>('. Date::humanDate(@$time) .')</small>' : '';
			$firstrow = empty($textId) || empty($textTitle) ? ''
				: '<p class="firstrow">'.
					$this->makeSimpleTextLink($textTitle, $textId) .
					$this->makeFromAuthorSuffix($fields) .'</p>';
			$acts = '';
			if ( !empty($textId) ) {
				$links = '';
				if ($this->includeCommentForm) {
					$links .= sprintf('<li><a href="%s#e%s" title="Отговор на коментара" onclick="return initReply(%d)"><span class="fa fa-reply"></span><span class="sr-only">Отговор</span></a></li>', $this->controller->generateUrl('text_comments', ['id' => $textId, 'replyto' => $id]), $id, $id);
				}
				if ( empty($this->textId) ) {
					$links .= sprintf('<li><a href="%s" title="Всички коментари за произведението"><span class="fa fa-comments"></span><span class="sr-only">Всички коментари</span></a></li>', $this->controller->generateUrl('text_comments', ['id' => $textId]));
				}
				if ($this->user->inGroup('admin')) {
					$links .= sprintf('<li><a href="%s" title="Редактиране на коментара"><span class="fa fa-edit"></span><span class="sr-only">Редактиране</span></a></li>', $this->controller->generateUrl('admin_text_comment_edit', ['id' => $id]));
					$links .= sprintf('<li><form action="%s" method="post" class="image-form delete-form"><button type="submit" title="Изтриване на коментара"><span class="fa fa-trash-o"></span><span class="sr-only">Изтриване</span></button></form></li>', $this->controller->generateUrl('admin_text_comment_delete', ['id' => $id]));
				}
				$acts = "<ul class='menu' style='float:right'>$links</ul>";
			}
			$nr = $this->putNr ? $nr.'. ' : '';
			$ratingview = empty($rating)
				? ''
				: ', оценка: <span title="Дадена на '.Date::humanDate($ratingdate).'">' . $rating . ' от ' . Text::getMaxRating() . '</span>';
			$secondrow = "<div class='secondrow'>$acts<strong>$nr$rnameview</strong>$timev$ratingview</div><hr>";
		}
		$content = String::prettifyInput(String::escapeInput($content));

		return <<<EOS

	<fieldset class="readercomment deletable" id="e$id">
		$firstrow
		$secondrow
		<div class="commenttext">
		$content
		</div>
	</fieldset>
	<div id="replyto$id"></div>
EOS;
	}

	public function makePreview() {
		return '<h2>Предварителен преглед</h2>' .
			$this->makeComment([
				'content' => $this->comment,
				'rname' => $this->reader
			]);
	}

	private function makeForm() {
		if ( empty($this->work) ) {
			return '';
		}
		return $this->makeEditForm();
	}

	private function makeEditForm() {
		$textId = $this->out->hiddenField('textId', $this->textId);
		$chunkId = $this->out->hiddenField('chunkId', $this->chunkId);
		$replyto = $this->out->hiddenField('replyto', $this->replyto);
		$reader = $this->user->isAnonymous()
			? '<div class="form-group"><label for="reader">Име: </label>' . $this->out->textField('reader', '', $this->reader, 40, 160, null, '', ['class' => 'form-control']) . '</div>'
			: '';
		$comment = $this->out->textarea('commenttext', '', $this->comment, 10, 77,
			null, ['onkeypress' => 'postform_changed = true', 'class' => 'form-control']);
		$hideform = !empty($this->comment) || !empty($this->replyto) ? ''
			: '$("#postform").hide();';

		$js = <<<JS
	var postform_changed = false;
	$hideform
	$('#postform :submit').on('click', function(e) {
		var form = jQuery(this.form);
		var button = this.name;
		jQuery.post(this.form.action, form.serialize() +'&'+button+'=1', function(response, textStatus, request) {
			if (button == 'preview') {
				var notice = request.getResponseHeader('X-Message-notice');
				var error = request.getResponseHeader('X-Message-error');
				var preview = jQuery('#postform-preview').show();
				preview.html(response);
				if (notice) {
					preview.prepend('<div class="alert alert-info">'+decodeURIComponent(notice)+'</div>');
				}
				if (error) {
					preview.prepend('<div class="alert alert-danger">'+decodeURIComponent(error)+'</div>');
				}
			} else {
				jQuery('#comments-wrapper').replaceWith(response);
			}
		});
		return false;
	});
JS;
		$this->addJs($js);

		$question = $this->makeCaptchaQuestion();
		if ($question) {
			$question = '<div class="form-group">' . $question . '</div>';
		}
		$allowHelp = empty(String::$allowableTags) ? ''
			: '<dl class="instruct"><dt>Разрешени са следните етикети</dt><dd><ul><li>&lt;'.implode('&gt;</li><li>&lt;', String::$allowableTags).'&gt;</li></ul></dd></dl>';

		$postUrl = $this->controller->generateUrl('text_comments', ['id' => $this->textId]);
		return <<<EOS

<form action="$postUrl" method="post" id="postform" class="form-horizontal">
<fieldset style="margin-top:2em">
<div class="writingrules">
<p>Уважаеми посетители на сайта, всеки е добре дошъл да изкаже мнението си относно дадено произведение. Имайте предвид, че модераторите ще изтрият коментара или част от него, ако той съдържа обидни и груби нападки към другите, както и ако рекламира собствени възгледи, които не са в контекста на произведението. Ако сте съгласни с това условие, моля, продължете към изпращането на коментара си.</p>

<p>Задължително е попълването на всички полета, както и писането с кирилица. Коментарите с латиница най-вероятно ще бъдат изтрити.</p>
<p>Спазвайте елементарни правописни правила:</p>
<ul>
	<li>Започвайте изреченията с главна буква;</li>
	<li>Оставяйте <span title="Синоними: шпация, пауза">интервал</span> след препинателния знак, а не преди него!</li>
</ul>
$allowHelp
</div>
	<legend>Нов коментар</legend>
	$textId
	$chunkId
	$replyto
	<div class="form-group">
		<label for="commenttext">Коментар:</label>
		$comment
	</div>
	$reader
	$question
	<div class="form-submit">
		<input type="submit" name="preview" class="btn btn-default" value="Предварителен преглед">
		<input type="submit" name="send" class="btn btn-success" value="Пращане">
	</div>
</fieldset>
<div id="postform-preview" class="well" style="display:none"></div>
</form>
EOS;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @param string $order
	 */
	private function makeAllComments($limit = 0, $offset = 0, $order = null) {
		$sql = $this->makeSqlQuery($limit, $offset, $order);
		$res = $this->db->query($sql);
		if ($this->db->numRows($res) == 0) {
			return '';
		}

		$params = [
			'pager'    => new Pager([
				'page'  => $this->lpage,
				'limit' => $this->llimit,
				'total' => $this->db->getCount(DBT_COMMENT . ' c', $this->wheres[$this->showMode]),
			]),
			'route' => 'texts_comments',
			'current_route' => 'texts_comments',
			'route_params' => [],
		];
		if ( ! empty($this->username)) {
			$params['route'] = 'user_comments';
			$params['route_params'] = ['username' => $this->username];
		}
		$pagelinks = $this->controller->renderView('App::pager.html.twig', $params);

		$c = '';
		while ($row = $this->db->fetchAssoc($res)) {
			$row['edit'] = $this->showMode == -1;
			$c .= $this->makeComment($row);
		}

		$rssLink = empty($this->username)
			? $this->getInlineRssLink('texts_comments', ['_format' => 'rss'])
			: '';

		return $rssLink . $pagelinks . $c . $pagelinks;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @param string $order
	 */
	private function makeSqlQuery($limit = 0, $offset = 0, $order = null) {
		if ( is_null($order) ) { $order = $this->sortOrder; }
		$key = $this->wheres[$this->showMode];
		if ( ! empty($this->textId) ) {
			$key['c.text_id'] = $this->textId;
			$title = $this->db->getFields(DBT_TEXT, ['id' => $this->textId], 'title');
			$this->title .= ' за „'.$title.'“';
		}
		$qa = [
			'SELECT' => 'c.id',
			'FROM' => DBT_COMMENT .' c',
			'WHERE' => $key,
			'ORDER BY' => "`time` $order",
			'LIMIT' => [$offset, $limit]
		];
		$res = $this->db->extselect($qa);
		$ids = [];
		while ($row = $this->db->fetchRow($res)) {
			$ids[] = $row[0];
		}

		$qa = [
			'SELECT' => 'c.*, t.id textId, t.title textTitle,
				GROUP_CONCAT(DISTINCT a.name ORDER BY aof.pos SEPARATOR ", ") author,
				tr.rating, tr.date ratingdate',
			'FROM' => DBT_COMMENT .' c',
			'LEFT JOIN' => [
				DBT_TEXT .' t' => 'c.text_id = t.id',
				DBT_AUTHOR_OF .' aof' => 't.id = aof.text_id',
				DBT_PERSON .' a' => 'aof.person_id = a.id',
				DBT_TEXT_RATING .' tr' => 'tr.text_id = t.id AND tr.user_id = c.user_id',
			],
			'WHERE' => empty($ids) ? ['FALSE'] : ['c.id IN ('.implode(',', $ids).')'],
			'GROUP BY' => 'c.id',
			'ORDER BY' => "`time` $order",
		];

		return $this->db->extselectQ($qa);
	}

	private function initData() {
		if ($this->initDone) {
			return true;
		}
		$this->initDone = true;
		$this->work = $this->controller->em()->getTextRepository()->find($this->textId);
		if ( empty($this->work) ) {
			$this->addMessage("Не съществува текст с номер <strong>$this->textId</strong>.", true);
			return false;
		}
		$this->title .= ' за „'.
			$this->makeSimpleTextLink($this->work->getTitle(), $this->textId) .'“';
		$this->title .= $this->makeFromAuthorSuffix($this->work);

		return true;
	}

}
