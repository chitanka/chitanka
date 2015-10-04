<?php namespace App\Entity;

use App\Generator\TextFb2Generator;
use App\Generator\TextFbiGenerator;
use App\Generator\TextHtmlGenerator;
use App\Service\ContentService;
use App\Util\Char;
use App\Util\Date;
use App\Util\File;
use App\Util\Language;
use App\Util\String;
use App\Legacy\Setup;
use App\Legacy\SfbParserSimple;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Sfblib\SfbToHtmlConverter;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="App\Entity\TextRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="text",
 *   indexes={
 *      @ORM\Index(name="title_idx", columns={"title"}),
 *      @ORM\Index(name="subtitle_idx", columns={"subtitle"}),
 *      @ORM\Index(name="orig_title_idx", columns={"orig_title"}),
 *      @ORM\Index(name="orig_subtitle_idx", columns={"orig_subtitle"}),
 *      @ORM\Index(name="type_idx", columns={"type"}),
 *      @ORM\Index(name="lang_idx", columns={"lang"}),
 *      @ORM\Index(name="orig_lang_idx", columns={"orig_lang"})}
 * )
 */
class Text extends BaseWork {
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
	 * @ORM\Column(type="string", length=2)
	 */
	private $lang = 'bg';

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $transYear;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $transYear2;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $origTitle;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $origSubtitle;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=3)
	 */
	private $origLang;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=10, nullable=true)
	 */
	private $year;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $year2;

	/**
	 * @var License
	 * @ORM\ManyToOne(targetEntity="License")
	 */
	private $origLicense;

	/**
	 * @var License
	 * @ORM\ManyToOne(targetEntity="License")
	 */
	private $transLicense;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=14)
	 */
	private $type;

	/**
	 * @var Series
	 * @ORM\ManyToOne(targetEntity="Series", inversedBy="texts")
	 */
	private $series;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $sernr;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $sernr2;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $headlevel = 0;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $size;

	/**
	 * @var float
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $zsize;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="date")
	 */
	private $createdAt;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $source;

	/**
	 * @var TextRevision
	 * @ORM\ManyToOne(targetEntity="TextRevision")
	 */
	private $curRev;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $commentCount = 0;

	/**
	 * @var float
	 * @ORM\Column(type="float")
	 */
	private $rating = 0;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $votes = 0;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $hasAnno = false;

//	/*
//	 * @var bool
//	 * @ORM\Column(type="boolean")
//	 */
//	private $hasTitleNote;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $isCompilation = false;

	/**
	 * An extra note about the text
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $note;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $article;

	/**
	 * A notice if the content is removed
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $removedNotice;

	/**
	 * @var ArrayCollection|TextAuthor[]
	 * @ORM\OneToMany(targetEntity="TextAuthor", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"pos" = "ASC"})
	 */
	private $textAuthors;

	/**
	 * @var ArrayCollection|TextTranslator[]
	 * @ORM\OneToMany(targetEntity="TextTranslator", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"pos" = "ASC"})
	 */
	private $textTranslators;

	/**
	 * @var Person[]
	 */
	private $authors;

	/**
	 * @var Person[]
	 */
	private $translators;

	/**
	 * @var ArrayCollection|BookText[]
	 * @ORM\OneToMany(targetEntity="BookText", mappedBy="text")
	 */
	private $bookTexts;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @var ArrayCollection|Book[]
	 * @ORM\ManyToMany(targetEntity="Book", mappedBy="texts")
	 * @ORM\JoinTable(name="book_text",
	 *	joinColumns={@ORM\JoinColumn(name="text_id", referencedColumnName="id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id")})
	 * @ORM\OrderBy({"title" = "ASC"})
	 */
	private $books;

	/**
	 * @var ArrayCollection|Label[]
	 * @ORM\ManyToMany(targetEntity="Label", inversedBy="texts")
	 * @ORM\OrderBy({"name" = "ASC"})
	 */
	private $labels;

	/**
	 * @var ArrayCollection|TextHeader[]
	 * @ORM\OneToMany(targetEntity="TextHeader", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"nr" = "ASC", "level" = "ASC"})
	 */
	private $headers;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @var ArrayCollection|User[]
	 * @ORM\ManyToMany(targetEntity="User")
	 * @ORM\JoinTable(name="user_text_read",
	 *	joinColumns={@ORM\JoinColumn(name="text_id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="user_id")})
	 */
	private $readers;

	/**
	 * @var ArrayCollection|UserTextContrib[]
	 * @ORM\OneToMany(targetEntity="UserTextContrib", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $userContribs;

	/**
	 * @var ArrayCollection|TextRevision[]
	 * @ORM\OneToMany(targetEntity="TextRevision", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $revisions;

	/**
	 * @var ArrayCollection|TextLink[]
	 * @ORM\OneToMany(targetEntity="TextLink", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $links;

	/**
	 * @var array
	 * @ORM\Column(type="array", nullable=true)
	 */
	private $alikes;

	public function __construct($id = null) {
		$this->id = $id;
		$this->textAuthors = new ArrayCollection;
		$this->textTranslators = new ArrayCollection;
		$this->authors = [];
		$this->translators = [];
		$this->bookTexts = new ArrayCollection;
		$this->books = new ArrayCollection;
		$this->labels = new ArrayCollection;
		$this->headers = new ArrayCollection;
		$this->readers = new ArrayCollection;
		$this->userContribs = new ArrayCollection;
		$this->links = new ArrayCollection;
	}

	public function __toString() {
		return $this->getTitle();
	}

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setSubtitle($subtitle) { $this->subtitle = $subtitle; }
	public function getSubtitle() { return $this->subtitle; }

	public function setLang($lang) { $this->lang = $lang; }
	public function getLang() { return $this->lang; }

	public function setTransYear($transYear) { $this->transYear = $transYear; }
	public function getTransYear() { return $this->transYear; }

	public function setTransYear2($transYear2) { $this->transYear2 = $transYear2; }
	public function getTransYear2() { return $this->transYear2; }

	public function setOrigTitle($origTitle) { $this->origTitle = $origTitle; }
	public function getOrigTitle() { return $this->origTitle; }

	public function setOrigSubtitle($origSubtitle) { $this->origSubtitle = $origSubtitle; }
	public function getOrigSubtitle() { return $this->origSubtitle; }

	public function setOrigLang($origLang) { $this->origLang = $origLang; }
	public function getOrigLang() { return $this->origLang; }

	public function setYear($year) { $this->year = $year; }
	public function getYear() { return $this->year; }

	public function setYear2($year2) { $this->year2 = $year2; }
	public function getYear2() { return $this->year2; }

	public function setOrigLicense($origLicense) { $this->origLicense = $origLicense; }
	public function getOrigLicense() { return $this->origLicense; }

	public function getOrigLicenseCode() {
		return $this->origLicense ? $this->origLicense->getCode() : null;
	}

	public function setTransLicense($transLicense) { $this->transLicense = $transLicense; }
	public function getTransLicense() { return $this->transLicense; }

	public function getTransLicenseCode() {
		return $this->transLicense ? $this->transLicense->getCode() : null;
	}

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setSeries($series) { $this->series = $series; }
	public function getSeries() { return $this->series; }
	public function getSeriesSlug() {
		return $this->series ? $this->series->getSlug() : null;
	}

	public function setSernr($sernr) { $this->sernr = $sernr; }
	public function getSernr() { return $this->sernr; }
	public function setSernr2($sernr2) { $this->sernr2 = $sernr2; }
	public function getSernr2() { return $this->sernr2; }

	public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
	public function getCreatedAt() { return $this->createdAt; }

	public function setSource($source) { $this->source = $source; }
	public function getSource() { return $this->source; }

	public function setCurRev($curRev) { $this->curRev = $curRev; }
	public function getCurRev() { return $this->curRev; }

	public function setCommentCount($commentCount) { $this->commentCount = $commentCount; }
	public function getCommentCount() { return $this->commentCount; }

	public function setRating($rating) { $this->rating = $rating; }
	public function getRating() { return $this->rating; }

	public function setVotes($votes) { $this->votes = $votes; }
	public function getVotes() { return $this->votes; }

	/**
	 * @param bool $hasAnno
	 */
	public function setHasAnno($hasAnno) { $this->hasAnno = $hasAnno; }
	public function hasAnno() { return $this->hasAnno; }

// 	public function setHasTitleNote($hasTitleNote) { $this->hasTitleNote = $hasTitleNote; }
// 	public function getHasTitleNote() { return $this->hasTitleNote; }

	public function isCompilation() { return $this->isCompilation; }

	public function setNote($note) { $this->note = $note; }
	public function getNote() { return $this->note; }

	public function setArticle($article) { $this->article = $article; }
	public function getArticle() { return $this->article; }

	public function setRemovedNotice($removedNotice) { $this->removedNotice = $removedNotice; }
	public function getRemovedNotice() { return $this->removedNotice; }

	public function getUserContribs() { return $this->userContribs; }
	public function setUserContribs($userContribs) { $this->userContribs = $userContribs; }
	public function addUserContrib(UserTextContrib $userContrib) {
		$this->userContribs[] = $userContrib;
	}
	public function removeUserContrib(UserTextContrib $userContrib) {
		$this->userContribs->removeElement($userContrib);
	}

	public function addAuthor(Person $author) {
		$this->authors[] = $author;
	}

	/** @return Person[] */
	public function getAuthors() {
		if (!isset($this->authors)) {
			$this->authors = array_filter(array_map(function(TextAuthor $author) {
				return $author->getPos() >= 0 ? $author->getPerson() : null;
			}, $this->getTextAuthors()->toArray()));
		}
		return $this->authors;
	}

	public function addTranslator(Person $translator) {
		$this->translators[] = $translator;
	}

	public function getTranslators() {
		if (!isset($this->translators)) {
			$this->translators = array_filter(array_map(function(TextTranslator $translator) {
				return $translator->getPos() >= 0 ? $translator->getPerson() : null;
			}, $this->getTextTranslators()->toArray()));
		}
		return $this->translators;
	}

	public function addTextAuthor(TextAuthor $textAuthor) {
		$this->textAuthors[] = $textAuthor;
	}
	public function removeTextAuthor(TextAuthor $textAuthor) {
		$this->textAuthors->removeElement($textAuthor);
	}
	// TODO needed by admin; why?
	public function addTextAuthors(TextAuthor $textAuthor) { $this->addTextAuthor($textAuthor); }

	public function setTextAuthors($textAuthors) { $this->textAuthors = $textAuthors; }
	/** @return \Doctrine\Common\Collections\ArrayCollection */
	public function getTextAuthors() { return $this->textAuthors; }

	public function addTextTranslator(TextTranslator $textTranslator) {
		$this->textTranslators[] = $textTranslator;
	}
	public function removeTextTranslator(TextTranslator $textTranslator) {
		$this->textTranslators->removeElement($textTranslator);
	}
	// TODO needed by admin; why?
	public function addTextTranslators(TextTranslator $textTranslator) { $this->addTextTranslator($textTranslator); }

	public function setTextTranslators($textTranslators) { $this->textTranslators = $textTranslators; }
	/** @return \Doctrine\Common\Collections\ArrayCollection */
	public function getTextTranslators() { return $this->textTranslators; }

	public function getBooks() { return $this->books; }

	public function getRevisions() { return $this->revisions; }
	public function addRevision(TextRevision $revision) {
		$this->revisions[] = $revision;
	}

	/**
	 * @param string $comment
	 * @param User $user
	 * @param \DateTime $date
	 */
	public function addNewRevision($comment = null, User $user = null, \DateTime $date = null) {
		$revision = new TextRevision;
		$revision->setComment($comment ?: 'Добавяне');
		$revision->setText($this);
		$revision->setUser($user);
		$revision->setDate($date ?: new \DateTime);
		$isFirst = count($this->getRevisions()) == 0;
		$revision->setFirst($isFirst);
		$this->addRevision($revision);
	}

	public function setLinks($links) { $this->links = $links; }
	public function getLinks() { return $this->links; }
	public function addLink(TextLink $link) {
		$this->links[] = $link;
	}
	// needed by SonataAdmin
	public function addLinks(TextLink $link) {
		$this->addLink($link);
	}
	public function removeLink(TextLink $link) {
		$this->links->removeElement($link);
	}

	public function setAlikes($alikes) { $this->alikes = $alikes; }
	public function getAlikes() { return (array) $this->alikes; }

	private $_book;
	/**
	 * Return the main book for the text
	 */
	public function getBook() {
		if ( ! isset($this->_book)) {
			$this->_book = false;
			foreach ($this->bookTexts as $bookText) {
				if ($bookText->getShareInfo()) {
					$this->_book = $bookText->getBook();
					break;
				}
			}
		}

		return $this->_book;
	}

	public function addLabel(Label $label) { $this->labels[] = $label; }
	public function getLabels() { return $this->labels; }

	public function getLabelsByGroup($group = null) {
		$labelsByGroup = [];
		foreach ($this->getLabels() as $label) {
			$labelsByGroup[$label->getGroup()][] = $label;
		}
		if ($group === null) {
			return $labelsByGroup;
		}
		return isset($labelsByGroup[$group]) ? $labelsByGroup[$group] : [];
	}

	public function getAvailableLabels() {
		return Label::getAvailableGroups();
	}

	public function addReader(User $reader) { $this->readers[] = $reader; }
	public function getReaders() { return $this->readers; }

	protected static $annotationDir = 'text-anno';
	protected static $infoDir = 'text-info';

	public function getDocId() {
		return 'http://chitanka.info/text/' . $this->getId();
	}

	public function getYearHuman() {
		$year2 = empty($this->year2) ? '' : '–'. abs($this->year2);
		return $this->year >= 0
			? $this->year . $year2
			: abs($this->year) . $year2 .' пр.н.е.';
	}

	public function getTransYearHuman() {
		return $this->transYear . (empty($this->transYear2) ? '' : '–'.$this->transYear2);
	}

	public function getAuthorNameEscaped() {
		$origNames = implode(', ', $this->getAuthorOrigNames());
		if (preg_match('/[a-z]/', $origNames)) {
			return String::removeDiacritics(Char::cyr2lat($origNames));
		}
		return Char::cyr2lat($this->getAuthorNamesString());
	}

	public function isGamebook() {
		return $this->type == 'gamebook';
	}

	public function isTranslation() {
		return $this->lang != $this->origLang;
	}

	public function getAuthorNames() {
		return array_map(function(Person $author) {
			return $author->getName();
		}, $this->getAuthors());
	}

	public function getAuthorNamesString() {
		return implode(', ', $this->getAuthorNames());
	}

	private function getAuthorOrigNames() {
		return array_map(function(Person $author) {
			return $author->getOrigName();
		}, $this->getAuthors());
	}

	private function getAuthorSlugs() {
		return array_map(function(Person $author) {
			return $author->getSlug();
		}, $this->getAuthors());
	}

	private function getTranslatorSlugs() {
		return array_map(function(Person $translator) {
			return $translator->getSlug();
		}, $this->getTranslators());
	}

	private function getTranslatorNames() {
		return array_map(function(Person $translator) {
			return $translator->getName();
		}, $this->getTranslators());
	}

	public function getTitleAsSfb() {
		$title = "|\t" . $this->escapeForSfb($this->title);
		if ( !empty($this->subtitle) ) {
			$title .= "\n|\t" . strtr($this->escapeForSfb($this->subtitle), [
				self::TITLE_NEW_LINE => "\n|\t",
				'\n' => "\n|\t",
			]);
		}
		if ( $this->hasTitleNote() ) {
			$title .= '*';
		}
		return $title;
	}

	/**
	 * @param string $string
	 */
	private function escapeForSfb($string) {
		return strtr($string, [
			'*' => '\*',
		]);
	}

	public function hasTitleNote() {
		if ( ! is_null( $this->hasTitleNote ) ) {
			return $this->hasTitleNote;
		}

		$conv = new SfbToHtmlConverter( ContentService::getInternalContentFilePath( 'text', $this->getId() ) );
		return $this->hasTitleNote = $conv->hasTitleNote();
	}

	public function getImages() {
		return $this->getImagesFromDir(ContentService::getInternalContentFilePath('img', $this->getId()));
	}

	public function getThumbImages() {
		return $this->getImagesFromDir(ContentService::getInternalContentFilePath('img', $this->getId()) . '/thumb');
	}

	/**
	 * @param string $dir
	 */
	public function getImagesFromDir($dir) {
		$images = [];

		if (is_dir($dir) && ($dh = opendir($dir)) ) {
			while (($file = readdir($dh)) !== false) {
				$fullname = "$dir/$file";
				if ( $file[0] == '.' || /*$file[0] == '_' ||*/
						File::isArchive($file) || is_dir($fullname) ) {
					continue;
				}
				$images[] = $fullname;
			}
			closedir($dh);
		}

		return $images;
	}

	public function getFullExtraInfo() {
		return $this->getExtraInfo() . $this->getBookExtraInfo();
	}

	public function getFullExtraInfoForHtml($imgDirPrefix = '') {
		return $this->_getContentHtml($this->getFullExtraInfo(), $imgDirPrefix);
	}

	public function getAnnotationHtml($imgDirPrefix = '') {
		return $this->_getContentHtml($this->getAnnotation(), $imgDirPrefix);
	}

	/**
	 * @param string $content SFB content
	 * @param string $imgDirPrefix
	 */
	protected function _getContentHtml($content, $imgDirPrefix) {
		$imgDir = $imgDirPrefix . ContentService::getContentFilePath('img', $this->getId());
		$conv = new SfbToHtmlConverter($content, $imgDir);

		return $conv->convert()->getContent();
	}

	public function getBookExtraInfo() {
		$info = '';
		foreach ($this->bookTexts as $bookText) {
			if ($bookText->getShareInfo()) {
				$file = ContentService::getInternalContentFilePath('book-info', $bookText->getBook()->getId());
				if ( file_exists($file) ) {
					$info .= "\n\n" . file_get_contents($file);
				}
			}
		}

		return $info;
	}

	public function getPlainTranslationInfo() {
		if (!$this->isTranslation()) {
			return '';
		}
		$lang = Language::langName($this->getOrigLang(), false);
		if ($lang) {
			$lang = ' от '.$lang;
		}
		$translators = implode(', ', $this->getTranslatorNames()) ?: '[Неизвестен]';
		$year = $this->getTransYearHuman() ?: '—';

		return sprintf('Превод%s: %s, %s', $lang, $translators, $year);
	}

	public function getPlainSeriesInfo() {
		if (empty($this->series)) {
			return null;
		}
		return sprintf('Част %d от „%s“', $this->sernr, $this->series->getName());
	}

	// TODO reimplement if needed
//	public function getNextFromSeries() {
//		if ( empty($this->series) ) {
//			return false;
//		}
//		$dbkey = array('series_id' => $this->seriesId);
//		if ($this->sernr == 0) {
//			$dbkey['t.id'] = array('>', $this->getId());
//		} else {
//			$dbkey[] = 'sernr = '. ($this->sernr + 1)
//				. " OR (sernr > $this->sernr AND t.id > $this->getId())";
//		}
//		return self::newFromDB($dbkey);
//	}

	// TODO reimplement if needed
//	public function getNextFromBooks() {
//		$nextWorks = array();
//		foreach ($this->books as $id => $book) {
//			$nextWorks[$id] = $this->getNextFromBook($id);
//		}
//		return $nextWorks;
//	}

	// TODO reimplement if needed
//	public function getNextFromBook($book) {
//		if ( empty($this->books[$book]) ) {
//			return false;
//		}
//		$bookDescr = ContentService::getContentFile('book', $book);
//		if ( preg_match('/\{'. $this->getId() . '\}\n\{(\d+)\}/m', $bookDescr, $m) ) {
//			return self::newFromId($m[1]);
//		}
//		return false;
//	}

	// TODO reimplement if needed
//	public function getPrefaceOfBook($book) {
//		if ( empty($this->books[$book]) || $this->type == 'intro' ) {
//			return false;
//		}
//		$subkey = array('book_id' => $book);
//		$subquery = Setup::db()->selectQ(DBT_BOOK_TEXT, $subkey, 'text_id');
//		$dbkey = array("t.id IN ($subquery)", 't.type' => 'intro');
//		return self::newFromDB($dbkey);
//	}

	public function getHistoryAndVersion() {
		$history = [];
		$historyRows = $this->getHistoryInfo();
		$verNo = 1;
		$ver = '0';
		foreach ( $historyRows as $data ) {
			$ver = '0.' . ($verNo++);
			$history[] = "$ver ($data[date]) — $data[comment]";
		}

		return [$history, $ver];
	}

	public function getFbi() {
		$generator = new TextFbiGenerator();
		return $generator->generateFbiForText($this);
	}

	public function getDataAsPlain() {
		$authors = implode($this->getAuthorSlugs());
		$translators = implode($this->getTranslatorSlugs());
		$labels = implode($this->getLabelSlugs());

		$contributors = [];
		foreach ($this->getUserContribs() as $userContrib/*@var $userContrib UserTextContrib*/) {
			$contributors[] = implode(',', [
				$userContrib->getUsername(),
				$userContrib->getPercent(),
				'"'.$userContrib->getComment().'"',
				$userContrib->getHumandate(),
			]);
		}
		$contributors = implode(';', $contributors);

		return <<<EOS
title         = {$this->getTitle()}
subtitle      = {$this->getSubtitle()}
authors       = $authors
slug          = {$this->getSlug()}
type          = {$this->getType()}
lang          = {$this->getLang()}
year          = {$this->getYear()}
orig_license  = {$this->getOrigLicenseCode()}
orig_title    = {$this->getOrigTitle()}
orig_subtitle = {$this->getOrigSubtitle()}
orig_lang     = {$this->getOrigLang()}
translators   = $translators
trans_license = {$this->getTransLicenseCode()}
series        = {$this->getSeriesSlug()}
ser_nr        = {$this->getSernr()}
source        = {$this->getSource()}
labels        = $labels
toc_level     = {$this->getHeadlevel()}
users         = $contributors
id            = {$this->getId()}
EOS;
	}

	public function getNameForFile() {
		$filename = strtr(Setup::setting('download_file'), [
			'AUTHOR' => $this->getAuthorNameEscaped(),
			'SERIES' => empty($this->series) ? '' : String::createAcronym(Char::cyr2lat($this->series->getName())),
			'SERNO' => empty($this->sernr) ? '' : $this->sernr,
			'TITLE' => Char::cyr2lat($this->title),
			'ID' => $this->getId(),
		]);
		$filename = substr(File::cleanFileName($filename), 0, 200);

		return $filename;
	}

	public static function getMinRating() {
		return self::$minRating ?: self::$minRating = min(array_keys(self::$ratings));
	}

	public static function getMaxRating() {
		return self::$maxRating ?: self::$maxRating = max(array_keys(self::$ratings));
	}

	public static function getRatings($id) {
		return Setup::db()->getFields(DBT_TEXT,
			['id' => $id],
			['rating', 'votes']);
	}

	public function getHistoryInfo() {
		$db = Setup::db();
		$res = $db->select(DBT_EDIT_HISTORY, ['text_id' => $this->getId()], '*', 'date ASC');
		$rows = [];

		while ( $row = $db->fetchAssoc($res) ) {
			$rows[] = $row;
		}

		if ($rows) {
			$isoEntryDate = $this->createdAt->format('Y-m-d');
			if ( "$isoEntryDate 24" < $rows[0]['date'] ) {
				array_unshift($rows, ['date' => $isoEntryDate, 'comment' => 'Добавяне']);
			}
		}

		return $rows;
	}

	/**
	 * @Assert\File
	 * @var UploadedFile
	 */
	private $content_file;
	public function getContentFile() {
		return $this->content_file;
	}

	/** @param UploadedFile $file */
	public function setContentFile(UploadedFile $file = null) {
		$this->content_file = $file;
		if ($file) {
			$this->setSize($file->getSize() / 1000);
			$this->rebuildHeaders($file->getRealPath());
		}
	}

	public function isContentFileUpdated() {
		return $this->getContentFile() !== null;
	}

	public function setHeadlevel($headlevel) {
		$this->headlevel = $headlevel;
		if ( !$this->isContentFileUpdated()) {
			$this->rebuildHeaders();
		}
	}
	public function getHeadlevel() { return $this->headlevel; }

	/**
	 * @param int $size
	 */
	public function setSize($size) {
		$this->size = $size;
		$this->setZsize($size / 3.5);
	}
	public function getSize() { return $this->size; }

	/**
	 * @param float $zsize
	 */
	public function setZsize($zsize) { $this->zsize = $zsize; }
	public function getZsize() { return $this->zsize; }

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
		$this->moveUploadedContentFile($this->getContentFile());
	}

	private function moveUploadedContentFile(UploadedFile $file = null) {
		if ($file) {
			$filename = ContentService::getContentFilePath('text', $this->getId());
			$file->move(dirname($filename), basename($filename));
		}
	}

	private $revisionComment;
	public function getRevisionComment() {
		return $this->revisionComment;
	}

	public function setRevisionComment($comment) {
		$this->revisionComment = $comment;
	}

	public function getContentAsSfb() {
		$sfb = $this->getFullTitleAsSfb() . "\n\n\n";

		$anno = $this->getAnnotation();
		if ( ! empty($anno) ) {
			$sfb .= "A>\n$anno\nA$\n\n";
		}
		$sfb .= $this->getRawContent();
		$extra = $this->getExtraInfoForDownload();
		$extra = preg_replace('/\n\n+/', "\n\n", $extra);
		$sfb .= "\nI>\n".trim($extra, "\n")."\nI$\n";

		return $sfb;
	}

	public function getRawContent($asFileName = false) {
		if ( ! $this->isCompilation) {
			if ($asFileName) {
				return ContentService::getContentFilePath('text', $this->getId());
			} else {
				return ContentService::getContentFile('text', $this->getId());
			}
		}

		$template = ContentService::getContentFile('text', $this->getId());
		if (preg_match_all('/(.*)\t\{file:(\d+-.+)\}/', $template, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				list($row, $command, $filename) = $match;
				$content = ContentService::getContentFile('text', $filename);
				if (strlen($command) > 1) {
					$normalizedCommand = substr($command, 1);
					$content = Content\BookTemplate::replaceSfbHeadings($content, $normalizedCommand);
				}
				$template = str_replace($row, $content, $template);
			}
		}
		// TODO cache the full output

		return $template;
	}

	public function getFullTitleAsSfb() {
		$sfb = '';
		$sfb .= "|\t" . ($this->getAuthorNamesString() ?: '(неизвестен автор)') . "\n";
		$sfb .= $this->getTitleAsSfb();

		return $sfb;
	}

	public function getExtraInfoForDownload() {
		$info = "\t\$source = Моята библиотека\n"
			. "\t\$id = {$this->getId()}\n";
		foreach ($this->getBooks() as $book) {
			$info .= "\t\$book_id = {$book->getId()}\n";
		}
		$info .= "\n" . $this->getFullExtraInfo();
		return $info;
	}

	public function getContentAsFb2() {
		$generator = new TextFb2Generator();
		return $generator->generateFb2($this);
	}

	public function getLabelsNames() {
		$names = [];
		foreach ($this->getLabels() as $label) {
			$names[] = $label->getName();
		}
		return $names;
	}

	public function getLabelSlugs() {
		$slugs = [];
		foreach ($this->getLabels() as $label) {
			$slugs[] = $label->getSlug();
		}
		return $slugs;
	}

	public function getHeaders() {
		return $this->headers;
	}
	public function setHeaders(ArrayCollection $headers) {
		$this->headers = $headers;
	}
	public function addHeader(TextHeader $header) {
		$this->headers[] = $header;
	}

	public function clearHeaders() {
		Entity::clearCollection($this->getHeaders());
	}

	/**
	 * @param string $file
	 */
	public function rebuildHeaders($file = null) {
		if ($file === null) $file = ContentService::getContentFilePath('text', $this->getId());
		$headlevel = $this->getHeadlevel();

		$this->clearHeaders();

		$parser = new SfbParserSimple($file, $headlevel);
		$parser->convert();
		foreach ($parser->headersFlat() as $headerData) {
			$header = new TextHeader;
			$header->setNr($headerData['nr']);
			$header->setLevel($headerData['level']);
			$header->setLinecnt($headerData['line_count']);
			$header->setName($headerData['title']);
			$header->setFpos($headerData['file_pos']);
			$header->setText($this);
			$this->addHeader($header);
		}
	}

	public function getEpubChunks($imgDir) {
		return $this->getEpubChunksFrom($this->getRawContent(true), $imgDir);
	}

	public function getContentHtml($imgDirPrefix = '', $part = 1, $objCount = 0) {
		$generator = new TextHtmlGenerator();
		return $generator->generateHtml($this, $imgDirPrefix, $part, $objCount);
	}

	/**
	 * @param int $nr
	 */
	public function getHeaderByNr($nr) {
		foreach ($this->getHeaders() as $header) {
			if ($header->getNr() == $nr) {
				return $header;
			}
		}

		return null;
	}

	/**
	 * @param int $nr
	 */
	public function getNextHeaderByNr($nr) {
		if ($nr > 0) {
			foreach ($this->getHeaders() as $header) {
				if ($header->getNr() == $nr + 1) {
					return $header;
				}
			}
		}

		return null;
	}

	public function getTotalRating() {
		return $this->rating * $this->votes;
	}

	/**
	 * Update average rating
	 *
	 * @param int $newRating Newly given rating
	 * @param int $oldRating An old rating which should be overwritten by the new one
	 * @return Text
	 */
	public function updateAvgRating($newRating, $oldRating = null) {
		if ( is_null($oldRating) ) {
			$this->rating = ($this->getTotalRating() + $newRating) / ($this->votes + 1);
			$this->votes += 1;
		} else {
			$this->rating = ($this->getTotalRating() - $oldRating + $newRating) / $this->votes;
		}

		return $this;
	}

	public function getMainContentFile() {
		return ContentService::getContentFilePath('text', $this->getId());
	}

}
