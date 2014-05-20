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
	protected function addNotice($notice) {
		$this->addFlashMessage('notice', $notice);
	}

	/**
	 * Add a flash message
	 * @param string $type
	 * @param string $message
	 */
	protected function addMessage($type, $message) {
		$this->bag->add($type, $message);
	}

	/**
	 * @return bool
	 */
	protected function hasMessages() {
		return $this->bag->peekAll() !== array();
	}

}
