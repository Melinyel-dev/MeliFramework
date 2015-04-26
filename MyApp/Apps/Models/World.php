<?php

namespace Apps\Models;

use System\Orm\EasyRecord;

class World extends EasyRecord {

    protected static $mappingConfig = [
        'database' => 'melidev',
        'table' => 'world',
        'identifiant' => 'id',
        'fields' => [
            'id' => [
                'type' => 'integer'
            ],
            'randomNumber' => [
                'type' => 'integer'
            ]
        ]
    ];
}


/* End of file */