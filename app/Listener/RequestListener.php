<?php namespace App\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;

class RequestListener {

	/**
	 * @param GetResponseEvent $event
	 */
	public function onKernelRequest(GetResponseEvent $event) {
		$this->registerCustomResponseFormats($event->getRequest());
	}

	/**
	 * @param Request $request
	 */
	protected function registerCustomResponseFormats(Request $request) {
		$request->setFormat('osd', 'application/opensearchdescription+xml');
		$request->setFormat('suggest', 'application/x-suggestions+json');
	}
}
