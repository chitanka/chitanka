<?php namespace App\Legacy;

use App\Util\String;
use App\Util\Number;
use App\Util\Char;
use App\Util\File;
use App\Entity\WorkEntry;
use App\Pagination\Pager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkPage extends Page {

	const DEF_TMPFILE = '';
	const DB_TABLE = DBT_WORK;
	const DB_TABLE2 = DBT_WORK_MULTI;
	const MAX_SCAN_STATUS = 2;

	private $FF_COMMENT = 'comment';
	private $FF_EDIT_COMMENT = 'editComment';
	private $FF_VIEW_LIST = 'vl';
	private $FF_SUBACTION = 'status';
	private $FF_LQUERY = 'wq';

	protected $action = 'work';
	protected $defViewList = 'work';
	protected $defListLimit = 50;
	protected $maxListLimit = 500;

	private $tabs = ['Самостоятелна подготовка', 'Работа в екип'];
	private $tabImgs = ['fa fa-user singleuser', 'fa fa-users multiuser'];
	private $tabImgAlts = ['сам', 'екип'];
	private $statuses = [
		WorkEntry::STATUS_0 => 'Планира се',
		WorkEntry::STATUS_1 => 'Сканира се',
		WorkEntry::STATUS_2 => 'За корекция',
		WorkEntry::STATUS_3 => 'Коригира се',
		WorkEntry::STATUS_4 => 'Иска се SFB',
		WorkEntry::STATUS_5 => 'Чака проверка',
		WorkEntry::STATUS_6 => 'Проверен',
		WorkEntry::STATUS_7 => 'За добавяне',
	];
	private $viewLists = [
		'work' => 'списъка на подготвяните произведения',
		'contrib' => 'списъка на помощниците',
		'listonly' => '',
	];
	private $viewTypes = [
		'all' => 'Всички',
		'my' => 'Мое участие',
		'waiting' => 'Търси се коректор',
	];
	private $statusClasses = [
		WorkEntry::STATUS_0 => 'fa fa-square-o status-plan',
		WorkEntry::STATUS_1 => 'fa fa-square status-scan',
		WorkEntry::STATUS_2 => 'fa fa-circle-o status-waiting',
		WorkEntry::STATUS_3 => 'fa fa-dot-circle-o status-edit',
		WorkEntry::STATUS_4 => 'fa fa-code status-format',
		WorkEntry::STATUS_5 => 'fa fa-question-circle status-forcheck',
		WorkEntry::STATUS_6 => 'fa fa-check-circle status-checked',
		WorkEntry::STATUS_7 => 'fa fa-circle status-done',
		'all' => 'fa fa-tasks',
		'my' => 'fa fa-user',
		'waiting' => 'fa fa-search-plus status-waiting',
	];

	private $fileWhiteList = [
		'sfb', 'fb2', 'txt',
		'odt', 'rtf', 'djvu', 'pdf', 'epub',
		'zip', '7z', 'gz', 'tar', 'tgz', 'bz2',
	];

	private $subaction;

	private $tmpDir;
	private $absTmpDir;

	private $entryId;
	private $entry;
	private $workType;
	private $btitle;
	private $author;
	private $publisher;
	private $pubYear;
	private $status;
	private $progress;
	private $isFrozen;
	private $availableAt;
	private $delete;
	private $scanuser;
	private $scanuser_view;
	private $data_scanuser_view;
	private $comment;
	private $tmpfiles;
	private $tfsize;
	private $editComment;
	private $uplfile;
	private $multidata = [];

	private $searchQuery;
	private $form;
	private $bypassExisting;
	private $date;
	private $rowclass;
	private $viewList;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Работно ателие';

		$this->tmpDir = 'todo';
		$this->absTmpDir = $this->container->getParameter('kernel.root_dir') . "/../web/{$this->tmpDir}";

		$this->subaction = $this->request->value( $this->FF_SUBACTION, '', 1 );

		$this->entryId = (int) $this->request->value('id');
		$this->workType = (int) $this->request->value('workType', 0, 3);
		$this->btitle = $this->request->value('title');
		$this->author = $this->request->value('author');
		$this->publisher = $this->request->value('publisher');
		$this->pubYear = $this->request->value('pubYear');
		$this->status = (int) $this->request->value('entry_status');
		$this->progress = Number::normInt($this->request->value('progress'), 100, 0);
		$this->isFrozen = $this->request->checkbox('isFrozen');
		$this->availableAt = $this->request->value('availableAt');
		$this->delete = $this->request->checkbox('delete');
		$this->scanuser = (int) $this->request->value('user', $this->user->getId());
		$this->scanuser_view = $this->request->value('user');
		$this->comment = $this->request->value($this->FF_COMMENT);
		$this->comment = strtr($this->comment, ["\r"=>'']);
		$this->tmpfiles = $this->request->value('tmpfiles', self::DEF_TMPFILE);
		$this->tfsize = $this->request->value('tfsize');
		$this->editComment = $this->request->value($this->FF_EDIT_COMMENT);

		$this->uplfile = $this->makeUploadedFileName();

		$this->searchQuery = $this->request->value($this->FF_LQUERY);

		$this->form = $this->request->value('form');
		$this->bypassExisting = (int) $this->request->value('bypass', 0);
		$this->date = date('Y-m-d H:i:s');
		$this->rowclass = null;
		$this->viewList = $this->request->value($this->FF_VIEW_LIST, $this->defViewList, null, $this->viewLists);

		if ( !empty($this->subaction) && !empty($this->viewTypes[$this->subaction]) ) {
			$this->title .= ' — ' . $this->viewTypes[$this->subaction];
		} else if ( ! empty( $this->scanuser_view ) ) {
			$this->setScanUserView($this->scanuser_view);
			$this->title .= ' — ' . $this->data_scanuser_view->getUsername();
		}

		$this->initPaginationFields();
	}

	private function setScanUserView($user) {
		$this->scanuser_view = $user;
		$this->data_scanuser_view = $this->findUser($user);
	}

	private function findUser($user) {
		$userRepo = $this->controller->em()->getUserRepository();
		return is_numeric($user) ? $userRepo->find($user) : $userRepo->findByUsername($user);
	}

	protected function processSubmission() {
		if ( !empty($this->entryId) && !$this->thisUserCanEditEntry($this->entryId, $this->workType) ) {
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

	private function updateMainUserData() {
		if ( empty($this->btitle) ) {
			$this->addMessage('Не сте посочили заглавие.', true);
			return $this->makeForm();
		}
		if ( empty($this->pubYear) ) {
			$this->addMessage('Не сте посочили година на издаване.', true);
			return $this->makeForm();
		}
		$this->btitle = String::my_replace($this->btitle);

		if ($this->entryId == 0) { // check if this text exists in the library
			$this->scanuser_view = 0;
			if ( ! $this->bypassExisting) {
				// TODO does not work if there are more than one titles with the same name
				$texts = $this->controller->em()->getTextRepository()->findBy(['title' => $this->btitle]);
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
				$key = ['title' => $this->btitle, 'deleted_at IS NULL'];
				if ($this->db->exists(self::DB_TABLE, $key)) {
					$this->addMessage('Вече се подготвя произведение със същото заглавие', true);
					$this->addMessage('Повторното съхраняване ще добави вашия запис въпреки горното предупреждение.');
					$this->bypassExisting = 1;

					return $this->makeWorkList(0, 0, false, $key) . $this->makeForm();
				}
			}
		}

		if ( $this->entryId == 0 ) {
			$id = $this->controller->em()->getNextIdRepository()->findNextId('App:WorkEntry')->getValue();
			$this->uplfile = preg_replace('/^0-/', "$id-", $this->uplfile);
			$entry = new WorkEntry();
		} else {
			$id = $this->entryId;
			$entry = $this->repo()->find($this->entryId);
		}
		if ($this->availableAt) {
			$entry->setAvailableAt($this->availableAt);
		}
		$set = [
			'id' => $id,
			'type' => in_array($this->status, [WorkEntry::STATUS_4]) ? 1 : $this->workType,
			'title'=>$this->btitle,
			'author'=> strtr($this->author, [';'=>',']),
			'publisher' => $this->publisher,
			'pub_year' => $this->pubYear,
			'user_id'=>$this->scanuser,
			'comment' => $this->pretifyComment($this->comment),
			'date'=>$this->date,
			'is_frozen' => $this->isFrozen,
			'available_at' => $entry->getAvailableAt('Y-m-d'),
			'status'=>$this->status,
			'progress' => $this->progress,
			'tmpfiles' => self::rawurlencode($this->tmpfiles),
			'tfsize' => $this->tfsize
		];
		if ($this->userIsAdmin()) {
			$set += [
				'admin_status' => $this->request->value('adminStatus'),
				'admin_comment' => $this->request->value('adminComment'),
			];
		}
		if ($this->delete && $this->userIsAdmin()) {
			$curDate = new \DateTime;
			$set += ['deleted_at' => $curDate->format('Y-m-d H:i:s'), 'is_frozen' => 0];
			$this->controller->em()->getConnection()->update(self::DB_TABLE, $set, ['id' => $this->entryId]);
			if ( $this->isMultiUser($this->workType) ) {
				$this->controller->em()->getConnection()->update(self::DB_TABLE2, ['deleted_at' => $curDate->format('Y-m-d H:i:s')], ['entry_id' => $this->entryId]);
			}
			$this->addMessage("Произведението „{$this->btitle}“ беше премахнато от списъка.");
			$this->deleteEntryFiles($this->entryId);
			$this->scanuser_view = null;

			return $this->makeLists();
		}

		if ( $this->handleUpload() && !empty($this->uplfile) ) {
			$set['uplfile'] = $this->uplfile;
			$set['tmpfiles'] = $this->makeTmpFilePath(self::rawurlencode($this->uplfile));
			$set['tfsize'] = Number::int_b2m(filesize("{$this->absTmpDir}/{$this->uplfile}"));
		}
		if ($this->entryId) {
			$this->controller->em()->getConnection()->update(self::DB_TABLE, $set, ['id' => $this->entryId]);
			$msg = 'Данните за произведението бяха обновени.';
		} else {
			$this->controller->em()->getConnection()->insert(self::DB_TABLE, $set);
			$msg = 'Произведението беше добавено в списъка с подготвяните.';
		}
		$this->scanuser_view = 0;
		$this->addMessage($msg);

		return $this->makeLists();
	}

	private function updateMultiUserData() {
		if ( $this->thisUserCanDeleteEntry() && $this->form != 'edit' ) {
			return $this->updateMainUserData();
		}

		return $this->updateMultiUserDataForEdit();
	}

	protected function updateMultiUserDataForEdit() {
		$pkey = ['id' => $this->entryId];
		$key = ['entry_id' => $this->entryId, 'user_id' => $this->user->getId()];
		if ( empty($this->editComment) ) {
			$this->addMessage('Въвеждането на коментар е задължително.', true);

			return $this->buildContent();
		}
		$this->editComment = $this->pretifyComment($this->editComment);
		$set = [
			'entry_id' => $this->entryId,
			'user_id' => $this->user->getId(),
			'comment' => $this->editComment,
			'date' => $this->date,
			'progress' => $this->progress,
			'is_frozen' => $this->isFrozen,
			'deleted_at' => null,
		];
		if ($this->request->value('uplfile') != '') {
			$set['uplfile'] = $this->request->value('uplfile');
			$set['filesize'] = $this->request->value('filesize');
		}
		if ( $this->handleUpload() && !empty($this->uplfile) ) {
			$set['uplfile'] = $this->uplfile;
		}
		if ($this->db->exists(self::DB_TABLE2, $key)) {
			$this->controller->em()->getConnection()->update(self::DB_TABLE2, $set, $key);
			$msg = 'Данните бяха обновени.';
		} else {
			$set['id'] = $this->controller->em()->getNextIdRepository()->findNextId('App:WorkContrib')->getValue();
			$this->controller->em()->getConnection()->insert(self::DB_TABLE2, $set);
			$msg = 'Току-що се включихте в подготовката на произведението.';
			$this->informScanUser($this->entryId);
		}
		$this->addMessage($msg);
		// update main entry
		$set = [
			'date' => $this->date,
			'status' => $this->isEditDone()
				? ( $this->isReady() ? WorkEntry::STATUS_6 : WorkEntry::STATUS_5 )
				: WorkEntry::STATUS_3
		];
		$this->controller->em()->getConnection()->update(self::DB_TABLE, $set, $pkey);

		return $this->makeLists();
	}

	private function handleUpload() {
		$tmpfile = $this->request->fileTempName('file');
		if ( !is_uploaded_file($tmpfile) ) {
			return false;
		}
		$dest = "{$this->absTmpDir}/{$this->uplfile}";
		if ( file_exists($dest) ) {
			rename($dest, $dest .'-'. time());
		}
		if ( !move_uploaded_file($tmpfile, $dest) ) {
			$this->addMessage("Файлът не успя да бъде качен. Опитайте пак!", true);

			return false;
		}

		// copy local file if there is a remote workroom
		if ( $remote = $this->container->getParameter('workroom_remote') ) {
			$com = sprintf('scp "%s" %s', $dest, $remote);
			shell_exec($com);
		}

		$this->addMessage("Файлът беше качен. Благодарим ви за положения труд!");

		return true;
	}

	private function makeUploadedFileName() {
		$filename = $this->request->fileName('file');
		if ( empty($filename) ) {
			return '';
		}

		$filename = Char::cyr2lat($filename);
		$filename = strtr($filename, [' ' => '_']);

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
		if ($this->subaction == 'edit') {
			if ($this->entryId) {
				$this->initData($this->entryId);
			}
			$content .= $this->makeForm();
		} else {
			// a global RSS link should be added to the page
			$content .= $this->getInlineRssLink('workroom_rss') . $this->makeLists();
		}

		return $content;
	}

	private function makeUserGuideLink() {
		return '<div class="float-right"><a href="http://wiki.chitanka.info/Workroom" title="Наръчник за работното ателие"><span class="fa fa-info-circle"></span> Наръчник за работното ателие</a></div>';
	}

	private function makeLists() {
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

	private function makeSearchForm() {
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

	private function makeWorkList($limit = 0, $offset = 0, $showPageLinks = true, $where = []) {
		$sql = $this->makeSqlQuery($limit, $offset, $where);
		$results = $this->controller->em()->getConnection()->executeQuery($sql)->fetchAll();
		if ( empty($results) ) {
			return '<p class="standalone emptylist"><strong>Няма подготвящи се произведения.</strong></p>';
		}
		$table = '';
		foreach ($results as $result) {
			$table .= $this->makeWorkListItem($result);
		}
		if ($showPageLinks) {
			$params = [
				$this->FF_SUBACTION => $this->subaction
			];
			if ($this->searchQuery) $params[$this->FF_LQUERY] = $this->searchQuery;
			if ($this->scanuser_view) $params['user'] = $this->scanuser_view;
			$pagelinks = $this->controller->renderView('App::pager.html.twig', [
				'pager'    => new Pager([
					'page'  => $this->lpage,
					'limit' => $this->llimit,
					'total' => $this->db->getCount(self::DB_TABLE, $this->makeSqlWhere('', $where)),
				]),
				'current_route' => 'workroom',
				'route_params' => $params,
			]);
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
$table
</tbody>
</table>
$pagelinks
EOS;
	}

	private function makeSqlQuery($limit = 0, $offset = 0, $where = []) {
		$qa = [
			'SELECT' => 'w.*, DATE(date) ddate, u.username, u.email, u.allowemail, num_comments',
			'FROM' => self::DB_TABLE. ' w',
			'LEFT JOIN' => [
				DBT_USER .' u' => 'w.user_id = u.id',
				'thread ct' => 'w.comment_thread_id = ct.id',
			],
			'WHERE' => $this->makeSqlWhere('w', $where),
			'ORDER BY' => 'date DESC, w.id DESC',
			'LIMIT' => [$offset, $limit]
		];

		return $this->db->extselectQ($qa);
	}

	private function makeSqlWhere($pref = '', $base = []) {
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
			$entry_idQ = "SELECT entry_id FROM ".self::DB_TABLE2." WHERE user_id = {$showuser} AND deleted_at IS NULL";
			$ors = [
				$pref.'user_id' => $showuser,
				$pref.'id IN ('. $entry_idQ .')'];
			$w = array_merge($w, [$ors]);
		} else if ($this->subaction == 'waiting') {
			$w = ['type' => 1, 'status' => self::MAX_SCAN_STATUS];
		} else if ( strpos($this->subaction, 'st-') !== false ) {
			$w = ['status' => str_replace('st-', '', $this->subaction)];
		} else if ( ! empty($this->searchQuery) ) {
			$w[] = [
				$pref.'title' => ['LIKE', "%$this->searchQuery%"],
				$pref.'author' => ['LIKE', "%$this->searchQuery%"],
			];
		}

		$w[] = $pref.'deleted_at IS NULL';

		return $w;
	}

	private function makeWorkListItem($dbrow) {
		$entry = $this->repo()->find($dbrow['id']);
		$author = strtr($dbrow['author'], [', '=>',']);
		$author = $this->makeAuthorLink($author);
		$userlink = $this->makeUserLinkWithEmail($dbrow['username'], $dbrow['email'], $dbrow['allowemail']);
		$info = $this->makeWorkEntryInfo($dbrow);
		$title = "<i>$dbrow[title]</i>";
		$file = '';
		if ($entry->isAvailable()) {
			if ( ! empty($dbrow['tmpfiles']) ) {
				$file = $this->makeFileLink($dbrow['tmpfiles']);
			} else if ( ! empty($dbrow['uplfile']) ) {
				$file = $this->makeFileLink($dbrow['uplfile']);
			}
		}
		$entryLink = $this->controller->generateUrl('workroom_entry_edit', ['id' => $dbrow['id']]);
		$commentsLink = $dbrow['num_comments'] ? sprintf('<a href="%s#fos_comment_thread" title="Коментари"><span class="fa fa-comments-o"></span>%s</a>', $entryLink, $dbrow['num_comments']) : '';
		$title = sprintf('<a href="%s" title="Към страницата за преглед">%s</a>', $entryLink, $title);
		if (!$entry->isAvailable()) {
			$title = '<span class="fa fa-ban" title="Дата на достъп: '.$entry->getAvailableAt('d.m.Y').'"></span> ' . $title;
		}
		$this->rowclass = $this->nextRowClass($this->rowclass);
		$st = $dbrow['progress'] > 0
			? $this->makeProgressBar($dbrow['progress'])
			: $this->makeStatus($dbrow['status']);
		$extraclass = $this->user->getId() == $dbrow['user_id'] ? ' hilite' : '';
		if ($dbrow['is_frozen']) {
			$sisFrozen = '<span title="Подготовката е замразена">(замразена)</span>';
			$extraclass .= ' is_frozen';
		} else {
			$sisFrozen = '';
		}
		if ( $this->isMultiUser($dbrow['type']) ) {
			$musers = '';
			foreach ($entry->getContribs() as $contrib) {
				$uinfo = $this->makeExtraInfo("{$contrib->getComment()} ({$contrib->getProgress()}%)");
				$ufile = $contrib->getUplfile() == '' || !$entry->isAvailable()
					? ''
					: $this->makeFileLink($contrib->getUplfile(), $contrib->getUser()->getUsername());
				if ($contrib->getUser()->getId() == $dbrow['user_id']) {
					$userlink = "$userlink $uinfo $ufile";
					continue;
				}
				$ulink = $this->makeUserLinkWithEmail($contrib->getUser()->getUsername(),
					$contrib->getUser()->getEmail(), $contrib->getUser()->getAllowemail());
				if ($contrib->isFrozen()) {
					$ulink = "<span class='is_frozen'>$ulink</span>";
				}
				$musers .= "\n\t<li>$ulink $uinfo $ufile</li>";
				$extraclass .= $this->user->getId() == $contrib->getUser()->getId() ? ' hilite' : '';
			}
			if ( !empty($musers) ) {
				$userlink = "<ul class='simplelist'>\n\t<li>$userlink</li>$musers</ul>";
			} else if ( $dbrow['status'] == self::MAX_SCAN_STATUS ) {
				$userlink .= ' (<strong>очакват се коректори</strong>)';
			}
		}
		$umarker = $this->getUserTypeMarker($dbrow['type']);

		$adminFields = $this->userIsAdmin() ? $this->makeAdminFieldsForTable($dbrow) : '';
		$humanDate = (new \DateTime($dbrow['ddate']))->format('d.m.Y');

		return <<<EOS

	<tr class="$this->rowclass$extraclass" id="e$dbrow[id]">
		<td class="date" title="$dbrow[date]">$humanDate</td>
		$adminFields
		<td>$umarker</td>
		<td>$info</td>
		<td>$commentsLink</td>
		<td>$file</td>
		<td>$title</td>
		<td>$author</td>
		<td style="min-width: 10em">$st $sisFrozen</td>
		<td>$userlink</td>
	</tr>
EOS;
	}

	private function makeAdminFieldsForTable($dbrow) {
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

	private function getUserTypeMarker($type) {
		return "<span class=\"{$this->tabImgs[$type]}\"><span class=\"sr-only\">{$this->tabImgAlts[$type]}</span></span>";
	}

	private function makeStatus($code) {
		return "<span class='{$this->statusClasses[$code]}'></span> {$this->statuses[$code]}";
	}

	private function makeWorkEntryInfo($dbrow) {
		$lines = [];
		if ($dbrow['publisher']) {
			$lines[] = '<b>Издател:</b> ' . $dbrow['publisher'];
		}
		if ($dbrow['pub_year']) {
			$lines[] = '<b>Година:</b> ' . $dbrow['pub_year'];
		}
		if ($dbrow['available_at']) {
			$lines[] = '<b>Дата на достъп:</b> ' . (new \DateTime($dbrow['available_at']))->format('d.m.Y');
		}
		$lines[] = $dbrow['comment'];
		return $this->makeExtraInfo(implode("\n", $lines));
	}

	private function makeExtraInfo($info) {
		$info = strtr(trim($info), [
			"\n"   => '<br>',
			"\r"   => '',
		]);
		if (empty($info)) {
			return $info;
		}
		$info = String::myhtmlspecialchars($info);

		return '<span class="popover-trigger" data-content="'.$info.'"><span class="fa fa-info-circle"></span><span class="sr-only">Инфо</span></span>';
	}

	private function makeProgressBar($progressInPerc) {
		return <<<HTML
<div class="progress">
	<div class="progress-bar" role="progressbar" aria-valuenow="$progressInPerc" aria-valuemin="0" aria-valuemax="100" style="width: $progressInPerc%;">
		<span>$progressInPerc%</span>
	</div>
</div>
HTML;
	}

	private function makeNewEntryLink() {
		if ( !$this->userCanAddEntry() ) {
			return '';
		}

		return sprintf('<a href="%s" class="btn btn-primary"><span class="fa fa-plus"></span> Добавяне на нов запис</a>',
			$this->controller->generateUrl('workroom_entry_new'));

	}

	private function makeViewWorksLinks() {
		$links = [];
		foreach ($this->viewTypes as $type => $title) {
			$class = $this->subaction == $type ? 'selected' : '';
			$links[] = sprintf('<li><a href="%s" class="%s" title="Преглед на произведенията по критерий „%s“">%s %s</a></li>',
				$this->controller->generateUrl('workroom', [
					$this->FF_SUBACTION => $type
				]),
				$class, $title, "<span class='{$this->statusClasses[$type]}'></span>", $title);
		}
		$links[] = '<li role="presentation" class="divider"></li>';
		foreach ($this->statuses as $code => $statusTitle) {
			$type = "st-$code";
			$class = $this->subaction == $type ? 'selected' : '';
			$links[] = sprintf('<li><a href="%s" class="%s" title="Преглед на произведенията по критерий „%s“">%s %s</a></li>',
				$this->controller->generateUrl('workroom', [
					$this->FF_SUBACTION => $type
				]),
				$class, $statusTitle, "<span class='{$this->statusClasses[$code]}'></span>", $statusTitle);
		}

		$links[] = '<li role="presentation" class="divider"></li>';
		$links[] = sprintf('<li><a href="%s">Списък на помощниците</a></li>', $this->controller->generateUrl('workroom_contrib'));

		return '<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">Преглед <span class="caret"></span></button>
			<ul class="dropdown-menu" role="menu">'. implode("\n", $links) .'</ul>
			</div>';
	}

	private function makeForm() {
		if (isset($this->entry) && !$this->entry->isAvailable() && !$this->thisUserCanDeleteEntry()) {
			return '<div class="alert alert-danger">Този запис ще бъде наличен след '.$this->entry->getAvailableAt('d.m.Y').'.</div>';
		}

		$this->title .= ' — '.(empty($this->entryId) ? 'Добавяне' : 'Редактиране');
		$helpTop = empty($this->entryId) ? $this->makeAddEntryHelp() : '';
		$tabs = '';
		foreach ($this->tabs as $type => $text) {
			$text = "<span class='{$this->tabImgs[$type]}'></span> $text";
			$class = '';
			$url = '#';
			if ($this->workType == $type) {
				$class = 'active';
			} else if ($this->thisUserCanDeleteEntry()) {
				$route = 'workroom_entry_new';
				$params = ['workType' => $type];
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
			$extra = $this->makeMultiEditInput();
		}
		if ( $this->thisUserCanDeleteEntry() ) {
			$title = $this->out->textField('title', '', $this->btitle, 50, 255, null, '', ['class' => 'form-control']);
			$author = $this->out->textField('author', '', $this->author, 50, 255,
				0, 'Ако авторите са няколко, ги разделете със запетаи', ['class' => 'form-control']);
			$publisher = $this->out->textField('publisher', '', $this->publisher, 50, 255, 0, null, ['class' => 'form-control']);
			$pubYear = $this->out->textField('pubYear', '', $this->pubYear, 50, 255, 0, null, ['class' => 'form-control']);
			$comment = $this->out->textarea($this->FF_COMMENT, '', $this->comment, 10, 80, null, ['class' => 'form-control']);
			$delete = empty($this->entryId) || !$this->userIsAdmin() ? ''
				: '<div class="error" style="margin-bottom:1em">'.
				$this->out->checkbox('delete', '', false, 'Изтриване на записа') .
				' (напр., ако произведението вече е добавено в библиотеката)</div>';
			$button = $this->makeSubmitButton();
			if ($this->status == WorkEntry::STATUS_7 && !$this->userCanSetStatus(WorkEntry::STATUS_7)) {
				$button = $delete = '';
			}
		} else {
			$title = $this->btitle;
			$author = $this->author;
			$publisher = $this->publisher;
			$pubYear = $this->pubYear;
			$comment = $this->comment;
			$button = $delete = '';
		}

		$alertIfDeleted = isset($this->entry) && $this->entry->isDeleted() ? '<div class="alert alert-danger">Този запис е изтрит.</div>' : '';
		$helpBot = $this->isSingleUser($this->workType) ? $this->makeSingleUserHelp() : '';
		$scanuser = $this->out->hiddenField('user', $this->scanuser);
		$entry = $this->out->hiddenField('id', $this->entryId);
		$workType = $this->out->hiddenField('workType', $this->workType);
		$bypass = $this->out->hiddenField('bypass', $this->bypassExisting);
		$action = $this->controller->generateUrl('workroom');
		$this->addJs($this->createCommentsJavascript($this->entryId));

		$corrections = $this->createCorrectionsView();

		$adminFields = $this->userIsAdmin() ? $this->makeAdminOnlyFields() : '';
		$user = $this->controller->em()->getUserRepository()->find($this->scanuser);
		$ulink = $this->makeUserLinkWithEmail($user->getUsername(), $user->getEmail(), $user->getAllowemail());

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
				<label for="publisher" class="col-sm-2 control-label">Издател:</label>
				<div class="col-sm-10">
					$publisher
				</div>
			</div>
			<div class="form-group">
				<label for="pubYear" class="col-sm-2 control-label">Година на издаване:</label>
				<div class="col-sm-10">
					$pubYear
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
		$dmpPath = $this->container->getParameter('assets_base_urls') . '/vendor/js/diff_match_patch.js';
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

	private function createCommentsJavascript($entry) {
		if (empty($entry)) {
			return '';
		}
		$user = $this->controller->em()->getUserRepository()->find($this->scanuser);
		$threadUrl = $this->controller->generateUrl('fos_comment_post_threads');
		$commentJs = $this->container->getParameter('assets_base_urls') . '/js/comments.js';
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

	private function canShowCorrections() {
		return strpos($this->btitle, '(корекция)') !== false
			&& strpos($this->tmpfiles, 'chitanka.info') !== false
			&& File::isSFB($this->absTmpDir.'/'.basename($this->tmpfiles));
	}

	private function makeSubmitButton() {
		$submit = $this->out->submitButton('Запис', '', null, true, ['class' => 'btn btn-primary']);
		$cancel = sprintf('<a href="%s" title="Към основния списък">Отказ</a>', $this->controller->generateUrl('workroom'));

		return $submit .' &#160; '. $cancel;
	}

	private function makeSingleUserEditFields() {
		$status = $this->getStatusSelectField($this->status);
		$progress = $this->out->textField('progress', '', $this->progress, 2, 3);
		$isFrozen = $this->out->checkbox('isFrozen', '', $this->isFrozen,
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
			$isFrozen
		</div>
	</div>
	<div class="form-group">
		<label for="availableAt" class="col-sm-2 control-label">Дата на достъп:</label>
		<div class="col-sm-10">
			<input type="date" name="availableAt" id="availableAt" class="form-control" value="$this->availableAt">
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

	private function makeAdminOnlyFields() {
		if (empty($this->entry)) {
			return '';
		}
		$status = $this->out->textField('adminStatus', '', $this->entry->getAdminStatus(), 30, 255, null, '', ['class' => 'form-control']);
		$comment = $this->out->textarea('adminComment', '', $this->entry->getAdminComment(), 3, 80, null, ['class' => 'form-control']);
		return <<<FIELDS
	<div class="form-group">
		<label for="adminStatus" class="col-sm-2 control-label">Админ.&nbsp;статус:</label>
		<div class="col-sm-10">
			$status
		</div>
	</div>
	<div class="form-group">
		<label for="adminComment" class="col-sm-2 control-label">Админ.&nbsp;коментар:</label>
		<div class="col-sm-10">
			$comment
		</div>
	</div>
FIELDS;
	}

	private function getStatusSelectField($selected, $max = null) {
		$statuses = $this->statuses;
		foreach ([WorkEntry::STATUS_6, WorkEntry::STATUS_7] as $status) {
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

	private function makeMultiUserEditFields() {
		return $this->makeMultiScanInput();
	}

	private function makeMultiScanInput() {
		$isFrozenLabel = 'Подготовката е спряна за известно време';
		$cstatus = $this->status > self::MAX_SCAN_STATUS
			? self::MAX_SCAN_STATUS
			: $this->status;
		if ( $this->thisUserCanDeleteEntry() ) {
			if ( empty($this->multidata) || $this->userIsSupervisor() ) {
				$status = $this->userIsSupervisor()
					? $this->getStatusSelectField($this->status)
					: $this->getStatusSelectField($cstatus, self::MAX_SCAN_STATUS);
				$status = "<select name='entry_status' id='entry_status'>$status</select>";
				$isFrozen = $this->out->checkbox('isFrozen', '', $this->isFrozen, $isFrozenLabel);
			} else {
				$status = $this->statuses[$cstatus]
					. $this->out->hiddenField('entry_status', $this->status);
				$isFrozen = '';
			}
			$tmpfiles = $this->out->textField('tmpfiles', '', rawurldecode($this->tmpfiles), 50, 255);
			$tmpfiles .= ' &#160; '.$this->out->label('Размер: ', 'tfsize') .
				$this->out->textField('tfsize', '', $this->tfsize, 2, 4) .
				'<abbr title="Мебибайта">MiB</abbr>';
		} else {
			$status = $this->statuses[$cstatus];
			$isFrozen = $this->isFrozen ? "($isFrozenLabel)" : '';
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
			$isFrozen
		</div>
	</div>
	<div class="form-group">
		<label for="availableAt" class="col-sm-2 control-label">Дата на достъп:</label>
		<div class="col-sm-10">
			<input type="date" name="availableAt" id="availableAt" class="form-control" value="$this->availableAt">
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

	private function makeMultiEditInput() {
		$editorList = $this->makeEditorList();
		$myContrib = $this->isMyContribAllowed() ? $this->makeMultiEditMyInput() : '';

		return <<<EOS
		<h3>Коригиране</h3>
		$editorList
		$myContrib
EOS;
	}

	private function isMyContribAllowed() {
		if ($this->userIsSupervisor()) {
			return true;
		}
		if (in_array($this->status, [WorkEntry::STATUS_5, WorkEntry::STATUS_6, WorkEntry::STATUS_7])) {
			return false;
		}
		if ($this->user->isAnonymous()) {
			return false;
		}
		return true;
	}

	private function makeMultiEditMyInput() {
		$msg = '';
		if ( empty($this->multidata[$this->user->getId()]) ) {
			$comment = $progress = $uplfile = $filesize = '';
			$isFrozen = false;
			$msg = '<p>Вие също може да се включите в подготовката на текста.</p>';
		} else {
			$contrib = $this->multidata[$this->user->getId()];
			$comment = $contrib->getComment();
			$progress = $contrib->getProgress();
			$uplfile = $contrib->getUplfile();
			$filesize = $contrib->getFilesize();
			$isFrozen = $contrib->isFrozen();
		}
		$ulink = $this->makeUserLink($this->user->getUsername());
		$button = $this->makeSubmitButton();
		$scanuser = $this->out->hiddenField('user', $this->scanuser);
		$entry = $this->out->hiddenField('id', $this->entryId);
		$workType = $this->out->hiddenField('workType', $this->workType);
		$form = $this->out->hiddenField('form', 'edit');
		$subaction = $this->out->hiddenField($this->FF_SUBACTION, $this->subaction);
		$comment = $this->out->textarea($this->FF_EDIT_COMMENT, '', $comment, 10, 80, null, ['class' => 'form-control']);
		$progress = $this->out->textField('progress', '', $progress, 2, 3, null, '', ['class' => 'form-control']);
		$isFrozen = $this->out->checkbox('isFrozen', 'isFrozen_e', $isFrozen, 'Корекцията е спряна за известно време');
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
			$isFrozen
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

	private function makeEditorList() {
		if ( empty($this->multidata) ) {
			return '<p>Все още никой не се е включил в корекцията на текста.</p>';
		}
		$l = $class = '';
		foreach ($this->multidata as $contrib) {
			$class = $this->nextRowClass($class);
			$ulink = $this->makeUserLinkWithEmail($contrib->getUser()->getUsername(), $contrib->getUser()->getEmail(), $contrib->getUser()->getAllowemail());
			$comment = strtr($contrib->getComment(), ["\n" => "<br>\n"]);
			if ($contrib->getUplfile() != '') {
				$comment .= ' ' . $this->makeFileLink($contrib->getUplfile(), $contrib->getUser()->getUsername(), $contrib->getFilesize());
			}
			$progressbar = $this->makeProgressBar($contrib->getProgress());
			if ($contrib->isFrozen()) {
				$class .= ' isFrozen';
				$progressbar .= ' (замразена)';
			}
			$deleteForm = $this->controller->renderView('App:Workroom:contrib_delete_form.html.twig', ['contrib' => ['id' => $contrib->getId()]]);
			$date = $contrib->getDate()->format('d.m.Y');
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

	private function makePageHelp() {
		$regUrl = $this->controller->generateUrl('register');
		$ext = $this->user->isAnonymous() ? "е необходимо първо да се <a href=\"$regUrl\">регистрирате</a> (не се притеснявайте, ще ви отнеме най-много 10–20 секунди, колкото и бавно да пишете). След това се върнете на тази страница и" : '';
		$umarker = $this->getUserTypeMarker(1);
		$banYearThreshold = $this->container->getParameter('workroom_ban_year_threshold');

		return <<<EOS

<p>Тук може да разгледате списък на произведенията, които се подготвят за добавяне в библиотеката.</p>
<p>За да започнете подготовката на нов текст, $ext последвайте връзката „Добавяне на нов запис“. В случай че нямате възможност сами да сканирате текстове, може да се присъедините към коригирането на заглавията, отбелязани ето така: $umarker.</p>
<p>Бързината на добавянето на нови текстове в библиотеката зависи както от броя на грешките, останали след сканирането и разпознаването, така и от форма&#768;та на текста. Най-бързо ще бъдат добавяни отлично коригирани текстове, правилно преобразувани във <a href="http://wiki.chitanka.info/SFB">формат SFB</a>.</p>
<div class="alert alert-danger error newbooks-notice media" style="margin:1em 0">
	<div class="pull-left">
		<span class="fa fa-warning"></span>
	</div>
	<div class="media-body">
		Разрешено е да се добавят само книги, издадени на български преди $banYearThreshold г. Изключение се прави за онези текстове, които са пратени от авторите си, както и за фен-преводи.
	</div>
</div>
EOS;
	}

	private function makeAddEntryHelp() {
		$mainlink = $this->controller->generateUrl('workroom');

		return <<<EOS

<p>Чрез долния формуляр може да добавите ново произведение към <a href="$mainlink">списъка с подготвяните</a>.</p>
<p>Имате възможност за избор между „{$this->tabs[0]}“ (сами ще обработите целия текст) или „{$this->tabs[1]}“ (вие ще сканирате текста, а други потребители ще имат възможността да се включат в коригирането му).</p>
<p>Въведете заглавието и автора и накрая посочете на какъв етап се намира подготовката. Ако още не сте започнали сканирането, изберете „{$this->statuses[WorkEntry::STATUS_0]}“.</p>
<p>През следващите дни винаги може да промените етапа, на който се намира подготовката на произведението. За тази цел, в основния списък, заглавието ще представлява връзка към страницата за редактиране.</p>
EOS;
	}

	private function makeSingleUserHelp() {
		return <<<EOS

<p>На тази страница може да променяте данните за произведението.
Най-често ще се налага да обновявате етапа, на който се намира подготовката. Възможно е да посочите напредъка на подготовката и чрез процент, в случай че операциите сканиране, разпознаване и коригиране се извършват едновременно.</p>
<p>Ако подготовката на произведението е замразена, това може да се посочи, като се отметне полето „Подготовката е спряна за известно време“.</p>
EOS;
	}

	private function makeContribList() {
		$sql = 'SELECT ut.user_id, u.username, COUNT(ut.user_id) count, SUM(ut.size) size
			FROM '. DBT_USER_TEXT .' ut
			LEFT JOIN '. DBT_USER .' u ON ut.user_id = u.id
			GROUP BY ut.user_id
			ORDER BY size DESC';
		$results = $this->controller->em()->getConnection()->executeQuery($sql)->fetchAll();

		if ( empty($results) ) {
			return '';
		}
		$list = '';
		$rownr = 0;
		$rowclass = '';
		foreach ($results as $dbrow) {
			$rowclass = $this->nextRowClass($rowclass);
			$ulink = $dbrow['user_id'] ? $this->makeUserLink($dbrow['username']) : $dbrow['username'];
			$s = Number::formatNumber($dbrow['size'], 0);
			$rownr += 1;
			$list .= <<<HTML
	<tr class="$rowclass">
		<td>$rownr</td>
		<td>$ulink</td>
		<td class="text-right">$s</td>
		<td class="text-right">$dbrow[count]</td>
	</tr>
HTML;
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

	private function initData($id) {
		$entry = $this->repo()->find($id);
		if (!$entry) {
			throw new NotFoundHttpException("Няма запис с номер $id.");
		}
		if ($entry->isDeleted() && !$this->userIsAdmin()) {
			throw new NotFoundHttpException("Изтрит запис.");
		}
		$this->btitle = $entry->getTitle();
		$this->author = $entry->getAuthor();
		$this->publisher = $entry->getPublisher();
		$this->pubYear = $entry->getPubYear();
		$this->scanuser = $entry->getUser()->getId();
		$this->comment = $entry->getComment();
		$this->date = $entry->getDate()->format('Y-m-d');
		$this->status = $entry->getStatus();
		$this->progress = $entry->getProgress();
		$this->isFrozen = $entry->getIsFrozen();
		$this->availableAt = $entry->getAvailableAt('Y-m-d');
		$this->tmpfiles = $entry->getTmpfiles();
		$this->tfsize = $entry->getTfsize();
		if ( !$this->thisUserCanDeleteEntry() || $this->request->value('workType', null, 3) === null ) {
			$this->workType = $entry->getType();
		}
		$this->multidata = [];
		foreach ($entry->getContribs() as $contrib) {
			$this->multidata[$contrib->getUser()->getId()] = $contrib;
		}

		$this->entry = $entry;
	}

	private function isEditDone() {
		$key = [
			'entry_id' => $this->entryId,
			'is_frozen' => false,
			'progress < 100',
			'deleted_at IS NULL',
		];
		return ! $this->db->exists(self::DB_TABLE2, $key);
	}

	private function isSingleUser($type = null) {
		if ($type === null) $type = $this->workType;

		return $type == 0;
	}
	private function isMultiUser($type = null) {
		if ($type === null) $type = $this->workType;

		return $type == 1;
	}

	private function thisUserCanEditEntry($entry, $type) {
		if ($this->user->isAnonymous()) {
			return false;
		}
		if ($this->userIsSupervisor() || $type == 1) return true;
		$key = ['id' => $entry, 'user_id' => $this->user->getId()];

		return $this->db->exists(self::DB_TABLE, $key);
	}

	private $tucde;
	private function thisUserCanDeleteEntry() {
		if ($this->userIsSupervisor() || empty($this->entryId)) return true;
		if ( isset($this->tucde) ) return $this->tucde;
		$key = ['id' => $this->entryId, 'user_id' => $this->user->getId()];

		return $this->tucde = $this->db->exists(self::DB_TABLE, $key);
	}

	private function userCanAddEntry() {
		return $this->user->isAuthenticated() && $this->user->allowsEmail();
	}

	private function userCanMarkAsReady() {
		return $this->userIsAdmin();
	}

	private function isReady() {
		return $this->userCanMarkAsReady() && $this->request->checkbox('ready');
	}

	private function userIsAdmin() {
		return $this->user->inGroup('workroom-admin');
	}

	private function userIsSupervisor() {
		return $this->user->inGroup(['workroom-admin', 'workroom-supervisor']);
	}

	private function userCanSetStatus($status) {
		switch ($status) {
			case WorkEntry::STATUS_7:
				return $this->user->inGroup('workroom-admin');
			case WorkEntry::STATUS_6:
				return $this->user->inGroup(['workroom-admin', 'workroom-supervisor']);
			default:
				return $this->user->isAuthenticated();
		}
	}

	private function informScanUser($entryId) {
		$entry = $this->repo()->find($entryId);

		if ($entry->getUser()->getEmail() == '') {
			return;
		}
		$editLink = $this->controller->generateUrl('workroom_entry_edit', ['id' => $entry->getId()], true);
		$messageBody = <<<EOS
Нов потребител се присъедини към подготовката на „{$entry->getTitle()}“ от {$entry->getAuthor()}.

$editLink

Моята библиотека
EOS;
		$message = \Swift_Message::newInstance("Моята библиотека: Нов коректор на ваш текст");
		$message->setFrom($this->container->getParameter('work_email'), 'Моята библиотека');
		$message->setTo($entry->getUser()->getEmail(), $entry->getUser()->getUsername());
		$message->setBody($messageBody);

		$notifier = new \App\Mail\Notifier($this->container->get('mailer'));
		$notifier->sendMessage($message);
	}

	private function makeTmpFilePath($file = '') {
		if (preg_match('|https?://|', $file)) {
			return $file;
		}
		return $this->container->getParameter('workroom_root')."/{$this->tmpDir}/{$file}";
	}

	private function makeFileLink($file, $username = '', $filesize = null) {
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

	private static function rawurlencode($file) {
		return strtr(rawurlencode($file), [
			'%2F' => '/',
			'%3A' => ':',
		]);
	}

	private function deleteEntryFiles($entry) {
		foreach (glob("{$this->absTmpDir}/{$entry}-*") as $file) {
			rename($file, "{$this->absTmpDir}/deleted/".basename($file));
		}
	}

	private function pretifyComment($text) {
		return String::my_replace($text);
	}

	/** @return \App\Entity\WorkEntryRepository */
	private function repo() {
		return $this->controller->em()->getWorkEntryRepository();
	}

	private function nextRowClass($curRowClass = '') {
		return $curRowClass == 'even' ? 'odd' : 'even';
	}
}
