<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $subcategory_id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Threads extends Model
{
    public static $table = 'threads';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;

    public function __construct()
    {
        parent::__construct();
    }
}
