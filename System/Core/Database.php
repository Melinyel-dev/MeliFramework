<?php

namespace Melidev\System\Core;

use Melidev\System\Helpers\Profiler;

class Database {
	private $server;
	private $database;
	private $login;
	private $password;
	private $port;
	private $socket;

	public $connexion;
	public $currentQuery;
	public $queryResult;
	public $lastQuery;


	public function __construct($server = null,$database = null,$login = null,$password = null,$port = null,$socket = null) {
		if(!$server){
			$server = $GLOBALS['databaseCfg']['host'];
			$database = $GLOBALS['databaseCfg']['database'];
			$login = $GLOBALS['databaseCfg']['login'];
			$password = $GLOBALS['databaseCfg']['password'];
			$port = $GLOBALS['databaseCfg']['port'];
			$socket = $GLOBALS['databaseCfg']['socket'];
		}
		mysqli_report(MYSQLI_REPORT_STRICT);

		Profiler::sys_mark('mysql_connection');
		$this->connexion = new \mysqli($server,$login,$password,$database,$port,$socket);
		Profiler::sys_mark('mysql_connection');

		if(!mysqli_connect_errno()) {
			$this->server = $server;
			$this->database = $database;
			$this->login = $login;
			$this->password = $password;
			$this->port = $port;
			$this->socket = $socket;

			$this->connexion->set_charset("utf8");
			return true;
		}
	}

        public function query() {
                $args = func_get_args();
                if(count($args) == 0) {
                        $this->currentQuery = new Query();
                        return $this->currentQuery;
                } elseif(count($args) == 1 || count($args) == 2) {
                        $this->currentQuery = $this->lastQuery = $args[0];
                        return $this->exec(array_key_exists(1, $args) ? $args[1] : null);
                }
                throw new mysqli_sql_exception("Database::query() expect 0 or 2 arguments", 1);
                return false;
        }

        public function exec($bindParam) {
                if(is_object($this->currentQuery) && get_class($this->currentQuery) == 'Query') {
                        $startTimeQuery = microtime(true) * 1000;
                        $this->lastQuery = $this->currentQuery->get();
                        $this->queryResult = $result = $this->connexion->query($this->lastQuery);
                        $timeQuery = (microtime(true) * 1000) - $startTimeQuery;
                        Profiler::query([$this->lastQuery, $time_query]);
                } else {
                        $startTimeQuery = microtime(true) * 1000;
                        if($bindParam){
                                $result = $stmt = $this->connexion->prepare($this->currentQuery);
                                if($stmt){
                                        if($bindParam->hasValues())
                                        call_user_func_array(array($stmt, 'bind_param'), $bindParam->get());
                                $result = $stmt->execute();
                                        $this->queryResult = $stmt->get_result();
                                }
                        }else{
                                $this->queryResult = $result = $this->connexion->query($this->currentQuery);
                        }
                        $timeQuery = (microtime(true) * 1000) - $startTimeQuery;
                        Profiler::query([$this->currentQuery, $timeQuery]);
                }
                if($result !== false) {
                        return new SQLResult($this->queryResult);
                } else {
                throw new mysqli_sql_exception("MySQL error in <br>".$_SERVER["PHP_SELF"]."<br>on ".$GLOBALS['conf']['display_name']."<br>".$this->connexion->error."<br><br>request : ".lastRequest(), $this->connexion->errno);
                        return false;
                }
        }

	public function close() {
		$this->connexion->close();
	}

	public function insertId(){
	    return $this->connexion->insert_id ? $this->connexion->insert_id : false;
	}
}


class Query {
	private $operation;
	private $distinct;
	private $fields;
	private $tables;
	private $sets = [];
	private $conditions;
	private $orders;
	private $groups;
	private $duplicateKey;
	private $limit;

	public function __construct() {
		$this->operation = '';
		$this->fields = array();
		$this->tables = array();
		$this->conditions = array();
		$this->orders = array();
		$this->groups = array();
		$this->duplicateKey = array();

		$this->distinct = false;

		$this->limit = '';
		return $this;
	}

	public function select() {
		$this->operation = 'SELECT';
		return $this;
	}

	public function update() {
		$this->operation = 'UPDATE';
		return $this;
	}

	public function delete() {
		$this->operation = 'DELETE';
		return $this;
	}

	public function insert() {
		$this->operation = 'INSERT';
		return $this;
	}

	public function distinct() {
		$this->distinct = true;
	}

	public function field($field,$table=null,$alias=null) {
		array_push($this->fields,new Field($field,$table,$alias));
		return $this;
	}

	public function from($table,$alias=null,$database=null) {
		array_push($this->tables,new Table($table,$database,$alias));
		return $this;
	}

	public function order($field,$dir='ASC') {
		array_push($this->orders,$field.' '.$dir);
		return $this;
	}

	public function group($field) {
		array_push($this->groups,$field);
		return $this;
	}

	public function limit($offset,$range) {
		$this->limit = ' LIMIT '.$offset.','.$range;
		return $this;
	}

	public function where($field,$operation,$expression) {
		array_push($this->conditions,new Condition($field,$operation,$expression,null));
		return $this;
	}

	public function andWhere($field,$operation,$expression) {
		array_push($this->conditions,new Condition($field,$operation,$expression,'AND'));
		return $this;
	}

	public function orWhere($field,$operation,$expression) {
		array_push($this->conditions,new Condition($field,$operation,$expression,'OR'));
		return $this;
	}

	public function set($field,$expression) {
		array_push($this->sets,$field.'='.mysql_real_escape_string($expression));
		return $this;
	}

	public function set_expr($field,$expression) {
		array_push($this->sets,$field.'='.$expression);
		return $this;
	}

	public function value($field,$expression) {
		$this->sets[$field] = mysql_real_escape_string($expression);
		return $this;
	}

	public function onDuplicateKey($field,$expression) {
		array_push($this->duplicateKey,$field.'='.mysql_real_escape_string($expression));
		return $this;
	}

	public function get() {
		if($this->operation != '' && !empty($this->tables)) {
			$gen = $this->operation.' ';
			if($this->distinct == true) $gen .= 'DISTINCT ';

			if(!empty($this->fields)) {
				$i=0;
				foreach($this->fields as $field) {
					if($i!=0) $gen .= ',';
					$gen .= $field->get();
					$i++;
				}
			} else {
				if($this->operation == 'SELECT') $gen .= '*';
			}

			if($this->operation != 'UPDATE' && $this->operation != 'INSERT') $gen .= ' FROM ';
			if($this->operation == 'INSERT') $gen .= 'INTO ';

			$i=0;
			foreach($this->tables as $table) {
				if($i!=0) $gen .= ',';
				$gen .= $table->get();
				$i++;
			}

			if($this->operation == 'UPDATE') {
				if(!empty($this->sets)) {
					$i=0;
					$gen .= ' SET ';
					foreach($this->sets as $set) {
						if($i!=0) $gen .= ',';
						$gen .= $set;
						$i++;
					}
				}
			}

			if($this->operation == 'INSERT') {
				if(!empty($this->sets)) {
					$i=0;
					$gen .= '(';
					foreach($this->sets as $field=>$value) {
						if($i!=0) $gen .= ',';
						$gen .= $field;
						$i++;
					}

					$gen .= ') VALUES (';
					$i=0;

					foreach($this->sets as $field=>$value) {
						if($i!=0) $gen .= ',';
						$gen .= "'".$value."'";
						$i++;
					}
					$gen .= ')';
				}
			}

			if(!empty($this->conditions)) {
				$i=0;
				$gen .= ' WHERE ';
				foreach($this->conditions as $condition) {
					if($i!=0) $gen .= ' ';
					$gen .= $condition->get();
					$i++;
				}
			}

			if($this->operation == 'INSERT') {
				$i=0;
				if(!empty($this->duplicateKey)) {
					$gen .= ' ON DUPLICATE KEY UPDATE ';
					foreach($this->duplicateKey as $set) {
						if($i!=0) $gen .= ',';
						$gen .= $set;
						$i++;
					}
				}
			}

			if(!empty($this->groups)) {
				$gen .= ' GROUP BY ';
				$i=0;
				foreach($this->groups as $group) {
					if($i!=0) $gen .= ',';
					$gen .= $group;
					$i++;
				}
			}

			if(!empty($this->orders)) {
				$gen .= ' ORDER BY ';
				$i=0;
				foreach($this->orders as $order) {
					if($i!=0) $gen .= ',';
					$gen .= $order;
					$i++;
				}
			}

			$gen .= $this->limit;

			return $gen;
		}
	}

	public function exec() {
		$ctrl = Controller::getInstance();
		return $ctrl->database->exec();
	}
}

class Field {
	private $name;
	private $table;
	private $alias;

	public function __construct($field,$table,$alias) {
		$this->name = $field;
		$this->table = $table;
		$this->alias = $alias;
	}

	public function get() {
		$gen = '';
		if($this->table !== null) $gen .= $this->table.'.';
		$gen = $this->name;
		if($this->alias !== null) {
			$gen .= ' AS '.$alias;
		}
		return $gen;
	}
}

class Table {
	private $name;
	private $database;
	private $alias;

	public function Table($table,$database,$alias) {
		$this->name = $table;
		$this->database = $database;
		$this->alias = $alias;
	}

	public function get() {
		$gen = '';
		if($this->database !== null) $gen .= $this->database.'.';
		$gen .= $this->name;
		if($this->alias !== null) {
			$gen .= ' '.$this->alias;
		}
		return $gen;
	}
}

class Condition {
	private $field;
	private $operation;
	private $expression;
	private $link;

	public function __construct($field,$operation,$expression,$link) {
		$this->field = $field;
		$this->operation = $operation;
		$this->expression = $expression;
		$this->link = $link;
	}

	public function get() {
		$gen = '';
		if($this->link !== null) {
			$gen .= $this->link.' ';
		}

		$gen .= $this->field.' '.$this->operation.' ';

		if(is_object($this->expression) && get_class($this->expression) == 'Query') {
			$gen .= '('.$this->expression->get().')';
		} else {
			$ctrl = Controller::getInstance();
			if(substr($this->expression,0,1) == "'") {
				$gen .= "'".$ctrl->database->connexion->real_escape_string(substr($this->expression,1,strlen($this->expression)-2))."'";
			} else {
				$gen .= $ctrl->database->connexion->real_escape_string($this->expression);
			}
		}

		return $gen;
	}
}

class SQLResult {
	private $sqlRes;
	private $currentReg;
	private $nbReg;

	public function __construct($sqlRes) {
		$this->sqlRes = $sqlRes;
	}

	public function next() {
		$startTimeFetch = microtime(true) * 1000;
		$this->currentReg = $this->sqlRes->fetch_assoc();
		//Profiler::queryFetch([$this->numQuery, (microtime(true) * 1000) - $startTimeFetch  ]);

		if($this->currentReg) {
			return true;
		} else {
			return false;
		}
	}

	public function getNbRes() {
		$this->nbRes = $this->sqlRes->num_rows;
		return $this->nbRes;
	}

	public function get($key) {
		return $this->currentReg[$key];
	}

	public function row() {
		return $this->currentReg;
	}

	public function all() {
		return $this->sqlRes->fetch_all(MYSQLI_ASSOC);
	}
}

/* End of file */