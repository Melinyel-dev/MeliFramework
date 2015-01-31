<?php

namespace System\Orm;

/* Helpers */
require_once __DIR__ . '/helpers.php';

use System\Helpers\Text;

class EasyRecord {
    #####################################
    # Core

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
    private $namespace            = NULL;
    private $classname            = NULL;

    #####################################
    # Relationships
    protected $relations                  = null;
    protected static $hasMany             = [];
    protected static $hasOne              = [];
    protected static $belongsTo           = [];
    protected static $hasAndBelongsToMany = [];

    #####################################
    # Validations
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

    #####################################
    # Scopes
    protected static $scopes        = [];

    #####################################
    # Cache
    protected static $cacheActivation = 'off';
    protected static $cacheTime       = 0;
    protected static $cacheFields     = [];

    #####################################
    # Securité
    protected static $attrAccessible = [];
    protected static $attrAccessor   = [];

    public function __construct($fromQuery = false) {
        $mapping      = static::mappingInit();
        if (!$fromQuery) $this->init();
        if (!static::getConfig('scopes')) $this->scopes();
        $this->errors = new ERErreurManager;
    }

    public static function &getConfig($key = null) {
        static $_config;

        $class = get_called_class();

        if (isset($_config) && isset($_config[$class])) {
            if ($key) return $_config[$class][$key];
            else return $_config[$class];
        }

        $config = [
            'database'            => static::$database,
            'table'               => static::$table,
            'identifiant'         => static::$identifiant,
            'mapping'             => static::$mapping,
            'mappingConfig'             => static::$mappingConfig,
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
        if ($key) return $_config[$class][$key];
        else return $_config[$class];
    }

    #####################################
    # Accessors/Settors

    public function getClassname() {
        $this->_parseClass();
        return $this->classname;
    }

    public function getNamespace() {
        $this->_parseClass();
        return $this->namespace;
    }

    public static function getIdentifier($lower = true) {
        if ($lower) return static::identifiant();
        return static::identifiant();
    }

    public function getIdentifierValue() {
        $id = self::getIdentifier();
        if (array_key_exists($id, $this->data)) return $this->data[$id];
        return isset($this->dirty[$id]) ? $this->dirty[$id] : false;
    }

    public function getDataDirty() {
        return array_merge($this->data, $this->dirty);
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
        return true;
    }

    public static function cacheActivation() {
        return static::getConfig('cacheActivation');
    }

    public static function cacheTime() {
        return static::getConfig('cacheTime');
    }

    public static function cacheFields() {
        return static::getConfig('cacheFields');
    }

    public static function hasAndBelongsToMany() {
        return static::getConfig('hasAndBelongsToMany');
    }

    public static function hasMany() {
        return static::getConfig('hasMany');
    }

    public static function hasOne() {
        return static::getConfig('hasOne');
    }

    public static function belongsTo() {
        return static::getConfig('belongsTo');
    }

    public static function getScopes() {
        if (!static::getConfig('scopes')) {
            $object = new static;
            $object->scopes();
        }
        return static::getConfig('scopes');
    }

    public static function database() {
        if (!static::getConfig('mapping')) static::mappingInit();
        return static::getConfig('database');
    }

    public static function table() {
        if (!static::getConfig('mapping')) static::mappingInit();
        return static::getConfig('table');
    }

    public static function identifiant() {
        if (!static::getConfig('mapping')) static::mappingInit();
        return static::getConfig('identifiant');
    }

    public static function mapping() {
        if (!static::getConfig('mapping')) static::mappingInit();
        return static::getConfig('mapping');
    }

    public static function mappingConfig() {
        if (!static::getConfig('mappingConfig')) static::mappingInit();
        return static::getConfig('mappingConfig');
    }

    #####################################
    # Magics

    public function __sleep() {
        return ['data', 'dirty', 'expr', 'errors', 'newRecord', 'readOnly', 'relations'];
    }

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([new static, $name], $arguments);
    }

    public function __call($name, $arguments) {
        if (preg_match('/([\w]*)Changed/', $name, $outputArray)) {
            return array_key_exists($outputArray[1], $this->dirty);
        }
        elseif (preg_match('/([\w]*)Was/', $name, $outputArray)) {
            if (array_key_exists($outputArray[1], $this->dirty))
                    return array_key_exists($outputArray[1], $this->data) ? $this->data[$outputArray[1]] : false;
            return false;
        } else {
            return call_user_func_array([$this->newQuery(), $name], $arguments);
        }
    }

    public function __get($key) {
        $tmpData            = $this->data;
        foreach ($this->dirty as $keyDirty => $value)
            $tmpData[$keyDirty] = $value;
        if (array_key_exists($key, $tmpData)) {
            return $tmpData[$key];
        }
        return $this->relations()->getRelations($key, $this);
    }

    public function __set($key, $value) {
        if ($key !== null) {
            $string = ucfirst($key);
            if (in_array($string, static::getConfig('belongsTo')) || array_key_exists($string, static::getConfig('belongsTo'))) {
                return ERRelations::setBelongsTo($string, $value, $this);
            }
            else {
                if (in_array($key, static::getConfig('attrAccessor')))
                        return $this->$key = $value;
                if ($this->hasSetMutator($key)) {
                    $method = 'set' . Text::underscoreToCamel($key) . 'Attribute';
                    return $this->{$method}($value);
                }
                $this->setProperty($key, $value, ($value === null));
            }
        }
    }

    public function __clone() {
        $this->errors    = clone $this->errors;
        if ($this->relations) $this->relations = clone $this->relations;
    }

    public function __isset($name) {
        if (array_key_exists($name, $this->dirty) || array_key_exists($name, $this->data))
                return true;
        return false;
    }

    public function __toString() {
        return $this->getIdentifierValue() !== false ? strval($this->getIdentifierValue()) : '';
    }

    public function toJSON() {
        return json_encode($this->data);
    }

    public function toRow() {
        return $this->data;
    }

    #####################################
    # Attributes/Object State

    public function changed() {
        return count($this->dirty) ? true : false;
    }

    public function changes() {
        $ary = [];
        foreach ($this->dirty as $key => $value) {
            $ary[$key] = [array_key_exists($key, $this->data) ? $this->data[$key] : false, $value];
        }
        return $ary;
    }

    public function changedAttributes() {
        $ary = [];
        foreach ($this->dirty as $key => $value) {
            $ary[$key] = [array_key_exists($key, $this->data) ? $this->data[$key] : false];
        }
        return $ary;
    }

    public function unsetProp($field) {
        $return = false;
        if (array_key_exists($field, $this->dirty)) unset($this->dirty[$field]);
        if (array_key_exists($field, $this->expr)) unset($this->expr[$field]);
        if (array_key_exists($field, $this->data)) {
            $return = $this->data[$field];
            unset($this->data[$field]);
        }
        return $return;
    }

    public function revert($field) {
        if (array_key_exists($field, $this->dirty)) {
            unset($this->dirty[$field]);
            return true;
        }
        return false;
    }

    public function valid() {
        return count($this->errors) ? false : true;
    }

    protected function resetCache() {
        ERCache::getInstance()->nsDelete('EasyRecordCache', get_class($this) . '_' . $this->getIdentifierValue() . $this::cacheActivation() . $this::cacheTime());
    }

    #####################################
    # New Related

    public function notNew() {
        $this->newRecord = false;
        return $this;
    }

    public function isNew() {
        return $this->newRecord;
    }

    #####################################
    # ReadOnly

    public function setReadOnly() {
        $this->readOnly = true;
        return $this;
    }

    public function getReadOnly() {
        return $this->readOnly;
    }

    #####################################
    # Scopes

    public function scope($name, $function) {
        if (!is_callable($function))
                throw new ERException(get_class($this) . '::scope() expects paramter 2 to be a function, ' . gettype($function) . ' given');
        $scopes        = & static::getConfig('scopes');
        $scopes[$name] = $function;
    }

    #####################################
    # Depreciated

    public static function query() {
        $nomClasse = get_called_class();
        $newObject = new $nomClasse(true);
        $query     = $newObject->newQuery();
        return $query;
    }

    public static function q() {
        $nomClasse = get_called_class();
        return $nomClasse::query();
    }

    public function setExpr($key, $value = null) {
        $this->setProperty($key, $value, true);
    }

    public function getChamps() {
        return $this->data;
    }

    #####################################
    # Relationship

    public function flush() {
        $this->relations = null;
        return $this;
    }

    #####################################
    # Object Persistence

    public static function build($arguments = [], $from = null) {
        $object                                       = new static;
        if ($from)
                $object->{$from['cle_etrangere']} = $from['referer'];
        foreach ($arguments as $key => $value) {
            $object->$key = $value;
        }
        return $object;
    }

    public static function create(array $params = [], $bypassAttrAccessible = false) {
        $nomClasse            = get_called_class();
        $nouvelObjet          = new $nomClasse();
        $massAssignmentErrors = [];
        foreach ($params as $key => $value) {
            if ($bypassAttrAccessible || count($nomClasse::getConfig('attrAccessible')) == 0 || in_array($key, $nomClasse::getConfig('attrAccessible')))
                    $nouvelObjet->$key      = $value;
            else $massAssignmentErrors[] = $key;
        }
        if ($massAssignmentErrors)
                throw new ERException('can\'t mass-assign protected attributes: ' . implode(', ', $massAssignmentErrors), 2);
        $nouvelObjet->save();
        return $nouvelObjet;
    }

    public function groupUpdate($ids = []) {
        if (!$ids || !is_array($ids)) return false;
        extract($this->buildUpdateGroup($ids));
        ERTools::execute($sqlQuery, $bindParam);
        $this->dirty = [];
        $this->expr  = [];
        return true;
    }

    public function save($force = false) {
        if ($this->readOnly)
                throw new ERException('readOnly objects can not be saved', 3);
        $config = & static::getConfig();
        if ($config['beforeValidation']) {
            foreach ($config['beforeValidation'] as $beforeValidation) {
                if (call_user_func(array($this, $beforeValidation)) === false)
                        return false;
            }
        }
        if (!$force && $this->validation()) return false;
        if ($config['afterValidation']) {
            foreach ($config['afterValidation'] as $afterValidation) {
                call_user_func(array($this, $afterValidation));
            }
        }
        if ($config['beforeSave']) {
            foreach ($config['beforeSave'] as $beforeSave) {
                if (call_user_func(array($this, $beforeSave)) === false)
                        return false;
            }
        }

        $values = array_values(array_diff_key($this->dirty, $this->expr));
        if (!$this->isNew()) {
            $newObject = false;
            if ($config['beforeUpdate']) {
                foreach ($config['beforeUpdate'] as $beforeUpdate) {
                    if (call_user_func(array($this, $beforeUpdate)) === false)
                            return false;
                }
            }
            $this->relations()->updateRelations($this);
            if (empty($values) && empty($this->expr)) {
                $this->errors = new ERErreurManager;
                return true;
            }
            //$dateMaj = new DateTime('now Europe/Paris');
            //$this->date_maj = $dateMaj->format("Y-m-d H:i:s");
            extract($this->buildUpdate());
        }
        else {
            $newObject = true;
            if ($config['beforeCreate']) {
                foreach ($config['beforeCreate'] as $beforeCreate) {
                    if (call_user_func(array($this, $beforeCreate)) === false)
                            return false;
                }
            }
            //$dateMaj = new DateTime('now Europe/Paris');
            //$this->date_maj = $dateMaj->format("Y-m-d H:i:s");
            extract($this->buildInsert());
        }
        ERTools::execute($sqlQuery, $bindParam);
        if ($this->isNew()) {
            $lastId                            = ERDB::getInstance()->lastId();
            if ($lastId) $this->data[self::getIdentifier()] = $lastId;
            $this->newRecord                   = false;
            $this->relations()->createRelations($this);
        }
        if ($this->expr) {
            $objectValues = $this::assocArray()->select(array_keys($this->expr))->noCache()->find($this->getIdentifierValue());
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
            ERCache::getInstance()->nsDelete('EasyRecordCache', $className . '_' . self::getIdentifierValue() . self::cacheActivation() . self::cacheTime());
        }

        return true;
    }

    public function updateAttributes($attributes, $bypassAttrAccessible = false, $save = true) {
        if (!isset($GLOBALS['update_attributes_object_hash']))
                $GLOBALS['update_attributes_object_hash']            = spl_object_hash($this);
        if (!isset($GLOBALS['update_attributes_objects']))
                $GLOBALS['update_attributes_objects']                = [];
        if (!isset($GLOBALS['update_attributes_errors']))
                $GLOBALS['update_attributes_errors']                 = [];
        if (!isset($GLOBALS['update_attributes_new']))
                $GLOBALS['update_attributes_new']                    = $this->isNew();
        if (!isset($GLOBALS['update_attributes_mass_assignment_errors']))
                $GLOBALS['update_attributes_mass_assignment_errors'] = [];

        $hasOnes = [];
        if (!is_array($attributes))
                throw new ERException(get_class($this) . '::updateAttributes() expects parameter 1 to be array, ' . gettype($attributes) . ' given');
        $config  = & static::getConfig();
        foreach ($attributes as $key => $value) {
            if ($bypassAttrAccessible || count($config['attrAccessible']) == 0 || in_array($key, $config['attrAccessible'])) {
                $keyU          = ucfirst($key);
                if (in_array($keyU, $config['hasOne']) || array_key_exists($keyU, $config['hasOne']))
                        $hasOnes[$key] = $value;
                elseif ($this->$key !== $value || $value === null || in_array($key, $config['attrAccessor']))
                        $this->$key    = $value;
            } else {
                $GLOBALS['update_attributes_mass_assignment_errors'][] = $key;
                $save                                                  = false;
            }
        }
        /* $beforeValidationError = false;
          if ($save && static::getConfig('beforeValidation')) {
          foreach (static::getConfig('beforeValidation') as $beforeValidation) {
          if (call_user_func(array($this, $beforeValidation)) === false)
          $beforeValidationError = true;
          }
          }
          if (!$this->validation() && $save && !$beforeValidationError) { */
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
                if (count($GLOBALS['update_attributes_errors']) && !$this->isNew())
                        $GLOBALS['update_attributes_objects'] = [];
                foreach ($GLOBALS['update_attributes_objects'] as $object) {
                    $object->save();
                }
            }
            unset($GLOBALS['update_attributes_object_hash']);
            unset($GLOBALS['update_attributes_objects']);
            unset($GLOBALS['update_attributes_errors']);
            unset($GLOBALS['update_attributes_new']);
            if ($GLOBALS['update_attributes_mass_assignment_errors'])
                    throw new ERException('can\'t mass-assign protected attributes: ' . implode(', ', $GLOBALS['update_attributes_mass_assignment_errors']), 2);
            unset($GLOBALS['update_attributes_mass_assignment_errors']);
        }
        return !count($this->getAndConstructErrors());
    }

    public function delete() {
        if ($this->getIdentifierValue()) {
            $config = & static::getConfig();
            if ($config['beforeDelete']) {
                foreach ($config['beforeDelete'] as $beforeDelete) {
                    if (call_user_func(array($this, $beforeDelete)) === false) {
                        return false;
                    }
                }
            }
            if ($config['hasMany']) $this->deleteHasMany();
            if ($config['hasOne']) $this->deleteHasOne();
            if ($config['hasAndBelongsToMany'])
                    $this->deleteHasAndBelongsToMany();
            $database = static::database();
            $table    = static::table();
            $query    = implode(' ', array(
                'DELETE FROM',
                ERTools::quoteIdentifiant($database) . '.' . ERTools::quoteIdentifiant($table),
                'WHERE',
                ERTools::quoteIdentifiant(self::getIdentifier(false)),
                '= ' . $this->getIdentifierValue(),
            ));
            ERTools::execute($query, new ERBindParam);
            if ($config['afterDelete']) {
                foreach ($config['afterDelete'] as $afterDelete) {
                    call_user_func(array($this, $afterDelete));
                }
            }
            return true;
        }
        return true;
    }

    #####################################
    # Getters Initializers

    public function relations() {
        if (!$this->relations) $this->relations = new ERRelations;
        return $this->relations;
    }

    #####################################
    # Private Query Builder

    private function buildUpdate() {
        $bindParam = new ERBindParam();
        $requete   = [];
        $requete[] = 'UPDATE ' . ERTools::quoteIdentifiant(static::database()) . '.' . ERTools::quoteIdentifiant(static::table()) . ' SET';

        $listeChamps = [];
        foreach ($this->dirty as $field => $value) {
            if (!array_key_exists($field, $this->expr)) {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ?';
                $type_colonne  = 's';
                if (array_key_exists($field, static::mapping()))
                        $type_colonne  = static::mapping()[$field]['type'][0];
                $bindParam->add($type_colonne, $value);
            } else {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ' . ($value === null ? 'NULL' : $value);
            }
        }
        $requete[] = implode(', ', $listeChamps);
        $requete[] = 'WHERE';
        $requete[] = ERTools::quoteIdentifiant(static::getIdentifier(false));
        $requete[] = '= "' . $this->getIdentifierValue() . '"';
        return ['sqlQuery' => implode(' ', $requete), 'bindParam' => $bindParam];
    }

    private function buildUpdateGroup($ids) {
        $bindParam = new ERBindParam();
        $requete   = [];
        $requete[] = 'UPDATE ' . ERTools::quoteIdentifiant(static::database()) . '.' . ERTools::quoteIdentifiant(static::table()) . ' SET';

        $listeChamps = [];
        foreach ($this->dirty as $field => $value) {
            if (!array_key_exists($field, $this->expr)) {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ?';
                $type_colonne  = 's';
                if (array_key_exists($field, static::mapping()))
                        $type_colonne  = static::mapping()[$field]['type'][0];
                $bindParam->add($type_colonne, $value);
            } else {
                $listeChamps[] = ERTools::quoteIdentifiant($field) . ' = ' . ($value === '' ? 'NULL' : $value);
            }
        }
        $requete[] = implode(', ', $listeChamps);
        $requete[] = 'WHERE';
        $requete[] = ERTools::quoteIdentifiant(static::getIdentifier(false));
        $requete[] = 'IN (' . implode(', ', $ids) . ')';
        return ['sqlQuery' => implode(' ', $requete), 'bindParam' => $bindParam];
    }

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
            if (!$first) $requeteStr .= ',';
            if (isset($this->expr[$field])) {
                $requeteStr .= $value === null ? 'NULL' : $value;
            }
            else {
                $requeteStr .= '?';
                $type_colonne = 's';
                if (array_key_exists($field, static::mapping()))
                        $type_colonne = static::mapping()[$field]['type'][0];
                $bindParam->add($type_colonne, $value);
            }
            $requete[] = $requeteStr;
            $first     = false;
        }
        $requete[] = ')';
        return ['sqlQuery' => implode(' ', $requete), 'bindParam' => $bindParam];
    }

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
                    $cleEtrangere = self::getIdentifier(false);
                }
                $objets = $objetName::where($cleEtrangere, $this->getIdentifierValue());
                if ($hasMany['dependent'] == 'destroy') {
                    foreach ($objets as $objetToDelete) {
                        $objetToDelete->delete();
                    }
                    return true;
                }
                elseif ($hasMany['dependent'] == 'delete_all') {
                    ERDB::getInstance()->query('DELETE FROM ' . $objetName::database() . '.' . $objetName::table() . ' WHERE ' . $cleEtrangere . ' = "' . $this->getIdentifierValue() . '"');
                    return true;
                }
                elseif ($hasMany['dependent'] == 'nullify') {
                    foreach ($objets as $objetToDelete) {
                        $objetToDelete->$cleEtrangere = null;
                        $objetToDelete->save();
                    }
                    return true;
                }
            }
        }
        return false;
    }

    private function deleteHasOne() {
        foreach (static::getConfig('hasOne') as $key => $hasOne) {
            $key = 'Agendaweb\App\Models\\' . $key;
            if (is_array($hasOne) && array_key_exists('dependent', $hasOne)) {
                if (array_key_exists('foreign_key', $hasOne))
                        $cleEtrangere = $hasOne['foreign_key'];
                if (array_key_exists('class_name', $hasOne))
                        $key          = 'Agendaweb\App\Models\\' . $hasOne['class_name'];
                if (!isset($cleEtrangere) && array_key_exists('inverse_of', $hasOne)) {
                    $cleEtrangere = $key::getConfig('belongsTo')[$hasOne['inverse_of']]['foreign_key'];
                }
                if (!isset($cleEtrangere))
                        $cleEtrangere = self::getIdentifier(false);
                $objets       = $key::where($cleEtrangere, $this->getIdentifierValue());
                if ($hasOne['dependent'] == 'destroy') {
                    foreach ($objets as $objetToDelete) {
                        $objetToDelete->delete();
                    }
                }
                elseif ($hasOne['dependent'] == 'delete_all') {
                    ERDB::getInstance()->query('DELETE FROM ' . $key::table() . ' WHERE ' . $cleEtrangere . ' = "' . $this->getIdentifierValue() . '"');
                }
                elseif ($hasOne['dependent'] == 'nullify') {
                    foreach ($objets as $objetToDelete) {
                        $objetToDelete->$cleEtrangere = null;
                        $objetToDelete->save();
                    }
                }
            }
        }
    }

    private function deleteHasAndBelongsToMany() {
        $database = static::database();
        $table    = static::table();
        foreach (static::getConfig('hasAndBelongsToMany') as $key => $hasAndBelongsToMany) {
            if (is_array($hasAndBelongsToMany)) {
                $objetName = 'Agendaweb\App\Models\\' . $key;
                if ($objetName[strlen($objetName) - 1] == 's')
                        $objetName = substr($objetName, 0, strlen($objetName) - 1);
                if (array_key_exists('class_name', $hasAndBelongsToMany))
                        $objetName = 'Agendaweb\App\Models\\' . $hasAndBelongsToMany['class_name'];
                if (array_key_exists('inverse_of', $hasAndBelongsToMany)) {
                    $infosArray = $objetName::getConfig('hasAndBelongsToMany')[$hasAndBelongsToMany['inverse_of']];
                }
                else {
                    $infosArray = $hasAndBelongsToMany;
                }
                if (array_key_exists('foreign_key', $infosArray))
                        $concatCleEtrangere = $infosArray['foreign_key'];
                if (array_key_exists('table', $infosArray))
                        $concatTable        = $infosArray['table'];
                if (array_key_exists('database', $infosArray))
                        $database           = $infosArray['database'];
            } else {
                $objetName = 'Agendaweb\App\Models\\' . $hasAndBelongsToMany;
                if ($objetName[strlen($objetName) - 1] == 's')
                        $objetName = substr($objetName, 0, strlen($objetName) - 1);
            }
            if (!isset($concatCleEtrangere))
                    $concatCleEtrangere = self::getIdentifier(false);
            if (!isset($concatTable)) {
                $habtmTable  = $objetName::table();
                if ($habtmTable < $table)
                        $concatTable = $habtmTable . '_' . $table;
                else $concatTable = $table . '_' . $habtmTable;
            }
            $concatTableDb = $database . '.' . $concatTable;
            ERDB::getInstance()->query('DELETE FROM ' . $concatTableDb . ' WHERE ' . $concatCleEtrangere . ' = "' . $this->getIdentifierValue() . '"');
        }
    }

    public function setAttribute($key, $value) {
        $this->data[$key] = $value;
    }

    protected function newQuery() {
        return new ERQuery($this);
    }

    protected function hasSetMutator($key) {
        return method_exists($this, 'set' . Text::underscoreToCamel($key) . 'Attribute');
    }

    protected function init() {

    }

    protected function validations() {

    }

    protected function scopes() {

    }

    protected static function mappingInit() {
        $config    = & static::getConfig();
        if (!$config['mapping'] && self::mappingConfig()) {
            $config['database']    = self::mappingConfig()['database'];
            $config['table']       = self::mappingConfig()['table'];
            $config['identifiant'] = self::mappingConfig()['identifiant'];
            $config['mapping']     = self::mappingConfig()['fields'];
            return self::mappingConfig();
        }
        return false;
    }

    protected function validates($field, $regle, $msg = null, $options = []) {
        $configValidation           = & static::getConfig('validations');
        if (!array_key_exists($field, $configValidation))
                $configValidation[$field]   = [];
        if (!array_key_exists('label', $options)) $options['label']           = $field;
        if (!array_key_exists('required', $options))
                $options['required']        = false;
        $validation                 = ['regle' => $regle, 'msg' => $msg, 'label' => $options['label'], 'required' => $options['required']];
        $configValidation[$field][] = $validation;
    }

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
                    $class        = get_class($this);
                    if (array_key_exists($champs, $this->dirty))
                            $valeurChamps = $this->dirty[$champs];
                    else $valeurChamps = $this->data[$champs];
                    $objects      = $class::where($champs, $valeurChamps);
                    if (!$this->isNew()) {
                        $objects = $objects->whereNotEqual(self::getIdentifier(), $this->getIdentifierValue());
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

    private function validation() {
        $configValidations = & static::getConfig('validations');
        if (!$configValidations) $this->validations();
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
                        if ($regle['regle'] == 'existe') $datas = [];
                        else $datas = [$champs => $this->$champs];
                    }
                    if (mb_strpos($regle['regle'], '[') !== false) {
                        $regleStr = mb_strstr($regle['regle'], '[', true);
                        $test     = mb_strstr($regle['regle'], '[');
                        $test     = str_replace('[', '', $test);
                        $test     = str_replace(']', '', $test);
                        $this->lectureRegles($champs, $regleStr, $test, $regle, $datas);
                    }
                    else
                            $this->lectureRegles($champs, $regle['regle'], null, $regle, $datas);
                }
            }
            unset($datas);
        }
        return count($this->errors) != 0;
    }

    private function getAndConstructErrors() {
        foreach (static::getConfig('hasOne') as $hasOne => $params) {
            if (is_numeric($hasOne)) $hasOne     = $params;
            $callObject = lcfirst($hasOne);
            $object     = $this->$callObject;
            $this->errors->merge($object->getAndConstructErrors());
        }
        return $this->errors;
    }

    private function _parseClass() {
        if ($this->namespace === NULL || $this->classname === NULL) {
            $name            = get_class($this);
            $this->namespace = join('\\', array_slice(explode('\\', $name), 0, -1));
            if ($this->namespace) $this->namespace .= '\\';
            $this->classname = join('', array_slice(explode('\\', $name), -1));
        }
    }

}

/* End of file */