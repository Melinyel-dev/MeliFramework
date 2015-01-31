<?php

namespace System\Orm;

/**
 * ERResult Class
 *
 * Stores SQL results
 * Manages SQL results
 * 
 * @author mathieu
 */
class ERResult implements \Countable, \IteratorAggregate {

    /**
     * MySQLi Result
     * 
     * @var \mysqli_result 
     */
    private $result;

    /**
     * Current row
     * 
     * @var array
     */
    private $row = [];

    // -------------------------------------------------------------------------

    /**
     * Class constructor
     * @param \mysqli_result $result
     */
    public function __construct(\mysqli_result $result) {
        $this->result = $result;
    }

    // -------------------------------------------------------------------------

    /**
     * Fetch a result row as an associative array
     * Returns null if there are no more rows
     * 
     * @return array|null
     */
    public function next() {
        $this->row = $this->result->fetch_assoc();
        return $this->row();
    }

    // -------------------------------------------------------------------------

    /**
     * Returns current row as associative array
     * Returns null if there are no more rows
     * 
     * @return type
     */
    public function row() {
        return $this->row;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns a column value from the current row
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = NULL) {
        return isset($this->row[$key]) ? $this->row[$key] : $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns all rows
     * 
     * @return array
     */
    public function all() {
        return $this->result->fetch_all(MYSQLI_ASSOC);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the number of rows
     * 
     * @return int
     */
    public function count() {
        return $this->result->num_rows;
    }

    // -------------------------------------------------------------------------

    /**
     * Create a new Iterator
     * 
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->all());
    }

    // -------------------------------------------------------------------------
}

/* End of file */