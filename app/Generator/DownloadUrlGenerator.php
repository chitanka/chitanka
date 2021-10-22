<?php namespace App\Generator;

class DownloadUrlGenerator {

	public function generateConverterUrl(string $epubUrl, string $targetFormat, array $mirrors): string {
		return $this->pickMirror($mirrors) . '/converter.php?' . http_build_query(['out' => $targetFormat, 'epub' => $epubUrl]);
	}

	private function pickMirror(array $mirrors): string {
		if (empty($mirrors)) {
			return '';
		}
		return $mirrors[array_rand($mirrors)];
	}
}
