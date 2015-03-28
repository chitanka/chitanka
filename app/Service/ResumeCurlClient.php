<?php namespace App\Service;

use Buzz\Client\Curl;
use Buzz\Listener\ListenerInterface;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

/**
 * A curl client which offers download resuming
 */
class ResumeCurlClient extends Curl implements ListenerInterface {

	protected $timeout = 1800;
	private $headers = [];
	private $saveDir;
	private $saveFile;
	private $saveFileSuffix = '.part';

	public function preSend(RequestInterface $request) {
		$this->setOption(CURLOPT_HEADERFUNCTION, [$this, 'acceptHeader']);
		$this->setOption(CURLOPT_WRITEFUNCTION, [$this, 'acceptChunk']);

		$saveFile = $this->openSaveFile($this->createSaveFileName(basename($request->getResource())));
		if ($saveFile->getSize()) {
			$this->setOption(CURLOPT_RANGE, $saveFile->getSize().'-');
		}
	}

	public function postSend(RequestInterface $request, MessageInterface $response) {
		$response->setHeaders($this->headers);
		$response->setContent(file_get_contents($this->saveFile->getRealPath()));
		unlink($this->saveFile->getRealPath());
	}

	/**
	 * @param string $saveDir
	 */
	public function setSaveDir($saveDir) {
		$this->saveDir = $saveDir;
	}

	/**
	 * @param string $filename
	 */
	private function openSaveFile($filename) {
		return $this->saveFile = new \SplFileObject($filename, "a");
	}

	/**
	 * @param string $basename
	 */
	private function createSaveFileName($basename) {
		return ($this->saveDir ?: sys_get_temp_dir()) . "/". $basename . $this->saveFileSuffix;
	}

	public function acceptHeader($curlHandle, $header) {
		$this->clearHeadersIfNewResponse();
		$this->headers[] = trim($header);
		return strlen($header);
	}
	private function clearHeadersIfNewResponse() {
		$len = count($this->headers);
		if ($len && $this->headers[$len-1] == "") {
			$this->headers = [];
		}
	}

	public function acceptChunk($curlHandle, $chunk) {
		if (!$this->isHeader($chunk)) {
			$this->saveFile->fwrite($chunk);
		}
		return strlen($chunk);
	}

	public function isHeader($chunk) {
		return substr($chunk, -2) == "\r\n";
	}
}
