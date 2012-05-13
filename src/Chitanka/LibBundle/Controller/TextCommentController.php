<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Form\TextCommentForm;

class TextCommentController extends Controller
{
	public function indexAction($page, $_format)
	{
		$this->responseAge = 0;
		$this->responseFormat = $_format;
		if ($_format == 'rss') {
			$limit = 10;
			$this->view = array(
				'comments' => $this->getTextCommentRepository()->getLatest($limit),
			);

			return $this->display('index');
		}

		$_REQUEST['page'] = $page;

		return $this->legacyPage('Comment');
	}


	public function listForTextAction($id)
	{
		$this->responseAge = 0;
		$text = $this->getTextRepository()->find($id);

		$_REQUEST['id'] = $id;

// 		$form = TextCommentForm::create($this->get('form.context'), 'comment', array('em' => $this->getEntityManager()));
//
// 		$form->bind($this->get('request'));
//
// 		if ($form->isValid()) {
// 			$form->process();
// 		}
//
		$this->view = array(
			'text' => $text,
// 			'comments' => $this->getTextCommentRepository()->getByText($text),
// 			'form' => $form,
		);


// RSS
// 	$_REQUEST['obj'] = 'comment';
// 	$_REQUEST['limit'] = 10;
// 	$_REQUEST[self::FF_TEXT_ID] = $this->textId;


		return $this->legacyPage('Comment', 'TextComment:text_comments');

// 		return $this->display('text_comments');
	}


	public function latestAction($limit = 5)
	{
		$this->view = array(
			'comments' => $this->getTextCommentRepository()->getLatest($limit),
		);

		return $this->display('latest_comments');
	}

}
