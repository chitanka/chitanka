<?php namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkEntry;
use App\Persistence\NextIdRepository;
use App\Persistence\TextRepository;
use App\Persistence\UserRepository;
use App\Persistence\WorkContribRepository;
use App\Persistence\WorkEntryRepository;
use Eko\FeedBundle\Field\Item\ItemField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkroomController extends Controller {

	/** How many entries are allowed in a feed */
	private static $feedListLimit = 200;

	protected $responseAge = 0;

	/** @var WorkEntryRepository */protected $workEntryRepository;
	/** @var WorkContribRepository */protected $workContribRepository;
	/** @var TextRepository */protected $textRepository;
	/** @var NextIdRepository */protected $nextIdRepository;

	public function __construct(
		UserRepository $userRepository,
		WorkEntryRepository $workEntryRepository,
		WorkContribRepository $workContribRepository,
		TextRepository $textRepository,
		NextIdRepository $nextIdRepository
	) {
		parent::__construct($userRepository);
		$this->workEntryRepository = $workEntryRepository;
		$this->workContribRepository = $workContribRepository;
		$this->textRepository = $textRepository;
		$this->nextIdRepository = $nextIdRepository;
	}

	public function indexAction($status, $page) {
		$_REQUEST['status'] = $status;
		$_REQUEST['page'] = $page;

		return $this->legacyWorkPage([
			'_controller' => 'Workroom/index',
		]);
	}

	public function listAction() {
		$_REQUEST['vl'] = 'listonly';

		return $this->legacyWorkPage();
	}

	public function listContributorsAction() {
		$_REQUEST['vl'] = 'contrib';

		return $this->legacyWorkPage();
	}

	public function newAction() {
		if ($this->getUser()->isAnonymous()) {
			throw $this->createAccessDeniedException('Нямате достатъчни права за това действие.');
		}
		$_REQUEST['id'] = 0;
		$_REQUEST['status'] = 'edit';

		return $this->legacyWorkPage();
	}
	public function createAction() {
		return $this->legacyWorkPage();
	}
	public function editAction(WorkEntry $entry) {
		$_REQUEST['id'] = $entry->getId();
		$_REQUEST['status'] = 'edit';
		return $this->legacyWorkPage([
			'entry' => $entry,
			'_controller' => 'Workroom/show',
		]);
	}
	public function updateAction() {
		return $this->legacyWorkPage();
	}
	public function deleteAction() {
		return $this->legacyWorkPage();
	}
	public function patchAction(WorkEntryRepository $workEntryRepository, Request $request, WorkEntry $entry) {
		$bibliomanId = $request->get('bibliomanId');
		if ($bibliomanId > 0 && $this->getUser()->inGroup([User::GROUP_WORKROOM_MEMBER, User::GROUP_WORKROOM_SUPERVISOR, User::GROUP_WORKROOM_ADMIN])) {
			$entry->setBibliomanId($bibliomanId);
			$workEntryRepository->save($entry);
		}
		return $this->asJson($entry);
	}

	public function deleteContribAction(WorkContribRepository $workContribRepository, Request $request, $id) {
		if (!$this->getUser()->inGroup('workroom-admin')) {
			throw $this->createAccessDeniedException('Нямате достатъчни права за това действие.');
		}

		$contrib = $workContribRepository->find($id);
		if ($contrib === null) {
			throw $this->createNotFoundException();
		}
		$entry = $contrib->getEntry();
		$contrib->delete();
		$workContribRepository->save($contrib);

		if ($request->isXmlHttpRequest()) {
			return $this->asJson($contrib);
		}

		return $this->urlRedirect($this->generateUrl('workroom_entry_edit', ['id' => $entry->getId()]));
	}

	/**
	 * @param int $limit
	 * Cache(maxage="60", public=true) - disabled
	 */
	public function rssAction(WorkEntryRepository $workEntryRepository, $limit) {
		$entries = $workEntryRepository->findLatest(min($limit, self::$feedListLimit));

		$feed = $this->get('eko_feed.feed.manager')->get('workroom');
		//$feed->addItemField(new ItemField('dc:creator', 'getFeedItemCreator'));
		$feed->addItemField(new ItemField('guid', 'getFeedItemGuid'));
		$feed->addFromArray($entries);

		return new Response($feed->render('rss'));
	}

	private function legacyWorkPage(array $params = []) {
		return $this->legacyPage('Work', $params, [
			'userRepository' => $this->userRepository,
			'workEntryRepository' => $this->workEntryRepository,
			'workContribRepository' => $this->workContribRepository,
			'textRepository' => $this->textRepository,
			'nextIdRepository' => $this->nextIdRepository
		]);
	}
}
