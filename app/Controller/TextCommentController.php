<?php namespace App\Controller;

class TextCommentController extends Controller {

	public function indexAction($page, $_format) {
		if ($_format == 'rss') {
			$limit = 10;
			$this->view = array(
				'comments' => $this->em()->getTextCommentRepository()->getLatest($limit),
			);

			return $this->display("index.$_format");
		}

		$_REQUEST['page'] = $page;

		return $this->legacyPage('Comment');
	}

	public function listForTextAction($id) {
		$this->responseAge = 0;
		$text = $this->em()->getTextRepository()->find($id);

		$_REQUEST['id'] = $id;

// 		$form = TextCommentForm::create($this->get('form.context'), 'comment', array('em' => $this->em()));
//
// 		$form->bind($this->get('request'));
//
// 		if ($form->isValid()) {
// 			$form->process();
// 		}
//
		$this->view = array(
			'text' => $text,
// 			'comments' => $this->em()->getTextCommentRepository()->getByText($text),
// 			'form' => $form,
		);

// RSS
// 	$_REQUEST['obj'] = 'comment';
// 	$_REQUEST['limit'] = 10;
// 	$_REQUEST[self::FF_TEXT_ID] = $this->textId;

		return $this->legacyPage('Comment', 'TextComment:text_comments');

// 		return $this->display('text_comments');
	}

}
