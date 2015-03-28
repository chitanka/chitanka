<?php namespace App\Controller;

use App\Legacy\Setup;
use App\Entity\User;
use App\Service\FlashService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class Controller extends SymfonyController {

	/** The max cache time of the response (in seconds) */
	protected $responseAge = 86400; // 24 hours

	/** @var \App\Entity\EntityManager */
	private $em;
	/** @var \App\Service\FlashService */
	private $flashes;

	/**
	 * @param string $pageName
	 * @param array $params
	 */
	protected function legacyPage($pageName, array $params = []) {
		$page = Setup::getPage($pageName, $this, $this->container);
		if ($page->redirect) {
			return $this->urlRedirect($page->redirect);
		}

		$params += [
			'page' => $page,
			'navlinks' => $this->renderLayoutComponent('sidebar-menu', 'App::navlinks.html.twig'),
			'footer_links' => $this->renderLayoutComponent('footer-menu', 'App::footer_links.html.twig'),
			'current_route' => $this->get('request')->attributes->get('_route'),
			'environment' => $this->container->get('kernel')->getEnvironment(),
			'ajax' => $this->get('request')->isXmlHttpRequest(),
			'_controller' => ':legacy',
		];
		if ($page->inlineJs) {
			$params['inline_js'] = $page->inlineJs;
		}

		$response = $this->render("App:{$params['_controller']}.{$this->get('request')->getRequestFormat()}.twig", $params);
		$this->setCacheStatusByResponse($response);

		return $response;
	}

	/**
	 * @param string $wikiPage
	 * @param string $fallbackTemplate
	 */
	protected function renderLayoutComponent($wikiPage, $fallbackTemplate) {
		$wikiPagePath = $this->container->getParameter('content_dir')."/wiki/special/$wikiPage.html";
		if (file_exists($wikiPagePath)) {
			list(, $content) = explode("\n\n", file_get_contents($wikiPagePath));
			return $content;
		}
		return $this->renderView($fallbackTemplate);
	}

	protected function asText($text, $contentType = 'text/plain') {
		return [
			'_content' => $text,
			'_type' => $contentType,
		];
	}

	protected function asJson($content) {
		return $this->asText(json_encode($content));
	}

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

	private $user;
	/** @return User */
	public function getUser() {
		if ( ! isset($this->user)) {
			$this->user = User::initUser($this->em()->getUserRepository());
			if ($this->user->isAuthenticated()) {
				$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($this->user, $this->user->getPassword(), 'User', $this->user->getRoles());
				$this->get('security.context')->setToken($token);
			}
		}
		return $this->user;
	}

	protected function getSavableUser() {
		return $this->em()->merge($this->getUser());
	}

	public function setUser($user) {
		$this->user = $user;
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

	/**
	 * Redirects to another route.
	 *
	 * @param string  $route      The route pattern to redirect to
	 * @param array   $parameters Possible parameters used by the route generation
	 *
	 * @return Response A Response instance
	 */
	public function redirectToRoute($route, array $parameters = []) {
		$parameters['_format'] = $this->get('request')->getRequestFormat();
		return $this->redirect($this->generateUrl($route, $parameters));
	}

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

	public function createAccessDeniedException($message = 'Access Denied', \Exception $previous = null) {
		return new \Symfony\Component\Security\Core\Exception\AccessDeniedException($message, $previous);
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
		return dirname($this->get('request')->server->get('SCRIPT_NAME'));
	}

}
