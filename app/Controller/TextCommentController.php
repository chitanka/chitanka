<?php namespace App\Controller;

class TextCommentController extends Controller {

	public function indexAction($page, $_format) {
		if ($_format == 'rss') {
			$limit = 10;
			return [
				'comments' => $this->em()->getTextCommentRepository()->getLatest($limit),
			];
		}

		$_REQUEST['page'] = $page;

		return $this->legacyPage('Comment');
	}

	public function listForTextAction($id) {
		$this->responseAge = 0;
		$text = $this->em()->getTextRepository()->find($id);

		$_REQUEST['id'] = $id;

		return $this->legacyPage('Comment', [
			'text' => $text,
			'_controller' => 'TextComment:text_comments',
		]);
	}

}
