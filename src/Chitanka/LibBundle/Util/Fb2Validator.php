<?php
namespace Chitanka\LibBundle\Util;

class Fb2Validator
{
	private $schema;

	public function __construct($schema = null)
	{
		$this->schema = $schema ?: __DIR__.'/FictionBook2.21.xsd';

		// Enable user error handling
		libxml_use_internal_errors(true);
	}

	public function isValid($xml)
	{
		$doc = new \DOMDocument;
		if (strpos($xml, '<') === false && file_exists($xml)) { // a filename
			$doc->load($xml);
		} else { // a string
			$doc->loadXML($xml);
		}

		return $doc->schemaValidate($this->schema);
	}

	public function getErrors()
	{
		$errors = array();
		foreach (libxml_get_errors() as $error) {
			$errors[] = $this->formatError($error);
		}
		libxml_clear_errors();

		return implode("\n", $errors);
	}

	/**
	* from Mike A. (17-Feb-2006 09:03)
	* http://de3.php.net/manual/en/domdocument.schemavalidate.php
	*/
	private function formatError($error)
	{
		$return = "\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= "Warning $error->code: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "Error $error->code: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "Fatal Error $error->code: ";
				break;
		}
		$return .= "\n" . trim($error->message);
		if ($error->file) {
			$return .= "\n\tin $error->file";
		}
		$return .= "\n\ton line $error->line\n";

		return $return;
	}
}
