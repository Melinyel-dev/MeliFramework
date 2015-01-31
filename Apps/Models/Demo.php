<?php

namespace Apps\Models;

use System\Orm\EasyRecord;

class Demo extends EasyRecord {

    protected static $mappingConfig = [
        'database' => 'melidev',
        'table' => 'demo',
        'identifiant' => 'id',
        'fields' => [
            'id' => [
                'type' => 'integer'
            ],
            'title' => [
                'type' => 'string'
            ],
            'date' => [
                'type' => 'string'
            ]
        ]
    ];

    #####################################
    # Cache

    protected static $cacheActivation = 'off';
    protected static $cacheTime       = 0;

    #####################################
    # Liaisons

    protected static $hasAndBelongsToMany = [];
    protected static $belongsTo = [];
    protected static $hasMany = [];

    #####################################
    # Callbacks

    #####################################
    # Callbacks Méthodes

}


/* End of file */