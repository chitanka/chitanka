<?php namespace App\Service;

class RocketChatClient {

	private $chatUrl;
	private $authToken;
	private $userId;
	private $postChannel;

	/**
	 * @param string $chatUrl
	 * @param string $authToken
	 * @param string $userId
	 * @param string $postChannel
	 */
	public function __construct($chatUrl, $authToken = null, $userId = null, $postChannel = null) {
		$this->chatUrl = $chatUrl;
		$this->authToken = $authToken;
		$this->userId = $userId;
		$this->postChannel = $postChannel ?: '#general';
	}

	public function canPost() {
		return $this->chatUrl && $this->authToken && $this->userId;
	}

	public function changeUrlScheme($newScheme) {
		$this->chatUrl = preg_replace('#^\w+://#', "$newScheme://", $this->chatUrl);
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @return string
	 */
	public function generatePostMessageScript($username, $password, $email) {
		$loginToken = $this->fetchLoginToken($username, $password, $email);
		if (empty($loginToken)) {
			return '<!-- Error: Chat login token could not be generated. -->';
		}
		return "
<script>
window.parent.postMessage({
	event: 'login-with-token',
	loginToken: ".json_encode($loginToken)."
}, ".json_encode($this->chatUrl).");
</script>";
	}

	public function postMessageIfAble($message, $channel = null) {
		if ($this->canPost()) {
			return $this->postMessage($message, $channel);
		}
		return null;
	}

	public function postMessage($message, $channel = null) {
		return $this->sendAuthenticatedRequest('chat.postMessage', ['channel' => $channel ?: $this->postChannel, 'text' => $message]);
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
		return $this->sendRequest('login', ['user' => $this->normalizeUsername($username), 'password' => $password]);
	}

	private function sendRegisterRequest($username, $password, $email) {
		return $this->sendRequest('users.register', ['username' => $this->normalizeUsername($username), 'email' => $email, 'pass' => $password, 'name' => $username]);
	}

	private function sendAuthenticatedRequest($path, $assocData) {
		return $this->sendRequest($path, $assocData, [
			"X-Auth-Token: {$this->authToken}",
			"X-User-Id: {$this->userId}",
		]);
	}

	private function sendRequest($path, $assocData, $headers = []) {
		$url = "{$this->chatUrl}/api/v1/{$path}";
		$options = [
			'http' => [
				'ignore_errors' => true,
				'header' => implode("\r\n", array_merge(['Content-Type: application/json'], $headers)),
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
