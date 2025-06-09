<?php

namespace models;

use classes\Core;
use classes\DB;
use classes\Model;

class AdminModules extends Model
{
    /**
     * @property int $id
     * @property string $name  // filename
     * @property string $title // description
     */

    public static $table = 'modules';

    public function __construct()
    {
        parent::__construct();
    }

    public static function addModule(string $name, string $description): bool
    {
        $module = new AdminModules();
        $module->name = $name;
        $module->title = $description;

        return $module->save();
    }

    public static function deleteModule(string $name)
    {

        $module = new AdminModules();
        return $module->deleteByCondition(["name" => $name]);
    }

    public static function editModule(string $name, $description)
    {
        $module = new AdminModules();
        $find = $module->findByCondition(["name" => $name])[0];
        if ($module === null) return false;
        // array count > 0 return false?

        $module->id = $find['id'];
        $module->name = $name;
        $module->title = $description;
        return $module->save();
    }
}
