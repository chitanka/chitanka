<?php
require __DIR__.'/../vendor/autoload.php';

$kernel = new AppKernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();/* @var $container \Symfony\Component\DependencyInjection\Container */

$epubUrl = filter_input(INPUT_GET, 'epub') ?? '';
$targetFormat = filter_input(INPUT_GET, 'out') ?? '';

$webCacheDir = '/cache/dl';
$cacheDir = __DIR__.$webCacheDir;

$converter = new App\Generator\EpubConverter($container->getParameterBag(), $cacheDir);
try {
	$outputFile = $converter->convert($epubUrl, $targetFormat);
} catch (InvalidArgumentException $e) {
	http_response_code(400);
	header("X-Exception: {$e->getMessage()}");
	exit;
} catch (Exception $e) {
	http_response_code(500);
	exit;
}
$webOutputFile = $webCacheDir.'/'.basename($outputFile);

header("Location: $webOutputFile");
