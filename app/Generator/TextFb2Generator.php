<?php namespace App\Generator;

use App\Entity\Text;
use App\Entity\Person;
use App\Service\ContentService;
use Sfblib\SfbToFb2Converter;

class TextFb2Generator {

	private $labelsToGenres = [
		'Алтернативна история' => 'sf_history',
		'Антиутопия' => 'sf_social',
		'Антична литература' => 'antique_ant',
		'Антропология' => 'science',
		'Археология' => 'science',
		'Биография' => 'nonf_biography',
		'Будизъм' => 'religion',
		'Военна фантастика' => 'sf_action',
		'Втора световна война' => 'sci_history',
		'Готварство' => 'home_cooking',
		'Готически роман' => 'sf_horror',
		'Дамска проза (чиклит)' => 'love_contemporary',
		'Даоизъм' => 'religion',
		'Детска литература' => 'child_prose',
		'Документална литература' => ['sci_history', 'nonfiction'],
		'Древен Египет' => 'sci_history',
		'Древен Рим' => 'sci_history',
		'Древна Гърция' => 'sci_history',
		'Епос' => 'antique_myths',
		'Еротика' => 'love_erotica',
		'Идеи и идеали' => 'sci_philosophy',
		'Икономика' => 'sci_business',
		'Индианска литература' => 'adv_indian',
		'Индия' => 'sci_culture',
		'Исторически роман' => 'prose_history',
		'История' => 'sci_history',
		'Киберпънк' => 'sf_cyberpunk',
		'Китай' => 'sci_culture',
		'Комедия' => 'humor',
		'Контракултура' => 'prose_counter',
		'Криминална литература' => 'detective',
		'Културология' => 'sci_culture',
		'Любовен роман' => 'love_contemporary',
		'Любовна лирика' => 'poetry',
		'Магически реализъм' => 'sf_horror',
		'Медицина' => 'sci_medicine',
		'Мемоари' => 'prose_history',
		'Мистика' => 'sf_horror',
		'Митология' => 'sci_culture',
		'Модернизъм' => ['sci_culture', 'design'],
		'Морска тематика' => 'adv_maritime',
		'Музика' => ['sci_culture', 'design'],
		'Народно творчество' => ['sci_culture', 'design'],
		'Научна фантастика' => 'sf',
		'Научнопопулярна литература' => 'science',
		'Окултизъм' => 'religion',
		'Организирана престъпност' => 'det_political',
		'Паралелни вселени' => ['sf', 'sf_epic', 'sf_heroic'],
		'Политология' => 'sci_politics',
		'Полусвободна литература' => 'home',
		'Постапокалипсис' => 'sf_history',
		'Приключенска литература' => 'adventure',
		'Психология' => 'sci_psychology',
		'Психофактор' => 'sci_philosophy',
		'Пътешествия' => 'adv_geo',
		'Реализъм' => ['sci_culture', 'design'],
		'Религия' => 'religion_rel',
		'Ренесанс' => 'sci_history',
		'Рицарски роман' => 'adv_history',
		'Робинзониада' => 'sf_heroic',
		'Родителство' => ['home_health', 'home'],
		'Романтизъм' => ['sci_culture', 'design'],
		'Руска класика' => 'prose_rus_classic',
		'Сатанизъм' => 'religion',
		'Сатира' => 'humor',
		'Световна класика' => 'prose_classic',
		'Секс' => 'home_sex',
		'Символизъм' => ['sci_culture', 'design'],
		'Средновековие' => 'antique',
		'Средновековна литература' => 'antique_european',
		'Старобългарска литература' => 'antique',
		'Съвременен роман (XX–XXI век)' => 'prose_contemporary',
		'Съвременна проза' => 'prose_contemporary',
		'Тайни и загадки' => 'sf_horror',
		'Трагедия' => 'antique',
		'Трилър' => 'thriller',
		'Уестърн' => 'adv_western',
		'Ужаси' => 'sf_horror',
		'Утопия' => 'sf_social',
		'Фантастика' => 'sf',
		'Фентъзи' => 'sf_fantasy',
		'Философия' => 'sci_philosophy',
		'Флора' => 'sci_biology',
		'Хумор' => 'humor',
		'Човек и бунт' => 'sci_philosophy',
		'Шпионаж' => 'det_espionage',
		'Япония' => 'sci_culture',

//		'Любовен роман+Исторически роман' => 'love_history',
//		'Детска литература+Фантастика' => 'child_sf',
//		'type play' => 'dramaturgy',
//		'type poetry' => 'poetry',
//		'type poetry+Детска литература' => 'child_verse',
//		'type tale+Детска литература' => 'child_tale',
	];

	public function generateFb2(Text $text) {
		$converter = new SfbToFb2Converter($text->getContentAsSfb(), ContentService::getInternalContentFilePath('img', $text->getId()));

		$converter->setObjectCount(1);
		$converter->setSubtitle(strtr($text->getSubtitle(), ['\n' => ' — ']));
		$converter->setGenre($this->getGenres($text));
		$converter->setKeywords($this->getKeywords($text));
		$converter->setTextDate($text->getYear());
		$converter->setLang($text->getLang());

		foreach ($text->getTranslators() as $translator) {
			$converter->addTranslator($translator->getName());
		}

		if ($text->getSeries()) {
			$converter->addSequence($text->getSeries()->getName(), $text->getSernr());
		}

		$this->setSrcData($converter, $text);

		$converter->setDocId($text->getDocId());
		list($history, $version) = $text->getHistoryAndVersion();
		$converter->setDocVersion($version);
		$converter->setHistory($history);
		$converter->setDocAuthor('Моята библиотека');

		if ($text->isGamebook()) {
			// recognize section links
			$converter->addRegExpPattern('/#(\d+)/', '<a l:href="#l-$1">$1</a>');
		}

		$converter->enablePrettyOutput();

		return $converter->convert()->getContent();
	}

	public function getGenres(Text $text) {
		$genres = [];
		$labels = $text->getLabelsNames();
		foreach ($labels as $label) {
			if (array_key_exists($label, $this->labelsToGenres)) {
				$genres = array_merge($genres, (array) $this->labelsToGenres[$label]);
			}
		}
		$genres = array_unique($genres);
		if (empty($genres)) {
			switch ($text->getType()) {
				case 'poetry': $genres[] = 'poetry'; break;
				default:       $genres[] = 'prose_contemporary';
			}
		}
		return $genres;
	}

	private function getKeywords(Text $text) {
		return implode(', ', $text->getLabelsNames());
	}

	private function setSrcData(SfbToFb2Converter $converter, Text $text) {
		$converter->setSrcLang($text->getOrigLang() ?: '');
		if (!$text->isTranslation()) {
			return;
		}
		foreach ($text->getAuthors() as $author) {
			$this->addSrcAuthor($converter, $author);
		}

		$converter->setSrcTitle($text->getOrigTitle() != '' ? $text->getOrigTitle() : '(no data for original title)');
		$converter->setSrcSubtitle($text->getOrigSubtitle());

		if ($text->getSeries() && $text->getSeries()->getOrigName()) {
			$converter->addSrcSequence($text->getSeries()->getOrigName(), $text->getSernr());
		}
	}

	private function addSrcAuthor(SfbToFb2Converter $converter, Person $author) {
		if ($author->getOrigName() == '') {
			$converter->addSrcAuthor('(no original name for '.$author->getName().')', false);
		} else {
			$converter->addSrcAuthor($author->getOrigName());
		}
	}
}
