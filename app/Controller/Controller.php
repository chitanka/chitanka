<?php namespace App\Controller;

use App\Legacy\Setup;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller extends SymfonyController {

	/** The unqualified name of the controller: Main for MainController */
	protected $name = null;

	/** Data to send to the view */
	protected $view = array();

	/** The format of the response */
	protected $responseFormat = 'html';

	/** The max cache time of the response (in seconds) */
	protected $responseAge = 86400; // 24 hours

	/** The status code of the response */
	protected $responseStatusCode = null;

	/**
	* Response headers. Used to overwrite default or add new ones
	*/
	protected $responseHeaders = array();

	private $em;

	/**
	 * @param string $page
	 * @param string $controller
	 */
	protected function legacyPage($page, $controller = ':legacy') {
		if (strpos($page, '.') === false) {
			$format = $this->responseFormat;
		} else {
			list($page, $format) = explode('.', $page);
		}
		$page = Setup::getPage($page, $this, $this->container);
		if ($page->redirect) {
			return $this->urlRedirect($page->redirect);
		}

		$data = $this->getDisplayVariables() + array('page' => $page);
		if ($page->inlineJs) {
			$data['inline_js'] = $page->inlineJs;
		}

		$response = $this->render("App:$controller.$format.twig", $this->view + $data);
		$this->setCacheStatusByResponse($response);

		return $response;
	}

	/**
	 * Render a given controler action.
	 * @param string $action  Action name. Can include controller and/or format. Examples:
	 *                        index - an action name
	 *                        Main:index - controller and action
	 *                        Main:catalog.opds - controller, action and format
	 * @param array $params   Parameters to be sent to te view
	 * @return Response
	 */
	protected function display($action, array $params = array()) {
		if (strpos($action, '.') === false) {
			$format = $this->responseFormat;
		} else {
			list($action, $format) = explode('.', $action);
		}
		if (strpos($action, ':') !== false) {
			list($controller, $action) = explode(':', $action);
		} else {
			$controller = $this->getName();
		}
		$this->get('request')->setFormat('osd', 'application/opensearchdescription+xml');
		$globals = $this->getDisplayVariables();

		if ($format == 'opds') {
			$textsUpdatedAt = $this->getTextRevisionRepository()->getMaxDate();
			$booksUpdatedAt = $this->getBookRevisionRepository()->getMaxDate();
			$globals += array(
				'texts_updated_at' => $textsUpdatedAt,
				'books_updated_at' => $booksUpdatedAt,
				'updated_at' => max($textsUpdatedAt, $booksUpdatedAt),
			);
		} else if ($format == 'osd') {
			$this->responseAge = 31536000; // an year
		}
		$response = $this->render("App:$controller:$action.$format.twig", $this->view + $params + $globals);
		if ($format == 'opds') {
			$normalizedContent = $response->getContent();
			$normalizedContent = strtr($normalizedContent, array(
				"\t" => ' ',
				"\n" => ' ',
			));
			$normalizedContent = preg_replace('/  +/', ' ', $normalizedContent);
			$normalizedContent = preg_replace('/> </', ">\n<", $normalizedContent);
			$normalizedContent = strtr($normalizedContent, array(
				'> ' => '>',
				' <' => '<',
			));
			$response->setContent($normalizedContent);
		}
		$this->setCacheStatusByResponse($response);
		if ($this->responseStatusCode) {
			$response->setStatusCode($this->responseStatusCode);
		}

		return $response;
	}

	protected function getDisplayVariables() {
		return array(
			'navlinks' => $this->renderNavLinks(),
			'navextra' => array(),
			'footer_links' => $this->renderFooterLinks(),
			'current_route' => $this->getCurrentRoute(),
			'script_library' => $this->container->getParameter('script_library'),
			'global_info_message' => $this->container->getParameter('global_info_message'),
			'analytics_snippet' => $this->container->getParameter('analytics_snippet'),
			'environment' => $this->container->get('kernel')->getEnvironment(),
			'ajax' => $this->get('request')->isXmlHttpRequest(),
		);
	}

	protected function renderNavLinks() {
		return $this->renderLayoutComponent('sidebar-menu', 'App::navlinks.html.twig');
	}

	protected function renderFooterLinks() {
		return $this->renderLayoutComponent('footer-menu', 'App::footer_links.html.twig');
	}

	/**
	 * @param string $wikiPage
	 * @param string $fallbackTemplate
	 */
	protected function renderLayoutComponent($wikiPage, $fallbackTemplate) {
		$wikiPagePath = $this->getParameter('content_dir')."/wiki/special/$wikiPage.html";
		if (file_exists($wikiPagePath)) {
			list(, $content) = explode("\n\n", file_get_contents($wikiPagePath));
			return $content;
		}
		return $this->renderView($fallbackTemplate);
	}

	protected function getStylesheet() {
		$url = $this->container->getParameter('style_url');
		if ( ! $url) {
			return false;
		}

		return $url . http_build_query($this->getUser()->getSkinPreference());
	}

	protected function displayText($text, $headers = array()) {
		$response = new Response($text);
		foreach ($headers as $header => $value) {
			$response->headers->set($header, $value);
		}
		$this->setCacheStatusByResponse($response);

		return $response;
	}

	protected function displayJson($content, $headers = array()) {
		return $this->displayText(json_encode($content), $headers);
	}

	protected function setCacheStatusByResponse(Response $response) {
		if ($this->responseAge && $this->container->getParameter('use_http_cache')) {
			$response->setSharedMaxAge($this->responseAge);
		}
		return $response;
	}

	public function getName() {
		if (is_null($this->name) && preg_match('/([\w]+)Controller$/', get_class($this), $m)) {
			$this->name = $m[1];
		}

		return $this->name;
	}

	protected function getCurrentRoute() {
		return $this->get('request')->attributes->get('_route');
	}

	/** @return \Doctrine\ORM\EntityManager */
	public function getEntityManager() {
		if (!isset($this->em)) {
			// TODO do this in the configuration
			$this->em = $this->getDoctrine()->getManager();
			$this->em->getConfiguration()->addCustomHydrationMode('id', 'App\Hydration\IdHydrator');
			$this->em->getConfiguration()->addCustomHydrationMode('key_value', 'App\Hydration\KeyValueHydrator');
		}

		return $this->em;
	}

	/**
	 * @param string $entityName
	 */
	public function getRepository($entityName) {
		return $this->getEntityManager()->getRepository('App:'.$entityName);
	}

	private $user;
	/** @return User */
	public function getUser() {
		// TODO remove
		if ( ! isset($this->user)) {
			$this->user = User::initUser($this->getUserRepository());
			if ($this->user->isAuthenticated()) {
				$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($this->user, $this->user->getPassword(), 'User', $this->user->getRoles());
				$this->get('security.context')->setToken($token);
			}
		}
		return $this->user;
		//return $this->get('security.context')->getToken()->getUser();
	}

	protected function getSavableUser() {
		return $this->getEntityManager()->merge($this->getUser());
	}

	public function setUser($user) {
		$this->user = $user;
	}

	/**
	 * @param string $notice
	 * @return Response
	 */
	protected function redirectWithNotice($notice) {
		$this->addFlashNotice($notice);
		return $this->redirect('message');
	}

	/**
	 * Add a flash message of type 'notice'
	 * @param string $notice
	 */
	protected function addFlashNotice($notice) {
		$this->addFlashMessage('notice', $notice);
	}

	/**
	 * Add a flash message
	 * @param string $type
	 * @param string $message
	 */
	protected function addFlashMessage($type, $message) {
		$this->getFlashBag()->add($type, $message);
	}

	/**
	 * @return bool
	 */
	protected function hasFlashMessages() {
		return $this->getFlashBag()->peekAll() !== array();
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Session\Flash\FlashBag
	 */
	protected function getFlashBag() {
		return $this->get('session')->getFlashBag();
	}

	/**
	 * Redirects to another route.
	 *
	 * It expects a route path parameter.
	 * By default, the response status code is 301.
	 *
	 * If the route empty, the status code will be 410.
	 * If the permanent path parameter is set, the status code will be 302.
	 * (copied from Symfony\Bundle\FrameworkBundle\Controller\RedirectController)
	 *
	 * @param string  $route     The route pattern to redirect to
	 * @param bool    $permanent Whether the redirect is permanent or not
	 *
	 * @return Response A Response instance
	 */
	public function redirect($route, $permanent = false) {
		if (!$route) {
			return new Response(null, 410);
		}

		$attributes = $this->container->get('request')->attributes->all();
		unset($attributes['_route'], $attributes['route'], $attributes['permanent'] );

		return new RedirectResponse($this->container->get('router')->generate($route, $attributes), $permanent ? 301 : 302);
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

	/**
	 * @param string $message
	 */
	protected function notAllowed($message = null) {
		throw new HttpException(401, $message);
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

	protected function enableCache($responseLifetime) {
		if (is_string($responseLifetime)) {
			$responseLifetime = strtotime($responseLifetime) - strtotime('now');
		}
		$this->responseAge = $responseLifetime;
	}

	protected function disableCache() {
		$this->responseAge = 0;
	}

	protected function getWebRoot() {
		return dirname($this->get('request')->server->get('SCRIPT_NAME'));
	}

	/**
	 * @param string $param
	 */
	protected function getParameter($param) {
		return $this->container->getParameter($param);
	}

	/** @return \App\Entity\BookRepository */
	protected function getBookRepository() { return $this->getRepository('Book'); }
	/** @return \App\Entity\BookmarkRepository */
	protected function getBookmarkRepository() { return $this->getRepository('Bookmark'); }
	/** @return \App\Entity\BookmarkFolderRepository */
	protected function getBookmarkFolderRepository() { return $this->getRepository('BookmarkFolder'); }
	/** @return \App\Entity\BookRevisionRepository */
	protected function getBookRevisionRepository() { return $this->getRepository('BookRevision'); }
	/** @return \App\Entity\CategoryRepository */
	protected function getCategoryRepository() { return $this->getRepository('Category'); }
	/** @return \App\Entity\FeaturedBookRepository */
	protected function getFeaturedBookRepository() { return $this->getRepository('FeaturedBook'); }
	/** @return \App\Entity\ForeignBookRepository */
	protected function getForeignBookRepository() { return $this->getRepository('ForeignBook'); }
	/** @return \App\Entity\LabelRepository */
	protected function getLabelRepository() { return $this->getRepository('Label'); }
	/** @return \App\Entity\PersonRepository */
	protected function getPersonRepository() { return $this->getRepository('Person'); }
	/** @return \App\Entity\SearchStringRepository */
	protected function getSearchStringRepository() { return $this->getRepository('SearchString'); }
	/** @return \App\Entity\SequenceRepository */
	protected function getSequenceRepository() { return $this->getRepository('Sequence'); }
	/** @return \App\Entity\SeriesRepository */
	protected function getSeriesRepository() { return $this->getRepository('Series'); }
	/** @return \App\Entity\SiteRepository */
	protected function getSiteRepository() { return $this->getRepository('Site'); }
	/** @return \App\Entity\SiteNoticeRepository */
	protected function getSiteNoticeRepository() { return $this->getRepository('SiteNotice'); }
	/** @return \App\Entity\TextRepository */
	protected function getTextRepository() { return $this->getRepository('Text'); }
	/** @return \App\Entity\TextCommentRepository */
	protected function getTextCommentRepository() { return $this->getRepository('TextComment'); }
	/** @return \App\Entity\TextRatingRepository */
	protected function getTextRatingRepository() { return $this->getRepository('TextRating'); }
	/** @return \App\Entity\TextRevisionRepository */
	protected function getTextRevisionRepository() { return $this->getRepository('TextRevision'); }
	/** @return \App\Entity\UserRepository */
	protected function getUserRepository() { return $this->getRepository('User'); }
	/** @return \App\Entity\UserTextContribRepository */
	protected function getUserTextContribRepository() { return $this->getRepository('UserTextContrib'); }
	/** @return \App\Entity\UserTextReadRepository */
	protected function getUserTextReadRepository() { return $this->getRepository('UserTextRead'); }
	/** @return \Doctrine\ORM\EntityRepository */
	protected function getWikiSiteRepository() { return $this->getRepository('WikiSite'); }
	/** @return \App\Entity\WorkEntryRepository */
	protected function getWorkEntryRepository() { return $this->getRepository('WorkEntry'); }
	/** @return \Doctrine\ORM\EntityRepository */
	protected function getWorkContribRepository() { return $this->getRepository('WorkContrib'); }

	protected function isValidPost(Request $request, Form $form) {
		return $request->isMethod('POST') && $form->handleRequest($request)->isValid();
	}
}
