<?php

namespace controllers;

use classes\DB;
use classes\Core;
use models\AdminPages;

class PageController
{
    private static ?PageController $instance = null;
    private static DB $db;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$db = Core::getInstance()->db;
        }
        return self::$instance;
    }

    public static function getModulesBySection($pageSlug): array
    {
        $page = AdminPages::findByCondition(['slug' => $pageSlug]);
        if (empty($page)) {
            throw new \Exception("Page not found: " . htmlspecialchars($pageSlug));
        }
        $pageId = $page[0]['id'];

        $sql = "SELECT m.name, pm.section, pm.position
                FROM page_modules pm
                JOIN modules m ON m.id = pm.module_id
                WHERE pm.page_id = :page_id
                ORDER BY 
                    CASE pm.section
                        WHEN 'header' THEN 1
                        WHEN 'body' THEN 2
                        WHEN 'footer' THEN 3
                        ELSE 4
                    END,
                    pm.position ASC";

        $modules = self::$db->selectQuery($sql, ['page_id' => $pageId]);
        $result = [
            'header' => [],
            'body' => [],
            'footer' => [],
        ];
        
        foreach ($modules as $module) {
            $section = $module['section'];
            $result[$section][] = $module['name'];
        }
    
        return $result;
    }

    public static function getAllModules(): array
    {
        return self::$db->select("modules");;
    }

    public static function getAllPages(){
        return self::$db->select("pages");
    }
}
