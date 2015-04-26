<?php

namespace Orb\Http;

/**
 * Ip Helper
 *
 * @author sugatasei
 */
class Ip
{
    /**
     * Convert string ip to unsigned ip num
     *
     * @param  string $stringIp ip address
     * @return int
     */
    public static function toNum($stringIp)
    {
        if (!self::isValidIp($stringIp)) {
            return FALSE;
        }

        list($o1, $o2, $o3, $o4) = explode('.', $stringIp);
        return (int) ((16777216 * $o1) + (65536 * $o2) + (256 * $o3) + $o4);
    }

    // -------------------------------------------------------------------------

    /**
     * Convert unsigned int ip to string ip
     *
     * @param  int $numIp unsigned num ip address
     * @return string
     */
    public static function toString($numIp)
    {
        $o1 = (int) (($numIp / 16777216) % 256);
        $o2 = (int) (($numIp / 65536   ) % 256);
        $o3 = (int) (($numIp / 256     ) % 256);
        $o4 = (int) (($numIp           ) % 256);

        $stringIp = implode('.', [$o1, $o2, $o3, $o4]);
        return (self::isValidIp($stringIp)) ? $stringIp : FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns if an ip is valid
     *
     * @param string $ip
     * @return bool
     */
    public static function isValidIp($ip)
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
}

/* End of file */