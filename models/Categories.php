<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Categories extends Model
{
    public static $table = 'categories';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;

    public function __construct()
    {
        parent::__construct();
    }
}
