<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $current_layout_id
 * @property bool $to_cache
 * @property int $cache_lifetime
 * @property array $exclude_cache
 */
class Settings extends Model
{
    public static $table = 'settings';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;

    public function __construct()
    {
        parent::__construct();
    }
}
