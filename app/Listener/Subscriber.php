<?php namespace App\Listener;

use App\Util\Opds;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Subscriber implements EventSubscriberInterface {

	public static $customResponseFormats = array(
		'osd' => 'application/opensearchdescription+xml',
		'suggest' => 'application/x-suggestions+json',
	);

	public static function getSubscribedEvents() {
		return array(
			KernelEvents::REQUEST => 'onKernelRequest',
			KernelEvents::RESPONSE => 'onKernelResponse',
		);
	}

	/**
	 * @param GetResponseEvent $event
	 */
	public function onKernelRequest(GetResponseEvent $event) {
		$this->registerCustomResponseFormats($event->getRequest());
	}

	/**
	 * @param FilterResponseEvent $event
	 */
	public function onKernelResponse(FilterResponseEvent $event) {
		$this->normalizeOpdsContent($event->getRequest(), $event->getResponse());
	}

	/**
	 * @param Request $request
	 */
	private function registerCustomResponseFormats(Request $request) {
		foreach (self::$customResponseFormats as $extension => $mimeType) {
			$request->setFormat($extension, $mimeType);
		}
	}

	private function normalizeOpdsContent(Request $request, Response $response) {
		if ($request->getRequestFormat() == 'opds') {
			$response->setContent(Opds::normalizeContent($response->getContent()));
		}
	}
}
