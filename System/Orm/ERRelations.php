<?php

namespace System\Orm;

use System\Helpers\Text;


/**
 * ERRelations Class
 *
 * @author anaeria
 */

class ERRelations {


    /**
     * Gère l'accès aux propriétés
     *
     * @param string key
     * @return mixed
     */

    public function __get($key) {
        if (!property_exists($this, $key)) {
            $this->$key = [];
        }
        return $this->$key;
    }


    // -------------------------------------------------------------------------

    /**
     * Détruit les relations enregistrées
     */

    protected function reset() {
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

    public function createRelations($model) {
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

    public function updateRelations($model) {
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

    protected function saveHasAndBelongsToManyToRemove($model) {
        foreach ($this->hasAndBelongsToManyToRemove as $concatTable => $todo) {
            foreach ($todo as $idHasAndBelongsToMany => $requestHasAndBelongsToMany) {
                ERDB::getInstance()->query('DELETE FROM '.$requestHasAndBelongsToMany['concatTable'].' WHERE '.$requestHasAndBelongsToMany['thisId'].' = "'.$model->getIdentifierValue().'" AND '.$requestHasAndBelongsToMany['objectId'].' = "'.$requestHasAndBelongsToMany['objectValue'].'"');
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute les objets en relations
     *
     * @param string model
     */

    protected function saveHasAndBelongsToManyToAdd($model) {
        foreach ($this->hasAndBelongsToManyToAdd as $concatTable => $todo) {
            foreach ($todo as $idHasAndBelongsToMany => $requestHasAndBelongsToMany) {
                ERDB::getInstance()->query('INSERT INTO '.$requestHasAndBelongsToMany['concatTable'].' ('.$requestHasAndBelongsToMany['thisId'].', '.$requestHasAndBelongsToMany['objectId'].') VALUES ("'.$model->getIdentifierValue().'", "'.$requestHasAndBelongsToMany['objectValue'].'")');
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Supprime les objets en relations
     *
     * @param string model
     */

    protected function saveHasManyToRemove($model) {
        foreach ($this->hasManyToRemove as $through => $todo) {
            foreach ($todo as $idHasMany => $requestHasMany) {
                $objectThrough = $through::where($requestHasMany['hasManyKey'], $requestHasMany['hasManyValue'])->where($requestHasMany['belongsToKey'], $model->getIdentifierValue())->take();
                if ($objectThrough) {
                    $objectThrough->delete();
                }
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute les objets en relations
     *
     * @param string model
     */

    protected function saveHasManyToAdd($model) {
        foreach ($this->hasManyToAdd as $through => $todo) {
            foreach ($todo as $idHasMany => $requestHasMany) {
                $objectThrough = new $through;

                $object                = new $requestHasMany['relName'];
                $cleEtrangere          = $requestHasMany['relName']::getIdentifier();
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

    protected function hasMany($key, $model, $alias) {
        $string = ucfirst($key);
        $hasMany = $model->getNamespace().$string;

        if ($hasMany[strlen($hasMany)-1] == 's') {
            $hasMany = substr($hasMany,0,strlen($hasMany)-1);
        }

        $whereExpr = NULL;
        $limit     = NULL;
        $orderBy   = NULL;

        if (array_key_exists($string, $model::hasMany())) {
            $infosArray = $model::hasMany()[$string];

            if (array_key_exists('class_name', $infosArray)) {
                $hasMany = $infosArray['class_name'];
            }

            if (array_key_exists('inverse_of', $infosArray)) {
                $infosArray = $hasMany::belongsTo()[$infosArray['inverse_of']];
            }

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangere = $infosArray['foreign_key'];
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

            if (array_key_exists('polymorphic', $infosArray)) {
                $cleEtrangere = $infosArray['polymorphic'].'_id';
                $cleType = $infosArray['polymorphic'].'_type';
                if ($whereExpr) {
                    $whereExpr .= ' AND';
                }
                $whereExpr .= ' '.$cleType.' = "'.get_class($model).'"';
            }

            if (array_key_exists('through', $model::hasMany()[$string])) {
                $through = $model->getNamespace().$model::hasMany()[$string]['through'];
                if ($through[strlen($through)-1] == 's') {
                    $through = substr($through,0,strlen($through)-1);
                }

                $belongsTo     = $model->getNamespace().get_class($model);
                if (array_key_exists($belongsTo, $through::belongsTo())) {
                    $infosArray = $through::belongsTo()[$belongsTo];

                    if (array_key_exists('foreign_key', $infosArray)) {
                        $cleEtrangere = $infosArray['foreign_key'];
                    }

                    if (array_key_exists('class_name', $infosArray)) {
                        $belongsTo = $infosArray['class_name'];
                    }

                    if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                        $cleEtrangere = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
                    }
                }

                if (!isset($cleEtrangere)) {
                    $cleEtrangere = $model::getIdentifier();
                }

                if (in_array($hasMany, $through::belongsTo()) || array_key_exists($hasMany, $through::belongsTo())) {
                    if (array_key_exists($hasMany, $through::belongsTo()) && array_key_exists('class_name', $through::belongsTo()[$hasMany])) {
                        $hasMany = $through::belongsTo()[$hasMany]['class_name'];
                    }
                    $throughs = $through.'s';
                } elseif (in_array($hasMany.'s', $through::hasMany()) || array_key_exists($hasMany.'s', $through::hasMany())) {
                    if (array_key_exists($hasMany.'s', $through::hasMany()) && array_key_exists('class_name', $through::hasMany()[$hasMany.'s'])) {
                        $hasMany = $through::hasMany()[$hasMany.'s']['class_name'];
                    }
                    $throughs = $through;
                } else {
                    throw new ERException('EasyRecord::joinsIncludes() parameter 1 is not a known relationship or incorrectly defined', 1);
                }

                $objects = $hasMany::
                      alias($alias)
                    ->joins($throughs)
                    ->where($through::table().'.'.$cleEtrangere, $model->getIdentifierValue())
                    ->where($whereExpr)
                    ->orderBy($orderBy)
                    ->limit($limit);

                if (array_key_exists('uniq', $model::hasMany()[$string]) && $model::hasMany()[$string]['uniq'] === TRUE) {
                    $objects->distinct();
                }
                return $objects;
            }
        }

        if (!isset($cleEtrangere)) {
            $cleEtrangere = $model::getIdentifier();
        }

        $hmObject = $hasMany::alias($alias)->where($whereExpr)->where($cleEtrangere, $model->getIdentifierValue())->orderBy($orderBy)->limit($limit);
        $hmObject->belongsToDetails = ['referer' => $model->getIdentifierValue(), 'cle_etrangere' => $cleEtrangere, 'model' => get_class($model)];
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

    protected function hasOne($key, $model, $alias) {
        $string = ucfirst($key);
        $hasOne = $model->getNamespace().$string;
        if (!array_key_exists($key, $this->storedHasOne)) {
            if (array_key_exists($string, $model::hasOne())) {
                $infosArray = $model::hasOne()[$string];

                if (array_key_exists('foreign_key', $infosArray)) {
                    $cleEtrangere = $infosArray['foreign_key'];
                }

                if (array_key_exists('class_name', $infosArray)) {
                    $hasOne = $infosArray['class_name'];
                }

                if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                    $cleEtrangere = $hasOne::belongsTo()[$infosArray['inverse_of']]['foreign_key'];
                }
            }

            if (!isset($cleEtrangere)) {
                $cleEtrangere = $model::getIdentifier();
            }

            if (!$model->isNew()) {
                $this->storedHasOne[$key] = $hasOne::where($cleEtrangere, $model->getIdentifierValue())->first();
            } else {
                $this->storedHasOne[$key] = NULL;
            }

            if (!$this->storedHasOne[$key]) {
                $this->storedHasOne[$key] = new $hasOne;
                if (!$model->isNew()) {
                    $this->storedHasOne[$key]->$cleEtrangere = $model->getIdentifierValue();
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

    protected function hasAndBelongsToMany($key, $model, $alias) {
        $string = ucfirst($key);
        $hasAndBelongsToMany = $model->getNamespace().$string;

        if ($hasAndBelongsToMany[strlen($hasAndBelongsToMany)-1] == 's') {
            $hasAndBelongsToMany = substr($hasAndBelongsToMany,0,strlen($hasAndBelongsToMany)-1);
        }

        $whereExpr           = NULL;
        $limit               = NULL;
        $orderBy             = NULL;
        $database            = $model::database();

        if (array_key_exists($string, $model::hasAndBelongsToMany())) {
            $infosArray = $model::hasAndBelongsToMany()[$string];

            if (array_key_exists('class_name', $infosArray)) {
                $hasAndBelongsToMany = $infosArray['class_name'];
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

        if (!isset($associationCleEtrangere)){
            $associationCleEtrangere = $hasAndBelongsToMany::getIdentifier();
        }

        if (!isset($concatCleEtrangere)){
            $concatCleEtrangere = $model::getIdentifier();
        }

        $staticTable = $model::table();
        $habtmTable  = $hasAndBelongsToMany::table();
        $objetTable  = $hasAndBelongsToMany::database().'.'.$habtmTable;

        if (!isset($concatTable)) {
            if ($habtmTable < $staticTable) {
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
            ->selectExpr($alias.'.*')
            ->joinWhere($concatTableDb, [$concatTable.'.'.$associationCleEtrangere => $alias.'.'.$hasAndBelongsToMany::getIdentifier()])
            ->where($whereExpr)
            ->where($concatTable.'.'.$concatCleEtrangere, $model->getIdentifierValue())
            ->orderBy($orderBy)
            ->limit($limit);

        $hasAndBelongsToManyObjectsToAdd    = [];
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

    protected function belongsTo($key, $model, $alias) {
        $string = ucfirst($key);

        $belongsTo = $model->getNamespace().$string;

        if (array_key_exists($string, $model::belongsTo())) {
            $infosArray = $model::belongsTo()[$string];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangere = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $belongsTo = $infosArray['class_name'];
            }

            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangere = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
            }

            if (array_key_exists('polymorphic', $infosArray)) {
                $cleEtrangere = $infosArray['polymorphic'].'_id';
                $cleType      = $infosArray['polymorphic'].'_type';
                $belongsTo    = $model->$cleType;
            }
        }

        if (!isset($cleEtrangere)) {
            $cleEtrangere = $belongsTo::getIdentifier();
        }

        if (!array_key_exists($key.$model->$cleEtrangere, $this->storedBelongsTo)) {
            $this->storedBelongsTo[$key.$model->$cleEtrangere] = NULL;

            if ($model->$cleEtrangere !== NULL) {
                $this->storedBelongsTo[$key.$model->$cleEtrangere] = $belongsTo::find($model->$cleEtrangere);
            }

            if (!$this->storedBelongsTo[$key.$model->$cleEtrangere]) {
                $this->storedBelongsTo[$key.$model->$cleEtrangere] = new $belongsTo;
                return $this->storedBelongsTo[$key.$model->$cleEtrangere];
            }
        }
        return $this->storedBelongsTo[$key.$model->$cleEtrangere];
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

    public function getRelations($key, $model, $alias = '') {
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
        return NULL;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une relation 1 / modèle
     *
     * @param string string
     * @param string value
     * @param string model
     */

    public static function setBelongsTo($string, $value, $model) {
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
                $cleEtrangere    = $infosArray['polymorphic'].'_id';
                $cleType         = $infosArray['polymorphic'].'_type';
                $model->$cleType = get_class($value);
            }
        }

        if (!isset($cleEtrangere)) {
            $cleEtrangere = $belongsTo::getIdentifier();
        }
        $model->$cleEtrangere = $value->getIdentifierValue();
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les clés étrangères des relations
     *
     * @param array relation
     * @return array
     */

    public static function getForeignKeys($relation) {
        $keys = [];

        foreach ($relation as $value) {
            if(is_array($value) && isset($value['foreign_key'])) {
                $keys[] = $value['foreign_key'];
            } elseif (is_string($value)) {
                $keys[] = 'id_'.Text::camelToUnderscore($value);
            }
        }

        return $keys;
    }
}

/* End of file */