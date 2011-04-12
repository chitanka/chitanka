<?php
namespace Chitanka\LibBundle\Legacy;

namespace Chitanka\LibBundle\Util\String;
namespace Chitanka\LibBundle\Util\File;

class EpubFile
{
	protected $mainDir = 'OPS';
	private
		$text,
		$files = array(),
		$items = array(
			'pre' => array(),
			'main' => array(),
			'post' => array(),
		),
		$curPlayOrder = 0;


	public function __construct($text)
	{
		$this->obj = $text;

		$this->containerFileName = 'container.xml';
		$this->contentFileName = 'content.opf';
		$this->tocFileName = 'toc.ncx';
		$this->cssFileName = 'css/main.css';
		$this->titlePageFileName = 'title-page.xhtml';
		$this->creditsPageFileName = 'credits.xhtml';

		$this->imagesDir = 'images';
		$this->templateDir = BASEDIR . '/include/templates-epub';

		$this->addFile('ncx', $this->tocFileName, 'application/x-dtbncx+xml');
		$this->addFile('stylesheet', $this->cssFileName, 'text/css');
	}

	public function getMimetypeFile()
	{
		return array(
			'name'    => 'mimetype',
			'content' => 'application/epub+zip',
		);
	}


	public function getContainerFile()
	{
		return array(
			'name'    => "META-INF/$this->containerFileName",
			'content' => $this->getContainerFileContent(),
		);
	}

	public function getContainerFileContent()
	{
		return $this->getTemplate($this->containerFileName, array(
			'ROOTFILE' => "$this->mainDir/$this->contentFileName"
		));
	}


	public function getCssFile()
	{
		return array(
			'name'    => "$this->mainDir/$this->cssFileName",
			'content' => $this->getCssFileContent(),
		);
	}

	public function getCssFileContent()
	{
		return $this->getTemplate($this->cssFileName);
	}


	public function getTocFile()
	{
		return array(
			'name'    => "$this->mainDir/$this->tocFileName",
			'content' => $this->getTocFileContent(),
		);
	}

	public function getTocFileContent()
	{
		return $this->getTemplate($this->tocFileName, array(
			'LANG'       => $this->obj->getLang(),
			'ID'         => $this->obj->getDocId(),
			'DEPTH'      => $this->obj->getMaxHeadersDepth(),
			'EPUBLISHER' => $this->getPublisher(),
			'TITLE'      => htmlspecialchars($this->obj->getTitles()),
			'AUTHOR'     => $this->obj->getAuthor(),
			'NAVPOINTS'  => $this->getNavPointTags(),
		));
	}


	public function getNavPointTags()
	{
		return $this->getPreNavPointTags() . $this->getMainNavPointTags() . $this->getPostNavPointTags();
	}

	protected function getMainNavPointTags()
	{
		$headers = strtr($this->obj->getHeadersAsNestedXml(false), array(
			'</li>' => '</navPoint>',
			'<ul>' => '', '</ul>' => '',
		));

		$navPoint = $this->curPlayOrder;
		while (preg_match('/<li level=(\d+)>/', $headers, $matches)) {
			$order = $this->curPlayOrder + ($matches[1] + 1);
			$repl = sprintf('<navPoint class="chapter" id="navpoint-%d" playOrder="%d"><navLabel><text>$2</text></navLabel><content src="%s"/>',
				++$navPoint, $order, $this->getItemFileName('$1', false));
			$headers = preg_replace('/<li level=(\d+)>([^<]+)/', $repl, $headers, 1);
		}
		$this->curPlayOrder = $order;

		return $headers;
	}

	protected function getPreNavPointTags()
	{
		return $this->getExtraNavPointTags('pre');
	}

	protected function getPostNavPointTags()
	{
		return $this->getExtraNavPointTags('post');
	}

	protected function getExtraNavPointTags($type)
	{
		$tags = array();
		foreach ($this->items[$type] as $href => $title) {
			$this->curPlayOrder++;
			$tags[] = sprintf('<navPoint class="chapter" id="navpoint-ext-%d" playOrder="%d"><navLabel><text>%s</text></navLabel><content src="%s"/></navPoint>',
				$this->curPlayOrder, $this->curPlayOrder, $title, $href);
		}

		return implode("\n", $tags);
	}


	public function getContentFile()
	{
		return array(
			'name'    => "$this->mainDir/$this->contentFileName",
			'content' => $this->getContentFileContent(),
		);
	}

	public function getContentFileContent()
	{
		return $this->getTemplate($this->contentFileName, array(
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
		));
	}


	public function getPublisher()
	{
		return 'Моята библиотека (http://chitanka.info)';
	}


	public function getAuthorTags()
	{
		$tags = array();

		if ($this->obj->getType() == 'book') {
			$tags = $this->getBookAuthorTags();
		} else {
			$tags = $this->getTextAuthorTags();
		}

		foreach ($this->obj->getTranslators() as $translator) {
			$tags[] = self::getCreatorTag($translator['name'], 'trl');
		}

		return implode("\n", $tags);
	}

	public function getTextAuthorTags()
	{
		$tags = array();
		foreach ($this->obj->getAuthors() as $author) {
			$tags[] = self::getCreatorTag($author['name'], 'aut');
		}

		return $tags;
	}

	public function getBookAuthorTags()
	{
		$tags = array();
		foreach ($this->obj->getMainAuthors() as $author) {
			$tags[] = self::getCreatorTag($author['name'], 'aut');
		}

		foreach ($this->obj->getAuthorsBy('intro') as $author) {
			$tags[] = self::getCreatorTag($author['name'], 'aui');
		}

		foreach ($this->obj->getAuthorsBy('outro') as $author) {
			$tags[] = self::getCreatorTag($author['name'], 'aft');
		}

		return $tags;
	}


	public static function getCreatorTag($name, $role)
	{
		return sprintf('<dc:creator opf:file-as="%s" opf:role="%s">%s</dc:creator>',
			String::getMachinePersonName($name), $role, $name);
	}


	public function getSubjectTags()
	{
		$tags = array();
		foreach ($this->obj->getLabels() as $label) {
			$tags[] = "<dc:subject>$label</dc:subject>";
		}

		return implode("\n", $tags);
	}

	public function getContributorTags()
	{
		return '';
	}


	public function getDateTags()
	{
		$tags = array();
		if ( ($year = $this->obj->getYear()) > 0 ) {
			$tags[] = sprintf('<dc:date opf:event="original-publication">%04d</dc:date>', $year);
		}

		if ($this->obj->isTranslation() && ($year = $this->obj->getTransYear())) {
			$tags[] = sprintf('<dc:date opf:event="translation-publication">%s</dc:date>', $year);
		}

		return implode("\n", $tags);
	}

	public function getRevisionTags()
	{
		$tags = array();
		foreach ($this->obj->getHistoryInfo() as $rev) {
			$tags[] = sprintf('<dc:date opf:event="chitanka-%s">%s</dc:date>',
				htmlspecialchars($rev['comment']),
				preg_replace('/(\S+) \S+/', '$1', $rev['date']));
		}

		return implode("\n", $tags);
	}

	public function getManifestItemTags()
	{
		$tags = array();

		foreach ($this->files as $id => $data) {
			list($href, $mime) = $data;
			$tags[] = sprintf('<item id="%s" href="%s" media-type="%s"/>', $id, $href, $mime);
		}

		foreach (array_keys($this->getItems()) as $i => $href) {
			$tags[] = sprintf('<item id="item-%d" href="%s" media-type="application/xhtml+xml"/>', ($i+1), $href);
		}

		return implode("\n", $tags);
	}

	public function getSpineItems()
	{
		$tags = array();

		for ($i = 1, $c = count($this->getItems()); $i <= $c; $i++) {
			$tags[] = sprintf('<itemref idref="item-%d" linear="yes"/>', $i);
		}

		return implode("\n", $tags);
	}


	public function getItems($plain = true)
	{
		$items = $this->items;
		if ($plain) {
			$items = array_merge($items['pre'], $items['main'], $items['post']);
		}

		return $items;
	}


	public function getCoverFileName()
	{
		return "$this->mainDir/cover";
	}

	public function getBackCoverFileName()
	{
		return "$this->mainDir/back-cover";
	}

	public function getImagesDir($full = true)
	{
		return $full ? "$this->mainDir/$this->imagesDir" : $this->imagesDir;
	}


	public function getCoverPageFile()
	{
		return $this->_getCoverPageFile('cover', 'Корица');
	}

	public function getBackCoverPageFile()
	{
		return $this->_getCoverPageFile('back-cover', 'Задна корица');
	}

	protected function _getCoverPageFile($key, $title)
	{
		$content = $this->_getCoverPageFileContent($key, $title);
		if ( empty($content) ) {
			return null;
		}

		return array(
			'name'    => "$this->mainDir/$key-page.xhtml",
			'content' => $this->getXhtmlContent($content),
			'title'   => $title,
		);
	}

	protected function _getCoverPageFileContent($key, $alt)
	{
		if ( ! isset($this->files[$key]) ) {
			return '';
		}

		list($cover) = $this->files[$key];
		return sprintf('<div id="cover-page" class="standalone"><img src="%s" alt="%s"/></div>', $cover, $alt);
	}


	public function getTitlePageFile()
	{
		return array(
			'name'    => "$this->mainDir/$this->titlePageFileName",
			'content' => $this->getXhtmlContent($this->getTitlePageFileContent()),
			'title'   => 'Заглавна страница',
		);
	}

	public function getTitlePageFileContent()
	{
		if ( ($series = $this->obj->getPlainSeriesInfo()) ) {
			$series = sprintf('<p class="series-info">%s</p>', $series);
		}

		if ( ($translator = $this->obj->getPlainTranslationInfo()) ) {
			$translator = sprintf('<p class="translator-info">%s</p>', $translator);
		}

		return $this->getTemplate($this->titlePageFileName, array(
			'AUTHOR'          => $this->obj->getAuthor(),
			'TITLE'           => str_replace(' — ', '<br/>', htmlspecialchars($this->obj->getTitles())),
			'SERIES-INFO'     => $series,
			'TRANSLATOR-INFO' => $translator,
		));
	}


	public function getCreditsFile()
	{
		return array(
			'name'    => "$this->mainDir/$this->creditsPageFileName",
			'content' => $this->getXhtmlContent($this->getCreditsFileContent()),
			'title'   => 'Заслуги',
		);
	}

	public function getCreditsFileContent()
	{
		return $this->getTemplate($this->creditsPageFileName);
	}


	public function getAnnotation()
	{
		$xhtml = $this->obj->getAnnotationAsXhtml($this->getImagesDir(false));
		if ( empty($xhtml) ) {
			return null;
		}

		$title = 'Анотация';
		return array(
			'name'    => "$this->mainDir/annotation.xhtml",
			'content' => $this->getXhtmlContent($xhtml, $title),
			'title'   => $title,
		);
	}


	public function getExtraInfo()
	{
		$xhtml = $this->obj->getExtraInfoAsXhtml($this->getImagesDir(false));
		if ( empty($xhtml) ) {
			return null;
		}

		$title = 'Допълнителна информация';
		return array(
			'name'    => "$this->mainDir/info.xhtml",
			'content' => $this->getXhtmlContent($xhtml, $title),
			'title'   => $title,
		);
	}


	public function getXhtmlContent($content, $title = null)
	{
		if ( is_null($title) ) {
			$title = $this->obj->getTitles();
		} else {
			$title .= ' | ' . $this->obj->getTitle();
		}

		return $this->getTemplate('layout.xhtml', array(
			'LANG'    => $this->obj->getLang(),
			'CSS'     => $this->cssFileName,
			'TITLE'   => htmlspecialchars($title),
			'CONTENT' => $this->fixContent($content),
		));
	}

	/**
	* @param string $template  File name
	* @param array  $data      Key-value pairs for replacement
	*/
	protected function getTemplate($template, $data = array())
	{
		$file = $this->templateDir . '/'. $template;
		if ( ! file_exists($file) ) {
			return false;
		}

		foreach ($data as $k => $v) {
			$data['{'.$k.'}'] = $v;
			unset($data[$k]);
		}

		$content = file_get_contents($file);
		return empty($data) ? $content : strtr($content, $data);
	}


	public function addCover($file)
	{
		$this->addFile('cover', $file);
	}

	public function addBackCover($file)
	{
		$this->addFile('back-cover', $file);
	}

	public function addFile($id, $href, $mimeType = null)
	{
		$href = str_replace("$this->mainDir/", '', $href);
		if (is_null($mimeType)) {
			$mimeType = File::guessMimeType($href);
		}
		$this->files[$id] = array($href, $mimeType);
	}


	public function addItem($href, $title = '', $type = 'main')
	{
		$href = str_replace("$this->mainDir/", '', $href);
		$this->items[$type][$href] = $title;
	}


	public function getItemFileName($id, $full = true)
	{
		return $full
			? sprintf('%s/chapter-%s.xhtml', $this->mainDir, $id)
			: sprintf('chapter-%s.xhtml', $id);
	}


	public function fixContent($content)
	{
		$content = $this->fixChitankaLinks($content);
		$content = $this->removeImageLinks($content);

		return $content;
	}

	public function fixChitankaLinks($content)
	{
		return preg_replace('/href="(\d+)"/', 'href="http://chitanka.info/text/$1"', $content);
	}

	public function removeImageLinks($content)
	{
		$content = preg_replace('!<a [^>]+>(<img [^>]+>)</a>!', '$1', $content);
		$content = preg_replace('!<a href="[^"]+" class="zoom"[^]]+>(<span class="image-title">[^<]+</span>)</a>!U', '$1', $content);

		return $content;
	}


	public function relocateUrls($content, $target)
	{
		$target = str_replace("$this->mainDir/", '', $target);
		return strtr($content, array(
			'href="#' => sprintf('href="%s#', $target)
		));
	}
}
