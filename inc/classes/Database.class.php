<?php

use K_Load\Util;

class Database {

	public static $conn;

	private static $host;
	private static $port;
	private static $user;
	private static $pass;
	private static $db;

	private static $sql = '';
	private static $type;
	private static $fake_instance;


	public function __construct() { }

	public static function clear() {
		self::$host = null;
		self::$port = null;
		self::$user = null;
		self::$pass = null;
		self::$db = null;
	}

	public static function ping() {
		return isset(self::$conn);
	}

	public static function connect($mysql = null) {
		self::$host = $mysql['host'] ?? 'localhost';
		self::$port = (int)$mysql['port'] ?? 3306;
		self::$user = $mysql['user'] ?? 'root';
		self::$pass = $mysql['pass'] ?? '';
		self::$db = $mysql['db'] ?? '';

		set_error_handler(function() {});
		self::$conn = new \mysqli(self::$host.(self::$host != 'localhost' ? ':'.self::$port : ''), self::$user, self::$pass, self::$db, self::$port);
		restore_error_handler();
		if (self::$conn->connect_error) {
			Util::log('mysql', '[FAIL] '.self::$conn->connect_error);
			self::$conn = null;
			return;
		}
		self::$conn->set_charset('utf8mb4');
		self::$conn->query("SET collation_connection = utf8mb4_unicode_ci");
	}

	public static function disconnect() {
		self::$conn->close();
	}

	public static function conn() {
		if (self::$fake_instance === null) {
			self::$fake_instance = new self;
		}
		return self::$fake_instance;
	}

	public static function escape($sql, array $data) {
		$params = substr_count($sql, '?');

		if ($params > 0) {
			for ($x = 0; $x < $params; $x++) {
				if (is_array($data[$x])) {
					$data[$x] = json_encode($data[$x], JSON_UNESCAPED_UNICODE);
				}
				$sql = preg_replace('/\?/', self::$conn->real_escape_string($data[$x]), $sql, 1);
			}
		}

		return $sql;
	}

	public function add($sql, array $data = []) {
		if (count($data) > 0) {
			$sql = self::escape($sql, $data);
		}
		self::$sql .= " $sql";
		return $this;
	}

	public static function quickEscape($data) {
		return self::$conn->real_escape_string($data);
	}

	public static function run($sql) {
		$result = self::$conn->query($sql);
		if ($result) {
			Util::log('mysql', '[QUERY] - '.$sql);
		} else {
			Util::log('mysql', '[FAIL] - '.self::$conn->error."\n\t\t\t\t\t\t\t".'- Query: '.$sql);
		}
	}

	public function count($table) {
		self::$type = 'count';
		self::$sql = "SELECT COUNT(*) FROM `$table`";
		return $this;
	}

	public function insert($sql) {
		self::$type = 'insert';
		self::$sql = $sql;
		return $this;
	}

	public function values(array $values) {
		self::$sql .= " VALUES";

		$x = 0;
		$val_count = count($values);

		foreach ($values as $row => $value) {
			$x++;
			self::$sql .= '(';

			$i = 0;
			$row_count = count($value);

			foreach ($value as $data) {
				$i++;
				$escaped = ($i < $row_count  ? "'".self::quickEscape($data)."'," : "'".self::quickEscape($data)."'");
				self::$sql .= $escaped;
			}

			self::$sql .= ')'.($x < $val_count ? ',' : '');
		}
		return $this;
	}

	public function select($sql, array $data = []) {
		self::$type = 'select';
		if (count($data) > 0) {
			$sql = self::escape($sql, $data);
		}
		self::$sql = $sql;
		return $this;
	}

	public function delete($table) {
		self::$type = 'delete';
		self::$sql = "DELETE FROM $table";
		return $this;
	}

	public function distinct(...$columns) {
		if (self::$type == 'count') {
			if ($columns) {
				self::$sql = str_replace('COUNT(*)', 'COUNT(DISTINCT `'.implode(',`', $columns).'`)', self::$sql);
			}
		} else {
			self::$sql = str_replace('SELECT', 'SELECT DISTINCT', self::$sql);
		}
		return $this;
	}

	public function where($sql, array $data = []) {
		if (count($data) > 0) {
			$sql = self::escape($sql, $data);
		}
		self::$sql .= " WHERE $sql";
		return $this;
	}

	public function orderBy($column, $type = 'asc') {
		$type = strtoupper(($type != 'desc' && $type != 'desc') ? 'asc' : $type );
		self::$sql .= " ORDER BY `$column` $type";
		return $this;
	}

	public function limit($limit, $offset = 0) {
		self::$sql .= " LIMIT $offset, $limit";
		return $this;
	}

	public function sql() {
		$sql = self::$sql;
		self::$sql = null;
		return $sql;
	}

	public function execute() {
		if (!self::$conn) {
			self::$sql = null;
			Util::log('mysql', '[FAIL] No Connection');
			return;
		}

		$result = self::$conn->query(self::$sql);
		$data = $result;
		if ($result) {
			switch (self::$type) {
				case 'select':
					if ($result->num_rows > 1) {
						if (function_exists('mysqli_fetch_all')) {
							$data = $result->fetch_all(MYSQLI_BOTH);
						} else {
							$data = [];
							while ($row = $result->fetch_assoc()) {
								$data[] = $row;
							}
						}
					}
					else if ($result->field_count == 1) {
						$data = $result->fetch_row();
						$data = $data[0];
					}
					else {
						$data = $result->fetch_assoc();
					}
					break;
				case 'insert':
					$data = (self::$conn->insert_id ? self::$conn->insert_id : true);
					break;
				case 'count':
					$data = (int)$result->fetch_row()[0];
					break;
				default:
					break;
			}
			Util::log('mysql', '[QUERY] - '.self::$sql);
		} else {
			Util::log('mysql', '[FAIL] - '.self::$conn->error."\n\t\t\t\t\t\t\t".'- Query: '.self::$sql);
		}
		self::$sql = null;
		self::$type = null;
		return $data;
	}
}
