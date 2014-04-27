<?php namespace App\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener {
	public function onKernelRequest(GetResponseEvent $event) {
		$request = $event->getRequest();
		$request->setFormat('osd', 'application/opensearchdescription+xml');
		$request->setFormat('suggest', 'application/x-suggestions+json');
	}
}
