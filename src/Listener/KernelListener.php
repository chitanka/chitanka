<?php namespace App\Listener;

use App\Controller\Controller;
use App\Persistence\EntityManager;
use App\Entity\User;
use App\Service\Responder;
use App\Util\Opds;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class KernelListener implements EventSubscriberInterface {

	public static function getSubscribedEvents() {
		return [
			KernelEvents::REQUEST => 'onKernelRequest',
			KernelEvents::RESPONSE => 'onKernelResponse',
			KernelEvents::CONTROLLER => 'onKernelController',
			KernelEvents::VIEW => 'onKernelView',
		];
	}

	private $responder;
	private $em;
	private $tokenStorage;
	private $controller;

	public function __construct(Responder $responder, EntityManager $em, TokenStorage $tokenStorage) {
		$this->responder = $responder;
		$this->em = $em;
		$this->tokenStorage = $tokenStorage;
	}

	/**
	 * @param GetResponseEvent $event
	 */
	public function onKernelRequest(GetResponseEvent $event) {
		if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
			return;
		}
		$this->responder->registerCustomResponseFormats($event->getRequest());
		$this->initTokenStorage();
	}

	/**
	 * @param FilterResponseEvent $event
	 */
	public function onKernelResponse(FilterResponseEvent $event) {
		$this->normalizeOpdsContent($event->getRequest(), $event->getResponse());
	}

	public function onKernelController(FilterControllerEvent $event) {
		$this->controller = $event->getController();
		$controllerObject = $this->controller[0];
		if ($controllerObject instanceof Controller) {
			$controllerObject->initInternalContentPath();
			$controllerObject->configureExtraDownloadFormats();
		}
	}

	/**
	 * @param GetResponseForControllerResultEvent $event
	 */
	public function onKernelView(GetResponseForControllerResultEvent $event) {
		$response = $this->responder->createResponse($event->getRequest(), $this->controller, $event->getControllerResult());
		$event->setResponse($response);
	}

	private function initTokenStorage() {
		$user = User::initUser($this->em->getUserRepository());
		if ($user->isAuthenticated()) {
			try {
				// register the user by doctrine
				$user = $this->em->merge($user);
			} catch (EntityNotFoundException $e) {
			}
		}
		$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), 'User', $user->getRoles());
		$this->tokenStorage->setToken($token);
	}

	private function normalizeOpdsContent(Request $request, Response $response) {
		if ($request->getRequestFormat() == 'opds') {
			$response->setContent(Opds::normalizeContent($response->getContent()));
		}
	}

}
