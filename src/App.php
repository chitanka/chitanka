<?php namespace App;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class App extends Bundle {
	/**
	 * {@inheritdoc}
	 */
	public function getNamespace() {
		return __NAMESPACE__;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPath() {
		return strtr(__DIR__, '\\', '/');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getContainerExtension() {
		return null;
	}

}
