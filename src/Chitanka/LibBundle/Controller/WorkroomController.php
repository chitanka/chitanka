<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkroomController extends Controller
{
	protected $repository = 'WorkEntry';
	protected $responseAge = 0;

	public function indexAction($status, $page)
	{
		$_REQUEST['status'] = $status;
		$_REQUEST['page'] = $page;

		$this->view = array(
			'js_extra' => array('jquery-tablesorter', 'jquery-tooltip'),
			'inline_js' => 'initTableSorter(); $(".tooltip").tooltip({showURL: false, showBody: "<br />"});',
		);

		return $this->legacyPage('Work');
	}


	public function listContributorsAction()
	{
		$_REQUEST['vl'] = 'contrib';

		return $this->legacyPage('Work');
	}

	public function showAction($id)
	{
		$_REQUEST['id'] = $id;

		return $this->legacyPage('Work');
	}


	public function newAction()
	{
		$_REQUEST['id'] = 0;
		$_REQUEST['status'] = 'edit';

		return $this->legacyPage('Work');
	}
	public function createAction()
	{
		return $this->legacyPage('Work');
	}
	public function editAction($id)
	{
		$_REQUEST['id'] = $id;
		$_REQUEST['status'] = 'edit';

		return $this->legacyPage('Work');
	}
	public function updateAction()
	{
		return $this->legacyPage('Work');
	}
	public function deleteAction()
	{
		return $this->legacyPage('Work');
	}


	public function deleteContribAction($id)
	{
		if ( ! $this->getUser()->inGroup('workroom-admin')) {
			throw new HttpException(401, 'Нямате достатъчни права за това действие.');
		}

		$contrib = $this->getRepository('WorkContrib')->find($id);
		if ( ! $contrib) {
			throw new NotFoundHttpException();
		}
		$entry = $contrib->getEntry();
		$contrib->delete();
		$em = $this->getEntityManager();
		$em->persist($contrib);
		$em->flush();

		if ($this->get('request')->isXmlHttpRequest()) {
			return $this->displayJson($contrib);
		}

		return $this->urlRedirect($this->generateUrl('workroom_entry_edit', array('id' => $entry->getId())));
	}


	public function rssAction()
	{
		$_REQUEST['type'] = 'work';

		return $this->legacyPage('Feed');
	}


	public function latestAction($limit = 10)
	{
		$this->view = array(
			'entries' => $this->getRepository('WorkEntry')->getLatest($limit),
		);

		return $this->display('entries_list');
	}
}
