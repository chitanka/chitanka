<?php

class SqlImporter {

	private $db;

	public function __construct($dsn, $dbuser, $dbpassword) {
		$this->db = new \PDO($dsn, $dbuser, $dbpassword);
	}

	public function importFile($sqlFile) {
		$this->db->exec('SET FOREIGN_KEY_CHECKS=0');
		$this->db->exec('SET NAMES utf8');

		$sqlProc = new SqlFileProcessor($sqlFile);
		$db = $this->db;
		$sqlProc->walkThruQueries(function($query) use ($db) {
			echo substr($query, 0, 80), "\n";
			$result = $db->exec($query);
			if ($result === false) {
				error_log("Error by $query");
				error_log(print_r($db->errorInfo(), true));
			}
		});

		$this->db->exec('SET FOREIGN_KEY_CHECKS=1');
	}
}


class SqlFileProcessor {

	/** @var string */
	private $filename;

	/**
	 * @param string $filename
	 */
	public function __construct($filename) {
		$this->filename = $filename;
	}

	/**
	 * @param Closure|null $callback
	 */
	public function walkThruQueries($callback = null) {
		$reader = new FileLineReader($this->filename);
		$queries = [];
		if ($callback === null) {
			$callback = function($query) use ($queries) {
				$queries[] = $query;
			};
		}
		$queryBuf = '';
		while ($reader->hasMore()) {
			$line = $reader->readLine();
			if (empty($line) || $this->isComment($line) || $this->isInternalMysqlQuery($line)) {
				continue;
			}
			$queryBuf .= $line;
			if (substr($queryBuf, -1, 1) == ';') {
				$callback($queryBuf);
				$queryBuf = '';
			}
		}
		if ($queryBuf) {
			$callback($queryBuf);
		}
		$reader->close();

		return $queries;
	}

	/**
	 * @param string $line
	 * @return bool
	 */
	private function isComment($line) {
		return strpos($line, '--') === 0;
	}

	/**
	 * @param string $line
	 * @return bool
	 */
	private function isInternalMysqlQuery($line) {
		return strpos($line, '/*') === 0;
	}
}


class FileLineReader {

	/** @var bool */
	private $isGzipped;
	/** @var string */
	private $filename;
	/** @var resource */
	private $handle;

	/**
	 * @param string $filename
	 * @throws \Exception If the file could not be opened for reading
	 */
	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new \Exception("File '$filename' does not exist.");
		}
		if (!is_readable($filename)) {
			throw new \Exception("File '$filename' is not readable.");
		}
		$this->isGzipped = preg_match('/\.gz$/', $filename) == 1;
		$this->filename = $filename;
		$this->handle = $this->open();
		if (!$this->handle) {
			throw new \Exception("File '$this->filename' could not be opened for reading.");
		}
	}
	public function __destruct() {
		$this->close();
	}

	/**
	 * Open the given file and return the file handle
	 * @return resource
	 */
	private function open() {
		return $this->isGzipped ? gzopen($this->filename, 'r') : fopen($this->filename, 'r');
	}

	private function eof() {
		if ($this->handle) {
			return $this->isGzipped ? gzeof($this->handle) : feof($this->handle);
		}
		return null;
	}

	public function hasMore() {
		return !$this->eof();
	}

	/**
	 * @param bool $trim
	 * @return string
	 */
	public function readLine($trim = true) {
		$line = $this->gets();
		return $trim ? rtrim($line) : $line;
	}

	private function gets() {
		if ($this->handle) {
			return $this->isGzipped ? gzgets($this->handle) : fgets($this->handle);
		}
		return null;
	}

	public function close() {
		if ($this->handle) {
			$this->isGzipped ? gzclose($this->handle) : fclose($this->handle);
			$this->handle = null;
		}
	}
}
