<?php namespace App\Entity;

class WorkSteward {
	public static function joinPersonKeysForWorks($works) {
		foreach ($works as $k => $work) {
			if (isset($work['book']) && isset($work['book']['bookAuthors'])) {
				$authors = [];
				foreach ($work['book']['bookAuthors'] as $bookAuthor) {
					if ($bookAuthor['pos'] >= 0) {
						$authors[] = $bookAuthor['person'];
					}
				}
				$works[$k]['book']['authors'] = $authors;
			}
			if (isset($work['text']) && isset($work['text']['textAuthors'])) {
				$authors = [];
				foreach ($work['text']['textAuthors'] as $textAuthor) {
					if ($textAuthor['pos'] >= 0) {
						$authors[] = $textAuthor['person'];
					}
				}
				$works[$k]['text']['authors'] = $authors;
			}
		}
		return $works;
	}

	public static function joinPersonKeysForBooks($books) {
		foreach ($books as $k => $book) {
			if (isset($book['bookAuthors'])) {
				$authors = [];
				foreach ($book['bookAuthors'] as $bookAuthor) {
					if ($bookAuthor['pos'] >= 0) {
						$authors[] = $bookAuthor['person'];
					}
				}
				$books[$k]['authors'] = $authors;
			}
		}
		return $books;
	}

	public static function joinPersonKeysForTexts($texts) {
		foreach ($texts as $k => $text) {
			if (isset($text['textAuthors'])) {
				$authors = [];
				foreach ($text['textAuthors'] as $textAuthor) {
					if ($textAuthor['pos'] >= 0) {
						$authors[] = $textAuthor['person'];
					}
				}
				$texts[$k]['authors'] = $authors;
			}
			if (isset($text['textTranslators'])) {
				$translators = [];
				foreach ($text['textTranslators'] as $textTranslator) {
					if ($textTranslator['pos'] >= 0) {
						$translators[] = $textTranslator['person'];
					}
				}
				$texts[$k]['translators'] = $translators;
			}
		}
		return $texts;
	}

}
