<?php namespace App\Controller;

use App\Entity\BaseWork;
use App\Entity\Book;
use App\Entity\User;
use App\Legacy\Setup;
use App\Service\ContentService;
use App\Service\FlashService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class Controller extends SymfonyController {

	const PARAM_SORT = 'sort';

	/** The max cache time of the response (in seconds) */
	protected $responseAge = 3600; // 1 hour

	/** @var \App\Entity\EntityManager */
	private $em;
	/** @var \App\Service\FlashService */
	private $flashes;

	/**
	 * @param string $pageName
	 * @param array $params
	 * @return Response
	 */
	protected function legacyPage($pageName, array $params = []) {
		$page = Setup::getPage($pageName, $this, $this->container);
		if ($page->redirect) {
			return $this->urlRedirect($page->redirect);
		}

		$request = $this->getRequest();
		$params += [
			'page' => $page,
			'navlinks' => $this->renderLayoutComponent('sidebar-menu', 'App::navlinks.html.twig'),
			'footer_links' => $this->renderLayoutComponent('footer-menu', 'App::footer_links.html.twig'),
			'current_route' => $request->attributes->get('_route'),
			'environment' => $this->container->get('kernel')->getEnvironment(),
			'ajax' => $request->isXmlHttpRequest(),
			'_controller' => ':legacy',
		];
		if ($page->inlineJs) {
			$params['inline_js'] = $page->inlineJs;
		}

		$response = $this->render("App:{$params['_controller']}.{$request->getRequestFormat()}.twig", $params);
		$this->setCacheStatusByResponse($response);

		return $response;
	}

	/**
	 * @param string $wikiPage
	 * @param string $fallbackTemplate
	 * @return string
	 */
	public function renderLayoutComponent($wikiPage, $fallbackTemplate) {
		$wikiPagePath = $this->container->getParameter('content_dir')."/wiki/special/$wikiPage.html";
		if (file_exists($wikiPagePath)) {
			list(, $content) = explode("\n\n", file_get_contents($wikiPagePath), 2);
			return $content;
		}
		return $this->renderView($fallbackTemplate);
	}

	/**
	 * @param string $text
	 * @param string $contentType
	 * @return array
	 */
	protected function asText($text, $contentType = 'text/plain') {
		return [
			'_content' => $text,
			'_type' => $contentType,
		];
	}

	/**
	 * @param string $content
	 * @return array
	 */
	protected function asJson($content) {
		return $this->asText(json_encode($content), 'application/json');
	}

	/**
	 * @param Response $response
	 * @return Response
	 */
	protected function setCacheStatusByResponse(Response $response) {
		if ($this->responseAge && $this->container->getParameter('use_http_cache')) {
			$response->setSharedMaxAge($this->responseAge);
		}
		return $response;
	}

	/** @return \App\Entity\EntityManager */
	public function em() {
		return $this->em ?: $this->em = $this->container->get('app.entity_manager');
	}

	/** @return User */
	public function getUser() {
		return $this->get('security.token_storage')->getToken()->getUser();
	}

	/** @return User */
	protected function getSavableUser() {
		return $this->getUser();
	}

	public function setUser($user) {
		$this->get('security.token_storage')->getToken()->setUser($user);
	}

	/**
	 * @param string $notice
	 * @return Response
	 */
	protected function redirectWithNotice($notice) {
		$this->flashes()->addNotice($notice);
		return $this->redirectToRoute('message');
	}

	/** @return \App\Service\FlashService */
	protected function flashes() {
		return $this->flashes ?: $this->flashes = new FlashService($this->get('session')->getFlashBag());
	}

//	/**
//	 * Redirects to another route.
//	 *
//	 * @param string  $route      The route pattern to redirect to
//	 * @param array   $parameters Possible parameters used by the route generation
//	 *
//	 * @return Response A Response instance
//	 */
//	public function redirectToRoute($route, array $parameters = []) {
//		$parameters['_format'] = $this->get('request')->getRequestFormat();
//		return $this->redirect($this->generateUrl($route, $parameters));
//	}

	/**
	 * Redirects to a URL.
	 *
	 * It expects a url path parameter.
	 * By default, the response status code is 301.
	 *
	 * If the url is empty, the status code will be 410.
	 * If the permanent path parameter is set, the status code will be 302.
	 *
	 * @param string  $url       The url to redirect to
	 * @param bool    $permanent Whether the redirect is permanent or not
	 *
	 * @return Response A Response instance
	 */
	public function urlRedirect($url, $permanent = false) {
		if (!$url) {
			return new Response(null, 410);
		}
		return new RedirectResponse($url, $permanent ? 301 : 302);
	}

	// TODO refactor: move to separate class
	protected function getMirrorServer() {
		$mirrorSites = $this->container->getParameter('mirror_sites');

		if ( empty($mirrorSites) ) {
			return false;
		}

		$ri = rand(1, 100);
		$curFloor = 0;
		foreach ($mirrorSites as $site => $prob) {
			$curFloor += $prob;
			if ( $ri <= $curFloor ) {
				return $site;
			}
		}

		return false; // main site
	}

	protected function getWebRoot() {
		return dirname($this->getRequest()->server->get('SCRIPT_NAME'));
	}

	protected function generateAbsoluteUrl($route, $parameters = array()) {
		return $this->generateUrl($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
	}

	public function generateUrlForLegacyCode($route, $parameters = [], $iAbsolute = false) {
		if ($iAbsolute) {
			return $this->generateAbsoluteUrl($route, $parameters);
		}
		return $this->generateUrl($route, $parameters);
	}

	public function renderViewForLegacyCode($view, array $parameters = []) {
		return $this->renderView($view, $parameters);
	}

	public function initInternalContentPath() {
		ContentService::setInternalContentPath($this->container->getParameter('content_dir'));
	}

	public function configureExtraDownloadFormats() {
		if (class_exists(BaseWork::class, false)) {
			BaseWork::$MOBI_ENABLED = $this->container->getParameter('mobi_download_enabled');
			BaseWork::$PDF_ENABLED = $this->container->getParameter('pdf_download_enabled');
		}
	}

	/** @return Request */
	private function getRequest() {
		return $this->get('request_stack')->getMasterRequest();
	}

	protected function readOptionOrParam(string $option, string $namespace = 'misc') {
		$fullOptionName = "$namespace.$option";
		$user = $this->getUser();
		$storedOption = $user->option($fullOptionName);
		$param = $this->getRequest()->query->get($option);
		if ($param !== null && $storedOption !== $param) {
			$user->setOption($fullOptionName, $param);
			$this->em()->getUserRepository()->save($user);
		}
		return $param ?? $storedOption;
	}
}
