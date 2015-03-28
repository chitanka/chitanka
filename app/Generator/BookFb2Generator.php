<?php namespace App\Generator;

use App\Entity\Book;
use Sfblib\SfbToFb2Converter;

class BookFb2Generator {

	private $fb2CoverWidth = 400;

	public function generateFb2(Book $book) {
		if (!$book->isInSfbFormat()) {
			return null;
		}
		$imgdir = $book->initTmpImagesDir();

		$conv = new SfbToFb2Converter($book->getContentAsSfb(), $imgdir);

		$conv->setObjectCount(1);
		$conv->setSubtitle($book->getSubtitle());
		$conv->setGenre($this->getGenresForFb2($book));
		$conv->setKeywords($this->getKeywords($book));
		$conv->setTextDate($book->getYear());

		if ( ($cover = $book->getCover($this->fb2CoverWidth)) ) {
			$conv->addCoverpage($cover);
		}

		$conv->setLang($book->getLang());
		$conv->setSrcLang($book->getOrigLang() ?: '?');

		foreach ($book->getTranslators() as $translator) {
			$conv->addTranslator($translator->getName());
		}

		$conv->setDocId($book->getDocId());
		$conv->setDocAuthor('Моята библиотека');

		$conv->enablePrettyOutput();

		$content = $conv->convert()->getContent();

		return $content;
	}

	private function getGenresForFb2(Book $book) {
		$genres = [];
		$textGenerator = new TextFb2Generator();
		foreach ($book->getTexts() as $text) {
			$genres = array_merge($genres, $textGenerator->getGenres($text));
		}
		$genres = array_unique($genres);
		return $genres;
	}

	private function getKeywords(Book $book) {
		return implode(', ', $book->getLabels());
	}
}
