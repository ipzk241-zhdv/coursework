<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $thread_id
 * @property int $parent_id
 * @property int $user_id
 * @property string $content
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Comments extends Model
{
    public static $table = 'comments';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;


    public function __construct()
    {
        parent::__construct();
    }
}
