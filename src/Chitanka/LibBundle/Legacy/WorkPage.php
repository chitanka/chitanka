<?php
namespace Chitanka\LibBundle\Legacy;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Util\Number;
use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\File;
use Chitanka\LibBundle\Entity\User;
use Chitanka\LibBundle\Entity\WorkEntry;
use Chitanka\LibBundle\Pagination\Pager;

class WorkPage extends Page {

	const
		DEF_TMPFILE = '',
		DB_TABLE = DBT_WORK,
		DB_TABLE2 = DBT_WORK_MULTI,
		FF_COMMENT = 'comment',
		FF_EDIT_COMMENT = 'editComment',
		FF_VIEW_LIST = 'vl',
		FF_SUBACTION = 'status',
		FF_LQUERY = 'wq',
		MAX_SCAN_STATUS = 2,
		STATUS_0 = 0,
		STATUS_1 = 1,
		STATUS_2 = 2,
		STATUS_3 = 3,
		STATUS_4 = 4,
		STATUS_5 = 5,
		STATUS_6 = 6,
		STATUS_7 = 7;

	protected $action = 'work';
	protected $defViewList = 'work';
	protected $defListLimit = 50;
	protected $maxListLimit = 500;

	private
		$tabs = array('Самостоятелна подготовка', 'Работа в екип'),
		$tabImgs = array('singleuser', 'multiuser'),
		$tabImgAlts = array('сам', 'екип'),
		$statuses = array(
			self::STATUS_0 => 'Планира се',
			self::STATUS_1 => 'Сканира се',
			self::STATUS_2 => 'За корекция',
			self::STATUS_3 => 'Коригира се',
			self::STATUS_4 => 'Иска се SFB',
			self::STATUS_5 => 'Чака проверка',
			self::STATUS_6 => 'Проверен',
			self::STATUS_7 => 'За добавяне',
		),
		$viewLists = array(
			'work' => 'списъка на подготвяните произведения',
			'contrib' => 'списъка на помощниците',
			'listonly' => '',
		),
		$viewTypes = array(
			'all' => 'Всички',
			'my' => 'Мое участие',
			'waiting' => 'Търси се коректор',
		),
		$progressBarWidth = '20',

		$fileWhiteList = array(
			'txt', 'sfb', 'fb2',
			'zip', 'rar',
			'7z', 'gz', 'tar', 'tgz', 'bz2',
			'odt', 'pdf', 'doc', 'rtf', 'djvu',
			'jpg', 'png', 'gif',
		);


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Работно ателие';

		$this->tmpDir = 'todo/';
		$this->absTmpDir = $this->container->getParameter('kernel.root_dir') . '/../web/'.$this->tmpDir;

		$this->subaction = $this->request->value( self::FF_SUBACTION, '', 1 );

		$this->entryId = (int) $this->request->value('id');
		$this->workType = (int) $this->request->value('workType', 0, 3);
		$this->btitle = $this->request->value('title');
		$this->author = $this->request->value('author');
		$this->status = (int) $this->request->value('entry_status');
		$this->progress = Number::normInt($this->request->value('progress'), 100, 0);
		$this->is_frozen = $this->request->checkbox('is_frozen');
		$this->delete = $this->request->checkbox('delete');
		$this->scanuser = (int) $this->request->value('user', $this->user->getId());
		$this->scanuser_view = $this->request->value('user');
		$this->comment = $this->request->value(self::FF_COMMENT);
		$this->comment = strtr($this->comment, array("\r"=>''));
		$this->tmpfiles = $this->request->value('tmpfiles', self::DEF_TMPFILE);
		$this->tfsize = $this->request->value('tfsize');
		$this->editComment = $this->request->value(self::FF_EDIT_COMMENT);

		$this->uplfile = $this->makeUploadedFileName();
		$this->uplfile = $this->escapeBlackListedExt($this->uplfile);

		$this->searchQuery = $this->request->value(self::FF_LQUERY);

		$this->form = $this->request->value('form');
		$this->bypassExisting = (int) $this->request->value('bypass', 0);
		$this->date = date('Y-m-d H:i:s');
		$this->rowclass = null;
		$this->showProgressbar = true;
		$this->viewList = $this->request->value(self::FF_VIEW_LIST,
			$this->defViewList, null, $this->viewLists);

		foreach (array(self::STATUS_4, self::STATUS_5, self::STATUS_6, self::STATUS_7) as $st) {
			$this->viewTypes["st-$st"] = $this->statuses[$st];
		}

		if ( !empty($this->subaction) && !empty($this->viewTypes[$this->subaction]) ) {
			$this->title .= ' — ' . $this->viewTypes[$this->subaction];
		} else if ( ! empty( $this->scanuser_view ) ) {
			$this->setScanUserView($this->scanuser_view);
			$this->title .= ' — ' . $this->data_scanuser_view->getUsername();
		}
		$this->multidata = array();

		$this->initPaginationFields();
	}


	public function setScanUserView($user)
	{
		$this->scanuser_view = $user;
		$this->data_scanuser_view = $this->findUser($user);
	}

	private function findUser($user)
	{
		$userRepo = $this->controller->getRepository('User');
		return is_numeric($user) ? $userRepo->find($user) : $userRepo->findByUsername($user);
	}

	protected function processSubmission() {
		if ( !empty($this->entryId) &&
				!$this->thisUserCanEditEntry($this->entryId, $this->workType) ) {
			$this->addMessage('Нямате права да редактирате този запис.', true);

			return $this->makeLists();
		}
		switch ($this->workType) {
			case 0: return $this->updateMainUserData();
			case 1: return $this->updateMultiUserData();
		}
	}


	protected function updateMainUserData() {
		if ( empty($this->btitle) ) {
			$this->addMessage('Не сте посочили заглавие на произведението.', true);

			return $this->makeForm();
		}
		$this->btitle = String::my_replace($this->btitle);

		if ($this->entryId == 0) { // check if this text exists in the library
			$this->scanuser_view = 0;
			if ( ! $this->bypassExisting) {
				// TODO does not work if there are more than one titles with the same name
				$texts = $this->controller->getRepository('Text')->findBy(array('title' => $this->btitle));
				foreach ($texts as $text) {
					if ($text->getAuthorNames() == $this->author) {
						$wl = $this->makeSimpleTextLink($text->getTitle(), $text->getId());
						$this->addMessage('В библиотеката вече съществува произведение'.
							$this->makeFromAuthorSuffix($text) .
							" със същото заглавие: <div class='standalone'>$wl.</div>", true);
						$this->addMessage('Повторното съхраняване ще добави вашия запис въпреки горното предупреждение.');
						$this->bypassExisting = 1;

						return $this->makeForm();
					}
				}
				$key = array('title' => $this->btitle, 'deleted_at IS NULL');
				if ($this->db->exists(self::DB_TABLE, $key)) {
					$this->addMessage('Вече се подготвя произведение със същото заглавие', true);
					$this->addMessage('Повторното съхраняване ще добави вашия запис въпреки горното предупреждение.');
					$this->bypassExisting = 1;

					return $this->makeWorkList(0, 0, null, false, $key) . $this->makeForm();
				}
			}
		}

		if ( $this->entryId == 0 ) {
			$id = $this->db->autoincrementId(self::DB_TABLE);
			$this->uplfile = preg_replace('/^0-/', "$id-", $this->uplfile);
		} else {
			$id = $this->entryId;
		}
		$set = array(
			'id' => $id,
			'type' => in_array($this->status, array(self::STATUS_4)) ? 1 : $this->workType,
			'title'=>$this->btitle,
			'author'=> strtr($this->author, array(';'=>',')),
			'user_id'=>$this->scanuser,
			'comment' => $this->pretifyComment($this->comment),
			'date'=>$this->date,
			'is_frozen' => $this->is_frozen,
			'status'=>$this->status,
			'progress' => $this->progress,
			'tmpfiles' => self::rawurlencode($this->tmpfiles),	#strpos($this->tmpfiles, '%') === false ? $this->tmpfiles : rawurldecode($this->tmpfiles),
			'tfsize' => $this->tfsize
		);
		if ($this->userIsAdmin()) {
			$set += array(
				'admin_status' => $this->request->value('admin_status'),
				'admin_comment' => $this->request->value('admin_comment'),
			);
		}

		$key = array('id' => $this->entryId);
		if ($this->delete && $this->userIsAdmin()) {
			$set += array('deleted_at' => new \DateTime, 'is_frozen' => 0);
			$this->db->update(self::DB_TABLE, $set, $key);
			if ( $this->isMultiUser($this->workType) ) {
				$this->db->update(self::DB_TABLE2, array('deleted_at' => new \DateTime), array('entry_id' => $this->entryId));
			}
			$this->addMessage("Произведението „{$this->btitle}“ беше махнато от списъка.");
			$this->deleteEntryFiles($this->entryId);
			$this->scanuser_view = null;

			return $this->makeLists();
		}

		if ( $this->handleUpload() && !empty($this->uplfile) ) {
			$set['uplfile'] = $this->uplfile;
			//if ( $this->isMultiUser() ) {
				$set['tmpfiles'] = $this->makeTmpFilePath(self::rawurlencode($this->uplfile));
				$set['tfsize'] = Legacy::int_b2m(filesize($this->absTmpDir . $this->uplfile));
			//}
		}
		$this->db->update(self::DB_TABLE, $set, $this->entryId);
		$msg = $this->entryId == 0
			? 'Произведението беше добавено в списъка с подготвяните.'
			: 'Данните за произведението бяха обновени.';
		$this->scanuser_view = 0;
		$this->addMessage($msg);

		return $this->makeLists();
	}


	protected function updateMultiUserData() {
		if ( $this->thisUserCanDeleteEntry() && $this->form != 'edit' ) {
			return $this->updateMainUserData();
		}

		return $this->updateMultiUserDataForEdit();
	}


	protected function updateMultiUserDataForEdit() {
		$pkey = array('id' => $this->entryId);
		$key = array('entry_id' => $this->entryId, 'user_id' => $this->user->getId());
		if ( empty($this->editComment) ) {
			$this->addMessage('Въвеждането на коментар е задължително.', true);

			return $this->buildContent();
		}
		$this->editComment = $this->pretifyComment($this->editComment);
		$set = array(
			'entry_id' => $this->entryId,
			'user_id' => $this->user->getId(),
			'comment' => $this->editComment,
			'date' => $this->date,
			'progress' => $this->progress,
			'is_frozen' => $this->is_frozen,
			'deleted_at = null',
		);
		if ($this->request->value('uplfile') != '') {
			$set['uplfile'] = $this->request->value('uplfile');
			$set['filesize'] = $this->request->value('filesize');
		}
		if ( $this->handleUpload() && !empty($this->uplfile) ) {
			$set['uplfile'] = $this->uplfile;
		}
		if ($this->db->exists(self::DB_TABLE2, $key)) {
			$this->db->update(self::DB_TABLE2, $set, $key);
			$msg = 'Данните бяха обновени.';
		} else {
			$this->db->insert(self::DB_TABLE2, $set);
			$msg = 'Току-що се включихте в подготовката на произведението.';
			$this->informScanUser($this->entryId);
		}
		$this->addMessage($msg);
		// update main entry
		$set = array(
			'date' => $this->date,
			'status' => $this->isEditDone()
				? ( $this->isReady() ? self::STATUS_6 : self::STATUS_5 )
				: self::STATUS_3
		);
		$this->db->update(self::DB_TABLE, $set, $pkey);

		return $this->makeLists();
	}


	protected function handleUpload() {
		$tmpfile = $this->request->fileTempName('file');
		if ( !is_uploaded_file($tmpfile) ) {
			return false;
		}
		$dest = $this->absTmpDir . $this->uplfile;
		if ( file_exists($dest) ) {
			rename($dest, $dest .'-'. time());
		}
		if ( !move_uploaded_file($tmpfile, $dest) ) {
			$this->addMessage("Файлът не успя да бъде качен. Опитайте пак!", true);

			return false;
		}

		// copy local file if there is a remote workroom
		if ( $remote = Setup::setting('workroom_remote') ) {
			$com = sprintf('scp "%s" %s', $dest, $remote);#echo $com;
			shell_exec($com);
		}

		$this->addMessage("Файлът беше качен. Благодарим ви за положения труд!");

		return true;
	}


	protected function makeUploadedFileName() {
		$filename = $this->request->fileName('file');
		if ( empty($filename) ) {
			return '';
		}

		$filename = Char::cyr2lat($filename);
		$filename = strtr($filename, array(' ' => '_'));

		return $this->entryId
			. '-' . date('Ymd-His')
			. '-' . $this->user->getUsername()
			. '-' . File::cleanFileName($filename, false);
	}


	protected function buildContent() {
		if ($this->viewList == 'listonly') {
			return $this->makeWorkList();
		}
		$content = $this->makeUserGuideLink();
		if ($this->subaction == 'edit'/* && $this->userCanAddEntry()*/) {
			if ($this->entryId) {
				$this->initData();
			}
			$content .= $this->makeForm();
		} else {
			$this->addRssLink();
			$content .= $this->getInlineRssLink('workroom_rss') . $this->makeLists();
		}

		return $content;
	}


	protected function makeUserGuideLink() {
		return '<div class="float-right"><a class="act-info" href="http://wiki.chitanka.info/Workroom" title="Наръчник за работното ателие">Наръчник за работното ателие</a></div>';
	}

	protected function makeLists() {
		$o = $this->makePageHelp()
			. '<div class="standalone buttonmenu">'
				. $this->makeNewEntryLink()
			. '</div>'
			. '<dl class="menu"><dt>Преглед</dt><dd>'
				. $this->makeViewWorksLinks()
			. '</dd></dl>'
			. $this->makeSearchForm();

		if ($this->viewList == 'work') {
			$o .= $this->makeWorkList($this->llimit, $this->loffset);
			$o .= sprintf('<div class="standalone"><hr><a href="%s">Списък на помощниците</a></div>',
				$this->controller->generateUrl('workroom_contrib'));
		} else {
			$o .= $this->makeContribList();
		}

		return $o;
	}


	protected function makeSearchForm()
	{
		$label = $this->out->label('Търсене на: ', self::FF_LQUERY);
		$search = $this->out->textField(self::FF_LQUERY, '', $this->searchQuery, 50, 100, 0, 'Търсене из подготвяните произведения', array('class' => 'search'));
		$action = $this->controller->generateUrl('workroom');

		return <<<EOS

<form action="$action" method="get"><div class="standalone">
	$label$search
	<input type="submit" value="Показване">
</div></form>
EOS;
	}

	public function makeWorkList(
			$limit = 0,
			$offset = 0,
			$order = null,
			$showPageLinks = true,
			$where = array())
	{
		$q = $this->makeSqlQuery($limit, $offset, $order, $where);
		$l = $this->db->iterateOverResult($q, 'makeWorkListItem', $this, true);
		if ( empty($l) ) {
			return '<p class="standalone emptylist"><strong>Няма подготвящи се произведения.</strong></p>';
		}
		if ($showPageLinks) {
			$params = array(
				self::FF_SUBACTION => $this->subaction
			);
			if ($this->searchQuery) $params[self::FF_LQUERY] = $this->searchQuery;
			if ($this->scanuser_view) $params['user'] = $this->scanuser_view;
			$pagelinks = $showPageLinks ? $this->controller->renderView('LibBundle::pager.html.twig', array(
				'pager'    => new Pager(array(
					'page'  => $this->lpage,
					'limit' => $this->llimit,
					'total' => $this->db->getCount(self::DB_TABLE, $this->makeSqlWhere('', $where)),
				)),
				'current_route' => 'workroom',
				'route_params' => $params,
			)) : '';
		} else {
			$pagelinks = '';
		}
		$adminStatus = $this->userIsAdmin() ? '<th title="Администраторски статус"></th>' : '';

		return <<<EOS
<table class="content sortable">
<thead>
	<tr>
		<th>Дата</th>
		$adminStatus
		<th title="Тип на записа"></th>
		<th title="Информация"></th>
		<th title="Коментари към записа"></th>
		<th title="Файл"></th>
		<th style="width: 25%">Заглавие</th>
		<th>Автор</th>
		<th>Етап на работата</th>
		<th>Потребител</th>
	</tr>
</thead>
<tbody>
$l
</tbody>
</table>
$pagelinks
EOS;
	}


	public function makeSqlQuery(
		$limit = 0, $offset = 0, $order = null, $where = array() )
	{
		$qa = array(
			'SELECT' => 'w.*, DATE(date) ddate, u.username, u.email, u.allowemail, num_comments',
			'FROM' => self::DB_TABLE. ' w',
			'LEFT JOIN' => array(
				DBT_USER .' u' => 'w.user_id = u.id',
				'thread ct' => 'w.comment_thread_id = ct.id',
			),
			'WHERE' => $this->makeSqlWhere('w', $where),
			'ORDER BY' => 'date DESC, w.id DESC',
			'LIMIT' => array($offset, $limit)
		);

		return $this->db->extselectQ($qa);
	}


	public function makeSqlWhere($pref = '', $base = array()) {
		$w = (array) $base;
		if ( !empty($pref) ) $pref .= '.';
		$showuser = 0;
		if ($this->subaction == 'my') {
			$showuser = $this->user->getId();
		} else if ( ! empty($this->scanuser_view) ) {
			$user = $this->findUser($this->scanuser_view);
			$showuser = $user ? $user->getId() : null;
		}
		if ( ! empty($showuser) ) {
			$entry_idQ = $this->db->selectQ(self::DB_TABLE2, array('user_id' => $showuser, 'deleted_at IS NULL'), 'entry_id');
			$ors = array(
				$pref.'user_id' => $showuser,
				$pref.'id IN ('. $entry_idQ .')');
			$w = array_merge($w, array($ors));
		} else if ($this->subaction == 'waiting') {
			$w = array('type' => 1, 'status' => self::MAX_SCAN_STATUS);
		} else if ( strpos($this->subaction, 'st-') !== false ) {
			$w = array('status' => str_replace('st-', '', $this->subaction));
		} else if ( ! empty($this->searchQuery) ) {
			$w[] = array(
				$pref.'title' => array('LIKE', "%$this->searchQuery%"),
				$pref.'author' => array('LIKE', "%$this->searchQuery%"),
			);
		}

		$w[] = $pref.'deleted_at IS NULL';

		return $w;
	}


	public function makeWorkListItem($dbrow, $astable = true) {
		extract($dbrow);
		$author = strtr($author, array(', '=>','));
		$author = $this->makeAuthorLink($author);
		$userlink = $this->makeUserLinkWithEmail($username, $email, $allowemail);
		$info = empty($comment) ? ''
			: $this->makeExtraInfo($comment, isset($expandinfo) && $expandinfo);
		$title = "<i>$title</i>";
		$file = '';
		if ( ! empty($tmpfiles) ) {
			$file = $this->makeFileLink($tmpfiles);
		} else if ( ! empty($uplfile) ) {
			$file = $this->makeFileLink($uplfile);
		}
		$entryLink = $this->controller->generateUrl('workroom_entry_edit', array('id' => $id));
		$commentsLink = $num_comments ? sprintf('<a href="%s#fos_comment_thread" title="Коментари" class="comments">%s</a>', $entryLink, $num_comments) : '';
		$title = sprintf('<a href="%s" title="Към страницата за редактиране">%s</a>', $entryLink, $title);
		$this->rowclass = $this->out->nextRowClass($this->rowclass);
		$st = $progress > 0
			? $this->makeProgressBar($progress)
			: $this->makeStatus($status);
		$extraclass = $this->user->getId() == $user_id ? ' hilite' : '';
		if ($is_frozen) {
			$sis_frozen = '<span title="Подготовката е замразена">(замразена)</span>';
			$extraclass .= ' is_frozen';
		} else {
			$sis_frozen = '';
		}
		if ( $this->isMultiUser($type) ) {
			$mdata = $this->getMultiEditData($id);
			$musers = '';
			foreach ($mdata as $muser => $data) {
				$uinfo = $this->makeExtraInfo("$data[comment] ($data[progress]%)");
				$ufile = empty( $data['uplfile'] )
					? ''
					: $this->makeFileLink($data['uplfile'], $data['username']);
				if ($muser == $user_id) {
					$userlink = "$userlink $uinfo $ufile";
					continue;
				}
				$ulink = $this->makeUserLinkWithEmail($data['username'],
					$data['email'], $data['allowemail']);
				if ($data['is_frozen']) {
					$ulink = "<span class='is_frozen'>$ulink</span>";
				}
				$musers .= "\n\t<li>$ulink $uinfo $ufile</li>";
				$extraclass .= $this->user->getId() == $muser ? ' hilite' : '';
			}
			if ( !empty($mdata) ) {
				$userlink = "<ul class='simplelist'>\n\t<li>$userlink</li>$musers</ul>";
				if ( isset($showeditors) && $showeditors ) {
					$userlink .= $this->makeEditorList($mdata);
				}
			} else if ( $status == self::MAX_SCAN_STATUS ) {
				$userlink .= ' (<strong>очакват се коректори</strong>)';
			}
		}
		$umarker = $this->_getUserTypeMarker($type);

		$adminFields = $this->userIsAdmin() ? $this->makeAdminFieldsForTable($dbrow) : '';

		if ($astable) {
			return <<<EOS

	<tr class="$this->rowclass$extraclass" id="e$id">
		<td class="date" title="$date">$ddate</td>
		$adminFields
		<td>$umarker</td>
		<td>$info</td>
		<td>$commentsLink</td>
		<td>$file</td>
		<td>$title</td>
		<td>$author</td>
		<td>$st $sis_frozen</td>
		<td>$userlink</td>
	</tr>
EOS;
		}
		$time = !isset($showtime) || $showtime ? "Дата: $date<br>" : '';
		$titlev = !isset($showtitle) || $showtitle ? $title : '';
		return <<<EOS

		<p>$time
		$info $titlev<br>
		<strong>Автор:</strong> $author<br>
		<strong>Етап:</strong> $st $sis_frozen<br>
		Подготвя се от $userlink
		</p>
EOS;
	}

	private function makeAdminFieldsForTable($dbrow)
	{
		$comment = htmlspecialchars(nl2br($dbrow['admin_comment']));
		$class = htmlspecialchars(str_replace(' ', '-', $dbrow['admin_status']));
		return <<<HTML
<td>
<span class="tooltip workroom-$class" title="$comment">
	<span>$dbrow[admin_status]</span>
</span>
</td>
HTML;
	}

	protected function _getUserTypeMarker($type)
	{
		return "<span class='{$this->tabImgs[$type]}'><span>{$this->tabImgAlts[$type]}</span></span>";
	}


	public function makeStatus($code) {
		return "<span class='progress progress-$code'>{$this->statuses[$code]}</span>";
	}


	public function makeExtraInfo($info, $expand = false) {
		$info = str_replace("\n\n", "\n——\n", $info);
		$info = strtr($info, array(
			"\n"   => '<br>',
			"\r"   => '',
		));
		if ($expand) {
			return $info;
		}
		$info = String::myhtmlspecialchars($info);

		return "<span class='act-info tooltip' title='$info'><span>Инфо</span></span>";
	}


	public function makeProgressBar($progressInPerc) {
		$perc = $progressInPerc .'%';
		if ( !$this->showProgressbar ) {
			return $perc;
		}
		$bar = str_repeat(' ', $this->progressBarWidth);
		$bar = substr_replace($bar, $perc, $this->progressBarWidth/2-1, strlen($perc));
		$curProgressWidth = ceil($this->progressBarWidth * $progressInPerc / 100);
		// done bar end
		$bar = substr_replace($bar, '</span>', $curProgressWidth, 0);
		$bar = strtr($bar, array(' ' => '&#160;'));

		return "<pre style='display:inline'><span class='progressbar'><span class='done'>$bar</span></pre>";
	}


	protected function makeNewEntryLink() {
		if ( !$this->userCanAddEntry() ) {
			return '';
		}

		return sprintf('<a href="%s">Подготовка на ново произведение</a>',
			$this->controller->generateUrl('workroom_entry_new'));

	}

	protected function makeViewWorksLinks() {
		$links = array();
		foreach ($this->viewTypes as $type => $title) {
			$class = $this->subaction == $type ? 'selected' : '';
			$links[] = sprintf('<li><a href="%s" class="%s" title="Преглед на произведенията по критерий „%s“">%s</a></li>',
				$this->controller->generateUrl('workroom', array(
					self::FF_SUBACTION => $type
				)),
				$class, $title, $title);
		}

		return '<ul>'. implode("\n", $links) .'</ul>';
	}


	protected function makeForm() {
		$this->title .= ' — '.(empty($this->entryId) ? 'Добавяне' : 'Редактиране');
		$helpTop = empty($this->entryId) ? $this->makeAddEntryHelp() : '';
		$tabs = '';
		foreach ($this->tabs as $type => $text) {
			$text = "<span class='{$this->tabImgs[$type]}'>$text</span>";
			$class = '';
			if ($this->workType == $type) {
				$class = ' selected';
			} else if ($this->thisUserCanDeleteEntry()) {
				$route = 'workroom_entry_new';
				$params = array('workType' => $type);
				if ($this->entryId) {
					$params['id'] = $this->entryId;
					$route = 'workroom_entry_edit';
				}
				$text = sprintf('<a href="%s">%s</a>',
					$this->controller->generateUrl($route, $params),
					$text);
			}
			$tabs .= "\n<div class='tab$class'>$text</div>";
		}
		if ( $this->isSingleUser($this->workType) ) {
			$editFields = $this->makeSingleUserEditFields();
			$extra = '';
		} else {
			$editFields = $this->makeMultiUserEditFields();
			#$extra = $this->isScanDone() ? $this->makeMultiEditInput() : '';
			$extra = $this->makeMultiEditInput();
		}
		if ( $this->thisUserCanDeleteEntry() ) {
			$title = $this->out->textField('title', '', $this->btitle, 50);
			$author = $this->out->textField('author', '', $this->author, 50, 255,
				0, 'Ако авторите са няколко, ги разделете със запетаи');
			$comment = $this->out->textarea(self::FF_COMMENT, '', $this->comment, 10, 80);
			$delete = empty($this->entryId) || !$this->userIsAdmin() ? ''
				: '<div class="error" style="margin-bottom:1em">'.
				$this->out->checkbox('delete', '', false, 'Изтриване на записа') .
				' (напр., ако произведението вече е добавено в библиотеката)</div>';
			$button = $this->makeSubmitButton();
			if ($this->status == self::STATUS_7 && !$this->userCanSetStatus(self::STATUS_7)) {
				$button = $delete = '';
			}
		} else {
			$title = $this->btitle;
			$author = $this->author;
			$comment = $this->comment;
			$button = $delete = '';
		}
		$lcomment = $this->out->label('Коментар:', self::FF_COMMENT);
		$helpBot = $this->isSingleUser($this->workType) ? $this->makeSingleUserHelp() : '';
		$scanuser = $this->out->hiddenField('user', $this->scanuser);
		$entry = $this->out->hiddenField('id', $this->entryId);
		$workType = $this->out->hiddenField('workType', $this->workType);
		$bypass = $this->out->hiddenField('bypass', $this->bypassExisting);
		$action = $this->controller->generateUrl('workroom');
		$this->addJs($this->createCommentsJavascript($this->entryId));

		$corrections = '';
		if ($this->canShowCorrections()) {
			// same domain as main site - for ajax
			$newFile = str_replace('http://static.chitanka.info', '', $this->tmpfiles);
			$dmpPath = $this->container->getParameter('assets_base_urls') . '/js/diff_match_patch.js';
			$corrections = <<<CORRECTIONS
<fieldset>
	<legend>Корекции</legend>
	<button onclick="jQuery(this).hide(); showWorkroomDiff('#corrections')">Показване</button>
	<pre id="corrections" style="display: none; white-space: pre-wrap; /* css-3 */ white-space: -moz-pre-wrap !important; /* Mozilla, since 1999 */ white-space: -pre-wrap; /* Opera 4-6 */ white-space: -o-pre-wrap; /* Opera 7 */ word-wrap: break-word; /* Internet Explorer 5.5+ */">
	Зареждане...
	</pre>
</fieldset>
<script src="$dmpPath"></script>
<script>
function showWorkroomDiff(target) {
	function doDiff(currentContent, newContent) {
		var dmp = new diff_match_patch();
		var d = dmp.diff_main(currentContent, newContent);
		dmp.diff_cleanupSemantic(d);
		var ds = dmp.diff_prettyHtml(d);
		var out = '';
		var sl = ds.split('<br>');
		var inIns = inDel = false;
		var prevLine = 1;
		for ( var i = 0, len = sl.length; i < len; i++ ) {
			if ( sl[i].indexOf('<ins') != -1 ) inIns = true;
			if ( sl[i].indexOf('<del') != -1 ) inDel = true;
			if ( inIns || inDel ) {
				var line = i+1;
				if (prevLine < line-1) {
					out += '		<span style="opacity: .1">[…]</span><br>';
				}
				out += '<span style="color: blue">' + line + ':</span>	' + sl[i] +'<br>';
				prevLine = line;
			}
			if ( sl[i].indexOf('</ins>') != -1 ) inIns = false;
			if ( sl[i].indexOf('</del>') != -1 ) inDel = false;
		}

		out = out.replace(/&para;/g, '<span style="opacity:.1">¶</span>');

		$(target).html(out);
	}
	$(target).show();
    $.get('$newFile', function(newContent) {
		// TODO find a better way to find the current text source
		var m = newContent.match(/(http:\/\/chitanka.info\/(book|text)\/\d+)/);
		if (m) {
			var curContentUrl = m[1]+'.sfb';
			$.get(curContentUrl, function(curContent){
				doDiff(curContent, newContent);
			});
		} else {
			$(target).text('Съдържанието на източника не беше открито.');
		}
	});
}
</script>
CORRECTIONS;
		}

		$adminFields = $this->userIsAdmin() ? $this->makeAdminOnlyFields() : '';

		return <<<EOS

$helpTop
<div class="tabbedpane" style="margin:1em auto">$tabs
<div class="tabbedpanebody">
<form action="$action" method="post" enctype="multipart/form-data">
<div style="margin:1em 0.3em 0.4em 0.3em">
	$scanuser
	$entry
	$workType
	$bypass
	<table><tr>
		<td style="width:6em"><label for="title">Заглавие:</label></td>
		<td>$title</td>
	</tr><tr>
		<td><label for="author">Автор:</label></td>
		<td>$author</td>
	</tr>
	<tr style="vertical-align:top">
		<td>$lcomment</td>
		<td>$comment</td>
	</tr>
	$editFields
	$adminFields
	</table>
	$delete
	<div class="form-submit">$button</div>
</div>
</form>
$extra
</div>
$corrections
</div>

<div id="fos_comment_thread"></div>

<div id="helpBottom">
$helpBot
</div>
EOS;
	}

	private function createCommentsJavascript($entry)
	{
		if (empty($entry)) {
			return '';
		}
		$user = $this->controller->getRepository('User')->find($this->scanuser);
		$threadUrl = $this->controller->generateUrl('fos_comment_post_threads');
		$commentJs = $this->container->getParameter('assets_base_urls') . '/bundles/lib/js/comments.js';
		return <<<JS
var fos_comment_thread_id = 'WorkEntry:$entry';

// api base url to use for initial requests
var fos_comment_thread_api_base_url = '$threadUrl';

// Snippet for asynchronously loading the comments
(function() {
    var fos_comment_script = document.createElement('script');
    fos_comment_script.async = true;
    fos_comment_script.src = '$commentJs';
    fos_comment_script.type = 'text/javascript';

    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(fos_comment_script);
})();

$(document)
	.on('fos_comment_before_load_thread', '#fos_comment_thread', function (event, data) {
		setTimeout(function(){
			$("#fos_comment_comment_cc").val("{$user->getUsername()}");
		}, 2000);
	})
	.on('fos_comment_show_form', '#fos_comment_thread', function (data) {
		var button = $(data.target);
		button.next().find('input[name="fos_comment_comment[cc]"]').val(button.data("name"));
	})
	.on('fos_comment_submitting_form', '#fos_comment_thread', function (event, data) {
		var form = $(event.target);
		if (form.is(".loading")) {
			event.preventDefault();
			return;
		}
		form.addClass("loading").find(":submit").attr("disabled", true);
	})
	.on('fos_comment_submitted_form', '#fos_comment_thread', function (event, data) {
		var form = $(event.target);
		form.removeClass("loading").find(":submit").removeAttr("disabled");
	})
;
JS;
	}

	private function canShowCorrections()
	{
		return strpos($this->btitle, '(корекция)') !== false
			&& strpos($this->tmpfiles, 'chitanka.info') !== false
			&& File::isSFB($this->absTmpDir.basename($this->tmpfiles));
	}

	protected function makeSubmitButton() {
		$submit = $this->out->submitButton('Запис');
		$cancel = sprintf('<a href="%s" title="Към основния списък">Отказ</a>', $this->controller->generateUrl('workroom'));

		return $submit .' &#160; '. $cancel;
	}

	protected function makeSingleUserEditFields() {
		$status = $this->getStatusSelectField($this->status);
		$progress = $this->out->textField('progress', '', $this->progress, 2, 3);
		$is_frozen = $this->out->checkbox('is_frozen', '', $this->is_frozen,
			'Подготовката е спряна за известно време');
		$file = $this->out->fileField('file', '');
		$maxFileSize = $this->out->makeMaxFileSizeField();
		$maxUploadSizeInMiB = Legacy::getMaxUploadSizeInMiB();

		$tmpfiles = $this->out->textField('tmpfiles', '', rawurldecode($this->tmpfiles), 50, 255)
			. ' &#160; '.$this->out->label('Размер: ', 'tfsize') .
				$this->out->textField('tfsize', '', $this->tfsize, 2, 4) .
				'<abbr title="Мегабайта">MiB</abbr>';

		$flink = $this->tmpfiles == self::DEF_TMPFILE ? ''
			: $this->out->link( $this->makeTmpFilePath($this->tmpfiles), String::limitLength($this->tmpfiles)) .
			($this->tfsize > 0 ? " ($this->tfsize&#160;MiB)" : '');

		return <<<EOS
	<tr>
		<td><label for="entry_status">Етап:</label></td>
		<td><select name="entry_status" id="entry_status">$status</select>
		&#160; или &#160;
		$progress<label for="progress">%</label><br>
		$is_frozen
		</td>
	</tr><tr>
		<td><label for="file">Файл:</label></td>
		<td>
		<div>
			$maxFileSize
			$file (макс. $maxUploadSizeInMiB MiB)
		</div>
		<p>или</p>
		<div>
		$tmpfiles
		<div>$flink</div>
		</div>
		</td>
	</tr>
EOS;
	}

	private function makeAdminOnlyFields()
	{
		if (empty($this->entry)) {
			return '';
		}
		$status = $this->out->textField('admin_status', '', $this->entry->getAdminStatus());
		$comment = $this->out->textarea('admin_comment', '', $this->entry->getAdminComment(), 3, 80);
		return <<<FIELDS
	<tr>
		<td>Админ.&nbsp;статус:</td>
		<td>$status</td>
	</tr>
	<tr>
		<td>Админ.&nbsp;коментар:</td>
		<td>$comment</td>
	</tr>
FIELDS;
	}

	protected function getStatusSelectField($selected, $max = null)
	{
		$statuses = $this->statuses;
		foreach (array(self::STATUS_6, self::STATUS_7) as $status) {
			if ( ! $this->userCanSetStatus($status) ) {
				unset( $statuses[$status] );
			}
		}
		$field = '';
		foreach ($statuses as $code => $text) {
			if ( !is_null($max) && $code > $max) break;
			$sel = $selected == $code ? ' selected="selected"' : '';
			$field .= "\n\t<option value='$code'$sel class='progress progress-$code'>$text</option>";
		}

		return $field;
	}


	protected function makeMultiUserEditFields() {
		$scanInput = $this->makeMultiScanInput();

		return <<<EOS
	<tr>
	<td colspan="2">
	$scanInput
	</td>
	</tr>
EOS;
	}


	protected function makeMultiScanInput() {
		$is_frozenLabel = 'Подготовката е спряна за известно време';
		$cstatus = $this->status > self::MAX_SCAN_STATUS
			? self::MAX_SCAN_STATUS
			: $this->status;
		if ( $this->thisUserCanDeleteEntry() ) {
			if ( empty($this->multidata) || $this->userIsSupervisor() ) {
				$status = $this->userIsSupervisor()
					? $this->getStatusSelectField($this->status)
					: $this->getStatusSelectField($cstatus, self::MAX_SCAN_STATUS);
				$status = "<select name='entry_status' id='entry_status'>$status</select>";
				$is_frozen = $this->out->checkbox('is_frozen', '', $this->is_frozen, $is_frozenLabel);
			} else {
				$status = $this->statuses[$cstatus]
					. $this->out->hiddenField('entry_status', $this->status);
				$is_frozen = '';
			}
			$tmpfiles = $this->out->textField('tmpfiles', '', rawurldecode($this->tmpfiles), 50, 255);
			$tmpfiles .= ' &#160; '.$this->out->label('Размер: ', 'tfsize') .
				$this->out->textField('tfsize', '', $this->tfsize, 2, 4) .
				'<abbr title="Мегабайта">MiB</abbr>';
		} else {
			$status = $this->statuses[$cstatus];
			$is_frozen = $this->is_frozen ? "($is_frozenLabel)" : '';
			$tmpfiles = '';
		}

		$user = $this->controller->getRepository('User')->find($this->scanuser);
		$ulink = $this->makeUserLinkWithEmail($user->getUsername(),
			$user->getEmail(), $user->getAllowemail());
		$flink = $this->tmpfiles == self::DEF_TMPFILE ? ''
			: $this->out->link( $this->makeTmpFilePath($this->tmpfiles), String::limitLength($this->tmpfiles)) .
			($this->tfsize > 0 ? " ($this->tfsize&#160;MiB)" : '');
		$file = $this->out->fileField('file', '');
		$maxFileSize = $this->out->makeMaxFileSizeField();
		$maxUploadSizeInMiB = Legacy::getMaxUploadSizeInMiB();

		return <<<EOS
	<fieldset>
		<legend>Сканиране и разпознаване ($ulink)</legend>
		<div>
		<label for="entry_status">Етап:</label>
  		$status
		$is_frozen
		</div>
		<div>
		<label for="file">Файл:</label>
		$maxFileSize
		$file (макс. $maxUploadSizeInMiB MiB)
		</div>
		<p>или</p>
		<div>
		<label for="tmpfiles">Външен файл:</label>
		$tmpfiles
		<div>$flink</div>
		</div>
	</fieldset>
EOS;
	}


	protected function makeMultiEditInput() {
		$editorList = $this->makeEditorList();
		$myContrib = $this->isMyContribAllowed() ? $this->makeMultiEditMyInput() : '';

		return <<<EOS
	<fieldset>
		<legend>Коригиране</legend>
		$editorList
		$myContrib
	</fieldset>
EOS;
	}

	protected function isMyContribAllowed() {
		if ($this->userIsSupervisor()) {
			return true;
		}
		if (in_array($this->status, array(self::STATUS_5, self::STATUS_6, self::STATUS_7))) {
			return false;
		}
		if ($this->user->isAnonymous()) {
			return false;
		}
		return true;
	}

	protected function makeMultiEditMyInput() {
		$msg = '';
		if ( empty($this->multidata[$this->user->getId()]) ) {
			$comment = $progress = $uplfile = $filesize = '';
			$is_frozen = false;
			$msg = '<p>Вие също може да се включите в подготовката на текста.</p>';
		} else {
			extract( $this->multidata[$this->user->getId()] );
		}
		$ulink = $this->makeUserLink($this->user->getUsername());
		$button = $this->makeSubmitButton();
		$scanuser = $this->out->hiddenField('user', $this->scanuser);
		$entry = $this->out->hiddenField('id', $this->entryId);
		$workType = $this->out->hiddenField('workType', $this->workType);
		$form = $this->out->hiddenField('form', 'edit');
		$subaction = $this->out->hiddenField(self::FF_SUBACTION, $this->subaction);
		$comment = $this->out->textarea(self::FF_EDIT_COMMENT, '', $comment, 10, 80);
		$lcomment = $this->out->label('Коментар:', self::FF_EDIT_COMMENT);
		$progress = $this->out->textField('progress', '', $progress, 2, 3);
		$is_frozen = $this->out->checkbox('is_frozen', 'is_frozen_e', $this->is_frozen,
			'Корекцията е спряна за известно време');
		$file = $this->out->fileField('file', 'file2');
		$readytogo = $this->userCanMarkAsReady()
			? $this->out->checkbox('ready', 'ready', false, 'Готово е за добавяне')
			: '';
		$action = $this->controller->generateUrl('workroom');

		$remoteFile = $this->out->textField('uplfile', 'uplfile2', rawurldecode($uplfile), 50, 255)
			. ' &#160; '.$this->out->label('Размер: ', 'filesize2') .
				$this->out->textField('filesize', 'filesize2', $filesize, 2, 4) .
				'<abbr title="Мегабайта">MiB</abbr>';

		return <<<EOS

<form action="$action" method="post" enctype="multipart/form-data">
	<fieldset>
		<legend>Моят принос ($ulink)</legend>
		$msg
	$scanuser
	$entry
	$workType
	$form
	$subaction
	<table>
	<tr style="vertical-align:top">
		<td>$lcomment</td>
		<td>$comment</td>
	</tr><tr>
		<td><label for="progress">Напредък:</label></td>
		<td>$progress<label for="progress">%</label> $is_frozen</td>
	</tr><tr>
		<td><label for="file2">Файл:</label></td>
		<td>$file</td>
	</tr></tr>
		<td>или</td>
		<td></td>
	</tr></tr>
		<td><label for="uplfile2">Външен файл:</label></td>
		<td>
			$remoteFile
		</td>
	</tr></tr>
		<td></td><td>$readytogo</td>
	</tr>
	</table>
	<div class="form-submit">$button</div>
	</fieldset>
</form>
EOS;
	}


	protected function makeEditorList($mdata = null) {
		Legacy::fillOnEmpty($mdata, $this->multidata);
		if ( empty($mdata) ) {
			return '<p>Все още никой не се е включил в корекцията на текста.</p>';
		}
		$l = $class = '';
		foreach ($mdata as $edata) {
			extract($edata);
			$class = $this->out->nextRowClass($class);
			$ulink = $this->makeUserLinkWithEmail($username, $email, $allowemail);
			$comment = strtr($comment, array("\n" => "<br>\n"));
			if ( !empty($uplfile) ) {
				$comment .= ' ' . $this->makeFileLink($uplfile, $username, $filesize);
			}
			$progressbar = $this->makeProgressBar($progress);
			if ($is_frozen) {
				$class .= ' is_frozen';
				$progressbar .= ' (замразена)';
			}
			$deleteForm = $this->controller->renderView('LibBundle:Workroom:contrib_delete_form.html.twig', array('contrib' => array('id' => $edata['id'])));

			$l .= <<<EOS

		<tr class="$class deletable">
			<td>$date</td>
			<td>$ulink $deleteForm</td>
			<td>$comment</td>
			<td>$progressbar</td>
		</tr>
EOS;
		}

		return <<<EOS

	<table class="content sortable">
	<caption>Следните потребители обработват текста:</caption>
	<thead>
	<tr>
		<th>Дата</th>
		<th>Потребител</th>
		<th>Коментар</th>
		<th>Напредък</th>
	</tr>
	</thead>
	<tbody>$l
	</tbody>
	</table>
EOS;
	}


	protected function makePageHelp() {
		$regUrl = $this->controller->generateUrl('register');
		$ext = $this->user->isAnonymous() ? "е необходимо първо да се <a href=\"$regUrl\">регистрирате</a> (не се притеснявайте, ще ви отнеме най-много 10–20 секунди, колкото и бавно да пишете). След това се върнете на тази страница и" : '';
		$umarker = $this->_getUserTypeMarker(1);

		return <<<EOS

<p>Тук може да разгледате списък на произведенията, които се подготвят за добавяне в библиотеката.</p>
<p>За да започнете подготовката на нов текст, $ext последвайте връзката „Подготовка на ново произведение“. В случай че нямате възможност сами да сканирате текстове, може да се присъедините към коригирането на заглавията, отбелязани ето така: $umarker (може и да няма такива). Те са достъпни и чрез връзката „{$this->viewTypes['waiting']}“.</p>
<p>Бързината на добавянето на нови текстове в библиотеката зависи както от броя на грешките, останали след сканирането и разпознаването, така и от форма&#768;та на текста. Най-бързо ще бъдат добавяни отлично коригирани текстове, правилно преобразувани във <a href="http://wiki.chitanka.info/SFB">формат SFB</a>.</p>
<p class="error newbooks-notice" style="margin:1em 0">Разрешено е да се добавят само книги, издадени на български преди 2011 г. Изключение се прави за онези текстове, които са пратени от авторите си, както и за фен-преводи.</p>
EOS;
	}


	protected function makeAddEntryHelp() {
		$mainlink = $this->controller->generateUrl('workroom');

		return <<<EOS

<p>Чрез долния формуляр може да добавите ново произведение към <a href="$mainlink">списъка с подготвяните</a>.</p>
<p>Имате възможност за избор между „{$this->tabs[0]}“ (сами ще обработите целия текст) или „{$this->tabs[1]}“ (вие ще сканирате текста, а други потребители ще имат възможността да се включат в коригирането му).</p>
<p>Въведете заглавието и автора и накрая посочете на какъв етап се намира подготовката. Ако още не сте започнали сканирането, изберете „{$this->statuses[self::STATUS_0]}“.</p>
<p>През следващите дни винаги може да промените етапа, на който се намира подготовката на произведението. За тази цел, в основния списък, заглавието ще представлява връзка към страницата за редактиране.</p>
EOS;
	}


	protected function makeSingleUserHelp() {
		return <<<EOS

<p>На тази страница може да променяте данните за произведението.
Най-често ще се налага да обновявате етапа, на който се намира подготовката. Възможно е да посочите напредъка на подготовката и чрез процент, в случай че операциите сканиране, разпознаване и коригиране се извършват едновременно.</p>
<p>Ако подготовката на произведението е замразена, това може да се посочи, като се отметне полето „Подготовката е спряна за известно време“.</p>
EOS;
	}


	protected function makeContribList() {
		$this->rownr = 0;
		$this->rowclass = '';
		$qa = array(
			'SELECT' => 'u.username, COUNT(ut.user_id) count, SUM(ut.size) size',
			'FROM' => DBT_USER_TEXT .' ut',
			'LEFT JOIN' => array(DBT_USER .' u' => 'ut.user_id = u.id'),
			'GROUP BY' => 'ut.user_id',
			'ORDER BY' => 'size DESC',
		);
		$q = $this->db->extselectQ($qa);
		$list = $this->db->iterateOverResult($q, 'makeContribListItem', $this);
		if ( empty($list) ) {
			return '';
		}

		return <<<EOS

	<table class="content sortable">
	<caption>Следните потребители са сканирали или коригирали текстове за библиотеката:</caption>
		<colgroup>
			<col />
			<col align="right" />
			<col align="right" />
		</colgroup>
	<thead>
	<tr>
		<th>№</th>
		<th>Потребител</th>
		<th title="Размер на обработените произведения в мебибайта">Размер (в <abbr title="Кибибайта">KiB</abbr>)</th>
		<th title="Брой на обработените произведения">Брой</th>
	</tr>
	</thead>
	<tbody>$list
	</tbody>
	</table>
EOS;
	}


	public function makeContribListItem($dbrow) {
		$this->rowclass = $this->out->nextRowClass($this->rowclass);
		$ulink = $this->makeUserLink($dbrow['username']);
		$s = Number::formatNumber($dbrow['size'], 0);
		$this->rownr += 1;

		return "\n\t<tr class='$this->rowclass'><td>$this->rownr</td><td>$ulink</td><td>$s</td><td>$count</td></tr>";
	}


	protected function initData() {
		$entry = $this->repo()->find($this->entryId);
		if ($entry == null) {
			throw new NotFoundHttpException("Няма запис с номер $this->entryId.");
		}
		if ($entry->isDeleted() && !$this->userIsAdmin()) {
			throw new NotFoundHttpException("Изтрит запис.");
		}
		$this->btitle = $entry->getTitle();
		$this->author = $entry->getAuthor();
		$this->scanuser = $entry->getUser()->getId();
		$this->comment = $entry->getComment();
		$this->date = $entry->getDate()->format('Y-m-d');
		$this->status = $entry->getStatus();
		$this->progress = $entry->getProgress();
		$this->is_frozen = $entry->getIsFrozen();
		$this->tmpfiles = $entry->getTmpfiles();
		$this->tfsize = $entry->getTfsize();
		if ( !$this->thisUserCanDeleteEntry() || $this->request->value('workType', null, 3) === null ) {
			$this->workType = $entry->getType();
		}
		$this->multidata = $this->getMultiEditData($entry->getId());

		$this->entry = $entry;
	}


	public function getMultiEditData($mainId) {
		$qa = array(
			'SELECT' => 'm.*, DATE(m.date) date, u.username, u.email, u.allowemail',
			'FROM' => self::DB_TABLE2 .' m',
			'LEFT JOIN' => array(
				DBT_USER .' u' => 'm.user_id = u.id',
			),
			'WHERE' => array('entry_id' => $mainId, 'deleted_at IS NULL'),
			'ORDER BY' => 'm.date DESC',
		);
		$q = $this->db->extselectQ($qa);
		$this->_medata = array();
		$this->db->iterateOverResult($q, 'addMultiEditData', $this);

		return $this->_medata;
	}


	public function addMultiEditData($dbrow) {
		$this->_medata[$dbrow['user_id']] = $dbrow;
	}


	protected function isScanDone() {
		return $this->status >= self::MAX_SCAN_STATUS;
	}


	protected function isEditDone() {
		$key = array(
			'entry_id' => $this->entryId,
			'is_frozen' => false,
			'progress < 100',
			'deleted_at IS NULL',
		);
		return ! $this->db->exists(self::DB_TABLE2, $key);
	}


	public function isSingleUser($type = null) {
		if ($type === null) $type = $this->workType;

		return $type == 0;
	}
	public function isMultiUser($type = null) {
		if ($type === null) $type = $this->workType;

		return $type == 1;
	}

	public function thisUserCanEditEntry($entry, $type) {
		if ($this->user->isAnonymous()) {
			return false;
		}
		if ($this->userIsSupervisor() || $type == 1) return true;
		$key = array('id' => $entry, 'user_id' => $this->user->getId());

		return $this->db->exists(self::DB_TABLE, $key);
	}

	public function userCanEditEntry($user, $type = 0) {
		if ($user->isAnonymous()) {
			return false;
		}
		return $this->userIsSupervisor()
			|| $user == $this->user->getId()
			|| ($type == 1 && $this->userCanAddEntry());
	}

	public function thisUserCanDeleteEntry() {
		if ($this->userIsSupervisor() || empty($this->entryId)) return true;
		if ( isset($this->_tucde) ) return $this->_tucde;
		$key = array('id' => $this->entryId, 'user_id' => $this->user->getId());

		return $this->_tucde = $this->db->exists(self::DB_TABLE, $key);
	}

	public function userCanDeleteEntry($user) {
		return $this->user->inGroup('workroom-admin', 'workroom-supervisor') || $user == $this->scanuser;
	}


	public function userCanAddEntry() {
		return $this->user->isAuthenticated() && $this->user->allowsEmail();
	}


	public function userCanMarkAsReady()
	{
		return $this->userIsAdmin();
	}

	public function isReady()
	{
		return $this->userCanMarkAsReady() && $this->request->checkbox('ready');
	}

	private function userIsAdmin()
	{
		return $this->user->inGroup('workroom-admin');
	}

	private function userIsSupervisor()
	{
		return $this->user->inGroup(array('workroom-admin', 'workroom-supervisor'));
	}

	private function userCanSetStatus($status) {
		switch ($status) {
			case self::STATUS_7:
				return $this->user->inGroup('workroom-admin');
			case self::STATUS_6:
				return $this->user->inGroup(array('workroom-admin', 'workroom-supervisor'));
			default:
				return $this->user->isAuthenticated();
		}
	}

	protected function informScanUser($entry) {
		$res = $this->db->select(self::DB_TABLE, array('id' => $entry));
		extract( $this->db->fetchAssoc($res) );

		$sel = array('realname', 'email');
		$res = $this->db->select(DBT_USER, array('id' => $user_id), $sel);
		extract( $this->db->fetchAssoc($res) );
		if ( empty($email) ) {
			return true;
		}
		$editLink = $this->controller->generateUrl('workroom_entry_edit', array('id' => $entry));

		$mailpage = Setup::getPage('Mail', $this->controller, $this->container, false);
		$msg = <<<EOS
Нов потребител се присъедини към подготовката на „{$title}“ от $author.

$editLink

Моята библиотека
EOS;
		$fields = array(
			'mailToName'  => $realname,
			'mailToEmail' => $email,
			'mailSubject' => "$this->sitename: Нов коректор на ваш текст",
			'mailMessage' => $msg);
		$mailpage->setFields($fields);

		return $mailpage->execute();
	}


	protected function escapeBlackListedExt($filename) {
		if ( ! File::hasValidExtension($filename, $this->fileWhiteList)) {
			$filename .= '.txt';
		}
		// remove leading dots
		$filename = ltrim($filename, '.');

		return $filename;
	}


	protected function makeTmpFilePath($file = '') {
		if (preg_match('|https?://|', $file)) {
			return $file;
		}

		return Setup::setting('workroom_root').'/'.$this->tmpDir . $file;
	}


	protected function makeFileLink($file, $username = '', $filesize = null)
	{
		$title = empty($username)
			? $file
			: "Качен файл от $username — $file";
		if ($filesize) {
			$title .= " ($filesize MiB)";
		}

		return $this->out->link_raw(
			$this->makeTmpFilePath($file),
			'<span>Файл</span>',
			$title,
			array('class' => 'save'));
	}


	static public function rawurlencode($file)
	{
		return strtr(rawurlencode($file), array(
			'%2F' => '/',
			'%3A' => ':',
		));
	}

	protected function deleteEntryFiles($entry)
	{
		$files = $this->absTmpDir . "$entry-*";
		$delDir = $this->absTmpDir . 'deleted';
		`mv $files $delDir`;
	}

	public function pretifyComment($text)
	{
		return String::my_replace($text);
	}

	private function repo()
	{
		return $this->controller->getRepository('WorkEntry');
	}
}
