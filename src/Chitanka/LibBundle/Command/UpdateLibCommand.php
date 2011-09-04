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

class UpdateLibCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('lib:update')
			->setDescription('Add or update new texts and books')
			->addArgument('input', InputArgument::REQUIRED, 'Directory with input files or other input directories')
			->addOption('save', null, InputOption::VALUE_NONE, 'Save generated files in corresponding directories')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp(<<<EOT
The <info>lib:update</info> command adds or updates texts and books.
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
		$queries = $this->conquerTheWorld($input, $output, $this->container->get('doctrine.orm.default_entity_manager'));

		$this->printQueries($queries);

		$output->writeln('/*Done.*/');
	}


	private function defineVars($em)
	{
		$this->em = $em;

		$this->overwrite = false; // overwrite existing files?

		$this->entrydate = date('Y-m-d');
		$this->modifDate = $this->entrydate . ' ' . date('H:i:s');

		$this->contentDir = $this->container->getParameter('kernel.root_dir').'/../web/content';
		$this->works = $this->books = array();
		$this->errors = array();
	}

	function conquerTheWorld(InputInterface $input, OutputInterface $output, $em)
	{
		$this->defineVars($em);

		$queries = array();
		$dir = $input->getArgument('input');
		if (count(glob("$dir/*.data")) == 0) {
			foreach (glob("$dir/*", GLOB_ONLYDIR) as $dir) {
				$queries = array_merge($queries, $this->processPacket($dir));
			}
		} else {
			$queries = $this->processPacket($dir);
		}
		array_unshift($queries, 'SET NAMES utf8');

		return $queries;
	}




	private function processPacket($dir)
	{
		$workFiles = self::sortDataFiles(glob("$dir/work.*.data"));
		$bookFiles = self::sortDataFiles(glob("$dir/book.*.data"));

		$queries = array();
		foreach ($workFiles as $workFile) {
			$queries = array_merge($queries, $this->insertWork($this->processWorkFiles($workFile)));
		}

		foreach ($bookFiles as $bookFile) {
			$queries = array_merge($queries, $this->insertBook($this->processBookFiles($bookFile)));
		}

		return $queries;
	}

	private function processWorkFiles($dataFile)
	{
		$work = array();
		foreach (file($dataFile) as $line) {
			$work += self::_extractVarFromLineData($line);
		}
		$packetId = $work['id'];
		$work['is_new'] = $packetId < 0;
		if ($work['is_new']) {
			$work['id'] = $this->getNextId('text');
		}
		$work['revision_id'] = $this->getNextId('text_revision');
		if (isset($work['subtitle']) && $work['subtitle'][0] == '(') {
			$work['subtitle'] = trim($work['subtitle'], '()');
		}
		if (isset($work['authors'])) {
			$authors = array();
			foreach (explode(',', $work['authors']) as $slug) {
				$authors[] = $this->getObjectId('person', $slug);
			}
			$work['authors'] = $authors;
		}
		if (isset($work['year']) && strpos($work['year'], '-') !== false) {
			list($work['year'], $work['year2']) = explode('-', $work['year']);
		}
		if (isset($work['translators'])) {
			$translators = array();
			foreach (explode(';', $work['translators']) as $slugYear) {
				list($slug, $transYear) = explode(',', $slugYear);
				if ($transYear == '?') $transYear = null;
				if ($slug != '?') {
					$translators[] = array($this->getObjectId('person', $slug), $transYear);
				}
				$work['trans_year'] = $transYear;
			}
			$work['translators'] = $translators;
		} else if ($work['is_new'] && $work['lang'] != $work['orig_lang']) {
			$work['trans_license'] = 'fc';
		}
		if (isset($work['users'])) {
			$users = array();
			foreach (explode(';', $work['users']) as $userPerc) {
				$parts = explode(',', $userPerc);
				$userId = $this->getObjectId('user', $parts[0], 'username');
				if ($userId) { // only registered users
					$users[$userId] = isset($parts[1]) ? $parts[1] : 100;
				}
			}
			$work['users'] = $users;
		}
		if (file_exists($file = str_replace('.data', '.tmpl', $dataFile))) {
			$work['tmpl'] = $file;
			$work = self::prepareWorkTemplate($work);
		} else if (file_exists($file = str_replace('.data', '.text', $dataFile))) {
			$work['text'] = $file;
		}
		if (file_exists($file = str_replace('.data', '.info', $dataFile))) {
			$work['info'] = $file;
		}
		if (file_exists($dir = strtr($dataFile, array('work.' => '', '.data' => ''))) && is_dir($dir)) {
			$work['img'] = $dir;
		}

		return $this->works[$packetId] = $work;
	}


	private function processBookFiles($dataFile)
	{
		$book = array();
		foreach (file($dataFile) as $line) {
			$book += self::_extractVarFromLineData($line);
		}
		$packetId = $book['id'];
		$book['is_new'] = $packetId < 0;
		if ($book['is_new']) {
			$book['id'] = $this->getNextId('book');
		}
		$book['revision_id'] = $this->getNextId('book_revision');
		if (isset($book['subtitle']) && $book['subtitle'][0] == '(') {
			$book['subtitle'] = trim($book['subtitle'], '()');
		}
		if (isset($book['authors'])) {
			$authors = array();
			foreach (explode(',', $book['authors']) as $slug) {
				$authors[] = $this->getObjectId('person', $slug);
			}
			$book['authors'] = $authors;
		}
		if (file_exists($file = str_replace('.data', '.tmpl', $dataFile))) {
			list($book['tmpl'], $book['works']) = $this->getBookTemplate($file, $this->works);
		}
		if (file_exists($file = str_replace('.data', '.anno', $dataFile))) {
			$book['anno'] = $file;
		}
		if (file_exists($file = str_replace('.data', '.info', $dataFile))) {
			$book['info'] = $file;
		}
		if (file_exists($file = str_replace('.data', '.covr.jpg', $dataFile))) {
			$book['cover'] = $file;
		}

		return $book;
	}


	static private function _extractVarFromLineData($line)
	{
		$separator = '=';
		$parts = explode($separator, $line);
		$var = trim(array_shift($parts));
		$value = trim(implode($separator, $parts));
		if (empty($value) || $value == '?') {
			return array();
		}
		if ($value == '-') {
			$value = '';
		}
		return array($var => $value);
	}

	static public function sortDataFiles($files)
	{
		$sorted = array();
		foreach ($files as $file) {
			if (preg_match('/\.(\d+)\.data/', $file, $matches)) {
				$sorted[$matches[1]] = $file;
			}
		}
		ksort($sorted);

		return $sorted;
	}


	static private function getBookTemplate($file, $works)
	{
		$bookTmpl = file_get_contents($file);
		$bookWorks = array();
		if (preg_match_all('/\{(file|text):(\d+)\}/', $bookTmpl, $m)) {
			// already existing in the database works included in this book
			foreach ($m[2] as $oldWork) {
				$bookWorks[] = array('id' => $oldWork, 'is_new' => false);
			}
		}
		foreach ($works as $packetId => $work) {
			if (strpos($bookTmpl, ":$packetId") !== false) {
				$bookTmpl = strtr($bookTmpl, array(
					":$packetId}" => ":$work[id]}",
					":$packetId-" => ":$work[id]-",
				));
				$bookWorks[] = $work;
			}
		}

		return array($bookTmpl, $bookWorks);
	}

	static private function prepareWorkTemplate($work)
	{
		$files = array();
		$template = file_get_contents($work['tmpl']);
		if (preg_match_all('/\{(text|file):-1(-.+)\}/', $template, $matches)) {
			foreach ($matches[2] as $match) {
				$files["$work[id]$match"] = str_replace('.tmpl', "$match.text", $work['tmpl']);
			}
		}
		$work['text'] = $files;
		$work['tmpl'] = preg_replace('/(file|text):-1-/', '$1:'.$work['id'].'-', $template);

		return $work;
	}


	private $_objectsIds = array();
	private function getObjectId($table, $query, $column = 'slug')
	{
		if ( ! isset($this->_objectsIds[$table][$query])) {
			$sql = "SELECT id FROM $table WHERE $column = '$query'";
			$result = $this->em->getConnection()->fetchAssoc($sql);
			$this->_objectsIds[$table][$query] = $result['id'];
		}

		return $this->_objectsIds[$table][$query];
	}

	private $_curIds = array();
	private function getNextId($table)
	{
		if ( ! isset($this->_curIds[$table])) {
			$this->_curIds[$table] = $this->olddb()->autoIncrementId($table);
		} else {
			$this->_curIds[$table]++;
		}

		return $this->_curIds[$table];
	}

	private function insertWork(array $work)
	{
		$qs = array();

		$set = array(
			'id' => $work['id'],
		);
		if (isset($work['title'])) {
			$set += array(
				'slug' => (isset($work['slug']) ? $work['slug'] : String::slugify($work['title'])),
				'title' => String::my_replace($work['title']),
			);
		}
		if (isset($work['toc_level'])) {
			$set['headlevel'] = $work['toc_level'];
		} else if (isset($work['text'])) {
			$set['headlevel'] = $work['toc_level'] = self::guessTocLevel(file_get_contents($work['text']));
		}
		if ( ! empty($work['type'])) $set['type'] = $work['type'];
		if ( ! empty($work['lang'])) $set['lang'] = $work['lang'];
		if ( ! empty($work['orig_lang'])) $set['orig_lang'] = $work['orig_lang'];
		if (isset($work['text'])) {
			$size = self::getFileSize($work['text']) / 1000;
			$set += array(
				'size' => $size,
				'zsize' => ($size / 3.5),
			);
		}
		if ($work['is_new']) {
			$set += array(
				'created_at' => $this->entrydate,
				'dl_count' => 0,
				'read_count' => 0,
				'comment_count' => 0,
				'rating' => 0,
				'votes' => 0,
				'has_anno' => 0,
				'has_cover' => 0,
				'is_compilation' => isset($work['tmpl']),
				'mode' => 'public',

				'sernr' => (isset($work['ser_nr']) ? $work['ser_nr'] : 0),
				'orig_title' => (empty($work['orig_title']) ? '' : self::fixOrigTitle($work['orig_title'])),
			);
		}
		if (isset($work['subtitle'])) $set['subtitle'] = String::my_replace($work['subtitle']);
		if (isset($work['orig_subtitle'])) $set['orig_subtitle'] = self::fixOrigTitle($work['orig_subtitle']);
		if (isset($work['year'])) $set['year'] = $work['year'];
		if (isset($work['year2'])) $set['year2'] = $work['year2'];
		if (isset($work['trans_year'])) $set['trans_year'] = $work['trans_year'];

		if (isset($work['series'])) $set['series_id'] = $this->getObjectId('series', $work['series']);

		if (isset($work['orig_license'])) $set['orig_license_id'] = $this->getObjectId('license', $work['orig_license'], 'code');
		if (isset($work['trans_license'])) $set['trans_license_id'] = $this->getObjectId('license', $work['trans_license'], 'code');

		if ($work['is_new']) {
			$qs[] = $this->olddb()->replaceQ(DBT_TEXT, $set);
		} else if (count($set) > 1) {
			$qs[] = $this->olddb()->updateQ(DBT_TEXT, $set, array('id' => $work['id']));
		}

		if (isset($work['revision'])) {
			$set = array(
				'id' => $work['revision_id'],
				'text_id' => $work['id'],
				'user_id' => 1,
				'comment' => $work['revision'],
				'date' => $this->modifDate,
				'first' => ($work['is_new'] ? 1 : 0),
			);
			$qs[] = $this->olddb()->replaceQ(DBT_EDIT_HISTORY, $set);
			$qs[] = $this->olddb()->updateQ(DBT_TEXT, array('cur_rev_id' => $work['revision_id']), array('id' => $work['id']));
		} else {
			$qs[] = "/* no revision for text $work[id] */";
		}

		if ( ! empty($work['authors'])) {
			$qs[] = $this->olddb()->deleteQ(DBT_AUTHOR_OF, array('text_id' => $work['id']));
			foreach ($work['authors'] as $pos => $author) {
				$set = array('person_id' => $author, 'text_id' => $work['id'], 'pos' => $pos);
				$qs[] = $this->olddb()->insertQ(DBT_AUTHOR_OF, $set, false, false);
			}
		}

		if ( ! empty($work['translators'])) {
			$qs[] = $this->olddb()->deleteQ(DBT_TRANSLATOR_OF, array('text_id' => $work['id']));
			foreach ($work['translators'] as $pos => $translator) {
				list($personId, $transYear) = $translator;
				$set = array('person_id' => $personId, 'text_id' => $work['id'], 'pos' => $pos, 'year' => $transYear);
				$qs[] = $this->olddb()->insertQ(DBT_TRANSLATOR_OF, $set, false, false);
			}
		}

		if (isset($work['text']) && isset($work['users'])) {
			foreach ($work['users'] as $user => $percent) {
				$usize = $percent/100 * $size;
				$set = array(
					'user_id' => $user,
					'text_id' => $work['id'],
					'size' => $usize,
					'percent' => $percent,
					//'comment' => '',
					'date' => $this->modifDate,
				);
				$qs[] = $this->olddb()->insertQ(DBT_USER_TEXT, $set, true, false);
			}
		}

		if ($this->saveFiles) {
			$path = Legacy::makeContentFilePath($work['id']);
			if (isset($work['tmpl'])) {
				File::myfile_put_contents("$this->contentDir/text/$path", String::my_replace($work['tmpl']));

				$fullText = $work['tmpl'];
				foreach ($work['text'] as $key => $textFile) {
					$entryFile = dirname("$this->contentDir/text/$path") . "/$key";
					self::copyTextFile($textFile, $entryFile);

					$fullText = str_replace("\t{file:$key}", String::my_replace(file_get_contents($textFile)), $fullText);
				}
				$tmpname = 'text.'.uniqid();
				file_put_contents($tmpname, $fullText);
				if (isset($work['toc_level'])) {
					$qs = array_merge($qs, $this->buildTextHeadersUpdateQuery($tmpname, $work['id'], $work['toc_level']));
				}
				unlink($tmpname);
			} else if (isset($work['text'])) {
				$entryFile = "$this->contentDir/text/$path";
				self::copyTextFile($work['text'], $entryFile);
				if (isset($work['toc_level'])) {
					$qs = array_merge($qs, $this->buildTextHeadersUpdateQuery($entryFile, $work['id'], $work['toc_level']));
				}
			}
			if (isset($work['info'])) {
				self::copyTextFile($work['info'], "$this->contentDir/text-info/$path");
			}
			if (isset($work['img'])) {
				$dir = "$this->contentDir/img/$path";
				if ( ! file_exists($dir)) {
					mkdir($dir, 0755, true);
				}
				`cp $work[img]/* $dir`;
				// TODO check if images are referenced from the text file
			}
		}

		return $qs;
	}


	private function insertBook(array $book)
	{
		$qs = array();

		$set = array(
			'id' => $book['id'],
		);
		if (isset($book['title'])) {
			$set += array(
				'slug' => (isset($book['slug']) ? $book['slug'] : String::slugify($book['title'])),
				'title' => String::my_replace($book['title']),
			);
		}
		if ( ! empty($book['title_extra'])) $set['title_extra'] = String::my_replace($book['title_extra']);
		if ( ! empty($book['lang'])) $set['lang'] = $book['lang'];
		if ( ! empty($book['orig_lang'])) $set['orig_lang'] = $book['orig_lang'];
		if ($book['is_new']) {
			$set += array(
				'created_at' => $this->entrydate,
				'has_anno' => 0,
				'has_cover' => 0,
				'type' => $book['type'],
				'mode' => 'public',

				'seqnr' => (isset($book['seq_nr']) ? $book['seq_nr'] : 0),
				'orig_title' => (empty($book['orig_title']) ? '' : self::fixOrigTitle($book['orig_title'])),
			);
		}
		if (isset($book['anno']))  $set['has_anno'] = 1;
		if (isset($book['cover']))  $set['has_cover'] = 1;
		if (isset($book['subtitle'])) $set['subtitle'] = String::my_replace($book['subtitle']);
		if (isset($book['year'])) $set['year'] = $book['year'];
		if (isset($book['trans_year'])) $set['trans_year'] = $book['trans_year'];

		if (isset($book['sequence'])) $set['sequence_id'] = $this->getObjectId('sequence', $book['sequence']);
		if (isset($book['category'])) $set['category_id'] = $this->getObjectId('category', $book['category']);

		if ($book['is_new']) {
			$qs[] = $this->olddb()->replaceQ(DBT_BOOK, $set);
		} else if (count($set) > 1) {
			$qs[] = $this->olddb()->updateQ(DBT_BOOK, $set, array('id' => $book['id']));
		}

		if (isset($book['revision'])) {
			$set = array(
				'id' => $book['revision_id'],
				'book_id' => $book['id'],
				'comment' => $book['revision'],
				'date' => $this->modifDate,
				'first' => ($book['is_new'] ? 1 : 0),
			);
			$qs[] = $this->olddb()->replaceQ('book_revision', $set);
		} else {
			$qs[] = "/* no revision for book $book[id] */";
		}

		if ( ! empty($book['authors'])) {
			$qs[] = $this->olddb()->deleteQ('book_author', array('book_id' => $book['id']));
			foreach ($book['authors'] as $pos => $author) {
				$set = array('person_id' => $author, 'book_id' => $book['id']);
				$qs[] = $this->olddb()->insertQ('book_author', $set, false, false);
			}
		}

		if ( ! empty($book['works'])) {
			foreach ($book['works'] as $work) {
				$set = array('book_id' => $book['id'], 'text_id' => $work['id'], 'share_info' => (int)$work['is_new']);
				$qs[] = $this->olddb()->insertQ('book_text', $set, false, false);
			}
		}

		if ($this->saveFiles) {
			$path = Legacy::makeContentFilePath($book['id']);
			if (isset($book['tmpl'])) {
				File::myfile_put_contents("$this->contentDir/book/$path", String::my_replace($book['tmpl']));
			}
			if (isset($book['anno'])) {
				self::copyTextFile($book['anno'], "$this->contentDir/book-anno/$path");
			}
			if (isset($book['info'])) {
				self::copyTextFile($book['info'], "$this->contentDir/book-info/$path");
			}
			if (isset($book['cover'])) {
				$dest = "$this->contentDir/book-cover/$path.jpg";
				File::make_parent($dest);
				`cp $book[cover] $dest`;
			}
		}

		return $qs;
	}


	static private function copyTextFile($source, $dest, $replaceChars = true)
	{
		$contents = file_get_contents($source);
		if ($replaceChars) {
			$contents = String::my_replace($contents);
		}
		File::myfile_put_contents($dest, $contents);
	}


	static public function guessTocLevel($text)
	{
		if (strpos($text, "\n>>") !== false) {
			return 2;
		} else if (strpos($text, "\n>") !== false) {
			return 1;
		}
		return 0;
	}

	static private function getFileSize($files)
	{
		$size = 0;
		if (is_array($files)) {
			foreach ($files as $file) {
				$size += strlen(file_get_contents($file));
			}
		} else {
			$size = strlen(file_get_contents($files));
		}

		return $size;
	}

	static private function fixOrigTitle($title)
	{
		return strtr($title, array(
			'\'' => '’',
		));
	}
}
