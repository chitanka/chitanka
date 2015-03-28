<?php namespace App\Generator;

use App\Util\String;
use App\Util\File;
use App\Entity\BaseWork;
use App\Entity\Book;
use App\Entity\Text;

class EpubFile {

	private $obj;
	private $files = [];
	private $items = [
		'pre' => [],
		'main' => [],
		'post' => [],
	];
	private $mainDir = 'OPS';
	private $curPlayOrder = 0;
	private $containerFileName;
	private $contentFileName;
	private $tocFileName;
	private $cssFileName;
	private $titlePageFileName;
	private $creditsPageFileName;
	private $imagesDir;
	private $templateDir;

	/**
	 * @param BaseWork $work
	 */
	public function __construct(BaseWork $work) {
		$this->obj = $work;

		$this->containerFileName = 'container.xml';
		$this->contentFileName = 'content.opf';
		$this->tocFileName = 'toc.ncx';
		$this->cssFileName = 'css/main.css';
		$this->titlePageFileName = 'title-page.xhtml';
		$this->creditsPageFileName = 'credits.xhtml';

		$this->imagesDir = 'images';
		$this->templateDir = __DIR__ . '/../Resources/templates/epub';

		$this->addFile('ncx', $this->tocFileName, 'application/x-dtbncx+xml');
		$this->addFile('stylesheet', $this->cssFileName, 'text/css');
	}

	public function getMimetypeFile() {
		return [
			'name'    => 'mimetype',
			'content' => 'application/epub+zip',
		];
	}

	public function getContainerFile() {
		return [
			'name'    => "META-INF/$this->containerFileName",
			'content' => $this->getContainerFileContent(),
		];
	}

	private function getContainerFileContent() {
		return $this->getTemplate($this->containerFileName, [
			'ROOTFILE' => "$this->mainDir/$this->contentFileName"
		]);
	}

	public function getCssFile() {
		return [
			'name'    => "$this->mainDir/$this->cssFileName",
			'content' => $this->getCssFileContent(),
		];
	}

	private function getCssFileContent() {
		return $this->getTemplate($this->cssFileName);
	}

	public function getTocFile() {
		return [
			'name'    => "$this->mainDir/$this->tocFileName",
			'content' => $this->getTocFileContent(),
		];
	}

	private function getTocFileContent() {
		return $this->getTemplate($this->tocFileName, [
			'LANG'       => $this->obj->getLang(),
			'ID'         => $this->obj->getDocId(),
			'DEPTH'      => $this->obj->getMaxHeadersDepth(),
			'EPUBLISHER' => $this->getPublisher(),
			'TITLE'      => htmlspecialchars($this->obj->getTitles()),
			'AUTHOR'     => $this->obj->getAuthorNamesString(),
			'NAVPOINTS'  => $this->getNavPointTags(),
		]);
	}

	private function getNavPointTags() {
		return $this->getPreNavPointTags() . $this->getMainNavPointTags() . $this->getPostNavPointTags();
	}

	private function getMainNavPointTags() {
		$headers = strtr($this->obj->getHeadersAsNestedXml(false), [
			'</li>' => '</navPoint>',
			'<ul>' => '', '</ul>' => '',
		]);

		$navPoint = $this->curPlayOrder;
		$order = 0;
		while (preg_match('/<li level=(\d+)>/', $headers, $matches)) {
			$order = $this->curPlayOrder + ((int) $matches[1] + 1);
			$repl = sprintf('<navPoint class="chapter" id="navpoint-%d" playOrder="%d"><navLabel><text>$2</text></navLabel><content src="%s"/>',
				++$navPoint, $order, $this->getItemFileName('$1', false));
			$headers = preg_replace('/<li level=(\d+)>([^<]+)/', $repl, $headers, 1);
		}
		$this->curPlayOrder = $order;

		return $headers;
	}

	private function getPreNavPointTags() {
		return $this->getExtraNavPointTags('pre');
	}

	private function getPostNavPointTags() {
		return $this->getExtraNavPointTags('post');
	}

	/**
	 * @param string $type
	 */
	private function getExtraNavPointTags($type) {
		$tags = [];
		foreach ($this->items[$type] as $href => $title) {
			$this->curPlayOrder++;
			$tags[] = sprintf('<navPoint class="chapter" id="navpoint-ext-%d" playOrder="%d"><navLabel><text>%s</text></navLabel><content src="%s"/></navPoint>',
				$this->curPlayOrder, $this->curPlayOrder, $title, $href);
		}

		return implode("\n", $tags);
	}

	public function getContentFile() {
		return [
			'name'    => "$this->mainDir/$this->contentFileName",
			'content' => $this->getContentFileContent(),
		];
	}

	private function getContentFileContent() {
		return $this->getTemplate($this->contentFileName, [
			'TITLE'          => htmlspecialchars($this->obj->getTitles()),
			'AUTHOR'         => $this->getAuthorTags(),
			'SUBJECT'        => $this->getSubjectTags(),
			'CONTRIBUTOR'    => $this->getContributorTags(),
			'DATE'           => $this->getDateTags(),
			'REVISIONS'      => $this->getRevisionTags(),
			'ID'             => $this->obj->getDocId(),
			'EPUBLISHER'     => $this->getPublisher(),
			'LANG'           => $this->obj->getLang(),
			'MANIFEST-ITEMS' => $this->getManifestItemTags(),
			'SPINE-ITEMS'    => $this->getSpineItems(),
		]);
	}

	private function getPublisher() {
		return 'Моята библиотека (http://chitanka.info)';
	}

	private function getAuthorTags() {
		if ($this->obj instanceof Book) {
			$tags = $this->getBookAuthorTags($this->obj);
		} else {
			$tags = $this->getTextAuthorTags($this->obj);
		}

		return implode("\n", $tags);
	}

	private function getTextAuthorTags(Text $text) {
		$tags = [];
		foreach ($text->getAuthors() as $author) {
			$tags[] = self::getCreatorTag($author->getName(), 'aut');
		}

		return $tags;
	}

	private function getBookAuthorTags(Book $book) {
		$tags = [];
		foreach ($book->getMainAuthors() as $author) {
			$tags[] = self::getCreatorTag($author->getName(), 'aut');
		}

		foreach ($book->getAuthorsBy('intro') as $author) {
			$tags[] = self::getCreatorTag($author->getName(), 'aui');
		}

		foreach ($book->getAuthorsBy('outro') as $author) {
			$tags[] = self::getCreatorTag($author->getName(), 'aft');
		}

		return $tags;
	}

	/**
	 * @param string $name
	 * @param string $role
	 */
	private static function getCreatorTag($name, $role) {
		return sprintf('<dc:creator opf:file-as="%s" opf:role="%s">%s</dc:creator>',
			String::getMachinePersonName($name), $role, $name);
	}

	/**
	 * @param string $name
	 * @param string $role
	 */
	private static function getContributorTag($name, $role) {
		return sprintf('<dc:contributor opf:file-as="%s" opf:role="%s">%s</dc:contributor>',
			String::getMachinePersonName($name), $role, $name);
	}

	private function getSubjectTags() {
		$tags = [];
		foreach ($this->obj->getLabels() as $label) {
			$tags[] = "<dc:subject>$label</dc:subject>";
		}

		return implode("\n", $tags);
	}

	private function getContributorTags() {
		$tags = [];
		foreach ($this->obj->getTranslators() as $translator) {
			$tags[] = self::getContributorTag($translator->getName(), 'trl');
		}

		return implode("\n", $tags);
	}

	private function getDateTags() {
		$tags = [];
		if ( ($year = $this->obj->getYear()) > 0 ) {
			$tags[] = sprintf('<dc:date opf:event="original-publication">%04d</dc:date>', $year);
		}

		if ($this->obj->isTranslation() && ($year = $this->obj->getTransYear())) {
			$tags[] = sprintf('<dc:date opf:event="translation-publication">%s</dc:date>', $year);
		}

		return implode("\n", $tags);
	}

	private function getRevisionTags() {
		$tags = [];
		foreach ($this->obj->getHistoryInfo() as $rev) {
			$tags[] = sprintf('<dc:date opf:event="chitanka-%s">%s</dc:date>',
				htmlspecialchars($rev['comment']),
				preg_replace('/(\S+) \S+/', '$1', $rev['date']));
		}

		return implode("\n", $tags);
	}

	private function getManifestItemTags() {
		$tags = [];

		foreach ($this->files as $id => $data) {
			list($href, $mime) = $data;
			$tags[] = sprintf('<item id="%s" href="%s" media-type="%s"/>', $id, $href, $mime);
		}

		foreach (array_keys($this->getItems()) as $i => $href) {
			$tags[] = sprintf('<item id="item-%d" href="%s" media-type="application/xhtml+xml"/>', ($i+1), $href);
		}

		return implode("\n", $tags);
	}

	private function getSpineItems() {
		$tags = [];

		for ($i = 1, $c = count($this->getItems()); $i <= $c; $i++) {
			$tags[] = sprintf('<itemref idref="item-%d" linear="yes"/>', $i);
		}

		return implode("\n", $tags);
	}

	/**
	 * @param bool $plain
	 * @return array
	 */
	private function getItems($plain = true) {
		$items = $this->items;
		if ($plain) {
			$items = array_merge($items['pre'], $items['main'], $items['post']);
		}

		return $items;
	}

	/**
	 * @return string
	 */
	public function getCoverFileName() {
		return "$this->mainDir/cover";
	}

	/**
	 * @return string
	 */
	public function getBackCoverFileName() {
		return "$this->mainDir/back-cover";
	}

	/**
	 * @param bool $full
	 * @return string
	 */
	public function getImagesDir($full = true) {
		return $full ? "$this->mainDir/$this->imagesDir" : $this->imagesDir;
	}

	public function getCoverPageFile() {
		return $this->getCoverPageFileByName('cover', 'Корица');
	}

	public function getBackCoverPageFile() {
		return $this->getCoverPageFileByName('back-cover', 'Задна корица');
	}

	/**
	 * @param string $key
	 * @param string $title
	 * @return array
	 */
	private function getCoverPageFileByName($key, $title) {
		$content = $this->getCoverPageFileContent($key, $title);
		if ( empty($content) ) {
			return null;
		}

		return [
			'name'    => "$this->mainDir/$key-page.xhtml",
			'content' => $this->getXhtmlContent($content),
			'title'   => $title,
		];
	}

	/**
	 * @param string $key
	 * @param string $alt
	 * @return string
	 */
	private function getCoverPageFileContent($key, $alt) {
		if ( ! isset($this->files[$key]) ) {
			return '';
		}

		list($cover) = $this->files[$key];
		return sprintf('<div id="cover-page" class="standalone"><img src="%s" alt="%s"/></div>', $cover, $alt);
	}

	public function getTitlePageFile() {
		return [
			'name'    => "$this->mainDir/$this->titlePageFileName",
			'content' => $this->getXhtmlContent($this->getTitlePageFileContent()),
			'title'   => 'Заглавна страница',
		];
	}

	private function getTitlePageFileContent() {
		if ( ($series = $this->obj->getPlainSeriesInfo()) ) {
			$series = sprintf('<p class="series-info">%s</p>', $series);
		}

		if ( ($translator = $this->obj->getPlainTranslationInfo()) ) {
			$translator = sprintf('<p class="translator-info">%s</p>', $translator);
		}

		return $this->getTemplate($this->titlePageFileName, [
			'AUTHOR'          => $this->obj->getAuthorNamesString(),
			'TITLE'           => str_replace(' — ', '<br/>', htmlspecialchars($this->obj->getTitles())),
			'SERIES-INFO'     => $series,
			'TRANSLATOR-INFO' => $translator,
		]);
	}

	public function getCreditsFile() {
		return [
			'name'    => "$this->mainDir/$this->creditsPageFileName",
			'content' => $this->getXhtmlContent($this->getCreditsFileContent()),
			'title'   => 'Заслуги',
		];
	}

	private function getCreditsFileContent() {
		return $this->getTemplate($this->creditsPageFileName);
	}

	public function getAnnotation() {
		$xhtml = $this->obj->getAnnotationAsXhtml($this->getImagesDir(false));
		if ( empty($xhtml) ) {
			return null;
		}

		$title = 'Анотация';
		return [
			'name'    => "$this->mainDir/annotation.xhtml",
			'content' => $this->getXhtmlContent($xhtml, $title),
			'title'   => $title,
		];
	}

	public function getExtraInfo() {
		$xhtml = $this->obj->getExtraInfoAsXhtml($this->getImagesDir(false));
		if ( empty($xhtml) ) {
			return null;
		}

		$title = 'Допълнителна информация';
		return [
			'name'    => "$this->mainDir/info.xhtml",
			'content' => $this->getXhtmlContent($xhtml, $title),
			'title'   => $title,
		];
	}

	/**
	 * @param string $title
	 */
	public function getXhtmlContent($content, $title = null) {
		if ( is_null($title) ) {
			$title = $this->obj->getTitles();
		} else {
			$title .= ' | ' . $this->obj->getTitle();
		}

		return $this->getTemplate('layout.xhtml', [
			'LANG'    => $this->obj->getLang(),
			'CSS'     => $this->cssFileName,
			'TITLE'   => htmlspecialchars($title),
			'CONTENT' => $this->fixContent($content),
		]);
	}

	/**
	 * @param string $template  File name
	 * @param array  $data      Key-value pairs for replacement
	 */
	private function getTemplate($template, $data = []) {
		$file = $this->templateDir . '/'. $template;
		if ( ! file_exists($file) ) {
			throw new \Exception("$file does not exist.");
		}

		foreach ($data as $k => $v) {
			$data['{'.$k.'}'] = $v;
			unset($data[$k]);
		}

		$content = file_get_contents($file);
		return empty($data) ? $content : strtr($content, $data);
	}

	/**
	 * @param string $file
	 */
	public function addCover($file) {
		$this->addFile('cover', $file);
	}

	/**
	 * @param string $file
	 */
	public function addBackCover($file) {
		$this->addFile('back-cover', $file);
	}

	/**
	 * @param string $id
	 * @param string $href
	 * @param string $mimeType
	 */
	public function addFile($id, $href, $mimeType = null) {
		$href = str_replace("$this->mainDir/", '', $href);
		if (is_null($mimeType)) {
			$mimeType = File::guessMimeType($href);
		}
		$this->files[$id] = [$href, $mimeType];
	}

	/**
	 * @param string $href
	 * @param string $title
	 * @param string $type
	 */
	public function addItem($href, $title = '', $type = 'main') {
		$href = str_replace("$this->mainDir/", '', $href);
		$this->items[$type][$href] = $title;
	}

	/**
	 * @param string $id
	 * @param bool $full
	 */
	public function getItemFileName($id, $full = true) {
		return $full
			? sprintf('%s/chapter-%s.xhtml', $this->mainDir, $id)
			: sprintf('chapter-%s.xhtml', $id);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function fixContent($content) {
		$content = $this->fixChitankaLinks($content);
		$content = $this->removeImageLinks($content);
		return $content;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function fixChitankaLinks($content) {
		return preg_replace('/href="(\d+)"/', 'href="http://chitanka.info/text/$1"', $content);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function removeImageLinks($content) {
		$content = preg_replace('!<a [^>]+>(<img [^>]+>)</a>!', '$1', $content);
		$content = preg_replace('!<a href="[^"]+" class="zoom"[^]]+>(<span class="image-title">[^<]+</span>)</a>!U', '$1', $content);
		return $content;
	}
}
