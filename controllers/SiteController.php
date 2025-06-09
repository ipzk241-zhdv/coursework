<?php

namespace controllers;

use classes\Controller;
use classes\Request;
use models\Rooms;
use Utils\MapSVG;
use models\Blocks;
use models\Users;
use classes\Core;
use Utils\Access;

class SiteController extends Controller
{
    public function actionView()
    {
        $params = PageController::getInstance()->getModulesBySection("site");
        return $this->view('Hostel', ['modules' => $params], "views/page.php");
    }

    #[Access(['student'])]
    public function actionMap()
    {
        $rooms = Rooms::findByCondition(['name' => "1%"]);
        $slots = MapSVG::getSlots(1, $rooms);
        $map = MapSVG::generateSVG($slots);
        return $this->view('Interactive map - Hostel', ["map" => $map]);
    }

    #[Access(['student'])]
    public function actionGetRoom()
    {
        $core = Core::getInstance();
        $roomid = Request::get("room_id");

        if ($roomid === null || !is_numeric($roomid)) {
            $core->respondError(400, "Некоректний запит: не вказано або неправильний room_id");
            exit;
        }

        $roomid = (int)$roomid;
        $room = Rooms::findById($roomid, true);
        if (!$room) {
            $core->respondError(404, "Кімнату не знайдено");
            exit;
        }

        $block = Blocks::findById($room['block_id'], true);
        if (!$block) {
            $core->respondError(404, "Блок не знайдено");
            exit;
        }

        $residents = Users::findByCondition(["room_id" => $roomid]);
        $warden = Users::findById($block['warden_id'], true);

        return json_encode([[
            "room" => $room,
            "block" => $block,
            "residents" => $residents ?? [],
            "warden" => $warden ?? null
        ]]);
    }

    #[Access(['student'])]
    public function actionGetFloor()
    {
        $core = Core::getInstance();
        $floor = Request::get("floor");

        if ($floor === null || !is_numeric($floor)) {
            $core->respondError(400, "Некоректний поверх");
            exit;
        }

        $floor = (int)$floor;
        
        $rooms = Rooms::findByCondition(['name' => "$floor%"]);
        if (!$rooms) {
            $core->respondError(404, "Кімнати не знайдено");
            exit;
        }

        $slots = MapSVG::getSlots($floor, $rooms);
        $map = MapSVG::generateSVG($slots);

        ob_start();
        echo $map;
        return ob_get_clean();
    }


    public function actionHistory()
    {
        $params = PageController::getInstance()->getModulesBySection("history");
        return $this->view('Hostel history', ['modules' => $params], "views/page.php");
    }
}
