<?php namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Text;
use App\Entity\TextRating;
use App\Entity\UserTextRead;
use App\Form\Type\TextRatingType;
use App\Form\Type\TextLabelType;
use App\Legacy\Setup;
use App\Pagination\Pager;
use App\Generator\TextDownloadService;
use App\Service\TextBookmarkService;
use App\Service\TextLabelService;
use App\Util\String;

class TextController extends Controller {

	public function indexAction($_format) {
		if ($_format == 'html') {
			$this->view = array(
				'labels' => $this->em()->getLabelRepository()->getAllAsTree(),
				'types' => $this->em()->getTextRepository()->getTypes(),
			);
		}

		return $this->display("index.$_format");
	}

	public function listByTypeIndexAction($_format) {
		return $this->display("list_by_type_index.$_format", array(
			'types' => $this->em()->getTextRepository()->getTypes()
		));
	}

	public function listByLabelIndexAction($_format) {
		return $this->display("list_by_label_index.$_format", array(
			'labels' => $this->em()->getLabelRepository()->getAll()
		));
	}

	public function listByAlphaIndexAction($_format) {
		return $this->display("list_by_alpha_index.$_format");
	}

	public function listByTypeAction($type, $page, $_format) {
		$textRepo = $this->em()->getTextRepository();
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

	public function listByLabelAction($slug, $page, $_format) {
		$textRepo = $this->em()->getTextRepository();
		$limit = 30;

		$slug = String::slugify($slug);
		$label = $this->em()->getLabelRepository()->findBySlug($slug);
		if ($label === null) {
			throw $this->createNotFoundException("Няма етикет с код $slug.");
		}
		$labels = $label->getDescendantIdsAndSelf();

		return $this->display("list_by_label.$_format", array(
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
	}

	public function listByAlphaAction($letter, $page, $_format) {
		$textRepo = $this->em()->getTextRepository();
		$limit = 30;
		$prefix = $letter == '-' ? null : $letter;

		return $this->display("list_by_alpha.$_format", array(
			'letter' => $letter,
			'texts' => $textRepo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByPrefix($prefix)
			)),
			'route_params' => array('letter' => $letter),
		));
	}

	public function showAction(Request $request, $id, $_format) {
		if ($this->canRedirectToMirror($_format) && ($mirrorServer = $this->getMirrorServer())) {
			return $this->redirectToMirror($mirrorServer, $id, $_format, $request->get('filename'));
		}
		list($id) = explode('-', $id); // remove optional slug
		switch ($_format) {
			case 'html':
				return $this->showHtml($this->findText($id, true));
			case 'epub':
			case 'fb2.zip':
			case 'txt.zip':
			case 'sfb.zip':
				Setup::doSetup($this->container);
				$service = new TextDownloadService($this->em()->getTextRepository());
				return $this->urlRedirect($this->getWebRoot() . $service->generateFile(explode(',', $id), $_format, $request->get('filename')));
			case 'fb2':
				Setup::doSetup($this->container);
				return $this->displayText($this->findText($id, true)->getContentAsFb2(), array('Content-Type' => 'application/xml'));
			case 'fbi':
				Setup::doSetup($this->container);
				return $this->displayText($this->findText($id, true)->getFbi(), array('Content-Type' => 'text/plain'));
			case 'txt':
				return $this->displayText($this->findText($id, true)->getContentAsTxt(), array('Content-Type' => 'text/plain'));
			case 'sfb':
				return $this->displayText($this->findText($id, true)->getContentAsSfb(), array('Content-Type' => 'text/plain'));
			case 'data':
				return $this->displayText($this->findText($id, true)->getDataAsPlain(), array('Content-Type' => 'text/plain'));
		}
		throw $this->createNotFoundException("Неизвестен формат: $_format");
	}

	private function canRedirectToMirror($format) {
		return in_array($format, array(
			'epub',
			'fb2.zip',
			'txt.zip',
			'sfb.zip',
		));
	}

	private function redirectToMirror($mirrorServer, $id, $format, $requestedFilename) {
		return $this->urlRedirect("$mirrorServer/text/$id.$format?filename=$requestedFilename");
	}

	public function showPartAction($id, $part) {
		return $this->showHtml($this->findText($id, true), $part);
	}

	public function showHtml(Text $text, $part = 1) {
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
			$alikes = $text->getAlikes();
			$this->view['similar_texts'] = $alikes ? $this->em()->getTextRepository()->getByIds(array_slice($alikes, 0, 30)) : array();
		}

		$this->view['js_extra'][] = 'text';

		return $this->display('show');
	}

	public function randomAction() {
		$id = $this->em()->getTextRepository()->getRandomId();

		return $this->urlRedirect($this->generateUrl('text_show', array('id' => $id)));
	}

	public function similarAction($id) {
		$text = $this->findText($id);
		$alikes = $text->getAlikes();
		$this->view = array(
			'text' => $text,
			'similar_texts' => $alikes ? $this->em()->getTextRepository()->getByIds(array_slice($alikes, 0, 30)) : array(),
		);
		return $this->display('similar');
	}

	public function ratingAction(Request $request, $id) {
		$text = $this->findText($id);

		$user = $this->em()->merge($this->getUser());
		$rating = $this->em()->getTextRatingRepository()->getByTextAndUser($text, $user);
		$form = $this->createForm(new TextRatingType, $rating);

		// TODO replace with DoctrineListener
		$oldRating = $rating->getRating();

		if ($request->isMethod('POST') && $user->isAuthenticated() && $form->submit($request)->isValid()) {
			// TODO replace with DoctrineListener
			$text->updateAvgRating($rating->getRating(), $oldRating);
			$this->em()->getTextRepository()->save($text);

			// TODO bind overwrites the Text object with an id
			$rating->setText($text);

			$rating->setCurrentDate();
			$this->em()->getTextRatingRepository()->save($rating);
		}

		if ($request->isXmlHttpRequest() || $request->isMethod('GET')) {
			$this->disableCache();
			return $this->display('rating', array(
				'text' => $text,
				'form' => $form->createView(),
				'rating' => $rating,
			));
		}
		return $this->redirectToText($text);
	}

	public function newLabelAction(Request $request, $id) {
		$this->disableCache();

		if (!$this->getUser()->canPutTextLabel()) {
			throw $this->createAccessDeniedException();
		}
		$text = $this->findText($id);
		$service = new TextLabelService($this->em()->getTextLabelLogRepository(), $this->getSavableUser());
		$textLabel = $service->newTextLabel($text);
		$form = $this->createForm(new TextLabelType, $textLabel);

		if ($form->handleRequest($request)->isValid()) {
			// TODO Form::handleRequest() overwrites the Text object with an id, so we give $text explicitly
			$service->addTextLabel($textLabel, $text);
			if ($request->isXmlHttpRequest()) {
				return $this->display('label_view', array('label' => $textLabel->getLabel()));
			}
			return $this->redirectToText($text);
		}

		return $this->display('new_label', array(
			'text' => $text,
			'text_label' => $textLabel,
			'form' => $form->createView(),
		));
	}

	public function deleteLabelAction(Request $request, $id, $labelId) {
		$this->disableCache();

		if (!$this->getUser()->canPutTextLabel()) {
			throw $this->createAccessDeniedException();
		}
		$text = $this->findText($id);
		$label = $this->findLabel($labelId);
		$service = new TextLabelService($this->em()->getTextLabelLogRepository(), $this->getSavableUser());
		$service->removeTextLabel($text, $label);

		if ($request->isXmlHttpRequest()) {
			return $this->displayText(1);
		}
		return $this->redirectToText($text);
	}

	public function labelLogAction($id) {
		$text = $this->findText($id);
		$log = $this->em()->getTextLabelLogRepository()->getForText($text);
		return $this->display('label_log', array(
			'text' => $text,
			'log' => $log,
		));
	}

	public function fullLabelLogAction(Request $request) {
		$page = $request->get('page', 1);
		$limit = 30;
		$repo = $this->em()->getTextLabelLogRepository();
		return $this->display('label_log', array(
			'log' => $repo->getAll($page, $limit),
			'pager' => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->count()
			)),
		));
	}

	/**
	 * Show all ratings for given text.
	 * @param int $id Text ID
	 */
	public function ratingsAction($id) {
		$text = $this->findText($id);
		$ratings = $this->em()->getTextRatingRepository()->getByText($text);
		return $this->display('ratings', array(
			'text' => $text,
			'ratings' => $ratings,
		));
	}

	public function markReadFormAction($id) {
		$this->disableCache();

		if ($this->getUser()->isAuthenticated()) {
			$tr = $this->em()->getUserTextReadRepository()->findOneBy(array('text' => $id, 'user' => $this->getUser()->getId()));
			if ($tr) {
				return new Response('Произведението е отбелязано като прочетено.');
			}
		}

		return $this->render('App:Text:mark_read_form.html.twig', array('id' => $id));
	}

	public function markReadAction(Request $request, $id) {
		$this->disableCache();

		if ( ! $this->getUser()->isAuthenticated()) {
			throw $this->createAccessDeniedException();
		}

		$text = $this->findText($id);
		$this->em()->getUserTextReadRepository()->save(new UserTextRead($this->getSavableUser(), $text));

		if ($request->isXmlHttpRequest()) {
			return $this->displayJson('Произведението е отбелязано като прочетено.');
		}
		return $this->redirectToText($text);
	}

	public function addBookmarkAction(Request $request, $id) {
		$this->disableCache();

		if ( ! $this->getUser()->isAuthenticated()) {
			throw $this->createAccessDeniedException();
		}

		$text = $this->findText($id);
		$service = new TextBookmarkService($this->em()->getBookmarkRepository(), $this->em()->getBookmarkFolderRepository(), $this->getSavableUser());
		$bookmark = $service->addBookmark($text);

		if ($request->isXmlHttpRequest()) {
			$response = $bookmark
				? array('addClass' => 'active', 'setTitle' => 'Премахване от Избрани')
				: array('removeClass' => 'active', 'setTitle' => 'Добавяне в Избрани');
			return $this->displayJson($response);
		}
		return $this->redirectToText($text);
	}

	/**
	 * @param Text $text
	 */
	protected function redirectToText($text) {
		$id = $text instanceof Text ? $text->getId() : $text;
		return $this->urlRedirect($this->generateUrl('text_show', array('id' => $id)));
	}

	protected function findText($textId, $bailIfNotFound = true, $fetchRelations = false) {
		$text = $this->em()->getTextRepository()->get($textId, $fetchRelations);
		if ($bailIfNotFound && $text === null) {
			throw $this->createNotFoundException("Няма текст с номер $textId.");
		}
		return $text;
	}

	protected function findLabel($labelId, $bailIfNotFound = true) {
		$label = $this->em()->getLabelRepository()->find($labelId);
		if ($bailIfNotFound && $label === null) {
			throw $this->createNotFoundException("Няма етикет с номер $labelId.");
		}
		return $label;
	}

}
