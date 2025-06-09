<?php

namespace models;

use classes\Model;

/**
 * @property int $id
 * @property string $table_name
 * @property string $field
 * @property string $label
 * @property string $type
 * @property int $sortable
 * @property int $searchable
 * @property int $visible
 * @property int $position
 */
class TableConfigs extends Model
{
    public static $table = 'table_configs';

    public function __construct()
    {
        parent::__construct();
    }
}
