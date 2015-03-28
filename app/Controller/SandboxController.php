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
			$htmlContent = $converter->convert()->getContent();
		}

		return [
			'image_dir' => $imageDir,
			'content' => $content,
			'html_content' => $htmlContent,
		];
	}

}
