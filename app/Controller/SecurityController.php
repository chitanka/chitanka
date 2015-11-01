<?php namespace App\Controller;

use App\Form\Type\RequestPasswordType;
use App\Form\Type\RequestUsernameType;
use App\Mail\PasswordRequestMailer;
use App\Mail\UsernameRequestMailer;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller {

	public function loginAction() {
		return $this->legacyPage('Login');
	}

	public function registerAction() {
		return $this->legacyPage('Register');
	}

	public function logoutAction(Request $request) {
		$user = $this->getUser();
		if ($user) {
			$user->eraseCredentials();
			$user->logout();
		}

		$request->getSession()->invalidate();
		$this->flashes()->addNotice('Излязохте от Моята библиотека.');

		return $this->redirectToRoute('homepage');
	}

	public function requestUsernameAction(Request $request) {
		$form = $this->createForm(new RequestUsernameType());

		if ($form->handleRequest($request)->isValid()) {
			$data = $form->getData();
			if ($user = $this->processUsernameRequest($data['email'])) {
				return $this->redirectWithNotice("На адреса <strong>{$user->getEmail()}</strong> беше изпратено напомнящо писмо. Ако не се сещате и за паролата си, ползвайте функцията „<a href=\"{$this->generateUrl('request_password')}\">Изпращане на нова парола</a>“. Иначе можете спокойно <a href=\"{$this->generateUrl('login')}\">да влезете</a>.");
			}
		}
		return [
			'form' => $form->createView(),
		];
	}

	public function requestPasswordAction(Request $request) {
		$form = $this->createForm(new RequestPasswordType());

		if ($form->handleRequest($request)->isValid()) {
			$data = $form->getData();
			if ($user = $this->processPasswordRequest($data['username'])) {
				return $this->redirectWithNotice("Нова парола беше изпратена на електронната поща на <strong>{$user->getUsername()}</strong>. Моля, <a href=\"{$this->generateUrl('login')}\">влезте отново</a>, след като я получите.");
			}
		}
		return [
			'form' => $form->createView(),
		];
	}

	private function processUsernameRequest($email) {
		$user = $this->em()->getUserRepository()->findByEmail($email);
		if (!$user) {
			$this->flashes()->addError("Не съществува потребител с електронна поща <strong>{$email}</strong>.");
			return false;
		}
		$mailer = new UsernameRequestMailer($this->get('mailer'), $this->get('twig'));
		$mailer->sendUsername($user, $this->container->getParameter('site_email'));
		return $user;
	}

	private function processPasswordRequest($username) {
		$userRepo = $this->em()->getUserRepository();
		$user = $userRepo->findByUsername($username);
		if (!$user) {
			$this->flashes()->addError("Не съществува потребител с име <strong>$username</strong>.");
			return false;
		}
		if ($user->getEmail() == '') {
			$this->flashes()->addError("За потребителя <strong>$username</strong> не е посочена електронна поща.");
			return false;
		}

		$newPassword = User::randomPassword();
		$user->setNewpassword($newPassword);
		$userRepo->save($user);

		$mailer = new PasswordRequestMailer($this->get('mailer'), $this->get('twig'));
		$mailer->sendNewPassword($user, $newPassword, $this->container->getParameter('site_email'));
		return $user;
	}
}
