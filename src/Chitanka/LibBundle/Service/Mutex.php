<?php
namespace Chitanka\LibBundle\Service;

class Mutex
{
	private $directory;
	private $id;

	public function __construct($directory, $id = null)
	{
		$this->directory = $directory;
		$this->id = $id;
	}

	public function acquireLock()
	{
		if (file_exists($this->getLockFile())) {
			return false;
		}
		if (touch($this->getLockFile())) {
			register_shutdown_function(array($this, 'releaseLock'));
		}
		return true;
	}

	public function releaseLock()
	{
		if (file_exists($this->getLockFile())) {
			return unlink($this->getLockFile());
		}
		return true;
	}

	private function getLockFile()
	{
		return "$this->directory/$this->id.lock";
	}
}
