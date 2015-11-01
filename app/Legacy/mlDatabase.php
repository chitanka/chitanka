<?php namespace App\Legacy;

class mlDatabase {

	private $server;
	private $user;
	private $pass;
	private $dbName;
	/**
		Connection to the database
		@var \mysqli
	*/
	private $conn;
	private $logFile;
	private $errLogFile;

	public function __construct($server, $user, $pass, $dbName) {
		$this->server = $server;
		$this->user = $user;
		$this->pass = $pass;
		$this->dbName = $dbName;
		$date = date('Y-m-d');
		$this->logFile = __DIR__."/../../var/log/db-$date.sql";
		$this->errLogFile = __DIR__."/../../var/log/db-error-$date";
	}

	public function exists($table, $keys = []) {
		return $this->getCount($table, $keys) > 0;
	}

	public function getFields($table, $dbkey, $fields) {
		$res = $this->select($table, $dbkey, $fields);
		if ( $this->numRows($res) == 0 ) {
			return null;
		}
		$row = $this->fetchRow($res);
		return count($row) > 1 ? $row : $row[0];
	}

	public function getFieldsMulti($table, $dbkey, $fields) {
		$res = $this->select($table, $dbkey, $fields);
		$data = [];
		while ( $row = $this->fetchRow($res) ) {
			$data[] = count($row) > 1 ? $row : $row[0];
		}
		return $data;
	}

	public function getRandomRow($table) {
		$res = $this->select($table, [], ['MIN(id)', 'MAX(id)']);
		list($min, $max) = $this->fetchRow($res);
		do {
			$res = $this->select($table, ['id' => rand($min, $max)]);
			$row = $this->fetchAssoc($res);
			if ( !empty($row) ) return $row;
		} while (true);
		return [];
	}

	public function getCount($table, $keys = []) {
		$res = $this->select($table, $keys, 'COUNT(*)');
		list($count) = mysqli_fetch_row($res);
		return (int) $count;
	}

	public function select($table, $keys = [], $fields = [], $orderby = '', $offset = 0, $limit = 0, $groupby = '') {
		return $this->query($this->selectQ($table, $keys, $fields, $orderby, $offset, $limit, $groupby));
	}

	public function selectQ($table, $keys = [], $fields = [], $orderby = '', $offset = 0, $limit = 0, $groupby = '') {
		settype($fields, 'array');
		$sel = empty($fields) ? '*' : implode(', ', $fields);
		$sorder = empty($orderby) ? '' : ' ORDER BY '.$orderby;
		$sgroup = empty($groupby) ? '' : ' GROUP BY '.$groupby;
		$slimit = $limit > 0 ? " LIMIT $offset, $limit" : '';
		return "SELECT $sel FROM $table".$this->makeWhereClause($keys).
			$sgroup . $sorder . $slimit;
	}

	public function extselect($qparts) {
		return $this->query( $this->extselectQ($qparts) );
	}

	/**
	 * Build an SQL SELECT statement with LEFT JOIN clause(s) from an array
	 * (Idea from phpBB).
	 * @param $qparts Associative array with following possible keys:
	 *                SELECT, FROM, LEFT JOIN, WHERE, GROUP BY, ORDER BY, LIMIT
	 * @param bool $distinct
	 * @return string
	 */
	public function extselectQ($qparts, $distinct = false) {
		$qd = $distinct ? ' DISTINCT' : '';
		$q = "SELECT$qd $qparts[SELECT] FROM $qparts[FROM]";
		if ( isset($qparts['LEFT JOIN']) ) {
			foreach ($qparts['LEFT JOIN'] as $table => $onrule) {
				$q .= " LEFT JOIN $table ON ($onrule)";
			}
		}
		if ( isset($qparts['WHERE']) ) {
			$q .= $this->makeWhereClause($qparts['WHERE']);
		}
		foreach ( ['GROUP BY', 'ORDER BY'] as $key ) {
			if ( isset($qparts[$key]) ) {
				$q .= " $key $qparts[$key]";
			}
		}
		if ( isset($qparts['LIMIT']) ) {
			if ( is_array($qparts['LIMIT']) ) {
				list($offset, $limit) = $qparts['LIMIT'];
			} else {
				$offset = 0;
				$limit = (int) $qparts['LIMIT'];
			}
			$q .= $limit > 0 ? " LIMIT $offset, $limit" : '';
		}
		return $q;
	}

	public function insert($table, $data, $ignore = false, $putId = true) {
		return $this->query($this->insertQ($table, $data, $ignore, $putId));
	}

	public function insertQ($table, $data, $ignore = false, $putId = true) {
		if ( empty($data) ) {
			return '';
		}

		if ($putId && ! array_key_exists('id', $data) && ($id = $this->autoIncrementId($table)) ) {
			$data['id'] = $id;
		}

		$signore = $ignore ? ' IGNORE' : '';
		return "INSERT$signore INTO $table". $this->makeSetClause($data);
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param string[] $fields
	 * @param bool $ignore
	 */
	public function multiinsertQ($table, $data, $fields, $ignore = false) {
		if ( empty($data) || empty($fields) ) {
			return '';
		}
		$vals = ' (`'. implode('`, `', $fields) .'`) VALUES';
		$fcnt = count($fields);
		foreach ($data as $rdata) {
			$vals .= ' (';
			for ($i=0; $i < $fcnt; $i++) {
				$val = isset($rdata[$i]) ? $this->normalizeValue($rdata[$i]) : "''";
				$vals .= $val .', ';
			}
			$vals = rtrim($vals, ' ,') .'),';
		}
		$signore = $ignore ? ' IGNORE' : '';
		return "INSERT$signore INTO $table". rtrim($vals, ',');
	}

	public function updateQ($table, $data, $keys) {
		if ( empty($data) ) { return ''; }
		if ( empty($keys) ) { return $this->insertQ($table, $data, true); }
		if ( !is_array($keys) ) {
			$keys = ['id' => $keys];
		}
		return 'UPDATE '. $table . $this->makeSetClause($data) .
			$this->makeWhereClause($keys);
	}

	public function replaceQ($table, $data) {
		if ( empty($data) ) { return ''; }
		return 'REPLACE '.$table.$this->makeSetClause($data);
	}

	public function delete($table, $keys, $limit = 0) {
		return $this->query( $this->deleteQ($table, $keys, $limit) );
	}

	public function deleteQ($table, $keys, $limit = 0) {
		if ( empty($keys) ) { return ''; }
		if ( !is_array($keys) ) $keys = ['id' => $keys];
		$q = 'DELETE FROM '. $table . $this->makeWhereClause($keys);
		if ( !empty($limit) ) $q .= " LIMIT $limit";
		return $q;
	}

	private function makeSetClause($data, $putKeyword = true) {
		if ( empty($data) ) { return ''; }
		$keyword = $putKeyword ? ' SET ' : '';
		$cl = [];
		foreach ($data as $field => $value) {
			if ($value === null) {
				continue;
			}
			if ( is_numeric($field) ) { // take the value as is
				$cl[] = $value;
			} else {
				$cl[] = "`$field` = ". $this->normalizeValue($value);
			}
		}
		return $keyword . implode(', ', $cl);
	}

	/**
	@param $keys Array with mixed keys (associative and numeric).
		By numeric key take the value as is if the value is a string, or send it
		recursive to makeWhereClause() with OR-joining if the value is an array.
		By string key use “=” for compare relation if the value is string;
		if the value is an array, use the first element as a relation and the
		second as comparison value.
		An example follows:
		$keys = array(
			'k1 <> 1', // numeric key, string value
			array('k2' => 2, 'k3' => 3), // numeric key, array value
			'k4' => 4, // string key, scalar value
			'k5' => array('>=', 5), // string key, array value (rel, val)
		)
	@param $join How to join the elements from $keys
	@param $putKeyword Should the keyword “WHERE” precede the clause
	*/
	private function makeWhereClause($keys, $join = 'AND', $putKeyword = true) {
		if ( empty($keys) ) {
			return $putKeyword ? ' WHERE 1' : '';
		}
		$cl = $putKeyword ? ' WHERE ' : '';
		$whs = [];
		foreach ($keys as $field => $rawval) {
			if ( is_numeric($field) ) { // take the value as is
				$field = $rel = '';
				if ( is_array($rawval) ) {
					$njoin = $join == 'AND' ? 'OR' : 'AND';
					$val = '('.$this->makeWhereClause($rawval, $njoin, false).')';
				} else {
					$val = $rawval;
				}
			} else {
				if ( is_array($rawval) ) {
					list($rel, $val) = $rawval;
					if (($rel == 'IN' || $rel == 'NOT IN') && is_array($val)) {
						// set relation — build an SQL set
						$cb = [$this, 'normalizeValue'];
						$val = '('. implode(', ', array_map($cb, $val)) .')';
					} else {
						$val = $this->normalizeValue($val);
					}
				} else {
					$rel = '='; // default relation
					$val = $this->normalizeValue($rawval);
				}
			}
			$whs[] = "$field $rel $val";
		}
		$cl .= '('. implode(") $join (", $whs) . ')';
		return $cl;
	}

	private function normalizeValue($value) {
		if ( is_bool($value) ) {
			$value = $value ? 1 : 0;
		} else if ($value instanceof \DateTime) {
			$value = $value->format('Y-m-d H:i:s');
		} else {
			$value = $this->escape($value);
		}
		return '\''. $value .'\'';
	}

	public function escape($string) {
		if ( !isset($this->conn) ) { $this->connect(); }
		return $this->conn->escape_string($string);
	}

	/**
		Send a query to the database.
		@param string $query
		@param bool $useBuffer Use buffered or unbuffered query
		@return resource
	*/
	public function query($query, $useBuffer = true) {
		if ( !isset($this->conn) ) { $this->connect(); }
		$res = $useBuffer
			? $this->conn->query($query)
			: $this->conn->query($query, MYSQLI_USE_RESULT);
		if ( !$res ) {
			$errno = $this->conn->errno;
			$error = $this->conn->error;
			$this->log("Error $errno: $error\nQuery: $query\n", true);
			throw new \Exception("A database query made a boo boo. Check the log.");
		}
		return $res;
	}

	/**
	 * @param resource $result
	 * @return array Associative array
	 */
	public function fetchAssoc($result) {
		return mysqli_fetch_assoc($result);
	}

	/**
	 * @param resource $result
	 * @return array
	 */
	public function fetchRow($result) {
		return mysqli_fetch_row($result);
	}

	/**
	 * @param resource $result
	 * @return int
	 */
	public function numRows($result) {
		return mysqli_num_rows($result);
	}

	/**
	 * Return next auto increment for a table
	 * @param string $tableName
	 * @return int
	 */
	public function autoIncrementId($tableName) {
		$res = $this->query('SHOW TABLE STATUS LIKE "'.$tableName.'"');
		$row = mysqli_fetch_assoc($res);
		return $row['Auto_increment'];
	}

	private function connect() {
		$this->conn = new \mysqli($this->server,  $this->user,  $this->pass, $this->dbName);
		$this->conn->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
	}

	private function log($msg, $isError = true) {
		file_put_contents($isError ? $this->errLogFile : $this->logFile,
			'/*'.date('Y-m-d H:i:s').'*/ '. $msg."\n", FILE_APPEND);
	}

}
