<?php namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkEntry;
use Eko\FeedBundle\Field\Item\ItemField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkroomController extends Controller {

	/** How many entries are allowed in a feed */
	private static $feedListLimit = 200;

	protected $responseAge = 0;

	public function indexAction($status, $page) {
		$_REQUEST['status'] = $status;
		$_REQUEST['page'] = $page;

		return $this->legacyPage('Work', [
			'_controller' => 'Workroom:index',
		]);
	}

	public function listAction() {
		$_REQUEST['vl'] = 'listonly';

		return $this->legacyPage('Work');
	}

	public function listContributorsAction() {
		$_REQUEST['vl'] = 'contrib';

		return $this->legacyPage('Work');
	}

	public function newAction() {
		if ($this->getUser()->isAnonymous()) {
			throw $this->createAccessDeniedException('Нямате достатъчни права за това действие.');
		}
		$_REQUEST['id'] = 0;
		$_REQUEST['status'] = 'edit';

		return $this->legacyPage('Work');
	}
	public function createAction() {
		return $this->legacyPage('Work');
	}
	public function editAction(WorkEntry $entry) {
		$_REQUEST['id'] = $entry->getId();
		$_REQUEST['status'] = 'edit';
		return $this->legacyPage('Work', [
			'entry' => $entry,
			'_controller' => 'Workroom:show',
		]);
	}
	public function updateAction() {
		return $this->legacyPage('Work');
	}
	public function deleteAction() {
		return $this->legacyPage('Work');
	}
	public function patchAction(Request $request, WorkEntry $entry) {
		$bibliomanId = $request->get('bibliomanId');
		if ($bibliomanId > 0 && $this->getUser()->inGroup([User::GROUP_WORKROOM_MEMBER, User::GROUP_WORKROOM_SUPERVISOR, User::GROUP_WORKROOM_ADMIN])) {
			$entry->setBibliomanId($bibliomanId);
			$this->em()->getWorkEntryRepository()->save($entry);
		}
		return $this->asJson($entry);
	}

	public function deleteContribAction(Request $request, $id) {
		if (!$this->getUser()->inGroup('workroom-admin')) {
			throw $this->createAccessDeniedException('Нямате достатъчни права за това действие.');
		}

		$contrib = $this->em()->getWorkContribRepository()->find($id);
		if ($contrib === null) {
			throw $this->createNotFoundException();
		}
		$entry = $contrib->getEntry();
		$contrib->delete();
		$this->em()->getWorkContribRepository()->save($contrib);

		if ($request->isXmlHttpRequest()) {
			return $this->asJson($contrib);
		}

		return $this->urlRedirect($this->generateUrl('workroom_entry_edit', ['id' => $entry->getId()]));
	}

	/**
	 * @param int $limit
	 * Cache(maxage="60", public=true) - disabled
	 */
	public function rssAction($limit) {
		$entries = $this->em()->getWorkEntryRepository()->findLatest(min($limit, self::$feedListLimit));

		$feed = $this->get('eko_feed.feed.manager')->get('workroom');
		//$feed->addItemField(new ItemField('dc:creator', 'getFeedItemCreator'));
		$feed->addItemField(new ItemField('guid', 'getFeedItemGuid'));
		$feed->addFromArray($entries);

		return new Response($feed->render('rss'));
	}

}
