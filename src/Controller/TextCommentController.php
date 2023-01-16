<?php namespace App\Controller;

use App\Persistence\TextCommentRepository;
use App\Persistence\TextRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class TextCommentController extends Controller {

	public function indexAction(TextRepository $textRepository, TextCommentRepository $textCommentRepository, AdminUrlGenerator $adminUrlGenerator, $page, $_format) {
		if ($_format == 'rss') {
			$limit = 10;
			return [
				'comments' => $textCommentRepository->getLatest($limit),
			];
		}

		$_REQUEST['page'] = $page;

		return $this->legacyPage('Comment', [], [
			'textRepository' => $textRepository,
			'textCommentRepository' => $textCommentRepository,
			'adminUrlGenerator' => $adminUrlGenerator,
		]);
	}

	public function listForTextAction(TextRepository $textRepository, TextCommentRepository $textCommentRepository, AdminUrlGenerator $adminUrlGenerator, $id) {
		$this->responseAge = 0;
		$text = $textRepository->find($id);

		$_REQUEST['id'] = $id;

		return $this->legacyPage('Comment', [
			'text' => $text,
			'_controller' => 'TextComment/text_comments',
		], [
			'textRepository' => $textRepository,
			'textCommentRepository' => $textCommentRepository,
			'adminUrlGenerator' => $adminUrlGenerator,
		]);
	}

}
