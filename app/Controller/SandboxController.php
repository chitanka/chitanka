<?php namespace App\Controller;

use Sfblib\SfbToHtmlConverter;
use Symfony\Component\HttpFoundation\Request;

class SandboxController extends Controller {

	public function indexAction(Request $request) {
		$imageDir = $request->get('image_dir');
		$content = $request->get('content');

		$htmlContent = null;
		if ($content) {
			$converter = new SfbToHtmlConverter($content, $imageDir);
			if ($request->get('is_gamebook')) {
				// recognize section links
				$converter->addRegExpPattern('/#(\d+)/', '<a href="#l-$1" class="ep" title="Към епизод $1">$1</a>');
			}
			$htmlContent = $converter->convert()->getContent();
		}
		return [
			'image_dir' => $imageDir,
			'content' => $content,
			'html_content' => $htmlContent,
			'js_extra' => ['text'],
		];
	}

}
