<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\NoResultException;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Util\File;
use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Entity\TextRating;
use Chitanka\LibBundle\Entity\UserTextRead;
use Chitanka\LibBundle\Entity\TextLabel;
use Chitanka\LibBundle\Entity\Bookmark;
//use Chitanka\LibBundle\Form\Type\TextRatingType;
//use Chitanka\LibBundle\Form\Type\TextLabelType;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Legacy\ZipFile;
use Chitanka\LibBundle\Legacy\CacheManager;


class TextController extends Controller
{
	protected $repository = 'Text';

	public function indexAction($_format)
	{
		if ($_format == 'html') {
			$this->view = array(
				'labels' => $this->getLabelRepository()->getAllAsTree(),
				'types' => $this->getTextRepository()->getTypes(),
			);
		}

		return $this->display("index.$_format");
	}

	public function listByTypeIndexAction($_format)
	{
		$this->view['types'] = $this->getTextRepository()->getTypes();

		return $this->display("list_by_type_index.$_format");
	}

	public function listByLabelIndexAction($_format)
	{
		$this->view['labels'] = $this->getLabelRepository()->getAll();

		return $this->display("list_by_label_index.$_format");
	}

	public function listByAlphaIndexAction($_format)
	{
		return $this->display("list_by_alpha_index.$_format");
	}

	public function listByTypeAction($type, $page, $_format)
	{
		$textRepo = $this->getTextRepository();
		$limit = 30;

		$this->view = array_merge($this->view, array(
			'type' => $type,
			'texts'   => $textRepo->getByType($type, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByType($type)
			)),
			'route_params' => array('type' => $type),
		));

		return $this->display("list_by_type.$_format");
	}


	public function listByLabelAction($slug, $page, $_format)
	{
		$textRepo = $this->getTextRepository();
		$limit = 30;

		$slug = String::slugify($slug);
		$label = $this->getLabelRepository()->findBySlug($slug);
		if ($label === null) {
			throw new NotFoundHttpException("Няма етикет с код $slug.");
		}
		$labels = $label->getDescendantIdsAndSelf();

		$this->view = array_merge($this->view, array(
			'label' => $label,
			'parents' => array_reverse($label->getAncestors()),
			'texts'   => $textRepo->getByLabel($labels, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByLabel($labels)
			)),
			'route' => $this->getCurrentRoute(),
			'route_params' => array('slug' => $slug),
		));

		return $this->display("list_by_label.$_format");
	}


	public function listByAlphaAction($letter, $page, $_format)
	{
		$textRepo = $this->getTextRepository();
		$limit = 30;

		$prefix = $letter == '-' ? null : $letter;
		$this->view = array(
			'letter' => $letter,
			'texts' => $textRepo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByPrefix($prefix)
			)),
			'route_params' => array('letter' => $letter),
		);

		return $this->display("list_by_alpha.$_format");
	}


	public function showAction($id, $_format)
	{
		list($id) = explode('-', $id); // remove optional slug
		try {
			$text = $this->getTextRepository()->get($id);
		} catch (NoResultException $e) {
			throw new NotFoundHttpException("Няма текст с номер $id.");
		}

		switch ($_format) {
			case 'txt':
				return $this->displayText($text->getContentAsTxt(), array('Content-Type' => 'text/plain'));
			case 'fb2':
				Setup::doSetup($this->container);
				return $this->displayText($text->getContentAsFb2(), array('Content-Type' => 'application/xml'));
			case 'sfb':
				return $this->displayText($text->getContentAsSfb(), array('Content-Type' => 'text/plain'));
			case 'fbi':
				return $this->displayText($text->getFbi(), array('Content-Type' => 'text/plain'));
// 			case 'clue':
// 				return $this->displayText($text->getClue());
			case 'txt.zip':
				return $this->urlRedirect($this->getTxtZipFile(explode(',', $id), $_format));
			case 'fb2.zip':
				return $this->urlRedirect($this->getFb2ZipFile(explode(',', $id), $_format));
			case 'sfb.zip':
				return $this->urlRedirect($this->getSfbZipFile(explode(',', $id), $_format));
			case 'epub':
				return $this->urlRedirect($this->getEpubFile(explode(',', $id), $_format));
			case 'html':
			default:
				return $this->showHtml($text, 1);
		}
	}

	public function showPartAction($id, $part)
	{
		return $this->showHtml($this->getTextRepository()->get($id), $part);
	}

	public function showHtml($text, $part)
	{
		$this->responseAge = 86400; // 24 hours

		$nextHeader = $text->getNextHeaderByNr($part);
		$nextPart = $nextHeader ? $nextHeader->getNr() : 0;
		$this->view = array(
			'text' => $text,
			'authors' => $text->getAuthors(),
			'part' => $part,
			'next_part' => $nextPart,
			'obj_count' => 3, /* after annotation and extra info */
		);

		if (empty($nextPart)) {
			Setup::doSetup($this->container);
			$ids = $text->getSimilar(5, $this->getUser());
			$this->view['similar_texts'] = $ids ? $this->getTextRepository()->getByIds($ids) : array();
		}

		$this->view['js_extra'][] = 'text';

		return $this->display('show');
	}


	/**
	* TODO
	*/
	public function showMultiAction($id, $_format)
	{
		$mirror = $this->tryMirrorRedirect($id, $_format);
		$filename = $this->get('request')->get('filename');
		if ( ! empty( $filename ) ) {
			$mirror .= '&filename=' . urlencode($filename);
		}

		return $this->urlRedirect($mirror);
	}

	public function randomAction()
	{
		$id = $this->getTextRepository()->getRandomId();

		return $this->urlRedirect($this->generateUrl('text_show', array('id' => $id)));
	}


	public function commentsAction($id, $_format)
	{
		$this->responseAge = 0;

		$_REQUEST['id'] = $id;

		return $this->legacyPage('Comment');
	}


	public function ratingAction(Request $request, $id)
	{
		$text = $this->getTextRepository()->find($id);

		$em = $this->getEntityManager();
		$user = $em->merge($this->getUser());
		$rating = $this->getTextRatingRepository()->getByTextAndUser($text, $user);
		if ( ! $rating) {
			$rating = new TextRating($text, $user);
		}
		$form = $this->createForm('text_rating_form', $rating);

		// TODO replace with DoctrineListener
		$oldRating = $rating->getRating();

		if ($request->getMethod() == 'POST') {
			$form->bindRequest($request);
			if ($form->isValid() && $this->getUser()->isAuthenticated()) {
				// TODO replace with DoctrineListener
				$text->updateAvgRating($rating->getRating(), $oldRating);
				$this->getEntityManager()->persist($text);

				// TODO bind overwrites the Text object with an id
				$rating->setText($text);

				$rating->setCurrentDate();
				$em->persist($rating);
				$em->flush();
			}
		}

		$this->view = array(
			'text' => $text,
			'form' => $form->createView(),
			'rating' => $rating,
		);

		if ($request->isXmlHttpRequest() || $request->getMethod() == 'GET') {
			$this->responseAge = 0;

			return $this->display('rating');
		} else {
			return $this->redirectToText($text);
		}
	}

	public function newLabelAction(Request $request, $id)
	{
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw new HttpException(401, 'Нямате достатъчни права за това действие.');
		}

		$text = $this->getTextRepository()->find($id);
		if ($text === null) {
			throw new NotFoundHttpException("Няма текст с номер $id.");
		}

		$textLabel = new TextLabel;
		$textLabel->setText($text);
		$form = $this->createForm('text_label_form', $textLabel);

		$this->view = array(
			'text' => $text,
			'text_label' => $textLabel,
			'form' => $form->createView(),
		);

		if ($request->getMethod() == 'POST') {
			$form->bindRequest($request);
			if ($form->isValid()) {
				// TODO bind overwrites the Text object with an id
				$textLabel->setText($text);
				$text->addLabel($textLabel->getLabel());
				$em = $this->getEntityManager();
				$em->persist($text);
				$em->flush();
				if ($request->isXmlHttpRequest()) {
					$this->view['label'] = $textLabel->getLabel();
					return $this->display('label_view');
				} else {
					return $this->redirectToText($text);
				}
			}
		}

		return $this->display('new_label');
	}

	public function deleteLabelAction($id, $labelId)
	{
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw new HttpException(401, 'Нямате достатъчни права за това действие.');
		}

		$this->getTextRepository()->deleteTextLabel($id, $labelId)->flush();

		if ($this->get('request')->isXmlHttpRequest()) {
			return $this->displayText(1);
		} else {
			return $this->urlRedirect($this->generateUrl('text_show', array('id' => $id)));
		}
	}

	public function ratingsAction($id)
	{
		$_REQUEST['id'] = $id;

		return $this->legacyPage('Textrating');
	}


	public function markReadFormAction($id)
	{
		$this->responseAge = 0;

		if ($this->getUser()->isAuthenticated()) {
			$tr = $this->getUserTextReadRepository()->findOneBy(array('text' => $id, 'user' => $this->getUser()->getId()));
			if ($tr) {
				return new Response('Произведението е отбелязано като прочетено.');
			}
		}

		return $this->render('LibBundle:Text:mark_read_form.html.twig', array('id' => $id));
	}

	public function markReadAction($id)
	{
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw new HttpException(401, 'Нямате достатъчни права за това действие.');
		}

		$text = $this->getTextRepository()->find($id);
		if ($text === null) {
			throw new NotFoundHttpException("Няма текст с номер $id.");
		}

		$em = $this->getEntityManager();
		$textReader = new UserTextRead;
		$textReader->setUser($em->merge($this->getUser()));
		$textReader->setText($text);
		//$textReader->setCurrentDate();
		$em->persist($textReader);
		$em->flush();

		if ($this->get('request')->isXmlHttpRequest()) {
			return $this->displayJson('Произведението е отбелязано като прочетено.');
		} else {
			return $this->redirectToText($text);
		}
	}


	public function addBookmarkAction($id)
	{
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw new HttpException(401, 'Нямате достатъчни права за това действие.');
		}

		$text = $this->getTextRepository()->find($id);
		if ($text === null) {
			throw new NotFoundHttpException("Няма текст с номер $id.");
		}

		$em = $this->getEntityManager();
		$user = $em->merge($this->getUser());

		$folder = $this->getBookmarkFolderRepository()->getOrCreateForUser($user, 'favorities');
		$bookmark = $this->getBookmarkRepository()->findOneBy(array(
			'folder' => $folder->getId(),
			'text' => $text->getId(),
			'user' => $user->getId(),
		));
		if ($bookmark) { // an existing bookmark, remove it
			$em->remove($bookmark);
			$response = array(
				'removeClass' => 'active',
				'setTitle' => 'Добавяне в отметките',
			);
		} else {
			$bookmark = new Bookmark(compact('folder', 'text', 'user'));
			$user->addBookmark($bookmark);

			$em->persist($folder);
			$em->persist($bookmark);
			$em->persist($user);
			$response = array(
				'addClass' => 'active',
				'setTitle' => 'Премахване от отметките',
			);
		}
		$em->flush();

		if ($this->get('request')->isXmlHttpRequest()) {
			return $this->displayJson($response);
		} else {
			return $this->redirectToText($text);
		}
	}



	public function suggestAction($id, $object)
	{
		$_REQUEST['id'] = $id;
		$_REQUEST['object'] = $object;

		return $this->legacyPage('SuggestData');
	}




	public function redirectToText($text)
	{
		return $this->urlRedirect($this->generateUrl('text_show', array('id' => $text->getId())));
	}






	protected function _initZipData($textId, $format)
	{
		if ($redirect = $this->tryMirrorRedirect($textId, $format)) {
			return $redirect;
		}

		Setup::doSetup($this->container);

		$this->textIds = $textId;
		$this->zf = new ZipFile;
		$this->zipFileName = $this->get('request')->query->get('filename');
		// track here how many times a filename occurs
		$this->_fnameCount = array();

		return true;
	}


	protected function tryMirrorRedirect($ids, $format = null)
	{
		$dlSite = $this->getMirrorServer();

		if ( $dlSite !== false ) {
			$params = '?action=text&textId=';
			$params .= is_array($ids) ? implode(', ', $ids) : $ids;
			if ($format) {
				$params .= '.' . $format;
			}

			return $dlSite . $params;
		}

		return false;
	}

	public function createDlFiles($from = 1, $to = 3) {
		$this->user->setOption('dlcover', true);
		for ($i = $from; $i <= $to; $i++) {
			$this->textIds = array($i);
			$this->zf = new ZipFile;
			$this->createSfbDlFile();
			$this->zipFileName = '';
		}
		return 'Готово.';
	}


	protected function getTxtZipFile($id, $format)
	{
		if ($redirect = $this->_initZipData($id, $format)) {
			return $redirect;
		}

		$dlFile = $this->createTxtDlFile();

		return '/'.  $dlFile;
	}


	protected function getSfbZipFile($id, $format)
	{
		if ($redirect = $this->_initZipData($id, $format)) {
			return $redirect;
		}

		$dlFile = $this->createSfbDlFile();

		return '/' .  $dlFile;
	}


	protected function getFb2ZipFile($id, $format)
	{
		if ($redirect = $this->_initZipData($id, $format)) {
			return $redirect;
		}

		$dlFile = $this->createFb2DlFile();

		return '/' .  $dlFile;
	}


	protected function getEpubFile($textId, $format)
	{
		if ($redirect = $this->tryMirrorRedirect($textId, $format)) {
			return $redirect;
		}

		$file = null;
		$dlFile = new DownloadFile;
		if ( count($textId) > 1 ) {
			$file = $dlFile->getEpubForTexts($textId);
		} else if ( $text = $this->getTextRepository()->find($textId[0]) ) {
			$file = $dlFile->getEpubForText($text);
		}

		if ($file) {
			return $file;
		} else {
			$this->addMessage('Няма такъв текст.', true);
		}
	}


	protected function sendCluePlainContent() {
		$this->sendCommonHeaders();
		$clue = $this->makeAnnotation();
		$this->addTemplates();
		$clue = Legacy::expandTemplates($clue);
		$this->encprint( $clue );
		$this->outputDone = true;
	}


	/** Sfb */
	protected function createSfbDlFile()
	{
		$key = '';
		if ($this->withCover) $key .= '-cov';
		if ($this->withFbi)   $key .= '-fbi';
		$key .= '-sfb';
		return $this->createDlFile($this->textIds, 'sfb', $key);
	}

	protected function addSfbToDlFileFromCache($textId)
	{
		$fEntry = unserialize( CacheManager::getDlCache($textId), '.sfb' );
		$this->zf->addFileEntry($fEntry);
		if ( $this->withFbi ) {
			$this->zf->addFileEntry(
				unserialize( CacheManager::getDlCache($textId, '.fbi') ) );
		}
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	protected function addSfbToDlFileFromNew($textId)
	{
		$mainFileData = $this->getMainFileData($textId);
		if ( ! $mainFileData ) {
			return false;
		}
		list($this->filename, $this->fPrefix, $this->fSuffix, $fbi) = $mainFileData;
		$this->addTextFileEntry($textId, '.sfb');
		if ( $this->withFbi ) {
			$this->addMiscFileEntry($fbi, $textId, '.fbi');
		}
		return true;
	}

	protected function addSfbToDlFileEnd($textId)
	{
		$this->addBinaryFileEntries($textId, $this->filename);
		return true;
	}


	/** Fb2 */
	protected function createFb2DlFile()
	{
		return $this->createDlFile($this->textIds, 'fb2');
	}

	protected function addFb2ToDlFileFromCache($textId)
	{
		$fEntry = unserialize( CacheManager::getDlCache($textId, '.fb2') );
		$this->zf->addFileEntry($fEntry);
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	protected function addFb2ToDlFileFromNew($textId)
	{
		$work = $this->getTextRepository()->find($textId);
		if ( ! $work ) {
			return false;
		}
		$this->filename = $this->getFileName($work);
		$this->addMiscFileEntry($work->getContentAsFb2(), $textId, '.fb2');
		return true;
	}


	/** Txt */
	protected function createTxtDlFile()
	{
		return $this->createDlFile($this->textIds, 'txt');
	}

	protected function addTxtToDlFileFromCache($textId)
	{
		$fEntry = unserialize( CacheManager::getDlCache($textId, '.txt') );
		$this->zf->addFileEntry($fEntry);
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	protected function addTxtToDlFileFromNew($textId)
	{
		$work = $this->getTextRepository()->find($textId);
		if ( ! $work ) {
			return false;
		}
		$this->filename = $this->getFileName($work);
		$this->addMiscFileEntry($work->getContentAsTxt(), $textId, '.txt');
		return true;
	}


	/** Common */
	protected function createDlFile($textIds, $format, $dlkey = null)
	{
		$textIds = $this->normalizeTextIds($textIds);
		if (is_null($dlkey)) $dlkey = ".$format";

		$dlCache = CacheManager::getDl($textIds, $dlkey);
		if ( $dlCache ) {
			$dlFile = CacheManager::getDlFile($dlCache);
			if ( file_exists($dlFile) && filesize($dlFile) ) {
				return $dlFile;
			}
		}

		$fileCount = count($textIds);
		$setZipFileName = $fileCount == 1 && empty($this->zipFileName);

		foreach ($textIds as $textId) {
			#$this->user->markTextAsDl($textId);
			$method = 'add' . ucfirst($format) . 'ToDlFileFrom';
			$method .= CacheManager::dlCacheExists($textId, ".$format") ? 'Cache' : 'New';
			if ( ! $this->$method($textId) ) {
				$fileCount--; // no file was added
				continue;
			}
			$sharedMethod = 'add' . ucfirst($format) . 'ToDlFileEnd';
			if ( method_exists($this, $sharedMethod) ) {
				$this->$sharedMethod($textId);
			}
			if ($setZipFileName) {
				$this->zipFileName = $this->filename;
			}
		}
		if ( $fileCount < 1 ) {
			$this->addMessage('Не е посочен валиден номер на текст за сваляне!', true);
			return null;
		}

		if ( ! $setZipFileName && empty($this->zipFileName) ) {
			$this->zipFileName = "Архив от $this->sitename - $fileCount файла-".time();
		}

		$this->zipFileName .= $fileCount > 1 ? "-$format" : $dlkey;
		$this->zipFileName = File::cleanFileName( Char::cyr2lat($this->zipFileName) );
		$fullZipFileName = $this->zipFileName . '.zip';

		CacheManager::setDlFile($fullZipFileName, $this->zf->file());
		CacheManager::setDl($textIds, $fullZipFileName, $dlkey);
		return CacheManager::getDlFile($fullZipFileName);
	}


	protected function normalizeTextIds($textIds)
	{
		foreach ($textIds as $key => $textId) {
			if ( ! $textId || ! is_numeric($textId) ) {
				unset($textIds[$key]);
			}
		}
		sort($textIds);
		$textIds = array_unique($textIds);
		return $textIds;
	}


	protected function addTextFileEntry($textId, $suffix = '.txt') {
		$fEntry = $this->zf->newFileEntry($this->fPrefix .
			$this->getContentData($textId) ."\n\n\tКРАЙ".
			$this->fSuffix, $this->filename . $suffix);
		CacheManager::setDlCache($textId, serialize($fEntry));
		$this->zf->addFileEntry($fEntry);
	}


	protected function addMiscFileEntry($content, $textId, $suffix = '.txt') {
		$fEntry = $this->zf->newFileEntry($content, $this->filename . $suffix);
		CacheManager::setDlCache($textId, serialize($fEntry), $suffix);
		$this->zf->addFileEntry($fEntry);
	}


	protected function addBinaryFileEntries($textId, $filename) {
		// add covers
		if ( $this->withCover ) {
			foreach (Text::getCovers($textId) as $file) {
				$ename = parent::renameCover(basename($file), $filename);
				$fEntry = $this->zf->newFileEntry(file_get_contents($file), $ename);
				$this->zf->addFileEntry($fEntry);
			}
		}
		// add images
		$dir = Legacy::getContentFilePath('img', $textId);
		if ( !is_dir($dir) ) { return; }
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				$fullname = "$dir/$file";
				if ( $file[0] == '.' || $file[0] == '_' ||
					File::isArchive($file) || is_dir($fullname) ) { continue; }
				$fEntry = $this->zf->newFileEntry(file_get_contents($fullname), $file);
				$this->zf->addFileEntry($fEntry);
			}
			closedir($dh);
		}
	}


	protected function getContentData($textId) {
		$fname = Legacy::getContentFilePath('text', $textId);
		if ( file_exists($fname) ) {
			return file_get_contents($fname);
		}
		return '';
	}


	protected function getMainFileData($textId)
	{
		$work = $this->getTextRepository()->find($textId);
		return array(
			$this->getFileName($work),
			$this->getFileDataPrefix($work, $textId),
			$this->getFileDataSuffix($work, $textId),
			$this->getTextFileStart() . $work->getFbi()
		);
	}


	public function getFileName($work = null)
	{
		if ( is_null($work) ) $work = $this->work;

		return $this->_getUniqueFileName($work->getNameForFile());
	}


	private function _getUniqueFileName($filename)
	{
		if ( isset( $this->_fnameCount[$filename] ) ) {
			$this->_fnameCount[$filename]++;
			$filename .= $this->_fnameCount[$filename];
		} else {
			$this->_fnameCount[$filename] = 1;
		}
		return $filename;
	}


	public function getFileDataPrefix($work, $textId)
	{
		$prefix = $this->getTextFileStart()
			. "|\t$work->author_name\n"
			. $work->getTitleAsSfb()
			. "\n\n\n";
		$anno = $work->getAnnotation();
		if ( !empty($anno) ) {
			$prefix .= "A>\n$anno\nA$\n\n\n";
		}
		return $prefix;
	}


	public function getFileDataSuffix($work, $textId)
	{
		$suffix = "\n"
			. 'I>'
			. $work->getFullExtraInfo()
			. "I$\n";
		$suffix = preg_replace('/\n\n+/', "\n\n", $suffix);
		return $suffix;
	}


	static public function getTextFileStart()
	{
		return "\xEF\xBB\xBF" . // Byte order mark for some windows software
			"\t[Kodirane UTF-8]\n\n";
	}


	private function rmFEntrySuffix($fEntryName) {
		return preg_replace('/\.[^.]+$/', '', $fEntryName);
	}

}
