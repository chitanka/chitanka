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
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

	public function __construct(Responder $responder, EntityManager $em, TokenStorageInterface $tokenStorage) {
		$this->responder = $responder;
		$this->em = $em;
		$this->tokenStorage = $tokenStorage;
	}

	public function onKernelRequest(RequestEvent $event) {
		if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
			return;
		}
		$this->responder->registerCustomResponseFormats($event->getRequest());
		$this->initTokenStorage();
	}

	public function onKernelResponse(ResponseEvent $event) {
		$this->normalizeOpdsContent($event->getRequest(), $event->getResponse());
	}

	public function onKernelController(ControllerEvent $event) {
		$this->controller = $event->getController();
		$controllerObject = $this->controller;
		if ($controllerObject instanceof Controller) {
			$controllerObject->initInternalContentPath();
			$controllerObject->configureExtraDownloadFormats();
		}
	}

	public function onKernelView(ViewEvent $event) {
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
