<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Util\File;

class LegacyUpdateLibCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('lib:legacy-update')
			->setDescription('Add or update new texts and books (legacy variant)')
			->addArgument('input', InputArgument::REQUIRED, 'Input text file')
			->addArgument('globals', InputArgument::OPTIONAL, 'File with global variables')
			->addOption('save', null, InputOption::VALUE_NONE, 'Save generated text files in corresponding directories')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp(<<<EOT
The <info>lib:legacy-update</info> command adds or updates texts and books.
EOT
		);
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract class is not implemented
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->saveFiles = $input->getOption('save') === true;
		$this->dumpSql = $input->getOption('dump-sql') === true;
		$this->defineVars();
		list($queries, $errors) = $this->conquerTheWorld($input, $output, $this->getContainer()->get('doctrine.orm.default_entity_manager'));

		if ( ! empty($errors) ) {
			$output->writeln("/* ###########!!!   ГРЕШКИ:\n\n"
				. implode("\n", $errors) . "*/\n\n");
		}
		echo implode(";\n", $queries), ";\n";

		$output->writeln('/*Done.*/');
	}


	private function defineVars()
	{
		Setup::doSetup($this->getContainer());
		$this->db = Setup::db();
		$this->overwrite = true; // overwrite existing files?

		//$this->curTextId = $this->getNextId('text');
		$this->curEditId = $this->db->autoIncrementId(DBT_EDIT_HISTORY);
		$this->curBookRev = $this->db->autoIncrementId('book_revision');
		$this->entrydate = date('Y-m-d');
		$this->modifDate = $this->entrydate . date(' H:i:s');

		$this->orig_lang = 'bg';
		$this->series = 0;
		$this->sernr = null;
		$this->book = 0;
		$this->book_author = false; // link the book with the author
		$this->author = 'hristo-smirnenski';
		$this->license_orig = 1; // 1: PD, 2: FC
		$this->license_trans = null;
		$this->translator = 0;
		$this->lang = 'bg';
		$this->year = 1920;
		$this->trans_year = null;
		$this->type = 'poetry';
		$this->comment = 'Добавяне (от Събрани съчинения, том 4. Български писател, 1979. Съставителство, редакция и бележки: Веска Иванова.)';
		// 'USERNAME' => array(PERCENT, 'Сканиране, разпознаване и корекция', date('Y'))
		$this->users = array('zelenkroki' => array(30, 'Форматиране и последна редакция', date('Y')));
		$this->year2 = null;
		$this->trans_year2 = null;
		$this->subtitle = $this->orig_title = $this->orig_subtitle = null;
		$this->labels = array();

		$this->errors = array();
		$this->contentDir = $this->getContainer()->getParameter('kernel.root_dir').'/../web/content';
		$this->books = array();
	}

	function conquerTheWorld(InputInterface $input, OutputInterface $output, $em)
	{
		$file = $input->getArgument('input');
		$contents = file_get_contents($file);
		if (strpos($contents, "\t") === false) {
			$queries = $this->insertManyFromManyFiles(explode("\n", $contents));
		} else {
			$queries = $this->insertManyFromOneFile($file);
		}
		array_unshift($queries, 'SET NAMES utf8');

		return array($queries, $this->errors);
	}




	function insertManyFromManyFiles(array $files)
	{
		$queries = array();

		foreach ($files as $file) {
			if (empty($file)) continue;

			$fp = fopen($file, 'r') or die("$file не съществува.\n");

			$authorLine = $this->clearHeadline( $this->getFirstNonEmptyFileLine($fp) );
			$titleLine = rtrim($this->clearHeadline( fgets($fp) ), '*');
			$textContent = ltrim($this->getFileContentTillEnd($fp), "\n");
			$headlevel = strpos($textContent, '>>') === false ? 1 : 2;

			fclose($fp);

			$queries = array_merge($queries, $this->insertCurrentText(array(
				'author'      => $authorLine,
				'title'       => $titleLine,
				'textContent' => $textContent,
				'headlevel'   => $headlevel,
			)));
		}

		return $queries;
	}


	function insertManyFromOneFile($file)
	{
		$fp = fopen($file, 'r') or die("$file не съществува.\n");

		$queries = array();

		$vars = array('textContent' => ''); // for current text

		$bookFile = '';

		while ( !feof($fp) ) {
			$line = fgets($fp);

			if ( $this->book && $this->isBookHeaderLine($line) ) {
				$bookFile .= str_replace('+', '>', $line);
			} else if ( ! $this->isHeaderLine($line) ) {
				// add this line to current text
				$vars['textContent'] .= $this->moveHeadersUp($line);
			} else {
				if ( count($vars) > 1 ) { // we have read a text, save it
					$queries = array_merge($queries, $this->insertCurrentText($vars));
				}

				$vars = array('textContent' => ''); // starting next text

				if (strpos($line, '>	$id=') === 0) {
					if ($this->book) {
						$textId = str_replace('>	$id=', '', rtrim($line));
						$set = array('book_id' => $this->book, 'text_id' => $textId, 'share_info' => 0);
						$queries[] = $this->db->insertQ(DBT_BOOK_TEXT, $set, true, false);
						$bookFile .= ">\t{text:". $textId ."}\n\n";
					}
					continue;
				}

				if ($this->book) {
					// FIXME this->curTextId is not initialized used anymore
					$bookFile .= ">\t{text:{$this->curTextId}}\n\n";
				}

				$line = rtrim($this->clearHeadline($line), '*');
				if ( strpos($line, '|') === false ) {
					$vars['title'] = $line;
				} else {
					list($vars['title'], $vars['subtitle']) = explode('|', $line);
				}

				// check for an author line
				$line2 = fgets($fp);
				if ( $this->isHeaderLine($line2) ) {
					$vars['author'] = $this->clearHeadline($line2);
				} else {
					$vars['textContent'] .= $this->moveHeadersUp($line2);
				}
			}
		}

		fclose($fp);

		// last text
		$queries = array_merge($queries, $this->insertCurrentText($vars));

		if ($bookFile) {
			$queries[] = "/*\n$bookFile\n*/";
		}

		return $queries;
	}


	/*
		the big, messy processing unit.
		has side effects

		@param $avars Variables for the current text.
			Should have at least following keys:
			- textContent
			- title
	*/
	private function insertCurrentText(array $avars)
	{
		extract($avars);

		list($newCont, $vars) = $this->popvars($textContent);
		$textContent = $newCont;

		extract($vars);

		// import non-existing stuff from the outer world
		$fields = array('subtitle', 'lang', 'orig_lang', 'orig_title', 'orig_subtitle', 'trans_year', 'year', 'trans_year2', 'year2', 'type', 'series', 'sernr', 'license_orig', 'license_trans', 'author', 'translator', 'book', 'labels', 'users');
		foreach ($fields as $field) {
			if ( !isset($$field) ) {
				$$field = $this->$field;
			}
		}

		$isNew = ! isset($id);
		if ( $isNew ) {
			$textId = $this->getNextId('text');
			$comment = $this->comment;
		} else {
			$textId = $id;
			$comment = $this->comment_edit;
		}

		foreach ( array('author', 'translator', 'labels') as $var ) {
			if ( is_string($$var) && strpos($$var, ',') !== false ) {
				$$var = explode(',', $$var);
			}
		}

		if ( is_string($users) ) {
			$uarr = explode(';', $users);
			$users = array();
			foreach ( $uarr as $user_perc ) {
				$up = explode(',', $user_perc);
				$users[ $up[0] ] = isset($up[1]) ? $up[1] : 100;
			}
		}

		$qs = array();
		$l = strlen($textContent) / 1000;
		$zl = $l / 3.5;

		$textQuery = '';
		if ($isNew) {
			$set = array(
				'slug' => String::slugify($title),
				'title' => $title,
				'subtitle' => $subtitle,
				'lang' => $lang,
				'orig_lang' => $orig_lang,
				'orig_title' => $orig_title,
				'orig_subtitle' => $orig_subtitle,
				'trans_year' => $trans_year,
				'year' => $year,
				'type' => $type,
				'sernr' => $sernr,
				'orig_license_id' => $license_orig,
				'trans_license_id' => $license_trans,
				'created_at' => $this->entrydate,
				'size' => $l,
				'zsize' => $zl,
				'id' => $textId,
				'headlevel' => (isset($headlevel) ? $headlevel : 0),
				'mode' => 'public',
			);
			if ($series) {
				$set['series_id'] = is_numeric($series) ? $series : $this->getSeriesId($series);
			}
			$textQuery = $this->db->replaceQ(DBT_TEXT, $set);
		} else {
			$set = array(
				'slug' => String::slugify($title),
				'title' => $title,
				'subtitle' => $subtitle,
				'size' => $l, 'zsize' => $zl,
				'headlevel' => isset($headlevel) ? $headlevel : 0,
			);
			$textQuery = $this->db->updateQ(DBT_TEXT, $set, array('id' => $textId));
		}
		$qs[] = "\n\n\n/* Текст $textId */\n\n$textQuery";

		$set = array(
			'id' => $this->curEditId,
			'text_id' => $textId,
			'user_id' => 1,
			'comment' => $comment,
			'date' => $this->modifDate,
			'first' => (int) $isNew,
		);
		$qs[] = $this->db->replaceQ(DBT_EDIT_HISTORY, $set);
		$qs[] = $this->db->updateQ(DBT_TEXT, array('cur_rev_id' => $this->curEditId), array('id' => $textId));
		$this->curEditId++;

		if ( !empty($book) ) {
			if ( ! in_array($book, $this->books)) {
				$this->books[] = $book;
				$book_title = isset($book_title) ? $book_title : $title;
				$set = array(
					'id' => $book,
					'category_id' => 1,
					'title' => $book_title,
					'subtitle' => (empty($subtitle) ? '' : $subtitle),
					'title_author' => (isset($book_author) ? $book_author : $author),
					'slug' => String::slugify($book_title),
					'lang' => $lang,
					'orig_lang' => $orig_lang,
					'year' => $year,
					'type' => 'single',
					'has_anno' => strpos($textContent, 'A>') !== false ? 1 : 0,
					'has_cover' => 1,
					'mode' => 'public',
					'created_at' => $this->entrydate,
				);
				$qs[] = $this->db->insertQ(DBT_BOOK, $set, true);

				$set = array(
					'id' => $this->curBookRev++,
					'book_id' => $book,
					'comment' => 'Добавяне',
					'date' => $this->modifDate,
				);
				$qs[] = $this->db->insertQ('book_revision', $set, true);
			}
			$set = array('book_id' => $book, 'text_id' => $textId, 'share_info' => 1);
			$qs[] = $this->db->insertQ(DBT_BOOK_TEXT, $set, true);
		}

		if ( $isNew ) {
			$qs[] = $this->db->deleteQ(DBT_AUTHOR_OF, array('text_id' => $textId));
			foreach ( (array) $author as $pos => $author1 ) {
				$authorId = is_numeric($author1) ? $author1 : $this->getPersonId($author1);
				if ( empty($authorId) ) { continue; }

				$set = array('person_id' => $authorId, 'text_id' => $textId, 'pos' => $pos);
				$qs[] = $this->db->insertQ(DBT_AUTHOR_OF, $set, false, false);
				if ( $this->book_author ) {
					$set = array('book_id' => $book, 'person_id' => $authorId);
					$qs[] = $this->db->insertQ(DBT_BOOK_AUTHOR, $set, true, false);
				}
			}
		}

		if ( $isNew ) {
			$qs[] = $this->db->deleteQ(DBT_TRANSLATOR_OF, array('text_id' => $textId));
			foreach ( (array) $translator as $pos => $translator1 ) {
				$translatorId = is_numeric($translator1) ? $translator1 : $this->getPersonId($translator1);
				if ( empty($translatorId) ) { continue; }

				$set = array('person_id' => $translatorId, 'text_id' => $textId, 'pos' => $pos);
				$qs[] = $this->db->insertQ(DBT_TRANSLATOR_OF, $set, false, false);
			}
		}

		foreach ( (array) $labels as $label ) {
			if ( empty($label) ) continue;
			$set = array('text_id' => $textId, 'label_id' => $label);
			$qs[] = $this->db->insertQ(DBT_TEXT_LABEL, $set, true, false);
		}

		foreach ($users as $user => $userData) {
			list($percent, $userComment, $humanDate) = $userData;
			$userId = $this->getUserId($user);
			if ( empty($userId) ) { continue; }
			$size = $percent/100 * $l;
			$set = array(
				'user_id' => $userId,
				'username' => $user,
				'text_id' => $textId,
				'size' => $size,
				'percent' => $percent,
				'date' => $this->modifDate,
				'humandate' => $humanDate,
				'comment' => $userComment,
			);
			$qs[] = $this->db->insertQ(DBT_USER_TEXT, $set, true, false);
		}

		$textContent = trim($textContent, "\n") . "\n";

		$file = $this->contentDir . '/text/' . Legacy::makeContentFilePath($textId);

		if ( !$this->overwrite && file_exists($file) ) {
			$qs[] = "/* $textId СЪЩЕСТВУВА! */\n";
		} else {
			$id = empty($book) || isset($anno) ? $textId : $book;
			$dir = empty($book) || isset($anno) ? 'text-anno' : 'book-anno';
			$textContent = $this->createAnnoFile($id, $textContent, $this->saveFiles, $dir);

			$id = empty($book) || isset($info) ? $textId : $book;
			$dir = empty($book) || isset($info) ? 'text-info' : 'book-info';
			$textContent = $this->createInfoFile($id, $textContent, $this->saveFiles, $dir);

			if ($this->saveFiles) {
				if ( ($type == 'poetry' || $type == 'poem' || $type == 'prosepoetry')
						&& strpos($textContent, 'P>') === false ) {
					if ( substr($textContent, 0, 3) == "\t[*" ) {
						$textContent = preg_replace('/^\t\[([^]]+)\]\n/ms', "$0P>\n", $textContent, 1) . "P$\n";
					} else {
						$textContent = "P>\n{$textContent}P$\n";
					}
				}
				File::myfile_put_contents($file, $textContent);
				if ( isset($headlevel) ) {
					$qs = array_merge($qs, $this->makeUpdateChunkQuery($file, $textId, $headlevel));
				}
			}
		}

		return $qs;
	}


	public function makeUpdateChunkQuery($file, $textId, $headlevel)
	{
		require_once __DIR__ . '/../Legacy/headerextract.php';

		$data = array();
		foreach (\Chitanka\LibBundle\Legacy\makeDbRows($file, $headlevel) as $row) {
			$name = $row[2];
			$name = strtr($name, array('_'=>''));
			$name = $this->db->escape(String::my_replace($name));
			$data[] = array($textId, $row[0], $row[1], $name, $row[3], $row[4]);
		}
		$qs = array();
		$qs[] = $this->db->deleteQ('text_header', array('text_id' => $textId));
		if ( !empty($data) ) {
			$fields = array('text_id', 'nr', 'level', 'name', 'fpos', 'linecnt');
			$qs[] = $this->db->multiinsertQ('text_header', $data, $fields);
		}

		return $qs;
	}


	private function createAnnoFile($textId, $content, $saveFiles = false, $dir = 'text-anno')
	{
		return $this->createExtraFile($textId, $content, "$this->contentDir/$dir/",
			\Sfblib_SfbConverter::ANNO_S, \Sfblib_SfbConverter::ANNO_E, $saveFiles);
	}

	private function createInfoFile($textId, $content, $saveFiles = false, $dir = 'text-info')
	{
		return $this->createExtraFile($textId, $content, "$this->contentDir/$dir/",
			\Sfblib_SfbConverter::INFO_S, \Sfblib_SfbConverter::INFO_E, $saveFiles);
	}

	private function createExtraFile($textId, $content, $dir, $startTag, $endTag, $saveFiles = false)
	{
		/* preg_* functions do not work correctly with big strings */

		$startPos = strpos($content, $startTag);
		if ( $startPos === false ) {
			return $content;
		}
		$endPos = strpos($content, $endTag, $startPos);
		if ( $endPos === false ) {
			return $content;
		}

		$len = $endPos - $startPos;
		$extra = substr($content, $startPos + strlen($startTag), $len - strlen($startTag));
		$extra = ltrim($extra, "\n");

		if ($saveFiles) {
			File::myfile_put_contents($dir . Legacy::makeContentFilePath($textId), $extra);
		}
		$content = substr_replace($content, '', $startPos, $len + strlen($endTag));
		$content = trim($content, "\n") . "\n";

		return $content;
	}


	private function popvars($text) {
		$vars = array();
		$re = '/\t?\$(\w+)=(.*)\n/';
		$mcnt = preg_match_all($re, $text, $m);
		if ($mcnt) {
			for ($i = 0; $i < $mcnt; $i++) {
				$key = $m[1][$i];
				$vars[$key] = $m[2][$i];
			}
			$text = preg_replace($re, '', $text);
			$text = trim($text, "\n") . "\n";
		}

		return array($text, $vars);
	}


	private $_curIds = array();
	private $_ids = array(
		'text' => array(),
		'book' => array(534,1113,1186,1224,1249,1299,1303,1697,2004,2115),
	);
	private function getNextId($table)
	{
		if (isset($this->_ids[$table]) && count($this->_ids[$table])) {
			return array_shift($this->_ids[$table]);
		}
		if ( ! isset($this->_curIds[$table])) {
			$this->_curIds[$table] = $this->db->autoIncrementId($table);
		} else {
			$this->_curIds[$table]++;
		}

		return $this->_curIds[$table];
	}

	private function getPersonId($personName)
	{
		$id = $this->db->getFields(DBT_PERSON, array('slug' => trim($personName)), 'id');

		if ( empty($id) ) {
			$this->errors[] = "Личността $personName не съществува";
		}

		return $id;
	}

	private function getSeriesId($name)
	{
		$id = $this->db->getFields(DBT_SERIES, array('slug' => trim($name)), 'id');

		if ( empty($id) ) {
			$this->errors[] = "Поредицата $name не съществува";
		}

		return $id;
	}

	private function getUserId($userName)
	{
		$id = $this->db->getFields(DBT_USER, array('username' => $userName), 'id');

		if ( empty($id) ) {
			$this->errors[] = "Потребителя $userName не съществува";
		}

		return $id;
	}


	private function clearHeadline($headline)
	{
		return trim($headline, " \n\t>|");
		#return strtr($headline, array("|\t" => '', "\n" => ''));
	}

	private function isHeaderLine($line)
	{
		return $line[0] == '>' && $line[1] == "\t";
	}

	private function isBookHeaderLine($line)
	{
		return $line[0] == '+';
	}

	/** move headers one niveau up */
	private function moveHeadersUp($content)
	{
		$content = preg_replace('/^>(>+)/', '$1', $content);

		return $content;
	}


	private function getFirstNonEmptyFileLine($fp)
	{
		while ( ! feof($fp) ) {
			$line = fgets($fp);
			$line = trim($line);
			if ( ! empty($line) ) {
				break;
			}
		}
		return $line;
	}


	private function getFileContentTillEnd($fp)
	{
		$content = '';
		while ( !feof($fp) ) {
			$content .= fgets($fp);
		}
		return $content;
	}

}
