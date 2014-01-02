<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Entity\User;

abstract class Controller extends SymfonyController
{
	/** Main repository for the controller */
	protected $repository = null;

	/** The unqualified name of the controller: Main for MainController */
	protected $name = null;

	/** Data to send to the view */
	protected $view = array();

	/** The format of the response */
	protected $responseFormat = 'html';

	/** The max cache time of the response (in seconds) */
	protected $responseAge = 600;

	/** The status code of the response */
	protected $responseStatusCode = null;

	/**
	* Response headers. Used to overwrite default or add new ones
	*/
	protected $responseHeaders = array();

	private $_em = null;

	protected function legacyPage($page, $controller = ':legacy')
	{
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

		$response = $this->render("LibBundle:$controller.$format.twig", $this->view + $data);
		$this->setCacheStatusByResponse($response);

		return $response;
	}


	protected function display($action, $controller = null)
	{
		if (strpos($action, '.') === false) {
			$format = $this->responseFormat;
		} else {
			list($action, $format) = explode('.', $action);
		}
		$this->getRequest()->setFormat('osd', 'application/opensearchdescription+xml');
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
		if ($controller === null) {
			$controller = $this->getName();
		}
		$response = $this->render(sprintf('LibBundle:%s:%s.%s.twig', $controller, $action, $format), $this->view + $globals);
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

	protected function getDisplayVariables()
	{
		return array(
			'menu' => $this->container->getParameter('menu'),
			'_user' => $this->getUser(),
			'navextra' => array(),
			'current_route' => $this->getCurrentRoute(),
			'script_library' => $this->container->getParameter('script_library'),
			'global_info_message' => $this->container->getParameter('global_info_message'),
			'analytics_snippet' => $this->container->getParameter('analytics_snippet'),
			'environment' => $this->container->get('kernel')->getEnvironment(),
			'ajax' => $this->getRequest()->isXmlHttpRequest(),
		);
	}

	protected function getStylesheet()
	{
		$url = $this->container->getParameter('style_url');
		if ( ! $url) {
			return false;
		}

		return $url . http_build_query($this->getUser()->getSkinPreference());
	}

	protected function displayText($text, $headers = array())
	{
		$response = new Response($text);
		foreach ($headers as $header => $value) {
			$response->headers->set($header, $value);
		}
		$this->setCacheStatusByResponse($response);

		return $response;
	}


	protected function displayJson($content, $headers = array())
	{
		return $this->displayText(json_encode($content), $headers);
	}

	protected function setCacheStatusByResponse(Response $response)
	{
		if ($this->responseAge && $this->container->getParameter('use_http_cache')) {
			$response->setPublic();
			$response->setSharedMaxAge($this->responseAge);
		}
		return $response;
	}

	public function getName()
	{
		if (is_null($this->name) && preg_match('/([\w]+)Controller$/', get_class($this), $m)) {
			$this->name = $m[1];
		}

		return $this->name;
	}

	protected function getCurrentRoute()
	{
		return $this->get('request')->attributes->get('_route');
	}

	/** @return \Doctrine\ORM\EntityManager */
	public function getEntityManager()
	{
		if (is_null($this->_em)) {
			// TODO do this in the configuration
			$this->_em = $this->get('doctrine.orm.entity_manager');
			$this->_em->getConfiguration()->addCustomHydrationMode('id', 'Chitanka\LibBundle\Hydration\IdHydrator');
			$this->_em->getConfiguration()->addCustomHydrationMode('key_value', 'Chitanka\LibBundle\Hydration\KeyValueHydrator');
		}

		return $this->_em;
	}


	public function getRepository($entityName = null)
	{
		return $this->getEntityManager()->getRepository($this->getEntityName($entityName));
	}


	protected function getEntityName($entityName)
	{
		return 'LibBundle:'.$entityName;
	}


	private $user;
	/** @return User */
	public function getUser()
	{
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

	public function setUser($user)
	{
		$this->_user = $user;
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
     * @param Boolean $permanent Whether the redirect is permanent or not
     *
     * @return Response A Response instance
     */
    public function redirect($route, $permanent = false)
    {
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
     * @param Boolean $permanent Whether the redirect is permanent or not
     *
     * @return Response A Response instance
     */
    public function urlRedirect($url, $permanent = false)
    {
        if (!$url) {
            return new Response(null, 410);
        }

        return new RedirectResponse($url, $permanent ? 301 : 302);
    }


	// TODO refactor: move to separate class
	protected function getMirrorServer()
	{
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

	/** @return \Chitanka\LibBundle\Entity\BookRepository */
	protected function getBookRepository() { return $this->getRepository('Book'); }
	/** @return \Chitanka\LibBundle\Entity\BookmarkRepository */
	protected function getBookmarkRepository() { return $this->getRepository('Bookmark'); }
	/** @return \Chitanka\LibBundle\Entity\BookmarkFolderRepository */
	protected function getBookmarkFolderRepository() { return $this->getRepository('BookmarkFolder'); }
	/** @return \Chitanka\LibBundle\Entity\BookRevisionRepository */
	protected function getBookRevisionRepository() { return $this->getRepository('BookRevision'); }
	/** @return \Chitanka\LibBundle\Entity\CategoryRepository */
	protected function getCategoryRepository() { return $this->getRepository('Category'); }
	/** @return \Chitanka\LibBundle\Entity\FeaturedBookRepository */
	protected function getFeaturedBookRepository() { return $this->getRepository('FeaturedBook'); }
	/** @return \Chitanka\LibBundle\Entity\ForeignBookRepository */
	protected function getForeignBookRepository() { return $this->getRepository('ForeignBook'); }
	/** @return \Chitanka\LibBundle\Entity\LabelRepository */
	protected function getLabelRepository() { return $this->getRepository('Label'); }
	/** @return \Chitanka\LibBundle\Entity\PersonRepository */
	protected function getPersonRepository() { return $this->getRepository('Person'); }
	/** @return \Chitanka\LibBundle\Entity\SearchStringRepository */
	protected function getSearchStringRepository() { return $this->getRepository('SearchString'); }
	/** @return \Chitanka\LibBundle\Entity\SequenceRepository */
	protected function getSequenceRepository() { return $this->getRepository('Sequence'); }
	/** @return \Chitanka\LibBundle\Entity\SeriesRepository */
	protected function getSeriesRepository() { return $this->getRepository('Series'); }
	/** @return \Chitanka\LibBundle\Entity\SiteRepository */
	protected function getSiteRepository() { return $this->getRepository('Site'); }
	/** @return \Chitanka\LibBundle\Entity\TextRepository */
	protected function getTextRepository() { return $this->getRepository('Text'); }
	/** @return \Chitanka\LibBundle\Entity\TextCommentRepository */
	protected function getTextCommentRepository() { return $this->getRepository('TextComment'); }
	/** @return \Chitanka\LibBundle\Entity\TextRatingRepository */
	protected function getTextRatingRepository() { return $this->getRepository('TextRating'); }
	/** @return \Chitanka\LibBundle\Entity\TextRevisionRepository */
	protected function getTextRevisionRepository() { return $this->getRepository('TextRevision'); }
	/** @return \Chitanka\LibBundle\Entity\UserRepository */
	protected function getUserRepository() { return $this->getRepository('User'); }
	/** @return \Chitanka\LibBundle\Entity\UserTextContribRepository */
	protected function getUserTextContribRepository() { return $this->getRepository('UserTextContrib'); }
	/** @return \Chitanka\LibBundle\Entity\UserTextReadRepository */
	protected function getUserTextReadRepository() { return $this->getRepository('UserTextRead'); }
	/** @return \Chitanka\LibBundle\Entity\WantedBookRepository */
	protected function getWantedBookRepository() { return $this->getRepository('WantedBook'); }
	/** @return \Chitanka\LibBundle\Entity\WikiSiteRepository */
	protected function getWikiSiteRepository() { return $this->getRepository('WikiSite'); }
	/** @return \Chitanka\LibBundle\Entity\WorkEntryRepository */
	protected function getWorkEntryRepository() { return $this->getRepository('WorkEntry'); }
	/** @return \Chitanka\LibBundle\Entity\WorkContribRepository */
	protected function getWorkContribRepository() { return $this->getRepository('WorkContrib'); }

}
