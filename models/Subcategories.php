<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Subcategories extends Model
{
    public static $table = 'subcategories';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;


    public function __construct()
    {
        parent::__construct();
    }
}
