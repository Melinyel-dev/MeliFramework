<?php

/*
 * This work is licensed under
 * the Creative Commons Attribution 4.0 International License.
 * To view a copy of this license, visit
 * http://creativecommons.org/licenses/by/4.0/.
 */

namespace Orb\File;

/**
 * Config class
 * 
 * Get data from a configuration file
 *
 * @author Mathieu Froehly <mathieu.froehly@gmail.com>
 * @copyright Copyright (c) 2014, Mathieu Froehly <mathieu.froehly@gmail.com>
 */
class Config {
    
    /**
     * Cache
     * 
     * @var array 
     */
    private $data = [];

    /**
     * Directory
     * 
     * @var string
     */
    private $directory = '';

    // -------------------------------------------------------------------------

    /**
     * Class constructor
     * 
     * @param string $directory
     */
    public function __construct($directory = NULL) {
        $this->initialize($directory);
    }

    // -------------------------------------------------------------------------

    /**
     * Initialize preferences
     * 
     * @param string $directory
     * @return \CDB\Core\Config
     */
    public function initialize($directory) {
        if ($directory) {
            $this->directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns data from a config file
     * 
     * @param string $filename
     * @param string $varname
     * @return array
     * @throws \RuntimeException
     */
    public function get($filename, $varname = NULL) {
        
        // Cache
        if(isset($this->data[$filename])) {
            return $this->data[$filename];
        }
        
        // Set file
        $_filename = $filename . '.php';
        $_file     = $this->directory . $_filename;

        // Set default $varname
        if ($varname === NULL) {
            $varname = basename($filename);
        }

        // Get data
        if (is_file($_file)) {
            ${$varname} = [];
            require $_file;
            
            $this->data[$filename] = ${$varname};
            return $this->data[$filename];
        }
        // File not found
        else {
            throw new \RuntimeException('Config file not found : ' . $_file);
        }
    }

    // -------------------------------------------------------------------------
}

/* EOF */