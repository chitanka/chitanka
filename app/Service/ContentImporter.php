<?php namespace App\Service;

use App\Entity\EntityManager;
use App\Service\ContentService;
use App\Service\TextService;
use App\Util\File;
use App\Util\String;
use Symfony\Component\Filesystem\Filesystem;

class ContentImporter {

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * Whether to overwrite existing files.
	 * @var bool
	 */
	private $overwrite;

	private $entrydate;
	private $modifDate;

	/**
	 * Path to the content directory
	 * @var string
	 */
	private $contentDir;

	/**
	 * Whether to save generated files.
	 * @var bool
	 */
	private $saveFiles;
	/**
	 * All processed texts
	 * @var array
	 */
	private $works;
	/**
	 * All processed books
	 * @var array
	 */
	private $books;
	/**
	 * All encountered errors
	 * @var array
	 */
	private $errors;

	private $fs;

	private $olddb;

	public function __construct(EntityManager $em, $contentDir, $saveFiles, \App\Legacy\mlDatabase $olddb) {
		$this->em = $em;
		$this->overwrite = false; // overwrite existing files?

		$this->entrydate = date('Y-m-d');
		$this->modifDate = $this->entrydate . ' ' . date('H:i:s');

		$this->contentDir = $contentDir;
		$this->saveFiles = $saveFiles;
		$this->works = $this->books = [];
		$this->errors = [];

		$this->fs = new Filesystem();

		$this->olddb = $olddb;
	}

	public function processPacket($dir) {
		$workFiles = self::sortDataFiles(glob("$dir/work.*.data"));
		$bookFiles = self::sortDataFiles(glob("$dir/book.*.data"));

		$queries = [];
		foreach ($workFiles as $workFile) {
			$workData = $this->processWorkFiles($workFile);
			$textContent = null;
			if ($this->saveFiles) {
				$textContent = $this->saveTextFiles($workData);
			}
			$queries = array_merge($queries, $this->generateSqlForTextAndCo($workData, $textContent));
		}

		foreach ($bookFiles as $bookFile) {
			$bookData = $this->processBookFiles($bookFile);
			$queries = array_merge($queries, $this->generateSqlForBookAndCo($bookData));
			if ($this->saveFiles) {
				$this->saveBookFiles($bookData);
			}
		}

		return $queries;
	}

	private function processWorkFiles($dataFile) {
		$work = [];
		foreach (file($dataFile) as $line) {
			$work += self::extractVarFromLineData($line);
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
			$authors = [];
			foreach (explode(',', $work['authors']) as $slug) {
				$authors[] = $this->getObjectId('person', $slug);
			}
			$work['authors'] = $authors;
		}
		if (isset($work['year']) && strpos($work['year'], '-') !== false) {
			list($work['year'], $work['year2']) = explode('-', $work['year']);
		}
		if (isset($work['translators'])) {
			$translators = [];
			foreach (explode(';', $work['translators']) as $slugYear) {
				list($slug, $transYear) = explode(',', $slugYear);
				if ($transYear == '?') {
					$transYear = null;
				}
				if ($slug != '?') {
					$translators[] = [$this->getObjectId('person', $slug), $transYear];
				}
				if (strpos($transYear, '-') !== false) {
					list($work['trans_year'], $work['trans_year2']) = explode('-', $transYear);
				} else {
					$work['trans_year'] = $transYear;
				}
			}
			$work['translators'] = $translators;
		} else if ($work['is_new'] && $work['lang'] != $work['orig_lang']) {
			$work['trans_license'] = 'fc';
		}
		if (isset($work['labels'])) {
			$work['labels'] = explode(',', $work['labels']);
		}
		if (isset($work['users'])) {
			if ($work['users'][0] == '*') {
				$work['users_as_new'] = true;
				$work['users'] = substr($work['users'], 1);
			}
			$users = [];
			foreach (explode(';', $work['users']) as $userContrib) {
				// username, percent, comment, date
				$parts = str_getcsv($userContrib, ',');
				if ($parts[0] == '-') {
					$parts[0] = '?';
					$parts[] = null;
				} else {
					if (strpos($parts[0], '(') !== false) {
						throw new \InvalidArgumentException("Username contains parentheses: '$parts[0]' (ID $work[id])");
					}
					try {
						$parts[] = $this->getObjectId('user', $parts[0], 'username');
					} catch (\InvalidArgumentException $e) {
						$parts[] = null;
					}
				}
				$users[] = $parts;
			}
			$work['users'] = $users;
		}
		if (file_exists($file = str_replace('.data', '.tmpl', $dataFile))) {
			$work['tmpl'] = $file;
			$work = self::prepareWorkTemplate($work);
		} else if (file_exists($file = str_replace('.data', '.text', $dataFile))) {
			$work['text'] = $file;
		}
		if (file_exists($file = str_replace('.data', '.anno', $dataFile))) {
			$work['anno'] = $file;
		}
		if (file_exists($file = str_replace('.data', '.info', $dataFile))) {
			$work['info'] = $file;
		}
		if (file_exists($dir = strtr($dataFile, ['work.' => '', '.data' => ''])) && is_dir($dir)) {
			$work['img'] = $dir;
		}
		if (!isset($work['toc_level'])) {
			$work['toc_level'] = self::guessTocLevel(file_get_contents($work['text']));
		}

		return $this->works[$packetId] = $work;
	}

	private function processBookFiles($dataFile) {
		$book = [];
		foreach (file($dataFile) as $line) {
			$book += self::extractVarFromLineData($line);
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
			$authors = [];
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
		if (file_exists($dir = strtr($dataFile, ['.data' => ''])) && is_dir($dir)) {
			$book['img'] = $dir;
		}
		if (file_exists($file = str_replace('.data', '.djvu', $dataFile))) {
			$book['djvu'] = $file;
		}
		if (file_exists($file = str_replace('.data', '.pdf', $dataFile))) {
			$book['pdf'] = $file;
		}
		if (isset($book['formats'])) {
			$book['formats'] = array_map('trim', explode(',', $book['formats']));
		} else if ($book['is_new']) {
			$book['formats'] = [];
			if (!empty($book['works'])) {
				$book['formats'][] = 'sfb';
			}
			if (!empty($book['djvu'])) {
				$book['formats'][] = 'djvu';
			}
			if (!empty($book['pdf'])) {
				$book['formats'][] = 'pdf';
			}
		}

		return $book;
	}

	private static function extractVarFromLineData($line) {
		$separator = '=';
		$parts = explode($separator, $line);
		$var = trim(array_shift($parts));
		$value = trim(implode($separator, $parts));
		if ($value === '') {
			return [];
		}
		if ($value == '-' || $value == '?') {
			$value = null;
		}
		return [$var => $value];
	}

	private static function sortDataFiles($files) {
		$sorted = [];
		foreach ($files as $file) {
			if (preg_match('/\.(\d+)\.data/', $file, $matches)) {
				$sorted[$matches[1]] = $file;
			}
		}
		ksort($sorted);

		return $sorted;
	}

	private static function getBookTemplate($file, $works) {
		$bookTmpl = file_get_contents($file);
		$bookWorks = [];
		if (preg_match_all('/\{(file|text):(\d+)/', $bookTmpl, $m)) {
			// already existing in the database works included in this book
			foreach ($m[2] as $oldWork) {
				$bookWorks[] = ['id' => $oldWork, 'is_new' => false];
			}
		}
		foreach ($works as $packetId => $work) {
			if (strpos($bookTmpl, ":$packetId") !== false) {
				$bookTmpl = strtr($bookTmpl, [
					":$packetId}" => ":$work[id]}",
					":$packetId-" => ":$work[id]-",
					":$packetId|" => ":$work[id]|",
				]);
				$bookWorks[] = $work;
			}
		}

		return [$bookTmpl, $bookWorks];
	}

	private static function prepareWorkTemplate($work) {
		$files = [];
		$template = file_get_contents($work['tmpl']);
		if (preg_match_all('/\{(text|file):-\d+(-.+)\}/', $template, $matches)) {
			foreach ($matches[2] as $match) {
				$files["$work[id]$match"] = str_replace('.tmpl', "$match.text", $work['tmpl']);
				$template = preg_replace("/(text|file):-\d+-/", "$1:$work[id]-", $template);
			}
		}
		$work['text'] = $files;
		$work['tmpl'] = $template;

		return $work;
	}

	private $objectsIds = [];

	/**
	 * @param string $table
	 * @param string $query
	 * @param string $column
	 */
	private function getObjectId($table, $query, $column = 'slug') {
		if ($column == 'slug') {
			$query = String::slugify($query);
		}
		if (!isset($this->objectsIds[$table][$query])) {
			$sql = "SELECT id FROM $table WHERE $column = '$query'";
			$result = $this->em->getConnection()->fetchAssoc($sql);
			if (empty($result['id'])) {
				throw new \InvalidArgumentException("Няма запис за $table.$column = '$query'");
			}
			$this->objectsIds[$table][$query] = $result['id'];
		}

		return $this->objectsIds[$table][$query];
	}

	private $curIds = [];
	private $ids = [
		'text' => [],
		'book' => [],
	];
	private function getNextId($table) {
		if (isset($this->ids[$table]) && count($this->ids[$table])) {
			return array_shift($this->ids[$table]);
		}
		if (!isset($this->curIds[$table])) {
			$tableClass = 'App\Entity\\'.  str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
			$this->curIds[$table] = $this->em->createQuery(sprintf('SELECT MAX(e.id) FROM %s e', $tableClass))->getSingleScalarResult() + 1;
		} else {
			$this->curIds[$table]++;
		}

		return $this->curIds[$table];
	}

	public function getNextIdUpdateQueries() {
		$tables = [
			'text_revision',
			'book_revision',
			'text_translator',
			'text_author',
			'book_author',
			'book_text',
			'series_author',
			'user_text_contrib',
		];
		$queries = [];
		foreach ($tables as $table) {
			$entityName = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
			$queries[] = "UPDATE next_id SET value=(SELECT max(id)+1 FROM $table) WHERE id = 'App\\\\Entity\\\\$entityName'";
		}
		return $queries;
	}

	private function generateSqlForTextAndCo(array $work, $textContent) {
		return array_merge(
			$this->generateSqlForText($work),
			$this->generateSqlForTextRevision($work),
			$this->generateSqlForTextAuthor($work),
			$this->generateSqlForTextTranslator($work),
			$this->generateSqlForTextLabel($work),
			$this->generateSqlForTextHeaders($work, $textContent),
			$this->generateSqlForUserTextContrib($work));
	}

	private function generateSqlForText(array $work) {
		$set = [
			'id' => $work['id'],
		];
		if (isset($work['title'])) {
			$set += [
				'slug' => (isset($work['slug']) ? String::slugify($work['slug']) : String::slugify($work['title'])),
				'title' => String::my_replace($work['title']),
			];
		}
		if (isset($work['toc_level'])) {
			$set['headlevel'] = $work['toc_level'];
		}
		if (!empty($work['type'])) {
			$set['type'] = $work['type'];
		}
		if (!empty($work['lang'])) {
			$set['lang'] = $work['lang'];
		}
		if (!empty($work['orig_lang'])) {
			$set['orig_lang'] = $work['orig_lang'];
		}
		if (isset($work['text'])) {
			$size = self::getFileSize($work['text']) / 1000;
			$set += [
				'size' => $size,
				'zsize' => ($size / 3.5),
			];
		}
		if ($work['is_new']) {
			$set += [
				'created_at' => $this->entrydate,
				'comment_count' => 0,
				'rating' => 0,
				'votes' => 0,
				'has_anno' => 0,
				'is_compilation' => isset($work['tmpl']),
				'orig_title' => (empty($work['orig_title']) ? '' : self::fixOrigTitle($work['orig_title'])),
			];
			if (isset($work['ser_nr'])) {
				$set['sernr'] = $work['ser_nr'];
			}
		}
		if (isset($work['subtitle'])) {
			$set['subtitle'] = String::my_replace($work['subtitle']);
		}
		if (isset($work['orig_subtitle'])) {
			$set['orig_subtitle'] = self::fixOrigTitle($work['orig_subtitle']);
		}
		if (isset($work['year'])) {
			$set['year'] = $work['year'];
		}
		if (isset($work['year2'])) {
			$set['year2'] = $work['year2'];
		}
		if (isset($work['trans_year'])) {
			$set['trans_year'] = $work['trans_year'];
		}
		if (isset($work['anno'])) {
			$set['has_anno'] = filesize($work['anno']) ? 1 : 0;
		}

		if (isset($work['series'])) {
			$set['series_id'] = $this->getObjectId('series', $work['series']);
		}

		if (isset($work['orig_license'])) {
			$set['orig_license_id'] = $this->getObjectId('license', $work['orig_license'], 'code');
		}
		if (isset($work['trans_license'])) {
			$set['trans_license_id'] = $this->getObjectId('license', $work['trans_license'], 'code');
		}

		if (isset($work['source'])) {
			$set['source'] = $work['source'];
		}

		if ($work['is_new']) {
			return [$this->olddb->replaceQ(DBT_TEXT, $set)];
		}
		if (count($set) > 1) {
			return [$this->olddb->updateQ(DBT_TEXT, $set, ['id' => $work['id']])];
		}
		return [];
	}

	private function generateSqlForTextRevision(array $work) {
		if (!isset($work['revision'])) {
			return [];
		}
		$set = [
			'id' => $work['revision_id'],
			'text_id' => $work['id'],
			'user_id' => 1,
			'comment' => $work['revision'],
			'date' => $this->modifDate,
			'first' => ($work['is_new'] ? 1 : 0),
		];
		return [
			$this->olddb->replaceQ(DBT_EDIT_HISTORY, $set),
			$this->olddb->updateQ(DBT_TEXT, ['cur_rev_id' => $work['revision_id']], ['id' => $work['id']]),
		];
	}

	private function generateSqlForTextAuthor(array $work) {
		if (empty($work['authors'])) {
			return [];
		}
		$sql = [$this->olddb->deleteQ(DBT_AUTHOR_OF, ['text_id' => $work['id']])];
		foreach ($work['authors'] as $pos => $author) {
			$set = [
				'id' => $this->getNextId(DBT_AUTHOR_OF),
				'person_id' => $author,
				'text_id' => $work['id'],
				'pos' => $pos,
			];
			$sql[] = $this->olddb->insertQ(DBT_AUTHOR_OF, $set, false, false);
		}
		if (isset($set['series_id'])) {
			foreach ($work['authors'] as $pos => $author) {
				$set = [
					'id' => $this->getNextId(DBT_SER_AUTHOR_OF),
					'person_id' => $author,
					'series_id' => $set['series_id'],
				];
				$sql[] = $this->olddb->insertQ(DBT_SER_AUTHOR_OF, $set, true, false);
			}
		}
		return $sql;
	}

	private function generateSqlForTextTranslator(array $work) {
		if (empty($work['translators'])) {
			return [];
		}
		$sql = [$this->olddb->deleteQ(DBT_TRANSLATOR_OF, ['text_id' => $work['id']])];
		foreach ($work['translators'] as $pos => $translator) {
			list($personId, $transYear) = $translator;
			$set = [
				'id' => $this->getNextId(DBT_TRANSLATOR_OF),
				'person_id' => $personId,
				'text_id' => $work['id'],
				'pos' => $pos,
				'year' => $transYear,
			];
			$sql[] = $this->olddb->insertQ(DBT_TRANSLATOR_OF, $set, false, false);
		}
		return $sql;
	}

	private function generateSqlForTextLabel(array $work) {
		if (empty($work['labels'])) {
			return [];
		}
		$sql = [$this->olddb->deleteQ('text_label', ['text_id' => $work['id']])];
		foreach ($work['labels'] as $label) {
			$sql[] = $this->olddb->insertQ('text_label', [
				'label_id' => $this->getObjectId('label', $label),
				'text_id' => $work['id']
			]);
		}
		return $sql;
	}

	private function generateSqlForUserTextContrib(array $work) {
		if (!isset($work['text']) || !isset($work['users'])) {
			return [];
		}
		$sql = [];
		if (isset($work['users_as_new']) && $work['users_as_new']) {
			$sql[] = $this->olddb->deleteQ(DBT_USER_TEXT, ['text_id' => $work['id']]);
		}
		foreach ($work['users'] as $user) {
			list($username, $percent, $comment, $date, $userId) = $user;
			$size = self::getFileSize($work['text']) / 1000;
			$set = [
				'id' => $this->getNextId(DBT_USER_TEXT),
				'text_id' => $work['id'],
				'size' => ($percent/100 * $size),
				'percent' => $percent,
				'comment' => $comment,
				'date' => $this->modifDate,
				'humandate' => $date,
			];
			if ($userId) {
				$set['user_id'] = $userId;
			}
			if ($username) {
				$set['username'] = $username;
			}
			$sql[] = $this->olddb->insertQ(DBT_USER_TEXT, $set, false, false);
		}
		return $sql;
	}

	private function generateSqlForTextHeaders(array $work, $textContent) {
		if (!isset($work['toc_level']) || empty($textContent)) {
			return [];
		}
		$textService = new TextService($this->olddb);
		if (isset($work['tmpl'])) {
			return $textService->buildTextHeadersUpdateQuery($textContent, $work['id'], $work['toc_level']);
		}
		if (isset($work['text'])) {
			return $textService->buildTextHeadersUpdateQuery($textContent, $work['id'], $work['toc_level']);
		}
		return [];
	}

	private function saveTextFiles(array $work) {
		$path = ContentService::makeContentFilePath($work['id']);
		$fullTextContent = '';
		if (isset($work['tmpl'])) {
			$this->fs->dumpFile("$this->contentDir/text/$path", String::my_replace($work['tmpl']));

			$fullTextContent = $work['tmpl'];
			foreach ($work['text'] as $key => $textFile) {
				$entryFile = dirname("$this->contentDir/text/$path") . "/$key";
				$this->copyTextFile($textFile, $entryFile);
				$fullTextContent = str_replace("\t{file:$key}", String::my_replace(file_get_contents($textFile)), $fullTextContent);
			}
		} else if (isset($work['text'])) {
			$entryFile = "$this->contentDir/text/$path";
			$this->copyTextFile($work['text'], $entryFile);
			$fullTextContent = $entryFile;
		}
		if (isset($work['anno'])) {
			$this->copyTextFile($work['anno'], "$this->contentDir/text-anno/$path");
		}
		if (isset($work['info'])) {
			$this->copyTextFile($work['info'], "$this->contentDir/text-info/$path");
		}
		if (isset($work['img'])) {
			$dir = "$this->contentDir/img/$path";
			if (!file_exists($dir)) {
				mkdir($dir, 0755, true);
			}
			`touch $work[img]/*`;
			$this->fs->mirror($work['img'], $dir);
			// TODO check if images are referenced from the text file
		}
		return $fullTextContent;
	}

	private function generateSqlForBookAndCo(array $book) {
		return array_merge(
			$this->generateSqlForBook($book),
			$this->generateSqlForBookRevision($book),
			$this->generateSqlForBookIsbn($book),
			$this->generateSqlForBookAuthors($book),
			$this->generateSqlForBookTexts($book));
	}

	private function generateSqlForBook(array $book) {
		$set = [
			'id' => $book['id'],
		];
		if (isset($book['title'])) {
			$set += [
				'slug' => (isset($book['slug']) ? String::slugify($book['slug']) : String::slugify($book['title'])),
				'title' => String::my_replace($book['title']),
			];
		}
		if (!empty($book['title_extra'])) {
			$set['title_extra'] = String::my_replace($book['title_extra']);
		}
		if (!empty($book['lang'])) {
			$set['lang'] = $book['lang'];
		}
		if (!empty($book['orig_lang'])) {
			$set['orig_lang'] = $book['orig_lang'];
		}
		if ($book['is_new']) {
			$set += [
				'created_at' => $this->entrydate,
				'has_anno' => 0,
				'has_cover' => 0,
			];
		}
		if (isset($book['type'])) {
			$set['type'] = $book['type'];
		}
		if (isset($book['orig_title'])) {
			$set['orig_title'] = self::fixOrigTitle($book['orig_title']);
		}
		if (isset($book['seq_nr'])) {
			$set['seqnr'] = $book['seq_nr'];
		}
		if (isset($book['anno'])) {
			$set['has_anno'] = filesize($book['anno']) ? 1 : 0;
		}
		if (isset($book['cover'])) {
			$set['has_cover'] = filesize($book['cover']) ? 1 : 0;
		}
		if (isset($book['subtitle'])) {
			$set['subtitle'] = String::my_replace($book['subtitle']);
		}
		if (isset($book['year'])) {
			$set['year'] = $book['year'];
		}
		if (isset($book['trans_year'])) {
			$set['trans_year'] = $book['trans_year'];
		}
		if (isset($book['formats'])) {
			$set['formats'] = serialize($book['formats']);
		}

		if (isset($book['sequence'])) {
			$set['sequence_id'] = $this->getObjectId('sequence', $book['sequence']);
		}
		if (isset($book['category'])) {
			$set['category_id'] = $this->getObjectId('category', $book['category']);
		}

		if ($book['is_new']) {
			return [$this->olddb->replaceQ(DBT_BOOK, $set)];
		}
		if (count($set) > 1) {
			return [$this->olddb->updateQ(DBT_BOOK, $set, ['id' => $book['id']])];
		}
		return [];
	}

	private function generateSqlForBookRevision(array $book) {
		if (!isset($book['revision'])) {
			return [];
		}
		$set = [
			'id' => $book['revision_id'],
			'book_id' => $book['id'],
			'comment' => $book['revision'],
			'date' => $this->modifDate,
			'first' => ($book['is_new'] ? 1 : 0),
		];
		return [$this->olddb->replaceQ('book_revision', $set)];
	}

	private function generateSqlForBookIsbn(array $book) {
		if (!isset($book['isbn'])) {
			return [];
		}
		$sql = [];
		foreach (explode(',', $book['isbn']) as $isbn) {
			$set = [
				'id' => $this->getNextId('book_isbn'),
				'book_id' => $book['id'],
				'code' => \App\Entity\BookIsbn::normalizeIsbn(trim($isbn)),
			];
			$sql[] = $this->olddb->replaceQ('book_isbn', $set);
		}
		return $sql;
	}

	private function generateSqlForBookAuthors(array $book) {
		if (empty($book['authors'])) {
			return [];
		}
		$sql = [$this->olddb->deleteQ('book_author', ['book_id' => $book['id']])];
		foreach ($book['authors'] as $pos => $author) {
			$set = [
				'id' => $this->getNextId('book_author'),
				'person_id' => $author,
				'book_id' => $book['id'],
			];
			$sql[] = $this->olddb->insertQ('book_author', $set, false, false);
		}
		$sql[] = $this->buildBookTitleAuthorQuery($book['id']);
		return $sql;
	}

	private function generateSqlForBookTexts(array $book) {
		if (empty($book['works'])) {
			return [];
		}
		$bookTextRepo = $this->em->getBookTextRepository();
		$sql = [];
		foreach ($book['works'] as $work) {
			$key = 'book_text'.$book['id'].'_'.$work['id'];
			if ($book['is_new'] || $work['is_new']) {
				$set = [
					'id' => $this->getNextId('book_text'),
					'book_id' => $book['id'],
					'text_id' => $work['id'],
					'share_info' => (int) $work['is_new'],
				];
				$sql[$key] = $this->olddb->insertQ('book_text', $set, false, false);
			} else {
				$relationExists = $bookTextRepo->findOneBy([
					'book' => $book['id'],
					'text' => $work['id'],
				]);
				if (!$relationExists) {
					$set = [
						'id' => $this->getNextId('book_text'),
						'book_id' => $book['id'],
						'text_id' => $work['id'],
						'share_info' => 0,
					];
					$sql[$key] = $this->olddb->insertQ('book_text', $set, false, false);
				}
			}
		}
		return $sql;
	}

	private function saveBookFiles(array $book) {
		$path = ContentService::makeContentFilePath($book['id']);
		if (isset($book['tmpl'])) {
			$this->fs->dumpFile("$this->contentDir/book/$path", String::my_replace($book['tmpl']));
		}
		if (isset($book['anno'])) {
			$this->copyTextFile($book['anno'], "$this->contentDir/book-anno/$path");
		}
		if (isset($book['info'])) {
			$this->copyTextFile($book['info'], "$this->contentDir/book-info/$path");
		}
		if (isset($book['cover'])) {
			self::copyFile($book['cover'], "$this->contentDir/book-cover/$path.jpg");
		}
		if (isset($book['djvu'])) {
			self::copyFile($book['djvu'], "$this->contentDir/book-djvu/$path");
		}
		if (isset($book['pdf'])) {
			self::copyFile($book['pdf'], "$this->contentDir/book-pdf/$path");
		}
		if (isset($book['img'])) {
			$this->fs->mirror($book['img'], "$this->contentDir/book-img/$path");
		}
	}

	private function buildBookTitleAuthorQuery($bookId) {
		return str_replace("\n", ' ', <<<QUERY
UPDATE book b
SET title_author = (
	SELECT GROUP_CONCAT(p.name SEPARATOR ', ')
	FROM book_author ba
	LEFT JOIN person p ON p.id = ba.person_id
	WHERE b.id = $bookId AND b.id = ba.book_id
	GROUP BY b.id
)
WHERE id = $bookId
QUERY
		);
	}

	private function copyTextFile($source, $dest, $replaceChars = true) {
		if (filesize($source) == 0) {
			unlink($dest);
			return;
		}
		$contents = file_get_contents($source);
		if ($replaceChars) {
			$enhancedContents = String::my_replace($contents);
			if (empty($enhancedContents)) {
				//$output->writeln(sprintf('<error>CharReplace failed by %s</error>', $source));
			} else {
				$contents = $enhancedContents;
			}
		}
		$this->fs->dumpFile($dest, $contents);
	}

	private static function copyFile($source, $dest) {
		if (is_dir($dest)) {
			$dest .= '/'.basename($source);
		}
		if (filesize($source) == 0) {
			unlink($dest);
			return;
		}
		File::make_parent($dest);
		copy($source, $dest);
		touch($dest);
	}

	/**
	 * @param string $text
	 */
	private static function guessTocLevel($text) {
		if (strpos($text, "\n>>") !== false) {
			return 2;
		}
		if (strpos($text, "\n>") !== false) {
			return 1;
		}
		return 0;
	}

	private static function getFileSize($files) {
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

	private static function fixOrigTitle($title) {
		return strtr($title, [
			'\'' => '’',
		]);
	}
}
