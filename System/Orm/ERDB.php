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
    private static $instance = [];

    /**
     * Mysqli connection
     *
     * @var \mysqli
     */
    private $db;

    /**
     * Last query
     *
     * @var string
     */
    private $lastQuery = '';

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
     * @throws ERException
     * @return ERDB
     */
    public static function connect($key = 'default', $host = 'localhost', $user = 'root', $passwd = '', $dbname = '', $port = 3306, $socket = '') {

        // Close previous instance
        if (($prev = self::getInstance($key)) !== FALSE) {
            $prev->close();
        }

        // Open a new mysqli connection
        $db = new \mysqli($host, $user, $passwd, $dbname, $port, $socket);

        // Connection error
        if ($db->connect_errno) {
            throw new ERException('Connexion impossible', ERROR_LEVEL_MYSQL);
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
     * @return ERDB
     */
    public static function getInstance($key = 'default') {
        if (isset(self::$instance[$key])) {
            return self::$instance[$key];
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Close a connection
     */
    public function close() {
        $this->db->close();
    }

    // -------------------------------------------------------------------------

    /**
     * Start a new transaction
     */
    public function transactionStart() {
        $this->db->autocommit(FALSE);
    }

    // -------------------------------------------------------------------------

    /**
     * Commit the transaction
     */
    public function commit() {
        $this->db->commit();
        $this->db->autocommit(TRUE);
    }

    // -------------------------------------------------------------------------

    /**
     * Rollback the transaction
     */
    public function rollback() {
        $this->db->rollback();
        $this->db->autocommit(TRUE);
    }

    // -------------------------------------------------------------------------

    /**
     * Execute a query and returns the results of the query
     * This method supports simple query or prepared query
     *
     * @param type $query
     * @param \Orb\EasyRecord\ERBindParam $param
     * @return \Orb\EasyRecord\ERResult
     * @throws \mysqli_sql_exception
     */
    public function query($query, ERBindParam $param = NULL) {

        $startTimeQuery = microtime(TRUE) * 1000;

        $result = FALSE;
        $errno  = FALSE;

        $this->lastQuery = [$query, $param];

        $stmt = $this->db->prepare($query);

        if($this->db->errno) {
            throw new \mysqli_sql_exception("MySQL error (#{$this->db->errno}) : {$this->db->error})\n Request: {$this->lastQuery()}", ERROR_LEVEL_MYSQL);
        }

        if ($stmt && $param !== NULL && $param->hasValues()) {
            call_user_func_array(array($stmt, 'bind_param'), $param->get());
        }

        if ($stmt->execute()) {
            $errno = $stmt->errno;
        }

        // SQL Error
        if ($errno > 0) {
            $stmt->close();
            throw new \mysqli_sql_exception("MySQL error (#{$errno})\n Request: {$this->lastQuery()}", ERROR_LEVEL_MYSQL);
        }

        $mysqliResult = $stmt->get_result();

        // Set of results
        if ($mysqliResult instanceof \mysqli_result) {
            $result = new ERResult($mysqliResult);
        }
        else {
            $result = TRUE;
        }

        $stmt->close();

        $timeQuery = (microtime(TRUE) * 1000) - $startTimeQuery;

        Profiler::query([$this->lastQuery(), $timeQuery]);

        return $result;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns last insert ID
     *
     * @return int|FALSE
     */
    public function lastId() {
        return $this->db->insert_id ? $this->db->insert_id : FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns last query
     *
     * @return string
     */
    public function lastQuery() {

        // The last query is already transformed into a string
        if (!is_array($this->lastQuery)) {
            return $this->lastQuery;
        }

        // Get prepared query and ERBindParam object
        list($query, $bind) = $this->lastQuery;

        // Any value to be bind
        if ($bind === NULL || !$bind->hasValues()) {
            $this->lastQuery = $query;

            return $this->lastQuery;
        }

        // Get types and values
        $binds   = $bind->get();
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
        $this->lastQuery = preg_replace_callback('/\?/', $callback, $query);

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