<?php

namespace Melidev\Apps\Models;

use Melidev\System\Orm\EasyRecord;

class Demo extends EasyRecord {
    protected static $database;
    protected static $table;
    protected static $identifiant;
    protected static $mapping;
    protected static $validations     = [];
    protected static $scopes          = [];
    protected static $defaultScopes   = [];

    #####################################
    # Cache

    protected static $cacheActivation = 'full';
    protected static $cacheTime       = 720;
    protected static $cacheFields     = [];

    #####################################
    # Securité

    protected static $attrAccessible  = [];
    protected static $attrAccessor    = [];

    protected static $hasAndBelongsToMany = [];
    protected static $belongsTo = [];
    protected static $hasMany = [];

    #####################################
    # Callbacks

    protected static $beforeValidation = [];
    protected static $afterValidation  = [];
    protected static $beforeSave       = [];
    protected static $beforeCreate     = [];
    protected static $beforeUpdate     = [];
    protected static $afterCreate      = [];
    protected static $afterUpdate      = [];
    protected static $afterSave        = [];
    protected static $beforeDelete     = [];
    protected static $afterDelete      = [];

    #####################################
    # Callbacks Méthodes

    #####################################
    # Validations

    #####################################    
}


/* End of file */