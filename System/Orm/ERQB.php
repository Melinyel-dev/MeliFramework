<?php

namespace System\Orm;

/**
 * ERQB
 * Query builder class for ERDB
 *
 * @method  \Orb\EasyRecord\ERQB getInstance(string $key)
 * @author sugatasei
 */
class ERQB extends ERDB {

    private $columns          = [];
    private $increments       = [];
    private $unparsed         = [];
    private $distinct         = false;
    private $from             = '';
    private $join             = [];
    private $where            = [];
    private $whereGroupCount  = 0;
    private $whereGroupLevel  = 0;
    private $groupby          = [];
    private $having           = [];
    private $havingGroupCount = 0;
    private $havingGroupLevel = 0;
    private $limit            = 0;
    private $offset           = 0;
    private $orderby          = [];
    private $data             = [];
    private $save             = [];
    private $dtConfig         = [];

    // -------------------------------------------------------------------------

    /**
     * Reset all
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function reset() {
        $this->_resetQuery();
        $this->_resetData();

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Select columns to read
     *
     * @param array $columns columns name comma separated
     * @return \Orb\EasyRecord\ERQB
     */
    public function select(...$columns) {
        if (!$columns) {
            $this->columns[] = '*';
        }

        foreach ($columns as $c) {
            $this->columns[] = $c;
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Select distinct
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function distinct() {
        $this->distinct = true;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Join two tables
     *
     * @param string $table Table name
     * @param string $cond join condition
     * @param string $type LEFT or RIGHT
     * @return \Orb\EasyRecord\ERQB
     */
    public function join($table, $cond, $type = '') {
        if ($type) {
            $type = strtoupper(trim($type));

            if ($type == 'LEFT' || $type == 'RIGHT') {
                $type .= ' ';
            }
            else {
                $type = '';
            }
        }

        $_table = trim($table);
        $_cond  = $this->_cleanString($cond);

        $this->join[] = $type . "JOIN\n\t" . $_table . " ON " . $_cond;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Left join 
     * 
     * @param string $table Table name
     * @param string $cond join condition
     * @return \Orb\EasyRecord\ERQB
     */
    public function left($table, $cond) {
        return $this->join($table, $cond, 'LEFT');
    }

    // -------------------------------------------------------------------------

    /**
     * Right join
     * 
     * @param string $table Table name
     * @param string $cond join condition
     * @return \Orb\EasyRecord\ERQB
     */
    public function right($table, $cond) {
        return $this->join($table, $cond, 'RIGHT');
    }

    // -------------------------------------------------------------------------

    /**
     * Where clause
     *
     * @param string $field field name
     * @param string $operator operator name
     * @param mixed $value value
     * @return \Orb\EasyRecord\ERQB
     */
    public function where($field, $operator, $value) {
        $this->_condition('AND', $field, $operator, $value, 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Or where clause
     *
     * @param string $field field name
     * @param string $operator operator name
     * @param mixed $value value
     * @return \Orb\EasyRecord\ERQB
     */
    public function orWhere($field, $operator, $value) {
        $this->_condition('OR', $field, $operator, $value, 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Where in clause
     *
     * @param string $field field name
     * @param array $values array of values
     * @return \Orb\EasyRecord\ERQB
     */
    public function whereIn($field, array $values) {
        $this->_condition('AND', $field, 'IN', $values, 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Where not in clause
     *
     * @param string $field field name
     * @param array $values array of values
     * @return \Orb\EasyRecord\ERQB
     */
    public function whereNotIn($field, array $values) {
        $this->_condition('AND', $field, 'NOT IN', $values, 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Or where in clause
     *
     * @param string $field field name
     * @param array $values array of values
     * @return \Orb\EasyRecord\ERQB
     */
    public function orWhereIn($field, array $values) {
        $this->_condition('OR', $field, 'IN', $values, 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Or where not in clause
     *
     * @param string $field field name
     * @param array $values array of values
     * @return \Orb\EasyRecord\ERQB
     */
    public function orWhereNotIn($field, array $values) {
        $this->_condition('OR', $field, 'NOT IN', $values, 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Like clause
     *
     * @param string $field field name
     * @param string $value value
     * @param string $side add wildcards to left, right, bot
     * @return \Orb\EasyRecord\ERQB
     */
    public function like($field, $value, $side = null) {
        $this->_condition('AND', $field, 'LIKE', $this->_getLike($value, $side), 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Or like clause
     *
     * @param string $field field name
     * @param string $value value
     * @param string $side add wildcards to left, right, both
     * @return \Orb\EasyRecord\ERQB
     */
    public function orLike($field, $value, $side = null) {
        $this->_condition('OR', $field, 'LIKE', $this->_getLike($value, $side), 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Not like clause
     *
     * @param string $field field name
     * @param string $value value
     * @param string $side add wildcards to left, right, both
     * @return \Orb\EasyRecord\ERQB
     */
    public function notLike($field, $value, $side = null) {
        $this->_condition('AND', $field, 'NOT LIKE', $this->_getLike($value, $side), 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Or not like clause
     *
     * @param string $field field name
     * @param string $value value
     * @param string $side add wildcards to left, right, bot
     * @return \Orb\EasyRecord\ERQB
     */
    public function orNotLike($field, $value, $side = null) {
        $this->_condition('OR', $field, 'NOT LIKE', $this->_getLike($value, $side), 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Searching a set of fields
     *
     * @param string $fields Fields comma separate
     * @param string $values Words
     * @return \Orb\EasyRecord\ERQB
     */
    public function search($values, ...$fields) {

        // Nothing to do
        if (!$fields || !$values) {
            return $this;
        }

        $values = preg_split("/,\s*]+/i", $values, null, PREG_SPLIT_NO_EMPTY);

        foreach ($values as $v) {
            $v = $this->_getLike(trim($v), 'both');
            $this->groupStart();
            foreach ($fields as $f) {
                $this->_condition('OR', trim($f), 'LIKE', $v, 'where');
            }
            $this->groupEnd();
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a group of where clause
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function groupStart() {
        $this->_groupStart('AND', 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a or group of where clause
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function orGroupStart() {
        $this->_groupStart('OR', 'where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Close a group of where clause
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function groupEnd() {
        $this->_groupEnd('where');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Order by clause
     *
     * @param array $columns column name and direction. Comma separated
     * @return \Orb\EasyRecord\ERQB
     */
    public function orderBy(...$columns) {

        foreach ($columns as $c) {
            $this->orderby[] = $c;
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Group by clause
     *
     * @param string $column columns comma separated
     * @return \Orb\EasyRecord\ERQB
     */
    public function groupBy(...$columns) {

        foreach ($columns as $c) {
            $this->groupby[] = $c;
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Having clause
     *
     * @param string $field field name
     * @param string $operator operator name
     * @param mixed $value value
     * @return \Orb\EasyRecord\ERQB
     */
    public function having($field, $operator, $value) {
        $this->_condition('AND', $field, $operator, $value, 'having');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Or having clause
     *
     * @param string $field field name
     * @param string $operator operator name
     * @param mixed $value value
     * @return \Orb\EasyRecord\ERQB
     */
    public function orHaving($field, $operator, $value) {
        $this->_condition('OR', $field, $operator, $value, 'having');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a group of having clause
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function havingGroupStart() {
        $this->_groupStart('AND', 'having');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a group of pr having clause
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function orHavingGroupStart() {
        $this->_groupStart('OR', 'having');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Close a group of having clause
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function havingEnd() {
        $this->_groupEnd('having');
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Limit and offset the query results
     *
     * @param int $limit
     * @param int $offset
     * @return \Orb\EasyRecord\ERQB
     */
    public function limit($limit, $offset = 0) {
        $this->limit  = (int) $limit;
        $this->offset = (int) $offset;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Increment a field
     *
     * @param string $field field name
     * @param int $value
     * @return \Orb\EasyRecord\ERQB
     */
    public function increment($field, $value = 1) {
        $num  = count($this->data);
        $_key = '§¤§' . $num . '§¤§';
        $_f   = trim($field);

        $this->increments[$_key] = $_f;
        $this->data[$_key]       = $value;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Decrement a field
     *
     * @param string $field field name
     * @param int $value
     * @return \Orb\EasyRecord\ERQB
     */
    public function decrement($field, $value = 1) {

        $this->increment($field, $value * -1);

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Set without protection
     * 
     * @param string $field
     * @param mixed $value
     * @return \Orb\EasyRecord\ERQB
     */
    public function set($field, $value) {
        $this->unparsed[$field] = $value;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Run a read query
     *
     * @param string $table Table name
     * @return \Orb\EasyRecord\ERResult
     */
    public function read($table) {
        $this->_from($table);
        $sql = $this->_buildRead();

        $this->_resetQuery();
        return $this->_exec($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Count rows in a table
     *
     * @param string $table Table name
     * @return int
     */
    public function count($table) {
        $this->_from($table);
        $this->columns = [];
        $this->select('COUNT(*) AS sum');

        $sql = $this->_buildRead();

        if ($this->groupby) {
            $sql = "SELECT COUNT(*) as sum FROM ($sql) as ERQBCOUNT";
        }

        $this->_resetQuery();
        $res = $this->_exec($sql)->one();

        return $res['sum'];
    }

    // -------------------------------------------------------------------------

    /**
     * Run an insert query
     *
     * @param string $table Table name
     * @param array $data set of data in an associative array
     * @return int affected rows
     */
    public function insert($table, array $data = []) {
        $sql = $this->_buildInsert($table, $data, false);
        $this->_resetQuery();
        return $this->_exec($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Run an insert query
     *
     * @param string $table Table name
     * @param array $data set of data in an associative array
     * @return int affected rows
     */
    public function replace($table, array $data = []) {
        $sql = $this->_buildInsert($table, $data, true);
        $this->_resetQuery();
        return $this->_exec($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Run an update query
     *
     * @param string $table Table name
     * @param array $data set of data in an associative array
     * @return int affected rows
     */
    public function update($table, array $data = []) {
        $sql = $this->_buildUpdate($table, $data);
        $sql .= $this->_buildJoin();
        $sql .= $this->_buildWhere();
        $this->_resetQuery();

        return $this->_exec($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Run a delete query
     *
     * @param string $table Table name
     * @return int affected rows
     */
    public function delete($table) {
        $sql = $this->_buildDelete($table);
        $sql .= $this->_buildJoin();
        $sql .= $this->_buildWhere();
        $this->_resetQuery();
        return $this->_exec($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Truncate a table
     *
     * @param string $table Table name
     * @return type
     */
    public function truncate($table) {
        $this->_from($table);
        $sql = "TRUNCATE TABLE " . $this->from;
        $this->_resetQuery();
        return $this->_exec($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Datatable query
     *
     * @param string $table Table name
     * @param array $config Configuration
     * @return array
     */
    public function dataTable($table, $config = []) {

        $this->dtConfig = $config;

        // Incomming Parameters
        $draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
        $length = isset($_POST['length']) ? (int) $_POST['length'] : 0;
        $start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
        $cols   = isset($_POST['columns']) ? $_POST['columns'] : [];
        $search = isset($_POST['search']) ? $_POST['search'] : [];
        $order  = isset($_POST['order']) ? $_POST['order'] : [];

        // Protection against miss use
        $this->columns  = [];
        $this->distinct = false;
        $this->limit    = 0;
        $this->offset   = 0;
        $this->orderby  = [];

        // Number of results
        $this->save();
        $iTotal = $this->count($table);
        $this->load();

        // Number of filtered results
        $this->_dtWhere($cols, trim($search['value']));
        $this->save();
        $iFilteredTotal = $this->count($table);
        $this->load();

        // Results
        $this->_dtSelect($cols);
        $this->_dtOrder($cols, $order);
        $aaData = $this->limit($length, $start)->read($table)->all();

        $this->dtConfig = [];

        // Output
        return [
            'draw'            => $draw,
            'recordsTotal'    => $iTotal,
            'recordsFiltered' => $iFilteredTotal,
            'data'            => $aaData
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * Save a query
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function save() {
        $this->save                     = [];
        $this->save['columns']          = $this->columns;
        $this->save['increments']       = $this->increments;
        $this->save['unparsed']         = $this->unparsed;
        $this->save['distinct']         = $this->distinct;
        $this->save['from']             = $this->from;
        $this->save['join']             = $this->join;
        $this->save['where']            = $this->where;
        $this->save['whereGroupCount']  = $this->whereGroupCount;
        $this->save['whereGroupLevel']  = $this->whereGroupLevel;
        $this->save['groupby']          = $this->groupby;
        $this->save['having']           = $this->having;
        $this->save['havingGroupCount'] = $this->havingGroupCount;
        $this->save['havingGroupLevel'] = $this->havingGroupLevel;
        $this->save['limit']            = $this->limit;
        $this->save['offset']           = $this->offset;
        $this->save['orderby']          = $this->orderby;
        $this->save['data']             = $this->data;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Load a query saved
     *
     * @return \Orb\EasyRecord\ERQB
     */
    public function load() {
        $this->columns          = $this->save['columns'];
        $this->increments       = $this->save['increments'];
        $this->unparsed         = $this->save['unparsed'];
        $this->distinct         = $this->save['distinct'];
        $this->from             = $this->save['from'];
        $this->join             = $this->save['join'];
        $this->where            = $this->save['where'];
        $this->whereGroupCount  = $this->save['whereGroupCount'];
        $this->whereGroupLevel  = $this->save['whereGroupLevel'];
        $this->groupby          = $this->save['groupby'];
        $this->having           = $this->save['having'];
        $this->havingGroupCount = $this->save['havingGroupCount'];
        $this->havingGroupLevel = $this->save['havingGroupLevel'];
        $this->limit            = $this->save['limit'];
        $this->offset           = $this->save['offset'];
        $this->orderby          = $this->save['orderby'];
        $this->data             = $this->save['data'];

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * DataTable : Select
     *
     * @param array $columns
     */
    private function _dtSelect($columns) {
        foreach ($columns as $c) {
            $col = $c['data'];
            if (!isset($this->dtConfig[$col]['exclude'])) {

                if (isset($this->dtConfig[$col]['select'])) {
                    $this->select($this->dtConfig[$col]['select'] . ' AS ' . $col);
                }
                else {
                    $this->select($this->escape($col));
                }
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * DataTable : search
     *
     * @param array $columns
     * @param array $values
     * @return void
     */
    private function _dtWhere($columns, $values) {
        if (!$values) {
            return;
        }

        foreach (explode(' ', $values) as $v) {
            $value = $this->escape(trim($v));

            $this->groupStart();

            foreach ($columns as $c) {
                $col = $c['data'];

                if ($c['searchable'] == 'true' && !isset($this->dtConfig[$col]['exclude'])) {

                    // Callback for changing the value
                    $val = $value;
                    if (isset($this->dtConfig[$col]['value']) && is_callable($this->dtConfig[$col]['value'])) {
                        $val = call_user_func($this->dtConfig[$col]['value'], $value);
                        if ($val === null) {
                            continue;
                        }
                    }

                    // Field name
                    $field = $this->escape($col);

                    if (isset($this->dtConfig[$col]['where'])) {
                        $field = $this->dtConfig[$col]['where'];
                    }
                    elseif (isset($this->dtConfig[$col]['select'])) {
                        $field = $this->dtConfig[$col]['select'];
                    }

                    // Search type
                    if (isset($this->dtConfig[$col]['exact']) && $this->dtConfig[$col]['exact']) {
                        $this->orWhere($field, '=', $val);
                    }
                    else {
                        $this->orLike($field, $val, 'both');
                    }
                }
            }

            $this->groupEnd();
        }
    }

    // -------------------------------------------------------------------------

    /**
     * DataTable : order
     *
     * @param array $columns
     * @param array $order
     */
    private function _dtOrder($columns, $order) {
        foreach ($order as $o) {
            $c   = $columns[$o['column']];
            $col = $c['data'];

            if ($c['orderable'] == 'true' && !isset($this->dtConfig[$col]['exclude'])) {

                if (isset($this->dtConfig[$col]['order'])) {
                    $this->orderBy($this->dtConfig[$col]['order'] . ' ' . $o['dir']);
                }
                elseif (isset($this->dtConfig[$col]['select'])) {
                    $this->orderBy($col . ' ' . $o['dir']);
                }
                else {
                    $this->orderBy($this->escape($col) . ' ' . $o['dir']);
                }
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Reset the query
     */
    private function _resetQuery() {
        $this->columns          = [];
        $this->increments       = [];
        $this->distinct         = false;
        $this->from             = '';
        $this->join             = [];
        $this->where            = [];
        $this->whereGroupCount  = 0;
        $this->whereGroupLevel  = 0;
        $this->groupby          = [];
        $this->having           = [];
        $this->havingGroupCount = 0;
        $this->havingGroupLevel = 0;
        $this->limit            = 0;
        $this->offset           = 0;
        $this->orderby          = [];
    }

    // -------------------------------------------------------------------------

    /**
     * Reset the data
     *
     * @return array
     */
    private function _resetData() {

        $data       = $this->data;
        $this->data = [];
        return $data;
    }

    // -------------------------------------------------------------------------

    /**
     * Execute the query
     *
     * @param string $sql
     * @return \Orb\EasyRecord\ERResult|int
     */
    private function _exec($sql) {

        $param   = new ERBindParam();
        $matches = [];

        if (preg_match_all("#§¤§[0-9]+§¤§#", $sql, $matches) && $matches) {

            foreach ($matches[0] as $key) {
                $v = $this->data[$key];
                $param->add($this->_getType($v), $v);
            }

            $sql = preg_replace("#§¤§[0-9]+§¤§#", "?", $sql);
        }

        $this->_resetData();

        return $this->query($sql, $param);
    }

    // -------------------------------------------------------------------------

    /**
     * Get the type of a variable for a mysqli bind
     *
     * @param mixed $var
     * @return string
     */
    private function _getType($var) {
        if (is_int($var) || is_bool($var)) {
            return 'i';
        }

        if (is_float($var)) {
            return 'd';
        }

        return 's';
    }

    // -------------------------------------------------------------------------

    /**
     * Build an INSERT or REPLACE query
     *
     * @param string $table Table name
     * @param array $data data to insert
     * @param string $replace build a REPLACE query
     * @return string
     */
    private function _buildInsert($table, $data, $replace = false) {
        $this->_from($table);
        $this->_setData($data);

        $cols  = $this->columns;
        $datai = array_keys($this->data);

        foreach ($this->unparsed as $k => $v) {
            $cols[]  = $k;
            $datai[] = $v;
        }

        $sql = ($replace ? "REPLACE" : "INSERT") . " INTO\n\t" . $this->from;
        $sql .= "(\n\t\t" . implode(",\n\t\t", $cols) . "\n\t)\n";
        $sql .= "VALUES \t(\n\t\t" . implode(",\n\t\t", $datai) . "\n\t)";
        return $sql;
    }

    // -------------------------------------------------------------------------

    /**
     * Build an UPDATE query fragment
     *
     * @param string $table Table name
     * @param array $data data to update
     * @return string
     */
    private function _buildUpdate($table, $data) {
        $this->_from($table);
        $this->_setData($data);

        $sql = "UPDATE\n\t" . $this->from;
        $sql .= $this->_buildJoin();
        $sql .= "\nSET\n\t";

        $cols = [];

        foreach ($this->columns as $v => $c) {
            $cols[] = "{$c} = {$v}";
        }

        foreach ($this->increments as $v => $c) {
            $cols[] = "{$c} = {$c} + {$v}";
        }

        foreach ($this->unparsed as $k => $v) {
            $cols[] = "{$k} = {$v}";
        }

        if ($cols) {
            $sql .= implode(",\n\t", $cols);
        }

        return $sql;
    }

    // -------------------------------------------------------------------------

    /**
     * Build a delete query fragment
     *
     * @param string $table Table name
     * @return string
     */
    private function _buildDelete($table) {
        $this->_from($table);

        return "DELETE FROM\n\t" . $this->from;
    }

    // -------------------------------------------------------------------------

    /**
     * Build a SELECT query
     * @return type
     */
    private function _buildRead() {
        $sql = $this->_buildSelect();
        $sql .= $this->_buildFrom();
        $sql .= $this->_buildJoin();
        $sql .= $this->_buildWhere();
        $sql .= $this->_buildGroupBy();
        $sql .= $this->_buildHaving();
        $sql .= $this->_buildOrderBy();
        $sql .= $this->_buildLimit();

        return $sql;
    }

    // -------------------------------------------------------------------------

    /**
     * Build the select query fragment
     *
     * @return string
     */
    private function _buildSelect() {
        if (!$this->columns) {
            $this->select();
        }

        $sql = $this->distinct ? 'SELECT DISTINCT ' : 'SELECT ';

        $sql .= "\n\t" . implode(",\n\t", $this->columns);


        return $sql;
    }

    // -------------------------------------------------------------------------

    /**
     *  Build the from query fragment
     *
     * @return type
     */
    private function _buildFrom() {
        return "\nFROM\n\t" . $this->from;
    }

    // -------------------------------------------------------------------------

    /**
     * Build the join on query fragment
     *
     * @return string
     */
    private function _buildJoin() {
        if ($this->join) {
            return "\n" . implode("\n", $this->join);
        }

        return '';
    }

    // -------------------------------------------------------------------------

    /**
     * Build the where query fragment
     *
     * @return string
     */
    private function _buildWhere() {
        // Close all groups
        while ($this->whereGroupLevel > 0) {
            $this->_groupEnd('where');
        }

        if ($this->where) {
            return "\nWHERE\n\t" . implode("\n\t", $this->where);
        }

        return '';
    }

    // -------------------------------------------------------------------------

    /**
     * Build the order by query fragment
     *
     * @return string
     */
    private function _buildOrderBy() {
        if ($this->orderby) {
            return "\nORDER BY\n\t" . implode(",\n\t", $this->orderby);
        }

        return '';
    }

    // -------------------------------------------------------------------------

    /**
     * Build the group by query fragment
     *
     * @return string
     */
    private function _buildGroupBy() {
        if ($this->groupby) {
            return "\nGROUP BY\n\t" . implode(",\n\t", $this->groupby);
        }

        return '';
    }

    // -------------------------------------------------------------------------

    /**
     * Build the having query fragment
     *
     * @return string
     */
    private function _buildHaving() {
        // Close all groups
        while ($this->havingGroupLevel > 0) {
            $this->_groupEnd('having');
        }

        if ($this->having) {
            return "\nHAVING\n\t" . implode("\n\t", $this->having);
        }

        return '';
    }

    // -------------------------------------------------------------------------

    /**
     * Build the limit query fragment
     *
     * @return string
     */
    private function _buildLimit() {

        if ($this->limit) {

            $sql = "\nLIMIT\t" . $this->limit;

            if ($this->offset) {
                $sql .= " OFFSET " . $this->offset;
            }

            return $sql;
        }

        return '';
    }

    // -------------------------------------------------------------------------

    /**
     * Remove multi spaces
     *
     * @param string $str
     * @return string
     */
    private function _cleanString($str) {
        return preg_replace("#\s+#", ' ', trim($str));
    }

    // -------------------------------------------------------------------------

    /**
     * Format the table
     *
     * @param string $table Table name
     */
    private function _from($table) {
        $this->from = trim($table);
    }

    // -------------------------------------------------------------------------

    /**
     *
     * @param string $joiner
     * @param string $field field name
     * @param string $operator sql operator
     * @param mixed $value value
     * @param string $type where or having
     */
    private function _condition($joiner, $field, $operator, $value, $type) {

        // Add joiner if not the first element or not the first element of a group
        $_joiner = '';
        if (($this->{$type . 'GroupLevel'} == 0 && $this->{$type}) || ($this->{$type . 'GroupLevel'} > 0 && $this->{$type . 'GroupCount'} > 0)) {
            $_joiner = $joiner . ' ';
        }

        // An element is added in a group
        if ($this->{$type . 'GroupLevel'} > 0) {
            $this->{$type . 'GroupCount'} ++;
        }

        $_field = trim($field);

        if ($operator === null) {
            $this->{$type}[] = $this->_lvlIndent($type) . $_joiner . $_field;
            return;
        }

        $_operator = ' ' . trim($operator) . ' ';
        $_value    = '';

        // Number of data : used for the key alias
        $num = count($this->data);

        // Set the null values
        if ($value === null) {
            $_value = 'null';
        }
        // An array of values
        elseif (is_array($value)) {
            foreach ($value as $v) {
                $_key              = '§¤§' . $num . '§¤§';
                $_value[]          = $_key;
                $this->data[$_key] = $v;
                $num++;
            }

            $_value = '(' . implode(',', $_value) . ')';
        }
        // One value
        else {
            $_key              = '§¤§' . $num . '§¤§';
            $_value            = $_key;
            $this->data[$_key] = $value;
        }

        // Add the query string to the where array
        $this->{$type}[] = $this->_lvlIndent($type) . $_joiner . $_field . $_operator . $_value;
    }

    // -------------------------------------------------------------------------

    /**
     * Format a like value
     *
     * @param string $value
     * @param string $side add wildcards to left, right, both
     * @return string
     */
    private function _getLike($value, $side) {
        $side = mb_strtolower($side);

        if ($side == 'left') {
            $value = '%' . $value;
        }
        elseif ($side == 'right') {
            $value = $value . '%';
        }
        elseif ($side == 'both') {
            $value = '%' . $value . '%';
        }

        return $value;
    }

    // -------------------------------------------------------------------------

    /**
     * Group start
     *
     * @param string $joiner AND or OR
     * @param string $type where or having
     */
    private function _groupStart($joiner, $type) {
        // Add joiner if not the first element
        // or not the first element of a group
        $_joiner = $this->_joiner($joiner, $type);

        $this->{$type}[] = $this->_lvlIndent($type) . $_joiner . '(';

        $this->{$type . 'GroupCount'} = 0;
        $this->{$type . 'GroupLevel'} ++;
    }

    // -------------------------------------------------------------------------

    /**
     * Group end
     *
     * @param string $type where or having
     */
    private function _groupEnd($type) {
        $this->{$type . 'GroupCount'} = 0;

        if ($this->{$type . 'GroupLevel'} > 0) {
            $this->{$type . 'GroupLevel'} --;
            $this->{$type}[] = $this->_lvlIndent($type) . ')';
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Chaining groups
     *
     * @param string $joiner
     * @return string
     */
    private function _joiner($joiner, $type) {
        $_joiner = '';
        if (($this->{$type . 'GroupLevel'} == 0 && $this->{$type}) || ($this->{$type . 'GroupLevel'} > 0 && $this->{$type . 'GroupCount'} > 0)) {
            $_joiner = $joiner . ' ';
        }

        return $_joiner;
    }

    // -------------------------------------------------------------------------

    /**
     * Group formating : indentation
     *
     * @param string $type
     * @return string
     */
    private function _lvlIndent($type) {
        return str_repeat("\t", $this->{$type . 'GroupLevel'});
    }

    // -------------------------------------------------------------------------

    /**
     * Data setter
     *
     * @param string $name
     * @param mixed $value
     * @return \Orb\EasyRecord\ERQB
     */
    private function _setData($name, $value = null) {
        if (!$name) {
            return $this;
        }

        if (!is_array($name)) {
            $name = [$name => $value];
        }

        $num = count($this->data);

        foreach ($name as $k => $v) {
            $_key                 = '§¤§' . $num . '§¤§';
            $this->columns[$_key] = trim($k);
            $this->data[$_key]    = $v;
            $num++;
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Insert a data set
     *
     * @param string $table Table name
     * @param array $cols list of columns
     * @param array $data data set
     * @param int $size max number of insert for each query
     * @param function $callback Callback.
     *        The number of inserted rows is send as first parameter
     * @return int Number of inserted rows
     * @throws ERException
     */
    public function insertBatch($table, array $cols, array $data, $size = 100, $callback = null) {
        // Nothing to do
        if (!$cols || !$data) {
            return 0;
        }

        // Number of cols
        $iCols = count($cols);

        // Check if cols and all data have same size
        foreach ($data as $v) {
            if ($iCols !== count($v)) {
                throw new ERException('cols and data have not same size');
            }
        }

        // Limit the number of data for each SQL queries
        if (count($data) > $size) {
            $data = array_chunk($data, $size);
        }
        else {
            $data = [$data];
        }

        // Affected rows
        $iInsert = 0;

        // Size of the last set of data
        $last = count($data) - 1;

        // SQL Prepare statement
        $query = $this->_buildInsertBatch($table, $cols, $iCols, $size);
        $this->prepare($query);

        foreach ($data as $i => $rows) {
            $isLast = $i == $last;

            // New SQL statement if the last set of data has a lesser size
            if ($isLast && count($rows) != $size) {
                $query = $this->_buildInsertBatch($table, $cols, $iCols, count($rows));
                $this->prepare($query);
            }

            // Bind data for the current set
            $param = new ERBindParam();
            foreach ($rows as $row) {
                foreach ($row as $col) {
                    $param->add($this->_getType($col), $col);
                }
            }

            // Execute the query
            $iInsert += $this->execute($param, $isLast);

            // Callback
            if ($callback !== null && is_callable($callback)) {
                $callback($iInsert);
            }
        }

        return $iInsert;
    }

    // -------------------------------------------------------------------------

    /**
     * Build an insert multiple query
     *
     * @param string $table Table name
     * @param array $cols array of cols
     * @param int $x number of columns
     * @param int $y number of rows
     * @return string
     */
    private function _buildInsertBatch($table, array $cols, $x, $y) {
        $sql = "INSERT INTO\n\t" . trim($table);
        $sql .= "(\n\t\t" . implode(",\n\t\t", $cols) . "\n\t)\n";
        $sql .= "VALUES";

        $values = [];
        for ($i = 0; $i < $y; $i++) {
            $val = [];
            for ($j = 0; $j < $x; $j++) {
                $val[] = '?';
            }
            $values[] = "\n\t(\n\t\t" . implode(",\n\t\t", $val) . "\n\t)";
        }
        $sql .= join(',', $values);

        return $sql;
    }

    // -------------------------------------------------------------------------
}

/* EOF */