<?php

namespace System\Orm;

/**
 * AntiSpam with ERCache
 */
abstract class AntiSpam {

    /**
     * Current instance
     * 
     * @var \Orb\EasyRecord\AntiSpam
     */
    private static $instance = null;

    /**
     * ERCache
     * 
     * @var \Orb\EasyRecord\ERCache
     */
    private $cache = null;

    /**
     * Config for each namespace
     * 
     * @var array
     */
    private $config = [];

    /**
     * Prefix
     * 
     * @var string
     */
    private $prefix = '';

    // -------------------------------------------------------------------------

    private function __construct() {

        $this->cache = ERCache::getInstance();

        $this->config = $this->_initConfig();
        $this->prefix = $this->_initPrefix() . '_';
    }

    /**
     * Returns current instance of this class
     *
     * @return \Orb\EasyRecord\AntiSpam
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    // -------------------------------------------------------------------------

    /**
     * Set config for each namespace
     * ex :
     * [
     * 'login' => ['expiration' => 60, 'limit' => 10],
     * 'signin' => ['expiration' => 120, 'limit' => 5]
     * ]
     * 
     * @return array
     */
    abstract protected function _initConfig();

    // -------------------------------------------------------------------------

    /**
     * Set a prefix for each namespace
     * 
     * @return string
     */
    abstract protected function _initPrefix();

    // -------------------------------------------------------------------------

    /**
     * Register a spam
     * 
     * @param string $namespace
     * @param string $key
     * @return mixed
     */
    public function add($namespace, $key) {

        if (!isset($this->config[$namespace]['expiration'])) {
            return false;
        }

        return $this->cache->nsIncrement($this->prefix . $namespace, $key, $this->config[$namespace]['expiration'], 1, 1);
    }

    // -------------------------------------------------------------------------

    /**
     * Check if the number of spams is under the limit
     * 
     * @param string $namespace
     * @param string $key
     * @return bool
     */
    public function isValid($namespace, $key) {

        if (!isset($this->config[$namespace]['limit']) || !$this->cache->isActive()) {
            return true;
        }

        $nb = $this->cache->nsGetCounter($this->prefix . $namespace, $key, 0);

        return $nb < $this->config[$namespace]['limit'];
    }

    // -------------------------------------------------------------------------

    /**
     * Get expiration
     * 
     * @param string $namespace
     * @return int
     */
    public function getExpiration($namespace) {
        return isset($this->config[$namespace]['expiration']) ? $this->config[$namespace]['expiration'] : 0;
    }

    // -------------------------------------------------------------------------

    /**
     * Get limit
     * 
     * @param string $namespace
     * @return int
     */
    public function getLimit($namespace) {
        return isset($this->config[$namespace]['limit']) ? $this->config[$namespace]['limit'] : 0;
    }

    // -------------------------------------------------------------------------
}

/* End of file */