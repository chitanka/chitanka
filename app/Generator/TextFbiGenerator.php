<?php namespace App\Generator;

use App\Entity\Text;
use App\Legacy\Legacy;

class TextFbiGenerator {

	/**
	 * Return fiction book info for given text.
	 * @param Text $text
	 * @return string
	 */
	public function generateFbiForText(Text $text) {
		return $this->clearFbi(implode("\n", [
			$this->genFbiMain($text),
			$this->genFbiOriginal($text),
			$this->genFbiDocument($text),
			$this->genFbiEdition(),
		]));
	}

	private function genFbiMain(Text $text) {
		return <<<EOS
{Произведение:
{$this->genFbiMainAuthors($text)}
|Заглавие     = {$this->genFbiMainTitle($text)}
{Анотация:
{$text->getAnnotation()}
}
|Дата         = {$text->getYear()}
|Корица       =
|Език         = {$text->getLang()}
|Ориг.език    = {$this->genFbiMainOrigLang($text)}
{$this->genFbiMainTranslators($text)}
|Поредица     = {$this->genFbiMainSeries($text)}
|Жанр         =
|Ключови-думи = {$this->genFbiMainKeywords($text)}
}
EOS;
	}

	private function genFbiOriginal(Text $text) {
		if (!$text->isTranslation()) {
			return '';
		}
		return <<<EOS
{Оригинал:
{$this->genFbiOriginalAuthors($text)}
|Заглавие     = {$this->genFbiOriginalTitle($text)}
|Дата         = {$text->getYear()}
|Език         = {$text->getOrigLang()}
|Поредица     = {$this->genFbiOriginalSeries($text)}
}
EOS;
	}

	private function genFbiDocument(Text $text) {
		$date = date('Y-m-d H:i:s');
		list($history, $version) = $text->getHistoryAndVersion();
		$history = "\n\t" . implode("\n\t", $history);
		return <<<EOS
{Документ:
|Автор         =
|Програми      =
|Дата          = $date
|Източник      =
|Сканирал      =
|Разпознал     =
|Редактирал    =
|Идентификатор = mylib-{$text->getId()}
|Версия        = $version
{История:$history
}
|Издател       =
}
EOS;
	}

	/**
	 * @todo implement
	 */
	private function genFbiEdition() {
		return <<<EOS
{Издание:
|Заглавие     =
|Издател      =
|Град         =
|Година       =
|ISBN         =
|Поредица     =
}
EOS;
	}

	/**
	 * @param string $fbi
	 */
	private function clearFbi($fbi) {
		return strtr($fbi, [
			"\n\n|" => "\n|",
		]);
	}

	private function genFbiMainAuthors(Text $text) {
		$fbi = '';
		foreach ($text->getAuthors() as $author) {
			$fbi .= "\n|Автор        = " . $author->getName();
		}
		return $fbi;
	}

	private function genFbiMainTitle(Text $text) {
		$fbi = $text->getTitle();
		if ($text->getSubtitle() != '') {
			$subtitle = strtr($text->getSubtitle(), [Text::TITLE_NEW_LINE => ', ']);
			$fbi .= ' (' . trim($subtitle, '()') . ')';
		}
		return $fbi;
	}

	private function genFbiMainTranslators(Text $text) {
		$fbi = '';
		foreach ($text->getTextTranslators() as $textTranslator) {
			$year = $textTranslator->getYear() ?: $text->getTransYear();
			$name = $textTranslator->getPerson()->getName();
			$fbi .= "\n|Преводач     = $name [&$year]";
		}
		return $fbi;
	}

	private function genFbiMainSeries(Text $text) {
		$series = $text->getSeries();
		$fbi = empty($series) ? Legacy::workType($text->getType(), false) : $series->getName();
		if ($series && $text->getSernr()) {
			$fbi .= " [{$text->getSernr()}]";
		}
		return $fbi;
	}

	private function genFbiMainOrigLang(Text $text) {
		return $text->isTranslation() ? $text->getOrigLang() : '';
	}

	private function genFbiMainKeywords(Text $text) {
		return implode(', ', $text->getLabelsNames());
	}

	private function genFbiOriginalAuthors(Text $text) {
		$fbi = '';
		foreach ($text->getAuthors() as $author) {
			$fbi .= "\n|Автор        = " . $author->getOrigName();
		}
		return $fbi;
	}

	private function genFbiOriginalTitle(Text $text) {
		$fbi = $text->getOrigTitle();
		$subtitle = $text->getOrigSubtitle();
		if ($subtitle != '') {
			$fbi .= ' (' . trim($subtitle, '()') . ')';
		}
		return $fbi;
	}

	private function genFbiOriginalSeries(Text $text) {
		$series = $text->getSeries();
		if (!$series) {
			return '';
		}
		$fbi = $series->getOrigName();
		if ($fbi != '' && $text->getSernr()) {
			$fbi .= " [{$text->getSernr()}]";
		}
		return $fbi;
	}

}
