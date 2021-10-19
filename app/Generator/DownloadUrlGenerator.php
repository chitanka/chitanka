<?php namespace App\Generator;

class DownloadUrlGenerator {

	public function generateConverterUrl(string $baseUrl, string $epubUrl): string {
		$queryDelimiter = strpos($baseUrl, '?') === false ? '?' : '&';
		return $baseUrl . $queryDelimiter . http_build_query(['epub' => $epubUrl]);
	}
}
