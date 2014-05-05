<?php namespace App\Controller;

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
		$this->addFlashNotice('Излязохте от Моята библиотека.');

		return $this->redirect('homepage');
	}

	public function requestUsernameAction() {
		return $this->legacyPage('SendUsername');
	}

	public function requestPasswordAction() {
		return $this->legacyPage('SendNewPassword');
	}

}
