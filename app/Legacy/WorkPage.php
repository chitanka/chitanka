<?php namespace App\Legacy;

use App\Entity\User;
use App\Entity\WorkEntry;
use App\Util\Char;
use App\Util\File;
use App\Util\Number;
use App\Util\Stringy;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkPage extends Page {

	const DEF_TMPFILE = '';
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
		WorkEntry::STATUS_0 => 'fa fa-fw fa-square-o status-plan',
		WorkEntry::STATUS_1 => 'fa fa-fw fa-square status-scan',
		WorkEntry::STATUS_2 => 'fa fa-fw fa-circle-o status-waiting',
		WorkEntry::STATUS_3 => 'fa fa-fw fa-dot-circle-o status-edit',
		WorkEntry::STATUS_4 => 'fa fa-fw fa-code status-format',
		WorkEntry::STATUS_5 => 'fa fa-fw fa-question-circle status-forcheck',
		WorkEntry::STATUS_6 => 'fa fa-fw fa-check-circle status-checked',
		WorkEntry::STATUS_7 => 'fa fa-fw fa-circle status-done',
		'all' => 'fa fa-fw fa-tasks',
		'my' => 'fa fa-fw fa-user',
		'waiting' => 'fa fa-fw fa-search-plus status-waiting',
	];

	private $fileWhiteList = [
		'sfb', 'fb2', 'txt',
		'odt', 'rtf', 'doc', 'docx', 'djvu', 'pdf', 'epub',
		'zip', '7z', 'gz', 'tar', 'tgz', 'bz2',
	];

	private $subaction;

	private $tmpDir;
	private $absTmpDir;

	private $entryId;
	/** @var WorkEntry */
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
	private $contribsByUser = [];

	private $searchQuery;
	private $form;
	private $bypassExisting;
	private $date;
	private $viewList;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Работно ателие';

		$this->tmpDir = 'todo';
		$this->absTmpDir = $this->container->getParameter('kernel.root_dir') . "/../web/{$this->tmpDir}";

		$this->subaction = $this->request->value( $this->FF_SUBACTION, '', 1 );

		$this->entryId = (int) $this->request->value('id');
		if ($this->entryId) {
			$this->entry = $this->repo()->find($this->entryId);
		}
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
		if ($this->entry && !$this->thisUserCanEditEntry($this->entry, $this->workType)) {
			$this->addMessage('Нямате права да редактирате този запис.', true);
			return $this->makeLists();
		}
		if ($this->uplfile && ! File::hasValidExtension($this->uplfile, $this->fileWhiteList)) {
			$formatList = implode(', ', $this->fileWhiteList);
			$this->addMessage("Файлът не е в един от разрешените формати: $formatList", true);

			return $this->makeLists();
		}

		switch ($this->workType) {
			case WorkEntry::TYPE_SINGLE_USER: return $this->updateMainUserData();
			case WorkEntry::TYPE_MULTI_USER: return $this->updateMultiUserData();
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
		$this->btitle = Stringy::my_replace($this->btitle);

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
				$existingEntries = $this->createPager($this->btitle);
				if ($existingEntries->count() > 0) {
					$this->addMessage('Вече се подготвя произведение със същото заглавие', true);
					$this->addMessage('Повторното съхраняване ще добави вашия запис въпреки горното предупреждение.');
					$this->bypassExisting = 1;
					return $this->makeWorkList($existingEntries) . $this->makeForm();
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
			'pack_type' => $this->sfrequest->get('packType'),
			'biblioman_id' => $this->sfrequest->get('bibliomanId'),
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
			$this->controller->em()->getConnection()->update(DBT_WORK, $set, ['id' => $this->entryId]);
			if ( $this->isMultiUser() ) {
				$this->controller->em()->getConnection()->update(DBT_WORK_MULTI, ['deleted_at' => $curDate->format('Y-m-d H:i:s')], ['entry_id' => $this->entryId]);
			}
			$this->addMessage("Произведението „{$this->btitle}“ беше премахнато от списъка.");
			$this->deleteEntryFiles($this->entryId);
			$this->scanuser_view = null;

			return $this->makeLists();
		}

		if ( $this->handleUpload() && !empty($this->uplfile) ) {
			$set['tmpfiles'] = $set['uplfile'] = $this->makeTmpFilePath(self::rawurlencode($this->uplfile));
			$set['tfsize'] = Number::int_b2m(filesize("{$this->absTmpDir}/{$this->uplfile}"));
		}
		if ($this->entryId) {
			$this->controller->em()->getConnection()->update(DBT_WORK, $set, ['id' => $this->entryId]);
			$msg = 'Данните за произведението бяха обновени.';
		} else {
			$this->controller->em()->getConnection()->insert(DBT_WORK, $set);
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
			$set['uplfile'] = $this->makeTmpFilePath(self::rawurlencode($this->uplfile));
		}
		if ($this->entry->hasContribForUser($this->user)) {
			$this->controller->em()->getConnection()->update(DBT_WORK_MULTI, $set, $key);
			$msg = 'Данните бяха обновени.';
		} else {
			$set['id'] = $this->controller->em()->getNextIdRepository()->findNextId('App:WorkContrib')->getValue();
			$this->controller->em()->getConnection()->insert(DBT_WORK_MULTI, $set);
			$msg = 'Току-що се включихте в подготовката на произведението.';
			$this->informScanUser($this->entryId);
		}
		$this->addMessage($msg);
		// update main entry
		$set = [
			'date' => $this->date,
			'status' => $this->entry->hasOpenContribs() ? WorkEntry::STATUS_3 : ( $this->isReady() ? WorkEntry::STATUS_6 : WorkEntry::STATUS_5 ),
		];
		$this->controller->em()->getConnection()->update(DBT_WORK, $set, $pkey);

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

		$this->addMessage("Файлът беше качен. Благодарности за положения труд!");

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
		if ($this->entryId) {
			$this->initData($this->entryId);
		}
		$content = $this->makeUserGuideLink();
		if ($this->subaction == 'edit') {
			$content .= <<<HTML
	<div class="alert alert-info">
		<span class="fa fa-warning"></span>
		<strong>Внимание:</strong>
		Записвайте всяка книга и в <a href="//biblioman.chitanka.info/">проекта Библиоман</a>. Неговата структурирана база от данни ще се използва впоследствие и от „Моята библиотека“.
	</div>
HTML;
			$content .= $this->makeForm();
		} else {
			// a global RSS link should be added to the page
			$content .= $this->getInlineRssLink('workroom_rss') . $this->makeLists();
		}

		return $content;
	}

	private function makeUserGuideLink() {
		return '<div class="float-right"><a href="//wiki.chitanka.info/Workroom" title="Наръчник за работното ателие"><span class="fa fa-info-circle"></span> Наръчник за работното ателие</a></div>';
	}

	private function makeLists() {
		$o = $this->controller->renderViewForLegacyCode('Workroom/intro.html.twig', [
				'banYearThreshold' => $this->container->getParameter('workroom_ban_year_threshold'),
			])
			. $this->controller->renderViewForLegacyCode('Workroom/searchForm.html.twig', [
				'query' => $this->searchQuery,
				'viewTypes' => $this->viewTypes,
				'statuses' => $this->statuses,
				'subaction' => $this->subaction,
				'statusClasses' => $this->statusClasses,
			])
			. '<div class="standalone">'
				. ($this->userCanAddEntry() ? sprintf('<a href="%s" class="btn btn-primary"><span class="fa fa-plus"></span> Добавяне на нов запис</a>', $this->controller->generateUrlForLegacyCode('workroom_entry_new')) : '')
			. '</div>'
			;

		if ($this->viewList == 'work') {
			$o .= $this->makeWorkList();
		} else {
			$o .= $this->makeContribList();
		}

		return $o;
	}

	private function makeWorkList(Pagerfanta $pager = null) {
		return $this->controller->renderViewForLegacyCode('Workroom/listing.html.twig', [
			'pager' => ($pager ?: $this->createPager()),
			'userIsAdmin' => $this->userIsAdmin(),
			'statusClasses' => $this->statusClasses,
		]);
	}

	private function createPager($titleFilter = null) {
		$adapter = new DoctrineORMAdapter($this->query($titleFilter));
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage($this->llimit);
		$pager->setCurrentPage($this->lpage);
		return $pager;
	}

	private function query($titleFilter = null) {
		$query = $this->repo()->getQueryBuilder();
		$query->leftJoin('e.commentThread', 't')->addSelect('t');
		if ($titleFilter) {
			$query->andWhere('e.title = :title')->setParameter('title', $titleFilter);
		}
		$showuser = null;
		if ($this->subaction == 'my') {
			$showuser = $this->user;
		} else if ( ! empty($this->scanuser_view) ) {
			$showuser = $this->findUser($this->scanuser_view);
		}
		if ($showuser) {
			$entriesWithUserContribution = $this->contribRepo()->createQueryBuilder('ec')
				->select('identity(ec.entry)')
				->where('ec.user = :user AND ec.deletedAt IS NULL');
			$query->andWhere($query->expr()->orX(
				$query->expr()->eq('e.user', ":user"),
				$query->expr()->in('e.id', $entriesWithUserContribution->getDQL())
			))->setParameter('user', $showuser);
		} else if ($this->subaction == 'waiting') {
			$query->andWhere('e.type = 1')->andWhere('e.status = '.self::MAX_SCAN_STATUS);
		} else if ( strpos($this->subaction, 'st-') !== false ) {
			$query->andWhere('e.status = '.(int) str_replace('st-', '', $this->subaction));
		} else if ( ! empty($this->searchQuery) ) {
			$query->andWhere($query->expr()->orX(
				$query->expr()->like('e.title', ":search"),
				$query->expr()->like('e.author', ":search")
			))->setParameter('search', "%{$this->searchQuery}%");
		}
		if ($this->sfrequest->get('onlyAvailable')) {
			$query->andWhere(
				$query->expr()->orX(
					'e.availableAt <= '.date('Y-m-d'),
					'e.availableAt IS NULL'
				)
			);
		}
		return $query;
	}

	private function makeForm() {
		$entry = $this->entry ?: new WorkEntry();
		if (!$this->entry) {
			$this->title = 'Нов запис' .' — '. $this->title;
		}
		$helpTop = empty($this->entryId) ? $this->controller->renderViewForLegacyCode('Workroom/newEntryIntro.html.twig') : '';
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
				$url = $this->controller->generateUrlForLegacyCode($route, $params);
			}
			$tabs .= "<li class='$class'><a href='$url'>$text</a></li>";
		}
		if ( $this->isSingleUser() ) {
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

		$alertUnavailable = '';
		if ($entry->getAvailableAt()) {
			$alertUnavailable = $entry->canShowFilesTo($this->user)
				? '<div class="alert alert-info">Качените файлове ще бъдат достъпни за обикновените потребители след '.$entry->getAvailableAt('d.m.Y').'.</div>'
				: '<div class="alert alert-danger">Качените файлове ще бъдат налични след '.$entry->getAvailableAt('d.m.Y').'.</div>';
		}
		$alertIfDeleted = $entry->isDeleted() ? '<div class="alert alert-danger">Този запис е изтрит.</div>' : '';
		$helpBot = $this->isSingleUser() ? $this->controller->renderViewForLegacyCode('Workroom/singleUserEditIntro.html.twig') : '';
		$scanuser = $this->out->hiddenField('user', $this->scanuser);
		$workType = $this->out->hiddenField('workType', $this->workType);
		$bypass = $this->out->hiddenField('bypass', $this->bypassExisting);
		$action = $this->controller->generateUrlForLegacyCode('workroom');
		$this->addJs($this->createCommentsJavascript($this->entryId));

		$adminFields = $this->userIsAdmin() ? $this->makeAdminOnlyFields() : '';
		$user = $this->controller->em()->getUserRepository()->find($this->scanuser);
		$ulink = $this->makeUserLinkWithEmail($user->getUsername(), $user->getEmail(), $user->getAllowemail());
		$printPackTypeRadioValue = function ($packType) use ($entry) {
			return ('value="'.$packType.'"') . ($entry->hasPackType($packType) ? ' checked' : '');
		};

		return <<<EOS

$alertUnavailable
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
			{$this->out->hiddenField('id', $this->entryId)}
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
				<label for="title" class="col-sm-2 control-label">Вид на творението:</label>
				<div class="col-sm-10">
					<label class="radio-inline">
						<input type="radio" name="packType" {$printPackTypeRadioValue(WorkEntry::PACK_TYPE_BOOK)}> Книга
					</label>
					<label class="radio-inline">
						<input type="radio" name="packType" {$printPackTypeRadioValue(WorkEntry::PACK_TYPE_WORK)}> Произведение
					</label>
				</div>
			</div>
			<div class="form-group">
				<label for="title" class="col-sm-2 control-label">№ в Библиоман:</label>
				<div class="col-sm-10">
					<input class="form-control" name="bibliomanId" value="{$entry->getBibliomanId()}">
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

<div id="fos_comment_thread"></div>

<div id="helpBottom">
$helpBot
</div>
EOS;
	}

	private function createCommentsJavascript($entry) {
		if (empty($entry) || $this->user->isAnonymous()) {
			return '';
		}
		return $this->controller->renderViewForLegacyCode('Workroom/comments.js.twig', [
			'entry' => $entry,
			'user' => $this->controller->em()->getUserRepository()->find($this->scanuser),
		]);
	}

	private function makeSubmitButton() {
		$submit = $this->out->submitButton('Запис', '', null, true, ['class' => 'btn btn-primary']);
		$cancel = sprintf('<a href="%s" title="Към основния списък">Отказ</a>', $this->controller->generateUrlForLegacyCode('workroom'));

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
			: $this->out->link( $this->tmpfiles, Stringy::limitLength($this->tmpfiles)) .
			($this->tfsize > 0 ? " ($this->tfsize&#160;MiB)" : '');

		$form = <<<EOS
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
EOS;
		if ($this->entry && $this->entry->canShowFilesTo($this->user)) {
			$form .= <<<EOS
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
		return $form;
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
			if ( empty($this->contribsByUser) || $this->userIsSupervisor() ) {
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
			: $this->out->link( $this->tmpfiles, Stringy::limitLength($this->tmpfiles)) .
			($this->tfsize > 0 ? " ($this->tfsize&#160;MiB)" : '');
		$file = $this->out->fileField('file', '');
		$maxFileSize = $this->out->makeMaxFileSizeField();
		$maxUploadSizeInMiB = Legacy::getMaxUploadSizeInMiB();

		$form = <<<EOS
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
EOS;
		if ($this->entry && $this->entry->canShowFilesTo($this->user)) {
			$form .= <<<EOS
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
		return $form;
	}

	private function makeMultiEditInput() {
		$editorList = $this->controller->renderViewForLegacyCode('Workroom/contributors.html.twig', ['entry' => $this->entry]);
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
		if ( empty($this->contribsByUser[$this->user->getId()]) ) {
			$comment = $progress = $uplfile = $filesize = '';
			$isFrozen = false;
			$msg = '<p>Вие също може да се включите в подготовката на текста.</p>';
		} else {
			$contrib = $this->contribsByUser[$this->user->getId()];
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
		$action = $this->controller->generateUrlForLegacyCode('workroom');

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

	private function makeContribList() {
		$query = $this->controller->em()->createQuery('SELECT ut AS contrib, u, count(u.id) AS nbContribs, sum(ut.size) AS size FROM App:UserTextContrib ut JOIN ut.user u GROUP BY u.id ORDER BY size desc');
		return $this->controller->renderViewForLegacyCode('Workroom/contributorRanking.html.twig', [
			'rows' => $query->getResult()
		]);
	}

	private function initData($id) {
		/* @var $entry WorkEntry */
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
		$this->contribsByUser = [];
		foreach ($entry->getContribs() as $contrib) {
			$this->contribsByUser[$contrib->getUser()->getId()] = $contrib;
		}

		$this->entry = $entry;
		$this->title = $this->entry->getTitle() .' — '. $this->title;
	}

	private function isSingleUser() {
		return $this->workType == WorkEntry::TYPE_SINGLE_USER;
	}
	private function isMultiUser() {
		return $this->workType == WorkEntry::TYPE_MULTI_USER;
	}

	private function thisUserCanEditEntry(WorkEntry $entry, $type) {
		if ($this->user->isAnonymous()) {
			return false;
		}
		if ($this->userIsSupervisor() || $type == WorkEntry::TYPE_MULTI_USER) {
			return true;
		}
		return $entry->belongsTo($this->user);
	}

	private function thisUserCanDeleteEntry() {
		if ($this->userIsSupervisor() || empty($this->entryId)) {
			return true;
		}
		return $this->entry->belongsTo($this->user);
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
		return $this->user->inGroup(User::GROUP_WORKROOM_ADMIN);
	}

	private function userIsSupervisor() {
		return $this->user->inGroup([User::GROUP_WORKROOM_ADMIN, User::GROUP_WORKROOM_SUPERVISOR]);
	}

	private function userCanSetStatus($status) {
		switch ($status) {
			case WorkEntry::STATUS_7:
				return $this->user->inGroup(User::GROUP_WORKROOM_ADMIN);
			case WorkEntry::STATUS_6:
				return $this->user->inGroup([User::GROUP_WORKROOM_ADMIN, User::GROUP_WORKROOM_SUPERVISOR]);
			default:
				return $this->user->isAuthenticated();
		}
	}

	private function informScanUser($entryId) {
		$entry = $this->repo()->find($entryId);

		if ($entry->getUser()->getEmail() == '') {
			return;
		}
		$editLink = $this->controller->generateUrlForLegacyCode('workroom_entry_edit', ['id' => $entry->getId()], true);
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

	private function makeTmpFilePath($file) {
		if (preg_match('|https?://|', $file)) {
			return $file;
		}
		$root = $this->container->getParameter('workroom_root');
		return "$root/{$this->tmpDir}/{$file}";
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
		return Stringy::my_replace($text);
	}

	/** @return \App\Entity\WorkEntryRepository */
	private function repo() {
		return $this->controller->em()->getWorkEntryRepository();
	}

	/** @return \Doctrine\ORM\EntityRepository */
	private function contribRepo() {
		return $this->controller->em()->getWorkContribRepository();
	}
}
