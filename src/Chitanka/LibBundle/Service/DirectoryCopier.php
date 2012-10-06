<?php
namespace Chitanka\LibBundle\Service;

class DirectoryCopier
{

	public function copy($sourceDir, $destDir)
	{
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST);
		foreach ($iterator as $item) {
			$dest = $destDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
			if ($item->isDir()) {
				mkdir($dest);
			} else {
				copy($item, $dest);
			}
		}
	}

}
