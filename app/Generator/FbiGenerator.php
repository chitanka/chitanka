<?php namespace App\Generator;

use App\Entity\Text;

class FbiGenerator {

	/**
	 * Return fiction book info for given text.
	 * @param Text $text
	 * @return string
	 */
	public function getFbiForText(Text $text) {
		return implode("\n", array(
			$this->getFbiMain($text),
			$this->getFbiOriginal($text),
			$this->getFbiDocument($text),
			//$this->getFbiEdition($text), // not implemented
		));
	}

	protected function getFbiMain(Text $text) {
		$authors = '';
		foreach ($text->getAuthors() as $author) {
			$authors .= "\n|Автор        = " . $author->getName();
		}
		$title = $text->getTitle();
		if ($text->getSubtitle() != '') {
			$subtitle = strtr($text->getSubtitle(), array(self::TITLE_NEW_LINE => ', '));
			$title .= ' (' . trim($subtitle, '()') . ')';
		}
		$anno = $text->getAnnotation();
		$translators = '';
		foreach ($text->getTextTranslators() as $textTranslator) {
			$year = $textTranslator->getYear() ?: $text->getTransYear();
			$name = $textTranslator->getPerson()->getName();
			$translators .= "\n|Преводач     = $name [&$year]";
		}
		$series = $text->getSeries();
		$seriesView = empty($series) ? Legacy::workType($text->getType(), false) : $series->getName();
		if ($series && $text->getSernr()) {
			$seriesView .= " [{$text->getSernr()}]";
		}
		$keywords = implode(', ', $text->getLabelsNames());
		$origLangView = $text->getLang() == $text->getOrigLang() ? '' : $text->getOrigLang();
		return <<<EOS
{Произведение:$authors
|Заглавие     = $title
{Анотация:
$anno
}
|Дата         = {$text->getYear()}
|Корица       =
|Език         = {$text->getLang()}
|Ориг.език    = $origLangView$translators
|Поредица     = $seriesView
|Жанр         =
|Ключови-думи = $keywords
}
EOS;
	}

	protected function getFbiOriginal(Text $text) {
		if ($text->getLang() == $text->getOrigLang()) {
			return '';
		}
		$authors = '';
		foreach ($text->getAuthors() as $author) {
			$name = $author->getOrigName();
			$authors .= "\n|Автор        = $name";
		}
		$title = $text->getOrigTitle();
		$subtitle = $text->getOrigSubtitle();
		if ($subtitle != '') {
			$title .= ' (' . trim($subtitle, '()') . ')';
		}
		if ($text->getSeries()) {
			$series = $text->getSeries()->getOrigName();
			if ($series != '' && $text->getSernr()) {
				$series .= " [{$text->getSernr()}]";
			}
		} else {
			$series = '';
		}

		return <<<EOS
{Оригинал:$authors
|Заглавие     = $title
|Дата         = {$text->getYear()}
|Език         = {$text->getOrigLang()}
|Поредица     = $series
}
EOS;
	}

	protected function getFbiDocument(Text $text) {
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

	protected function getFbiEdition(Text $text) {
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
}
