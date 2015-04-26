<?php

namespace System\Core;

/**
 * User Agent Class
 *
 * @author sugatasei
 */

class UserAgent {

    /**
     * UserAgent
     *
     * @var \Orb\Http\UserAgent
     */
    protected static $instance = NULL;

    /**
     * Current user-agent
     *
     * @var string
     */
    protected $agent = NULL;

    /**
     * Flag for if the user-agent belongs to a browser
     *
     * @var bool
     */
    protected $is_browser = FALSE;

    /**
     * Flag for if the user-agent is a robot
     *
     * @var bool
     */
    protected $is_robot = FALSE;

    /**
     * Flag for if the user-agent is a mobile browser
     *
     * @var bool
     */
    protected $is_mobile = FALSE;

    /**
     * Languages accepted by the current user agent
     *
     * @var array
     */
    protected $languages = [];

    /**
     * Character sets accepted by the current user agent
     *
     * @var array
     */
    protected $charsets = [];

    /**
     * List of platforms to compare against current user agent
     *
     * @var array
     */
    protected $platforms = [];

    /**
     * List of browsers to compare against current user agent
     *
     * @var array
     */
    protected $browsers = [];

    /**
     * List of mobile browsers to compare against current user agent
     *
     * @var array
     */
    protected $mobiles = [];

    /**
     * List of robots to compare against current user agent
     *
     * @var array
     */
    protected $robots = [];

    /**
     * Current user-agent platform
     *
     * @var string
     */
    protected $platform = '';

    /**
     * Current user-agent browser
     *
     * @var string
     */
    protected $browser = '';

    /**
     * Current user-agent version
     *
     * @var string
     */
    protected $version = '';

    /**
     * Current user-agent mobile name
     *
     * @var string
     */
    protected $mobile = '';

    /**
     * Current user-agent robot name
     *
     * @var string
     */
    protected $robot = '';

    /**
     * HTTP Referer
     *
     * @var     mixed
     */
    protected $referer;

    // -------------------------------------------------------------------------

    /**
     * Constructor
     *
     * Sets the User Agent and runs the compilation routine
     *
     * @return  void
     */
    public function __construct() {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
        }

        if ($this->agent !== NULL && $this->_load_agent_file()) {
            $this->_compile_data();
        }
    }

    // ------------------------------------------------------------------------------

    /**
     * Return current instance
     *
     * @return \Orb\Http\UserAgent
     */
    public static function getInstance() {
        if (self::$instance === NULL) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    // -------------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @return  bool
     */
    protected function _load_agent_file() {

        require __DIR__ . '/user_agents.php';

        $return = FALSE;

        if (isset($platforms)) {
            $this->platforms = $platforms;
            unset($platforms);
            $return          = TRUE;
        }

        if (isset($browsers)) {
            $this->browsers = $browsers;
            unset($browsers);
            $return         = TRUE;
        }

        if (isset($mobiles)) {
            $this->mobiles = $mobiles;
            unset($mobiles);
            $return        = TRUE;
        }

        if (isset($robots)) {
            $this->robots = $robots;
            unset($robots);
            $return       = TRUE;
        }

        return $return;
    }

    // -------------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @return  bool
     */
    protected function _compile_data() {
        $this->_set_platform();

        foreach (array('_set_robot', '_set_browser', '_set_mobile') as $function) {
            if ($this->$function() === TRUE) {
                break;
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Set the Platform
     *
     * @return  bool
     */
    protected function _set_platform() {
        if (is_array($this->platforms) && count($this->platforms) > 0) {
            foreach ($this->platforms as $key => $val) {
                if (preg_match('|' . preg_quote($key) . '|i', $this->agent)) {
                    $this->platform = $val;
                    return TRUE;
                }
            }
        }

        $this->platform = 'Unknown Platform';
        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the Browser
     *
     * @return  bool
     */
    protected function _set_browser() {
        if (is_array($this->browsers) && count($this->browsers) > 0) {
            foreach ($this->browsers as $key => $val) {
                if (preg_match('|' . $key . '.*?([0-9\.]+)|i', $this->agent, $match)) {
                    $this->is_browser = TRUE;
                    $this->version    = $match[1];
                    $this->browser    = $val;
                    $this->_set_mobile();
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the Robot
     *
     * @return  bool
     */
    protected function _set_robot() {
        if (is_array($this->robots) && count($this->robots) > 0) {
            foreach ($this->robots as $key => $val) {
                if (preg_match('|' . preg_quote($key) . '|i', $this->agent)) {
                    $this->is_robot = TRUE;
                    $this->robot    = $val;
                    $this->_set_mobile();
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the Mobile Device
     *
     * @return  bool
     */
    protected function _set_mobile() {
        if (is_array($this->mobiles) && count($this->mobiles) > 0) {
            foreach ($this->mobiles as $key => $val) {
                if (FALSE !== (stripos($this->agent, $key))) {
                    $this->is_mobile = TRUE;
                    $this->mobile    = $val;
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the accepted languages
     *
     * @return  void
     */
    protected function _set_languages() {
        if ((count($this->languages) === 0) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->languages = explode(',', preg_replace('/(;\s?q=[0-9\.]+)|\s/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE']))));
        }

        if (count($this->languages) === 0) {
            $this->languages = array('Undefined');
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Set the accepted character sets
     *
     * @return  void
     */
    protected function _set_charsets() {
        if ((count($this->charsets) === 0) && !empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $this->charsets = explode(',', preg_replace('/(;\s?q=.+)|\s/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET']))));
        }

        if (count($this->charsets) === 0) {
            $this->charsets = array('Undefined');
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Is Browser
     *
     * @param   string  $key
     * @return  bool
     */
    public function isBrowser($key = NULL) {

        // No need to be specific, it's a browser
        if ($key === NULL) {
            return $this->is_browser;
        }

        // Check for a specific browser
        return (isset($this->browsers[$key]) && $this->browser === $this->browsers[$key]);
    }

    // -------------------------------------------------------------------------

    /**
     * Is Robot
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRobot($key = NULL) {

        // No need to be specific, it's a robot
        if ($key === NULL) {
            return $this->is_robot;
        }

        // Check for a specific robot
        return (isset($this->robots[$key]) && $this->robot === $this->robots[$key]);
    }

    // -------------------------------------------------------------------------

    /**
     * Is Mobile
     *
     * @param   string  $key
     * @return  bool
     */
    public function isMobile($key = NULL) {

        // No need to be specific, it's a mobile
        if ($key === NULL) {
            return $this->is_mobile;
        }

        // Check for a specific robot
        return (isset($this->mobiles[$key]) && $this->mobile === $this->mobiles[$key]);
    }

    // -------------------------------------------------------------------------

    /**
     * Agent String
     *
     * @return  string
     */
    public function agentString() {
        return $this->agent;
    }

    // -------------------------------------------------------------------------

    /**
     * Get Platform
     *
     * @return  string
     */
    public function platform() {
        return $this->platform;
    }

    // -------------------------------------------------------------------------

    /**
     * Get Browser Name
     *
     * @return  string
     */
    public function browser() {
        return $this->browser;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the Browser Version
     *
     * @return  string
     */
    public function version() {
        return $this->version;
    }

    // -------------------------------------------------------------------------

    /**
     * Get The Robot Name
     *
     * @return  string
     */
    public function robot() {
        return $this->robot;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the Mobile Device
     *
     * @return  string
     */
    public function mobile() {
        return $this->mobile;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the referrer
     *
     * @return  bool
     */
    public function referrer() {
        return empty($_SERVER['HTTP_REFERER']) ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    // -------------------------------------------------------------------------

    /**
     * Get the accepted languages
     *
     * @return  array
     */
    public function languages() {
        if (count($this->languages) === 0) {
            $this->_set_languages();
        }

        return $this->languages;
    }

    // -------------------------------------------------------------------------

    /**
     * Get the accepted Character Sets
     *
     * @return  array
     */
    public function charsets() {
        if (count($this->charsets) === 0) {
            $this->_set_charsets();
        }

        return $this->charsets;
    }

    // -------------------------------------------------------------------------

    /**
     * Test for a particular language
     *
     * @param   string  $lang
     * @return  bool
     */
    public function acceptLang($lang = 'en') {
        return in_array(strtolower($lang), $this->languages(), TRUE);
    }

    // -------------------------------------------------------------------------

    /**
     * Test for a particular character set
     *
     * @param   string  $charset
     * @return  bool
     */
    public function acceptCharset($charset = 'utf-8') {
        return in_array(strtolower($charset), $this->charsets(), TRUE);
    }

    // -------------------------------------------------------------------------

    /**
     * Parse a custom user-agent string
     *
     * @param   string  $string
     * @return  void
     */
    public function parse($string) {
        // Reset values
        $this->is_browser = FALSE;
        $this->is_robot   = FALSE;
        $this->is_mobile  = FALSE;
        $this->browser    = '';
        $this->version    = '';
        $this->mobile     = '';
        $this->robot      = '';

        // Set the new user-agent string and parse it, unless empty
        $this->agent = $string;

        if (!empty($string)) {
            $this->_compile_data();
        }
    }

    // -------------------------------------------------------------------------
}

/* End of file */
