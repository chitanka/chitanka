<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\NoResultException;
use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Entity\TextRating;
use Chitanka\LibBundle\Entity\UserTextRead;
use Chitanka\LibBundle\Form\Type\TextRatingType;
use Chitanka\LibBundle\Form\Type\TextLabelType;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Service\TextBookmarkService;
use Chitanka\LibBundle\Service\TextDownloadService;
use Chitanka\LibBundle\Service\TextLabelService;
use Chitanka\LibBundle\Util\String;


class TextController extends Controller {

	protected $responseAge = 86400; // 24 hours

	public function indexAction($_format) {
		if ($_format == 'html') {
			$this->view = array(
				'labels' => $this->getLabelRepository()->getAllAsTree(),
				'types' => $this->getTextRepository()->getTypes(),
			);
		}

		return $this->display("index.$_format");
	}

	public function listByTypeIndexAction($_format) {
		$this->view['types'] = $this->getTextRepository()->getTypes();

		return $this->display("list_by_type_index.$_format");
	}

	public function listByLabelIndexAction($_format) {
		$this->view['labels'] = $this->getLabelRepository()->getAll();

		return $this->display("list_by_label_index.$_format");
	}

	public function listByAlphaIndexAction($_format) {
		return $this->display("list_by_alpha_index.$_format");
	}

	public function listByTypeAction($type, $page, $_format) {
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


	public function listByLabelAction($slug, $page, $_format) {
		$textRepo = $this->getTextRepository();
		$limit = 30;

		$slug = String::slugify($slug);
		$label = $this->getLabelRepository()->findBySlug($slug);
		if ($label === null) {
			return $this->notFound("Няма етикет с код $slug.");
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


	public function listByAlphaAction($letter, $page, $_format) {
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


	public function showAction(Request $request, $id, $_format) {
		list($id) = explode('-', $id); // remove optional slug
		try {
			$text = $this->getTextRepository()->get($id);
		} catch (NoResultException $e) {
			return $this->notFound("Няма текст с номер $id.");
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
				Setup::doSetup($this->container);
				return $this->displayText($text->getFbi(), array('Content-Type' => 'text/plain'));
			case 'data':
				return $this->displayText($text->getDataAsPlain(), array('Content-Type' => 'text/plain'));
			case 'html':
				return $this->showHtml($text, 1);
		}
		if ($redirect = $this->tryMirrorRedirect($id, $_format)) {
			return $redirect;
		}
		Setup::doSetup($this->container);
		$service = new TextDownloadService($this->getTextRepository());
		switch ($_format) {
			case 'txt.zip':
				return $this->urlRedirect($this->getWebRoot() . $service->getTxtZipFile(explode(',', $id), $_format, $request->get('filename')));
			case 'fb2.zip':
				return $this->urlRedirect($this->getWebRoot() . $service->getFb2ZipFile(explode(',', $id), $_format, $request->get('filename')));
			case 'sfb.zip':
				return $this->urlRedirect($this->getWebRoot() . $service->getSfbZipFile(explode(',', $id), $_format, $request->get('filename')));
			case 'epub':
				return $this->urlRedirect($this->getWebRoot() . $service->getEpubFile(explode(',', $id), $_format, $request->get('filename')));
		}
		throw new \Exception("Неизвестен формат: $_format");
	}

	public function showPartAction($id, $part) {
		return $this->showHtml($this->getTextRepository()->get($id), $part);
	}

	public function showHtml(Text $text, $part) {
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
			$this->view['similar_texts'] = $alikes ? $this->getTextRepository()->getByIds(array_slice($alikes, 0, 30)) : array();
		}

		$this->view['js_extra'][] = 'text';

		return $this->display('show');
	}


	public function showMultiAction(Request $request, $id, $_format) {
		$mirror = $this->tryMirrorRedirect(explode(',', $id), $_format);
		$requestedFilename = $request->get('filename');
		if ($mirror) {
			if ($requestedFilename) {
				$mirror .= '?filename=' . urlencode($requestedFilename);
			}
			return $this->urlRedirect($mirror);
		}

		Setup::doSetup($this->container);
		$service = new TextDownloadService($this->getTextRepository());
		switch ($_format) {
			case 'txt.zip':
				return $this->urlRedirect($this->getWebRoot() . $service->getTxtZipFile(explode(',', $id), $_format, $requestedFilename));
			case 'fb2.zip':
				return $this->urlRedirect($this->getWebRoot() . $service->getFb2ZipFile(explode(',', $id), $_format, $requestedFilename));
			case 'sfb.zip':
				return $this->urlRedirect($this->getWebRoot() . $service->getSfbZipFile(explode(',', $id), $_format, $requestedFilename));
			case 'epub':
				return $this->urlRedirect($this->getWebRoot() . $service->getEpubFile(explode(',', $id), $_format, $requestedFilename));
		}
		throw new \Exception("Неизвестен формат: $_format");
	}

	public function randomAction() {
		$id = $this->getTextRepository()->getRandomId();

		return $this->urlRedirect($this->generateUrl('text_show', array('id' => $id)));
	}


	public function commentsAction($id, $_format) {
		$this->disableCache();

		$_REQUEST['id'] = $id;

		return $this->legacyPage('Comment');
	}

	public function similarAction($id) {
		$text = $this->findText($id);
		$alikes = $text->getAlikes();
		$this->view = array(
			'text' => $text,
			'similar_texts' => $alikes ? $this->getTextRepository()->getByIds(array_slice($alikes, 0, 30)) : array(),
		);
		return $this->display('similar');
	}

	public function ratingAction(Request $request, $id) {
		$text = $this->findText($id);

		$em = $this->getEntityManager();
		$user = $em->merge($this->getUser());
		$rating = $this->getTextRatingRepository()->getByTextAndUser($text, $user);
		if ( ! $rating) {
			$rating = new TextRating($text, $user);
		}
		$form = $this->createForm(new TextRatingType, $rating);

		// TODO replace with DoctrineListener
		$oldRating = $rating->getRating();

		if ($request->getMethod() == 'POST') {
			$form->bind($request);
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

		if ($request->isXmlHttpRequest() || $request->isMethod('GET')) {
			$this->disableCache();
			return $this->display('rating');
		}
		return $this->redirectToText($text);
	}

	public function newLabelAction(Request $request, $id) {
		$this->disableCache();

		if (!$this->getUser()->canPutTextLabel()) {
			return $this->notAllowed();
		}
		$text = $this->findText($id);
		$service = new TextLabelService($this->getEntityManager(), $this->getSavableUser());
		$textLabel = $service->newTextLabel($text);
		$form = $this->createForm(new TextLabelType, $textLabel);

		$this->view = array(
			'text' => $text,
			'text_label' => $textLabel,
			'form' => $form->createView(),
		);

		if ($request->isMethod('POST') && $form->submit($request)->isValid()) {
			$service->addTextLabel($textLabel, $text);
			if ($request->isXmlHttpRequest()) {
				$this->view['label'] = $textLabel->getLabel();
				return $this->display('label_view');
			}
			return $this->redirectToText($text);
		}

		return $this->display('new_label');
	}

	public function deleteLabelAction(Request $request, $id, $labelId) {
		$this->disableCache();

		if (!$this->getUser()->canPutTextLabel()) {
			return $this->notAllowed();
		}
		$text = $this->findText($id);
		$label = $this->findLabel($labelId);
		$service = new TextLabelService($this->getEntityManager(), $this->getSavableUser());
		$service->removeTextLabel($text, $label);

		if ($request->isXmlHttpRequest()) {
			return $this->displayText(1);
		}
		return $this->redirectToText($text);
	}

	public function labelLogAction($id) {
		$text = $this->findText($id);
		$log = $this->getRepository('TextLabelLog')->getForText($text);
		return $this->display('label_log', array(
			'text' => $text,
			'log' => $log,
		));
	}

	public function fullLabelLogAction(Request $request) {
		$page = $request->get('page', 1);
		$limit = 30;
		$repo = $this->getRepository('TextLabelLog');
		return $this->display('label_log', array(
			'log' => $repo->getAll($page, $limit),
			'pager' => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->count()
			)),
		));
	}

	public function ratingsAction($id) {
		$_REQUEST['id'] = $id;

		return $this->legacyPage('Textrating');
	}


	public function markReadFormAction($id) {
		$this->disableCache();

		if ($this->getUser()->isAuthenticated()) {
			$tr = $this->getUserTextReadRepository()->findOneBy(array('text' => $id, 'user' => $this->getUser()->getId()));
			if ($tr) {
				return new Response('Произведението е отбелязано като прочетено.');
			}
		}

		return $this->render('LibBundle:Text:mark_read_form.html.twig', array('id' => $id));
	}

	public function markReadAction(Request $request, $id) {
		$this->disableCache();

		if ( ! $this->getUser()->isAuthenticated()) {
			return $this->notAllowed();
		}

		$text = $this->findText($id);
		$textReader = new UserTextRead;
		$textReader->setUser($this->getSavableUser());
		$textReader->setText($text);
		//$textReader->setCurrentDate();
		$em = $this->getEntityManager();
		$em->persist($textReader);
		$em->flush();

		if ($request->isXmlHttpRequest()) {
			return $this->displayJson('Произведението е отбелязано като прочетено.');
		}
		return $this->redirectToText($text);
	}


	public function addBookmarkAction(Request $request, $id) {
		$this->disableCache();

		if ( ! $this->getUser()->isAuthenticated()) {
			return $this->notAllowed();
		}

		$text = $this->findText($id);
		$service = new TextBookmarkService($this->getEntityManager(), $this->getSavableUser());
		$bookmark = $service->addBookmark($text);

		if ($request->isXmlHttpRequest()) {
			$response = $bookmark
				? array('addClass' => 'active', 'setTitle' => 'Премахване от Избрани')
				: array('removeClass' => 'active', 'setTitle' => 'Добавяне в Избрани');
			return $this->displayJson($response);
		}
		return $this->redirectToText($text);
	}



	public function suggestAction($id, $object) {
		$_REQUEST['id'] = $id;
		$_REQUEST['object'] = $object;

		return $this->legacyPage('SuggestData');
	}




	protected function redirectToText($text) {
		$id = $text instanceof Text ? $text->getId() : $text;
		return $this->urlRedirect($this->generateUrl('text_show', array('id' => $id)));
	}

	protected function findText($textId, $bailIfNotFound = true) {
		$text = $this->getTextRepository()->find($textId);
		if ($bailIfNotFound && $text === null) {
			return $this->notFound("Няма текст с номер $textId.");
		}
		return $text;
	}

	protected function findLabel($labelId, $bailIfNotFound = true) {
		$label = $this->getLabelRepository()->find($labelId);
		if ($bailIfNotFound && $label === null) {
			return $this->notFound("Няма етикет с номер $labelId.");
		}
		return $label;
	}

	private function tryMirrorRedirect($ids, $format = null) {
		$dlSite = $this->getMirrorServer();
		if (!$dlSite) {
			return false;
		}
		$ids = (array) $ids;
		$url = (count($ids) > 1 ? '/text-multi/' : '/text/') . implode(',', $ids);
		if ($format) {
			$url .= '.' . $format;
		}
		return $dlSite . $url;
	}

}
