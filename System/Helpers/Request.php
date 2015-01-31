<?php

namespace Orb\Http;

class Request {

    /**
     * Default configuration
     *
     * @var array
     */
    private static $config = [];

    /**
     * Path of the controller script
     *
     * @var string
     */
    private static $file = '';

    /**
     * Current URL
     *
     * @var string
     */
    private static $url;

    /**
     * HTTP method
     *
     * @var type
     */
    private static $method;

    /**
     * Remember if the params are already built
     *
     * @var boolean
     */
    private static $isBuildParams = FALSE;

    /**
     * Array of params
     *
     * @var array
     */
    private static $params = [];

    /**
     * Array of options
     *
     * @var array
     */
    private static $options = [];

    /**
     * Current remote IP
     *
     * @var string
     */
    private static $remoteIP;

    /**
     * Get user agent
     *
     * @var string
     */
    private static $agent = NULL;

    /**
     * Array of robots
     *
     * @var array
     */
    private static $robots = [];

    /**
     * Current robot
     *
     * @var string
     */
    private static $robot = NULL;

    // -------------------------------------------------------------------------

    /**
     * Set the default configuration
     *
     * @param array $conf
     */
    public static function setConf(array $conf) {
        self::$config = array_merge(self::$config, $conf);
    }

    // -------------------------------------------------------------------------

    /**
     * Set list of robots
     *
     * @param array $robots
     */
    public static function setRobots(array $robots) {
        self::$robots = $robots;
    }

    // -------------------------------------------------------------------------

    /**
     * Get URL
     *
     * @return string
     */
    public static function getUrl() {

        // First call : Create URL
        if (!isset(self::$url)) {
            $url     = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            $urlPath = parse_url($url, PHP_URL_PATH);

            self::$url = isset($urlPath) ? $urlPath : '/';
        }

        return self::$url;
    }

    // -------------------------------------------------------------------------

    /**
     * Get Remote IP Adress
     *
     * @return string|NULL
     */
    public static function getIP() {

        // First call : Create remoteIP
        if (!isset(self::$remoteIP)) {
            $ip             = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
            self::$remoteIP = $ip ? $ip : NULL;
        }
        return self::$remoteIP;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the HTTP method
     */
    private static function setMethod() {

        // Init method
        $method = '';

        // Get method
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        // Test if method is correct
        if (!in_array($method, ['GET', 'POST', 'DELETE', 'PUT', 'AJAX'])) {
            $method = 'GET';
        }

        // Test if method is ajax
        if ($method == 'POST' && isset($_POST['action']) && $_POST['action'] == 'AJAX') {
            $method = 'AJAX';
            unset($_POST['action']);
        }

        self::$method = $method;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the HTTP method
     *
     * @return string
     */
    public static function getMethod() {

        // First call : set method
        if (!isset(self::$method)) {
            self::setMethod();
        }

        return self::$method;
    }

    // -------------------------------------------------------------------------

    /**
     * Build Params
     */
    private static function buildParam() {
        $saveParams   = self::$params;
        self::$params = [];
        foreach (array_merge($_GET, $_POST, $saveParams) as $key => $val) {
            self::setParam($key, $val);
        }

        self::$isBuildParams = TRUE;
    }

    // -------------------------------------------------------------------------

    /**
     * XSS Protection
     *
     * @param mixed $value
     * @param bool $protect
     * @return mixed
     */
    private static function protectParam($value, $protect) {

        if ($protect !== FALSE && is_string($value)) {
            return htmlspecialchars($value);
        }

        return $value;
    }

    // -------------------------------------------------------------------------

    /**
     * Set a parameter or an array of parameters
     *
     * @param string/array $name
     * @param mixed $value
     * @return bool : success or not
     */
    public static function setParam($name, $value = NULL) {

        // Set a param
        if (isset($value) && is_string($name)) {
            self::$params[$name] = $value;
            return TRUE;
        }
        // Set an array of param
        elseif ($name && is_array($name)) {
            $iInsert = 0;
            foreach ($name as $key => $val) {
                if (self::setParam($key, $val)) {
                    $iInsert++;
                }
            }
            return $iInsert == count($name);
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Test if a parameter exists
     *
     * @param string $name
     * @return bool
     */
    public static function hasParam($name) {

        if (!is_string($name)) {
            return FALSE;
        }

        if (!self::$isBuildParams) {
            self::buildParam();
        }

        return isset(self::$params[$name]);
    }

    // -------------------------------------------------------------------------

    /**
     * Get parameter
     *
     * @param mixed $name :
     *          - string : get a parameter
     *          - null   : get all parameters
     * @param mixed $default
     * @return mixed
     */
    public static function getParam($name = NULL, $default = NULL, $protect = TRUE) {

        $value = $default;

        // Get all params
        if (is_null($name)) {
            $value = self::getParams();
        }
        // Get a param
        elseif (self::hasParam($name)) {
            $value = self::protectParam(self::$params[$name], $protect);
        }

        return $value;
    }

    // -------------------------------------------------------------------------

    /**
     * Get all parameters
     *
     * @return array
     */
    public static function getParams() {

        if (!self::$isBuildParams) {
            self::buildParam();
        }

        $params = [];
        foreach (array_keys(self::$params) as $name) {
            $params[$name] = self::getParam($name);
        }

        return $params;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the path of the controller script
     *
     * @param string $file
     * @return boolean
     */
    public static function setFile($file) {
        self::$file = $file;
        return TRUE;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the path of the controller script
     *
     * @return string
     */
    public static function getFile() {
        return self::$file;
    }

    // -------------------------------------------------------------------------

    /**
     * Set an option
     *
     * @param string $name
     * @param mixed $value
     * @return boolean
     */
    public static function setOption($name, $value = NULL) {

        // Set an option
        if (isset($value) && is_string($name)) {
            self::$options[$name] = $value;
            return TRUE;
        }
        // Set an array of options
        elseif ($name && is_array($name)) {
            $iInsert = 0;
            foreach ($name as $key => $val) {
                if (is_string($key)) {
                    self::$options[$key] = $val;
                    $iInsert++;
                }
            }
            return $iInsert == count($name);
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Test if an option is set
     *
     * @param type $name
     * @return type
     */
    public static function hasOption($name) {
        return isset(self::$options[$name]);
    }

    // -------------------------------------------------------------------------

    /**
     * Get an option
     *
     * @param type $name
     * @return type
     */
    public static function getOption($name, $default = NULL) {

        if (self::hasOption($name)) {
            return self::$options[$name];
        }

        return $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the current user agent
     *
     * @return string|false
     */
    public static function getAgent() {
        if (self::$agent === NULL) {
            self::$agent = (!isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];
        }

        return self::$agent;
    }

    // -------------------------------------------------------------------------

    /**
     * Check if the current user is a robot
     *
     * @return bool
     */
    public static function isRobot() {
        return (bool) self::getRobot();
    }

    // -------------------------------------------------------------------------

    /**
     * Get the name of the robot
     *
     * @return string
     */
    public static function getRobot() {

        if (self::$robot === NULL) {

            if (self::$robots) {
                foreach (self::$robots as $key => $val) {
                    if (preg_match("|" . preg_quote($key) . "|i", self::getAgent())) {
                        self::$robot = $val;
                        return self::$robot;
                    }
                }
            }
            else {
                self::$robot = FALSE;
            }
        }

        return self::$robot;
    }

    // -------------------------------------------------------------------------
}

/* End of file */