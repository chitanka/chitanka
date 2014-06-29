<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Person;

class UpdatePersonsFromWikiDbCommand extends Command {

	private $output;
	private $errors;

	protected function configure() {
		$this
			->setName('db:update-persons-from-wiki')
			->setDescription('Update persons from wiki data')
			->setHelp('The <info>%command.name%</info> command reads data from the wiki and updates or adds new person entries.');
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;
		$this->errors = array();
		$this->processWikiPage('Работно ателие/Нови автори');
		$output->writeln('Done.');
	}

	/**
	 * @param string $pageName
	 */
	protected function processWikiPage($pageName) {
		$this->output->writeln('Fetching and processing wiki content...');
		$wikiPage = $this->_wikiBot()->fetch_page($pageName);
		if (preg_match('/== Готови автори ==(.+)== За попълване ==/ms', $wikiPage->text, $m)) {
			$personTemplates = trim($m[1]);
			if ($personTemplates && $this->updatePersons($personTemplates)) {
				$errors = implode("\n\n", $this->errors);
				$wikiPage->text = preg_replace('/(== Готови автори ==\n).+(\n== За попълване ==)/ms', "$1$errors\n$2", $wikiPage->text);
				$this->_wikiBot()->submit_page($wikiPage, '/* Готови автори */ пренасяне в базата на библиотеката');
			}
		}
	}

	/**
	 * @param string $wikiContent
	 */
	protected function updatePersons($wikiContent) {
		$persons = $this->_getPersonsDataFromWikiContent($wikiContent);
		$this->output->writeln('Updating persons...');
		foreach ($persons as $personData) {
			if ($personData['slug'] || $personData['name']) {
				$person = $this->createPerson($personData);
				if ($this->isNewPersonWithTakenSlug($person)) {
					$this->errors[] = "При $personData[name] се генерира идентификатор ({$person->getSlug()}), който вече присъства в базата.";
					continue;
				}
				try {
					$this->getEntityManager()->getPersonRepository()->save($person);
				} catch (\PDOException $e) {
					$this->errors[] = $e->getMessage();
				}
			}
		}
		return count($persons);
	}

	protected function createPerson($data) {
		if ($data['slug']) {
			$person = $this->getEntityManager()->getPersonRepository()->getBySlug($data['slug']);
			if ( ! $person) {
				$person = new Person;
				$person->setSlug($data['slug']);
			}
		} else {
			$person = new Person;
		}
		if ( ! empty($data['orig_name'])) $person->setOrigName($data['orig_name']);
		if ( ! empty($data['name'])) $person->setName($data['name']);
		if ( ! empty($data['real_name'])) $person->setRealName($data['real_name']);
		if ( ! empty($data['oreal_name'])) $person->setOrealName($data['oreal_name']);
		if ( ! empty($data['country'])) $person->setCountry($data['country']);
		if ( ! empty($data['info'])) $person->setInfo($data['info']);

		return $person;
	}

	protected function isNewPersonWithTakenSlug($person) {
		return !$person->getId() && $this->getEntityManager()->getPersonRepository()->getBySlug($person->getSlug());
	}

	private $_wikiBot;
	private function _wikiBot() {
		if ($this->_wikiBot == null) {
			error_reporting(E_WARNING);
			require_once $this->getContainer()->getParameter('kernel.root_dir') . '/../vendor/apibot/apibot.php';
			// $logins comes from vendor/apibot/logins.php
			$this->_wikiBot = new \Apibot($logins['chitanka'], array('dump_mode' => 0));
		}
		return $this->_wikiBot;
	}

	/**
	 * @param string $wikiContent
	 */
	private function _getPersonsDataFromWikiContent($wikiContent) {
		$templates = $this->_getPersonTemplatesFromWikiContent($wikiContent);
		$persons = array();
		foreach ($templates as $template) {
			$persons[] = $this->_getPersonDataFromWikiContent($template);
		}
		return $persons;
	}

	private function _getPersonDataFromWikiContent($template) {
		$wikiVars = $this->_getPersonVarsFromWikiContent($template);
		return array(
			'slug'       => @$wikiVars['идентификатор'],
			'name'       => @$wikiVars['име'],
			'orig_name'  => @$wikiVars['оригинално име'],
			'real_name'  => @$wikiVars['истинско име'],
			'oreal_name' => @$wikiVars['оригинално истинско име'],
			'country'    => @$wikiVars['държава'],
			'info'       => str_replace('_', ' ', @$wikiVars['уики']),
		);
	}

	private function _getPersonVarsFromWikiContent($template) {
		$wikiVars = array();
		foreach (explode("\n", trim($template)) as $row) {
			list($wikiVar, $value) = explode('=', ltrim($row, '| '));
			$wikiVars[trim($wikiVar)] = trim($value);
		}
		return $wikiVars;
	}

	/**
	 * @param string $wikiContent
	 */
	private function _getPersonTemplatesFromWikiContent($wikiContent) {
		if (preg_match_all('|\{\{Нов автор(.+)\}\}|Ums', $wikiContent, $matches)) {
			return $matches[1];
		}

		return array();
	}
}
