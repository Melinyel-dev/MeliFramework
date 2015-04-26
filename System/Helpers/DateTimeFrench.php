<?php

namespace System\Helpers;

/**
 * DateTimeFrench class
 *
 * @author sugatasei
 */

class DateTimeFrench extends \DateTime {

    /**
     * Format un objet DateTime avec la langue francaise
     *
     * @param string format
     * @return string
     */

    public function format($format) {
        $english_days       = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $french_days        = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
        $english_months     = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        $french_months      = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
        $english_days_abr   = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
        $french_days_abr    = array('Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.', 'Dim.');
        $english_months_abr = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $french_months_abr  = array('Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.');

        if (strpos($format, 'M') !== FALSE) {
            return str_replace($english_months_abr, $french_months_abr, str_replace($english_days_abr, $french_days_abr, str_replace($english_months, $french_months, str_replace($english_days, $french_days, parent::format($format)))));
        }

        return str_replace($english_days_abr, $french_days_abr, str_replace($english_months, $french_months, str_replace($english_days, $french_days, parent::format($format))));
    }

}

/* End of file */