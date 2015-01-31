<?php

namespace Orb\File;

/**
 * Description of Image
 *
 * @author mathieu
 */
class File {

    /**
     * File name
     * @var string
     */
    protected $path = NULL;

    // -------------------------------------------------------------------------
    
    /**
     * Class constructor
     * @param string $path
     */
    public function __construct($path) {
        $this->init($path);
    }

    // -------------------------------------------------------------------------
    
    /**
     * Init the path variable
     * @param string $path
     */
    public function init($path) {
        if (isset($path) && is_file($path)) {
            $this->path = $path;
        } else {
            $this->path = NULL;
        }
    }

    // -------------------------------------------------------------------------
    
    /**
     * Check if the file exists
     * @return boolean
     */
    public function exists() {
        return $this->path != NULL;
    }

    // -------------------------------------------------------------------------
    
    /**
     * Copy the file
     * @param string $to
     * @return boolean\Agendaweb\Core\Helpers\File
     */
    public function copy($to) {
        if ($this->exists() && copy($this->path, $to)) {
            return $this->create($to);
        }

        return FALSE;
    }
    
    // -------------------------------------------------------------------------

    /**
     * Move the file
     * @param string $to
     * @return boolean
     */
    public function move($to) {
        if ($this->exists() && rename($this->path, $to)) {
            $this->init($to);
            return TRUE;
        }

        return FALSE;
    }
    
    // -------------------------------------------------------------------------

    /**
     * Delete the file
     * @return boolean
     */
    public function delete() {
        if ($this->exists() && unlink($this->path)) {
            $this->init(NULL);
            return TRUE;
        }

        return FALSE;
    }
    
    // -------------------------------------------------------------------------

    protected function create($path) {
        $current = get_class($this);
        return new $current($path);
    }
    
    // -------------------------------------------------------------------------
}

/* End of file */