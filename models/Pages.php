<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 */
class Pages extends Model
{
    public static $table = 'pages';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;


    public function __construct()
    {
        parent::__construct();
    }
}
