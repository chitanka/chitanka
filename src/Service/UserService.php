<?php namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Container;

class UserService {
	private $user;
	private $contentDir;

	public function __construct(User $user, $contentDir) {
		$this->user = $user;
		$this->contentDir = $contentDir;
	}

	public function getUserPageContent() {
		$filename = $this->buildUserPageContentFilename();
		$content = '';

		if (file_exists($filename)) {
			$content = file_get_contents($filename);
		}

		return $content;
	}

	public function saveUserPageContent($content) {
		file_put_contents($this->buildUserPageContentFilename(), $content);
	}

	private function buildUserPageContentFilename() {
		return "{$this->contentDir}/user/{$this->user->getId()}";
	}
}