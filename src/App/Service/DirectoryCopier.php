<?php namespace App\Service;

class DirectoryCopier {

	/**
	 * @param string $sourceDir
	 * @param string $destDir
	 */
	public function copy($sourceDir, $destDir) {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST);
		foreach ($iterator as $item) {
			$dest = $destDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
			if ($item->isDir()) {
				if ( ! file_exists($dest)) {
					mkdir($dest);
				}
			} else {
				copy($item, $dest);
			}
		}
	}

}
