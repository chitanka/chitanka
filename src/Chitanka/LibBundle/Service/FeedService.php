<?php
namespace Chitanka\LibBundle\Service;

class FeedService
{

	public function cleanup($contents)
	{
		$contents = $this->removeScriptContent($contents);
		$contents = $this->removeImageBeacons($contents);
		return $contents;
	}

	public function removeScriptContent($contents)
	{
		$dirtyContents = $cleanedContents = $contents;
		while (true) {
			$cleanedContents = preg_replace('|<\s*script[^>]*>.*<\s*/\s*script\s*>|Ums', '', $dirtyContents);
			if ($cleanedContents === $dirtyContents) {
				break;
			}
			$dirtyContents = $cleanedContents;
		}
		return $cleanedContents;
	}

	public function removeImageBeacons($contents)
	{
		$minWidthOrHeight = 4;
		return preg_replace_callback('|<\s*img [^>]+>|', function($match) use ($minWidthOrHeight) {
			foreach (explode(' ', $match[0]) as $attr) {
				if (strpos($attr, '=') === false) {
					continue;
				}
				list($name, $value) = explode('=', $attr);
				if ($name != 'width' && $name != 'height') {
					continue;
				}
				$intValue = trim($value, '\'"');
				if ($intValue < $minWidthOrHeight) {
					return '';
				}
			}
			return $match[0];
		}, $contents);
	}
}
