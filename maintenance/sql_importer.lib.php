<?php

class SqlImporter {

	private $db;

	public function __construct($dsn, $dbuser, $dbpassword) {
		$this->db = new PDO($dsn, $dbuser, $dbpassword);
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

	private $filename;

	public function __construct($filename) {
		$this->filename = $filename;
	}

	/**
	 * @param Closure $callback
	 */
	public function walkThruQueries($callback = null) {
		$reader = new FileLineReader($this->filename);
		$queries = array();
		if ($callback === null) {
			$callback = function($query) use ($queries) {
				$queries[] = $query;
			};
		}
		$queryBuf = '';
		while ($reader->hasMore()) {
			$line = $reader->readLine();
			if (empty($line) || $this->isComment($line) || $this->isInternMysqlQuery($line)) {
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
	 */
	private function isComment($line) {
		return strpos($line, '--') === 0;
	}

	/**
	 * @param string $line
	 */
	private function isInternMysqlQuery($line) {
		return strpos($line, '/*') === 0;
	}
}


class FileLineReader {

	private $isGzipped;
	private $filename;
	private $handle;

	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new \Exception("File '$filename' does not exist.");
		}
		if (!is_readable($filename)) {
			throw new \Exception("File '$filename' is not readable.");
		}
		$this->isGzipped = preg_match('/\.gz$/', $filename);
		$this->filename = $filename;
		$this->handle = $this->open();
	}
	public function __destruct() {
		if ($this->handle) {
			$this->close();
		}
	}

	public function open() {
		$handle = $this->isGzipped ? gzopen($this->filename, 'r') : fopen($this->filename, 'r');
		if (!$handle) {
			throw new \Exception("File '$this->filename' could not be opened for reading.");
		}
		return $this->handle = $handle;
	}

	public function eof() {
		return $this->isGzipped ? gzeof($this->handle) : feof($this->handle);
	}

	public function hasMore() {
		return !$this->eof();
	}

	public function readLine($trim = true) {
		$line = $this->gets();
		return $trim ? rtrim($line) : $line;
	}

	public function gets() {
		return $this->isGzipped ? gzgets($this->handle) : fgets($this->handle);
	}

	public function close() {
		$this->isGzipped ? gzclose($this->handle) : fclose($this->handle);
		$this->handle = null;
	}
}
