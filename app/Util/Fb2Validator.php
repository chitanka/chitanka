<?php namespace App\Util;

class Fb2Validator {
	private $schema;

	public function __construct($schema = null) {
		$this->schema = $schema ?: __DIR__.'/FictionBook2.21.xsd';

		// Enable user error handling
		libxml_use_internal_errors(true);
	}

	public function isValid($xml) {
		$doc = new \DOMDocument;
		if (strpos($xml, '<') === false && file_exists($xml)) { // a filename
			$doc->load($xml);
		} else { // a string
			$doc->loadXML($xml);
		}

		return $doc->schemaValidate($this->schema);
	}

	public function getErrors() {
		$errors = [];
		foreach (libxml_get_errors() as $error) {
			$errors[] = $this->formatError($error);
		}
		libxml_clear_errors();

		return implode("\n", $errors);
	}

	private function formatError($error) {
		$titles = [
			LIBXML_ERR_WARNING	=> "Warning",
			LIBXML_ERR_ERROR	=> "Error",
			LIBXML_ERR_FATAL	=> "Fatal error",
		];
		return <<<MSG

{$titles[$error->level]} {$error->code}:
{$error->message}
    in {$error->file}
    on line {$error->line}

MSG;
	}
}
