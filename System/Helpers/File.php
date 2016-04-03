<?php

namespace System\Helpers;

/**
 * Description of Image
 *
 * @author sugatasei
 */
class File {

    /**
     * File name
     * @var string
     */
    protected $path = null;

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
            $this->path = null;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Check if the file exists
     * @return boolean
     */
    public function exists() {
        return $this->path != null;
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

        return false;
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
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------

    /**
     * Delete the file
     * @return boolean
     */
    public function delete() {
        if ($this->exists() && unlink($this->path)) {
            $this->init(null);
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------

    protected function create($path) {
        $current = get_class($this);
        return new $current($path);
    }

    // -------------------------------------------------------------------------
}

/* End of file */