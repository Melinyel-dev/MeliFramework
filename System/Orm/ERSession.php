<?php

namespace System\Orm;

use Orb\Http\Cookie;
use Orb\Security\Encrypt;

/**
 * ERSession Class
 *
 * Use Cookie to store SESSION ID in cookies
 * Use Encrypt to protect data inside the cookie
 * Use ERDB to store data
 *
 * Table structure :
 * CREATE TABLE `SESSIONS` (
 * `CLEF_SESSION` bigint(20) NOT NULL AUTO_INCREMENT,
 * `DATA` longblob NOT NULL,
 * `LAST_ACTIVITY` int(10) unsigned NOT NULL,
 * `SEARCH` varchar(32) DEFAULT NULL,
 * PRIMARY KEY (`CLEF_SESSION`),
 * KEY `LAST_ACTIVITY_INDEX` (`LAST_ACTIVITY`),
 * KEY `SEARCH_INDEX` (`SEARCH`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 * @author mathieu
 */
class ERSession {

    /**
     * Current instance of this class
     *
     * @var \Orb\EasyRecord\ERSession
     */
    private static $instance = NULL;

    /**
     * Current instance of Database
     *
     * @var \Orb\EasyRecord\ERDB
     */
    private $db = NULL;

    /**
     * Current timestamp
     *
     * @var int
     */
    private $time = NULL;

    /**
     * Config
     *
     * @var array
     */
    private $config = [
        'cookie_name'    => 'session',
        'table_name'     => 'sessions',
        'encryption_key' => 'sessions',
        'expiration'     => 7200,
        'serialize'      => 'json_encode',
        'unserialize'    => 'json_decode'
    ];

    /**
     * Session ID
     *
     * @var int
     */
    private $id = NULL;

    /**
     * Is substitute user
     *
     * @var int
     */
    private $isSU = FALSE;

    /**
     * Data
     *
     * @var array
     */
    private $data = [];

    // -------------------------------------------------------------------------

    /**
     * Class constructor
     */
    private function __construct(ERDB $db) {
        $this->db = $db;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns current instance of this class
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public static function getInstance(ERDB $db = NULL) {
        if (self::$instance === NULL) {
            if ($db) {
                self::$instance = new static($db);
            }
            else {
                throw new \RuntimeException('ERSession needs an ERDB object');
            }
        }
        return self::$instance;
    }

    // -------------------------------------------------------------------------

    /**
     * Set a configuration
     *
     * @param array $config
     * @return \Orb\EasyRecord\ERSession
     */
    public function setConfig(array $config) {
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a session
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public function start() {

        // Session already started
        if ($this->id !== NULL) {
            return;
        }

        // Get the session from the substitute user cookie
        $id = $this->_getCryptedCookie('_SU');

        // Activate the subsitute user mode
        if ($id) {
            $this->isSU = TRUE;
        }
        // Get the session from the default user cookie
        else {
            $id = $this->_getCryptedCookie();
        }

        // A user cookie is found
        if ($id) {
            // Get Session from DB
            $this->_loadId($id);
        }

        // The session is not found
        if ($this->id === NULL) {
            $this->_create();
        }
        // Refresh Cookie
        else {
            $this->_setCryptedCookie($this->isSU ? '_SU' : '');
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Close the session
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public function close($save = TRUE) {
        if ($save) {
            $this->_save();
        }
        else {
            $this->_refresh();
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Connect to another session based on search field
     *
     * @param string $search
     * @return \Orb\EasyRecord\ERSession
     */
    public function su($search) {

        // Activate the subsitute user mode
        $this->isSU = TRUE;

        // Reset variables
        $this->_init();

        // Get Session from DB
        $this->_loadSearch($search);

        // The session is not found
        if ($this->id === NULL) {
            $this->_create();
        }
        else {
            // Save Session Id in cookie
            $this->_setCryptedCookie('_SU');
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Destroy a session
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public function destroy() {

        // Deconnect the substitute user
        if ($this->isSU) {

            // Desactivate the subsitute user mode
            $this->isSU = FALSE;
            Cookie::delete($this->_getCookie('_SU'));

            // Reset variables
            $this->_init();

            // Connect the default user
            if (($id = $this->_getCryptedCookie())) {
                // Get Session from DB
                $this->_loadId($id);
            }

            // The session is not found
            if ($this->id === NULL) {
                $this->_create();
            }
        }
        // Destroy the session
        else {

            // Remove data in DB
            if ($this->id) {
                $table = $this->config['table_name'];
                $query = "DELETE FROM {$table} WHERE CLEF_SESSION = ?";

                $bind = new ERBindParam();
                $bind->add('i', $this->id);

                $this->db->query($query, $bind);
            }

            // Reset variables
            $this->_init();

            // Create a new session
            $this->_create();
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Delete expired sessions
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public function clean() {

        $table = $this->config['table_name'];
        $query = "DELETE FROM {$table} WHERE LAST_ACTIVITY < ?";

        $bind = new ERBindParam();
        $bind->add('i', $this->_getTime() - $this->config['expiration']);

        $this->db->query($query, $bind);

        return $this;
    }

    // -------------------------------------------------------------------------

    public function isSu() {
        return $this->isSU;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns Session ID
     *
     * @return int
     */
    public function getId($default = NULL) {
        return $this->id === NULL ? $default : $this->id;
    }

    // -------------------------------------------------------------------------

    /**
     * Check if a data exists
     *
     * @param string $name
     * @return boolean
     */
    public function has($name) {
        return isset($this->data[$name]);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns data
     *
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public function & get($name, $default = NULL) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns all data
     *
     * @return array
     */
    public function all() {
        return $this->data;
    }

    // -------------------------------------------------------------------------

    /**
     * Set data
     *
     * @param string $name
     * @param string $value
     * @return \Orb\EasyRecord\ERSession
     */
    public function set($name, $value) {
        $this->data[$name] = $value;

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Delete a data
     *
     * @param string $name
     * @return \Orb\EasyRecord\ERSession
     */
    public function delete($name) {

        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Set search string
     *
     * @param string $search
     * @return \Orb\EasyRecord\ERSession
     */
    public function setSearch($search) {
        if ($this->id) {
            $table = $this->config['table_name'];
            $query = "UPDATE {$table} SET SEARCH = ? WHERE CLEF_SESSION = ?";

            $bind = new ERBindParam();
            $bind->add('s', $search);
            $bind->add('i', $this->id);

            $this->db->query($query, $bind);
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns current session timestamp
     *
     * @return int
     */
    private function _getTime() {

        if ($this->time === NULL) {
            $this->time = time();
        }
        return $this->time;
    }

    // -------------------------------------------------------------------------

    /**
     * Init variables
     */
    private function _init() {
        $this->id   = NULL;
        $this->data = [];
    }

    // -------------------------------------------------------------------------

    /**
     * Create a new session entry
     */
    private function _create() {

        // Create new session in DB
        $table = $this->config['table_name'];
        $query = "INSERT INTO {$table}(DATA, LAST_ACTIVITY) VALUES(?,?)";

        $bind = new ERBindParam();
        $bind->add('s', call_user_func($this->config['serialize'], $this->data));
        $bind->add('i', $this->_getTime());

        $this->db->query($query, $bind);

        $this->id = $this->db->lastId();

        // Save Session Id in cookie
        $this->_setCryptedCookie($this->isSU ? '_SU' : '');
    }

    // -------------------------------------------------------------------------

    /**
     * Load session from ID
     * @param int $id
     */
    private function _loadId($id) {
        $table = $this->config['table_name'];
        $query = "SELECT * from {$table} WHERE CLEF_SESSION = ? AND LAST_ACTIVITY < ?";

        $bind = new ERBindParam();
        $bind->add('i', $id);
        $bind->add('i', $this->_getTime() + $this->config['expiration']);

        $this->_loadResult($this->db->query($query, $bind));
    }

    // -------------------------------------------------------------------------

    /**
     * Load session from search
     *
     * @param string $search
     */
    private function _loadSearch($search) {
        $table = $this->config['table_name'];
        $query = "SELECT * from {$table} WHERE SEARCH = ? AND LAST_ACTIVITY < ? "
                . "ORDER BY LAST_ACTIVITY DESC LIMIT 1";

        $bind = new ERBindParam();
        $bind->add('s', $search);
        $bind->add('i', $this->_getTime() + $this->config['expiration']);

        $this->_loadResult($this->db->query($query, $bind));
    }

    // -------------------------------------------------------------------------

    /**
     * Load session from ERResult
     *
     * @param \Orb\EasyRecord\ERResult $result
     */
    private function _loadResult($result) {
        if ($result->count()) {
            $result->next();
            $this->id = $result->get('CLEF_SESSION');

            if (($data = $result->get('DATA'))) {
                $this->data = call_user_func($this->config['unserialize'], $data);
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Refresh the session activity
     */
    public function _refresh() {
        if ($this->id) {
            $table = $this->config['table_name'];
            $query = "UPDATE {$table} SET LAST_ACTIVITY = ? WHERE CLEF_SESSION = ?";

            $bind = new ERBindParam();
            $bind->add('i', $this->_getTime());
            $bind->add('i', $this->id);

            $this->db->query($query, $bind);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Save the data and refresh the session activity
     */
    public function _save() {
        if ($this->id) {
            $table = $this->config['table_name'];
            $query = "UPDATE {$table} SET LAST_ACTIVITY = ?, DATA = ? WHERE CLEF_SESSION = ?";

            $bind = new ERBindParam();
            $bind->add('i', $this->_getTime());
            $bind->add('s', call_user_func($this->config['serialize'], $this->data));
            $bind->add('i', $this->id);

            $this->db->query($query, $bind);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Returns Session Id from the cookie
     *
     * @return boolean
     */
    private function _getCryptedCookie($suffix = '') {

        $id = Cookie::get($this->_getCookie($suffix), FALSE);

        if ($id) {
            $encrypt = new Encrypt();
            return $encrypt->decode($id, $this->config['encryption_key']);
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Set crypted Session Id in the cookie
     */
    private function _setCryptedCookie($suffix = '') {
        $encrypt     = new Encrypt();
        $encryptedId = $encrypt->encode($this->id, $this->config['encryption_key']);

        $expire = $this->_getTime() + $this->config['expiration'];
        Cookie::set($this->_getCookie($suffix), $encryptedId, $expire);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns cookie name with support of suffix
     *
     * @param string $suffix
     * @return string
     */
    private function _getCookie($suffix) {
        return $this->config['cookie_name'] . $suffix;
    }

    // -------------------------------------------------------------------------
}
