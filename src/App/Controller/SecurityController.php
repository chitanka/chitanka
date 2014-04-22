<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends Controller {

	public function loginAction(Request $request) {
		return $this->legacyPage('Login');

		// get the error if any (works with forward and redirect -- see below)
		if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
			$error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
			$error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
		}

		$this->view = array(
			// last username entered by the user
			'last_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME),
			'error'         => $error,
		);

		return $this->display('login');
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

		$session = $request->getSession();
		$session->invalidate();
		$session->getFlashBag()->set('notice', 'Излязохте от Моята библиотека.');

		return $this->redirect('homepage');
	}

	public function requestUsernameAction() {
		return $this->legacyPage('SendUsername');
	}

	public function requestPasswordAction() {
		return $this->legacyPage('SendNewPassword');
	}

}
