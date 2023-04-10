<?php namespace App\Controller;

use App\Entity\Language;
use App\Entity\Text;
use App\Entity\TextType;
use App\Entity\UserTextRead;
use App\Form\Type\RandomTextFilter;
use App\Form\Type\TextLabelType;
use App\Form\Type\TextRatingType;
use App\Generator\DownloadUrlGenerator;
use App\Generator\TextDownloadService;
use App\Legacy\Setup;
use App\Pagination\Pager;
use App\Persistence\BookmarkFolderRepository;
use App\Persistence\BookmarkRepository;
use App\Persistence\LabelRepository;
use App\Persistence\LanguageRepository;
use App\Persistence\TextCombinationRepository;
use App\Persistence\TextLabelLogRepository;
use App\Persistence\TextRatingRepository;
use App\Persistence\TextRepository;
use App\Persistence\TextTypeRepository;
use App\Persistence\UserRepository;
use App\Persistence\UserTextReadRepository;
use App\Service\SearchService;
use App\Service\TextBookmarkService;
use App\Service\TextLabelService;
use App\Service\WikiReader;
use App\Util\Stringy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TextController extends Controller {

	const PAGE_COUNT_DEFAULT = 50;
	const PAGE_COUNT_LIMIT = 500;

	private $textRepository;

	public function __construct(UserRepository $userRepository, TextRepository $textRepository) {
		parent::__construct($userRepository);
		$this->textRepository = $textRepository;
	}

	public function indexAction(LabelRepository $labelRepository, TextTypeRepository $textTypeRepository, LanguageRepository $languageRepository, $_format) {
		if (in_array($_format, ['html', 'json'])) {
			return [
				'labels' => $labelRepository->getAllAsTree(),
				'types' => $textTypeRepository->findAll(),
				'languages' => $languageRepository->findAll(),
			];
		}

		return [];
	}

	public function listByTypeIndexAction(TextTypeRepository $textTypeRepository) {
		return [
			'types' => $textTypeRepository->findAll()
		];
	}

	public function listByLabelIndexAction(LabelRepository $labelRepository) {
		return [
			'labels' => $labelRepository->findAll()
		];
	}

	public function listByLanguageIndexAction(LanguageRepository $languageRepository) {
		return [
			'languages' => $languageRepository->findAll(),
		];
	}

	public function listByOriginalLanguageIndexAction(LanguageRepository $languageRepository) {
		return [
			'languages' => $languageRepository->findAll(),
		];
	}

	public function listByAlphaIndexAction() {
		return [];
	}

	public function listByTypeAction(TextRepository $textRepo, Request $request, TextType $type, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$sorting = $textRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'text'));
		return [
			'type' => $type,
			'texts'   => $textRepo->getByType($type, $page, $limit, $sorting),
			'pager'    => new Pager($page, $textRepo->countByType($type), $limit),
			'route_params' => ['type' => $type->getCode()],
			'sorting' => $sorting,
		];
	}

	public function listByLabelAction(TextRepository $textRepo, LabelRepository $labelRepo, Request $request, $slug, $page = 1) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$slug = Stringy::slugify($slug);
		$label = $labelRepo->findBySlug($slug);
		if ($label === null) {
			throw $this->createNotFoundException("Няма етикет с код $slug.");
		}
		$labels = $labelRepo->getLabelDescendantIdsWithSelf($label);
		$sorting = $textRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'text'));
		return [
			'label' => $label,
			'parents' => array_reverse($labelRepo->findLabelAncestors($label)),
			'texts'   => $textRepo->getByLabel($labels, $page, $limit, $sorting),
			'pager'    => new Pager($page, $textRepo->countByLabel($labels), $limit),
			'route_params' => ['slug' => $slug],
			'sorting' => $sorting,
		];
	}

	public function listByLanguageAction(TextRepository $textRepo, Request $request, Language $language, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$sorting = $textRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'text'));
		return [
			'language' => $language,
			'texts'   => $textRepo->getByLanguage($language, $page, $limit, $sorting),
			'pager'    => new Pager($page, $textRepo->countByLanguage($language), $limit),
			'route_params' => ['language' => $language->getCode()],
			'sorting' => $sorting,
		];
	}

	public function listByOriginalLanguageAction(TextRepository $textRepo, Request $request, Language $language, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$sorting = $textRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'text'));
		return [
			'language' => $language,
			'texts'   => $textRepo->getByOriginalLanguage($language, $page, $limit, $sorting),
			'pager'    => new Pager($page, $textRepo->countByOriginalLanguage($language), $limit),
			'route_params' => ['language' => $language->getCode()],
			'sorting' => $sorting,
		];
	}

	public function listByAlphaAction(TextRepository $textRepo, Request $request, $letter, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$prefix = $letter == '-' ? null : $letter;
		$sorting = $textRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'text'));
		return [
			'letter' => $letter,
			'texts' => $textRepo->getByPrefix($prefix, $page, $limit, $sorting),
			'pager'    => new Pager($page, $textRepo->countByPrefix($prefix), $limit),
			'route_params' => ['letter' => $letter],
			'sorting' => $sorting,
		];
	}

	public function showAction(TextCombinationRepository $textCombinationRepository, WikiReader $wikiReader, Request $request, $id, ParameterBagInterface $parameterBag, $_format = 'html') {
		$parameters = $parameterBag->all();
		if ($this->canRedirectToMirror($_format) && ($mirrorServer = $this->getMirrorServer($parameters['mirror_sites']))) {
			return $this->redirectToMirror($mirrorServer, $id, $_format, $request->get('filename'), $request->getScheme());
		}
		[$id] = explode('-', $id); // remove optional slug
		if ($_format === 'htmlx' || $request->isXmlHttpRequest()) {
			return $this->showPartAction($textCombinationRepository, $wikiReader, $request, $id, 1, $_format);
		}
		switch ($_format) {
			case 'html':
				if ($this->isMultiId($id)) {
					return $this->showMultiHtml($textCombinationRepository, $this->explodeMultiId($id));
				}
				return $this->showHtml($wikiReader, $this->findText($id, true));
			case 'epub':
			case 'fb2.zip':
			case 'txt.zip':
			case 'sfb.zip':
				Setup::doSetup($this->container);
				$service = new TextDownloadService($this->textRepository);
				$ids = $this->explodeMultiId($id);
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

		$converterFormatKey = "{$_format}_download_enabled";
		if (isset($parameters[$converterFormatKey])) {
			if ( ! $parameters[$converterFormatKey]) {
				throw $this->createNotFoundException("Поддръжката на формата {$_format} не е включена.");
			}
			return $this->urlRedirect($this->generateConverterUrl($id, $_format, $parameters['mirror_sites_for_converter']));
		}

		throw $this->createNotFoundException("Неизвестен формат: $_format");
	}

	public function searchAction(SearchService $searchService, Request $request, $_format) {
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
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,origTitle';
		}
		$texts = $this->textRepository->getByQuery($query);
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

	private function redirectToMirror($mirrorServer, $id, $format, $requestedFilename, $requestedScheme) {
		if (substr($mirrorServer, 0, 2) === '//') {
			$mirrorServer = $requestedScheme .':'. $mirrorServer;
		}
		return $this->urlRedirect("$mirrorServer/text/$id.$format?filename=$requestedFilename");
	}

	protected function generateConverterUrl(string $id, string $targetFormat, array $mirrors): string {
		$epubUrl = $this->generateAbsoluteUrl('text_show', ['id' => $id, '_format' => Text::FORMAT_EPUB]);
		return (new DownloadUrlGenerator())->generateConverterUrl($epubUrl, $targetFormat, $mirrors);
	}

	public function showPartAction(TextCombinationRepository $textCombinationRepository, WikiReader $wikiReader, Request $request, $id, $part, $_format) {
		if ($this->isMultiId($id)) {
			return $this->showMultiHtml($textCombinationRepository, $this->explodeMultiId($id), $part);
		}
		$text = $this->findText($id, true);
		if ($_format === 'htmlx' || $request->isXmlHttpRequest()) {
			$nextHeader = $text->getNextHeaderByNr($part);
			return [
				'text' => $text,
				'part' => $part,
				'next_part' => ($nextHeader ? $nextHeader->getNr() : 0),
				'_template' => 'Text/show.htmlx.twig',
			];
		}
		return $this->showHtml($wikiReader, $text, $part);
	}

	public function showHtml(WikiReader $wikiReader, Text $text, $part = 1) {
		$nextHeader = $text->getNextHeaderByNr($part);
		$nextPart = $nextHeader ? $nextHeader->getNr() : 0;
		$similarTexts = [];
		if (empty($nextPart)) {
			$alikes = $text->getAlikes();
			$similarTexts = $alikes ? $this->textRepository->getByIds(array_slice($alikes, 0, 30)) : [];
		}
		$vars = [
			'text' => $text,
			'juxtaposedTexts' => $text->getJuxtaposedTexts(),
			'authors' => $text->getAuthors(),
			'part' => $part,
			'obj_count' => 3, /* after annotation and extra info */
			'js_extra' => ['text'],
			'similar_texts' => $similarTexts,
			'_template' => 'Text/show.html.twig',
		];
		if ($text->getArticle()) {
			$vars['wikiPage'] = $wikiReader->fetchPage($text->getArticle());
		}
		return $vars;
	}

	protected function showMultiHtml(TextCombinationRepository $textCombinationRepository, array $ids, int $part = 1) {
		$maxNrOfTexts = 4;
		$texts = $this->textRepository->getMulti(array_slice($ids, 0, $maxNrOfTexts));/* @var $texts Text[] */
		$gridColsMap = [
			1 => 12,
			2 => 6,
			3 => 4,
			4 => 3,
		];
		$textCombinations = $textCombinationRepository->getForTexts($texts);
		$joinedTextCombinations = $textCombinations ? array_replace(...array_map(function(\App\Entity\TextCombination $tc) {
			return $tc->toArray();
		}, $textCombinations)) : [];
		return [
			'texts' => $texts,
			'part' => $part,
			'textCombinations' => $joinedTextCombinations ?: null,
			'obj_count' => 3, /* after annotation and extra info */
			'grid_cols' => $gridColsMap[count($texts)],
			'_template' => 'Text/show_multi.html.twig',
		];
	}

	public function randomAction(Request $request) {
		$form = $this->createForm(RandomTextFilter::class);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$criteria = new Criteria();
			if ($selectedTypes = $form->getData()['type']) {
				/* @var $selectedTypes ArrayCollection */
				$codes = $selectedTypes->map(function (TextType $textType) {
					return $textType->getCode();
				})->getValues();
				if ($codes) {
					$criteria = $criteria->where(Criteria::expr()->in('type', $codes));
				}
			}
			$id = $this->textRepository->getRandomId($criteria);
			return $this->redirectToRoute('text_show', ['id' => $id]);
		}
		return ['form' => $form->createView()];
	}

	public function similarAction($id) {
		$text = $this->findText($id);
		$alikes = $text->getAlikes();
		return [
			'text' => $text,
			'similar_texts' => $alikes ? $this->textRepository->getByIds(array_slice($alikes, 0, 30)) : [],
		];
	}

	public function ratingAction(TextRatingRepository $textRatingRepository, Request $request, $id) {
		$text = $this->findText($id);

		$user = $this->userRepository->__em__()->merge($this->getUser());
		$rating = $textRatingRepository->getByTextAndUser($text, $user);
		$form = $this->createForm(TextRatingType::class, $rating);

		// TODO replace with DoctrineListener
		$oldRating = $rating->getRating();

		$form->handleRequest($request);
		if ($user->isAuthenticated() && $form->isSubmitted() && $form->isValid()) {
			// TODO replace with DoctrineListener
			$text->updateAvgRating($rating->getRating(), $oldRating);
			$this->textRepository->save($text);

			// TODO bind overwrites the Text object with an id
			$rating->setText($text);

			$rating->setCurrentDate();
			$textRatingRepository->save($rating);
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

	public function newLabelAction(TextLabelLogRepository $textLabelLogRepository, Request $request, $id) {
		if (!$this->getUser()->canPutTextLabel()) {
			throw $this->createAccessDeniedException();
		}
		$text = $this->findText($id);
		$service = new TextLabelService($textLabelLogRepository, $this->getSavableUser());
		$textLabel = $service->newTextLabel($text);
		$group = $request->get('group');
		$form = $this->createForm(TextLabelType::class, $textLabel, ['group' => $group]);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			// TODO Form::handleRequest() overwrites the Text object with an id, so we give $text explicitly
			$service->addTextLabel($textLabel, $text);
			if ($request->isXmlHttpRequest()) {
				return [
					'_template' => 'Text/label_view.html.twig',
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

	public function deleteLabelAction(LabelRepository $labelRepository, TextLabelLogRepository $textLabelLogRepository, Request $request, $id, $labelId) {
		$this->responseAge = 0;

		if (!$this->getUser()->canPutTextLabel()) {
			throw $this->createAccessDeniedException();
		}
		$text = $this->findText($id);
		$label = $this->findLabel($labelRepository, $labelId);
		$service = new TextLabelService($textLabelLogRepository, $this->getSavableUser());
		$service->removeTextLabel($text, $label);

		if ($request->isXmlHttpRequest()) {
			return $this->asText(1);
		}
		return $this->redirectToText($text);
	}

	public function labelLogAction(TextLabelLogRepository $textLabelLogRepository, $id) {
		$text = $this->findText($id);
		return [
			'text' => $text,
			'log' => $textLabelLogRepository->getForText($text),
		];
	}

	public function fullLabelLogAction(TextLabelLogRepository $repo, Request $request) {
		$page = $request->get('page', 1);
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		return [
			'log' => $repo->getAll($page, $limit),
			'pager' => new Pager($page, $repo->count(), $limit),
		];
	}

	/**
	 * Show all ratings for given text.
	 * @param int $id Text ID
	 */
	public function ratingsAction(TextRatingRepository $textRatingRepository, $id) {
		$text = $this->findText($id);
		return [
			'text' => $text,
			'ratings' => $textRatingRepository->getByText($text),
		];
	}

	public function markReadFormAction(UserTextReadRepository $userTextReadRepository, $id) {
		if ($this->getUser()->isAuthenticated()) {
			$tr = $userTextReadRepository->findOneBy(['text' => $id, 'user' => $this->getUser()->getId()]);
			if ($tr) {
				return new Response('Произведението е отбелязано като прочетено.');
			}
		}
		return [
			'id' => $id,
			'_cache' => 0,
		];
	}

	public function markReadAction(UserTextReadRepository $userTextReadRepository, Request $request, $id) {
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw $this->createAccessDeniedException();
		}

		$text = $this->findText($id);
		$userTextReadRepository->save(new UserTextRead($this->getSavableUser(), $text));

		if ($request->isXmlHttpRequest()) {
			return $this->asJson('Произведението е отбелязано като прочетено.');
		}
		return $this->redirectToText($text);
	}

	public function addBookmarkAction(BookmarkRepository $bookmarkRepository, BookmarkFolderRepository $bookmarkFolderRepository, Request $request, $id) {
		$this->responseAge = 0;

		if ( ! $this->getUser()->isAuthenticated()) {
			throw $this->createAccessDeniedException();
		}

		$text = $this->findText($id);
		$service = new TextBookmarkService($bookmarkRepository, $bookmarkFolderRepository, $this->getSavableUser());
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
		$text = $this->textRepository->get($textId, $fetchRelations);
		if ($text === null) {
			throw $this->createNotFoundException("Няма текст с номер $textId.");
		}
		return $text;
	}

	/**
	 * @param int $labelId
	 * @return \App\Entity\Label
	 */
	protected function findLabel(LabelRepository $labelRepository, $labelId) {
		$label = $labelRepository->find($labelId);
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
		return $this->textRepository->findByQuery($query);
	}

	protected function isMultiId(string $id): bool {
		return strpos($id, ',') !== false;
	}

	protected function explodeMultiId(string $id): array {
		return array_map('trim', explode(',', $id));
	}
}
