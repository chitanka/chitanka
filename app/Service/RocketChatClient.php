<?php namespace App\Service;

class RocketChatClient {

	private $chatUrl;

	/**
	 * @param string $chatUrl
	 */
	public function __construct($chatUrl) {
		$this->chatUrl = $chatUrl;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @return string
	 */
	public function generatePostMessageScript($username, $password, $email) {
		$loginToken = $this->fetchLoginToken($username, $password, $email);
		return "
<script>
window.parent.postMessage({
	event: 'login-with-token',
	loginToken: ".json_encode($loginToken)."
}, ".json_encode($this->chatUrl).");
</script>";
	}

	protected function normalizeUsername($name) {
		$name = str_replace(' ', '_', $name);
		return $name;
	}

	private function fetchLoginToken($username, $password, $email) {
		$loginResponse = $this->sendLoginRequest($username, $password);
		if ($loginResponse->status === 'success') {
			return $loginResponse->data->authToken;
		}
		$registerResponse = $this->sendRegisterRequest($username, $password, $email);
		if ($registerResponse->success) {
			$loginResponse = $this->sendLoginRequest($username, $password);
			if ($loginResponse->status === 'success') {
				return $loginResponse->data->authToken;
			}
		}
		$this->logError(['user' => [$username, $email], 'registerResponse' => $registerResponse]);
		return null;
	}

	private function sendLoginRequest($username, $password) {
		return $this->sendChatRequest('login', ['user' => $this->normalizeUsername($username), 'password' => $password]);
	}

	private function sendRegisterRequest($username, $password, $email) {
		return $this->sendChatRequest('users.register', ['username' => $this->normalizeUsername($username), 'email' => $email, 'pass' => $password, 'name' => $username]);
	}

	private function sendChatRequest($path, $assocData) {
		$url = "{$this->chatUrl}/api/v1/{$path}";
		$options = [
			'http' => [
				'ignore_errors' => true,
				'header' => 'Content-Type: application/json',
				'method' => 'POST',
				'content' => json_encode($assocData)
			]
		];
		$response = file_get_contents($url, false, stream_context_create($options));
		if ($response) {
			return json_decode($response);
		}
		$errorResponse = (object) [
			'status' => 'error',
			'url' => $url,
			'data' => $assocData,
			'response' => $response,
			'responseHeaders' => $http_response_header,
		];
		$this->logError($errorResponse);
		return $errorResponse;
	}

	private function logError($error) {
		if (is_array($error)) {
			$error = json_encode($error);
		}
		error_log($error);
	}

}
