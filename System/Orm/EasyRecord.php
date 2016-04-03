<?php

namespace System\Orm;

use System\Helpers\Text;


/**
 * EasyRecord Class
 *
 * Classe principale des modèles
 *
 * @author anaeria
 */


class EasyRecord {

    // Constantes
    const READ_TO_ARRAY = 0;
    const READ_TO_STRING = 1;
    const READ_TO_LOWER = 2;

    // Core
    protected $data               = [];
    protected $dirty              = [];
    protected $expr               = [];
    public $errors                = null;
    protected $newRecord          = true;
    protected $readOnly           = false;
    protected static $database;
    protected static $table;
    protected static $identifiant;
    protected static $mapping;
    protected static $mappingFile = '.';
    protected static $mappingConfig;
    protected static $relations_foreign_keys;
    private $namespace            = null;
    private $classname            = null;
    private $potential_relations  = [];

    // Relationships
    protected $relations                  = null;
    protected static $hasMany             = [];
    protected static $hasOne              = [];
    protected static $belongsTo           = [];
    protected static $hasAndBelongsToMany = [];

    // Validations
    protected static $validations      = [];
    protected static $beforeValidation = [];
    protected static $afterValidation  = [];
    protected static $beforeSave       = [];
    protected static $afterSave        = [];
    protected static $beforeCreate     = [];
    protected static $afterCreate      = [];
    protected static $beforeUpdate     = [];
    protected static $afterUpdate      = [];
    protected static $beforeDelete     = [];
    protected static $afterDelete      = [];

    // Scopes
    protected static $scopes        = [];

    // Cache
    protected static $cacheActivation = 'off';
    protected static $cacheTime       = 0;
    protected static $cacheFields     = [];

    // Securité
    protected static $attrAccessible = [];
    protected static $attrAccessor   = [];



    /**
     * Constructeur
     *
     * @param boolean fromQuery
     */

    public function __construct($fromQuery = false) {
        $mapping = static::mappingInit();
        if (!$fromQuery)  {
            $this->init();
        }
        if (!static::getConfig('scopes')) {
            $this->scopes();
        }
        $this->errors = new ERErreurManager;
    }

    // -------------------------------------------------------------------------

    /**
     * Retourne la configuration du modèle
     *
     * @param string key
     * @return array | string
     */

    public static function &getConfig($key = null) {
        static $_config;

        $class = get_called_class();

        if (isset($_config) && isset($_config[$class])) {
            if ($key) {
                return $_config[$class][$key];
            }
            else {
                return $_config[$class];
            }
        }

        $config = [
            'database'            => static::$database,
            'table'               => static::$table,
            'identifiant'         => static::$identifiant,
            'mappingConfig'       => static::$mappingConfig,
            'mapping'             => static::$mapping,
            'hasMany'             => static::$hasMany,
            'hasOne'              => static::$hasOne,
            'belongsTo'           => static::$belongsTo,
            'hasAndBelongsToMany' => static::$hasAndBelongsToMany,
            'validations'         => static::$validations,
            'beforeValidation'    => static::$beforeValidation,
            'afterValidation'     => static::$afterValidation,
            'beforeSave'          => static::$beforeSave,
            'afterSave'           => static::$afterSave,
            'beforeCreate'        => static::$beforeCreate,
            'afterCreate'         => static::$afterCreate,
            'beforeUpdate'        => static::$beforeUpdate,
            'afterUpdate'         => static::$afterUpdate,
            'beforeDelete'        => static::$beforeDelete,
            'afterDelete'         => static::$afterDelete,
            'scopes'              => static::$scopes,
            'cacheActivation'     => static::$cacheActivation,
            'cacheTime'           => static::$cacheTime,
            'cacheFields'         => static::$cacheFields,
            'attrAccessible'      => static::$attrAccessible,
            'attrAccessor'        => static::$attrAccessor,
        ];

        $_config[$class] = & $config;
        if ($key) {
            return $_config[$class][$key];
        }
        else {
            return $_config[$class];
        }
    }



    ############################################################################
    #
    #  Accesseurs / Muttateurs
    #
    ############################################################################


    // -------------------------------------------------------------------------

    /**
     * Retourne le nom de la classe instanciée
     *
     * @return string
     */

    public function getClassname() {
        $this->_parseClass();
        return $this->classname;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le namespace de la classe instanciée
     *
     * @return string
     */

    public function getNamespace() {
        $this->_parseClass();
        return $this->namespace;
    }

    // -------------------------------------------------------------------------

    /**
     * Retourne le nom de la clé primaire
     *
     * @param int mode
     * @return string
     */

    public static function getIdentifier($mode = 2) {
        if($mode == self::READ_TO_ARRAY) {
            $keys = [];
            foreach (static::identifiant() as $id) {
                $keys[$id] = $id;
            }
            return $keys;
        } else {
            $id = implode('_', static::identifiant());
        }

        return $id;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne l'instance à une clé primaire valide
     *
     * @return boolean
     */

    public function hasIdentifier() {
        $valid = true;

        foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $pk) {
            if(!$pk) {
                $valid = false;
            }
        }

        return $valid;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la valeur de la clé primaire
     *
     * @param int
     * @return mixed
     */

    public function getIdentifierValue($mode = 2) {
        $ids = self::getIdentifier(self::READ_TO_ARRAY);

        $values = [];
        foreach ($ids as $key => $id) {
            $values[$id] = isset($this->data[$id]) ? $this->data[$id] : (isset($this->dirty[$id]) ? $this->dirty[$id] : false);
        }

        if($mode == self::READ_TO_ARRAY) {
            return $values;
        } else {
            return implode('_', $values);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la nom d'une clé étrangère d'une relation
     *
     * @param string
     * @param string
     * @return mixed
     */

    public function getForeignKey($relation, $primary_key) {
        if (!isset(self::$relations_foreign_keys[$this->getRelationName($relation)])) {
            $this->getRelationKeys($relation);
        }

        if (!isset(self::$relations_foreign_keys[$this->getRelationName($relation)][$primary_key])) {
            throw new ERException('EasyRecord::getForeignKey unable to access primary key (' . $this->getClassname() . '::' . $primary_key . ') for relation (' . $relation . ')', 1);
        }

        return self::$relations_foreign_keys[$this->getRelationName($relation)][$primary_key];
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les clés d'un relation
     *
     * @param string
     * @return array
     */

    public function getRelationKeys($relation) {
        if (!isset(self::$relations_foreign_keys[$this->getRelationName($relation)])) {
            self::$relations_foreign_keys[$this->getRelationName($relation)] = [];

            $operation = $this->getRelationData($relation);

            $relationData = self::{$operation}();
            if(isset($relationData[ucfirst($relation)])) {
                $relationData = $relationData[ucfirst($relation)];
            } else {
                $relationData = [];
            }

            $className = isset($relationData['class_name']) ? $relationData['class_name'] : $this->getNamespace() . ucfirst($relation);

            if(($operation == 'hasMany' || $operation == 'hasAndBelongsToMany') && $className[strlen($className)-1] == 's') {
                $className = substr($className,0,strlen($className)-1);
            }

            if(isset($relationData['inverse_of'])) {
                $dist = new $className;
                self::$relations_foreign_keys[$this->getRelationName($relation)] = array_flip($dist->getRelationKeys($relationData['inverse_of']));
                return self::$relations_foreign_keys[$this->getRelationName($relation)];
            }

            if($operation == 'hasMany' || $operation == 'hasOne' || $operation == 'hasAndBelongsToMany') {
                $source = $this->getNamespace() . $this->getClassname();
                $dest = $className;
            } elseif($operation == 'belongsTo') {
                $source = $className;
                $dest = $this->getNamespace() . $this->getClassname();
            }

            if(isset($relationData['foreign_key'])) {
                foreach ($source::getIdentifier(self::READ_TO_ARRAY) as $primary_key) {
                    if($operation == 'belongsTo') {
                        if(isset($relationData['foreign_key'][$primary_key])) {
                            self::$relations_foreign_keys[$this->getRelationName($relation)][$relationData['foreign_key'][$primary_key]] = $primary_key;
                        } else {
                            self::$relations_foreign_keys[$this->getRelationName($relation)][$relationData['foreign_key']] = $primary_key;
                        }
                    } else {
                        if(isset($relationData['foreign_key'][$primary_key])) {
                            self::$relations_foreign_keys[$this->getRelationName($relation)][$primary_key] = $relationData['foreign_key'][$primary_key];
                        } else {
                            self::$relations_foreign_keys[$this->getRelationName($relation)][$primary_key] = $relationData['foreign_key'];
                        }
                    }
                }
            } else {
                self::$relations_foreign_keys[$this->getRelationName($relation)] = array_combine(array_values($source::getIdentifier($source::READ_TO_ARRAY)), array_values($source::getIdentifier($dest::READ_TO_ARRAY)));
            }
        }

        return self::$relations_foreign_keys[$this->getRelationName($relation)];
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le type d'une relation
     *
     * @param string
     * @return string
     */

    public function getRelationData($relation) {
        $relation = ucfirst($relation);
        $rel = false;

        if (isset(self::belongsTo()[$relation]) || in_array($relation, self::belongsTo())) {
            $rel = 'belongsTo';
        }

        elseif (isset(self::hasMany()[$relation]) || in_array($relation, self::hasMany())) {
            $rel = 'hasMany';
        }

        elseif (isset(self::hasOne()[$relation]) || in_array($relation, self::hasOne())) {
            $rel = 'hasOne';
        }

        elseif (isset(self::hasAndBelongsToMany()[$relation]) || in_array($relation, self::hasAndBelongsToMany())) {
            $rel = 'hasAndBelongsToMany';
        }

        if($rel) {
            return $rel;
        }

        throw new ERException('EasyRecord::getRelationData unable to access relation (' . $relation . ')', 1);
    }


    public function getRelationClassName($relation) {

        $className = '';
        $infosArray = false;

        switch($this->getRelationData($relation)) {
            case 'belongsTo':
                if (array_key_exists($relation, self::belongsTo())) {
                    $infosArray = self::belongsTo()[$relation];
                }
                break;
            case 'hasOne':
                if (array_key_exists($relation, self::hasOne())) {
                    $infosArray = self::hasOne()[$relation];
                }
                break;
            case 'hasMany':
                if (array_key_exists($relation, self::hasMany())) {
                    $infosArray = self::hasMany()[$relation];
                }
                break;
            case 'hasAndBelongsToMany':
                if (array_key_exists($relation, self::hasAndBelongsToMany())) {
                    $infosArray = self::hasAndBelongsToMany()[$relation];
                }
                break;
            default:
                break;
        }

        if($infosArray && array_key_exists('class_name', $infosArray)) {
            $className = $this->getNamespace() . $infosArray['class_name'];
        } else {
            $className = $this->getNamespace() . $relation;
        }

        return $className;
    }



    // -------------------------------------------------------------------------

    /**
     * Retourne le nom d'une relation
     *
     * @param string
     * @return string
     */

    public function getRelationName($relation) {
        return $this->getNamespace() . $this->getClassname() . '_' . $relation;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les données du modèles, ainsi que celles non-sauvgardées
     *
     * @return array
     */

    public function getDataDirty() {
        return array_merge($this->data, $this->dirty);
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les données du modèles telle quelles sont en BDD
     *
     * @return array
     */

    public function getData() {
        return $this->data;
    }


    // -------------------------------------------------------------------------

    /**
     * Affecte les données du modèle
     *
     * @param array data
     */

    public function setData($data) {
        $this->data = $data;
        return true;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne l'état d'activation du cache
     *
     * @return string
     */

    public static function cacheActivation() {
        return static::getConfig('cacheActivation');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la durée de mise en cache des données en minutes
     *
     * @return int
     */

    public static function cacheTime() {
        return static::getConfig('cacheTime');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le liste des champs mis en cache
     *
     * @return array
     */

    public static function cacheFields() {
        return static::getConfig('cacheFields');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les relations m / n du modèle
     *
     * @return array
     */

    public static function hasAndBelongsToMany() {
        return static::getConfig('hasAndBelongsToMany');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les relations modèles / n du modèle
     *
     * @return array
     */

    public static function hasMany() {
        return static::getConfig('hasMany');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les relations 1 / 1 du modèle
     *
     * @return array
     */

    public static function hasOne() {
        return static::getConfig('hasOne');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les relations 1 / modèle du modèle
     *
     * @return array
     */

    public static function belongsTo() {
        return static::getConfig('belongsTo');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les scopes disponibles
     *
     * @return array
     */

    public static function getScopes() {
        if (!static::getConfig('scopes')) {
            $object = new static;
            $object->scopes();
        }
        return static::getConfig('scopes');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la base de données du modèle
     *
     * @return string
     */

    public static function database() {
        if (!static::getConfig('mapping')) {
            static::mappingInit();
        }
        return static::getConfig('database');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la table du modèle
     *
     * @return string
     */

    public static function table() {
        if (!static::getConfig('mapping')) {
            static::mappingInit();
        }
        return static::getConfig('table');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la clé primaire du modèle
     *
     * @return string
     */

    public static function identifiant() {
        if (!static::getConfig('mapping')) {
            static::mappingInit();
        }
        return static::getConfig('identifiant');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le mapping du modèle
     *
     * @return array
     */

    public static function mapping() {
        if (!static::getConfig('mapping')) {
            static::mappingInit();
        }
        return static::getConfig('mapping');
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne la configuration du mapping du modèle
     *
     * @return array
     */

    public static function mappingConfig() {
        return static::getConfig('mappingConfig');
    }


    // -------------------------------------------------------------------------

    /**
     * Récupère les données du modèle au format JSON
     *
     * @return string
     */

    public function toJSON() {
        return json_encode($this->data);
    }


    // -------------------------------------------------------------------------

    /**
     * Recupére les données du modèle en tableau
     *
     * @return array
     */

    public function toRow() {
        return $this->data;
    }



    ############################################################################
    #
    #  Méthodes magiques
    #
    ############################################################################


    /**
     * Liste les attibuts au serialiser
     *
     * @return array
     */

    public function __sleep() {
        return ['data', 'dirty', 'expr', 'errors', 'newRecord', 'readOnly', 'relations'];
    }


    // -------------------------------------------------------------------------

    /**
     * Gère les appels statique au modèle
     *
     * @param string name
     * @param array arguments
     * @return mixed
     */

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([new static, $name], $arguments);
    }



    // -------------------------------------------------------------------------

    /**
     * Gère la création dynamique de propriétés
     *
     * @param string name
     * @param array arguments
     * @return mixed
     */

    public function __call($name, $arguments) {
        if (preg_match('/([\w]*)Changed/', $name, $outputArray)) {
            return array_key_exists($outputArray[1], $this->dirty);
        }
        elseif (preg_match('/([\w]*)Was/', $name, $outputArray)) {
            if (array_key_exists($outputArray[1], $this->dirty)) {
                return array_key_exists($outputArray[1], $this->data) ? $this->data[$outputArray[1]] : false;
            }
            return false;
        } else {
            return call_user_func_array([$this->newQuery(), $name], $arguments);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Gère l'accès aux propriété du modèle ou a ses relations
     *
     * @param string key
     * @return mixed
     */

    public function __get($key) {
        $tmpData = $this->data;
        foreach ($this->dirty as $keyDirty => $value) {
            $tmpData[$keyDirty] = $value;
        }

        if (array_key_exists($key, $tmpData)) {
            if(isset(static::mapping()[$key]) && static::mapping()[$key]['type'] == 'sjson') {
                $tmpData[$key] = json_decode($tmpData[$key], true);
            }
            if ($this->hasGetMutator($key)) {
                $method = 'get' . Text::underscoreToCamel($key) . 'Attribute';
                return $this->{$method}($value);
            }
            return $tmpData[$key];

        } elseif (array_key_exists($key, $this->potential_relations)) {
            $className = $this->getRelationClassName($key);

            $object = $this->potential_relations[$key]->instanciateAs($className);

            return $object;
        }
        return $this->relations()->getRelations($key, $this);
    }


    // -------------------------------------------------------------------------

    /**
     * Gère l'attribution de données au modèle ou a ses relations
     *
     * @param string key
     * @param mixed value
     * @return mixed
     */

    public function __set($key, $value) {
        if ($key !== null) {
            $string = ucfirst($key);

            if (in_array($string, static::getConfig('belongsTo')) || array_key_exists($string, static::getConfig('belongsTo'))) {
                return ERRelations::setBelongsTo($string, $value, $this);
            }
            else {
                if (in_array($key, static::getConfig('attrAccessor'))) {
                    return $this->$key = $value;
                }

                if ($this->hasSetMutator($key)) {
                    $method = 'set' . Text::underscoreToCamel($key) . 'Attribute';
                    $this->setProperty($key, $this->{$method}($value), ($value === null));
                    return $this->$key;
                }

                if(static::mapping()[$key]['type'] == 'sjson') {
                    $value = json_encode($value);
                }
                $this->setProperty($key, $value, ($value === null));
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Gère la duplication du modèle
     */


    public function __clone() {
        $this->errors = clone $this->errors;
        if ($this->relations) {
            $this->relations = clone $this->relations;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Gère l'existance d'une propriété du modèle
     *
     * @param string name
     * @return boolean
     */

    public function __isset($name) {
        if (array_key_exists($name, $this->dirty) || array_key_exists($name, $this->data)) {
            return true;
        }
        return false;
    }


    // -------------------------------------------------------------------------

    /**
     * Gère l'affichage directe du modèle
     *
     * @return string
     */

    public function __toString() {
        return $this->getIdentifierValue(self::READ_TO_STRING) !== false ? strval($this->getIdentifierValue(self::READ_TO_STRING)) : '';
    }



    ############################################################################
    #
    #  Attributs et état du modèle
    #
    ############################################################################


    /**
     * Donne une valeur à une attribut
     *
     * @param string key
     * @param mixed value
     */

    public function setAttribute($key, $value) {

        if(strpos($key, '_') === 0) {
            $parts = explode('_', $key);
            array_shift($parts);
            $finalkey = array_pop($parts);

            $relation = false;

            foreach ($parts as $key => $rel) {
                if(!$relation) {
                    $relation = $this->getPotentialRelation($rel);
                } else {
                    $relation = $relation->getPotentialRelation($rel);
                }
            }

            $relation->setAttribute($finalkey, $value);
        } else {
            $this->data[$key] = $value;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Test si le modèle à changer par rapport à la BDD
     *
     * @return boolean
     */

    public function changed() {
        return count($this->dirty) ? true : false;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les changement par rapport à la BDD
     *
     * @return array
     */

    public function changes() {
        $ary = [];
        foreach ($this->dirty as $key => $value) {
            $ary[$key] = [array_key_exists($key, $this->data) ? $this->data[$key] : false, $value];
        }
        return $ary;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les attibuts affectés par les changments
     *
     * @return array
     */

    public function changedAttributes() {
        $ary = [];
        foreach ($this->dirty as $key => $value) {
            $ary[$key] = [array_key_exists($key, $this->data) ? $this->data[$key] : false];
        }
        return $ary;
    }


    // -------------------------------------------------------------------------

    /**
     * Efface un propriété du modèle
     *
     * @param string field
     * @return false | mixed
     */

    public function unsetProp($field) {
        $return = false;
        if (array_key_exists($field, $this->dirty)) {
            unset($this->dirty[$field]);
        }
        if (array_key_exists($field, $this->expr)) {
            unset($this->expr[$field]);
        }
        if (array_key_exists($field, $this->data)) {
            $return = $this->data[$field];
            unset($this->data[$field]);
        }
        return $return;
    }



    // -------------------------------------------------------------------------

    /**
     * Annule les changement effectué au modèle
     *
     * @param string field
     * @return boolean
     */

    public function revert($field) {
        if (array_key_exists($field, $this->dirty)) {
            unset($this->dirty[$field]);
            return true;
        }
        return false;
    }


    // -------------------------------------------------------------------------

    /**
     * Annule les changement effectué au modèle
     *
     * @return boolean
     */

    public function revertAll(){
        foreach ($this->dirty as $key => $value) {
            unset($this->dirty[$key]);
        }
        return true;
    }


    // -------------------------------------------------------------------------

    /**
     * Test la validité du modèle
     *
     * @return boolean
     */

    public function valid() {
        return count($this->errors) ? false : true;
    }


    // -------------------------------------------------------------------------

    /**
     * Efface l'entrée en cache du modèle
     */

    protected function resetCache() {
        ERCache::getInstance()->nsDelete('EasyRecordCache', get_class($this) . '_' . $this->getIdentifierValue(self::READ_TO_STRING) . self::cacheActivation() . self::cacheTime());
    }



    ############################################################################
    #
    #  Gestion des nouveaux modèles
    #
    ############################################################################


    /**
     * Force le modèle à être reconnu comme non nouveau
     *
     * @return object
     */

    public function notNew() {
        $this->newRecord = false;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Test si le modèle est nouveau
     *
     * @return boolean
     */

    public function isNew() {
        return $this->newRecord;
    }



    ############################################################################
    #
    #  Gestion de la lecture seule
    #
    ############################################################################


    /**
     * Définit le modèle en lecture seule
     *
     * @return object
     */

    public function setReadOnly() {
        $this->readOnly = true;
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Retourne l'état de lecture seule
     *
     * @return boolean
     */

    public function getReadOnly() {
        return $this->readOnly;
    }



    ############################################################################
    #
    #  Gestion des scopes
    #
    ############################################################################


    /**
     * Déclare un scope
     *
     * @param string name
     * @param callable function
     */

    public function scope($name, $function) {
        if (!is_callable($function)) {
            throw new ERException(get_class($this) . '::scope() expects paramter 2 to be a function, ' . gettype($function) . ' given');
        }
        $scopes        = & static::getConfig('scopes');
        $scopes[$name] = $function;
    }


    ############################################################################
    #
    #  Gestion des relations
    #
    ############################################################################


    /**
     * Efface les relations enregistrées
     *
     * @return object
     */

    public function flush() {
        $this->relations = null;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Initialise une relation potentielle
     *
     * @param string
     */

    public function preparePotentialRelation($name) {
        $this->potential_relations[$name] = new ERPotentialRelations();
    }


    // -------------------------------------------------------------------------

    /**
     * Définie une relation potentielle
     *
     * @param string
     * @param object
     */

    public function setPotentialRelation($name, $datas) {
        $this->potential_relations[$name] = $datas;
    }


    // -------------------------------------------------------------------------

    /**
     * Détruit une relation potentielle
     *
     * @param string
     */

    public function deletePotentialRelation($name) {
        if(isset($this->potential_relations[$name])) {
            unset($this->potential_relations[$name]);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne l'ensemble des relations potentielles
     */

    public function getPotentialRelations() {
        return $this->potential_relations;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne une relation potentielle
     *
     * @param string
     * @return object
     */

    public function getPotentialRelation($name) {
        if(isset($this->potential_relations[$name])) {
            return $this->potential_relations[$name];
        } else {
            throw new ERException('Potential relation ' . $name .' not found in model ' . $this->getClassname());
        }
    }


    ############################################################################
    #
    #  Méthodes de contruction des requêtes
    #
    ############################################################################



    /**
     * Initialise une nouvelle instance de requête
     */

    public static function query() {
        $class = get_called_class();
        return new ERQuery(new $class);
    }


    // -------------------------------------------------------------------------

    /**
     * Initialise une nouvelle instance du modèle avec des données
     *
     * @param array arguments
     * @param string from
     * @return object
     */

    public static function build($arguments = [], $from = null) {
        $object = new static;
        if ($from) {
            if(is_array($from['cle_etrangere'])) {
                foreach ($from['cle_etrangere'] as $key => $value) {
                    $object->{$value} = $from['referer'][$key];
                }
            } else {
                $object->{$from['cle_etrangere']} = $from['referer'];
            }
        }

        foreach ($arguments as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }


    // -------------------------------------------------------------------------

    /**
     * Initialise une nouvelle instance du modèle
     *
     * @param array params
     * @param boolean bypassAttrAccessible
     * @return object
     */

    public static function create(array $params = [], $bypassAttrAccessible = false) {
        $nomClasse            = get_called_class();
        $nouvelObjet          = new $nomClasse();
        $massAssignmentErrors = [];

        foreach ($params as $key => $value) {
            if ($bypassAttrAccessible || count($nomClasse::getConfig('attrAccessible')) == 0 || in_array($key, $nomClasse::getConfig('attrAccessible'))) {
                $nouvelObjet->$key      = $value;
            }
            else {
                $massAssignmentErrors[] = $key;
            }
        }

        if ($massAssignmentErrors) {
            throw new ERException('can\'t mass-assign protected attributes: ' . implode(', ', $massAssignmentErrors), 2);
        }

        $nouvelObjet->save();
        return $nouvelObjet;
    }


    // -------------------------------------------------------------------------

    /**
     * Met à jour un groupe
     *
     * @param array ids
     * @return boolean
     */

    public function groupUpdate($ids = []) {
        if (!$ids || !is_array($ids)) {
            return false;
        }

        extract($this->buildUpdateGroup($ids));
        ERTools::execute($sqlQuery, $bindParam);

        $this->dirty = [];
        $this->expr  = [];

        return true;
    }


    // -------------------------------------------------------------------------

    /**
     * Sauvgarde le modèle en base de données
     *
     * @param boolean force
     * @return boolean
     */

    public function save($force = false) {
        $sqlQuery  = null;
        $bindParam = null;

        if ($this->readOnly) {
            throw new ERException('readOnly objects can not be saved', 3);
        }

        $config = & static::getConfig();

        if ($config['beforeValidation']) {
            foreach ($config['beforeValidation'] as $beforeValidation) {
                if (call_user_func(array($this, $beforeValidation)) === false) {
                    return false;
                }
            }
        }

        if (!$force && $this->validation()) {
            return false;
        }

        if ($config['afterValidation']) {
            foreach ($config['afterValidation'] as $afterValidation) {
                call_user_func(array($this, $afterValidation));
            }
        }

        if ($config['beforeSave']) {
            foreach ($config['beforeSave'] as $beforeSave) {
                if (call_user_func(array($this, $beforeSave)) === false) {
                    return false;
                }
            }
        }

        $values = array_values(array_diff_key($this->dirty, $this->expr));

        if (!$this->isNew()) {
            $newObject = false;

            if ($config['beforeUpdate']) {
                foreach ($config['beforeUpdate'] as $beforeUpdate) {
                    if (call_user_func(array($this, $beforeUpdate)) === false) {
                        return false;
                    }
                }
            }

            $this->relations()->updateRelations($this);

            if (empty($values) && empty($this->expr)) {
                $this->errors = new ERErreurManager;
                return true;
            }
            extract($this->buildUpdate());
        }
        else {
            $newObject = true;
            if ($config['beforeCreate']) {
                foreach ($config['beforeCreate'] as $beforeCreate) {
                    if (call_user_func(array($this, $beforeCreate)) === false) {
                        return false;
                    }
                }
            }
            extract($this->buildInsert());
        }

        ERTools::execute($sqlQuery, $bindParam);

        if ($this->isNew()) {
            if (count(self::getIdentifier(self::READ_TO_ARRAY)) == 1) {
                $lastId = ERDB::getInstance()->lastId();

                if ($lastId) {
                    $this->data[self::getIdentifier(self::READ_TO_LOWER)] = $lastId;
                }
            }

            $this->newRecord = false;
            $this->relations()->createRelations($this);
        }

        if ($this->expr) {
            $objectValues = self::assocArray()->select(array_keys($this->expr))->noCache()->find($this->getIdentifierValue(self::READ_TO_ARRAY));

            foreach ($objectValues as $key => $value) {
                $this->data[$key] = $value;
                unset($this->dirty[$key]);
            }
            unset($objectValues);
        }

        if ($newObject) {
            if ($config['afterCreate']) {
                foreach ($config['afterCreate'] as $afterCreate) {
                    call_user_func(array($this, $afterCreate));
                }
            }
        }
        else {
            if ($config['afterUpdate']) {
                foreach ($config['afterUpdate'] as $afterUpdate) {
                    call_user_func(array($this, $afterUpdate));
                }
            }
        }

        if ($config['afterSave']) {
            foreach ($config['afterSave'] as $afterSave) {
                call_user_func(array($this, $afterSave));
            }
        }

        foreach ($this->dirty as $key => $value) {
            $this->data[$key] = $value;
        }

        $this->dirty  = [];
        $this->expr   = [];
        $this->errors = new ERErreurManager;

        $className = get_class($this);

        if (self::cacheActivation() == 'full') {
            ERCache::getInstance()->nsDelete('EasyRecordCache', $className . '_' . self::getIdentifierValue(self::READ_TO_STRING) . self::cacheActivation() . self::cacheTime());
        }

        return true;
    }


    // -------------------------------------------------------------------------

    /**
     * Met à jour les attributs accessible du modèle
     *
     * @param array attributes
     * @param boolean bypassAttrAccessible
     * @param boolean save
     * @return int
     */

    public function updateAttributes($attributes, $bypassAttrAccessible = false, $save = true) {
        if (!isset($GLOBALS['update_attributes_object_hash'])) {
            $GLOBALS['update_attributes_object_hash'] = spl_object_hash($this);
        }

        if (!isset($GLOBALS['update_attributes_objects'])) {
            $GLOBALS['update_attributes_objects'] = [];
        }

        if (!isset($GLOBALS['update_attributes_errors'])) {
            $GLOBALS['update_attributes_errors'] = [];
        }

        if (!isset($GLOBALS['update_attributes_new'])) {
            $GLOBALS['update_attributes_new'] = $this->isNew();
        }

        if (!isset($GLOBALS['update_attributes_mass_assignment_errors'])) {
            $GLOBALS['update_attributes_mass_assignment_errors'] = [];
        }

        $hasOnes = [];
        if (!is_array($attributes)) {
            throw new ERException(get_class($this) . '::updateAttributes() expects parameter 1 to be array, ' . gettype($attributes) . ' given');
        }

        $config  = & static::getConfig();

        foreach ($attributes as $key => $value) {
            if ($bypassAttrAccessible || count($config['attrAccessible']) == 0 || in_array($key, $config['attrAccessible'])) {
                $keyU = ucfirst($key);

                if (in_array($keyU, $config['hasOne']) || array_key_exists($keyU, $config['hasOne'])) {
                    $hasOnes[$key] = $value;
                }
                elseif ($this->$key !== $value || $value === null || in_array($key, $config['attrAccessor'])) {
                    $this->$key    = $value;
                }
            } else {
                $GLOBALS['update_attributes_mass_assignment_errors'][] = $key;
                $save = false;
            }
        }

        if ($save) {
            if ($this->isNew()) {
                if ($this->save()) {
                    $GLOBALS['update_attributes_objects'][] = $this;
                    $save                                   = true;
                }
                else {
                    $GLOBALS['update_attributes_errors'][] = $this;
                    $save                                  = false;
                }
            }
            else {
                $GLOBALS['update_attributes_objects'][] = $this;
                $save                                   = true;
            }
        }
        else {
            $GLOBALS['update_attributes_errors'][] = $this;
            //$save = false;
        }

        foreach ($hasOnes as $key => $value) {
            $method = $key;
            $object = $this->$method;
            $object->updateAttributes($value, $bypassAttrAccessible, $save);
        }

        if (spl_object_hash($this) == $GLOBALS['update_attributes_object_hash']) {
            if ($GLOBALS['update_attributes_new']) {
                if (count($GLOBALS['update_attributes_errors'])) {
                    foreach ($GLOBALS['update_attributes_objects'] as $object) {
                        $object->delete();
                    }
                }
            }
            else {
                if (count($GLOBALS['update_attributes_errors']) && !$this->isNew()) {
                    $GLOBALS['update_attributes_objects'] = [];
                }

                foreach ($GLOBALS['update_attributes_objects'] as $object) {
                    $object->save();
                }
            }

            unset($GLOBALS['update_attributes_object_hash']);
            unset($GLOBALS['update_attributes_objects']);
            unset($GLOBALS['update_attributes_errors']);
            unset($GLOBALS['update_attributes_new']);

            if ($GLOBALS['update_attributes_mass_assignment_errors']) {
                throw new ERException('can\'t mass-assign protected attributes: ' . implode(', ', $GLOBALS['update_attributes_mass_assignment_errors']), 2);
            }

            unset($GLOBALS['update_attributes_mass_assignment_errors']);
        }

        return !count($this->getAndConstructErrors());
    }


    // -------------------------------------------------------------------------

    /**
     * Supprime une instance
     *
     * @return boolean
     */

    public function delete() {
        if ($this->hasIdentifier()) {
            $config = & static::getConfig();

            if ($config['beforeDelete']) {
                foreach ($config['beforeDelete'] as $beforeDelete) {
                    if (call_user_func(array($this, $beforeDelete)) === false) {
                        return false;
                    }
                }
            }

            if ($config['hasMany']) {
                $this->deleteHasMany();
            }

            if ($config['hasOne']) {
                $this->deleteHasOne();
            }

            if ($config['hasAndBelongsToMany']) {
                $this->deleteHasAndBelongsToMany();
            }

            $database = static::database();
            $table    = static::table();

            $query = 'DELETE FROM ' . ERTools::quoteIdentifiant($database) . '.' . ERTools::quoteIdentifiant($table) . ' WHERE ';

            $bindParam = new ERBindParam;
            $condition = [];
            foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
                $condition[] = ERTools::quoteIdentifiant($col) . ' = ?';
                $bindParam->add(static::mapping()[$col]['type'][0], $value);
            }

            $query .= implode(' AND ', $condition);

            ERTools::execute($query, $bindParam);

            if ($config['afterDelete']) {
                foreach ($config['afterDelete'] as $afterDelete) {
                    call_user_func(array($this, $afterDelete));
                }
            }
            return true;
        }
        return true;
    }


    ############################################################################
    #
    #  Initialisation des accesseurs
    #
    ############################################################################


    /**
     * Initialise les relations du modèle
     *
     * @return object
     */

    public function relations() {
        if (!$this->relations) {
            $this->relations = new ERRelations;
        }
        return $this->relations;
    }


    ############################################################################
    #
    #  Query builder
    #
    ############################################################################


    /**
     * Contruction d'une requête d'update
     *
     * @return array
     */

    private function buildUpdate() {
        $bindParam = new ERBindParam();
        $requete   = [];
        $requete[] = 'UPDATE ' . ERTools::quoteIdentifiant(static::database()) . '.' . ERTools::quoteIdentifiant(static::table()) . ' SET';

        $listeChamps = [];
        foreach ($this->dirty as $field => $value) {
            if (!array_key_exists($field, $this->expr)) {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ?';
                $type_colonne  = 's';

                if (array_key_exists($field, static::mapping())) {
                    $type_colonne  = static::mapping()[$field]['type'][0];
                }

                $bindParam->add($type_colonne, $value);
            } else {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ' . ($value === null ? 'null' : $value);
            }
        }

        $requete[] = implode(', ', $listeChamps);
        $requete[] = 'WHERE';

        $condition = [];

        foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
            $condition[] = ERTools::quoteIdentifiant($col) . ' = ?';
            $bindParam->add(static::mapping()[$col]['type'][0], $value);
        }

        $requete[] = implode(' AND ', $condition);

        return ['sqlQuery' => implode(' ', $requete), 'bindParam' => $bindParam];
    }


    // -------------------------------------------------------------------------

    /**
     * Construction d'une requête d'update de group
     *
     * @param array ids
     * @return array
     */

    private function buildUpdateGroup($ids) {
        $bindParam = new ERBindParam();
        $requete   = [];
        $requete[] = 'UPDATE ' . ERTools::quoteIdentifiant(static::database()) . '.' . ERTools::quoteIdentifiant(static::table()) . ' SET';

        $listeChamps = [];

        foreach ($this->dirty as $field => $value) {
            if (!array_key_exists($field, $this->expr)) {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ?';
                $type_colonne  = 's';

                if (array_key_exists($field, static::mapping())) {
                    $type_colonne  = static::mapping()[$field]['type'][0];
                }
                $bindParam->add($type_colonne, $value);
            } else {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ' . ($value === '' ? 'null' : $value);
            }
        }

        $requete[] = implode(', ', $listeChamps);
        $requete[] = 'WHERE';

        $pk = static::getIdentifier(self::READ_TO_ARRAY);
        if(count($pk) == 1) {
            $requete[] = '(' . implode(', ', $pk) . ')';
            $requete[] = 'IN (' . implode(', ', $ids) . ')';
        } else {
            $requete[] =  ERTools::quoteIdentifiant($pk[0]);

            foreach($ids as $key => $value) {
                $ids[$key] = '(' . implode(', ', $value). ')';
            }

            $requete[] = 'IN (' . implode(', ', $ids) . ')';
        }

        return ['sqlQuery' => implode(' ', $requete), 'bindParam' => $bindParam];
    }


    // -------------------------------------------------------------------------

    /**
     * Construction d'une requête d'insert
     *
     * @return array
     */

    private function buildInsert() {
        $bindParam   = new ERBindParam();
        $requete[]   = 'INSERT INTO';
        $requete[]   = ERTools::quoteIdentifiant(static::database()) . '.' . ERTools::quoteIdentifiant(static::table());
        $listeChamps = array_map('Orb\EasyRecord\ERTools::quoteIdentifiant', array_keys($this->dirty));
        $requete[]   = '(' . implode(', ', $listeChamps) . ')';
        $requete[]   = 'VALUES';
        $requete[]   = '(';

        $first = true;

        foreach ($this->dirty as $field => $value) {
            $requeteStr = null;

            if (!$first) {
                $requeteStr .= ',';
            }

            if (isset($this->expr[$field])) {
                $requeteStr .= $value === null ? 'null' : $value;
            }
            else {
                $requeteStr .= '?';
                $type_colonne = 's';

                if (array_key_exists($field, static::mapping())) {
                    $type_colonne = static::mapping()[$field]['type'][0];
                }

                $bindParam->add($type_colonne, $value);
            }

            $requete[] = $requeteStr;
            $first     = false;
        }
        $requete[] = ')';
        return ['sqlQuery' => implode(' ', $requete), 'bindParam' => $bindParam];
    }


    // -------------------------------------------------------------------------

    /**
     * Suppression des relations 1 / n
     *
     * @param string relation
     * @return boolean
     */

    public function deleteHasMany($relation = null) {
        $hasManies = static::getConfig('hasMany');

        if ($relation) {
            $hasManies = array_intersect_key($hasManies, [$relation => true]);
        }

        foreach ($hasManies as $key => $hasMany) {
            if (is_array($hasMany) && array_key_exists('dependent', $hasMany) && !array_key_exists('through', $hasMany)) {
                $objetName = 'Agendaweb\App\Models\\' . $key;

                if ($objetName[strlen($objetName) - 1] == 's') {
                    $objetName = substr($objetName, 0, strlen($objetName) - 1);
                }

                if (array_key_exists('class_name', $hasMany)) {
                    $objetName = 'Agendaweb\App\Models\\' . $hasMany['class_name'];
                }

                if (array_key_exists('inverse_of', $hasMany)) {
                    $infosArray = $objetName::getConfig('belongsTo')[$hasMany['inverse_of']];
                }
                else {
                    $infosArray = $hasMany;
                }

                if (array_key_exists('foreign_key', $infosArray)) {
                    $cleEtrangere = $infosArray['foreign_key'];
                }

                if (!isset($cleEtrangere)) {
                    $cleEtrangere = self::getIdentifier(self::READ_TO_ARRAY);
                }

                $objets = $objetName::query();

                if(is_array($cleEtrangere)) {
                    foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
                        $objets->where($col, $value);
                    }
                } else {
                    $objets->where($cleEtrangere, $this->getIdentifierValue(self::READ_TO_STRING));
                }

                if ($hasMany['dependent'] == 'destroy') {
                    foreach ($objets as $objetToDelete) {
                        $objetToDelete->delete();
                    }
                    return true;
                }
                elseif ($hasMany['dependent'] == 'delete_all') {
                    $request = 'DELETE FROM ' . $objetName::database() . '.' . $objetName::table() . ' WHERE ';

                    $condition = [];

                    $bindParam = new ERBindParam();
                    foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
                        $condition[] = ERTools::quoteIdentifiant($col) . ' = ?';
                        $bindParam->add(static::mapping()[$col]['type'][0], $value);
                    }

                    $request .= implode(' AND ', $condition);

                    ERDB::getInstance()->query($request, $bindParam);

                    return true;
                }
                elseif ($hasMany['dependent'] == 'nullify') {
                    foreach ($objets as $objetToDelete) {
                        foreach ($this->getIdentifier(self::READ_TO_ARRAY) as $pk) {
                            $objetToDelete->$pk = null;
                        }
                        $objetToDelete->save();
                    }
                    return true;
                }
            }
        }
        return false;
    }


    // -------------------------------------------------------------------------

    /**
     * Suppresion d'une relation 1 / 1
     */

    private function deleteHasOne() {
        foreach (static::getConfig('hasOne') as $key => $hasOne) {
            $key = 'Agendaweb\App\Models\\' . $key;

            if (is_array($hasOne) && array_key_exists('dependent', $hasOne)) {
                if (array_key_exists('foreign_key', $hasOne)) {
                    $cleEtrangere = $hasOne['foreign_key'];
                }

                if (array_key_exists('class_name', $hasOne)) {
                    $key = 'Agendaweb\App\Models\\' . $hasOne['class_name'];
                }

                if (!isset($cleEtrangere) && array_key_exists('inverse_of', $hasOne)) {
                    $cleEtrangere = $key::getConfig('belongsTo')[$hasOne['inverse_of']]['foreign_key'];
                }

                if (!isset($cleEtrangere)) {
                    $cleEtrangere = self::getIdentifier(self::READ_TO_ARRAY);
                }

                $objets = $objetName::query();

                if(is_array($cleEtrangere)) {
                    foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
                        $objets->where($col, $value);
                    }
                } else {
                    $objets->where($cleEtrangere, $this->getIdentifierValue(self::READ_TO_STRING));
                }

                if ($hasOne['dependent'] == 'destroy') {
                    foreach ($objets as $objetToDelete) {
                        $objetToDelete->delete();
                    }
                }
                elseif ($hasOne['dependent'] == 'delete_all') {
                    $request = 'DELETE FROM ' . $objetName::database() . '.' . $objetName::table() . ' WHERE ';

                    $condition = [];

                    $bindParam = new ERBindParam();
                    foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
                        $condition[] = ERTools::quoteIdentifiant($col) . ' = ?';
                        $bindParam->add(static::mapping()[$col]['type'][0], $value);
                    }

                    $request .= implode(' AND ', $condition);

                    ERDB::getInstance()->query($request, $bindParam);

                    return true;
                }
                elseif ($hasOne['dependent'] == 'nullify') {
                    foreach ($objets as $objetToDelete) {
                        foreach ($this->getIdentifier(self::READ_TO_ARRAY) as $pk) {
                            $objetToDelete->$pk = null;
                        }
                        $objetToDelete->save();
                    }
                }
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Supression d'une relation m / n
     */

    private function deleteHasAndBelongsToMany() {
        $database = static::database();
        $table    = static::table();
        foreach (static::getConfig('hasAndBelongsToMany') as $key => $hasAndBelongsToMany) {
            if (is_array($hasAndBelongsToMany)) {
                $objetName = 'Agendaweb\App\Models\\' . $key;

                if ($objetName[strlen($objetName) - 1] == 's') {
                    $objetName = substr($objetName, 0, strlen($objetName) - 1);
                }

                if (array_key_exists('class_name', $hasAndBelongsToMany)) {
                    $objetName = 'Agendaweb\App\Models\\' . $hasAndBelongsToMany['class_name'];
                }

                if (array_key_exists('inverse_of', $hasAndBelongsToMany)) {
                    $infosArray = $objetName::getConfig('hasAndBelongsToMany')[$hasAndBelongsToMany['inverse_of']];
                }
                else {
                    $infosArray = $hasAndBelongsToMany;
                }

                if (array_key_exists('foreign_key', $infosArray)) {
                    $concatCleEtrangere = $infosArray['foreign_key'];
                }

                if (array_key_exists('table', $infosArray)) {
                    $concatTable        = $infosArray['table'];
                }

                if (array_key_exists('database', $infosArray)) {
                    $database           = $infosArray['database'];
                }
            } else {
                $objetName = 'Agendaweb\App\Models\\' . $hasAndBelongsToMany;

                if ($objetName[strlen($objetName) - 1] == 's') {
                    $objetName = substr($objetName, 0, strlen($objetName) - 1);
                }
            }

            if (!isset($concatCleEtrangere)) {
                $concatCleEtrangere = self::getIdentifier(self::READ_TO_ARRAY);
            }

            if (!isset($concatTable)) {
                $habtmTable  = $objetName::table();

                if ($habtmTable < $table) {
                    $concatTable = $habtmTable . '_' . $table;
                }
                else {
                    $concatTable = $table . '_' . $habtmTable;
                }
            }

            $request = 'DELETE FROM ' . $database . '.' . $concatTable . ' WHERE ';
            $condition = [];

            $bindParam = new ERBindParam();
            foreach ($this->getIdentifierValue(self::READ_TO_ARRAY) as $col => $value) {
                $condition[] = ERTools::quoteIdentifiant($col) . ' = ?';
                $bindParam->add(static::mapping()[$col]['type'][0], $value);
            }

            $request .= implode(' AND ', $condition);

            ERDB::getInstance()->query($request, $bindParam);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instancie une nouvelle requête à la base de données
     *
     * @return object
     */

    protected function newQuery() {
        return new ERQuery($this);
    }


    // -------------------------------------------------------------------------

    /**
     * Test l'existance d'un mutateur sur l'affectation d'une propriété
     *
     * @param string key
     */

    protected function hasSetMutator($key) {
        return method_exists($this, 'set' . Text::underscoreToCamel($key) . 'Attribute');
    }


    // -------------------------------------------------------------------------

    /**
     * Test l'existance d'un mutateur sur la lecture d'une propriété
     *
     * @param string key
     */

    protected function hasGetMutator($key) {
        return method_exists($this, 'get' . Text::underscoreToCamel($key) . 'Attribute');
    }


    // -------------------------------------------------------------------------

    /**
     * Initialisation du modèle
     */

    protected function init() {
    }


    // -------------------------------------------------------------------------

    /**
     * Validations du modèle
     */

    protected function validations() {
    }


    // -------------------------------------------------------------------------

    /**
     * Scopes du modèle
     */

    protected function scopes() {
    }

    // -------------------------------------------------------------------------

    /**
     * Initialise le mapping du modèle
     *
     * @return false | array
     */


    protected static function mappingInit() {
        $className = get_called_class();
        $config    = & static::getConfig();

        if (!$config['mapping'] && self::mappingConfig()) {
            $config['database']    = (self::mappingConfig()['database'] == 'default' ? $GLOBALS['databaseCfg']['database'] : self::mappingConfig()['database']);
            $config['table']       = self::mappingConfig()['table'];
            $config['identifiant'] = is_array(self::mappingConfig()['identifiant']) ? self::mappingConfig()['identifiant'] : [self::mappingConfig()['identifiant']];
            $config['mapping']     = self::mappingConfig()['fields'];
            return self::mappingConfig();
        }
        return false;
    }


    // -------------------------------------------------------------------------

    /**
     * Valide la propriété d'un modèle suivant une règle
     *
     * @param string field
     * @param string regle
     * @param string msg
     * @param array options
     */

    protected function validates($field, $regle, $msg = null, $options = []) {
        $configValidation = & static::getConfig('validations');

        if (!array_key_exists($field, $configValidation)) {
            $configValidation[$field] = [];
        }

        if (!array_key_exists('label', $options)) {
            $options['label'] = $field;
        }

        if (!array_key_exists('required', $options)) {
            $options['required'] = false;
        }

        $validation                 = ['regle' => $regle, 'msg' => $msg, 'label' => $options['label'], 'required' => $options['required']];
        $configValidation[$field][] = $validation;
    }


    // -------------------------------------------------------------------------

    /**
     * Affecte une valeur à une propriété
     *
     * @param string key
     * @param mixed value
     * @param string $expr
     */

    private function setProperty($key, $value = null, $expr = false) {
        if (!is_array($value)) {
            if (!array_key_exists($key, static::mapping())) {
                throw new ERException('Try to assign to a non defined attribute or accessor (' . $key . ')');
            }

            if (static::mapping()[$key]['type'] == 'integer' || static::mapping()[$key]['type'] == 'decimal') {
                if (!is_numeric($value) && $value !== null) {
                    throw new ERException('Try to assign a non-numeric value (' . $value . ') to a numeric field (' . $key . ')');
                }
                else {
                    if (!is_null($value)) {
                        $value = $value + 0;
                    }
                }
            }
            elseif (static::mapping()[$key]['type'] == 'date') {
                $datetime = \DateTime::createFromFormat('Y-m-d', $date);
                if (!($datetime && $datetime->format('Y-m-d') == $date)) {
                    throw new ERException('Try to assign a non-date value (' . $value . ') to a date field (' . $key . ')');
                }
            }
            elseif (static::mapping()[$key]['type'] == 'datetime') {
                $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
                if (!($datetime && $datetime->format('Y-m-d H:i:s') == $date)) {
                    throw new ERException('Try to assign a non-datetime value (' . $value . ') to a datetime field (' . $key . ')');
                }
            }
            else {
                if (!is_null($value)) {
                    $value = trim($value);
                }
            }
        }
        else {
            $expr  = true;
            $value = current($value);
        }

        $this->dirty[$key] = $value;

        if (false === $expr and isset($this->expr[$key])) {
            unset($this->expr[$key]);
        }
        elseif (true === $expr) {
            $this->expr[$key] = true;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Test les données en fonction des règles de validation
     *
     * @param string champs
     * @param string regle
     * @param string test
     * @param array regleTab
     * @param array $datas
     */

    private function lectureRegles($champs, $regle, $test, $regleTab, $datas) {
        $regles = array('requis', 'existe', 'valeurs', 'unique', 'longueur_min', 'longueur_max', 'longueur_exacte', 'superieur', 'inferieur', 'alpha', 'alpha_numerique', 'numerique', 'entier', 'decimal', 'nb_naturel', 'nb_naturel_sans_zero', 'valid_email', 'valid_ip', 'valid_url');

        if (in_array($regle, $regles)) {
            switch ($regle) {
                case 'requis':
                    if (!$datas[$champs]) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' est obligatoire';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'existe':
                    if (!array_key_exists($champs, $datas)) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' est obligatoire';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'valeurs':
                    if (!in_array($datas[$champs], explode(',', $test))) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'La valeur du champs ' . $regleTab['label'] . ' est incorrecte';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'longueur_min':
                    if (($datas[$champs] || $regleTab['required']) && strlen($datas[$champs]) < $test) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être d\'au moins ' . $test . ' caractères';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'longueur_max':
                    if (($datas[$champs] || $regleTab['required']) && strlen($datas[$champs]) > $test) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' ne doit pas dépasser ' . $test . ' caractères';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'longueur_exacte':
                    if (($datas[$champs] || $regleTab['required']) && strlen($datas[$champs]) != $test) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être de ' . $test . ' caractères';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'superieur':
                    if (($datas[$champs] || $regleTab['required']) && $datas[$champs] <= $test) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être supérieur à ' . $test . '';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'inferieur':
                    if (($datas[$champs] || $regleTab['required']) && $datas[$champs] >= $test) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être supérieur à ' . $test . '';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'alpha':
                    if (($datas[$champs] || $regleTab['required']) && !ctype_alpha($datas[$champs])) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' ne doit contenir que des lettres';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'alpha_numerique':
                    if (($datas[$champs] || $regleTab['required']) && !ctype_alnum($datas[$champs])) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être de type alphanumérique';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'numerique':
                    if (($datas[$champs] || $regleTab['required']) && !is_numeric($datas[$champs])) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit contenir uniquement des nombres';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'entier':
                    if (($datas[$champs] || $regleTab['required']) && filter_var($datas[$champs], FILTER_VALIDATE_INT) === false) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit contenir un nombre entier';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'decimal':
                    if (($datas[$champs] || $regleTab['required']) && filter_var($datas[$champs], FILTER_VALIDATE_FLOAT) === false) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit contenir un nombre decimal';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'nb_naturel':
                    $options = array(
                        'options' => array(
                            'min_range' => 0
                        )
                    );
                    if (($datas[$champs] || $regleTab['required']) && filter_var($datas[$champs], FILTER_VALIDATE_INT, $options) === false) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être un nombre naturel';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'nb_naturel_sans_zero':
                    $options = array(
                        'options' => array(
                            'min_range' => 1
                        )
                    );
                    if (($datas[$champs] || $regleTab['required']) && filter_var($datas[$champs], FILTER_VALIDATE_INT, $options) === false) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être un nombre naturel différent de zéro';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'valid_email':
                    if (($datas[$champs] || $regleTab['required']) && !filter_var($datas[$champs], FILTER_VALIDATE_EMAIL)) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être une adresse email valide';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'valid_ip':
                    if (($datas[$champs] || $regleTab['required']) && !filter_var($datas[$champs], FILTER_VALIDATE_IP)) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être une adresse IP valide';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'valid_url':
                    if (($datas[$champs] || $regleTab['required']) && !filter_var($datas[$champs], FILTER_VALIDATE_URL)) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être une URL valide';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
                case 'unique':
                    $class = get_class($this);
                    if (array_key_exists($champs, $this->dirty)) {
                        $valeurChamps = $this->dirty[$champs];
                    }
                    else {
                        $valeurChamps = $this->data[$champs];
                    }

                    $objects      = $class::where($champs, $valeurChamps);

                    if (!$this->isNew()) {
                        foreach(self::getIdentifierValue(self::READ_TO_ARRAY) as $col => $pk) {
                            $objects = $objects->whereNotEqual($col, $pk);
                        }
                    }

                    $objects = $objects->count()->first()->count;

                    if ($objects) {
                        if ($regleTab['msg']) {
                            $erreurTxt = $regleTab['msg'];
                        }
                        else {
                            $erreurTxt = 'Le champs ' . $regleTab['label'] . ' doit être unique';
                        }
                        $this->errors->add($champs, $erreurTxt, $regle);
                    }
                    break;
            }
        }
        else {
            if (((array_key_exists($champs, $datas) && $datas[$champs]) || $regleTab['required']) && !call_user_func(array($this, $regle), $champs, $test)) {
                if ($regleTab['msg']) {
                    $erreurTxt = $regleTab['msg'];
                }
                else {
                    $erreurTxt = null;
                }
                $this->errors->add($champs, $erreurTxt, $regle);
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Exécute la validation d'un modèle
     *
     * @return int
     */

    private function validation() {
        $configValidations = & static::getConfig('validations');

        if (!$configValidations) {
            $this->validations();
        }

        foreach ($configValidations as $champs => $regles) {
            if (array_key_exists($champs, $this->dirty) || array_key_exists($champs, $this->data) || (in_array($champs, static::getConfig('attrAccessor')) && property_exists($this, $champs))) {
                if (array_key_exists($champs, $this->dirty)) {
                    $datas = $this->dirty;
                }
                elseif (array_key_exists($champs, $this->data)) {
                    $datas = $this->data;
                }
                else {
                    $datas = [$champs => $this->$champs];
                }
            }
            foreach ($regles as $regle) {
                if (($regle['regle'] == 'requis' || $regle['regle'] == 'existe' || $regle['required'] == true) || isset($datas)) {
                    if (!isset($datas)) {
                        if ($regle['regle'] == 'existe') {
                            $datas = [];
                        }
                        else {
                            $datas = [$champs => $this->$champs];
                        }
                    }
                    if (mb_strpos($regle['regle'], '[') !== false) {
                        $regleStr = mb_strstr($regle['regle'], '[', true);
                        $test     = mb_strstr($regle['regle'], '[');
                        $test     = str_replace('[', '', $test);
                        $test     = str_replace(']', '', $test);
                        $this->lectureRegles($champs, $regleStr, $test, $regle, $datas);
                    }
                    else {
                        $this->lectureRegles($champs, $regle['regle'], null, $regle, $datas);
                    }
                }
            }
            unset($datas);
        }
        return count($this->errors) != 0;
    }


    // -------------------------------------------------------------------------

    /**
     * Met en forme et retourne les erreurs du modèle
     *
     * @return array
     */

    private function getAndConstructErrors() {
        foreach (static::getConfig('hasOne') as $hasOne => $params) {
            if (is_numeric($hasOne)) {
                $hasOne     = $params;
            }

            $callObject = lcfirst($hasOne);
            $object     = $this->$callObject;
            $this->errors->merge($object->getAndConstructErrors());
        }
        return $this->errors;
    }


    // -------------------------------------------------------------------------

    /**
     * Interprète le nom de la classe
     */

    private function _parseClass() {
        if ($this->namespace === null || $this->classname === null) {
            $name            = get_class($this);
            $this->namespace = join('\\', array_slice(explode('\\', $name), 0, -1));

            if ($this->namespace) {
                $this->namespace .= '\\';
            }

            $this->classname = join('', array_slice(explode('\\', $name), -1));
        }
    }
}

/* End of file */