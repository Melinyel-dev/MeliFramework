<?php

namespace System\Orm;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Orb\Helpers\Erreur;


/**
 * ERErreurManager Class
 *
 * @author anaeria
 */

class ERErreurManager implements IteratorAggregate, ArrayAccess, Countable {

    protected $erreurs = [];


    /**
     * Retourne le nombre d'erreurs
     *
     * @return int
     */

    public function count() {
        return count($this->erreurs);
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les erreur sous la forme d'un ArrayIterator
     *
     * @return object
     */

    public function getIterator() {
        return new \ArrayIterator($this->erreurs);
    }


    // -------------------------------------------------------------------------

    /**
     * Défini un offset pour les erreurs
     *
     * @param mixed offset
     * @param mixed value
     */

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->erreurs[] = $value;
        } else {
            $this->erreurs[$offset] = $value;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Vérifie l'existance d'une offset
     *
     * @param mixed offset
     * @return boolean
     */

    public function offsetExists($offset) {
        return isset($this->erreurs[$offset]);
    }


    // -------------------------------------------------------------------------

    /**
     * Détruit un offset
     *
     * @param mixed offset
     */

    public function offsetUnset($offset) {
        unset($this->erreurs[$offset]);
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le contenu d'un offset
     *
     * @param mixed offset
     * @return FALSE | array
     */

    public function offsetGet($offset) {
        foreach ($this->erreurs as $erreur) {
            if($erreur->attribute == $offset){
                return $erreur;
            }
        }
        return isset($this->erreurs[$offset]) ? $this->erreurs[$offset] : FALSE;
    }


    // -------------------------------------------------------------------------

    /**
     * Constructeur
     */

    public function __construct() {
        $argList = func_get_args();
        foreach ($argList as $arg) {
            $this->erreurs[] = $arg;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Test si des erreurs sont présentes
     *
     * @return boolean
     */

    public function any() {
        return count($this->erreurs) ? TRUE : FALSE;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une erreur
     *
     * @param array champs
     * @param string erreurTxt
     * @param string type
     */

    public function add($champs, $erreurTxt, $type = 'custom') {
        $this->erreurs[] = new Erreur(
            $champs,
            $type,
            $erreurTxt
        );
    }


    // -------------------------------------------------------------------------

    /**
     * Fussionne des erreurs
     *
     * @param array errors
     * @return object
     */

    public function merge($errors){
        $this->erreurs = array_merge($this->erreurs, $errors->erreurs);
        return $this;
    }
}

/* End of file */