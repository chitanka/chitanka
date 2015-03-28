<?php namespace App\Service;

/**
 */
class ParametersYamlUpdater {
	public function update($distFileName, $yamlFileName) {
		$yamlBackupFileName = $yamlFileName.'.bak';
		if ( ! copy($yamlFileName, $yamlBackupFileName)) {
			error_log("Failed to create '$yamlBackupFileName'");
			return false;
		}
		$updatedYaml = $this->updateYaml(file_get_contents($distFileName), file_get_contents($yamlFileName));
		return file_put_contents($yamlFileName, $updatedYaml);
	}

	/**
	 * @param string $distYaml
	 * @param string $yaml
	 */
	public function updateYaml($distYaml, $yaml) {
		if ( ! preg_match_all('/^    (\w[^:]+):(.*)/m', $yaml, $matches, PREG_SET_ORDER)) {
			return $yaml;
		}
		$replacements = [];
		foreach ($matches as $match) {
			$replacements['/^(    '. preg_quote(trim($match[1])) .':).+/m'] = '$1' . str_replace('$', '\$', $match[2]);
		}

		return preg_replace(array_keys($replacements), array_values($replacements), $distYaml);
	}
}
