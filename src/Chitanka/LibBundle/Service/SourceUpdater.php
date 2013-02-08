<?php
namespace Chitanka\LibBundle\Service;

class SourceUpdater extends FileUpdater
{

	public function lockFrontController()
	{
		$lockedContent = str_replace("//".$this->lockMethodCall(), $this->lockMethodCall(), file_get_contents($this->frontControllerName()));
		file_put_contents($this->frontControllerName(), $lockedContent);
	}

	public function unlockFrontController()
	{
		$contents = file_get_contents($this->frontControllerName());
		if (strpos($contents, "//".$this->lockMethodCall()) !== false) {
			// already unlocked
			return;
		}
		$unlockedContent = str_replace($this->lockMethodCall(), "//".$this->lockMethodCall(), $contents);
		file_put_contents($this->frontControllerName(), $unlockedContent);
	}

	protected function onAfterExtract(\ZipArchive $zip)
	{
		if ($zip->locateName('app/config/parameters.yml.dist') !== false) {
			$yamlUpdater = new ParametersYamlUpdater;
			$configDir = "$this->rootDir/app/config";
			$yamlUpdater->update("$configDir/parameters.yml.dist", "$configDir/parameters.yml");
		}
	}

	private function frontControllerName()
	{
		return "$this->rootDir/web/index.php";
	}

	private function lockMethodCall()
	{
		return "exitWithMessage('maintenance');";
	}
}
