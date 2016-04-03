<?php

namespace System\Orm;

/**
 * ERResult Class
 *
 * Stores SQL results
 * Manages SQL results
 *
 * @author sugatasei
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
     * Get fields name
     *
     * @return array
     */
    public function fields() {
        return $this->result->fetch_fields();
    }

    // -------------------------------------------------------------------------

    /**
     * Fetch a result row
     * Returns null if there are no more rows
     *
     * @return array|null
     */
    public function next() {
        return $this->nextAssoc();
    }

    // -------------------------------------------------------------------------

    /**
     * Fetch a result row as an associative array
     * Returns null if there are no more rows
     *
     * @return array|null
     */
    public function nextAssoc() {
        $this->row = $this->result->fetch_assoc();
        return $this->row();
    }

    // -------------------------------------------------------------------------

    /**
     * Fetch a result row as an object
     * Returns null if there are no more rows
     *
     * @return array|null
     */
    public function nextObject() {
        $this->row = $this->result->fetch_object();
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
    public function get($key, $default = null) {
        return isset($this->row[$key]) ? $this->row[$key] : $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns all rows
     *
     * @return array
     */
    public function all() {
         return $this->allAssoc();
    }

    // -------------------------------------------------------------------------

    /**
     * Returns all rows as array of associative arrays
     *
     * @return array
     */
    public function allAssoc() {
         return $this->result->fetch_all(MYSQLI_ASSOC);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns all rows as array of objects
     *
     * @return array
     */
    public function allObject() {
        $data = [];

        while (($row  = $this->result->fetch_object())) {
            $data[] = $row;
        }

        return $data;
    }

    // -------------------------------------------------------------------------

    /**
     * Return an unique
     *
     * @return array
     * @throws \Orb\EasyRecord\ERException
     */
    public function one($exception = true) {
        return $this->oneAssoc($exception);
    }

    // -------------------------------------------------------------------------

    /**
     * Return an unique row as an associative array
     *
     * @return array
     * @throws \Orb\EasyRecord\ERException
     */
    public function oneAssoc($exception = true) {
        if ($this->count() !== 1) {

            if ($exception) {
                throw new ERException("The number of results is different to 1 ({$this->count()})");
            }

            return false;
        }

        return $this->next();
    }

    // -------------------------------------------------------------------------

    /**
     * Return an unique row as an object
     *
     * @return array
     * @throws \Orb\EasyRecord\ERException
     */
    public function oneObject($exception = true) {
        if ($this->count() !== 1) {

            if ($exception) {
                throw new ERException("The number of results is different to 1 ({$this->count()})");
            }

            return false;
        }

        return $this->nextObject();
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