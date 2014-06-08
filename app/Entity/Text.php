<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Generator\TextFb2Generator;
use App\Generator\TextFbiGenerator;
use App\Generator\TextHtmlGenerator;
use App\Util\Char;
use App\Util\File;
use App\Util\Language;
use App\Util\String;
use App\Legacy\Legacy;
use App\Legacy\Setup;
use App\Legacy\SfbParserSimple;
use Sfblib\SfbConverter;
use Sfblib\SfbToHtmlConverter;

/**
* @ORM\Entity(repositoryClass="App\Entity\TextRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="text",
*	indexes={
*		@ORM\Index(name="title_idx", columns={"title"}),
*		@ORM\Index(name="subtitle_idx", columns={"subtitle"}),
*		@ORM\Index(name="orig_title_idx", columns={"orig_title"}),
*		@ORM\Index(name="orig_subtitle_idx", columns={"orig_subtitle"}),
*		@ORM\Index(name="type_idx", columns={"type"}),
*		@ORM\Index(name="lang_idx", columns={"lang"}),
*		@ORM\Index(name="orig_lang_idx", columns={"orig_lang"})}
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
	private $trans_year;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $trans_year2;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $orig_title;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $orig_subtitle;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=3)
	 */
	private $orig_lang;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
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
	private $orig_license;

	/**
	 * @var License
	 * @ORM\ManyToOne(targetEntity="License")
	 */
	private $trans_license;

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
	 * @ORM\Column(type="integer")
	 */
	private $size;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $zsize;

	/**
	 * @var date
	 * @ORM\Column(type="date")
	 */
	private $created_at;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $source;

	/**
	 * @var int
	 * @ORM\ManyToOne(targetEntity="TextRevision")
	 */
	private $cur_rev;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $dl_count = 0;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $read_count = 0;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $comment_count = 0;

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
	private $has_anno = false;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $has_cover = false;

	/*
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $has_title_note;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $is_compilation = false;

	/**
	 * An extra note about the text
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $note;

	/**
	 * A notice if the content is removed
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $removed_notice;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="TextAuthor", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"pos" = "ASC"})
	 */
	private $textAuthors;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="TextTranslator", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"pos" = "ASC"})
	 */
	private $textTranslators;

	/**
	 * @var ArrayCollection
	 */
	private $authors;

	/**
	 * Comma separated list of author names
	 */
	private $authorNames;

	/**
	 * Comma separated list of author original names
	 */
	private $authorOrigNames;

	/**
	 * @var ArrayCollection
	 */
	private $translators;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookText", mappedBy="text")
	 */
	private $bookTexts;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="Book", mappedBy="texts")
	 * @ORM\JoinTable(name="book_text",
	 *	joinColumns={@ORM\JoinColumn(name="text_id", referencedColumnName="id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id")})
	 * @ORM\OrderBy({"title" = "ASC"})
	 */
	private $books;

	/**
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="Label", inversedBy="texts")
	 * @ORM\OrderBy({"name" = "ASC"})
	 */
	private $labels;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="TextHeader", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"nr" = "ASC"})
	 */
	private $headers;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @var ArrayCollection
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="readTexts")
	 * @ORM\JoinTable(name="user_text_read",
	 *	joinColumns={@ORM\JoinColumn(name="text_id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="user_id")})
	 */
	private $readers;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="UserTextContrib", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $userContribs;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="TextRevision", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $revisions;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="TextLink", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $links;

	/**
	 * @ORM\Column(type="array", nullable=true)
	 */
	private $alikes;

	public function __construct($id = null) {
		$this->id = $id;
		$this->textAuthors = new ArrayCollection;
		$this->textTranslators = new ArrayCollection;
		$this->authors = new ArrayCollection;
		$this->translators = new ArrayCollection;
		$this->bookTexts = new ArrayCollection;
		$this->books = new ArrayCollection;
		$this->labels = new ArrayCollection;
		$this->headers = new ArrayCollection;
		$this->readers = new ArrayCollection;
		$this->userContribs = new ArrayCollection;
		$this->links = new ArrayCollection;
// 		if ( empty($this->year) ) {
// 			$this->year = $this->author_year;
// 		}
// 		if ( empty($this->trans_year) ) {
// 			$this->trans_year = $this->translator_year;
// 		}
// 		$this->subtitle = strtr($this->subtitle, array('\n' => self::TITLE_NEW_LINE));
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

	public function setTransYear($transYear) { $this->trans_year = $transYear; }
	public function getTransYear() { return $this->trans_year; }
	public function trans_year() { return $this->trans_year; }

	public function setTransYear2($transYear2) { $this->trans_year2 = $transYear2; }
	public function getTransYear2() { return $this->trans_year2; }
	public function trans_year2() { return $this->trans_year2; }

	public function setOrigTitle($origTitle) { $this->orig_title = $origTitle; }
	public function getOrigTitle() { return $this->orig_title; }
	public function orig_title() { return $this->orig_title; }

	public function setOrigSubtitle($origSubtitle) { $this->orig_subtitle = $origSubtitle; }
	public function getOrigSubtitle() { return $this->orig_subtitle; }
	public function orig_subtitle() { return $this->orig_subtitle; }

	public function setOrigLang($origLang) { $this->orig_lang = $origLang; }
	public function getOrigLang() { return $this->orig_lang; }
	public function orig_lang() { return $this->orig_lang; }

	public function setYear($year) { $this->year = $year; }
	public function getYear() { return $this->year; }

	public function setYear2($year2) { $this->year2 = $year2; }
	public function getYear2() { return $this->year2; }

	public function setOrigLicense($origLicense) { $this->orig_license = $origLicense; }
	public function getOrigLicense() { return $this->orig_license; }
	public function orig_license() { return $this->orig_license; }
	public function getOrigLicenseCode() {
		return $this->orig_license ? $this->orig_license->getCode() : null;
	}

	public function setTransLicense($transLicense) { $this->trans_license = $transLicense; }
	public function getTransLicense() { return $this->trans_license; }
	public function trans_license() { return $this->trans_license; }
	public function getTransLicenseCode() {
		return $this->trans_license ? $this->trans_license->getCode() : null;
	}

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setCover($cover) { $this->cover = $cover; }
	//public function getCover() { return $this->cover; }
	public function hasCover() {
		return false;
	}

	public function setSeries($series) { $this->series = $series; }
	public function getSeries() { return $this->series; }
	public function getSeriesSlug() {
		return $this->series ? $this->series->getSlug() : null;
	}

	public function setSernr($sernr) { $this->sernr = $sernr; }
	public function getSernr() { return $this->sernr; }
	public function setSernr2($sernr2) { $this->sernr2 = $sernr2; }
	public function getSernr2() { return $this->sernr2; }

	public function setCreatedAt($created_at) { $this->created_at = $created_at; }
	public function getCreatedAt() { return $this->created_at; }

	public function setSource($source) { $this->source = $source; }
	public function getSource() { return $this->source; }

	public function setCurRev($curRev) { $this->cur_rev = $curRev; }
	public function getCurRev() { return $this->cur_rev; }

	public function setDlCount($dlCount) { $this->dl_count = $dlCount; }
	public function getDlCount() { return $this->dl_count; }

	public function setReadCount($readCount) { $this->read_count = $readCount; }
	public function getReadCount() { return $this->read_count; }

	public function setCommentCount($commentCount) { $this->comment_count = $commentCount; }
	public function getCommentCount() { return $this->comment_count; }

	public function setRating($rating) { $this->rating = $rating; }
	public function getRating() { return $this->rating; }

	public function setVotes($votes) { $this->votes = $votes; }
	public function getVotes() { return $this->votes; }

	public function setHasAnno($hasAnno) { $this->has_anno = $hasAnno; }
	public function getHasAnno() { return $this->has_anno; }

// 	public function setHasTitleNote($hasTitleNote) { $this->has_title_note = $hasTitleNote; }
// 	public function getHasTitleNote() { return $this->has_title_note; }

	public function isCompilation() { return $this->is_compilation; }

	public function setNote($note) { $this->note = $note; }
	public function getNote() { return $this->note; }

	public function setRemovedNotice($removed_notice) { $this->removed_notice = $removed_notice; }
	public function getRemovedNotice() { return $this->removed_notice; }

	public function getUserContribs() { return $this->userContribs; }
	public function setUserContribs($userContribs) { $this->userContribs = $userContribs; }
	public function addUserContrib(UserTextContrib $userContrib) {
		$this->userContribs[] = $userContrib;
	}
	public function removeUserContrib(UserTextContrib $userContrib) {
		$this->userContribs->removeElement($userContrib);
	}

	public function addAuthor(Person $author) { $this->authors[] = $author; }

	public function getAuthors() {
		if (!isset($this->authors)) {
			$this->authors = array();
			foreach ($this->getTextAuthors() as $author) {
				if ($author->getPos() >= 0) {
					$this->authors[] = $author->getPerson();
				}
			}
		}
		return $this->authors;
	}

	public function addTranslator(Person $translator) { $this->translators[] = $translator; }
	public function getTranslators() {
		if (!isset($this->translators)) {
			$this->translators = array();
			foreach ($this->getTextTranslators() as $translator) {
				if ($translator->getPos() >= 0) {
					$this->translators[] = $translator->getPerson();
				}
			}
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
	public function getTextTranslators() { return $this->textTranslators; }

	public function addBook(Book $book) { $this->books[] = $book; }
	public function getBooks() { return $this->books; }

	public function getRevisions() { return $this->revisions; }
	public function addRevision(TextRevision $revision) {
		$this->revisions[] = $revision;
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
	public function getAlikes() { return $this->alikes; }

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

	public function addReader(User $reader) { $this->readers[] = $reader; }
	public function getReaders() { return $this->readers; }

	static protected $annotationDir = 'text-anno';
	static protected $infoDir = 'text-info';

	public function getDocId() {
		return 'http://chitanka.info/text/' . $this->id;
	}

	public function getYearHuman() {
		$year2 = empty($this->year2) ? '' : '–'. abs($this->year2);
		return $this->year >= 0
			? $this->year . $year2
			: abs($this->year) . $year2 .' пр.н.е.';
	}

	public function getTransYearHuman() {
		return $this->trans_year . (empty($this->trans_year2) ? '' : '–'.$this->trans_year2);
	}

	public function getAuthorNameEscaped() {
		if ( preg_match('/[a-z]/', $this->getAuthorOrigNames()) ) {
			return Legacy::removeDiacritics( Char::cyr2lat($this->getAuthorOrigNames()) );
		}

		return Char::cyr2lat($this->getAuthorNames());
	}

	public function isGamebook() {
		return $this->type == 'gamebook';
	}

	public function isTranslation() {
		return $this->lang != $this->orig_lang;
	}

	public function getAuthorNames() {
		if ( ! isset($this->authorNames)) {
			$this->authorNames = '';
			foreach ($this->getAuthors() as $author) {
				$this->authorNames .= $author->getName() . ', ';
			}
			$this->authorNames = rtrim($this->authorNames, ', ');
		}

		return $this->authorNames;
	}
	public function getAuthorsPlain() {
		return $this->getAuthorNames();
	}

	public function getAuthorOrigNames() {
		if ( ! isset($this->authorOrigNames)) {
			$this->authorOrigNames = '';
			foreach ($this->getAuthors() as $author) {
				$this->authorOrigNames .= $author->getOrigName() . ', ';
			}
			$this->authorOrigNames = rtrim($this->authorOrigNames, ', ');
		}

		return $this->authorOrigNames;
	}

	public function getAuthorSlugs() {
		if ( ! isset($this->authorSlugs)) {
			$this->authorSlugs = array();
			foreach ($this->getAuthors() as $author) {
				$this->authorSlugs[] = $author->getSlug();
			}
		}
		return $this->authorSlugs;
	}

	public function getTranslatorSlugs() {
		if ( ! isset($this->translatorSlugs)) {
			$this->translatorSlugs = array();
			foreach ($this->getTranslators() as $translator) {
				$this->translatorSlugs[] = $translator->getSlug();
			}
		}
		return $this->translatorSlugs;
	}

	public function getTitleAsSfb() {
		$title = "|\t" . $this->escapeForSfb($this->title);
		if ( !empty($this->subtitle) ) {
			$title .= "\n|\t" . strtr($this->escapeForSfb($this->subtitle), array(
				self::TITLE_NEW_LINE => "\n|\t",
				'\n' => "\n|\t",
			));
		}
		if ( $this->hasTitleNote() ) {
			$title .= '*';
		}
		return $title;
	}

	public function getTitleAsHtml($cnt = 0) {
		$title = $this->getTitle();

		if ( $this->hasTitleNote() ) {
			$suffix = SfbConverter::createNoteIdSuffix($cnt, 0);
			$title .= sprintf('<sup id="ref_%s" class="ref"><a href="#note_%s">[0]</a></sup>', $suffix, $suffix);
		}

		return "<h1>$title</h1>";
	}

	/**
	 * @param string $string
	 */
	public function escapeForSfb($string) {
		return strtr($string, array(
			'*' => '\*',
		));
	}

	public function hasTitleNote() {
		if ( ! is_null( $this->_hasTitleNote ) ) {
			return $this->_hasTitleNote;
		}

		$conv = new SfbToHtmlConverter( Legacy::getInternalContentFilePath( 'text', $this->id ) );
		return $this->_hasTitleNote = $conv->hasTitleNote();
	}

	public function getOrigTitleAsSfb() {
		if ( $this->orig_lang == $this->lang ) {
			return '';
		}
		$authors = '';
		foreach ($this->authors as $author) {
			$authors .= ', '. $author->getOrigName();
		}
		$authors = ltrim($authors, ', ');
		$orig_title = $this->orig_title;
		if ( !empty($this->orig_subtitle) ) {
			$orig_title .= " ({$this->orig_subtitle})";
		}
		$orig_title .= ', '. $this->getYearHuman();
		$orig_title = ltrim($orig_title, ', ');

		return rtrim("\t$authors\n\t$orig_title");
	}

	// TODO remove
	public function getCover($width = null) {
		return null;
	}

	public function getImages() {
		return $this->getImagesFromDir(Legacy::getInternalContentFilePath('img', $this->id));
	}

	public function getThumbImages() {
		return $this->getImagesFromDir(Legacy::getInternalContentFilePath('img', $this->id) . '/thumb');
	}

	/**
	 * @param string $dir
	 */
	public function getImagesFromDir($dir) {
		$images = array();

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
		$imgDir = $imgDirPrefix . Legacy::getContentFilePath('img', $this->id);
		$conv = new SfbToHtmlConverter($content, $imgDir);

		return $conv->convert()->getContent();
	}

	public function getBookExtraInfo() {
		$info = '';
		foreach ($this->bookTexts as $bookText) {
			if ($bookText->getShareInfo()) {
				$file = Legacy::getInternalContentFilePath('book-info', $bookText->getBook()->getId());
				if ( file_exists($file) ) {
					$info .= "\n\n" . file_get_contents($file);
				}
			}
		}

		return $info;
	}

	public function getPlainTranslationInfo() {
		if ($this->lang == $this->orig_lang) {
			return '';
		}

		$lang = Language::langName($this->orig_lang, false);
		if ( ! empty($lang) ) $lang = ' от '.$lang;

		$translator = empty($this->translator_name) ? '[Неизвестен]' : $this->translator_name;
		$year = $this->getTransYearHuman();
		if (empty($year)) $year = '—';

		return sprintf('Превод%s: %s, %s', $lang, $translator, $year);
	}

	public function getPlainSeriesInfo() {
		if (empty($this->series)) {
			return null;
		}

		return sprintf('Част %d от „%s“', $this->sernr, $this->series->getName());
	}

	// TODO reimplement if needed
	public function getNextFromSeries() {
//		if ( empty($this->series) ) {
//			return false;
//		}
//		$dbkey = array('series_id' => $this->seriesId);
//		if ($this->sernr == 0) {
//			$dbkey['t.id'] = array('>', $this->id);
//		} else {
//			$dbkey[] = 'sernr = '. ($this->sernr + 1)
//				. " OR (sernr > $this->sernr AND t.id > $this->id)";
//		}
//		return self::newFromDB($dbkey);
	}

	// TODO reimplement if needed
	public function getNextFromBooks() {
		return null;
//		$nextWorks = array();
//		foreach ($this->books as $id => $book) {
//			$nextWorks[$id] = $this->getNextFromBook($id);
//		}
//		return $nextWorks;
	}

	// TODO reimplement if needed
	public function getNextFromBook($book) {
//		if ( empty($this->books[$book]) ) {
//			return false;
//		}
//		$bookDescr = Legacy::getContentFile('book', $book);
//		if ( preg_match('/\{'. $this->id . '\}\n\{(\d+)\}/m', $bookDescr, $m) ) {
//			return self::newFromId($m[1]);
//		}
//		return false;
	}

	// TODO reimplement if needed
	public function getPrefaceOfBook($book) {
//		if ( empty($this->books[$book]) || $this->type == 'intro' ) {
//			return false;
//		}
//		$subkey = array('book_id' => $book);
//		$subquery = Setup::db()->selectQ(DBT_BOOK_TEXT, $subkey, 'text_id');
//		$dbkey = array("t.id IN ($subquery)", 't.type' => 'intro');
//		return self::newFromDB($dbkey);
	}

	public function getHistoryAndVersion() {
		$history = array();
		$historyRows = $this->getHistoryInfo();
		$verNo = 1;
		$ver = '0';
		foreach ( $historyRows as $data ) {
			$ver = '0.' . ($verNo++);
			$history[] = "$ver ($data[date]) — $data[comment]";
		}

		return array($history, $ver);
	}

	public function getFbi() {
		$generator = new TextFbiGenerator();
		return $generator->generateFbiForText($this);
	}

	public function getDataAsPlain() {
		$authors = implode($this->getAuthorSlugs());
		$translators = implode($this->getTranslatorSlugs());
		$labels = implode($this->getLabelSlugs());

		$contributors = array();
		foreach ($this->getUserContribs() as $userContrib/*@var $userContrib UserTextContrib*/) {
			$contributors[] = implode(',', array(
				$userContrib->getUsername(),
				$userContrib->getPercent(),
				'"'.$userContrib->getComment().'"',
				$userContrib->getHumandate(),
			));
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
orig_subtitle = {$this->getOrigsubtitle()}
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
		$filename = strtr(Setup::setting('download_file'), array(
			'AUTHOR' => $this->getAuthorNameEscaped(),
			'SERIES' => empty($this->series) ? '' : Legacy::getAcronym(Char::cyr2lat($this->series->getName())),
			'SERNO' => empty($this->sernr) ? '' : $this->sernr,
			'TITLE' => Char::cyr2lat($this->title),
			'ID' => $this->id,
		));
		$filename = $this->normalizeFileName($filename);

		return $filename;
	}

	static public function getMinRating() {
		if ( is_null( self::$_minRating ) ) {
			self::$_minRating = min( array_keys( self::$ratings ) );
		}
		return self::$_minRating;
	}

	static public function getMaxRating() {
		if ( is_null( self::$_maxRating ) ) {
			self::$_maxRating = max( array_keys( self::$ratings ) );
		}
		return self::$_maxRating;
	}

	static public function getRatings($id) {
		return Setup::db()->getFields(DBT_TEXT,
			array('id' => $id),
			array('rating', 'votes'));
	}

	public function getHistoryInfo() {
		$db = Setup::db();
		$res = $db->select(DBT_EDIT_HISTORY, array('text_id' => $this->id), '*', 'date ASC');
		$rows = array();

		while ( $row = $db->fetchAssoc($res) ) {
			$rows[] = $row;
		}

		if ($rows) {
			$isoEntryDate = $this->created_at->format('Y-m-d');
			if ( "$isoEntryDate 24" < $rows[0]['date'] ) {
				array_unshift($rows, array('date' => $isoEntryDate, 'comment' => 'Добавяне'));
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
	 * @ORM\PostPersist()
	 * @ORM\PostUpdate()
	 */
	public function postUpload() {
		$this->moveUploadedContentFile($this->getContentFile());
	}

	private function moveUploadedContentFile(UploadedFile $file = null) {
		if ($file) {
			$filename = Legacy::getContentFilePath('text', $this->id);
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
		if ( ! $this->is_compilation) {
			if ($asFileName) {
				return Legacy::getContentFilePath('text', $this->id);
			} else {
				return Legacy::getContentFile('text', $this->id);
			}
		}

		$template = Legacy::getContentFile('text', $this->id);
		if (preg_match_all('/\t\{file:(\d+-.+)\}/', $template, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				list($row, $filename) = $match;
				$template = str_replace($row, Legacy::getContentFile('text', $filename), $template);
			}
		}
		// TODO cache the full output

		return $template;
	}

	public function getFullTitleAsSfb() {
		$sfb = '';
		$sfb .= "|\t" . ($this->getAuthorNames() ?: '(неизвестен автор)') . "\n";
		$sfb .= $this->getTitleAsSfb();

		return $sfb;
	}

	public function getExtraInfoForDownload() {
		return $this->getOrigTitleAsSfb() . "\n\n"
			. $this->getFullExtraInfo()      . "\n\n"
			. "\tСвалено от „Моята библиотека“: ".$this->getDocId()."\n"
			. "\tПоследна корекция: ".Legacy::humanDate($this->cur_rev->getDate())."\n";
	}

	public function getContentAsFb2() {
		$generator = new TextFb2Generator();
		return $generator->generateFb2($this);
	}

	public function getLabelsNames() {
		$names = array();
		foreach ($this->getLabels() as $label) {
			$names[] = $label->getName();
		}
		return $names;
	}

	public function getLabelSlugs() {
		$slugs = array();
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
		$this->clearCollection($this->getHeaders());
	}

	/**
	 * @param string $file
	 */
	public function rebuildHeaders($file = null) {
		if ($file === null) $file = Legacy::getContentFilePath('text', $this->id);
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
		return Legacy::getContentFilePath('text', $this->id);
	}

}