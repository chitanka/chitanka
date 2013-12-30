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
		MAX_SCAN_STATUS = 2,
		STATUS_0 = 0,
		STATUS_1 = 1,
		STATUS_2 = 2,
		STATUS_3 = 3,
		STATUS_4 = 4,
		STATUS_5 = 5,
		STATUS_6 = 6,
		STATUS_7 = 7;

	private
		$FF_COMMENT = 'comment',
		$FF_EDIT_COMMENT = 'editComment',
		$FF_VIEW_LIST = 'vl',
		$FF_SUBACTION = 'status',
		$FF_LQUERY = 'wq';

	protected $action = 'work';
	protected $defViewList = 'work';
	protected $defListLimit = 50;
	protected $maxListLimit = 500;

	private
		$tabs = array('Самостоятелна подготовка', 'Работа в екип'),
		$tabImgs = array('fa fa-user singleuser', 'fa fa-users multiuser'),
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
		$statusClasses = array(
			self::STATUS_0 => 'fa fa-square-o status-plan',
			self::STATUS_1 => 'fa fa-square status-scan',
			self::STATUS_2 => 'fa fa-circle-o status-waiting',
			self::STATUS_3 => 'fa fa-dot-circle-o status-edit',
			self::STATUS_4 => 'fa fa-code status-format',
			self::STATUS_5 => 'fa fa-question-circle status-forcheck',
			self::STATUS_6 => 'fa fa-check-circle status-checked',
			self::STATUS_7 => 'fa fa-circle status-done',
			'all' => 'fa fa-tasks',
			'my' => 'fa fa-user',
			'waiting' => 'fa fa-search-plus status-waiting',
		),

		$fileWhiteList = array(
			'sfb', 'fb2', 'txt',
			'odt', 'rtf', 'djvu', 'pdf',
			'zip', '7z', 'gz', 'tar', 'tgz', 'bz2',
			'jpg', 'png',
		);


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Работно ателие';

		$this->tmpDir = 'todo/';
		$this->absTmpDir = $this->container->getParameter('kernel.root_dir') . '/../web/'.$this->tmpDir;

		$this->subaction = $this->request->value( $this->FF_SUBACTION, '', 1 );

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
		$this->comment = $this->request->value($this->FF_COMMENT);
		$this->comment = strtr($this->comment, array("\r"=>''));
		$this->tmpfiles = $this->request->value('tmpfiles', self::DEF_TMPFILE);
		$this->tfsize = $this->request->value('tfsize');
		$this->editComment = $this->request->value($this->FF_EDIT_COMMENT);

		$this->uplfile = $this->makeUploadedFileName();

		$this->searchQuery = $this->request->value($this->FF_LQUERY);

		$this->form = $this->request->value('form');
		$this->bypassExisting = (int) $this->request->value('bypass', 0);
		$this->date = date('Y-m-d H:i:s');
		$this->rowclass = null;
		$this->showProgressbar = true;
		$this->viewList = $this->request->value($this->FF_VIEW_LIST,
			$this->defViewList, null, $this->viewLists);

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
		if ($this->uplfile && ! File::hasValidExtension($this->uplfile, $this->fileWhiteList)) {
			$formatList = implode(', ', $this->fileWhiteList);
			$this->addMessage("Файлът не е в един от разрешените формати: $formatList", true);

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
			$id = $this->controller->getRepository('NextId')->findNextId('LibBundle:WorkEntry')->getValue();
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
			$set['id'] = $this->controller->getRepository('NextId')->findNextId('LibBundle:WorkContrib')->getValue();
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
		return '<div class="float-right"><a href="http://wiki.chitanka.info/Workroom" title="Наръчник за работното ателие"><span class="fa fa-info-circle"></span> Наръчник за работното ателие</a></div>';
	}

	protected function makeLists() {
		$o = $this->makePageHelp()
			. $this->makeSearchForm()
			. '<div class="standalone">' . $this->makeNewEntryLink() . '</div>'
			;

		if ($this->viewList == 'work') {
			$o .= $this->makeWorkList($this->llimit, $this->loffset);
		} else {
			$o .= $this->makeContribList();
		}

		return $o;
	}


	protected function makeSearchForm()
	{
		$id = $this->FF_LQUERY;
		$action = $this->controller->generateUrl('workroom');
		return <<<EOS

<form action="$action" method="get" class="form-inline standalone" role="form">
	{$this->makeViewWorksLinks()}
	<div class="form-group">
		<label for="$id" class="sr-only">Търсене на: </label>
		<div class="input-group">
			<input type="text" class="form-control" title="Търсене из подготвяните произведения" maxlength="100" size="50" id="$id" name="$id">
			<span class="input-group-btn">
				<button class="btn btn-default" type="submit"><span class="fa fa-search"></span><span class="sr-only">Търсене</span></button>
			</span>
		</div>
	</div>
</form>
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
				$this->FF_SUBACTION => $this->subaction
			);
			if ($this->searchQuery) $params[$this->FF_LQUERY] = $this->searchQuery;
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
<table class="table table-striped table-condensed table-bordered">
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
		$commentsLink = $num_comments ? sprintf('<a href="%s#fos_comment_thread" title="Коментари"><span class="fa fa-comments-o"></span>%s</a>', $entryLink, $num_comments) : '';
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
		<td style="min-width: 10em">$st $sis_frozen</td>
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
		if (empty($dbrow['admin_comment'])) {
			return '<td></td>';
		}
		$comment = htmlspecialchars(nl2br($dbrow['admin_comment']));
		$class = htmlspecialchars(str_replace(' ', '-', $dbrow['admin_status']));
		return <<<HTML
<td>
<span class="popover-trigger workroom-$class" data-content="$comment">
	<span>$dbrow[admin_status]</span>
</span>
</td>
HTML;
	}

	protected function _getUserTypeMarker($type)
	{
		return "<span class=\"{$this->tabImgs[$type]}\"><span class=\"sr-only\">{$this->tabImgAlts[$type]}</span></span>";
	}


	public function makeStatus($code) {
		return "<span class='{$this->statusClasses[$code]}'></span> {$this->statuses[$code]}";
	}


	public function makeExtraInfo($info, $expand = false) {
		$info = strtr($info, array(
			"\n"   => '<br>',
			"\r"   => '',
		));
		if ($expand) {
			return $info;
		}
		$info = String::myhtmlspecialchars($info);

		return '<span class="popover-trigger" data-content="'.$info.'"><span class="fa fa-info-circle"></span><span class="sr-only">Инфо</span></span>';
	}


	public function makeProgressBar($progressInPerc) {
		$perc = $progressInPerc .'%';
		if ( !$this->showProgressbar ) {
			return $perc;
		}
		return <<<HTML
<div class="progress">
	<div class="progress-bar" role="progressbar" aria-valuenow="$progressInPerc" aria-valuemin="0" aria-valuemax="100" style="width: $progressInPerc%;">
		<span>$progressInPerc%</span>
	</div>
</div>
HTML;
	}


	protected function makeNewEntryLink() {
		if ( !$this->userCanAddEntry() ) {
			return '';
		}

		return sprintf('<a href="%s" class="btn btn-primary"><span class="fa fa-plus"></span> Добавяне на нов запис</a>',
			$this->controller->generateUrl('workroom_entry_new'));

	}

	protected function makeViewWorksLinks() {
		$links = array();
		foreach ($this->viewTypes as $type => $title) {
			$class = $this->subaction == $type ? 'selected' : '';
			$links[] = sprintf('<li><a href="%s" class="%s" title="Преглед на произведенията по критерий „%s“">%s %s</a></li>',
				$this->controller->generateUrl('workroom', array(
					$this->FF_SUBACTION => $type
				)),
				$class, $title, "<span class='{$this->statusClasses[$type]}'></span>", $title);
		}
		$links[] = '<li role="presentation" class="divider"></li>';
		foreach ($this->statuses as $code => $statusTitle) {
			$type = "st-$code";
			$class = $this->subaction == $type ? 'selected' : '';
			$links[] = sprintf('<li><a href="%s" class="%s" title="Преглед на произведенията по критерий „%s“">%s %s</a></li>',
				$this->controller->generateUrl('workroom', array(
					$this->FF_SUBACTION => $type
				)),
				$class, $statusTitle, "<span class='{$this->statusClasses[$code]}'></span>", $statusTitle);
		}

		$links[] = '<li role="presentation" class="divider"></li>';
		$links[] = sprintf('<li><a href="%s">Списък на помощниците</a></li>', $this->controller->generateUrl('workroom_contrib'));

		return '<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">Преглед <span class="caret"></span></button>
			<ul class="dropdown-menu" role="menu">'. implode("\n", $links) .'</ul>
			</div>';
	}


	protected function makeForm() {
		$this->title .= ' — '.(empty($this->entryId) ? 'Добавяне' : 'Редактиране');
		$helpTop = empty($this->entryId) ? $this->makeAddEntryHelp() : '';
		$tabs = '';
		foreach ($this->tabs as $type => $text) {
			$text = "<span class='{$this->tabImgs[$type]}'></span> $text";
			$class = '';
			if ($this->workType == $type) {
				$class = 'active';
				$url = '#';
			} else if ($this->thisUserCanDeleteEntry()) {
				$route = 'workroom_entry_new';
				$params = array('workType' => $type);
				if ($this->entryId) {
					$params['id'] = $this->entryId;
					$route = 'workroom_entry_edit';
				}
				$url = $this->controller->generateUrl($route, $params);
			}
			$tabs .= "<li class='$class'><a href='$url'>$text</a></li>";
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
			$title = $this->out->textField('title', '', $this->btitle, 50, 255, null, '', array('class' => 'form-control'));
			$author = $this->out->textField('author', '', $this->author, 50, 255,
				0, 'Ако авторите са няколко, ги разделете със запетаи', array('class' => 'form-control'));
			$comment = $this->out->textarea($this->FF_COMMENT, '', $this->comment, 10, 80, null, array('class' => 'form-control'));
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

		$alertIfDeleted = $this->entry->isDeleted() ? '<div class="alert alert-danger">Този запис е изтрит.</div>' : '';
		$helpBot = $this->isSingleUser($this->workType) ? $this->makeSingleUserHelp() : '';
		$scanuser = $this->out->hiddenField('user', $this->scanuser);
		$entry = $this->out->hiddenField('id', $this->entryId);
		$workType = $this->out->hiddenField('workType', $this->workType);
		$bypass = $this->out->hiddenField('bypass', $this->bypassExisting);
		$action = $this->controller->generateUrl('workroom');
		$this->addJs($this->createCommentsJavascript($this->entryId));

		$corrections = $this->createCorrectionsView();

		$adminFields = $this->userIsAdmin() ? $this->makeAdminOnlyFields() : '';
		$user = $this->controller->getRepository('User')->find($this->scanuser);
		$ulink = $this->makeUserLinkWithEmail($user->getUsername(),
			$user->getEmail(), $user->getAllowemail());

		return <<<EOS

$alertIfDeleted
$helpTop
<div style="clear:both"></div>
<ul class="nav nav-tabs">
	$tabs
</ul>
<div class="tab-content">
	<div class="tab-pane active">
		<form action="$action" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
			$scanuser
			$entry
			$workType
			$bypass
			<div class="form-group">
				<label class="col-sm-2 control-label">Отговорник:</label>
				<div class="col-sm-10">
					<div class="form-control">
						$ulink
					</div>
				</div>
			</div>
			<div class="form-group">
				<label for="title" class="col-sm-2 control-label">Заглавие:</label>
				<div class="col-sm-10">
					$title
				</div>
			</div>
			<div class="form-group">
				<label for="author" class="col-sm-2 control-label">Автор:</label>
				<div class="col-sm-10">
					$author
				</div>
			</div>
			<div class="form-group">
				<label for="$this->FF_COMMENT" class="col-sm-2 control-label">Коментар:</label>
				<div class="col-sm-10">
					$comment
				</div>
			</div>
			$editFields
			$adminFields
			$delete
			<div class="form-submit">$button</div>
		</form>
		$extra
	</div>
</div>

	$corrections

<div id="fos_comment_thread"></div>

<div id="helpBottom">
$helpBot
</div>
EOS;
	}

	private function createCorrectionsView() {
		if (!$this->canShowCorrections()) {
			return '';
		}
		// same domain as main site - for ajax
		$newFile = str_replace('http://static.chitanka.info', '', $this->tmpfiles);
		$dmpPath = $this->container->getParameter('assets_base_urls') . '/js/diff_match_patch.js';
		return <<<CORRECTIONS
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
		$submit = $this->out->submitButton('Запис', '', null, true, array('class' => 'btn btn-primary'));
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
				'<abbr title="Мебибайта">MiB</abbr>';

		$flink = $this->tmpfiles == self::DEF_TMPFILE ? ''
			: $this->out->link( $this->makeTmpFilePath($this->tmpfiles), String::limitLength($this->tmpfiles)) .
			($this->tfsize > 0 ? " ($this->tfsize&#160;MiB)" : '');

		return <<<EOS
	<div class="form-group">
		<label for="entry_status" class="col-sm-2 control-label">Етап:</label>
		<div class="col-sm-10">
			<select name="entry_status" id="entry_status">$status</select>
			&#160; или &#160;
			$progress<label for="progress">%</label><br>
			$is_frozen
		</div>
	</div>
	<div class="form-group">
		<label for="file" class="col-sm-2 control-label">Файл:</label>
		<div class="col-sm-10">
			<div>
				$maxFileSize
				$file (макс. $maxUploadSizeInMiB MiB)
			</div>
			<p>или</p>
			<div>
			$tmpfiles
			<div>$flink</div>
			</div>
		</div>
	</div>
EOS;
	}

	private function makeAdminOnlyFields()
	{
		if (empty($this->entry)) {
			return '';
		}
		$status = $this->out->textField('admin_status', '', $this->entry->getAdminStatus(), 30, 255, null, '', array('class' => 'form-control'));
		$comment = $this->out->textarea('admin_comment', '', $this->entry->getAdminComment(), 3, 80, null, array('class' => 'form-control'));
		return <<<FIELDS
	<div class="form-group">
		<label for="admin_status" class="col-sm-2 control-label">Админ.&nbsp;статус:</label>
		<div class="col-sm-10">
			$status
		</div>
	</div>
	<div class="form-group">
		<label for="admin_comment" class="col-sm-2 control-label">Админ.&nbsp;коментар:</label>
		<div class="col-sm-10">
			$comment
		</div>
	</div>
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
			$field .= "<option value='$code'$sel>$text</option>";
		}

		return $field;
	}


	protected function makeMultiUserEditFields() {
		return $this->makeMultiScanInput();
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
				'<abbr title="Мебибайта">MiB</abbr>';
		} else {
			$status = $this->statuses[$cstatus];
			$is_frozen = $this->is_frozen ? "($is_frozenLabel)" : '';
			$tmpfiles = '';
		}

		$flink = $this->tmpfiles == self::DEF_TMPFILE ? ''
			: $this->out->link( $this->makeTmpFilePath($this->tmpfiles), String::limitLength($this->tmpfiles)) .
			($this->tfsize > 0 ? " ($this->tfsize&#160;MiB)" : '');
		$file = $this->out->fileField('file', '');
		$maxFileSize = $this->out->makeMaxFileSizeField();
		$maxUploadSizeInMiB = Legacy::getMaxUploadSizeInMiB();

		return <<<EOS
	<div class="form-group">
		<label for="entry_status" class="col-sm-2 control-label">Етап:</label>
		<div class="col-sm-10">
			$status
			$is_frozen
		</div>
	</div>
	<div class="form-group">
		<label for="file" class="col-sm-2 control-label">Файл:</label>
		<div class="col-sm-10">
			<div>
				$maxFileSize
				$file (макс. $maxUploadSizeInMiB MiB)
			</div>
			<p>или</p>
			<div>
			$tmpfiles
			<div>$flink</div>
			</div>
		</div>
	</div>
EOS;
	}


	protected function makeMultiEditInput() {
		$editorList = $this->makeEditorList();
		$myContrib = $this->isMyContribAllowed() ? $this->makeMultiEditMyInput() : '';

		return <<<EOS
		<h3>Коригиране</h3>
		$editorList
		$myContrib
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
		$subaction = $this->out->hiddenField($this->FF_SUBACTION, $this->subaction);
		$comment = $this->out->textarea($this->FF_EDIT_COMMENT, '', $comment, 10, 80, null, array('class' => 'form-control'));
		$progress = $this->out->textField('progress', '', $progress, 2, 3, null, '', array('class' => 'form-control'));
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
				'<abbr title="Мебибайта">MiB</abbr>';

		return <<<EOS

<form action="$action" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
	<fieldset>
		<legend>Моят принос ($ulink)</legend>
		$msg
	$scanuser
	$entry
	$workType
	$form
	$subaction
	<div class="form-group">
		<label for="$this->FF_EDIT_COMMENT" class="col-sm-2 control-label">Коментар:</label>
		<div class="col-sm-10">
			$comment
		</div>
	</div>
	<div class="form-group">
		<label for="progress" class="col-sm-2 control-label">Напредък:</label>
		<div class="col-sm-10">
			<div class="input-group">
				$progress
				<span class="input-group-addon">%</span>
			</div>
			$is_frozen
		</div>
	</div>
	<div class="form-group">
		<label for="file2" class="col-sm-2 control-label">Файл:</label>
		<div class="col-sm-10">
			$file
		</div>
	</div>
	<div class="form-group">
		<label for="uplfile2" class="col-sm-2 control-label">Външен файл:</label>
		<div class="col-sm-10">
			$remoteFile
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-2">&nbsp;</div>
		<div class="col-sm-10">
			$readytogo
		</div>
	</div>

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

	<table class="content">
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
<p>За да започнете подготовката на нов текст, $ext последвайте връзката „Добавяне на нов запис“. В случай че нямате възможност сами да сканирате текстове, може да се присъедините към коригирането на заглавията, отбелязани ето така: $umarker. Те са достъпни и чрез връзката „{$this->viewTypes['waiting']}“.</p>
<p>Бързината на добавянето на нови текстове в библиотеката зависи както от броя на грешките, останали след сканирането и разпознаването, така и от форма&#768;та на текста. Най-бързо ще бъдат добавяни отлично коригирани текстове, правилно преобразувани във <a href="http://wiki.chitanka.info/SFB">формат SFB</a>.</p>
<div class="alert alert-danger error newbooks-notice media" style="margin:1em 0">
	<div class="pull-left">
		<span class="fa fa-warning"></span>
	</div>
	<div class="media-body">
		Разрешено е да се добавят само книги, издадени на български преди 2011 г. Изключение се прави за онези текстове, които са пратени от авторите си, както и за фен-преводи.
	</div>
</div>
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
			'SELECT' => 'ut.user_id, u.username, COUNT(ut.user_id) count, SUM(ut.size) size',
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

	<table class="table table-striped table-condensed table-bordered" style="margin: 0 auto; max-width: 30em">
	<caption>Следните потребители са сканирали или коригирали текстове за библиотеката:</caption>
	<thead>
	<tr>
		<th>№</th>
		<th>Потребител</th>
		<th class="text-right" title="Размер на обработените произведения в мебибайта">Размер (в <abbr title="Кибибайта">KiB</abbr>)</th>
		<th class="text-right" title="Брой на обработените произведения">Брой</th>
	</tr>
	</thead>
	<tbody>$list
	</tbody>
	</table>
EOS;
	}


	public function makeContribListItem($dbrow) {
		$this->rowclass = $this->out->nextRowClass($this->rowclass);
		$ulink = $dbrow['user_id'] ? $this->makeUserLink($dbrow['username']) : $dbrow['username'];
		$s = Number::formatNumber($dbrow['size'], 0);
		$this->rownr += 1;

		return <<<HTML
<tr class="$this->rowclass">
	<td>$this->rownr</td>
	<td>$ulink</td>
	<td class="text-right">$s</td>
	<td class="text-right">$dbrow[count]</td>
</tr>
HTML;
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
			'<span class="fa fa-save"></span><span class="sr-only">Файл</span>',
			$title);
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
