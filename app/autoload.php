<?php

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
	'Symfony'          => array(__DIR__.'/../vendor/symfony/src', __DIR__.'/../vendor/bundles'),
	'Sensio'           => __DIR__.'/../vendor/bundles',
	'JMS'              => __DIR__.'/../vendor/bundles',
	'Doctrine\\Common' => __DIR__.'/../vendor/doctrine-common/lib',
	//'Doctrine\\DBAL\\Migrations'     => __DIR__.'/../vendor/doctrine-migrations/lib',
	'Doctrine\\DBAL'   => __DIR__.'/../vendor/doctrine-dbal/lib',
	'Doctrine'         => __DIR__.'/../vendor/doctrine/lib',
	'Monolog'          => __DIR__.'/../vendor/monolog/src',
	'Assetic'          => __DIR__.'/../vendor/assetic/src',
	'Chitanka'         => __DIR__.'/../src',
	'FOS'              => __DIR__.'/../src',
	'Sonata'           => __DIR__.'/../src',
	'Knplabs'          => __DIR__.'/../src',
));
$loader->registerPrefixes(array(
	'Twig_Extensions_' => __DIR__.'/../vendor/twig-extensions/lib',
	'Twig_'            => __DIR__.'/../vendor/twig/lib',
	'Swift_'           => __DIR__.'/../vendor/swiftmailer/lib/classes',
	'Sfblib_'          => __DIR__.'/../vendor/sfblib/lib',
));
$loader->register();
$loader->registerPrefixFallback(array(
	__DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs',
));
