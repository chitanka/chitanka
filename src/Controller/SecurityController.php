<?php namespace App\Controller;

use App\Entity\User;
use App\Form\Type\RequestPasswordType;
use App\Form\Type\RequestUsernameType;
use App\Mail\PasswordRequestMailer;
use App\Mail\UsernameRequestMailer;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller {

	public function loginAction(bool $allowUserRegistration) {
		if (!$allowUserRegistration) {
			throw $this->createAccessDeniedException();
		}
		return $this->legacyPage('Login', [], [
			'userRepository' => $this->userRepository,
		]);
	}

	public function registerAction(bool $allowUserRegistration) {
		if (!$allowUserRegistration) {
			throw $this->createAccessDeniedException();
		}
		return $this->legacyPage('Register', [], [
			'userRepository' => $this->userRepository,
		]);
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

	public function requestUsernameAction(Request $request, string $siteEmail) {
		$form = $this->createForm(RequestUsernameType::class);

		if ($form->handleRequest($request)->isValid()) {
			$data = $form->getData();
			if ($user = $this->processUsernameRequest($data['email'], $siteEmail)) {
				return $this->redirectWithNotice("На адреса <strong>{$user->getEmail()}</strong> беше изпратено напомнящо писмо. Ако не се сещате и за паролата си, ползвайте функцията „<a href=\"{$this->generateUrl('request_password')}\">Изпращане на нова парола</a>“. Иначе можете спокойно <a href=\"{$this->generateUrl('login')}\">да влезете</a>.");
			}
		}
		return [
			'form' => $form->createView(),
		];
	}

	public function requestPasswordAction(Request $request, string $siteEmail) {
		$form = $this->createForm(RequestPasswordType::class);

		if ($form->handleRequest($request)->isValid()) {
			$data = $form->getData();
			if ($user = $this->processPasswordRequest($data['username'], $siteEmail)) {
				return $this->redirectWithNotice("Нова парола беше изпратена на електронната поща на <strong>{$user->getUsername()}</strong>. Моля, <a href=\"{$this->generateUrl('login')}\">влезте отново</a>, след като я получите.");
			}
		}
		return [
			'form' => $form->createView(),
		];
	}

	private function processUsernameRequest($email, string $siteEmail) {
		$user = $this->userRepository->findByEmail($email);
		if (!$user) {
			$this->flashes()->addError("Не съществува потребител с електронна поща <strong>{$email}</strong>.");
			return false;
		}
		if (!$user->isEmailValid()) {
			$this->flashes()->addError("Посоченият адрес за електронна поща е отбелязан като невалиден. Свържете се с администратор, ако смятате, че това е грешка.");
			return false;
		}
		$mailer = new UsernameRequestMailer($this->get('mailer'), $this->get('twig'));
		$mailer->sendUsername($user, $siteEmail);
		return $user;
	}

	private function processPasswordRequest($username, string $siteEmail) {
		$userRepo = $this->userRepository;
		$user = $userRepo->findByUsername($username);
		if (!$user) {
			$this->flashes()->addError("Не съществува потребител с име <strong>$username</strong>.");
			return false;
		}
		if ($user->getEmail() == '') {
			$this->flashes()->addError("За потребителя <strong>$username</strong> не е посочена електронна поща.");
			return false;
		}
		if (!$user->isEmailValid()) {
			$this->flashes()->addError("Посоченият адрес за електронна поща е отбелязан като невалиден. Свържете се с администратор, ако смятате, че това е грешка.");
			return false;
		}

		$newPassword = User::randomPassword();
		$user->setNewpassword($newPassword);
		$userRepo->save($user);

		$mailer = new PasswordRequestMailer($this->get('mailer'), $this->get('twig'));
		$mailer->sendNewPassword($user, $newPassword, $siteEmail);
		return $user;
	}
}
