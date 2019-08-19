<?php namespace App\Controller;

use App\Entity\Text;
use App\Entity\TextType;
use App\Entity\UserTextRead;
use App\Form\Type\RandomTextFilter;
use App\Form\Type\TextLabelType;
use App\Form\Type\TextRatingType;
use App\Generator\TextDownloadService;
use App\Legacy\Setup;
use App\Pagination\Pager;
use App\Service\SearchService;
use App\Service\TextBookmarkService;
use App\Service\TextLabelService;
use App\Util\Stringy;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TextController extends Controller {

	const PAGE_COUNT_DEFAULT = 50;
	const PAGE_COUNT_LIMIT = 500;

	public function indexAction($_format) {
		if (in_array($_format, ['html', 'json'])) {
			return [
				'labels' => $this->em()->getLabelRepository()->getAllAsTree(),
				'types' => $this->em()->getTextTypeRepository()->findAll(),
			];
		}

		return [];
	}

	public function listByTypeIndexAction() {
		return [
			'types' => $this->em()->getTextTypeRepository()->findAll()
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

	public function listByTypeAction(Request $request, TextType $type, $page) {
		$textRepo = $this->em()->getTextRepository();
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		return [
			'type' => $type,
			'texts'   => $textRepo->getByType($type, $page, $limit, $request->query->get('sort')),
			'pager'    => new Pager($page, $textRepo->countByType($type), $limit),
			'route_params' => ['type' => $type->getCode()],
		];
	}

	public function listByLabelAction(Request $request, $slug, $page) {
		$textRepo = $this->em()->getTextRepository();
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$slug = Stringy::slugify($slug);
		$labelRepo = $this->em()->getLabelRepository();
		$label = $labelRepo->findBySlug($slug);
		if ($label === null) {
			throw $this->createNotFoundException("Няма етикет с код $slug.");
		}
		$labels = $labelRepo->getLabelDescendantIdsWithSelf($label);
		return [
			'label' => $label,
			'parents' => array_reverse($labelRepo->findLabelAncestors($label)),
			'texts'   => $textRepo->getByLabel($labels, $page, $limit, $request->query->get('sort')),
			'pager'    => new Pager($page, $textRepo->countByLabel($labels), $limit),
			'route_params' => ['slug' => $slug],
		];
	}

	public function listByAlphaAction(Request $request, $letter, $page) {
		$textRepo = $this->em()->getTextRepository();
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$prefix = $letter == '-' ? null : $letter;
		return [
			'letter' => $letter,
			'texts' => $textRepo->getByPrefix($prefix, $page, $limit, $request->query->get('sort')),
			'pager'    => new Pager($page, $textRepo->countByPrefix($prefix), $limit),
			'route_params' => ['letter' => $letter],
		];
	}

	public function showAction(Request $request, $id, $_format) {
		if ($this->canRedirectToMirror($_format) && ($mirrorServer = $this->getMirrorServer())) {
			return $this->redirectToMirror($mirrorServer, $id, $_format, $request->get('filename'));
		}
		list($id) = explode('-', $id); // remove optional slug
		if ($_format === 'htmlx' || $request->isXmlHttpRequest()) {
			return $this->showPartAction($request, $id, 1, $_format);
		}
		switch ($_format) {
			case 'html':
				return $this->showHtml($this->findText($id, true));
			case 'epub':
			case 'fb2.zip':
			case 'txt.zip':
			case 'sfb.zip':
				Setup::doSetup($this->container);
				$service = new TextDownloadService($this->em()->getTextRepository());
				$ids = explode(',', $id);
				if (count($ids) === 1) {
					$this->findText($id);
				}
				return $this->urlRedirect($this->getWebRoot() . $service->generateFile($ids, $_format, $request->get('filename')));
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
			case 'json':
				return ['text' => $this->findText($id, true)];
		}
		throw $this->createNotFoundException("Неизвестен формат: $_format");
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$query = $request->query->get('q');
			$texts = $this->findByQuery([
				'text'  => $query,
				'by'    => 'title,subtitle,origTitle',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			$items = $descs = $urls = [];
			foreach ($texts as $text) {
				$authors = $text->getAuthorNamesString();
				$items[] = $text->getTitle() . ($authors ? " – $authors" : '');
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('text_show', ['id' => $text->getId()]);
			}

			return [$query, $items, $descs, $urls];
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

	public function showPartAction(Request $request, $id, $part, $_format) {
		$text = $this->findText($id, true);
		if ($_format === 'htmlx' || $request->isXmlHttpRequest()) {
			$nextHeader = $text->getNextHeaderByNr($part);
			return [
				'text' => $text,
				'part' => $part,
				'next_part' => ($nextHeader ? $nextHeader->getNr() : 0),
				'_template' => 'App:Text:show.htmlx.twig',
			];
		}
		return $this->showHtml($text, $part);
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

	public function randomAction(Request $request) {
		$form = $this->createForm(RandomTextFilter::class);
		if ($form->handleRequest($request)->isValid()) {
			$criteria = new Criteria();
			if ($selectedTypes = $form->getData()['type']) {
				$criteria = $criteria->where(Criteria::expr()->in('type', $selectedTypes));
			}
			$id = $this->em()->getTextRepository()->getRandomId($criteria);
			return $this->redirectToRoute('text_show', ['id' => $id]);
		}
		return ['form' => $form->createView()];
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
		$form = $this->createForm(TextRatingType::class, $rating);

		// TODO replace with DoctrineListener
		$oldRating = $rating->getRating();

		$form->handleRequest($request);
		if ($user->isAuthenticated() && $form->isValid()) {
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
		$form = $this->createForm(TextLabelType::class, $textLabel, ['group' => $group]);

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
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$repo = $this->em()->getTextLabelLogRepository();
		return [
			'log' => $repo->getAll($page, $limit),
			'pager' => new Pager($page, $repo->count(), $limit),
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
	 * @return Response
	 */
	protected function redirectToText($text) {
		$id = $text instanceof Text ? $text->getId() : $text;
		return $this->urlRedirect($this->generateUrl('text_show', ['id' => $id]));
	}

	/**
	 * @param int $textId
	 * @param bool $fetchRelations
	 * @return Text
	 */
	protected function findText($textId, $fetchRelations = false) {
		$text = $this->em()->getTextRepository()->get($textId, $fetchRelations);
		if ($text === null) {
			throw $this->createNotFoundException("Няма текст с номер $textId.");
		}
		return $text;
	}

	/**
	 * @param int $labelId
	 * @return \App\Entity\Label
	 */
	protected function findLabel($labelId) {
		$label = $this->em()->getLabelRepository()->find($labelId);
		if ($label === null) {
			throw $this->createNotFoundException("Няма етикет с номер $labelId.");
		}
		return $label;
	}

	/**
	 * @param array $query
	 * @return Text[]
	 */
	protected function findByQuery(array $query) {
		return $this->em()->getTextRepository()->findByQuery($query);
	}
}
