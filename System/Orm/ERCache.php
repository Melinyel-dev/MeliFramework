<?php

namespace System\Orm;

/**
 * ERCache class
 *
 * Features :
 * Emulates namespaces in memcached
 * Limit memcached calls with a local cache
 * Support not connected mode with the local cache
 *
 * Warning :
 * The namespace is not supported natively but emulates by this class.
 * Some of methods helps to manage the keys stored by memcached.
 * Some keys deleted by memcached, can persist in the index.
 * Use precise methods listed below for getting data
 * Use imprecise methods listed below to manage memcached
 * Use the nsRefresh method every days to keep the indexes in memory
 *
 * Precise methods for getting data :
 * nsGet
 * nsGetMulti
 *
 * Imprecise methods for getting data :
 * nsGetNamespaces
 * nsGetNamespacesKeys
 */
class ERCache extends \Memcached {

    private $prefix          = '__';
    private $cacheLocal      = [];
    private $active          = FALSE;
    private static $instance = NULL;

    // -------------------------------------------------------------------------

    /**
     * Returns current instance of this class
     *
     * @return \Orb\EasyRecord\ERCache
     */
    public static function getInstance() {
        if (self::$instance === NULL) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    // -------------------------------------------------------------------------

    /**
     * Connect to a memcached server
     *
     * @param string $host
     * @param string $port
     * @return boolean
     */
    public function connect($host, $port) {

        // Check if the server is active
        $serverExists = $this->_isServerActive($host, $port);

        // Check if the server is already registered
        if ($serverExists) {
            $status = parent::getVersion();
            if (!isset($status[$host . ':' . $port])) {
                parent::addServer($host, $port);
            }
        }

        // Set if memcached is available or not
        $this->active = $serverExists && !empty(parent::getVersion());

        return $this->active;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the prefix
     *
     * @param type $prefix
     */
    public function setPrefix($prefix = '') {
        if ($prefix) {
            $this->prefix = '__' . $prefix . '_';
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Get if a server is active
     *
     * @return type
     */
    public function isActive() {
        return $this->active;
    }

    // -------------------------------------------------------------------------

    /**
     * Get an item
     *
     * @param string $namespace If FALSE, get all namespaces keys
     * @param string $key If FALSE, get all keys in a namespace
     * @return boolean
     */
    public function nsGet($namespace, $key, $default = FALSE) {

        // Get from local
        if (isset($this->cacheLocal[$namespace][$key])) {
            return $this->cacheLocal[$namespace][$key];
        }

        // Get from memcached
        if ($this->isActive()) {
            $result                             = $this->_getItem($namespace, $key);
            $this->cacheLocal[$namespace][$key] = $result;
            return $result;
        }

        return $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Get a list of items
     *
     * @param string $namespace
     * @param array $keys If empty, it returns all data from the namespace
     * @return array
     */
    public function nsGetMulti($namespace, $keys = []) {

        $result = [];

        // Data validation
        if (!$namespace) {
            return $result;
        }

        // All keys if empty
        if (empty($keys)) {
            $keys = $this->nsGetNamespaceKeys($namespace);
        }

        // Get from local
        $keysFound = [];
        foreach ($keys as $key) {
            if (isset($this->cacheLocal[$namespace][$key])) {
                $result[$key] = $this->cacheLocal[$namespace][$key];
                // Keep in memory the key found in local
                $keysFound[]  = $key;
            }
        }

        // Get from memcached
        if ($this->isActive()) {

            // Get all keys not found in the local cache
            $keysNotFound = array_diff($keys, $keysFound);

            // All keys are not found in the local cache
            if ($keysNotFound) {

                // Build memcache keys using the namespacing
                $nsKeys = [];
                foreach ($keysNotFound as $key) {
                    $nsKeys[] = $this->keyItem($namespace, $key);
                }

                // Get items not found in the local cache
                $resNotFound = parent::getMulti($nsKeys);

                // Add data to the result array
                if (is_array($resNotFound)) {
                    foreach ($resNotFound as $nsKey => $value) {
                        $key          = str_replace($this->prefix . $namespace . '_', '', $nsKey);
                        $result[$key] = $value;
                    }
                }
            }
        }

        // Save local
        $this->cacheLocal[$namespace] = $result;

        return $result;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the list of keys from a namespace
     *
     * @param string $namespace
     * @return array
     */
    public function nsGetNamespaceKeys($namespace) {

        // Get from memcached
        if ($this->isActive()) {
            return array_keys($this->_getNamespaceKeysCleared($namespace));
        }

        // Get from local
        if (isset($this->cacheLocal[$namespace])) {
            return array_keys($this->cacheLocal[$namespace]);
        }
        else {
            return [];
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Get the list of namespaces
     *
     * @return array
     */
    public function nsGetNamespaces() {

        // Get from memcached
        if ($this->isActive()) {
            return array_keys($this->_getNamespaces());
        }

        // Get from local
        return array_keys($this->cacheLocal);
    }

    // -------------------------------------------------------------------------

    /**
     * Set an item
     *
     * @param string $namespace
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     */
    public function nsSet($namespace, $key, $value, $expiration = 0) {

        // Set to memcached
        if ($this->isActive()) {

            // Build expiration time in seconds
            $exp = $this->_buildExpiration($expiration);

            // Set the namespace
            $this->_setNamespaces($namespace);

            // Set key to the namespace
            $this->_setNamespaceKeys($namespace, $key, $exp);

            // Set item
            $this->_setItem($namespace, $key, $value, $exp);
        }

        // Set to local cache
        $this->cacheLocal[$namespace][$key] = $value;
    }

    // -------------------------------------------------------------------------

    /**
     * Set a list of items
     *
     * @param string $namespace
     * @param string $items
     * @param int $expiration
     */
    public function nsSetMulti($namespace, array $items, $expiration = 0) {

        // Set to memcached
        if ($this->isActive()) {

            // Build expiration time in seconds
            $exp = $this->_buildExpiration($expiration);

            // Set namespace
            $this->_setNamespaces($namespace);

            // Set keys
            $this->_setNamespaceKeys($namespace, array_keys($items), $exp);

            // Set items
            $nsItems = [];
            foreach ($items as $key => $value) {
                $nsItems[$this->keyItem($namespace, $key)] = $value;
            }
            parent::setMulti($nsItems, $exp);
        }

        // Set to local cache
        foreach ($items as $key => $value) {
            $this->cacheLocal[$namespace][$key] = $value;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Delete an item
     *
     * @param string $namespace If FALSE, all data are deleted
     * @param string $key If FALSE all data in a namespace are deleted
     */
    public function nsDelete($namespace, $key) {

        // Delete to memcached
        if ($this->isActive()) {
            // Delete item
            $this->_delItem($namespace, $key);

            // Delete key in parent
            $this->_delNamespaceKeys($namespace, $key);
        }

        // Delete to local
        if (isset($this->cacheLocal[$namespace][$key])) {
            unset($this->cacheLocal[$namespace][$key]);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Delete items into a namespace
     *
     * @param  string $namespace If FALSE, all data are deleted
     * @param  array  $keys      If FALSE all data in a namespace are deleted
     */
    public function nsDeleteMulti($namespace, array $keys) {

        foreach ($keys as $key) {
            // Delete item
            $this->nsDelete($namespace, $key);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Delete all from a namespace
     *
     * @param string $namespace If FALSE, all data are deleted
     */
    public function nsDeleteNamespaceKeys($namespace) {

        // Delete to memcached
        if ($this->isActive()) {

            // Delete childs
            $keys = $this->_getNamespaceKeys($namespace);
            if (!empty($keys)) {
                foreach (array_keys($keys) as $key) {
                    $this->nsDelete($namespace, $key);
                }
            }

            // Delete key
            $this->_delNamespaces($namespace);
        }

        // Delete to local
        if (isset($this->cacheLocal[$namespace])) {
            unset($this->cacheLocal[$namespace]);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Delete all
     */
    public function nsDeleteNamespaces() {

        // Delete to memcached
        if ($this->isActive()) {

            // Delete childs
            $namespaces = $this->_getNamespaces();
            if (is_array($namespaces)) {
                foreach (array_keys($namespaces) as $namespace) {
                    $this->nsDeleteNamespaceKeys($namespace);
                }
            }

            // Delete index
            $this->_delNamespaces();
        }

        // Delete to local
        $this->cacheLocal = [];
    }

    // -------------------------------------------------------------------------

    /**
     * Refresh indexes
     */
    public function nsRefresh() {

        // Get the list of namespaces
        $namespaces = $this->_getNamespaces();

        // Refresh the list of keys for each namespace
        foreach (array_keys($namespaces) as $i => $namespace) {

            $namespaceKeys = $this->_getNamespaceKeysCleared($namespace);

            // Removes the empty namespace
            if (!$namespaceKeys) {
                unset($namespaces[$i]);
            }
        }

        // Refresh the list of namespaces
        parent::set($this->_keyNamespaces(), $namespaces);
    }

    // -------------------------------------------------------------------------

    /**
     * Check if a server is active
     *
     * @param string $host
     * @param string $port
     * @return boolean
     */
    private function _isServerActive($host, $port) {
        $fp = @fsockopen($host, $port);
        if ($fp) {
            fclose($fp);
            return TRUE;
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the key for storing all namespaces
     *
     * @return string
     */
    private function _keyNamespaces() {
        return $this->prefix . 'namespaces';
    }

    // -------------------------------------------------------------------------

    /**
     * Get the namespaces list
     *
     * @return array|FALSE
     */
    private function _getNamespaces() {
        $namespaces = parent::get($this->_keyNamespaces());
        return is_array($namespaces) ? $namespaces : [];
    }

    // -------------------------------------------------------------------------

    /**
     * Set a namespace in the namespaces list
     *
     * @param string $namespace
     */
    private function _setNamespaces($namespace) {

        // Get namespaces list
        $namespaces = $this->_getNamespaces();

        // Set the namespace in the list
        $namespaces[$namespace] = TRUE;

        // Save
        parent::set($this->_keyNamespaces(), $namespaces);
    }

    // -------------------------------------------------------------------------

    /**
     * Delete a namespace in the namespaces list
     *
     * @param string|FALSE $namespace If FALSE, delete all
     */
    private function _delNamespaces($namespace = FALSE) {

        // Delete a namespace
        if ($namespace) {
            $namespaces = $this->_getNamespaces();
            if (isset($namespaces[$namespace])) {
                unset($namespaces[$namespace]);
            }
        }
        // Delete all
        else {
            $namespaces = [];
        }

        // Save
        parent::set($this->_keyNamespaces(), $namespaces);
    }

    // -------------------------------------------------------------------------

    /**
     * Get the key for storing all keys from a namespace
     *
     * @param type $namespace
     * @return string
     */
    private function _keyNamespaceKeys($namespace) {
        return $this->prefix . $namespace . '_keys';
    }

    // -------------------------------------------------------------------------

    /**
     * Get all keys from a namespace
     *
     * @param string $namespace
     * @return array|FALSE
     */
    private function _getNamespaceKeys($namespace) {
        $keys = parent::get($this->_keyNamespaceKeys($namespace));
        return is_array($keys) ? $keys : [];
    }

    // -------------------------------------------------------------------------

    /**
     * Set a key for a namespace
     *
     * @param string $namespace
     * @param string|array $key
     */
    private function _setNamespaceKeys($namespace, $key, $expiration) {

        // Get list of keys from a namspace
        $keys = $this->_getNamespaceKeys($namespace);

        // Set an array of keys
        if (is_array($key)) {
            foreach ($key as $k) {
                $keys[$k] = $expiration;
            }
        }
        // Set a key
        else {
            $keys[$key] = $expiration;
        }

        // Save
        parent::set($this->_keyNamespaceKeys($namespace), $keys);
    }

    // -------------------------------------------------------------------------


    private function _getNamespaceKeysCleared($namespace) {

        // Get keys
        $keys = $this->_getNamespaceKeys($namespace);

        // Removes expired keys
        $now = time();
        foreach ($keys as $key => $expiration) {
            if ($expiration < $now) {
                unset($keys[$key]);
            }
        }

        // Save
        parent::set($this->_keyNamespaceKeys($namespace), $keys);

        return $keys;
    }

    /**
     * Delete a key for a namespace
     *
     * @param string $namespace
     */
    private function _delNamespaceKeys($namespace, $key = FALSE) {

        // Delete a key
        if ($key) {
            $keys = $this->_getNamespaceKeys($namespace);
            if (isset($keys[$key])) {
                unset($keys[$key]);
            }
        }
        // Delete all keys
        else {
            $keys = [];
        }

        // Save
        parent::set($this->_keyNamespaceKeys($namespace), $keys);
    }

    // -------------------------------------------------------------------------

    /**
     * Get the key for storing an item
     *
     * @param string $namespace
     * @param string $key
     * @return string
     */
    public function keyItem($namespace, $key) {
        return $this->prefix . $namespace . '_' . $key;
    }

    // -------------------------------------------------------------------------

    /**
     * Get an item
     *
     * @param string $namespace
     * @param string $key
     * @return mixed|FALSE
     */
    private function _getItem($namespace, $key) {
        return parent::get($this->keyItem($namespace, $key));
    }

    // -------------------------------------------------------------------------

    /**
     * Set an item
     *
     * @param string $namespace
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     */
    private function _setItem($namespace, $key, $value, $expiration) {
        // Add data to memcache
        parent::set($this->keyItem($namespace, $key), $value, $expiration);
    }

    // -------------------------------------------------------------------------

    /**
     * Delete an item
     *
     * @param string $namespace
     * @param string $key
     */
    private function _delItem($namespace, $key) {
        parent::delete($this->keyItem($namespace, $key));
    }

    // -------------------------------------------------------------------------

    /**
     * Build expiration time
     *
     * @param int $expiration
     * @return int
     */
    private function _buildExpiration($expiration) {

        // Set expiration in minutes
        $exp = (int) $expiration;

        // Limit min / max (30 days - 43200 minutes)
        $max = 43200;
        if ($exp == 0 || $exp > $max) {
            $exp = $max;
        }

        return \time() + $exp * 60;
    }

    // -------------------------------------------------------------------------
}

/* End of file */