<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
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

	/**
	* Response headers. Used to overwrite default or add new ones
	*/
	protected $responseHeaders = array();

	private $_em = null;

	protected function legacyPage($page, $controller = ':legacy')
	{
		$page = Setup::getPage($page, $this, $this->container);
		if ($page->redirect) {
			return $this->urlRedirect($page->redirect);
		}

		$data = array(
			'page' => $page,
			'menu' => $this->container->getParameter('menu'),
			'_user' => $this->getUser(),
			'navextra' => array(),
			'current_route' => $this->get('request')->attributes->get('_route'),
			//'stylesheet' => $this->getStylesheet(),
			'script_library' => $this->container->getParameter('script_library'),
		);
		if ($page->inlineJs) {
			$data['inline_js'] = $page->inlineJs;
		}

		$response = $this->render("LibBundle:$controller.$this->responseFormat.twig", $this->view + $data);
		if ($this->responseAge) {
			$response->setPublic();
			$response->setSharedMaxAge($this->responseAge);
		}

		return $response;
	}


	protected function display($action)
	{
		$request = $this->get('request');
		$globals = array(
			'menu' => $this->container->getParameter('menu'),
			'_user' => $this->getUser(),
			'navextra' => array(),
			'current_route' => $request->attributes->get('_route'),
			// done in a separate request
			//'stylesheet' => $this->getStylesheet(),
			'script_library' => $this->container->getParameter('script_library'),
			'ajax' => $request->isXmlHttpRequest(),
		);

		$response = $this->render(sprintf('LibBundle:%s:%s.%s.twig', $this->getName(), $action, $this->responseFormat), $this->view + $globals);
		if ($this->responseAge) {
			$response->setPublic();
			$response->setSharedMaxAge($this->responseAge);
		}

		return $response;
	}

	protected function getStylesheet()
	{
		$url = $this->container->getParameter('style_url');
		if ( ! $url) {
			return false;
		}

		$skin = $this->get('request')->query->get('useskin');
		if ( ! $skin) {
			$skin = $this->getUser()->getSkinPreference();
		}

		return str_replace('FILE', $skin, $url);
	}

	protected function displayText($text, $headers = array())
	{
		$response = new Response($text);
		foreach ($headers as $header => $value) {
			$response->headers->set($header, $value);
		}

		return $response;
	}


	protected function displayJson($content, $headers = array())
	{
		return $this->displayText(json_encode($content), $headers);
	}

	protected function display404($text)
	{
	}


	public function getName()
	{
		if (is_null($this->name) && preg_match('/([\w]+)Controller$/', get_class($this), $m)) {
			$this->name = $m[1];
		}

		return $this->name;
	}

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


	public function getUser()
	{
		// TODO remove
		if ( ! isset($this->_user)) {
			$this->_user = User::initUser($this->getRepository('User'));
		}
		return $this->_user;

		return $this->get('security.context')->getUser();
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

}
