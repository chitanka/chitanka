<?php

namespace Chitanka\LibBundle\Controller;

class LiternewsController extends Controller
{
	public function indexAction($page)
	{
		$_REQUEST['page'] = $page;

		return $this->legacyPage('Liternews');
	}

	public function editAction($id)
	{
		$_REQUEST['id'] = $id;

		return $this->legacyPage('EditLiternews');
	}
}
