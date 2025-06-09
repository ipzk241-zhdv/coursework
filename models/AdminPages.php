<?php

namespace models;

use classes\Core;
use classes\DB;
use classes\Model;

class AdminPages extends Model
{
    /**
     * @property int $id
     * @property string $page_id
     * @property string $module_id
     * @property string $section // header/body/footer
     * @property string $position // order of module in section
     */

     public static $table = 'pages';

    public function __construct()
    {
        parent::__construct();
    }
}
