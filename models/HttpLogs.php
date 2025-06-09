<?php

namespace models;

use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $status_code
 * @property string $message
 * @property array $extra
 * @property string $path
 * @property string $method
 * @property bool $is_ajax
 * @property int $user_id
 * @property \DateTime $created_at
 */
class HttpLogs extends Model
{
    public static $table = 'http_logs';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;

    public function __construct()
    {
        parent::__construct();
    }
}
