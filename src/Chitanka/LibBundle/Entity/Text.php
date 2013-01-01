<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\File;
use Chitanka\LibBundle\Util\Language;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Legacy\Setup;
use Sfblib_SfbConverter as SfbConverter;
use Sfblib_SfbToHtmlConverter as SfbToHtmlConverter;
use Sfblib_SfbToFb2Converter as SfbToFb2Converter;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\TextRepository")
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
class Text extends BaseWork
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	protected $id;

	/**
	* @var string $slug
	* @ORM\Column(type="string", length=50)
	*/
	private $slug;

	/**
	* @var string $title
	* @ORM\Column(type="string", length=255)
	*/
	private $title;

	/**
	* @var string $subtitle
	* @ORM\Column(type="string", length=255, nullable=true)
	*/
	private $subtitle;

	/**
	* @var string $lang
	* @ORM\Column(type="string", length=2)
	*/
	private $lang = 'bg';

	/**
	* @var integer $trans_year
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $trans_year;

	/**
	* @var integer $trans_year2
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $trans_year2;

	/**
	* @var string $orig_title
	* @ORM\Column(type="string", length=255, nullable=true)
	*/
	private $orig_title;

	/**
	* @var string $orig_subtitle
	* @ORM\Column(type="string", length=255, nullable=true)
	*/
	private $orig_subtitle;

	/**
	* @var string $orig_lang
	* @ORM\Column(type="string", length=3)
	*/
	private $orig_lang;

	/**
	* @var integer $year
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $year;

	/**
	* @var integer $year2
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $year2;

	/**
	* @var integer $orig_license
	* @ORM\ManyToOne(targetEntity="License")
	*/
	private $orig_license;

	/**
	* @var integer $trans_license
	* @ORM\ManyToOne(targetEntity="License")
	*/
	private $trans_license;

	/**
	* @var string $type
	* @ORM\Column(type="string", length=12)
	*/
	private $type;

	/**
	* @var integer $series
	* @ORM\ManyToOne(targetEntity="Series", inversedBy="texts")
	*/
	private $series;

	/**
	* @var integer $sernr
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $sernr;

	/**
	* @var integer $sernr2
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $sernr2;

	/**
	* @var integer $headlevel
	* @ORM\Column(type="smallint")
	*/
	private $headlevel = 0;

	/**
	* @var integer $size
	* @ORM\Column(type="integer")
	*/
	private $size;

	/**
	* @var integer $zsize
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
	* @var integer $cur_rev
	* @ORM\ManyToOne(targetEntity="TextRevision")
	*/
	private $cur_rev;

	/**
	* @var integer $dl_count
	* @ORM\Column(type="integer")
	*/
	private $dl_count = 0;

	/**
	* @var integer $read_count
	* @ORM\Column(type="integer")
	*/
	private $read_count = 0;

	/**
	* @var integer $comment_count
	* @ORM\Column(type="integer")
	*/
	private $comment_count = 0;

	/**
	* @var float $rating
	* @ORM\Column(type="float")
	*/
	private $rating = 0;

	/**
	* @var integer $votes
	* @ORM\Column(type="integer")
	*/
	private $votes = 0;

	/**
	* @var boolean $has_anno
	* @ORM\Column(type="boolean")
	*/
	private $has_anno = false;

	/**
	* @var boolean
	* @ORM\Column(type="boolean")
	*/
	private $has_cover = false;

	/*
	* @var boolean
	* @ORM\Column(type="boolean")
	*/
	private $has_title_note;

	/**
	* @var boolean
	* @ORM\Column(type="boolean")
	*/
	private $is_compilation = false;

	/**
	 * A notice if the content is removed
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $removed_notice;


	/**
	* @var array
	* @ORM\OneToMany(targetEntity="TextAuthor", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	*/
	private $textAuthors;

	/**
	* @var array
	* @ORM\OneToMany(targetEntity="TextTranslator", mappedBy="text", cascade={"persist", "remove"}, orphanRemoval=true)
	*/
	private $textTranslators;

	/** FIXME doctrine:schema:create does not allow this relation
	* @ORM\ManyToMany(targetEntity="Person", inversedBy="textsAsAuthor")
	* @ORM\JoinTable(name="text_author")
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

	/** FIXME doctrine:schema:create does not allow this relation
	* @ORM\ManyToMany(targetEntity="Person", inversedBy="textsAsTranslator")
	* @ORM\JoinTable(name="text_translator")
	*/
	private $translators;

	/**
	* @var array
	* @ORM\OneToMany(targetEntity="BookText", mappedBy="text")
	*/
	private $bookTexts;

	/** FIXME doctrine:schema:create does not allow this relation
	* @ORM\ManyToMany(targetEntity="Book", mappedBy="texts")
	* @ORM\JoinTable(name="book_text",
	*	joinColumns={@ORM\JoinColumn(name="text_id", referencedColumnName="id")},
	*	inverseJoinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id")})
	* @ORM\OrderBy({"title" = "ASC"})
	*/
	private $books;

	/**
	* @var array
	* @ORM\ManyToMany(targetEntity="Label", inversedBy="texts")
	* @ORM\OrderBy({"name" = "ASC"})
	*/
	private $labels;

	/**
	* @var array
	* @ORM\OneToMany(targetEntity="TextHeader", mappedBy="text")
	* @ORM\OrderBy({"nr" = "ASC"})
	*/
	private $headers;

	/** FIXME doctrine:schema:create does not allow this relation
	* @var array
	* @ORM\ManyToMany(targetEntity="User", inversedBy="readTexts")
	* @ORM\JoinTable(name="user_text_read",
	*	joinColumns={@ORM\JoinColumn(name="text_id")},
	*	inverseJoinColumns={@ORM\JoinColumn(name="user_id")})
	*/
	private $readers;

	/**
	* @var array
	* @ORM\OneToMany(targetEntity="UserTextContrib", mappedBy="text")
	*/
	private $userContribs;

	/**
	* @var array
	* @ORM\OneToMany(targetEntity="TextRevision", mappedBy="text")
	*/
	private $revisions;

	public function __construct($id = null)
	{
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
// 		if ( empty($this->year) ) {
// 			$this->year = $this->author_year;
// 		}
// 		if ( empty($this->trans_year) ) {
// 			$this->trans_year = $this->translator_year;
// 		}
// 		$this->subtitle = strtr($this->subtitle, array('\n' => self::TITLE_NEW_LINE));
	}

	public function __toString()
	{
		return $this->getTitle();
		//return "$this->id";
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

	public function setTransLicense($transLicense) { $this->trans_license = $transLicense; }
	public function getTransLicense() { return $this->trans_license; }
	public function trans_license() { return $this->trans_license; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setCover($cover) { $this->cover = $cover; }
	//public function getCover() { return $this->cover; }
	public function hasCover() {
		return false;
	}

	public function setSeries($series) { $this->series = $series; }
	public function getSeries() { return $this->series; }

	public function setSernr($sernr) { $this->sernr = $sernr; }
	public function getSernr() { return $this->sernr; }
	public function setSernr2($sernr2) { $this->sernr2 = $sernr2; }
	public function getSernr2() { return $this->sernr2; }

	public function setHeadlevel($headlevel) { $this->headlevel = $headlevel; }
	public function getHeadlevel() { return $this->headlevel; }

	public function setSize($size) { $this->size = $size; }
	public function getSize() { return $this->size; }

	public function setZsize($zsize) { $this->zsize = $zsize; }
	public function getZsize() { return $this->zsize; }

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

	public function setRemovedNotice($removed_notice) { $this->removed_notice = $removed_notice; }
	public function getRemovedNotice() { return $this->removed_notice; }

	public function getUserContribs() { return $this->userContribs; }

	public function addAuthor(Person $author) { $this->authors[] = $author; }
	public function getAuthors() { return $this->authors; }

	public function addTranslator(Person $translator) { $this->translators[] = $translator; }
	public function getTranslators() { return $this->translators; }

	public function addTextAuthor(TextAuthor $textAuthor)
	{
		$this->textAuthors[] = $textAuthor;
	}
	public function removeTextAuthor(TextAuthor $textAuthor)
	{
		$this->textAuthors->removeElement($textAuthor);
	}
	// TODO needed by admin; why?
	public function addTextAuthors(TextAuthor $textAuthor) { $this->addTextAuthor($textAuthor); }

	public function setTextAuthors($textAuthors) { $this->textAuthors = $textAuthors; }
	public function getTextAuthors() { return $this->textAuthors; }

	public function addTextTranslator(TextTranslator $textTranslator)
	{
		$this->textTranslators[] = $textTranslator;
	}
	public function removeTextTranslator(TextTranslator $textTranslator)
	{
		$this->textTranslators->removeElement($textTranslator);
	}
	// TODO needed by admin; why?
	public function addTextTranslators(TextTranslator $textTranslator) { $this->addTextTranslator($textTranslator); }

	public function setTextTranslators($textTranslators) { $this->textTranslators = $textTranslators; }
	public function getTextTranslators() { return $this->textTranslators; }

	public function addBook(Book $book) { $this->books[] = $book; }
	public function getBooks() { return $this->books; }

	public function getRevisions() { return $this->revisions; }

	/**
	* Return the main book for the text
	*/
	public function getBook()
	{
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


	protected
		$annotationDir = 'text-anno',
		$infoDir = 'text-info';


	public function getDocId()
	{
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


	public function getAuthorNameEscaped()
	{
		if ( preg_match('/[a-z]/', $this->getAuthorOrigNames()) ) {
			return Legacy::removeDiacritics( Char::cyr2lat($this->getAuthorOrigNames()) );
		}

		return Char::cyr2lat($this->getAuthorNames());
	}


	public function isGamebook()
	{
		return $this->type == 'gamebook';
	}

	public function isTranslation()
	{
		return $this->lang != $this->orig_lang;
	}


	public function getAuthorNames()
	{
		if ( ! isset($this->authorNames)) {
			$this->authorNames = '';
			foreach ($this->getAuthors() as $author) {
				$this->authorNames .= $author->getName() . ', ';
			}
			$this->authorNames = rtrim($this->authorNames, ', ');
		}

		return $this->authorNames;
	}
	public function getAuthorsPlain()
	{
		return $this->getAuthorNames();
	}


	public function getAuthorOrigNames()
	{
		if ( ! isset($this->authorOrigNames)) {
			$this->authorOrigNames = '';
			foreach ($this->getAuthors() as $author) {
				$this->authorOrigNames .= $author->getOrigName() . ', ';
			}
			$this->authorOrigNames = rtrim($this->authorOrigNames, ', ');
		}

		return $this->authorOrigNames;
	}

	public function getTitleAsSfb() {
		$title = "|\t" . $this->escapeForSfb($this->title);
		if ( !empty($this->subtitle) ) {
			$title .= "\n|\t" . strtr($this->escapeForSfb($this->subtitle),
				array(self::TITLE_NEW_LINE => "\n|\t"));
		}
		if ( $this->hasTitleNote() ) {
			$title .= '*';
		}
		return $title;
	}


	public function getTitleAsHtml($cnt = 0)
	{
		$title = $this->getTitle();

		if ( $this->hasTitleNote() ) {
			$suffix = SfbConverter::createNoteIdSuffix($cnt, 0);
			$title .= sprintf('<sup id="ref_%s" class="ref"><a href="#note_%s">[0]</a></sup>', $suffix, $suffix);
		}

		return "<h1>$title</h1>";
	}

	public function escapeForSfb($string)
	{
		return strtr($string, array(
			'*' => '\*',
		));
	}


	public function hasTitleNote()
	{
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
	public function getCover($width = null)
	{
		return null;
	}


	public function getImages()
	{
		return $this->getImagesFromDir(Legacy::getInternalContentFilePath('img', $this->id));
	}

	public function getThumbImages()
	{
		return $this->getImagesFromDir(Legacy::getInternalContentFilePath('img', $this->id) . '/thumb');
	}

	public function getImagesFromDir($dir)
	{
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


	public function getExtraInfo() {
		return parent::getExtraInfo() . $this->getBookExtraInfo();
	}


	public function getExtraInfoHtml($imgDirPrefix = '')
	{
		return $this->_getContentHtml($this->getExtraInfo(), $imgDirPrefix);
	}

	public function getAnnotationHtml($imgDirPrefix = '')
	{
		return $this->_getContentHtml($this->getAnnotation(), $imgDirPrefix);
	}


	protected function _getContentHtml($content, $imgDirPrefix)
	{
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


	public function getPlainTranslationInfo()
	{
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


	public function getPlainSeriesInfo()
	{
		if (empty($this->series)) {
			return null;
		}

		return sprintf('Част %d от „%s“', $this->sernr, $this->series->getName());
	}


	public function getNextFromSeries() {
		if ( empty($this->series) ) {
			return false;
		}
		$dbkey = array('series_id' => $this->seriesId);
		if ($this->sernr == 0) {
			$dbkey['t.id'] = array('>', $this->id);
		} else {
			$dbkey[] = 'sernr = '. ($this->sernr + 1)
				. " OR (sernr > $this->sernr AND t.id > $this->id)";
		}
		return self::newFromDB($dbkey);
	}


	public function getNextFromBooks() {
		$nextWorks = array();
		foreach ($this->books as $id => $book) {
			$nextWorks[$id] = $this->getNextFromBook($id);
		}
		return $nextWorks;
	}

	public function getNextFromBook($book) {
		if ( empty($this->books[$book]) ) {
			return false;
		}
		$bookDescr = Legacy::getContentFile('book', $book);
		if ( preg_match('/\{'. $this->id . '\}\n\{(\d+)\}/m', $bookDescr, $m) ) {
			return self::newFromId($m[1]);
		}
		return false;
	}


	public function getPrefaceOfBook($book) {
		if ( empty($this->books[$book]) || $this->type == 'intro' ) {
			return false;
		}
		$subkey = array('book_id' => $book);
		$subquery = Setup::db()->selectQ(DBT_BOOK_TEXT, $subkey, 'text_id');
		$dbkey = array("t.id IN ($subquery)", 't.type' => 'intro');
		return self::newFromDB($dbkey);
	}


	/**
		Return fiction book info for this work
	*/
	public function getFbi()
	{
		return ''; // TODO rewrite legacy code
		return $this->getFbiMain()
			. "\n" . $this->getFbiOriginal()
			. "\n" . $this->getFbiDocument()
			//. "\n" . $this->getFbiEdition() // not implemented
			;
	}


	protected function getFbiMain()
	{
		$authors = '';
		foreach ($this->authors as $author) {
			$authors .= "\n|Автор        = " . $author->getName();
		}
		$title = $this->title;
		if ( ! empty( $this->subtitle ) ) {
			$subtitle = strtr($this->subtitle, array(self::TITLE_NEW_LINE => ', '));
			$title .= ' (' . trim($subtitle, '()') . ')';
		}
		$anno = $this->getAnnotation();
		$translators = '';
		foreach ($this->translators as $data) {
			$year = empty( $data['year'] ) ? $this->trans_year : $data['year'];
			$translators .= "\n|Преводач     = $data[name] [&$year]";
		}
		$series = empty($this->series) ? Legacy::workType($this->type, false) : $this->series->getName();
		if ( ! empty($this->series) && ! empty( $this->sernr ) ) {
			$series .= " [$this->sernr]";
		}
		$keywords = implode(', ', $this->getLabels());
		$origLangView = $this->lang == $this->orig_lang ? '' : $this->orig_lang;
		return <<<EOS
{Произведение:$authors
|Заглавие     = $title
{Анотация:
$anno
}
|Дата         = $this->year
|Корица       =
|Език         = $this->lang
|Ориг.език    = $origLangView$translators
|Поредица     = $series
|Жанр         =
|Ключови-думи = $keywords
}
EOS;
	}


	protected function getFbiOriginal()
	{
		if ( $this->lang == $this->orig_lang ) {
			return '';
		}
		$authors = '';
		foreach ($this->authors as $data) {
			$name = $data['orig_name'];
			$authors .= "\n|Автор        = $name";
		}
		$title = $this->orig_title;
		$subtitle = $this->orig_subtitle;
		if ( ! empty( $subtitle ) ) {
			$title .= ' (' . trim($subtitle, '()') . ')';
		}
		if ($this->series) {
			$series = $this->series->getOrigName();
			if ( ! empty($series) && ! empty( $this->sernr ) ) {
				$series .= " [$this->sernr]";
			}
		} else {
			$series = '';
		}

		return <<<EOS
{Оригинал:$authors
|Заглавие     = $title
|Дата         = $this->year
|Език         = $this->orig_lang
|Поредица     = $series
}
EOS;
	}


	protected function getFbiDocument()
	{
		$date = date('Y-m-d H:i:s');
		list($history, $version) = $this->getHistoryAndVersion();
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
|Идентификатор = mylib-$this->id
|Версия        = $version
{История:$history
}
|Издател       =
}
EOS;
	}


	public function getHistoryAndVersion()
	{
		$history = array();
		$historyRows = $this->getHistoryInfo();
		$verNo = 1;
/*		if ( "$this->created_at 24" < $historyRows[0]['date'] ) {
			$ver = '0.' . ($verNo++);
			$vdate = $this->created_at == '0000-00-00' ? LIB_OPEN_DATE : $this->created_at;
			$history[] = "$ver ($vdate) — Добавяне";
		}*/
		foreach ( $historyRows as $data ) {
			$ver = '0.' . ($verNo++);
			$history[] = "$ver ($data[date]) — $data[comment]";
		}

		return array($history, $ver);
	}


	protected function getFbiEdition()
	{
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


	public function getNameForFile()
	{
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


	public function getHistoryInfo()
	{
		$db = Setup::db();
		$res = $db->select(DBT_EDIT_HISTORY, array('text_id' => $this->id),
			'*', 'date ASC');
		$rows = array();

		while ( $row = $db->fetchAssoc($res) ) {
			$rows[] = $row;
		}

		$isoEntryDate = $this->created_at->format('Y-m-d');
		if ( "$isoEntryDate 24" < $rows[0]['date'] ) {
			$date = is_null($this->created_at) ? LIB_OPEN_DATE : $isoEntryDate;
			array_unshift($rows, array('date' => $date, 'comment' => 'Добавяне'));
		}

		return $rows;
	}


	public function getContentAsSfb()
	{
		$sfb = $this->getFullTitleAsSfb() . "\n\n\n";

		$anno = $this->getAnnotation();
		if ( ! empty($anno) ) {
			$sfb .= "A>\n$anno\nA$\n\n";
		}
		$sfb .= $this->getRawContent();
		$extra = $this->getFullExtraInfo();
		$extra = preg_replace('/\n\n+/', "\n\n", $extra);
		$sfb .= "\nI>\n".trim($extra, "\n")."\nI$\n";

		return $sfb;
	}


	public function getRawContent($asFileName = false)
	{
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

	public function getFullTitleAsSfb()
	{
		$sfb = '';
		if ( ($authorNames = $this->getAuthorNames()) ) {
			$sfb .= "|\t" . $authorNames . "\n";
		}
		$sfb .= $this->getTitleAsSfb();

		return $sfb;
	}


	public function getFullExtraInfo()
	{
		return $this->getOrigTitleAsSfb() . "\n\n"
			. $this->getExtraInfo()      . "\n\n"
			. "\tСвалено от „Моята библиотека“: ".$this->getDocId()."\n"
			. "\tПоследна корекция: ".Legacy::humanDate($this->cur_rev->getDate())."\n";
	}


	public function getContentAsFb2()
	{
		$conv = new SfbToFb2Converter($this->getContentAsSfb(), Legacy::getInternalContentFilePath('img', $this->id));

		$conv->setObjectCount(1);
		$conv->setSubtitle($this->subtitle);
		$conv->setGenre($this->getGenresForFb2());
		$conv->setKeywords($this->getKeywordsForFb2());
		$conv->setTextDate($this->year);

		$conv->setLang($this->lang);
		$conv->setSrcLang(empty($this->orig_lang) ? '?' : $this->orig_lang);

		foreach ($this->translators as $translator) {
			$conv->addTranslator($translator->getName());
		}

		if ($this->series) {
			$conv->addSequence($this->series->getName(), $this->sernr);
		}

		if ( $this->lang != $this->orig_lang ) {
			foreach ($this->authors as $author) {
				if ($author->getOrigName() == '') {
					$conv->addSrcAuthor('(no original name for '.$author->getName().')', false);
				} else {
					$conv->addSrcAuthor($author->getOrigName());
				}
			}

			$conv->setSrcTitle(empty($this->orig_title) ? '(no data for original title)' : '');
			$conv->setSrcSubtitle($this->orig_subtitle);

			if ($this->series && $this->series->getOrigName()) {
				$conv->addSrcSequence($this->series->getOrigName(), $this->sernr);
			}
		}

		$conv->setDocId($this->getDocId());
		list($history, $version) = $this->getHistoryAndVersion();
		$conv->setDocVersion($version);
		$conv->setHistory($history);
		$conv->setDocAuthor('Моята библиотека');

		if ($this->type == 'gamebook') {
			// recognize section links
			$conv->addRegExpPattern('/#(\d+)/', '<a l:href="#l-$1">$1</a>');
		}

		$conv->enablePrettyOutput();

		return $conv->convert()->getContent();
	}

	// TODO move this to a proper generation class
	private $labelsToGenres = array(
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
		'Документална литература' => array('sci_history', 'nonfiction'),
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
		'Модернизъм' => array('sci_culture', 'design'),
		'Морска тематика' => 'adv_maritime',
		'Музика' => array('sci_culture', 'design'),
		'Народно творчество' => array('sci_culture', 'design'),
		'Научна фантастика' => 'sf',
		'Научнопопулярна литература' => 'science',
		'Окултизъм' => 'religion',
		'Организирана престъпност' => 'det_political',
		'Паралелни вселени' => array('sf', 'sf_epic', 'sf_heroic'),
		'Политология' => 'sci_politics',
		'Полусвободна литература' => 'home',
		'Постапокалипсис' => 'sf_history',
		'Приключенска литература' => 'adventure',
		'Психология' => 'sci_psychology',
		'Психофактор' => 'sci_philosophy',
		'Пътешествия' => 'adv_geo',
		'Разказ с (не)очакван край' => 'prose',
		'Реализъм' => array('sci_culture', 'design'),
		'Религия' => 'religion_rel',
		'Ренесанс' => 'sci_history',
		'Рицарски роман' => 'adv_history',
		'Робинзониада' => 'sf_heroic',
		'Родителство' => array('home_health', 'home'),
		'Романтизъм' => array('sci_culture', 'design'),
		'Руска класика' => 'prose_rus_classic',
		'Сатанизъм' => 'religion',
		'Сатира' => 'humor',
		'Световна класика' => 'prose_classic',
		'Секс' => 'home_sex',
		'Символизъм' => array('sci_culture', 'design'),
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
	);
	public function getGenresForFb2()
	{
		$genres = array();
		$labels = $this->getLabelsNames();
		foreach ($labels as $label) {
			if (array_key_exists($label, $this->labelsToGenres)) {
				$genres = array_merge($genres, (array) $this->labelsToGenres[$label]);
			}
		}
		$genres = array_unique($genres);
		if (empty($genres)) {
			switch ($this->getType()) {
				case 'poetry': $genres[] = 'poetry'; break;
				default:       $genres[] = 'prose';
			}
		}
		return $genres;
	}

	private function getKeywordsForFb2()
	{
		return implode(', ', $this->getLabelsNames());
	}

	public function getLabelsNames()
	{
		$names = array();
		foreach ($this->getLabels() as $label) {
			$names[] = $label->getName();
		}
		return $names;
	}

	static public function newFromId($id, $reader = 0) {
		return self::newFromDB( array('t.id' => $id), $reader );
	}

	static public function newFromTitle($title, $reader = 0) {
		return self::newFromDB( array('t.title' => $title), $reader );
	}


	static public function incReadCounter($id) {
		return; // disable
		Setup::db()->update(DBT_TEXT, array('read_count=read_count+1'), compact('id'));
	}

	static public function incDlCounter($id) {
		return; // disable
		Setup::db()->update(DBT_TEXT, array('dl_count=dl_count+1'), compact('id'));
	}

	static protected function newFromDB($dbkey, $reader = 0) {
		$db = Setup::db();
		//$dbkey['mode'] = 'public';
		$qa = array(
			'SELECT' => 't.*,
				s.id seriesId,
				s.name series, s.orig_name seriesOrigName,
				lo.code lo_code, lo.fullname lo_name, lo.copyright lo_copyright, lo.uri lo_uri,
				lt.code lt_code, lt.fullname lt_name, lt.copyright lt_copyright, lt.uri lt_uri,
				r.user_id isRead, h.date lastedit',
			'FROM' => DBT_TEXT .' t',
			'LEFT JOIN' => array(
				DBT_SERIES .' s' => 't.series_id = s.id',
				DBT_LICENSE .' lo' => 't.orig_license_id = lo.id',
				DBT_LICENSE .' lt' => 't.trans_license_id = lt.id',
				DBT_READER_OF .' r' => "t.id = r.text_id AND r.user_id = ".((int)$reader),
				DBT_EDIT_HISTORY .' h' => 't.cur_rev_id = h.id',
			),
			'WHERE' => $dbkey,
			'ORDER BY' => 't.sernr ASC',
			'LIMIT' => 1,
		);
		$fields = $db->fetchAssoc( $db->extselect($qa) );
		if ( empty($fields) ) {
			return null;
		}

		// Author(s), translator(s)
		$tables = array('author' => DBT_AUTHOR_OF, 'translator' => DBT_TRANSLATOR_OF);
		foreach ($tables as $role => $table) {
			$qa = array(
				'SELECT' => 'p.*, of.year',
				'FROM' => $table .' of',
				'LEFT JOIN' => array(DBT_PERSON .' p' => "of.person_id = p.id"),
				'WHERE' => array('of.text_id' => $fields['id']),
				'ORDER BY' => 'of.pos ASC',
			);
			$res = $db->extselect($qa);
			$persons = array();
			$string_name = $string_orig_name = $string_year = '';
			while ( $data = $db->fetchAssoc($res) ) {
				$persons[] = $data;
				$string_name .= ', '. $data['name'];
				$string_orig_name .= ', '. $data['orig_name'];
				$string_year .= ', '. $data['year'];
			}
			$fields[$role.'s'] = $persons;
			$fields[$role.'_name'] = ltrim($string_name, ', ');
			$fields[$role.'_orig_name'] = ltrim($string_orig_name, ', ');
			$fields[$role.'_year'] = ltrim($string_year, ', 0');
		}
		// Books
		$qa = array(
			'SELECT' => 'b.*, bt.*',
			'FROM' => DBT_BOOK_TEXT .' bt',
			'LEFT JOIN' => array(DBT_BOOK .' b' => 'bt.book_id = b.id'),
			'WHERE' => array('bt.text_id' => $fields['id']),
		);
		$res = $db->extselect($qa);
		$fields['books'] = array();
		while ( $data = $db->fetchAssoc($res) ) {
			$fields['books'][$data['id']] = $data;
		}
		$text = new Text;
		foreach ($fields as $field => $value) {
			$mutator = 'set'.ucfirst($field);
			if (is_callable(array($text, $mutator))) {
				$text->$mutator($value);
			}
		}

		return $text;
	}


	/**
		Get similar texts based ot readers count.
		@param $limit   Return up to this limit number of texts
		@param $reader  Do not return texts marked as read by this reader
	*/
	public function getSimilar($limit = 10, $reader = null)
	{
		$db = Setup::db();
		$qa = array(
			'SELECT'   => 'text_id, count(*) readers',
			'FROM'     => DBT_READER_OF .' r',
			'WHERE'    => array(
				'r.text_id' => array('<>', $this->id),
				'r.user_id IN ('
					. $db->selectQ(DBT_READER_OF, array('text_id' => $this->id), 'user_id')
					. ')',
			),
			'GROUP BY' => 'r.text_id',
			'ORDER BY' => 'readers DESC',
		);
		if ( is_object($reader) ) {
			$qa['WHERE'][] = 'text_id NOT IN ('
				. $db->selectQ(DBT_READER_OF, array('user_id' => $reader->getId()), 'text_id')
				. ')';
		}
		$res = $db->extselect($qa);
		$texts = $textsInQueue = array();
		$lastReaders = 0;
		$count = 0;
		while ( $row = $db->fetchAssoc($res) ) {
			$count++;
			if ( $lastReaders > $row['readers'] ) {
				if ( $count > $limit ) {
					break;
				}
				$texts = array_merge($texts, $textsInQueue);
				$textsInQueue = array();
			}
			$textsInQueue[] = $row['text_id'];
			$lastReaders = $row['readers'];
		}

		if ( $count > $limit ) {
			$texts = array_merge($texts, $this->filterSimilarByLabel($textsInQueue, $limit - count($texts)));
		}

// 		if ( empty($texts) ) {
// 			$texts = $this->getSimilarByLabel($limit, $reader);
// 		}

		return $texts;
	}


	/**
		Get similar texts based ot readers count.
		@param $limit   Return up to this limit number of texts
		@param $reader  Do not return texts marked as read by this reader
	*/
	public function getSimilarByLabel($limit = 10, $reader = null)
	{
		$db = Setup::db();
		$qa = array(
			'SELECT'   => 'text_id',
			'FROM'     => DBT_TEXT_LABEL,
			'WHERE'    => array(
				'text_id' => array('<>', $this->id),
				'label_id IN ('
					. $db->selectQ(DBT_TEXT_LABEL, array('text_id' => $this->id), 'label_id')
					. ')',
			),
			'GROUP BY' => 'text_id',
			'ORDER BY' => 'COUNT(text_id) DESC',
			'LIMIT'    => $limit,
		);
		if ( $reader ) {
			$qa['WHERE'][] = 'text_id NOT IN ('
				. $db->selectQ(DBT_READER_OF, array('user_id' => $reader), 'text_id')
				. ')';
		}
		$res = $db->extselect($qa);
		$texts = array();
		while ($row = $db->fetchRow($res)) {
			$texts[] = $row[0];
		}
		return $texts;
	}


	public function filterSimilarByLabel($texts, $limit)
	{
		$db = Setup::db();
		$qa = array(
			'SELECT'   => 'text_id',
			'FROM'     => DBT_TEXT_LABEL,
			'WHERE'    => array(
				'text_id' => array('IN', $texts),
				'label_id IN ('
					. $db->selectQ(DBT_TEXT_LABEL, array('text_id' => $this->id), 'label_id')
					. ')',
			),
			'GROUP BY' => 'text_id',
			'ORDER BY' => 'COUNT(text_id) DESC',
			'LIMIT'    => $limit,
		);
		$res = $db->extselect($qa);
		$texts = array();
		while ($row = $db->fetchRow($res)) {
			$texts[] = $row[0];
		}
		return $texts;
	}


	public function getHeaders()
	{
		return $this->headers;
	}


	public function getEpubChunks($imgDir)
	{
		return $this->getEpubChunksFrom($this->getRawContent(true), $imgDir);
	}


	public function getContentHtml($imgDirPrefix = '', $part = 1, $objCount = 0)
	{
		$imgDir = $imgDirPrefix . Legacy::getContentFilePath('img', $this->id);
		$conv = new SfbToHtmlConverter($this->getRawContent(true), $imgDir);
		// TODO do not hardcode it; inject it through parameter
		$conv->setInternalLinkTarget("/text/$this->id/0");

		if ( ! empty( $objCount ) ) {
			$conv->setObjectCount($objCount);
		}
		$header = $this->getHeaderByNr($part);
		if ($header) {
			$conv->startpos = $header->getFpos();
			$conv->maxlinecnt = $header->getLinecnt();
		}
		if ($this->type == 'gamebook') {
			// recognize section links
			$conv->patterns['/#(\d+)/'] = '<a href="#l-$1" class="ep" title="Към част $1">$1</a>';
		}

		return $conv->convert()->getContent();
	}


	public function getHeaderByNr($nr)
	{
		foreach ($this->getHeaders() as $header) {
			if ($header->getNr() == $nr) {
				return $header;
			}
		}

		return null;
	}

	public function getNextHeaderByNr($nr)
	{
		if ($nr > 0) {
			foreach ($this->getHeaders() as $header) {
				if ($header->getNr() == $nr + 1) {
					return $header;
				}
			}
		}

		return null;
	}


	public function getTotalRating()
	{
		return $this->rating * $this->votes;
	}

	/**
	* Update average rating
	*
	* @param int Newly given rating
	* @param int (optional) An old rating which should be overwritten by the new one
	* @return this
	*/
	public function updateAvgRating($newRating, $oldRating = null)
	{
		if ( is_null($oldRating) ) {
			$this->rating = ($this->getTotalRating() + $newRating) / ($this->votes + 1);
			$this->votes += 1;
		} else {
			$this->rating = ($this->getTotalRating() - $oldRating + $newRating) / $this->votes;
		}

		return $this;
	}

	public function getMainContentFile()
	{
		return Legacy::getContentFilePath('text', $this->id);
	}

}
