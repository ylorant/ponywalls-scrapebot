<?php

class Database {

	protected $_PDO;
	protected $_queries = array();
	
	public function connect($engine, $host, $port, $user, $password, $db)
	{
		$this->_PDO = new PDO($engine.':host='.$host.';port='.$port.';dbname='.$db, $user, $password);
	}

	public function prepare($query)
	{
		return new DBQuery($this->_PDO->prepare($query));
	}
	
	public function lastInsertID()
	{
		return $this->_PDO->lastInsertId();
	}
}

class DBQuery
{
	private $_query;
	protected $_curParamID = 1;
	
	public function __construct(PDOStatement $query)
	{
		$this->_query = $query;
	}
	
	public function bind($name, $value = NULL)
	{
		if($value === NULL)
			list($value, $name) = array($name, ++$this->_curParamID);
		
		$type = PDO::PARAM_STR;
		if(is_int($value))
			$type = PDO::PARAM_INT;
		
		$this->_query->bindValue($name, $value, $type);
	}
	
	public function execute($values = array())
	{
		if(!is_array($values))
			$values = func_get_args();
		
		return $this->_query->execute($values);
	}
	
	public function fetch()
	{
		return $this->_query->fetch(PDO::FETCH_ASSOC);
	}
	
	public function fetchAll()
	{
		return $this->_query->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function reset()
	{
		$this->_query->resetCursor();
	}
}

class DB
{
	private static $instance;
	
	public static function getInstance()
	{
		if(!self::$instance)
			self::$instance = new Database();
		
		return self::$instance;
	}
	
	public static function __callStatic($func, $args)
	{
		$self = self::getInstance();
		return call_user_func_array(array($self, $func), $args);
	}
}
?>
