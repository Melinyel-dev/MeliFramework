<?php

namespace System\Orm;

use System\Helpers\Text;



/**
 * ERRelations Class
 *
 * @author anaeria
 */

class ERRelations
{


    /**
     * Gère l'accès aux propriétés
     *
     * @param string key
     * @return mixed
     */

    public function __get($key)
    {
        if (!property_exists($this, $key)) {
            $this->$key = [];
        }
        return $this->$key;
    }


    // -------------------------------------------------------------------------

    /**
     * Détruit les relations enregistrées
     */

    protected function reset()
    {
        unset($this->hasAndBelongsToManyToAdd);
        unset($this->hasAndBelongsToManyToRemove);
        unset($this->hasManyToAdd);
        unset($this->hasManyToRemove);
    }


    // -------------------------------------------------------------------------

    /**
     * Crée les relations en attente
     *
     * @param string model
     */

    public function createRelations($model)
    {
        if ($this->hasAndBelongsToManyToAdd) {
            $this->saveHasAndBelongsToManyToAdd($model);
        }

        if ($this->hasManyToAdd) {
            $this->saveHasManyToAdd($model);
        }

        $this->reset();
    }


    // -------------------------------------------------------------------------

    /**
     * Met à jour les relations d'un modèle
     *
     * @param string model
     */

    public function updateRelations($model)
    {
        if ($this->hasAndBelongsToManyToRemove) {
            $this->saveHasAndBelongsToManyToRemove($model);
        }

        if ($this->hasAndBelongsToManyToAdd) {
            $this->saveHasAndBelongsToManyToAdd($model);
        }

        if ($this->hasManyToRemove) {
            $this->saveHasManyToRemove($model);
        }

        if ($this->hasManyToAdd) {
            $this->saveHasManyToAdd($model);
        }
        $this->reset();
    }


    // -------------------------------------------------------------------------

    /**
     * Supprime les objets en relations
     *
     * @param string model
     */

    protected function saveHasAndBelongsToManyToRemove($model)
    {
        foreach ($this->hasAndBelongsToManyToRemove as $concatTable => $todo) {
            foreach ($todo as $idHasAndBelongsToMany => $requestHasAndBelongsToMany) {

                $thisIds  = array_values($requestHasAndBelongsToMany['thisId']);
                $objectId = array_values($requestHasAndBelongsToMany['objectId']);

                $modelVals = [];
                $n = 0;
                foreach ($model->getIdentifierValue($model::READ_TO_ARRAY) as $key => $value) {
                    $modelVals[] = $thisIds[$n] . " = '" . $value . "'";
                    $n++;
                }

                $objectVals = [];
                $n = 0;
                foreach ($requestHasAndBelongsToMany['objectValue'] as $key => $value) {
                    $objectVals[] = $objectId[$n] . " = '" . $value . "'";
                    $n++;
                }

                ERDB::getInstance()->query('DELETE FROM '.$requestHasAndBelongsToMany['concatTable'].' WHERE ' . implode(' AND ', $modelVals) . ' AND ' . implode(' AND ', $objectVals) . '');
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute les objets en relations
     *
     * @param string model
     */

    protected function saveHasAndBelongsToManyToAdd($model)
    {
        foreach ($this->hasAndBelongsToManyToAdd as $concatTable => $todo) {
            foreach ($todo as $idHasAndBelongsToMany => $requestHasAndBelongsToMany) {

                $thisIds = array_values($requestHasAndBelongsToMany['thisId']);
                $objectId = array_values($requestHasAndBelongsToMany['objectId']);

                $modelVals = [];
                foreach ($model->getIdentifierValue($model::READ_TO_ARRAY) as $key => $value) {
                    $modelVals[] = "'" . $value . "'";
                }

                $objectVals = [];
                foreach ($requestHasAndBelongsToMany['objectValue'] as $key => $value) {
                    $objectVals[] = "'" . $value . "'";
                }

                ERDB::getInstance()->query('INSERT INTO '.$requestHasAndBelongsToMany['concatTable']." (" . implode(', ', $thisIds) . ", " . implode(', ', $objectId) . ") VALUES (" . implode(', ', $modelVals) . ", " . implode(', ', $objectVals) . ")");
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Supprime les objets en relations
     *
     * @param string model
     */

    protected function saveHasManyToRemove($model)
    {
        foreach ($this->hasManyToRemove as $through => $todo) {
            foreach ($todo as $idHasMany => $requestHasMany) {
                $objectThrough = $through::query();

                foreach ($requestHasMany['hasManyKey'] as $key => $value) {
                    $objectThrough->where($value, $requestHasMany['hasManyValue'][$key]);
                }

                foreach ($model->getIdentifierValue($model::READ_TO_ARRAY) as $key => $value) {
                    $objectThrough->where($requestHasMany['belongsToKey'][$key], $value);
                }

                $objectThrough = $objectThrough->take();
                if ($objectThrough)
                    $objectThrough->delete();
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute les objets en relations
     *
     * @param string model
     */

    protected function saveHasManyToAdd($model)
    {
        foreach ($this->hasManyToAdd as $through => $todo) {
            foreach ($todo as $idHasMany => $requestHasMany) {
                $objectThrough = new $through;

                $object = new $requestHasMany['relName'];
                $cleEtrangere = $requestHasMany['relName']::getIdentifier();
                $object->$cleEtrangere = $requestHasMany['relValue'];

                $requestHasMany['args'] = array_change_key_case($requestHasMany['args']);

                $hasManyRel = lcfirst($requestHasMany['relName']);
                $belongsToRel = lcfirst(get_class($model));
                $objectThrough->$hasManyRel = $object;
                $objectThrough->$belongsToRel = $model;
                foreach ($requestHasMany['args'] as $key => $value) {
                    $objectThrough->$key = $value;
                }
                $objectThrough->save();
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Déinit une relation 1 / n
     *
     * @param string key
     * @param string model
     * @param string alias
     * @return array
     */

    protected function hasMany($key, $model, $alias)
    {
        $string = ucfirst($key);
        $hasMany = $model->getNamespace().$string;

        if ($hasMany[strlen($hasMany)-1] == 's') {
            $hasMany = substr($hasMany,0,strlen($hasMany)-1);
        }

        $whereExpr = null;
        $limit     = null;
        $orderBy   = null;

        if (array_key_exists($string, $model::hasMany())) {
            $infosArray = $model::hasMany()[$string];

            if (array_key_exists('class_name', $infosArray)) {
                $hasMany = $model->getNamespace().$infosArray['class_name'];
            }

            if (array_key_exists('inverse_of', $infosArray)) {
                $infosArray = $hasMany::belongsTo()[$infosArray['inverse_of']];
            }

            if (array_key_exists('conditions', $infosArray)) {
                $whereExpr = $infosArray['conditions'];
            }

            if (array_key_exists('limit', $infosArray)) {
                $limit = $infosArray['limit'];
            }

            if (array_key_exists('order_by', $infosArray)) {
                $orderBy = $infosArray['order_by'];
            }

            if (array_key_exists('through', $model::hasMany()[$string])) {

                $through = $model::hasMany()[$string]['through'];

                if ($through[strlen($through)-1] == 's') {
                    $through = substr($through,0,strlen($through)-1);
                }

                $belongsTo     = $model->getNamespace().$string;

                if (array_key_exists($belongsTo, $through::belongsTo())) {
                    $infosArray = $through::belongsTo()[$belongsTo];

                    if (array_key_exists('class_name', $infosArray)) {
                        $belongsTo = $model->getNamespace().$infosArray['class_name'];
                    }
                }

                $cleEtrangere = $through::getRelationKeys($string);

                if (in_array($string, $through::belongsTo()) || array_key_exists($string, $through::belongsTo())) {
                    if (array_key_exists($string, $through::belongsTo()) && array_key_exists('class_name', $through::belongsTo()[$string])) {
                        $hasMany = $model->getNamespace().$through::belongsTo()[$string]['class_name'];
                    }

                    $throughs = $through.'s';
                } elseif (in_array($string, $through::hasMany()) || array_key_exists($string, $through::hasMany())) {
                    if (array_key_exists($string, $through::hasMany()) && array_key_exists('class_name', $through::hasMany()[$string])) {
                        $hasMany = $model->getNamespace().$through::hasMany()[$string]['class_name'];
                    }

                    $throughs = $string;
                } else {
                    throw new ERException('EasyRecord::joinsIncludes() parameter 1 is not a known relationship or incorrectly defined', 1);
                }

                $objects = $model::
                      alias($alias)
                    ->select('*')
                    ->joins($throughs)
                    ->where($whereExpr)
                    ->orderBy($orderBy)
                    ->limit($limit);

                $values = $model->getIdentifierValue($model::READ_TO_ARRAY);
                foreach ($cleEtrangere as $key => $value) {
                    $objects->where($through::table() . '.' . $value, $values[$key]);
                }

                if (array_key_exists('uniq', $model::hasMany()[$string]) && $model::hasMany()[$string]['uniq'] === true) {
                    $objects->distinct();
                }

                return $objects;
            }
        }

        $cleEtrangere = $model->getRelationKeys($key);

        $hmObject = $hasMany::alias($alias)->where($whereExpr)->orderBy($orderBy)->limit($limit);

        $values = $model->getIdentifierValue($model::READ_TO_ARRAY);

        foreach ($cleEtrangere as $key => $value) {
            $hmObject->where($value, $values[$key]);
        }

        $hmObject->belongsToDetails = ['referer' => $model->getIdentifierValue($model::READ_TO_ARRAY), 'cle_etrangere' => $cleEtrangere, 'model' => get_class($model)];

        return $hmObject;
    }


    // -------------------------------------------------------------------------

    /**
     * Définit une relation 1 / 1
     *
     * @param string key
     * @param string model
     * @param string alias
     * @return array
     */

    protected function hasOne($key, $model, $alias)
    {
        $string = ucfirst($key);
        $hasOne = $model->getNamespace().$string;

        if (!array_key_exists($key, $this->storedHasOne)) {
            if (array_key_exists($string, $model::hasOne())) {
                $infosArray = $model::hasOne()[$string];

                if (array_key_exists('class_name', $infosArray)) {
                    $hasOne = $model->getNamespace().$infosArray['class_name'];
                }
            }

            $cleEtrangere = $model->getRelationKeys($key);

            if (!$model->isNew()) {
                $this->storedHasOne[$key] = $hasOne::query();

                $values = $model->getIdentifierValue($model::READ_TO_ARRAY);
                foreach ($cleEtrangere as $pk => $value) {
                    $this->storedHasOne[$key]->where($value, $values[$pk]);
                }

                $this->storedHasOne[$key] = $this->storedHasOne[$key]->first();
            } else {
                $this->storedHasOne[$key] = null;
            }

            if (!$this->storedHasOne[$key]) {
                $this->storedHasOne[$key] = new $hasOne;

                if (!$model->isNew()) {
                    $values = $model->getIdentifierValue($model::READ_TO_ARRAY);
                    foreach ($cleEtrangere as $pk => $value) {
                        $this->storedHasOne[$key]->$value = $values[$pk];
                    }
                }
            }
        }
        return $this->storedHasOne[$key];
    }


    // -------------------------------------------------------------------------

    /**
     * Définit une relation m / n
     *
     * @param string key
     * @param string model
     * @param string alias
     * @return array
     */

    protected function hasAndBelongsToMany($key, $model, $alias)
    {
        $string = ucfirst($key);
        $hasAndBelongsToMany = $model->getNamespace().$string;

        if ($hasAndBelongsToMany[strlen($hasAndBelongsToMany)-1] == 's') {
            $hasAndBelongsToMany = substr($hasAndBelongsToMany,0,strlen($hasAndBelongsToMany)-1);
        }

        $whereExpr           = null;
        $limit               = null;
        $orderBy             = null;
        $database            = $model::database();

        if (array_key_exists($string, $model::hasAndBelongsToMany())) {
            $infosArray = $model::hasAndBelongsToMany()[$string];

            if (array_key_exists('class_name', $infosArray)) {
                $hasAndBelongsToMany = $model->getNamespace().$infosArray['class_name'];
            }

            if (array_key_exists('inverse_of', $infosArray)) {
                $infosArray = $hasAndBelongsToMany::hasAndBelongsToMany()[$infosArray['inverse_of']];
            }

            if (array_key_exists('association_foreign_key', $infosArray)) {
                $associationCleEtrangere = $infosArray['association_foreign_key'];
            }

            if (array_key_exists('foreign_key', $infosArray)) {
                $concatCleEtrangere = $infosArray['foreign_key'];
            }

            if (array_key_exists('table', $infosArray)) {
                $concatTable = $infosArray['table'];
            }

            if (array_key_exists('conditions', $infosArray)) {
                $whereExpr = $infosArray['conditions'];
            }

            if (array_key_exists('limit', $infosArray)) {
                $limit = $infosArray['limit'];
            }

            if (array_key_exists('order_by', $infosArray)) {
                $orderBy = $infosArray['orderBy'];
            }

            if (array_key_exists('database', $infosArray)) {
                $database = $infosArray['database'];
            }
        }

        if (!isset($associationCleEtrangere)) {
            $associationCleEtrangere = $hasAndBelongsToMany::getIdentifier($hasAndBelongsToMany::READ_TO_ARRAY);
        }

        if (!isset($concatCleEtrangere)) {
            $concatCleEtrangere = $model::getIdentifier($model::READ_TO_ARRAY);
        }

        if(!is_array($associationCleEtrangere)) {
            $associationCleEtrangere = [$hasAndBelongsToMany::getIdentifier($model::READ_TO_STRING) => $associationCleEtrangere];
        }

        if(!is_array($concatCleEtrangere)) {
            $concatCleEtrangere = [$model::getIdentifier($hasAndBelongsToMany::READ_TO_STRING) => $concatCleEtrangere];
        }

        $staticTable = $model::table();
        $habtmTable = $hasAndBelongsToMany::table();
        $objetTable = $hasAndBelongsToMany::database().'.'.$habtmTable;

        if (!isset($concatTable)) {
            if ($habtmTable < $staticTable){
                $concatTable = $habtmTable.'_'.$staticTable;
            }
            else {
                $concatTable = $staticTable.'_'.$habtmTable;
            }
        }

        $concatTableDb = $database.'.'.$concatTable;

        if (!$alias) {
            $alias = $habtmTable;
        }

        $habtmObject = $hasAndBelongsToMany::
              alias($alias)
            ->selectExpr($alias . '.*')
            ->selectExpr($concatTableDb . '.*')
            ->where($whereExpr)
            ->orderBy($orderBy)
            ->limit($limit);

        $constraints = [];
        foreach ($associationCleEtrangere as $key => $value) {
            $constraints[] = ['foreign_key' => $concatTable . '.' . $value, 'identifier' => $alias . '.' . $key];
        }

        $habtmObject->joinWhere($concatTableDb, $constraints);

        $values = $model->getIdentifierValue($model::READ_TO_ARRAY);

        foreach ($concatCleEtrangere as $key => $value) {
            $habtmObject->where($concatTable . '.' . $value, $values[$key]);
        }

        $hasAndBelongsToManyObjectsToAdd = [];
        $hasAndBelongsToManyObjectsToRemove = [];

        if (array_key_exists($concatTable, $this->hasAndBelongsToManyToAdd)) {
            foreach ($this->hasAndBelongsToManyToAdd[$concatTable] as $keyId => $valueRequest) {
                $hasAndBelongsToManyObjectsToAdd[] = $hasAndBelongsToMany::find($keyId);
            }
            $habtmObject = array_unique(array_merge($habtmObject, $hasAndBelongsToManyObjectsToAdd));
        }

        if (array_key_exists($concatTable, $this->hasAndBelongsToManyToRemove)) {
            foreach ($this->hasAndBelongsToManyToRemove[$concatTable] as $keyId => $valueRequest) {
                $hasAndBelongsToManyObjectsToRemove[] = $hasAndBelongsToMany::find($keyId);
            }
            $habtmObject = array_filter(array_diff($habtmObject, $hasAndBelongsToManyObjectsToRemove));
        }

        return $habtmObject;
    }


    // -------------------------------------------------------------------------

    /**
     * Définit une relation 1 / modèle
     *
     * @param string key
     * @param string model
     * @param string alias
     * @return array
     */

    protected function belongsTo($key, $model, $alias)
    {
        $string = ucfirst($key);
        $belongsTo = $model->getNamespace().$string;

        if (array_key_exists($string, $model::belongsTo())) {
            $infosArray = $model::belongsTo()[$string];

            if (array_key_exists('class_name', $infosArray)) {
                $belongsTo = $infosArray['class_name'];
            }
        }

        $cleEtrangere = $model->getRelationKeys($string);

        $fks = $model->getIdentifierValue($model::READ_TO_STRING);

        if (!array_key_exists($key.$fks, $this->storedBelongsTo)) {
            $this->storedBelongsTo[$key.$fks] = null;

            foreach ($cleEtrangere as $pk => $value) {
                $rel[$value] = $model->$pk;
            }

            $this->storedBelongsTo[$key.$fks] = $belongsTo::find($rel);

            if (!$this->storedBelongsTo[$key.$fks]) {
                $this->storedBelongsTo[$key.$fks] = new $belongsTo;
                return $this->storedBelongsTo[$key.$fks];
            }
        }
        return $this->storedBelongsTo[$key.$fks];
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les relations à un modèle
     *
     * @param string key
     * @param string model
     * @param string alias
     * @return NULL | array
     */

    public function getRelations($key, $model, $alias = '')
    {
        $string = ucfirst($key);
        if (in_array($string, $model::hasMany()) || array_key_exists($string, $model::hasMany())) {
            return $this->hasMany($key, $model, $alias);
        } elseif (in_array($string, $model::hasOne()) || array_key_exists($string, $model::hasOne())) {
            return $this->hasOne($key, $model, $alias);
        } elseif (in_array($string, $model::hasAndBelongsToMany()) || array_key_exists($string, $model::hasAndBelongsToMany())) {
            return $this->hasAndBelongsToMany($key, $model, $alias);
        } elseif (in_array($string, $model::belongsTo()) || array_key_exists($string, $model::belongsTo())) {
            return $this->belongsTo($key, $model, $alias);
        }
        return null;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une relation 1 / modèle
     *
     * @param string string
     * @param string value
     * @param string model
     */

    public static function setBelongsTo($string, $value, $model)
    {
        $belongsTo = $model->getNamespace().$string;

        if (array_key_exists($string, $model::belongsTo())) {
            $infosArray = $model::belongsTo()[$string];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangere = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $belongsTo = $model->getNamespace().$infosArray['class_name'];
            }

            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangere = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
            }

            if (array_key_exists('polymorphic', $infosArray)) {
                $cleEtrangere = $infosArray['polymorphic'].'_id';
                $cleType = $infosArray['polymorphic'].'_type';
                $model->$cleType = get_class($value);
            }
        }

        if (!isset($cleEtrangere)) {
            $cleEtrangere = $belongsTo::getIdentifier($belongsTo::READ_TO_ARRAY);
        }

        if(!is_array($cleEtrangere)) {
            $cleEtrangere = [$cleEtrangere => $cleEtrangere];
        }

        $values = array_values($value->getIdentifierValue($model::READ_TO_ARRAY));

        $i = 0;
        foreach ($cleEtrangere as $key => $value) {
            $model->$value = $values[$i];
            $i++;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les clés étrangères des relations
     *
     * @param array relation
     * @return array
     */

    public static function getForeignKeys($model, $relation) {
        $keys = [];

        foreach ($relation as $value) {
            if(is_array($value) && isset($value['foreign_key'])) {
                $keys[] = $value['foreign_key'];
            } elseif (is_string($value)) {
                $className = $model->getNamespace() . $value;
                $keys[] = $className::getIdentifier($model::READ_TO_ARRAY);
            }
        }

        return $keys;
    }
}

/* End of file */