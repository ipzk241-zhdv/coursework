<?php

namespace models;

use classes\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $img
 * @property int $places
 * @property int $block_id
 * @property string $room_type
 */
class Rooms extends Model
{
    public static $table = 'rooms';

    public function __construct()
    {
        parent::__construct();
    }
}
