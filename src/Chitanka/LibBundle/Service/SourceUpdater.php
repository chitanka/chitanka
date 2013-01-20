<?php
namespace Chitanka\LibBundle\Service;

class SourceUpdater extends FileUpdater
{

	public function lockFrontController()
	{
		rename($this->frontControllerName(), $this->lockedFrontControllerName());
		file_put_contents($this->frontControllerName(), $this->lockedFrontControllerContents());
	}

	public function unlockFrontController()
	{
		rename($this->lockedFrontControllerName(), $this->frontControllerName());
	}

	private function frontControllerName()
	{
		return "$this->rootDir/web/index.php";
	}
	private function lockedFrontControllerName()
	{
		return "$this->rootDir/index.php";
	}
	private function lockedFrontControllerContents()
	{
		return <<<PHP
<?php
header('HTTP/1.0 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 60');
?>
<!doctype html>
<p>Down for maintenance. Visit again in a couple of minutes.</p>
PHP;
	}

	public function clearCache()
	{
		foreach (array('prod', 'dev') as $env) {
			$this->rmdir("$this->rootDir/app/cache/$env");
		}
	}

}
