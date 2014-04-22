<?php

namespace App\Controller;

class ForeignBookController extends Controller
{

	public function indexAction($_format)
	{
		$this->view = array(
			'books' => $this->getForeignBookRepository()->getLatest(100)
		);

		return $this->display("index.$_format");
	}


	public function bookAction()
	{
		$this->view = array(
			'book' => $this->getForeignBookRepository()->getRandom()
		);

		return $this->display('book');
	}

}
