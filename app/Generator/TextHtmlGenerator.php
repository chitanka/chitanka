<?php namespace App\Generator;

use App\Entity\Text;
use App\Service\ContentService;
use Sfblib\SfbToHtmlConverter;

class TextHtmlGenerator {

	/**
	 * @param Text $text
	 * @param string $imgDirPrefix
	 * @param int $part
	 * @param int $objCount
	 * @return string
	 */
	public function generateHtml(Text $text, $imgDirPrefix = '', $part = 1, $objCount = 0) {
		$imgDir = $imgDirPrefix . ContentService::getContentFilePath('img', $text->getId());
		$conv = new SfbToHtmlConverter($text->getRawContent(true), $imgDir);

		// TODO do not hardcode it; inject it through parameter
		$internalLinkTarget = "/text/{$text->getId()}/0";

		if ($objCount) {
			$conv->setObjectCount($objCount);
		}
		$header = $text->getHeaderByNr($part);
		if ($header) {
			$conv->setStartPosition($header->getFpos());
			$conv->setMaxLineCount($header->getLinecnt());
		} else {
			$internalLinkTarget = '';
		}
		if ($text->isGamebook()) {
			// recognize section links
			$conv->addRegExpPattern('/#(\d+)/', '<a href="#l-$1" class="ep" title="Към епизод $1">$1</a>');
		}
		$conv->setInternalLinkTarget($internalLinkTarget);

		return $conv->convert()->getContent();
	}
}
