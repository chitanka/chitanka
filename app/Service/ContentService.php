<?php namespace App\Service;

use App\Entity\Book;
use App\Util\Ary;
use Buzz\Browser;

class ContentService {

	public static $bibliomanCoverUrlTemplate = 'https://biblioman.chitanka.info/books/ID.cover?size=600';
	public static $bibliomanBookUrlTemplate = 'https://biblioman.chitanka.info/books/ID';
	public static $bibliomanBookJsonUrlTemplate = 'https://biblioman.chitanka.info/books/ID.json';
	public static $clearBookCoverCacheUrl = 'https://assets.chitanka.info/cc_thumb.php';
	public static $bookCoverExtension = 'jpg';
	public static $internalContentPath = __DIR__ .'/../../web/content';
	public static $webContentPath = 'content/';
	public static $webThumbPath = 'thumb/?';

	/**
	 * @param string $key
	 * @param int $num
	 * @return string
	 */
	public static function getContentFile($key, $num) {
		$file = self::getInternalContentFilePath($key, $num);
		if (file_exists($file)) {
			return file_get_contents($file);
		}
		return null;
	}

	public static function getContentFilePath(string $key, int $num, bool $full = true): string {
		return self::getPrefixedContentFilePath(self::$webContentPath, $key, $num, $full);
	}

	public static function getInternalContentFilePath(string $key, int $num, bool $full = true): string {
		return self::getPrefixedContentFilePath(self::$internalContentPath, $key, $num, $full);
	}

	public static function setInternalContentPath($path) {
		self::$internalContentPath = $path;
	}

	protected static function getPrefixedContentFilePath(string $dir, string $key, int $num, bool $full = true): string {
		return rtrim($dir, '/').'/'. $key .'/' . self::makeContentFilePath($num, $full);
	}

	/**
	 * TODO use this for sfbzip too
	 * @param int $num
	 * @param bool $full
	 * @return string
	 */
	public static function makeContentFilePath($num, $full = true) {
		$realnum = $num;
		$num = (int) $num;
		$word = 4; // a word is four bytes long
		$bin_in_hex = 4; // one hex character corresponds to four binary digits
		$path = str_repeat('+/', $num >> ($word * $bin_in_hex));
		$hex = str_pad(dechex($num), $word, '0', STR_PAD_LEFT);
		$hex = substr($hex, -$word); // take last $word characters
		$path .= substr($hex, 0, 2) . '/';
		if ($full) {
			$path .= $realnum;
		}

		return $path;
	}

	public static function getCover($id, $width = 200, $format = 'jpg') {
		return str_replace(self::$webContentPath, self::$webThumbPath, self::getContentFilePath('book-cover', $id)) . ".$width.$format";
	}

	public static function fetchBibliomanCover($bibliomanId) {
		return file_get_contents(str_replace('ID', $bibliomanId, self::$bibliomanCoverUrlTemplate));
	}

	public static function clearCoverCache($bookOrId) {
		if ($bookOrId instanceof Book) {
			$bookOrId = [$bookOrId->getId()];
		} else if (!is_array($bookOrId)) {
			$bookOrId = [$bookOrId];
		}
		$browser = new Browser();
		$browser->post(self::$clearBookCoverCacheUrl, [], http_build_query(['ids' => implode("\n", $bookOrId)]));
	}

	public static function copyCoverFromBiblioman(Book $book) {
		$internalCoverPath = self::getInternalContentFilePath('book-cover', $book->getId()).'.'.self::$bookCoverExtension;
		$bibliomanCover = self::fetchBibliomanCover($book->getBibliomanId());
		file_put_contents($internalCoverPath, $bibliomanCover);
		self::clearCoverCache($book);
	}

	public static function generateBookInfoFromBiblioman($bibliomanId) {
		$url = str_replace('ID', $bibliomanId, self::$bibliomanBookJsonUrlTemplate);
		$bibliomanBook = json_decode(file_get_contents($url));
		if (empty($bibliomanBook)) {
			return null;
		}
		$shownFields = [
			'author' => 'Автор',
			'title' => 'Заглавие',
			'translator' => 'Преводач',
			'dateOfTranslation' => 'Година на превод',
			'translatedFromLanguage' => 'Език, от който е преведено',
			'edition' => 'Издание',
			'publisher' => 'Издател',
			'publisherCity' => 'Град на издателя',
			'publishingYear' => 'Година на издаване',
			'contentType' => 'Тип',
			'nationality' => 'Националност',
			'printingHouse' => 'Печатница',
			'printOut' => 'Излязла от печат',
			'chiefEditor' => 'Главен редактор',
			'managingEditor' => 'Отговорен редактор',
			'editor' => 'Редактор',
			'publisherEditor' => 'Редактор на издателството',
			'artistEditor' => 'Художествен редактор',
			'technicalEditor' => 'Технически редактор',
			'consultant' => 'Консултант',
			'scienceEditor' => 'Научен редактор',
			'reviewer' => 'Рецензент',
			'artist' => 'Художник',
			'illustrator' => 'Художник на илюстрациите',
			'corrector' => 'Коректор',
			'isbn' => 'ISBN',
			'isbn13' => 'ISBN-13',
		];
		$output = ['__Издание:__'];
		foreach ($shownFields as $shownField => $fieldName) {
			if (!empty($bibliomanBook->$shownField)) {
				$output[] = "{$fieldName}: {$bibliomanBook->$shownField}";
			}
		}
		$output[] = "Адрес в Библиоман: ".str_replace('ID', $bibliomanId, self::$bibliomanBookUrlTemplate);
		return "\t".implode("\n\t", $output)."\n";
	}
}
