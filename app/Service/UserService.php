<?php namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Container;

class UserService {
	private $user;
	private $container;

	public function __construct(User $user, Container $container) {
		$this->user = $user;
		$this->container = $container;
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
		return $this->container->getParameter('kernel.root_dir') .
			'/../web/content/user' .'/'. $this->user->getId();
	}
}