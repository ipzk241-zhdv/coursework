<?php

namespace models;

use classes\Core;
use classes\DB;
use classes\Model;

class AdminPageModules extends Model
{
    /**
     * @property int $id
     * @property string $page_id
     * @property string $module_id
     * @property string $section // header/body/footer
     * @property string $position // order of module in section
     */

     public static $table = 'page_modules';

    public function __construct()
    {
        parent::__construct();
    }

    public static function editPageModules($assocPageModules)
    {
        if ($assocPageModules === null) {
            return;
        }

        self::deletePageModules($assocPageModules['slug']);

        
        foreach ($assocPageModules['sections'] as $sectionName => $sectionModules) {
            foreach ($sectionModules as $pos => $module) {
                $pageModules = new AdminPageModules();
                $pageModules->page_id = AdminPages::findByCondition(["slug" => $assocPageModules['slug']])[0]['id'];
                $pageModules->module_id = AdminModules::findByCondition(["name" => $module])[0]['id'];
                $pageModules->section = $sectionName;
                $pageModules->position = $pos;
                $pageModules->save();
            }
        }
    }

    private static function deletePageModules($slug)
    {
        $pageId = AdminPages::findByCondition(["slug" => $slug])[0]['id'];
        return self::deleteByCondition(["page_id" => $pageId]);
    }
}
