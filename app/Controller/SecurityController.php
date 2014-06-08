<?php namespace App\Controller;

use App\Form\Type\RequestPasswordType;
use App\Mail\PasswordRequestMailer;
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
		$this->disableCache();

		$user = $this->getUser();
		if ($user) {
			$user->eraseCredentials();
			$user->logout();
		}

		$request->getSession()->invalidate();
		$this->flashes()->addNotice('Излязохте от Моята библиотека.');

		return $this->redirect('homepage');
	}

	public function requestUsernameAction() {
		return $this->legacyPage('SendUsername');
	}

	public function requestPasswordAction(Request $request) {
		$form = $this->createForm(new RequestPasswordType());

		if ($this->isValidPost($request, $form)) {
			$data = $form->getData();
			if ($user = $this->processPasswordRequest($data['username'])) {
				return $this->redirectWithNotice("Нова парола беше изпратена на електронната поща на <strong>{$user->getUsername()}</strong>. Моля, <a href=\"{$this->generateUrl('login')}\">влезте отново</a>, след като я получите.");
			}
		}
		return $this->display('request_password', array(
			'form' => $form->createView(),
		));
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
		$mailer->sendNewPassword($user, $newPassword, $this->getParameter('site_email'));
		return $user;
	}
}
