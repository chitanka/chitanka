<?php namespace App\Service;

use App\Entity\EntityManager;
use App\Service\TextService;
use App\Util\File;
use App\Util\String;

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

	private $olddb;

	public function __construct(EntityManager $em, $contentDir, $saveFiles, \App\Legacy\mlDatabase $olddb) {
		$this->em = $em;
		$this->overwrite = false; // overwrite existing files?

		$this->entrydate = date('Y-m-d');
		$this->modifDate = $this->entrydate . ' ' . date('H:i:s');

		$this->contentDir = $contentDir;
		$this->saveFiles = $saveFiles;
		$this->works = $this->books = array();
		$this->errors = array();

		$this->olddb = $olddb;
	}

	public function processPacket($dir) {
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

	private function processWorkFiles($dataFile) {
		$work = array();
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
				if (strpos($transYear, '-') !== false) {
					list($work['transYear'], $work['transYear2']) = explode('-', $transYear);
				} else {
					$work['transYear'] = $transYear;
				}
			}
			$work['translators'] = $translators;
		} else if ($work['is_new'] && $work['lang'] != $work['origLang']) {
			$work['transLicense'] = 'fc';
		}
		if (isset($work['labels'])) {
			$work['labels'] = explode(',', $work['labels']);
		}
		if (isset($work['users'])) {
			if ($work['users'][0] == '*') {
				$work['users_as_new'] = true;
				$work['users'] = substr($work['users'], 1);
			}
			$users = array();
			foreach (explode(';', $work['users']) as $userContrib) {
				// username, percent, comment, date
				$parts = str_getcsv($userContrib, ',');
				if ($parts[0] == '-') {
					$parts[0] = '?';
					$parts[] = null;
				} else {
					if (strpos($parts[0], '(') !== false) {
						throw new \Exception("Username contains parentheses: '$parts[0]' (ID $work[id])");
					}
					try {
						$parts[] = $this->getObjectId('user', $parts[0], 'username');
					} catch (\Exception $e) {
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
		if (file_exists($dir = strtr($dataFile, array('work.' => '', '.data' => ''))) && is_dir($dir)) {
			$work['img'] = $dir;
		}

		return $this->works[$packetId] = $work;
	}

	private function processBookFiles($dataFile) {
		$book = array();
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
		if (file_exists($dir = strtr($dataFile, array('.data' => ''))) && is_dir($dir)) {
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
			$book['formats'] = array();
			if ( ! empty($book['works'])) {
				$book['formats'][] = 'sfb';
			}
			if ( ! empty($book['djvu'])) {
				$book['formats'][] = 'djvu';
			}
			if ( ! empty($book['pdf'])) {
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
			return array();
		}
		if ($value == '-' || $value == '?') {
			$value = null;
		}
		return array($var => $value);
	}

	private static function sortDataFiles($files) {
		$sorted = array();
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
		$bookWorks = array();
		if (preg_match_all('/\{(file|text):(\d+)/', $bookTmpl, $m)) {
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
					":$packetId|" => ":$work[id]|",
				));
				$bookWorks[] = $work;
			}
		}

		return array($bookTmpl, $bookWorks);
	}

	private static function prepareWorkTemplate($work) {
		$files = array();
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

	private $_objectsIds = array();

	/**
	 * @param string $table
	 * @param string $query
	 * @param string $column
	 */
	private function getObjectId($table, $query, $column = 'slug') {
		if ($column == 'slug') {
			$query = String::slugify($query);
		}
		if ( ! isset($this->_objectsIds[$table][$query])) {
			$sql = "SELECT id FROM $table WHERE $column = '$query'";
			$result = $this->em->getConnection()->fetchAssoc($sql);
			if (empty($result['id'])) {
				throw new \Exception("Няма запис за $table.$column = '$query'");
			}
			$this->_objectsIds[$table][$query] = $result['id'];
		}

		return $this->_objectsIds[$table][$query];
	}

	private $_curIds = array();
	private $_ids = array(
		'text' => array(),
		'book' => array(),
	);
	private function getNextId($table) {
		if (isset($this->_ids[$table]) && count($this->_ids[$table])) {
			return array_shift($this->_ids[$table]);
		}
		if ( ! isset($this->_curIds[$table])) {
			$tableClass = 'App\Entity\\'.  str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
			$this->_curIds[$table] = $this->em->createQuery(sprintf('SELECT MAX(e.id) FROM %s e', $tableClass))->getSingleScalarResult() + 1;
		} else {
			$this->_curIds[$table]++;
		}

		return $this->_curIds[$table];
	}

	public function getNextIdUpdateQueries() {
		$tables = array(
			'text_revision',
			'book_revision',
			'text_translator',
			'text_author',
			'book_author',
			'book_text',
			'series_author',
		);
		$queries = array();
		foreach ($tables as $table) {
			$entityName = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
			$queries[] = "UPDATE next_id SET value=(SELECT max(id)+1 FROM $table) WHERE id LIKE '%Entity\\\\$entityName'";
		}
		return $queries;
	}

	private function insertWork(array $work) {
		$qs = array();

		$set = array(
			'id' => $work['id'],
		);
		if (isset($work['title'])) {
			$set += array(
				'slug' => (isset($work['slug']) ? String::slugify($work['slug']) : String::slugify($work['title'])),
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
		if ( ! empty($work['origLang'])) $set['orig_lang'] = $work['origLang'];
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
				'comment_count' => 0,
				'rating' => 0,
				'votes' => 0,
				'has_anno' => 0,
				'has_cover' => 0,
				'is_compilation' => isset($work['tmpl']),
				'orig_title' => (empty($work['origTitle']) ? '' : self::fixOrigTitle($work['origTitle'])),
			);
			if (isset($work['ser_nr'])) {
				$set['sernr'] = $work['ser_nr'];
			}
		}
		if (isset($work['subtitle'])) $set['subtitle'] = String::my_replace($work['subtitle']);
		if (isset($work['origSubtitle'])) $set['orig_subtitle'] = self::fixOrigTitle($work['origSubtitle']);
		if (isset($work['year'])) $set['year'] = $work['year'];
		if (isset($work['year2'])) $set['year2'] = $work['year2'];
		if (isset($work['transYear'])) $set['trans_year'] = $work['transYear'];
		if (isset($work['anno'])) $set['has_anno'] = filesize($work['anno']) ? 1 : 0;

		if (isset($work['series'])) $set['series_id'] = $this->getObjectId('series', $work['series']);

		if (isset($work['origLicense'])) $set['orig_license_id'] = $this->getObjectId('license', $work['origLicense'], 'code');
		if (isset($work['transLicense'])) $set['trans_license_id'] = $this->getObjectId('license', $work['transLicense'], 'code');

		if (isset($work['source'])) $set['source'] = $work['source'];

		if ($work['is_new']) {
			$qs[] = $this->olddb->replaceQ(DBT_TEXT, $set);
		} else if (count($set) > 1) {
			$qs[] = $this->olddb->updateQ(DBT_TEXT, $set, array('id' => $work['id']));
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
			$qs[] = $this->olddb->replaceQ(DBT_EDIT_HISTORY, $set);
			$qs[] = $this->olddb->updateQ(DBT_TEXT, array('cur_rev_id' => $work['revision_id']), array('id' => $work['id']));
		} else {
			$qs[] = "/* no revision for text $work[id] */";
		}

		if ( ! empty($work['authors'])) {
			$qs[] = $this->olddb->deleteQ(DBT_AUTHOR_OF, array('text_id' => $work['id']));
			foreach ($work['authors'] as $pos => $author) {
				$set = array(
					'id' => $this->getNextId(DBT_AUTHOR_OF),
					'person_id' => $author,
					'text_id' => $work['id'],
					'pos' => $pos,
				);
				$qs[] = $this->olddb->insertQ(DBT_AUTHOR_OF, $set, false, false);
			}
			if (isset($set['series_id'])) {
				foreach ($work['authors'] as $pos => $author) {
					$set = array(
						'id' => $this->getNextId(DBT_SER_AUTHOR_OF),
						'person_id' => $author,
						'series_id' => $set['series_id'],
					);
					$qs[] = $this->olddb->insertQ(DBT_SER_AUTHOR_OF, $set, true, false);
				}
			}
		}

		if ( ! empty($work['translators'])) {
			$qs[] = $this->olddb->deleteQ(DBT_TRANSLATOR_OF, array('text_id' => $work['id']));
			foreach ($work['translators'] as $pos => $translator) {
				list($personId, $transYear) = $translator;
				$set = array(
					'id' => $this->getNextId(DBT_TRANSLATOR_OF),
					'person_id' => $personId,
					'text_id' => $work['id'],
					'pos' => $pos,
					'year' => $transYear,
				);
				$qs[] = $this->olddb->insertQ(DBT_TRANSLATOR_OF, $set, false, false);
			}
		}

		if ( ! empty($work['labels'])) {
			$qs[] = $this->olddb->deleteQ('text_label', array('text_id' => $work['id']));
			foreach ($work['labels'] as $label) {
				$qs[] = $this->olddb->insertQ('text_label', array(
					'label_id' => $this->getObjectId('label', $label),
					'text_id' => $work['id']
				));
			}
		}

		if (isset($work['text']) && isset($work['users'])) {
			if (isset($work['users_as_new']) && $work['users_as_new']) {
				$qs[] = $this->olddb->deleteQ(DBT_USER_TEXT, array('text_id' => $work['id']));
			}
			foreach ($work['users'] as $user) {
				list($username, $percent, $comment, $date, $userId) = $user;
				$usize = $percent/100 * $size;
				$set = array(
					'id' => $this->getNextId(DBT_USER_TEXT),
					'text_id' => $work['id'],
					'size' => $usize,
					'percent' => $percent,
					'comment' => $comment,
					'date' => $this->modifDate,
					'humandate' => $date,
				);
				if ($userId) $set['user_id'] = $userId;
				if ($username) $set['username'] = $username;
				$qs[] = $this->olddb->insertQ(DBT_USER_TEXT, $set, false, false);
			}
		}

		if ($this->saveFiles) {
			$path = File::makeContentFilePath($work['id']);
			$textService = new TextService($this->olddb);
			if (isset($work['tmpl'])) {
				File::myfile_put_contents("$this->contentDir/text/$path", String::my_replace($work['tmpl']));

				$fullText = $work['tmpl'];
				foreach ($work['text'] as $key => $textFile) {
					$entryFile = dirname("$this->contentDir/text/$path") . "/$key";
					$this->copyTextFile($textFile, $entryFile);

					$fullText = str_replace("\t{file:$key}", String::my_replace(file_get_contents($textFile)), $fullText);
				}
				$tmpname = 'text.'.uniqid();
				file_put_contents($tmpname, $fullText);
				if (isset($work['toc_level'])) {
					$qs = array_merge($qs, $textService->buildTextHeadersUpdateQuery($tmpname, $work['id'], $work['toc_level']));
				}
				unlink($tmpname);
			} else if (isset($work['text'])) {
				$entryFile = "$this->contentDir/text/$path";
				$this->copyTextFile($work['text'], $entryFile);
				if (isset($work['toc_level'])) {
					$qs = array_merge($qs, $textService->buildTextHeadersUpdateQuery($entryFile, $work['id'], $work['toc_level']));
				}
			}
			if (isset($work['anno'])) {
				$this->copyTextFile($work['anno'], "$this->contentDir/text-anno/$path");
			}
			if (isset($work['info'])) {
				$this->copyTextFile($work['info'], "$this->contentDir/text-info/$path");
			}
			if (isset($work['img'])) {
				$dir = "$this->contentDir/img/$path";
				if ( ! file_exists($dir)) {
					mkdir($dir, 0755, true);
				}
				`touch $work[img]/*`;
				`cp $work[img]/* $dir`;
				// TODO check if images are referenced from the text file
			}
		}

		return $qs;
	}

	private function insertBook(array $book) {
		$qs = array();

		$set = array(
			'id' => $book['id'],
		);
		if (isset($book['title'])) {
			$set += array(
				'slug' => (isset($book['slug']) ? String::slugify($book['slug']) : String::slugify($book['title'])),
				'title' => String::my_replace($book['title']),
			);
		}
		if ( ! empty($book['titleExtra'])) $set['title_extra'] = String::my_replace($book['titleExtra']);
		if ( ! empty($book['lang'])) $set['lang'] = $book['lang'];
		if ( ! empty($book['origLang'])) $set['orig_lang'] = $book['origLang'];
		if ($book['is_new']) {
			$set += array(
				'created_at' => $this->entrydate,
				'has_anno' => 0,
				'has_cover' => 0,
			);
		}
		if (isset($book['type']))  $set['type'] = $book['type'];
		if (isset($book['origTitle'])) $set['orig_title'] = self::fixOrigTitle($book['origTitle']);
		if (isset($book['seq_nr'])) $set['seqnr'] = $book['seq_nr'];
		if (isset($book['anno'])) $set['has_anno'] = filesize($book['anno']) ? 1 : 0;
		if (isset($book['cover'])) $set['has_cover'] = filesize($book['cover']) ? 1 : 0;
		if (isset($book['subtitle'])) $set['subtitle'] = String::my_replace($book['subtitle']);
		if (isset($book['year'])) $set['year'] = $book['year'];
		if (isset($book['transYear'])) $set['trans_year'] = $book['transYear'];
		if (isset($book['formats'])) $set['formats'] = serialize($book['formats']);

		if (isset($book['sequence'])) $set['sequence_id'] = $this->getObjectId('sequence', $book['sequence']);
		if (isset($book['category'])) $set['category_id'] = $this->getObjectId('category', $book['category']);

		if ($book['is_new']) {
			$qs[] = $this->olddb->replaceQ(DBT_BOOK, $set);
		} else if (count($set) > 1) {
			$qs[] = $this->olddb->updateQ(DBT_BOOK, $set, array('id' => $book['id']));
		}

		if (isset($book['revision'])) {
			$set = array(
				'id' => $book['revision_id'],
				'book_id' => $book['id'],
				'comment' => $book['revision'],
				'date' => $this->modifDate,
				'first' => ($book['is_new'] ? 1 : 0),
			);
			$qs[] = $this->olddb->replaceQ('book_revision', $set);
		} else {
			$qs[] = "/* no revision for book $book[id] */";
		}

		if ( ! empty($book['authors'])) {
			$qs[] = $this->olddb->deleteQ('book_author', array('book_id' => $book['id']));
			foreach ($book['authors'] as $pos => $author) {
				$set = array(
					'id' => $this->getNextId('book_author'),
					'person_id' => $author,
					'book_id' => $book['id'],
				);
				$qs[] = $this->olddb->insertQ('book_author', $set, false, false);
			}
			$qs[] = $this->buildBookTitleAuthorQuery($book['id']);
		}

		if ( ! empty($book['works'])) {
			$bookTextRepo = $this->em->getBookTextRepository();
			foreach ($book['works'] as $work) {
				$key = 'book_text'.$book['id'].'_'.$work['id'];
				if ($book['is_new'] || $work['is_new']) {
					$set = array(
						'id' => $this->getNextId('book_text'),
						'book_id' => $book['id'],
						'text_id' => $work['id'],
						'share_info' => (int) $work['is_new'],
					);
					$qs[$key] = $this->olddb->insertQ('book_text', $set, false, false);
				} else {
					$relationExists = $bookTextRepo->findOneBy(array(
						'book' => $book['id'],
						'text' => $work['id'],
					));
					if (!$relationExists) {
						$set = array(
							'id' => $this->getNextId('book_text'),
							'book_id' => $book['id'],
							'text_id' => $work['id'],
							'share_info' => 0,
						);
						$qs[$key] = $this->olddb->insertQ('book_text', $set, false, false);
					}
				}
			}
		}

		if ($this->saveFiles) {
			$path = File::makeContentFilePath($book['id']);
			if (isset($book['tmpl'])) {
				File::myfile_put_contents("$this->contentDir/book/$path", String::my_replace($book['tmpl']));
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
				self::copyDir($book['img'], "$this->contentDir/book-img/$path");
			}
		}

		return $qs;
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
		File::myfile_put_contents($dest, $contents);
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

	private static function copyDir($sourceDir, $destDir) {
		if ( ! file_exists($destDir)) {
			mkdir($destDir, 0755, true);
		}
		foreach (glob("$sourceDir/*") as $source) {
			self::copyFile($source, $destDir);
		}
	}

	/**
	 * @param string $text
	 */
	private static function guessTocLevel($text) {
		if (strpos($text, "\n>>") !== false) {
			return 2;
		} else if (strpos($text, "\n>") !== false) {
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
		return strtr($title, array(
			'\'' => '’',
		));
	}
}
