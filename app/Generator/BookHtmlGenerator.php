<?php namespace App\Generator;

use App\Entity\Book;
use App\Service\ContentService;
use Sfblib\SfbToHtmlConverter;

class BookHtmlGenerator {

	public function generateHtml(Book $book, $imgRoot = '') {
		if (!$book->isInSfbFormat()) {
			return null;
		}
		$imgDir = $imgRoot . ContentService::getContentFilePath('book-img', $book->getId());
		$converter = new SfbToHtmlConverter($book->getContentAsSfb(), $imgDir);
		return $converter->convert()->getContent();
	}

}
