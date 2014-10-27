<?php

namespace Melidev\System\Orm;


class ErreurManager implements \IteratorAggregate, \ArrayAccess, \Countable {

    protected $erreurs = [];

    #####################################
    # IteratorAggregate, ArrayAccess, Countable

    public function count() {
        return count($this->erreurs);
    }

    public function getIterator() {
        return new ArrayIterator($this->erreurs);
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->erreurs[] = $value;
        } else {
            $this->erreurs[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->erreurs[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->erreurs[$offset]);
    }

    public function offsetGet($offset) {
        foreach ($this->erreurs as $erreur) {
            if($erreur->attribute == $offset){
                return $erreur;
            }
        }
        return isset($this->erreurs[$offset]) ? $this->erreurs[$offset] : false;
    }

    public function __construct(){
        $argList = func_get_args();
        foreach ($argList as $arg) {
            $this->erreurs[] = $arg;
        }
    }

    public function any(){
        return count($this->erreurs) ? true : false;
    }

    public function add($champs, $erreurTxt, $type = 'custom'){
        $this->erreurs[] = new Erreur(
            $champs,
            $type,
            $erreurTxt
        );
        return true;
    }

    public function merge($errors){
        $this->erreurs = array_merge($this->erreurs, $errors->erreurs);
        return $this;
    }
}

/* End of file */