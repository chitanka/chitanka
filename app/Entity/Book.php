<?php namespace App\Entity;

use App\Generator\BookFb2Generator;
use App\Service\ContentService;
use App\Util\Ary;
use App\Util\File;
use App\Util\String;
use Sfblib\SfbConverter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
* @ORM\Entity(repositoryClass="App\Entity\BookRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="book",
*	indexes={
*		@ORM\Index(name="title_idx", columns={"title"}),
*		@ORM\Index(name="title_author_idx", columns={"title_author"}),
*		@ORM\Index(name="subtitle_idx", columns={"subtitle"}),
*		@ORM\Index(name="orig_title_idx", columns={"orig_title"})}
* )
*/
class Book extends BaseWork {

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	protected $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50)
	 */
	private $slug;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $titleAuthor;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $title = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $subtitle;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $titleExtra;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $origTitle;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=2)
	 */
	private $lang;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=3, nullable=true)
	 */
	private $origLang;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $year;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $transYear;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=10)
	 */
	private $type;
	private static $typeList = [
		'single' => 'Обикновена книга',
		'collection' => 'Сборник',
		'poetry' => 'Стихосбирка',
		'anthology' => 'Антология',
		'magazine' => 'Списание',
	];

	/**
	 * @var Sequence
	 * @ORM\ManyToOne(targetEntity="Sequence", inversedBy="books")
	 */
	private $sequence;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $seqnr;

	/**
	 * @var Category
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="books")
	 */
	private $category;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $hasAnno;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $hasCover;

	/**
	 * List of formats in which the book is available
	 * @var array
	 * @ORM\Column(type="array")
	 */
	private $formats = [];

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookRevision", mappedBy="book", cascade={"persist"})
	 */
	private $revisions;

	/**
	 * A notice if the content is removed
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $removedNotice;

	/*
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="Person", inversedBy="books")
	 * @ORM\JoinTable(name="book_author")
	 */
	private $authors;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookAuthor", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"pos" = "ASC"})
	 */
	private $bookAuthors;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookText", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $bookTexts;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="Text", inversedBy="books")
	 * @ORM\JoinTable(name="book_text",
	 *	joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="text_id", referencedColumnName="id")})
	 */
	private $texts;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookLink", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $links;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookIsbn", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $isbns;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="date")
	 */
	private $createdAt;

	public function __construct() {
		$this->bookAuthors = new ArrayCollection;
		$this->bookTexts = new ArrayCollection;
		$this->links = new ArrayCollection;
	}

	/**
	 * @ORM\PrePersist()
	 */
	public function onPreInsert() {
		$this->setCreatedAt(new \DateTime());
	}

	/**
	 * @ORM\PostPersist()
	 * @ORM\PostUpdate()
	 */
	public function onPostSave() {
		$this->persistAnnotation();
		$this->persistExtraInfo();
	}

	public function __toString() {
		return $this->title;
	}

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setTitleAuthor($titleAuthor) { $this->titleAuthor = $titleAuthor; }
	public function getTitleAuthor() { return $this->titleAuthor; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setSubtitle($subtitle) { $this->subtitle = $subtitle; }
	public function getSubtitle() { return $this->subtitle; }

	public function setTitleExtra($title) { $this->titleExtra = $title; }
	public function getTitleExtra() { return $this->titleExtra; }

	public function setOrigTitle($origTitle) { $this->origTitle = $origTitle; }
	public function getOrigTitle() { return $this->origTitle; }

	public function setLang($lang) { $this->lang = $lang; }
	public function getLang() { return $this->lang; }

	public function setOrigLang($origLang) { $this->origLang = $origLang; }
	public function getOrigLang() { return $this->origLang; }

	public function setYear($year) { $this->year = $year; }
	public function getYear() { return $this->year; }

	public function setTransYear($transYear) { $this->transYear = $transYear; }
	public function getTransYear() { return $this->transYear; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setFormats($formats) { $this->formats = $formats; }
	public function getFormats() { return $this->formats; }
	public function isInSfbFormat() {
		return in_array('sfb', $this->formats);
	}

	public function getRevisions() { return $this->revisions; }
	public function addRevision(BookRevision $revision) {
		$this->revisions[] = $revision;
	}

	public function setRemovedNotice($removedNotice) { $this->removedNotice = $removedNotice; }
	public function getRemovedNotice() { return $this->removedNotice; }

	public function getAuthors() {
		if (!isset($this->authors)) {
			$this->authors = [];
			foreach ($this->getBookAuthors() as $author) {
				if ($author->getPos() >= 0) {
					$this->authors[] = $author->getPerson();
				}
			}
		}
		return $this->authors;
	}

	public function getAuthorNamesString() {
		$authors = [];
		foreach ($this->getAuthors() as $author) {
			$authors[] = $author->getName();
		}

		return implode(', ', $authors);
	}

	public function addAuthor($author) {
		$this->authors[] = $author;
	}

	public function addBookAuthor(BookAuthor $bookAuthor) {
		$this->bookAuthors[] = $bookAuthor;
	}
	public function removeBookAuthor(BookAuthor $bookAuthor) {
		$this->bookAuthors->removeElement($bookAuthor);
	}
	// TODO needed by admin; why?
	public function addBookAuthors(BookAuthor $bookAuthor) { $this->addBookAuthor($bookAuthor); }

	public function setBookAuthors($bookAuthors) { $this->bookAuthors = $bookAuthors; }
	public function getBookAuthors() { return $this->bookAuthors; }

	public function getBookTexts() { return $this->bookTexts; }
	public function setBookTexts($bookTexts) { $this->bookTexts = $bookTexts; }
	public function addBookText(BookText $bookText) {
		$this->bookTexts[] = $bookText;
	}

	public function getTexts() { return $this->texts; }
	public function setTexts($texts) {
		$bookTexts = $this->getBookTexts();
		foreach ($texts as $key => $text) {
			$bookText = $bookTexts->get($key);
			if ($bookText === null) {
				$bookText = new BookText;
				$bookText->setBook($this);
				$bookText->setShareInfo(true);
				$this->addBookText($bookText);
			}
			$bookText->setText($text);
			$bookText->setPos($key);
		}
		$keysToRemove = array_diff($bookTexts->getKeys(), array_keys($texts));
		foreach ($keysToRemove as $key) {
			$bookTexts->remove($key);
		}
		$this->textsNeedUpdate = false;
	}

	private $textsNeedUpdate = false;
	public function textsNeedUpdate() {
		return $this->textsNeedUpdate;
	}

	public function setIsbns($isbns) { $this->isbns = $isbns; }
	public function getIsbns() { return $this->isbns; }
	public function addIsbn(BookIsbn $isbn) {
		$this->isbns[] = $isbn;
	}
	public function removeIsbn(BookIsbn $isbn) {
		$this->isbns->removeElement($isbn);
	}

	public function setLinks($links) { $this->links = $links; }
	public function getLinks() { return $this->links; }
	public function addLink(BookLink $link) {
		$this->links[] = $link;
	}
	public function removeLink(BookLink $link) {
		$this->links->removeElement($link);
	}
	// TODO needed by admin; why?
	public function addLinks(BookLink $link) { $this->addLink($link); }

	/** @param bool $hasAnno */
	public function setHasAnno($hasAnno) { $this->hasAnno = $hasAnno; }
	public function hasAnno() { return $this->hasAnno; }

	public function setHasCover($hasCover) { $this->hasCover = $hasCover; }
	public function hasCover() { return $this->hasCover; }

	public function setSequence($sequence) { $this->sequence = $sequence; }
	public function getSequence() { return $this->sequence; }
	public function getSequenceSlug() {
		return $this->sequence ? $this->sequence->getSlug() : null;
	}

	public function setSeqnr($seqnr) { $this->seqnr = $seqnr; }
	public function getSeqnr() { return $this->seqnr; }

	public function setCategory($category) { $this->category = $category; }
	public function getCategory() { return $this->category; }
	public function getCategorySlug() {
		return $this->category ? $this->category->getSlug() : null;
	}

	public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
	public function getCreatedAt() { return $this->createdAt; }

	public function getSfbg() {
		return $this->getLink('SFBG');
	}

	public function getPuk() {
		return $this->getLink('ПУК!');
	}

	public function getLink($name) {
		$links = $this->getLinks();
		foreach ($links as $link) {
			if ($link->getSiteName() == $name) {
				return $link;
			}
		}

		return null;
	}

	private $textIds = [];
	private $textsById = [];

	protected static $annotationDir = 'book-anno';
	protected static $infoDir = 'book-info';
	protected $covers = [];

	public function getDocId() {
		return 'http://chitanka.info/book/' . $this->id;
	}

	public function getAuthor() {
		return $this->titleAuthor;
	}

	private $mainAuthors;
	/** @return Person[] */
	public function getMainAuthors() {
		if ( ! isset($this->mainAuthors) ) {
			$this->mainAuthors = [];
			foreach ($this->getTextsById() as $text) {
				if ( self::isMainWorkType($text->getType()) ) {
					foreach ($text->getAuthors() as $author) {
						$this->mainAuthors[$author->getId()] = $author;
					}
				}
			}
		}

		return $this->mainAuthors;
	}

	public function getAuthorSlugs() {
		$slugs = [];
		foreach ($this->getAuthors() as $author/*@var $author Person*/) {
			$slugs[] = $author->getSlug();
		}
		return $slugs;
	}

	public static function isMainWorkType($type) {
		return ! in_array($type, ['intro', 'outro'/*, 'interview', 'article'*/]);
	}

	private $authorsBy = [];
	public function getAuthorsBy($type) {
		if ( ! isset($this->authorsBy[$type]) ) {
			$this->authorsBy[$type] = [];
			foreach ($this->getTextsById() as $text) {
				if ($text->getType() == $type) {
					foreach ($text->getAuthors() as $author) {
						$this->authorsBy[$type][$author->getId()] = $author;
					}
				}
			}
		}

		return $this->authorsBy[$type];
	}

	private $translators;
	public function getTranslators() {
		if ( ! isset($this->translators) ) {
			$this->translators = [];
			$seen = [];
			foreach ($this->getTexts() as $text) {
				foreach ($text->getTranslators() as $translator) {
					if ( ! in_array($translator->getId(), $seen) ) {
						$this->translators[] = $translator;
						$seen[] = $translator->getId();
					}
				}
			}
		}

		return $this->translators;
	}

	public function withAutohide() {
		return $this->getTemplate()->hasAutohide();
	}

	public function getTemplateAsXhtml() {
		return $this->getTemplate()->getAsXhtml();
	}

	private $template;
	/** @return Content\BookTemplate */
	public function getTemplate() {
		return $this->template ?: $this->template = new Content\BookTemplate($this);
	}

	public function getRawTemplate() {
		return $this->getTemplate()->getContent();
	}

	public function setRawTemplate($template) {
		$this->getTemplate()->setContent($template);
		$this->textsNeedUpdate = true;
	}

	public function getTextIdsFromTemplate() {
		return $this->getTemplate()->getTextIds();
	}

	public function getCover($width = null) {
		$this->initCovers();
		return is_null($width) ? $this->covers['front'] : File::genThumbnail($this->covers['front'], $width);
	}

	public function getBackCover($width = null) {
		$this->initCovers();
		return is_null($width) ? $this->covers['back'] : File::genThumbnail($this->covers['back'], $width);
	}

	private static $exts = ['.jpg'];

	private function initCovers() {
		if (empty($this->covers)) {
			$this->covers['front'] = $this->covers['back'] = null;

			$covers = self::getCovers($this->id);
			if ($covers) {
				$this->covers['front'] = $covers[0];
				$back = preg_replace('/(.+)\.(\w+)$/', '$1-back.$2', $this->covers['front']);
				if (file_exists($back)) {
					$this->covers['back'] = $back;
				}
			}
		}
	}

	/**
	 * @param $id Text or book ID
	 * @param $defCover Default covers if there aren’t any for $id
	 */
	public static function getCovers($id, $defCover = null) {
		$key = 'book-cover-content';
		$bases = [ContentService::getContentFilePath($key, $id)];
		if ( ! empty($defCover)) {
			$bases[] = ContentService::getContentFilePath($key, $defCover);
		}
		$coverFiles = Ary::cartesianProduct($bases, self::$exts);
		$covers = [];
		foreach ($coverFiles as $file) {
			if (file_exists($file)) {
				$covers[] = $file;
				// search for more images of the form “ID-DIGIT.EXT”
				for ($i = 2; /* infinity */; $i++) {
					$efile = strtr($file, ['.' => "-$i."]);
					if (file_exists($efile)) {
						$covers[] = $efile;
					} else {
						break;
					}
				}
				break; // don’t check other extensions
			}
		}
		return $covers;
	}

	public static function renameCover($cover, $newname) {
		$rexts = strtr(implode('|', self::$exts), ['.'=>'\.']);
		return preg_replace("/\d+(-\d+)?($rexts)/", "$newname$1$2", $cover);
	}

	public function getImages() {
		return array_merge($this->getLocalImages(), $this->getTextImages());
	}

	public function getThumbImages() {
		return $this->getTextThumbImages();
	}

	public function getLocalImages() {
		$images = [];

		$dir = ContentService::getInternalContentFilePath('book-img', $this->id);
		foreach (glob("$dir/*") as $img) {
			$images[] = $img;
		}

		return $images;
	}

	public function getTextImages() {
		$images = [];

		foreach ($this->getTexts() as $text) {
			$images = array_merge($images, $text->getImages());
		}

		return $images;
	}

	public function getTextThumbImages() {
		$images = [];

		foreach ($this->getTexts() as $text) {
			$images = array_merge($images, $text->getThumbImages());
		}

		return $images;
	}

	public function getLabels() {
		$labels = [];

		foreach ($this->getTexts() as $text) {
			foreach ($text->getLabels() as $label) {
				$labels[] = $label->getName();
			}
		}

		$labels = array_unique($labels);

		return $labels;
	}

	public function getContentAsSfb() {
		if (!$this->isInSfbFormat()) {
			return null;
		}
		return $this->getTitleAsSfb() . SfbConverter::EOL
			. $this->getAllAnnotationsAsSfb()
			. $this->getMainBodyAsSfb()
			. $this->getInfoAsSfb();
	}

	public function getMainBodyAsSfb() {
		return $this->getTemplate()->generateSfb();
	}

	private $_mainBodyAsSfbFile;
	public function getMainBodyAsSfbFile() {
		if ( isset($this->_mainBodyAsSfbFile) ) {
			return $this->_mainBodyAsSfbFile;
		}

		$this->_mainBodyAsSfbFile = tempnam(sys_get_temp_dir(), 'book-');
		file_put_contents($this->_mainBodyAsSfbFile, $this->getMainBodyAsSfb());

		return $this->_mainBodyAsSfbFile;
	}

	/**
	 * Return the author of a text if he/she is not on the book title
	 */
	public function getBookAuthorIfNotInTitle($text) {
		$bookAuthorsIds = $this->getAuthorIds();
		$authors = [];
		foreach ($text->getAuthors() as $author) {
			if ( ! in_array($author->getId(), $bookAuthorsIds)) {
				$authors[] = $author;
			}
		}

		return $authors;
	}

	public function getTitleAsSfb() {
		$sfb = '';
		$prefix = SfbConverter::HEADER . SfbConverter::CMD_DELIM;

		if ('' != $authors = $this->getAuthorNamesString()) {
			$sfb .= $prefix . $authors . SfbConverter::EOL;
		}

		$sfb .= $prefix . $this->title . SfbConverter::EOL;

		if ( ! empty($this->subtitle) ) {
			$sfb .= $prefix . $this->subtitle . SfbConverter::EOL;
		}

		return $sfb;
	}

	public function getAllAnnotationsAsSfb() {
		return $this->getAnnotationAsSfb();
	}

	public function getAnnotationAsXhtml($imgDir = null) {
		if ($imgDir === null) {
			$imgDir = 'IMG_DIR_PREFIX' . ContentService::getContentFilePath('book-img', $this->id);
		}
		return parent::getAnnotationAsXhtml($imgDir);
	}

	public function getInfoAsSfb() {
		return SfbConverter::INFO_S . SfbConverter::EOL
			. SfbConverter::CMD_DELIM . "\$id = {$this->getId()}" . SfbConverter::EOL
			. SfbConverter::CMD_DELIM . "\$source = Моята библиотека" . SfbConverter::EOL
			. rtrim($this->getExtraInfo()) . SfbConverter::EOL
			. SfbConverter::INFO_E . SfbConverter::EOL;
	}

	public function getContentAsFb2() {
		$generator = new BookFb2Generator();
		return $generator->generateFb2($this);
	}

	private $_headers;
	public function getHeaders() {
		if ( isset($this->_headers) ) {
			return $this->_headers;
		}

		require_once __DIR__ . '/../Legacy/SfbParserSimple.php';
		$this->_headers = [];
		foreach (\App\Legacy\makeDbRows($this->getMainBodyAsSfbFile(), 4) as $row) {
			$header = new TextHeader;
			$header->setNr($row[0]);
			$header->setLevel($row[1]);
			$header->setName($row[2]);
			$header->setFpos($row[3]);
			$header->setLinecnt($row[4]);
			$this->_headers[] = $header;
		}

		return $this->_headers;
	}

	public function getEpubChunks($imgDir) {
		return $this->getEpubChunksFrom($this->getMainBodyAsSfbFile(), $imgDir);
	}

	public function initTmpImagesDir() {
		$dir = sys_get_temp_dir() . '/' . uniqid();
		mkdir($dir);
		foreach ($this->getImages() as $image) {
			copy($image, $dir.'/'.basename($image));
		}

		return $dir;
	}

	public function getNameForFile() {
		return trim("$this->titleAuthor - $this->title - $this->subtitle-$this->id-b", '- ');
	}

	public function getTextIds() {
		if ( empty($this->textIds) ) {
			preg_match_all('/\{(text|file):(\d+)/', $this->getTemplate()->getContent(), $matches);
			$this->textIds = $matches[2];
		}

		return $this->textIds;
	}

	public function getTextById($textId) {
		$texts = $this->getTextsById();
		return isset($texts[$textId]) ? $texts[$textId] : null;
	}

	public function getTextsById() {
		if ( empty($this->textsById) ) {
			foreach ($this->getTextIds() as $id) {
				$this->textsById[$id] = null;
			}
			foreach ($this->getTexts() as $text) {
				$this->textsById[$text->getId()] = $text;
			}
			foreach ($this->textsById as $id => $text) {
				if (is_null($text)) {
					$text = new Text($id);
					$this->textsById[$id] = $text;
				}
			}
		}

		return $this->textsById;
	}

	public function isGamebook() {
		return false;
	}

	public function isFromSameAuthor($text) {
		return $this->getAuthorIds() == $text->getAuthorIds();
	}

	/** TODO set for a books with only one novel */
	public function getPlainSeriesInfo() {
		return '';
	}

	public function getPlainTranslationInfo() {
		$info = [];
		foreach ($this->getTranslators() as $translator) {
			$info[] = $translator->getName();
		}

		return sprintf('Превод: %s', implode(', ', $info));
	}

	public function getDataAsPlain() {
		$authors = implode($this->getAuthorSlugs());

		return <<<EOS
title       = {$this->getTitle()}
subtitle    = {$this->getSubtitle()}
title_extra = {$this->getTitleExtra()}
authors     = $authors
slug        = {$this->getSlug()}
lang        = {$this->getLang()}
orig_title  = {$this->getOrigTitle()}
orig_lang   = {$this->getOrigLang()}
year        = {$this->getYear()}
sequence    = {$this->getSequenceSlug()}
seq_nr      = {$this->getSeqnr()}
category    = {$this->getCategorySlug()}
type        = {$this->getType()}
id          = {$this->getId()}
EOS;
	}

	public function getDatafiles() {
		$files = [];
		$files['book'] = ContentService::getContentFilePath('book', $this->id);
		if ($this->hasCover()) {
			$files['book-cover'] = ContentService::getContentFilePath('book-cover', $this->id) . '.max.jpg';
		}
		if ($this->hasAnno()) {
			$files['book-anno'] = ContentService::getContentFilePath('book-anno', $this->id);
		}
		$files['book-info'] = ContentService::getContentFilePath('book-info', $this->id);

		return $files;
	}
	public function setDatafiles($f) {} // dummy for sonata admin

	public function getStaticFile($format) {
		if (!in_array($format, ['djvu', 'pdf'])) {
			throw new \Exception("Format $format is not a valid static format for a book.");
		}
		return ContentService::getContentFilePath('book-'.$format, $this->id);
	}

	##################
	# legacy pic stuff
	##################

	const THUMB_DIR = 'thumb';
	const THUMBS_FILE_TPL = 'thumbs-%d.jpg';
	const MAX_JOINED_THUMBS = 50;

	private $_files;
	public function getFiles() {
		if ( isset($this->_files) ) {
			return $this->_files;
		}

		$dir = ContentService::getContentFilePath('book-pic', $this->id);

		$ignore = [self::THUMB_DIR];

		$files = [];
		foreach (scandir($dir) as $file) {
			if ( $file[0] == '.' || in_array($file, $ignore) ) {
				continue;
			}
			$files[] = $file;
		}

		sort($files);

		return $this->_files = $files;
	}

	private $_imageDir;
	public function getImageDir() {
		return $this->_imageDir ?: $this->_imageDir = ContentService::getContentFilePath('book-pic', $this->id);
	}

	private $_thumbDir;
	public function getThumbDir() {
		return $this->_thumbDir ?: $this->_thumbDir = $this->getImageDir() .'/'. self::THUMB_DIR;
	}

	public function getWebImageDir() {
		return $this->getImageDir();
	}

	public function getWebThumbDir() {
		return $this->getThumbDir();
	}

	public function getThumbFile($currentPage) {
		$currentJoinedFile = floor($currentPage / self::MAX_JOINED_THUMBS);

		return sprintf(self::THUMBS_FILE_TPL, $currentJoinedFile);
	}

	public function getThumbClass($currentPage) {
		return 'th' . ($currentPage % self::MAX_JOINED_THUMBS);
	}

//	public function getSiblings() {
//		if ( isset($this->_siblings) ) {
//			return $this->_siblings;
//		}
//
//		$qa = array(
//			'SELECT' => 'p.*, s.name seriesName, s.type seriesType',
//			'FROM' => DBT_PIC .' p',
//			'LEFT JOIN' => array(
//				DBT_PIC_SERIES .' s' => 'p.series = s.id'
//			),
//			'WHERE' => array(
//				'series' => $this->series,
//				'p.series' => array('>', 0),
//			),
//			'ORDER BY' => 'sernr ASC'
//		);
//		$db = Setup::db();
//		$res = $db->extselect($qa);
//		$siblings = array();
//		while ( $row = $db->fetchAssoc($res) ) {
//			$siblings[ $row['id'] ] = new PicWork($row);
//		}
//
//		return $this->_siblings = $siblings;
//	}

//	public function getNextSibling() {
//		if ( empty($this->series) ) {
//			return false;
//		}
//		$dbkey = array('series' => $this->series);
//		if ($this->sernr == 0) {
//			$dbkey['p.id'] = array('>', $this->id);
//		} else {
//			$dbkey[] = 'sernr = '. ($this->sernr + 1)
//				. " OR (sernr > $this->sernr AND p.id > $this->id)";
//		}
//		return self::newFromDB($dbkey);
//	}

//	public function sameAs($otherPic) {
//		return $this->id == $otherPic->id;
//	}

	public static function getTypeList() {
		return self::$typeList;
	}

	private $revisionComment;
	public function getRevisionComment() {
		return $this->revisionComment;
	}

	public function setRevisionComment($comment) {
		$this->revisionComment = $comment;
	}

}
