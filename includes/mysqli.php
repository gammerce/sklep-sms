<?php

class SqlQueryException extends Exception
{
	/** @var  string */
	private $query;

	/** @var  string */
	private $message_id;

	/** @var  string */
	private $error;

	/** @var  int */
	private $errorno;

	/**
	 * @param bool $escape
	 * @return string
	 */
	public function getQuery($escape = true)
	{
		return $escape ? htmlspecialchars($this->query) : $this->query;
	}

	/**
	 * @return string
	 */
	public function getMessageId()
	{
		return $this->message_id;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param string $query
	 */
	public function setQuery($query)
	{
		$this->query = $query;
	}

	/**
	 * @param string $error
	 */
	public function setError($error)
	{
		$this->error = $error;
	}

	/**
	 * @return int
	 */
	public function getErrorno()
	{
		return $this->errorno;
	}

	/**
	 * @param int $errorno
	 */
	public function setErrorno($errorno)
	{
		$this->errorno = $errorno;
	}
}

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
	private $result;
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
		if ($this->link = @mysqli_connect($this->host, $this->user, $this->pass)) {
			if (!@mysqli_select_db($this->link, $this->name))
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
		$this->query = $query;
		//file_put_contents(SQL_LOG, file_get_contents(SQL_LOG)."\n".$query);
		if ($this->result = @mysqli_query($this->link, $query)) {
			return $this->result;
		} else {
			$this->exception("query_error");
			return false;
		}
	}

	public function multi_query($query)
	{
		$this->query = $query;
		//file_put_contents(SQL_LOG, file_get_contents(SQL_LOG)."\n\n".$query);
		if ($this->result = @mysqli_multi_query($this->link, $query)) {
			return $this->result;
		} else {
			$this->exception("query_error");
			return false;
		}
	}

	public function get_column($query, $column)
	{
		$this->query = $query;
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

	public function get_last_query()
	{
		return $this->query;
	}

	private function exception($message_id)
	{
		$exception = new SqlQueryException($message_id);

		if ($this->link) {
			$this->error = mysqli_error($this->link);
			$this->errno = mysqli_errno($this->link);
		}

		$exception->setError($this->error);
		$exception->setErrorno($this->errno);
		$exception->setQuery($this->query);

		throw $exception;
	}

	public static function showError(SqlQueryException $e)
	{
		global $settings, $lang, $templates;

		if (strlen($e->getQuery())) {
			$text = date($settings['date_format']) . ": " . $e->getQuery(false);
			if (!file_exists(SQL_LOG) || !strlen(file_get_contents(SQL_LOG)))
				file_put_contents(SQL_LOG, $text);
			else
				file_put_contents(SQL_LOG, file_get_contents(SQL_LOG) . "\n\n" . $text);
		}

		$message = $lang->mysqli[$e->getMessage()];
		$query = $e->getQuery();

		if (SCRIPT_NAME == 'jsonhttp' || SCRIPT_NAME == 'jsonhttp_admin') {
			output_page($message);
		}

		$header = eval($templates->render("header_error"));
		output_page(eval($templates->render("error_handler")));
	}
}