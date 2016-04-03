<?php

namespace System\Orm;

use System\Helpers\Profiler;

if (!defined('ERROR_LEVEL_MYSQL')) {
    define('ERROR_LEVEL_MYSQL', 3);
}

/**
 * ERDatabase class
 *
 * Manage database connections
 * Manage database queries
 *
 * @author sugatasei
 */
class ERDB {

    /**
     * Instances of ERDB
     *
     * @var array
     */
    protected static $instance = [];

    /**
     * Mysqli connection
     *
     * @var \mysqli
     */
    protected $db;

    /**
     * Mysqli statement
     *
     * @var \mysqli_stmt
     */
    protected $stmt;

    /**
     * Last query
     *
     * @var string
     */
    protected $lastQuery = '';

    /**
     * Last bind params
     *
     * @var \Orb\EasyRecord\ERBindParam
     */
    protected $lastBind = null;

    // -------------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param \mysqli $db
     */
    public function __construct($db) {
        $this->db = $db;
    }

    // -------------------------------------------------------------------------

    /**
     * Connect to a database
     *
     * @param string $key
     * @param string $host
     * @param string $user
     * @param string $passwd
     * @param string $dbname
     * @param int $port
     * @param string $socket
     * @throws \Orb\EasyRecord\ERException
     * @return ERDB
     */
    public static function connect($key = 'default', $host = 'localhost', $user = 'root', $passwd = '', $dbname = '', $port = 3306, $socket = '') {

        // Close previous instance
        if (($prev = self::getInstance($key)) !== false) {
            $prev->close();
        }

        // Activate exceptions
        \mysqli_report(\MYSQLI_REPORT_STRICT);

        // Open a new mysqli connection
        try {
            $db = \mysqli_init();
            //$db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2); NOTE: Problème requête 'longue'
            $db->connect($host, $user, $passwd, $dbname, $port, $socket);
        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        // Connection error
        if ($db->connect_errno) {
            throw new ERException($db->connect_error, ERROR_LEVEL_MYSQL);
        }

        // Charset
        $db->set_charset("utf8");

        // New object
        self::$instance[$key] = new static($db);

        return self::$instance[$key];
    }

    // -------------------------------------------------------------------------

    /**
     * Returns an instance
     *
     * @param string $key
     * @return \Orb\EasyRecord\ERDB
     */
    public static function getInstance($key = 'default') {
        if (isset(self::$instance[$key])) {
            return self::$instance[$key];
        }

        return false;
    }

    // -------------------------------------------------------------------------

    /**
     * Close a connexion
     *
     * @return \Orb\EasyRecord\ERDB
     * @throws \Orb\EasyRecord\ERException
     */
    public function close() {
        try {
            $this->db->close();
        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a new transaction
     *
     * @return \Orb\EasyRecord\ERDB
     * @throws \Orb\EasyRecord\ERException
     */
    public function transactionStart() {
        try {
            $this->db->autocommit(false);
        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Commit the transaction
     *
     * @return \Orb\EasyRecord\ERDB
     * @throws \Orb\EasyRecord\ERException
     */
    public function commit() {
        try {
            $this->db->commit();
            $this->db->autocommit(true);
        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Rollback the transaction
     *
     * @return \Orb\EasyRecord\ERDB
     * @throws \Orb\EasyRecord\ERException
     */
    public function rollback() {
        try {
            $this->db->rollback();
            $this->db->autocommit(true);
        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Execute a query and returns the results of the query
     * This method supports simple query or prepared query
     *
     * @param string $query
     * @param \Orb\EasyRecord\ERBindParam $param
     * @return \Orb\EasyRecord\ERResult
     * @throws \Orb\EasyRecord\ERException
     */
    public function query($query, ERBindParam $param = null) {
        $this->prepare($query);
        return $this->execute($param);
    }

    // -------------------------------------------------------------------------

    /**
     * Prepare a query
     *
     * @param string $query
     * @return \Orb\EasyRecord\ERDB
     * @throws ERException
     */
    public function prepare($query) {
        try {
            // Save the last query
            $this->lastQuery = $query;

            // Prepare the query
            $this->stmt = $this->db->prepare($query);

            // Error on prepare
            if ($this->stmt == false) {
                throw new ERException("MySQL error (#{$this->db->errno}) : {$this->db->error}\nRequest:\n{$this->lastQuery()}", ERROR_LEVEL_MYSQL);
            }
        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Execute a query
     *
     * @param \Orb\EasyRecord\ERBindParam $param
     * @return \Orb\EasyRecord\ERResult
     * @throws ERException
     */
    public function execute(ERBindParam $param = null, $stmtClose = true) {
        $result = false;

        $startTimeQuery = microtime(true) * 1000;

        try {
            // Save the last bind
            $this->lastBind = $param;

            // Bind values
            if ($param !== null && $param->hasValues()) {
                $bind = $this->stmt->bind_param(...$param->get());

                if ($bind == false) {
                    throw new ERException("MySQL error (#{$this->stmt->errno}) : {$this->stmt->error}\nRequest:\n{$this->lastQuery()}", ERROR_LEVEL_MYSQL);
                }
            }

            // Execute query
            $exec = $this->stmt->execute();

            // Error on execute
            if ($exec == false) {
                throw new ERException("MySQL error (#{$this->stmt->errno}) : {$this->stmt->error}\nRequest:\n{$this->lastQuery()}", ERROR_LEVEL_MYSQL);
            }

            // Get results
            $mysqliResult = $this->stmt->get_result();

            // Set of results
            if ($mysqliResult instanceof \mysqli_result) {
                $result = new ERResult($mysqliResult);
            }
            // Rows affected
            else {
                $result = $this->stmt->affected_rows;
            }

            // Close statement
            if ($stmtClose) {
                $this->stmt->close();
            }


            $timeQuery = (microtime(true) * 1000) - $startTimeQuery;

            Profiler::query([$this->lastQuery(), $timeQuery]);

        }
        catch (\mysqli_sql_exception $ex) {
            throw new ERException($ex->getMessage(), $ex->getCode());
        }

        return $result;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns last insert ID
     *
     * @return int|false
     */
    public function lastId() {
        return $this->db->insert_id ? $this->db->insert_id : false;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns last query
     *
     * @return string
     */
    public function lastQuery() {

        // Any binded values
        if ($this->lastBind === null || !$this->lastBind->hasValues()) {
            return $this->lastQuery;
        }

        // Get types and values
        $binds   = $this->lastBind->get();
        $types   = array_shift($binds);
        $replace = [];
        foreach ($binds as $key => $value) {
            $replace[] = [
                'type'  => $types[$key],
                'value' => $value
            ];
        }

        // Prepare callback for the replacement
        $callback = function ($match) use (&$replace) {
            $bind = array_shift($replace);

            if($bind['value'] === null) {
                return 'null';
            }

            switch ($bind['type']) {
                case 'i':
                    $value = (int) $bind['value'];
                    break;
                case 'd':
                    $value = (float) $bind['value'];
                    break;
                case 's':
                    $value = "'{$bind['value']}'";
                    break;
                case 'b':
                    $value = "'BLOB'";
                    break;
                default:
                    $value = '';
                    break;
            }
            return $value;
        };

        // Replace all : '?' by binded params
        $this->lastQuery = preg_replace_callback('/\?/', $callback, $this->lastQuery);
        $this->lastBind  = null;

        return $this->lastQuery;
    }

    // -------------------------------------------------------------------------

    /**
     * Escape a string
     *
     * @param string $str
     * @return string
     */
    public function escape($str) {
        return $this->db->real_escape_string($str);
    }

    // -------------------------------------------------------------------------
}

/* End of file */