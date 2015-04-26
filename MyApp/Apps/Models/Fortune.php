<?php

namespace Apps\Models;

use System\Orm\EasyRecord;

class Fortune extends EasyRecord {

    protected static $mappingConfig = [
        'database' => 'melidev',
        'table' => 'fortunes',
        'identifiant' => 'id',
        'fields' => [
            'id' => [
                'type' => 'integer'
            ],
            'message' => [
                'type' => 'string'
            ]
        ]
    ];
}


/* End of file */