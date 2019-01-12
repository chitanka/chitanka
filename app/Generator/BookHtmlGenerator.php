<?php namespace App\Generator;

use App\Entity\Book;
use Sfblib\SfbToHtmlConverter;

class BookHtmlGenerator {

	public function generateHtml(Book $book) {
		if (!$book->isInSfbFormat()) {
			return null;
		}
		$converter = new SfbToHtmlConverter($book->getContentAsSfb(), $book->initTmpImagesDir());
		return $converter->convert()->getContent();
	}

}
