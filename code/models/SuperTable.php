<?php

class DBException extends Exception {}
class DBExceptionBindFailure extends DBException {}
class DBExceptionQuestionMarks extends DBException {}
class DBExceptionMultiInsert extends DBException {}
class DBExceptionDelete extends DBException {}
class DBExceptionTableName extends DBException {}
class DBExceptionSelect extends DBException {}

abstract class SuperTable {

	const NAME = false;

	// currencies
	const CURRENCY_US = 'us';
	const CURRENCY_SE = 'se';
	const CURRENCY_NO = 'no';
	const CURRENCY_UK = 'uk';
	const CURRENCY_AU = 'au';

	// inspection, son?
	static $currencies = array(
		self::CURRENCY_US,
		self::CURRENCY_UK,
		self::CURRENCY_SE,
		self::CURRENCY_NO,
		self::CURRENCY_AU,
	);

	/**
	 * @return SuperTable
	 */
	static function factory() {
		static $instance;
		if (!isset($instance)) {
			$instance = new static();
		}
		return $instance;
	}

	public function tableName() {
		if (static::NAME == false) {
			throw new DBExceptionTableName('The class' . get_class($this) . ' failed to define the table name');
		}
		return static::NAME;
	}

	private function getConnection($ping=true) {
		/** @var $conn mysqli */
		static $conn;
		if ($ping && isset($conn)) {
			// check that we're still live
			$conn->ping();
		}
		if (!isset($conn)) {
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_SCHEMA);
			if ($conn->connect_error) {
				throw new Exception('Connect Error (' . $conn->connect_errno . ') '. $conn->connect_error);
			}
			$conn->set_charset('utf8');
		}
		return $conn;
	}

	public function getLastError() {
		return $this->getConnection(false)->error;
	}

	public function getLastErrno() {
		return $this->getConnection(false)->errno;
	}


	/**
	 * Execute a bound statement
	 *
	 * @throws Exception
	 * @param string $sql
	 * @param string $bindstypes
	 * @param array $binds
	 * @return mysqli_stmt
	 */
	public function bound_query($sql, $bindstypes='', $binds=array()) {
//		clie('Bound Query: '. $sql);
//		clie('Bound Types: '. $bindstypes);
//		clie('Bound Parms: '. print_r($binds,1));
		$sql = trim($sql);
		if ($stmt = $this->getConnection()->prepare($sql)) {
			if (count($binds)) {
				call_user_func_array(array($stmt, 'bind_param'), $this->makeRefs($bindstypes, $binds));
			}
			$stmt->execute();
			return $stmt;
		}
		throw new DBExceptionBindFailure('Could not bind this: ' . $sql);
	}

	private function makeRefs($bindstypes, $arr){
        $refs = array($bindstypes);
        foreach($arr as $key => $value)
            $refs[$key+1] = &$arr[$key];
        return $refs;
	}

	/**
	 * Execute a simple query
	 *
	 * @param string $sql
	 * @return bool|mysqli_result
	 */
	public function query($sql) {
		// do a simple, unbound query
		return $this->getConnection()->query(trim($sql));
	}

	/**
	 * Insert a single record or multiple records at once
	 *
	 * @throws DBExceptionMultiInsert
	 * @param array $rows Either an array of column:value pairs, or an array or arrays with column:value pairs
	 * @return int
	 */
	public function insert($rows) {

//		$item    = array('col' => 'val');
//		$itemset = array(
//			array('col' => 'val'),
//			array('col' => 'val')
//		);

		if (isset($rows[0]) && is_array($rows[0])) {
			// set of inserts
			$multi = true;

			$keys = array_keys($rows[0]);
			$keyCount = count($keys);

			$bindstypes = '';
			$values = $binds = array();
			foreach($rows as $set) {
				if (count($set) != $keyCount) {
					throw new DBExceptionMultiInsert('A row in the insert set does not have the correct number of key:value pairs. All entries must match.');
				}
				// use the keys array to assing binds so they are always in the right order
				foreach($keys as $keyId) {
					$binds[] = $set[$keyId];
					$bindstypes .= static::$bind_types[$keyId];
				}
				$values[] = $this->questionMarks($keyCount);
			}

			$values = implode('), (', $values);
		}
		else {
			// single insert
			$multi = false;

			$keys = array_keys($rows);
			$binds = array_values($rows);
			$bindstypes = '';
			foreach($keys as $key) {
				$bindstypes .= static::$bind_types[$key];
			}
			$values = $this->questionMarks($binds);

		}

		$a = $this->bound_query('INSERT INTO `' . $this->tableName() . '` (`'. implode('`, `', $keys) .'`) VALUES ('. $values .')', $bindstypes, $binds);

		if ($multi) {
			$affected = $a->affected_rows;
			$a->close();
			return $affected;
		}
		else {
			$id = $a->insert_id;
			$a->close();
			return $id;
		}

	}

	/**
	 * Based on the number or array, return the correct "?,?,?,?,?" pattern
	 *
	 * @throws DBExceptionQuestionMarks
	 * @param array|int $obj
	 * @return string
	 */
	public function questionMarks($obj) {
		if (is_array($obj)) {
			return trim(str_repeat('?,', count($obj)), ',');
		}
		elseif (is_numeric($obj)) {
			return trim(str_repeat('?,', $obj), ',');
		}
		else {
			throw new DBExceptionQuestionMarks();
		}
	}

	/**
	 * Delete some rows based on a where statement
	 *
	 * @throws DBExceptionDelete
	 * @param array $where
	 * @param int $limit How many rows to limit this delete to, if provided.
	 * @return int Number of rows deleted
	 */
	public function delete($where=array(), $limit=0) {

		if (count($where)==0) {
			throw new DBExceptionDelete('Must provide a where clause');
		}

		list($whereClause, $bindtypes, $binds) = $this->whereClause($where);

		if ($limit && is_numeric($limit) && $limit > 0) {
			$limit = ' LIMIT ' . $limit;
		}
		else {
			$limit = '';
		}

		$a = $this->bound_query('DELETE FROM `' . $this->tableName() . '` WHERE ' . $whereClause . $limit, $bindtypes, $binds);
		$affected = $a->affected_rows;
		$a->close();
		return $affected;

	}

	/**
	 * Find columns based on the $where and update their values from the $set
	 *
	 * @param array $set
	 * @param array $where
	 * @param int $limit How many rows to limit this update to, if provided.
	 * @return int Number of affected rows
	 */
	public function update($set, $where=array(), $limit=0) {

		list($setClause, $setbindstypes, $setBinds) = $this->updateClause($set);
		list($whereClause, $wherebindstypes, $whereBinds) = $this->whereClause($where);

		$binds = array_merge($setBinds, $whereBinds);

		if ($limit && is_numeric($limit) && $limit > 0) {
			$limit = ' LIMIT ' . $limit;
		}
		else {
			$limit = '';
		}

		$a = $this->bound_query('UPDATE `' . $this->tableName() . '` SET '. $setClause .' WHERE ' . $whereClause . $limit, $setbindstypes . $wherebindstypes, $binds);
		$affected = $a->affected_rows;
		$a->close();
		return $affected;
	}

	/**
	 * Generate a list of "key = ?" statements joined by "AND" for where clauses. Also sends back bindtypes and binds
	 *
	 * @param array $where
	 * @return array
	 */
	private function whereClause($where=array()) {
		return $this->splitClause($where, ' AND ');
	}

	/**
	 * Generate a list of "key = ?" statements joined by commas for update clauses. Also sends back bindtypes and binds
	 *
	 * @param array $where
	 * @return array
	 */
	private function updateClause($where=array()) {
		return $this->splitClause($where, ', ');
	}

	private function splitClause($where, $joiner) {
		$clause = $binds = array();
		$bindstypes = '';
		$columns = $this->getColumns();
		foreach ($where as $col => $val) {
			if (in_array($col, $columns)) {
				$clause[] = '`'. $col .'` = ?';
			}
			else {
				$clause[] = $col . ' = ?';
			}
			$binds[] = $val;
			$bindstypes .= static::$bind_types[$col];
		}
		return array(implode($joiner, $clause), $bindstypes, $binds);
	}

	/**
	 * Create a mysql datetime formatted string from a timestamp
	 *
	 * @param int|null $ts Optional: Specific a unix timestamp for the date to be generated from
	 * @return string
	 */
	static function mysql_now($ts=null) {
		if ($ts===null) {
			$ts = time();
		}
        return date('Y-m-d H:i:s', $ts);
    }

	private function getColumns() {
		static $cols;
		if (!isset($cols)) {
			$cols = array();
		}
		$myclass = get_class($this);
		if (!isset($cols[$myclass])) {
			$r = new ReflectionClass($myclass);
			$consts = $r->getConstants();
			$cols[$myclass] = array();
			foreach ($consts as $constname => $constval) {
				if (substr($constname, 0, 2) == 'C_') {
					$cols[$myclass][$constname] = $constval;
				}
			}
		}
		return $cols[$myclass];
	}

	public function get_col($col, $where=array()) {
		$results = $this->get_row(array($col), $where);
		return isset($results[$col]) ? $results[$col] : null;
	}

	public function get_row($cols=array(), $where=array()) {
		$results = $this->get_results($cols, $where);
		return count($results) ? $results[0] : null;
	}

	public function makeColSelect($cols) {

		$select = array();
		$columns = $this->getColumns();

		foreach ($cols as $colval) {
			if (in_array($colval, $columns)) {
				$select[] = '`' . $colval . '`';
			}
			else {
				$select[] = $colval;
			}
		}

		if (count($select)==0) {
			throw new DBExceptionSelect('Empty selection set.');
		}

		return $select;
	}

	public function get_results($cols=array(), $where=array(), $limit=0, $orderby=array()) {

		$select = $this->makeColSelect($cols);

		list($whereClause, $bindtypes, $binds) = $this->whereClause($where);

		if (!empty($whereClause)) {
			$whereClause = ' WHERE ' . $whereClause;
		}

		if ($limit && is_numeric($limit) && $limit > 0) {
			$limit = ' LIMIT ' . $limit;
		}
		else {
			$limit = '';
		}

		$orderbyclause = '';
		if (is_array($orderby) && count($orderby)) {
			$columns = $this->getColumns();
			$orderbyset = array();
			foreach ($orderby as $col => $order) {
				if (in_array($order, $columns)) {
					$orderbyset[] = '`'. $order .'` DESC';
				}
				elseif (in_array($col, $columns) AND in_array(strtoupper($order), array('DESC', 'ASC'))) {
					$orderbyset[] = '`'. $col.'` '. strtoupper($order);
				}

			}
			if (count($orderbyset)) {
				$orderbyclause = ' ORDER BY ' . implode(', ', $orderbyset);
			}
		}

		$sql = 'SELECT '. implode(', ', $select) .' FROM `' . $this->tableName() . '`' . $whereClause . $orderbyclause . $limit;

		return $this->sql_get_results($sql, $bindtypes, $binds);

	}

	public function sql_get_results($sql, $bindtypes='', $binds=array()) {

		if (count($binds)) {
			$result = $this->bound_query($sql, $bindtypes, $binds);
		}
		else {
			$result = $this->query($sql);
		}

		$rows = $this->fetchFromResult($result);

		$result->close();

		return $rows;

	}

	/**
	 * @param mysqli_stmt|mysqli_result $result
	 * @return array
	 */
	private function fetchFromResult($result)
{
		$array = array();

		if($result instanceof mysqli_stmt) {
			/** @var $result mysqli_stmt */
			$result->store_result();

			$variables = array();
			$data = array();
			$meta = $result->result_metadata();

			while($field = $meta->fetch_field()) {
				$variables[] = &$data[$field->name]; // pass by reference
			}

			call_user_func_array(array($result, 'bind_result'), $variables);

			$i=0;
			while($result->fetch()) {
				$array[$i] = array();
				foreach($data as $k=>$v) {
					$array[$i][$k] = $v;
				}
				$i++;
				// don't know why, but when I tried $array[] = $data, I got the same one result in all rows
			}
		}
		elseif($result instanceof mysqli_result) {
			/** @var $result mysqli_result */
			while($row = $result->fetch_assoc()) {
				$array[] = $row;
			}
		}

		return $array;
	}

}
