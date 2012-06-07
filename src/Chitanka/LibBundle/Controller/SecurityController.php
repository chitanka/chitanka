<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Encoder\MessageDigestPasswordEncoder;

class SecurityController extends Controller
{
	public function loginAction()
	{
		return $this->legacyPage('Login');

// 		$encoder = new MessageDigestPasswordEncoder('sha1');
// 		echo $encoder->encodePassword('parola', '9');

		// get the error if any (works with forward and redirect -- see below)
		if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
			$error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
			$error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
		}

		$this->view = array(
            // last username entered by the user
            'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
		);

		return $this->display('login');
	}


	public function registerAction()
	{
		return $this->legacyPage('Register');
	}

	public function logoutAction(Request $request)
	{
		$user = $this->getUser();
		if ($user) {
			$user->eraseCredentials();
			$user->logout();
		}

		$session = $request->getSession();
		$session->invalidate();
		$session->setFlash('notice', 'Излязохте от Моята библиотека.');

		return $this->redirect('homepage');
	}

	public function requestUsernameAction()
	{
		return $this->legacyPage('SendUsername');
	}

	public function requestPasswordAction()
	{
		return $this->legacyPage('SendNewPassword');
	}

}
