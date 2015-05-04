<?php

class Database
{

	private $host;
	private $user;
	private $pass;
	private $name;
	private $link;
	private $error;
	private $errno;
	private $query;
	public $counter = 0;

	function __construct($host, $user, $pass, $name, $conn = true)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->name = $name;
		if ($conn)
			$this->connect();
	}

	function __destruct()
	{
		@mysqli_close($this->link);
	}

	public function connect()
	{
		if ($this->link = mysqli_connect($this->host, $this->user, $this->pass)) {
			if (!mysqli_select_db($this->link, $this->name))
				$this->exception("no_db_connection");
		} else {
			$this->error = mysqli_connect_error();
			$this->errno = mysqli_connect_errno();
			$this->exception("no_server_connection");
		}
	}

	public function close()
	{
		@mysqli_close($this->link);
	}

	public function prepare($query, $values)
	{
		// Escapeowanie wszystkich argumentów
		$i = 0;
		foreach ($values as $value) {
			$values[$i++] = $this->escape($value);
		}

		return vsprintf($query, $values);
	}

	public function query($query)
	{
		$this->counter += 1;
		//file_put_contents(SQL_LOG, file_get_contents(SQL_LOG)."\n".$query);
		if ($this->query = @mysqli_query($this->link, $query)) {
			return $this->query;
		} else {
			$this->exception("query_error", $query);
			return false;
		}
	}

	public function multi_query($query)
	{
		//file_put_contents(SQL_LOG, file_get_contents(SQL_LOG)."\n\n".$query);
		if ($this->query = @mysqli_multi_query($this->link, $query)) {
			return $this->query;
		} else {
			$this->exception("query_error", $query);
			return false;
		}
	}

	public function get_column($query, $column)
	{
		$result = $this->query($query);

		if (!$this->num_rows($result))
			return NULL;

		$row = $this->fetch_array_assoc($result);
		if (!isset($row[$column]))
			return NULL;

		return $row[$column];
	}

	public function num_rows($result)
	{
		if (empty($result)) {
			$this->exception("no_query_num_rows");
			return false;
		} else {
			return mysqli_num_rows($result);
		}
	}

	public function fetch_array_assoc($result)
	{
		if (empty($result)) {
			$this->exception("no_query_fetch_array_assoc");
			return false;
		} else {
			$data = mysqli_fetch_assoc($result);
		}
		return $data;
	}

	public function fetch_array($result)
	{
		if (empty($result)) {
			$this->exception("no_query_fetch_array");
			return false;
		} else {
			$data = mysqli_fetch_array($result);
		}
		return $data;
	}

	public function last_id()
	{
		return mysqli_insert_id($this->link);
	}

	public function affected_rows()
	{
		return mysqli_affected_rows($this->link);
	}

	public function escape($str)
	{
		return mysqli_real_escape_string($this->link, $str);
	}

	private function exception($message_id, $query = "")
	{
		if ($this->link) {
			$this->error = mysqli_error($this->link);
			$this->errno = mysqli_errno($this->link);
		}

		$array['message_id'] = $message_id;
		$array['query'] = $query;
		$array['error'] = $this->error;

		if (PHP_SAPI !== 'cli') {
			$array['request_uri'] = @$_SERVER['REQUEST_URI'];

			if (@$_SERVER['HTTP_REFERER'])
				$array['http_referer'] = @$_SERVER['HTTP_REFERER'];
		}
		$string = json_encode($array);
		trigger_error($string, E_USER_ERROR);
	}
}