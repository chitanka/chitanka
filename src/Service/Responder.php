<?php namespace App\Service;

use App\Entity\EntityManager;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Responder {

	const FORMAT_JSON = 'json';
	const FORMAT_OPDS = 'opds';
	const FORMAT_OSD = 'osd';
	const FORMAT_SUGGEST = 'suggest';

	public static $customResponseFormats = [
		self::FORMAT_OPDS => 'application/atom+xml',
		self::FORMAT_OSD => 'application/xml', //'application/opensearchdescription+xml',
		self::FORMAT_SUGGEST => 'application/x-suggestions+json',
	];

	/** @var \Twig_Environment */
	private $twig;
	private $em;
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
	 * @param Request $request
	 */
	public function registerCustomResponseFormats(Request $request) {
		foreach (self::$customResponseFormats as $extension => $mimeType) {
			$request->setFormat($extension, $mimeType);
		}
	}

	/**
	 *
	 * @param Request $request
	 * @param callable $controller A callable controller action as an array
	 * @param array $params
	 * @return Response
	 */
	public function createResponse(Request $request, $controller, $params) {
		if ($params === null) {
			$params = [];
		}
		$params += $this->getExtraParamsForFormat($request->getRequestFormat()) + [
			'_cache' => $this->useHttpCache ? 3600/*1 hour*/ : 0,
			'_status' => null,
			'_type' => null,
		];
		if (in_array($request->getRequestFormat(), [self::FORMAT_JSON, self::FORMAT_SUGGEST])) {
			unset($params['_cache'], $params['_status'], $params['_type']);
			$response = new JsonResponse($params);
			//$response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);
		} else {
			$response = new Response();
			if (isset($params['_content'])) {
				$content = $params['_content'];
			} else {
				$params += $this->createExtraTemplateParams($request);
				$template = $params['_template'] ?: $this->createTemplateReference($controller, $request)->getLogicalName();
				$content = $this->twig->render($template, $params);
			}
			$response->setContent($content);
		}
		if (isset($params['_cache'])) {
			$response->setSharedMaxAge($params['_cache']);
		}
		if (isset($params['_status'])) {
			$response->setStatusCode($params['_status']);
		}
		if (isset($params['_type'])) {
			$response->headers->set('Content-Type', $params['_type']);
		}
		return $response;
	}

	/**
	 * @param Request $request
	 * @return array
	 */
	private function createExtraTemplateParams(Request $request) {
		return [
			'navlinks' => $this->renderLayoutComponent('sidebar-menu', 'App::navlinks.html.twig'),
			'footer_links' => $this->renderLayoutComponent('footer-menu', 'App::footer_links.html.twig'),
			'current_route' => $request->attributes->get('_route'),
			'environment' => $this->debug ? 'dev' : 'prod',
			'ajax' => $request->isXmlHttpRequest(),
			'_template' => null,
		];
	}

	/**
	 * @param string $format
	 * @return array
	 */
	private function getExtraParamsForFormat($format) {
		switch ($format) {
			case self::FORMAT_OPDS:
				$textsUpdatedAt = $this->em->getTextRevisionRepository()->getMaxDate();
				$booksUpdatedAt = $this->em->getBookRevisionRepository()->getMaxDate();
				return [
					'texts_updated_at' => $textsUpdatedAt,
					'books_updated_at' => $booksUpdatedAt,
					'updated_at' => max($textsUpdatedAt, $booksUpdatedAt),
				];
			case self::FORMAT_OSD:
				return [
					'_cache' => 31536000, // an year
				];
		}
		return [];
	}

	/**
	 * @param callable $controller A callable controller action as an array
	 * @param Request $request
	 * @param string $engine
	 * @return TemplateReference
	 * @throws \InvalidArgumentException
	 */
	private function createTemplateReference($controller, Request $request, $engine = 'twig') {
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
	 * @param string $wikiPage
	 * @param string $fallbackTemplate
	 * @return string
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
