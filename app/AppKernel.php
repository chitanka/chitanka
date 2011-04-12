<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
	public function registerBundles()
	{
		$bundles = array(
			new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
			new Symfony\Bundle\SecurityBundle\SecurityBundle(),
			new Symfony\Bundle\TwigBundle\TwigBundle(),
			new Symfony\Bundle\MonologBundle\MonologBundle(),
			new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
			new Symfony\Bundle\DoctrineBundle\DoctrineBundle(),
			//new Symfony\Bundle\AsseticBundle\AsseticBundle(),
			new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
			//new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),

			//new FOS\UserBundle\FOSUserBundle(),
			new Sonata\jQueryBundle\SonatajQueryBundle(),
			new Sonata\BluePrintBundle\SonataBluePrintBundle(),
			new Sonata\AdminBundle\SonataAdminBundle(),
			new Knplabs\MenuBundle\KnplabsMenuBundle(),

			new Chitanka\LibBundle\LibBundle(),
		);

		if (in_array($this->getEnvironment(), array('dev', 'test'))) {
			$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
		}

		return $bundles;
	}

	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		$loader->load($this->getConfigurationFile($this->getEnvironment()));
	}

	/**
	 * Returns the config_{environment}_local.yml file or
	 * the default config_{environment}.yml if it does not exist.
	 * Useful to override development password.
	 * Code from http://symfony2bundles.org
	 *
	 * @param string Environment
	 * @return The configuration file path
	 */
	protected function getConfigurationFile($environment, $format = 'yml')
	{
		$basePath = __DIR__.'/config/config_';
		$file = $basePath.$environment.'_local.'.$format;

		if (file_exists($file)) {
			return $file;
		}

		return $basePath.$environment.'.'.$format;
	}

	public function registerRootDir()
	{
		return __DIR__;
	}
}
