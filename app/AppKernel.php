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
			new Symfony\Bundle\AsseticBundle\AsseticBundle(),
			new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
			new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
//			new JMS\AopBundle\JMSAopBundle(),
//			new JMS\DiExtraBundle\JMSDiExtraBundle($this),
//			new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),

			//new FOS\UserBundle\FOSUserBundle(),
			new Sonata\CoreBundle\SonataCoreBundle(),
			new Sonata\AdminBundle\SonataAdminBundle(),
			new Sonata\BlockBundle\SonataBlockBundle(),
			//new Sonata\CacheBundle\SonataCacheBundle(),
			new Sonata\jQueryBundle\SonatajQueryBundle(),
			new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
			new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),
			new Knp\Bundle\MenuBundle\KnpMenuBundle(),
			new JMS\SerializerBundle\JMSSerializerBundle($this),
			new FOS\RestBundle\FOSRestBundle(),
			new FOS\CommentBundle\FOSCommentBundle(),
			new Sensio\Bundle\BuzzBundle\SensioBuzzBundle(),

			new App\App(),
		);

		if ($this->getEnvironment() != 'prod') {
			$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
//			$bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
		}

		return $bundles;
	}

	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		$loader->load($this->getConfigurationFile($this->getEnvironment()));
	}

	/**
	 * Returns the configuration file for the given environment and format: config_{environment}.{format}.
	 *
	 * @param string $environment   Application environment
	 * @param string $format        File format (default: yml)
	 * @return The configuration file path
	 */
	protected function getConfigurationFile($environment, $format = 'yml')
	{
		return __DIR__."/config/config_$environment.$format";
	}
}
