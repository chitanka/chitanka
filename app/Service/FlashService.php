<?php namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class FlashService {

	private $bag;

	public function __construct(FlashBag $bag) {
		$this->bag = $bag;
	}

	/**
	 * Add a flash message of type 'notice'
	 * @param string $notice
	 */
	public function addNotice($notice) {
		$this->addFlashMessage('notice', $notice);
	}

	/**
	 * Add a flash message
	 * @param string $type
	 * @param string $message
	 */
	public function addMessage($type, $message) {
		$this->bag->add($type, $message);
	}

	/**
	 * @return bool
	 */
	public function hasMessages() {
		return $this->bag->peekAll() !== array();
	}

}
