<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $user_id
 * @property string $target_type
 * @property string $target_id
 * @property \DateTime $createdAt
 */
class Likes extends Model
{
    public static $table = 'likes';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;


    public function __construct()
    {
        parent::__construct();
    }
}
