<?php

namespace models;

use classes\Core;
use classes\Model;

/**
 * @property int $id
 * @property int $floor
 * @property int $warden_id
 * @property string $name
 */
class Blocks extends Model
{
    public static $table = 'blocks';

    public function __construct()
    {
        parent::__construct();
    }
}
