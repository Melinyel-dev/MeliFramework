<?php

namespace System\Orm;

use MaxMind\Db\Reader;
use Orb\Helpers\Geolocation;
use Orb\Helpers\Text;

/**
 * ERGeolocation Class
 *
 * Ville location
 *
 * @author sugatasei
 */
class ERGeolocation {

    /**
     * ERGeolocation instance
     *
     * @var \Orb\EasyRecord\ERGeolocation
     */
    private static $instance = null;

    /**
     * MaxMind Reader Instance
     *
     * @var \MaxMind\Db\Reader
     */
    private $reader = null;

    // -------------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param string $file
     */
    public function __construct($file) {
        if ($file === null) {
            $file = '/usr/share/GeoIP/GeoIP2-City-Europe.mmdb';
        }
        $this->reader = new Reader($file);
    }

    // -------------------------------------------------------------------------

    /**
     * Singleton
     *
     * @param string $file
     * @return \Orb\EasyRecord\ERGeolocation
     */
    public static function getInstance($file = null) {
        if (self::$instance === null) {
            self::$instance = new static($file);
        }

        return self::$instance;
    }

    // -------------------------------------------------------------------------

    public function getVilleId($ip) {

        $res = $this->_getFromIp($ip);

        // Not found
        if (!$res) {
            return 0;
        }

        if (isset($res['city'])) {
            return $this->_getFromCache($res);
        }

        return $this->_getFromGeo($res);
    }

    // -------------------------------------------------------------------------

    /**
     * Get address from an IP using MaxMind DB
     *
     * @param string $ip
     * @return false|array
     */
    private function _getFromIp($ip) {
        try {
            $res = $this->reader->get($ip);

            // Not found
            if (!$res) {
                return false;
            }

            return $res;
        }
        // MaxMind has an error
        catch (\Exception $ex) {
            return false;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Get VilleID from cache
     *
     * @param array $res
     * @return int
     */
    private function _getFromCache(array $res) {
        $cache = ERCache::getInstance();

        $villeID = $cache->nsGet('Geolocation', $res['city']['geoname_id']);

        // Found
        if ($villeID) {
            return $villeID;
        }
        // Not found
        else {

            // Get from DB
            $villeID = $this->_getFromDB($res);

            // Add to cache
            if ($villeID) {
                $cache->nsSet('Geolocation', $res['city']['geoname_id'], $villeID);
            }

            return $villeID;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Get VilleID from DB
     *
     * @param array $res
     * @return int
     */
    private function _getFromDB(array $res) {
        $db    = ERDB::getInstance();
        $geoID = (int) $res['city']['geoname_id'];
        $geo   = $db->query("SELECT CLEF_VILLE FROM commun.GEOVILLE WHERE CLEF_GEO = {$geoID}");

        // Found
        if (($row = $geo->next())) {
            return $row['CLEF_VILLE'];
        }
        else {
            $villeID = $this->_getFromGeo($res);

            if ($villeID) {
                $db->query("INSERT INTO commun.GEOVILLE(CLEF_GEO,CLEF_VILLE) VALUES({$geoID}, {$villeID})");
            }

            return $villeID;
        }
    }

    // -------------------------------------------------------------------------

    private function _getFromGeo(array $res) {

        // No GPS coordinates
        if (!isset($res['location']['latitude']) || !isset($res['location']['longitude'])) {
            return 0;
        }

        // Search cities in a square centered by the GPS coordinates
        $bounds = Geolocation::boundingBox($res['location']['latitude'], $res['location']['longitude'], 5000);
        $db     = ERDB::getInstance();
        $geo    = $db->query("SELECT * FROM commun.VILLE WHERE LATITUDE BETWEEN {$bounds[0]} AND {$bounds[1]} AND LONGITUDE BETWEEN {$bounds[2]} AND {$bounds[3]}")->all();

        // No results
        if (!$geo) {
            return 0;
        }

        // Search by city name
        $name = isset($res['city']['names']['fr']) ? $res['city']['names']['fr'] : null;
        if ($name === null) {
            $name = isset($res['city']['names']['en']) ? $res['city']['names']['en'] : null;
        }

        if ($name !== null) {
            $name = self::_format($name);
            foreach ($geo as $v) {
                if ($name == self::_format($v['VILLE'])) {
                    return $v['CLEF_VILLE'];
                }
            }
        }

        // Search by postal code
        $postal  = isset($res['postal']['code']) ? $res['postal']['code'] : null;
        $postals = [];
        if ($postal !== null) {

            // Filter matching postal code
            foreach ($geo as $v) {
                if ($postal == $v['CODE_POSTAL']) {
                    $postals[] = $v;
                }
            }

            // Sort postal by distance
            if ($postals) {
                usort($postals, function($a, $b) use ($res) {
                    $da = abs(Geolocation::distance($res['location']['latitude'], $res['location']['longitude'], $a['LATITUDE'], $a['LONGITUDE']));
                    $db = abs(Geolocation::distance($res['location']['latitude'], $res['location']['longitude'], $b['LATITUDE'], $b['LONGITUDE']));
                    if ($da == $db) {
                        return 0;
                    }
                    return ($da < $db) ? -1 : 1;
                });

                return $postal[0];
            }
        }

        // Search by distance
        usort($geo, function($a, $b) use ($res) {
            $da = abs(Geolocation::distance($res['location']['latitude'], $res['location']['longitude'], $a['LATITUDE'], $a['LONGITUDE']));
            $db = abs(Geolocation::distance($res['location']['latitude'], $res['location']['longitude'], $b['LATITUDE'], $b['LONGITUDE']));
            if ($da == $db) {
                return 0;
            }
            return ($da < $db) ? -1 : 1;
        });

        return $geo[0];
    }

    // -------------------------------------------------------------------------

    /**
     * Format string for search
     *
     * @param string $str
     * @return string
     */
    private static function _format($str) {
        $str = strtolower(Text::convertAccents(trim($str)));
        return preg_replace('#[^a-z]#', '', $str);
    }

    // -------------------------------------------------------------------------
}

/* End of file */