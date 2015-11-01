<?php namespace App\Controller;

use App\Entity\Text;
use App\Entity\UserTextRead;
use App\Form\Type\TextRatingType;
use App\Form\Type\TextLabelType;
use App\Legacy\Setup;
use App\Pagination\Pager;
use App\Generator\TextDownloadService;
use App\Service\TextBookmarkService;
use App\Service\TextLabelService;
use App\Service\SearchService;
use App\Util\String;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TextController extends Controller {

	public function indexAction($_format) {
		if (in_array($_format, ['html', 'json'])) {
			return [
				'labels' => $this->em()->getLabelRepository()->getAllAsTree(),
				'types' => $this->em()->getTextRepository()->getTypes(),
			];
		}

		return [];
	}

	public function listByTypeIndexAction() {
		return [
			'types' => $this->em()->getTextRepository()->getTypes()
		];
	}

	public function listByLabelIndexAction() {
		return [
			'labels' => $this->em()->getLabelRepository()->getAll()
		];
	}

	public function listByAlphaIndexAction() {
		return [];
	}

	public function listByTypeAction(Request $request, $type, $page) {
		$textRepo = $this->em()->getTextRepository();
		$limit = 30;

		return [
			'type' => $type,
			'texts'   => $textRepo->getByType($type, $page, $limit, $request->query->get('sort')),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByType($type)
			]),
			'route_params' => ['type' => $type],
		];
	}

	public function listByLabelAction(Request $request, $slug, $page) {
		$textRepo = $this->em()->getTextRepository();
		$limit = 30;

		$slug = String::slugify($slug);
		$label = $this->em()->getLabelRepository()->findBySlug($slug);
		if ($label === null) {
			throw $this->createNotFoundException("Няма етикет с код $slug.");
		}
		$labels = $label->getDescendantIdsAndSelf();

		return [
			'label' => $label,
			'parents' => array_reverse($label->getAncestors()),
			'texts'   => $textRepo->getByLabel($labels, $page, $limit, $request->query->get('sort')),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByLabel($labels)
			]),
			'route_params' => ['slug' => $slug],
		];
	}

	public function listByAlphaAction(Request $request, $letter, $page) {
		$textRepo = $this->em()->getTextRepository();
		$limit = 30;
		$prefix = $letter == '-' ? null : $letter;

		return [
			'letter' => $letter,
			'texts' => $textRepo->getByPrefix($prefix, $page, $limit, $request->query->get('sort')),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $textRepo->countByPrefix($prefix)
			]),
			'route_params' => ['letter' => $letter],
		];
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
				return $this->asText($this->findText($id, true)->getContentAsFb2(), 'application/xml');
			case 'fbi':
				Setup::doSetup($this->container);
				return $this->asText($this->findText($id, true)->getFbi());
			case 'txt':
				return $this->asText($this->findText($id, true)->getContentAsTxt());
			case 'sfb':
				return $this->asText($this->findText($id, true)->getContentAsSfb());
			case 'data':
				return $this->asText($this->findText($id, true)->getDataAsPlain());
		}
		throw $this->createNotFoundException("Неизвестен формат: $_format");
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$texts = $this->em()->getTextRepository()->getByQuery([
				'text'  => $query,
				'by'    => 'title',
				'match' => 'prefix',
				'limit' => 10,
			]);
			foreach ($texts as $text) {
				$items[] = $text['title'];
				$descs[] = '';
				$urls[] = $this->generateUrl('text_show', ['id' => $text['id']], true);
			}

			return $this->asJson([$query, $items, $descs, $urls]);
		}
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,origTitle';
		}
		$texts = $this->em()->getTextRepository()->getByQuery($query);
		$found = count($texts) > 0;
		return [
			'query' => $query,
			'texts' => $texts,
			'found' => $found,
			'_status' => !$found ? 404 : null,
		];
	}

	private function canRedirectToMirror($format) {
		return in_array($format, [
			'epub',
			'fb2.zip',
			'txt.zip',
			'sfb.zip',
		]);
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
		$similarTexts = [];
		if (empty($nextPart)) {
			$alikes = $text->getAlikes();
			$similarTexts = $alikes ? $this->em()->getTextRepository()->getByIds(array_slice($alikes, 0, 30)) : [];
		}
		$vars = [
			'text' => $text,
			'authors' => $text->getAuthors(),
			'part' => $part,
			'next_part' => $nextPart,
			'obj_count' => 3, /* after annotation and extra info */
			'js_extra' => ['text'],
			'similar_texts' => $similarTexts,
			'_template' => 'App:Text:show.html.twig',
		];
		if ($text->getArticle()) {
			$vars['wikiPage'] = $this->container->get('wiki_reader')->fetchPage($text->getArticle());
		}
		return $vars;
	}

	public function randomAction() {
		$id = $this->em()->getTextRepository()->getRandomId();

		return $this->urlRedirect($this->generateUrl('text_show', ['id' => $id]));
	}

	public function similarAction($id) {
		$text = $this->findText($id);
		$alikes = $text->getAlikes();
		return [
			'text' => $text,
			'similar_texts' => $alikes ? $this->em()->getTextRepository()->getByIds(array_slice($alikes, 0, 30)) : [],
		];
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
			return [
				'text' => $text,
				'form' => $form->createView(),
				'rating' => $rating,
				'_cache' => 0,
			];
		}
		return $this->redirectToText($text);
	}

	public function newLabelAction(Request $request, $id) {
		if (!$this->getUser()->canPutTextLabel()) {
			throw $this->createAccessDeniedException();
		}
		$text = $this->findText($id);
		$service = new TextLabelService($this->em()->getTextLabelLogRepository(), $this->getSavableUser());
		$textLabel = $service->newTextLabel($text);
		$group = $request->get('group');
		$form = $this->createForm(new TextLabelType($group), $textLabel);

		if ($form->handleRequest($request)->isValid()) {
			// TODO Form::handleRequest() overwrites the Text object with an id, so we give $text explicitly
			$service->addTextLabel($textLabel, $text);
			if ($request->isXmlHttpRequest()) {
				return [
					'_template' => 'App:Text:label_view.html.twig',
					'text' => $text,
					'label' => $textLabel->getLabel(),
				];
			}
			return $this->redirectToText($text);
		}

		return [
			'text' => $text,
			'text_label' => $textLabel,
			'group' => $group,
			'form' => $form->createView(),
			'_cache' => 0,
		];
	}

	public function deleteLabelAction(Request $request, $id, $labelId) {
		$this->responseAge = 0;

		if (!$this->getUser()->canPutTextLabel()) {
			throw $this->createAccessDeniedException();
		}
		$text = $this->findText($id);
		$label = $this->findLabel($labelId);
		$service = new TextLabelService($this->em()->getTextLabelLogRepository(), $this->getSavableUser());
		$service->removeTextLabel($text, $label);

		if ($request->isXmlHttpRequest()) {
			return $this->asText(1);
		}
		return $this->redirectToText($text);
	}

	public function labelLogAction($id) {
		$text = $this->findText($id);
		return [
			'text' => $text,
			'log' => $this->em()->getTextLabelLogRepository()->getForText($text),
		];
	}

	public function fullLabelLogAction(Request $request) {
		$page = $request->get('page', 1);
		$limit = 30;
		$repo = $this->em()->getTextLabelLogRepository();
		return [
			'log' => $repo->getAll($page, $limit),
			'pager' => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->count()
			]),
		];
	}

	/**
	 * Show all ratings for given text.
	 * @param int $id Text ID
	 */
	public function ratingsAction($id) {
		$text = $this->findText($id);
		return [
			'text' => $text,
			'ratings' => $this->em()->getTextRatingRepository()->getByText($text),
		];
	}

	public function markReadFormAction($id) {
		if ($this->getUser()->isAuthenticated()) {
			$tr = $this->em()->getUserTextReadRepository()->findOneBy(['text' => $id, 'user' => $this->getUser()->getId()]);
			if ($tr) {
				return new Response('Произведението е отбелязано като прочетено.');
			}
		}
		return [
			'id' => $id,
			'_cache' => 0,
		];
	}

	public function markReadAction(Request $request, $id) {
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw $this->createAccessDeniedException();
		}

		$text = $this->findText($id);
		$this->em()->getUserTextReadRepository()->save(new UserTextRead($this->getSavableUser(), $text));

		if ($request->isXmlHttpRequest()) {
			return $this->asJson('Произведението е отбелязано като прочетено.');
		}
		return $this->redirectToText($text);
	}

	public function addBookmarkAction(Request $request, $id) {
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw $this->createAccessDeniedException();
		}

		$text = $this->findText($id);
		$service = new TextBookmarkService($this->em()->getBookmarkRepository(), $this->em()->getBookmarkFolderRepository(), $this->getSavableUser());
		$bookmark = $service->addBookmark($text);

		if ($request->isXmlHttpRequest()) {
			$response = $bookmark
				? ['addClass' => 'active', 'setTitle' => 'Премахване от Избрани']
				: ['removeClass' => 'active', 'setTitle' => 'Добавяне в Избрани'];
			return $this->asJson($response);
		}
		return $this->redirectToText($text);
	}

	/**
	 * @param Text $text
	 */
	protected function redirectToText($text) {
		$id = $text instanceof Text ? $text->getId() : $text;
		return $this->urlRedirect($this->generateUrl('text_show', ['id' => $id]));
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
