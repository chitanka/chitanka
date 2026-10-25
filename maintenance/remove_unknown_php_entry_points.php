<?php namespace chitanka;

function disableDisallowedPhpFiles(string $directory, array $allowedFiles): void {
	$doneLockFile = dirname(__DIR__).'/var/cache/disable_disallowed_php_files.done';
	if (file_exists($doneLockFile)) {
		return;
	}
	$lockFile = dirname(__DIR__).'/var/cache/disable_disallowed_php_files.lock';
	if (file_exists($lockFile)) {
		return;
	}
	touch($lockFile);

	$allowed = array_fill_keys($allowedFiles, true);
	$directory = rtrim($directory, DIRECTORY_SEPARATOR);

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator(
			$directory,
			\FilesystemIterator::SKIP_DOTS
		),
		\RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator as $file) {
		if (
			!$file->isFile()
			|| strtolower(substr($file->getFilename(), -4)) !== '.php'
		) {
			continue;
		}

		$relativePath = substr(
			$file->getPathname(),
			strlen($directory) + 1
		);

		if (isset($allowed[$relativePath])) {
			continue;
		}

		rename(
			$file->getPathname(),
			$file->getPathname() . '.disabled'
		);
	}

	touch($doneLockFile);
	if (file_exists($lockFile)) {
		unlink($lockFile);
	}
}
