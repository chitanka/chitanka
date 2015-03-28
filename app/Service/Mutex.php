<?php namespace App\Service;

class Mutex {
	const EXPIRATION_TIME = 86400; // 24 hours
	private $directory;
	private $id;

	public function __construct($directory, $id = null) {
		$this->directory = $directory;
		$this->id = $id;
	}

	public function acquireLock($expirationTime = self::EXPIRATION_TIME) {
		if ($this->hasValidLockFile($expirationTime)) {
			return false;
		}
		if (touch($this->getLockFile())) {
			register_shutdown_function([$this, 'releaseLock']);
		}
		return true;
	}

	public function releaseLock() {
		if (file_exists($this->getLockFile())) {
			return unlink($this->getLockFile());
		}
		return true;
	}

	/**
	 * @param int $expirationTime
	 */
	private function hasValidLockFile($expirationTime) {
		return file_exists($this->getLockFile()) && (time() - filemtime($this->getLockFile()) < $expirationTime);
	}

	/**
	 * @return string
	 */
	private function getLockFile() {
		return "$this->directory/$this->id.lock";
	}
}
