<?php

namespace Orb\File;

/**
 * Description of Image
 *
 * @author sugatasei
 */
class Image extends File {

    /**
     * Imagick Object of the current image
     * @var \Imagick
     */
    protected $imgMagick;

    /**
     * Max width
     * @var int
     */
    protected $maxWidth = 1024;

    /**
     * Max height
     * @var int
     */
    protected $maxHeight = 768;

    /**
     * Limit of pixels to be considered as a thumbnail
     * Details : 300 * 225 - 1 = 67499
     * @var int
     */
    protected $thumbSizeLimit = 67499;

    // -------------------------------------------------------------------------

    /**
     * Init the path variable
     * @param string $path
     */
    public function init($path) {
        parent::init($path);

        $this->imgMagick = new \Imagick($path);
    }

    // -------------------------------------------------------------------------

    /**
     * Get current image width
     * @return int
     */
    public function getWidth() {
        return $this->imgMagick->getimagewidth();
    }

    // -------------------------------------------------------------------------

    /**
     * Get current image height
     * @return int
     */
    public function getHeight() {
        return $this->imgMagick->getimageheight();
    }

    // -------------------------------------------------------------------------

    /**
     * Get Imagick object
     * @return \Imagick
     */
    public function getMagick() {
        return $this->imgMagick;
    }

    // -------------------------------------------------------------------------

    /**
     * Set max width and max height
     * @param int $width
     * @param int $height
     * @return boolean
     */
    public function setMaxSizes($width, $height) {

        $width  = (int) $width;
        $height = (int) $height;

        if ($width && $height) {
            $this->maxWidth  = $width;
            $this->maxHeight = $height;
            return TRUE;
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Check if current image is considered as a thumbnail
     * @return boolean
     */
    public function isThumbnail() {
        return $this->getWidth() * $this->getHeight() <= $this->thumbSizeLimit;
    }

    // -------------------------------------------------------------------------

    /**
     * Display current image
     */
    public function display() {
        $format = strtolower($this->imgMagick->getImageFormat());
        header("Content-type: image/{$format}");
        echo $this->imgMagick->getImageBlob();
    }

    // -------------------------------------------------------------------------

    /**
     * Save an image
     * @param string $to
     * @return \Agendaweb\Core\Helpers\Image
     */
    public function prepare($to = NULL) {

        // CMJN TO RGB
        if ($this->imgMagick->getImageColorspace() == \Imagick::COLORSPACE_CMYK) {
            $this->imgMagick->setImageColorspace(\Imagick::COLORSPACE_CMYK);
            $this->imgMagick->profileImage('*', NULL);
            $this->imgMagick->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $this->imgMagick->negateImage(FALSE, \Imagick::CHANNEL_ALL);
        }

        // Convert the output to jpeg
        $this->imgMagick->setimagecompression(\Imagick::COMPRESSION_JPEG);
        $this->imgMagick->setimagecompressionquality(100);
        $this->imgMagick->setimageformat('jpeg');

        // Auto rotate
        $this->autorotate();

        // Strips an image of all profiles and comments
        $this->imgMagick->stripimage();

        // Resize
        if ($this->imgMagick->getimagewidth() > $this->maxWidth || $this->imgMagick->getimageheight() > $this->maxHeight) {
            $this->resize($this->maxWidth, $this->maxHeight);
        }

        // Save the image
        return $this->save($this->forceExtension($to, 'jpg'));
    }

    private function forceExtension($to, $ext) {
        if ($to == NULL) {
            $to = $this->path;
        }

        $infos = \pathinfo($to);

        if (!isset($infos['extension']) || $infos['extension'] != $ext) {
            $to = $infos['dirname'] . '/' . $infos['filename'] . '.' . $ext;
        }

        return $to;
    }

    // -------------------------------------------------------------------------

    /**
     * Auto rotate an image based on EXIF properties
     * http://sylvana.net/jpegcrop/exif_orientation.html
     * @return boolean
     */
    public function autorotate() {
        try {
            // Check if Orientation is set in properties
            if ($this->imgMagick->getImageProperties("exif:Orientation", FALSE)) {

                // Change orientation
                switch ($this->imgMagick->getImageOrientation()) {
                    case \Imagick::ORIENTATION_TOPRIGHT:
                        $this->imgMagick->flopimage();
                        break;
                    case \Imagick::ORIENTATION_BOTTOMRIGHT:
                        $this->imgMagick->rotateimage("#000", 180);
                        break;
                    case \Imagick::ORIENTATION_BOTTOMLEFT:
                        $this->imgMagick->flipimage();
                        break;
                    case \Imagick::ORIENTATION_LEFTTOP:
                        $this->imgMagick->flopimage();
                        $this->imgMagick->rotateimage("#000", 270);
                        break;
                    case \Imagick::ORIENTATION_RIGHTTOP:
                        $this->imgMagick->rotateimage("#000", 90);
                        break;
                    case \Imagick::ORIENTATION_RIGHTBOTTOM:
                        $this->imgMagick->flopimage();
                        $this->imgMagick->rotateimage("#000", 90);
                        break;
                    case \Imagick::ORIENTATION_LEFTBOTTOM:
                        $this->imgMagick->rotateimage("#000", 270);
                        break;
                }

                // Update exif
                $this->imgMagick->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);

                return TRUE;
            }
        } catch (\ImagickException $ex) {
            return $ex->getMessage();
        }

        return FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Resize an image
     * @param int $width
     * @param int $height
     * @param boolean $autosave
     * @return boolean
     */
    public function resize($width, $height) {

        $width  = (int) $width;
        $height = (int) $height;

        // Check if given sizes are considered as thumbnail
        if ($width * $height <= $this->thumbSizeLimit) {
            $this->imgMagick->thumbnailImage($width, $height, TRUE);
        } else {
            $this->imgMagick->resizeimage($width, $height, \Imagick::FILTER_LANCZOS, 1, TRUE);
        }

        return TRUE;
    }

    // -------------------------------------------------------------------------

    /**
     *
     * @param \Agendaweb\Core\Helpers\Image $img
     * @param int $x
     * @param int $y
     */
    public function watermark(Image $img, $x, $y) {
        $imgMagick = $img->getMagick();
        $composite = \Imagick::COMPOSITE_DEFAULT;
        $this->imgMagick->compositeImage($imgMagick, $composite, $x, $y);
    }

    // -------------------------------------------------------------------------

    /**
     * Save an image
     * @param string $to
     * @return \Agendaweb\Core\Helpers\Image
     */
    public function save($to = NULL) {

        $this->imgMagick->writeImage($to);

        if ($to == NULL || $to == $this->path) {
            return $this;
        } else {
            return $this->create($to);
        }
    }

    // -------------------------------------------------------------------------
}

/* End of file */