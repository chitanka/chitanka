<?php namespace App\Listener;

use App\Entity\EntityManager;
use App\Util\Opds;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;

class KernelListener implements EventSubscriberInterface {

	public static $customResponseFormats = array(
		'osd' => 'application/opensearchdescription+xml',
		'suggest' => 'application/x-suggestions+json',
	);

	public static function getSubscribedEvents() {
		return array(
			KernelEvents::REQUEST => 'onKernelRequest',
			KernelEvents::RESPONSE => 'onKernelResponse',
			KernelEvents::CONTROLLER => 'onKernelController',
			KernelEvents::VIEW => 'onKernelView',
		);
	}

	/** @var \Twig_Environment */
	private $twig;
	private $em;
	private $controller;
	private $contentDir;
	private $useHttpCache;
	private $debug;

	public function __construct(\Twig_Environment $twig, EntityManager $em, $contentDir, $useHttpCache, $debug) {
		$this->twig = $twig;
		$this->em = $em;
		$this->contentDir = $contentDir;
		$this->useHttpCache = $useHttpCache;
		$this->debug = $debug;
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

	public function onKernelController(FilterControllerEvent $event) {
		$this->controller = $event->getController();
	}

	/**
	 * @param GetResponseForControllerResultEvent $event
	 */
	public function onKernelView(GetResponseForControllerResultEvent $event) {
		$request = $event->getRequest();
		$response = $event->getResponse() ?: new Response();
		$params = $event->getControllerResult();
		$params += $this->getExtraParamsForFormat($request->getRequestFormat()) + array(
			'navlinks' => $this->renderLayoutComponent('sidebar-menu', 'App::navlinks.html.twig'),
			'footer_links' => $this->renderLayoutComponent('footer-menu', 'App::footer_links.html.twig'),
			'current_route' => $request->attributes->get('_route'),
			'environment' => $this->debug ? 'dev' : 'prod',
			'ajax' => $request->isXmlHttpRequest(),
			'_template' => null,
			'_status' => null,
			'_cache' => $this->useHttpCache ? 86400/*24 hours*/ : 0,
		);

		$template = $params['_template'] ?: $this->createTemplateReference($this->controller, $request)->getLogicalName();
		$response->setContent($this->twig->render($template, $params));
		if ($params['_cache']) {
			$response->setSharedMaxAge($params['_cache']);
			$response->setMaxAge($params['_cache']);
		}
		if ($params['_status']) {
			$response->setStatusCode($params['_status']);
		}
		$event->setResponse($response);
	}

	private function getExtraParamsForFormat($format) {
		switch ($format) {
			case 'opds':
				$textsUpdatedAt = $this->em->getTextRevisionRepository()->getMaxDate();
				$booksUpdatedAt = $this->em->getBookRevisionRepository()->getMaxDate();
				return array(
					'texts_updated_at' => $textsUpdatedAt,
					'books_updated_at' => $booksUpdatedAt,
					'updated_at' => max($textsUpdatedAt, $booksUpdatedAt),
				);
			case 'osd':
				return array(
					'_cache' => 31536000, // an year
				);
		}
		return array();
	}

	private function createTemplateReference(array $controller, Request $request, $engine = 'twig') {
		$controllerClass = get_class($controller[0]);
		if (!preg_match('/Controller\\\\(.+)Controller$/', $controllerClass, $matchController)) {
			throw new \InvalidArgumentException("The '{$controllerClass}' class does not look like a controller class. It must be in a 'Controller' sub-namespace and the class name must end with 'Controller')");
		}
		if (!preg_match('/^(.+)Action$/', $controller[1], $matchAction)) {
			throw new \InvalidArgumentException("The '{$controller[1]}' method does not look like an action method as it does not end with Action");
		}
		return new TemplateReference('App', $matchController[1], \Doctrine\Common\Util\Inflector::tableize($matchAction[1]), $request->getRequestFormat(), $engine);
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

	/**
	 * @param string $wikiPage
	 * @param string $fallbackTemplate
	 */
	private function renderLayoutComponent($wikiPage, $fallbackTemplate) {
		$wikiPagePath = "{$this->contentDir}/wiki/special/{$wikiPage}.html";
		if (file_exists($wikiPagePath)) {
			list(, $content) = explode("\n\n", file_get_contents($wikiPagePath));
			return $content;
		}
		return $this->twig->render($fallbackTemplate);
	}

}
