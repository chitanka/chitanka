<?php

namespace Chitanka\LibBundle\Controller;

class SandboxController extends Controller
{
	public function indexAction()
	{
		$request = $this->get('request')->request;
		$image_dir = $request->get('image_dir');
		$content = $request->get('content');
		$this->view = compact('image_dir', 'content');

		if ($content) {
			$converter = new \Sfblib_SfbToHtmlConverter($content, $image_dir);
			$this->view['html_content'] = $converter->convert()->getContent();
		}

		return $this->display('index');
	}

}
