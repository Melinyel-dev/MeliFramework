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
 * `CLEF_SESSION` bigint(20) NOT null AUTO_INCREMENT,
 * `DATA` longblob NOT null,
 * `LAST_ACTIVITY` int(10) unsigned NOT null,
 * `SEARCH` varchar(32) DEFAULT null,
 * PRIMARY KEY (`CLEF_SESSION`),
 * KEY `LAST_ACTIVITY_INDEX` (`LAST_ACTIVITY`),
 * KEY `SEARCH_INDEX` (`SEARCH`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 * @author sugatasei
 */
class ERSession extends \Orb\Helpers\Data {

    /**
     * Current instance of this class
     *
     * @var \Orb\EasyRecord\ERSession
     */
    protected static $instance = null;

    /**
     * Current instance of Database
     *
     * @var \Orb\EasyRecord\ERDB
     */
    protected $db = null;

    /**
     * Current timestamp
     *
     * @var int
     */
    protected $time = null;

    /**
     * Config
     *
     * @var array
     */
    protected $config = [
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
    protected $id = null;

    /**
     * Is substitute user
     *
     * @var int
     */
    protected $isSU = false;

    // -------------------------------------------------------------------------

    /**
     * Class constructor
     */
    protected function __construct(ERDB $db) {
        $this->db = $db;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns current instance of this class
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public static function getInstance(ERDB $db = null) {
        if (self::$instance === null) {
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
     * Get a configuration
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getConfig($name, $default = null) {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }

        return $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Start a session
     *
     * @return \Orb\EasyRecord\ERSession
     */
    public function start() {

        // Session already started
        if ($this->id !== null) {
            return;
        }

        // Get the session from the substitute user cookie
        $id = $this->_getCryptedCookie('_SU');

        // Activate the subsitute user mode
        if ($id) {
            $this->isSU = true;
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
        if ($this->id === null) {
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
    public function close() {

        // Session not started
        if ($this->id === null) {
            return $this;
        }

        $table = $this->config['table_name'];
        $query = "UPDATE {$table} SET LAST_ACTIVITY = ?, DATA = ? WHERE CLEF_SESSION = ?";

        $bind = new ERBindParam();
        $bind->add('i', $this->_getTime());
        $bind->add('s', call_user_func($this->config['serialize'], $this->data));
        $bind->add('i', $this->id);

        $this->db->query($query, $bind);

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Connect to another session based on search field
     *
     * @param int $site
     * @param int $user
     * @return \Orb\EasyRecord\ERSession
     */
    public function su($site, $user) {

        // Activate the subsitute user mode
        $this->isSU = true;

        // Reset variables
        $this->_init();

        // Get Session from DB
        $this->_loadSearch($site, $user);

        // The session is not found
        if ($this->id === null) {
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

        // Session not started
        if ($this->id === null) {
            return $this;
        }

        // Deconnect the substitute user
        if ($this->isSU) {

            // Desactivate the subsitute user mode
            $this->isSU = false;
            Cookie::getInstance()->delete($this->_getCookie('_SU'));

            // Reset variables
            $this->_init();

            // Connect the default user
            if (($id = $this->_getCryptedCookie())) {
                // Get Session from DB
                $this->_loadId($id);
            }

            // The session is not found
            if ($this->id === null) {
                $this->_create();
            }
        }
        // Destroy data
        else {
            $this->clear();
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

        // Session started
        if ($this->id !== null) {
            return $this;
        }

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
    public function getId($default = null) {
        return $this->id === null ? $default : $this->id;
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
     * Set user for search
     * 
     * @param int $site
     * @param int $id
     * @return \Orb\EasyRecord\ERSession
     */
    public function setUser($site, $id) {
        if ($this->id) {
            $table = $this->config['table_name'];
            $query = "UPDATE {$table} SET SITEID = ?, CLEFCOMPTE = ?  WHERE CLEF_SESSION = ?";

            $bind = new ERBindParam();
            $bind->add('i', (int) $site);
            $bind->add('i', (int) $id);
            $bind->add('i', $this->id);

            $this->db->query($query, $bind);
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Check if a flash data is set
     * 
     * @param string $name
     * @return boolean
     */
    public function hasFlash($name) {
        return $this->has(':old:' . $name);
    }

    // -------------------------------------------------------------------------

    /**
     * Get a flash data set previously
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getFlash($name, $default = null) {
        return $this->get(':old:' . $name, $default);
    }

    // -------------------------------------------------------------------------

    /**
     * Set a flash data
     * 
     * @param string $name
     * @param mixed $value
     * @return \Orb\EasyRecord\ERSession
     */
    public function setFlash($name, $value) {
        $this->set(':new:' . $name, $value);

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Delete a flash data
     * 
     * @param string $name
     * @return \Orb\EasyRecord\ERSession
     */
    public function deleteFlash($name) {
        $this->deleteFlash(':old:' . $name);
        $this->deleteFlash(':new:' . $name);

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Keep a flash data
     * 
     * @param string $name
     * @return \Orb\EasyRecord\ERSession
     */
    public function keepFlash($name) {
        $value = $this->get(':old:' . $name);
        if ($value !== null) {
            $this->set(':new:' . $name, $value);
        }
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns current session timestamp
     *
     * @return int
     */
    protected function _getTime() {

        if ($this->time === null) {
            $this->time = time();
        }
        return $this->time;
    }

    // -------------------------------------------------------------------------

    /**
     * Init variables
     */
    protected function _init() {
        $this->id   = null;
        $this->data = [];
    }

    // -------------------------------------------------------------------------

    /**
     * Create a new session entry
     */
    protected function _create() {

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
    protected function _loadId($id) {
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
    protected function _loadSearch($site, $user) {
        $table = $this->config['table_name'];
        $query = "SELECT * from {$table} WHERE SITEID = ? AND CLEFCOMPTE = ? AND LAST_ACTIVITY < ? "
                . "ORDER BY LAST_ACTIVITY DESC LIMIT 1";

        $bind = new ERBindParam();
        $bind->add('i', (int) $site);
        $bind->add('i', (int) $user);
        $bind->add('i', $this->_getTime() + $this->config['expiration']);

        $this->_loadResult($this->db->query($query, $bind));
    }

    // -------------------------------------------------------------------------

    /**
     * Load session from ERResult
     *
     * @param \Orb\EasyRecord\ERResult $result
     */
    protected function _loadResult($result) {
        if ($result->count()) {
            $row      = $result->next();
            $this->id = $row['CLEF_SESSION'];

            if (($data = $row['DATA'])) {
                $this->data = (array) call_user_func($this->config['unserialize'], $data);
            }
        }

        $this->_sweepFlash();
    }

    // -------------------------------------------------------------------------

    /**
     * Returns Session Id from the cookie
     *
     * @return boolean
     */
    protected function _getCryptedCookie($suffix = '') {

        $id = Cookie::getInstance()->get($this->_getCookie($suffix), false);

        if ($id) {
            $encrypt = new Encrypt();
            return $encrypt->decode($id, $this->config['encryption_key']);
        }

        return false;
    }

    // -------------------------------------------------------------------------

    /**
     * Set crypted Session Id in the cookie
     */
    protected function _setCryptedCookie($suffix = '') {
        $encrypt     = new Encrypt();
        $encryptedId = $encrypt->encode($this->id, $this->config['encryption_key']);

        $expire = $this->_getTime() + $this->config['expiration'];
        Cookie::getInstance()->set($this->_getCookie($suffix), $encryptedId, $expire);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns cookie name with support of suffix
     *
     * @param string $suffix
     * @return string
     */
    protected function _getCookie($suffix) {
        return $this->config['cookie_name'] . $suffix;
    }

    // -------------------------------------------------------------------------

    /**
     * Removes all flashdata marked as 'old'
     * Mark all 'new' flashdata as 'old'
     *
     * @access	private
     * @return	void
     */
    function _sweepFlash() {
        $userdata = $this->all();
        foreach ($userdata as $key => $value) {

            if (strpos($key, ':old:') !== false) {
                $this->delete($key);
            }
            elseif (strpos($key, ':new:') !== false) {
                $this->set(str_replace(':new:', ':old:', $key), $value);
                $this->delete($key);
            }
        }
    }

    // -------------------------------------------------------------------------
}
