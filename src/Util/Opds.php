<?php namespace App\Util;

class Opds {

	public static function normalizeContent($content) {
		$normalizedContent = $content;
		$normalizedContent = strtr($normalizedContent, [
			"\t" => ' ',
			"\n" => ' ',
		]);
		$normalizedContent = preg_replace('/  +/', ' ', $normalizedContent);
		$normalizedContent = preg_replace('/> </', ">\n<", $normalizedContent);
		$normalizedContent = strtr($normalizedContent, [
			'> ' => '>',
			' <' => '<',
		]);
		return $normalizedContent;
	}
}
