<?php

namespace controllers;

use classes\Controller;
use classes\Request;
use models\AdminModules;
use classes\Core;
use models\AdminPageModules;
use models\Rooms;
use models\Blocks;
use models\Categories;
use models\Comments;
use models\HttpLogs;
use models\Likes;
use models\Pages;
use models\Subcategories;
use models\Users;
use models\TableConfigs;
use models\Threads;
use models\Layouts;
use models\Settings;
use Utils\MapSVG;
use Utils\Access;

class AdminController extends Controller
{
    #[Access(['admin'])]
    public function actionCategories()
    {
        return $this->handleAdminTable(Categories::class, "categories", "Categories - Admin");
    }

    #[Access(['admin'])]
    public function actionLayouts()
    {
        return $this->handleAdminTable(Layouts::class, "layouts", "Layouts - Admin");
    }

    #[Access(['admin'])]
    public function actionHttp_logs()
    {
        return $this->handleAdminTable(HttpLogs::class, "http_logs", "Http logs - Admin");
    }

    #[Access(['admin'])]
    public function actionPages()
    {
        return $this->handleAdminTable(Pages::class, "pages", "Pages - Admin");
    }

    #[Access(['admin'])]
    public function actionThreads()
    {
        return $this->handleAdminTable(Threads::class, "threads", "Threads - Admin");
    }

    #[Access(['admin'])]
    public function actionComments()
    {
        return $this->handleAdminTable(Comments::class, "comments", "Comments - Admin");
    }

    #[Access(['admin'])]
    public function actionLikes()
    {
        return $this->handleAdminTable(Likes::class, "likes", "Likes - Admin");
    }

    #[Access(['admin'])]
    public function actionSubcategories()
    {
        return $this->handleAdminTable(Subcategories::class, "subcategories", "Subcategories - Admin");
    }

    #[Access(['admin'])]
    public function actionBlocks()
    {
        return $this->handleAdminTable(Blocks::class, "blocks", "Blocks - Admin");
    }

    #[Access(['admin'])]
    public function actionUsers()
    {
        return $this->handleAdminTable(Users::class, "users", "Users - Admin");
    }

    protected function handleAdminTable(string $modelClass, string $tableName, string $title)
    {
        if (Request::isAjax()) {
            if (Request::method() === "POST") {
                return $this->handleAdminPost($modelClass);
            }

            $page = (int)Request::get('page', 1);
            $limit = (int)Request::get('limit', 10);
            $sort = Request::get('sort', 'id');
            $dir = strtolower(Request::get('dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
            $search = trim(Request::get('search', ''));

            $orderBy = "$sort $dir";
            if ($search !== '') {
                $tableKey = strtolower(basename(str_replace('\\', '/', $modelClass)));
                $fields = TableConfigs::findByCondition(["table_name" => $tableKey]);

                $fields = array_filter($fields, function ($field) {
                    return isset($field['searchable']) && $field['searchable'] == 1;
                });
                $fields = array_column($fields, 'field');
                $searchFields = array_fill_keys($fields, $search);
                $searchFields = array_map(fn($v) => $v . '%', $searchFields);
                $items = $modelClass::findByCondition($searchFields, $page - 1, $limit, true, true, $orderBy);
                $total = Core::getInstance()->db->count($tableName, $searchFields, true, true);
            } else {
                $items = $modelClass::findAll($page, $limit, $orderBy);
                $total = Core::getInstance()->db->count($tableName);
            }


            return $this->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }

        return $this->view(
            $title,
            [
                "apiUrl" => "/admin/$tableName",
                "configUrl" => "/admin/TableConfigs?ajax=1&table_name=$tableName"
            ],
            "views/admin/page.php"
        );
    }

    protected function handleAdminPost(string $modelClass)
    {
        $data = Request::all();

        if (Request::post("delete") !== null) {
            if (empty($data['id'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Не вказано ID для видалення'];
            }

            $existing = $modelClass::findById($data['id']);
            if (!$existing) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Запис для видалення не знайдено'];
            }

            $modelClass::apiDelete($data['id']);
            return ['status' => 'success', 'edited' => true];
        }

        if (Request::post("put") !== null) {
            if (empty($data['id'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Не вказано ID для оновлення'];
            }

            $existing = $modelClass::findById($data['id']);
            if (!$existing) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Запис для оновлення не знайдено'];
            }

            $modelClass::apiUpdate($data);
            return ['status' => 'success', 'edited' => true];
        }

        if (Request::post("add") !== null) {
            $modelClass::apiCreate($data);
            return ['status' => 'success', 'edited' => true];
        }

        http_response_code(400);
        return ['status' => 'error', 'message' => 'Невідома дія'];
    }

    #[Access(['admin'])]
    public function actionDashboard()
    {
        $db = Core::getInstance()->db;

        $statusStats = $db->selectQuery(
            "SELECT 
            DATE(created_at) AS log_date,
            status_code,
            COUNT(*) AS count
        FROM http_logs
        WHERE created_at > NOW() - INTERVAL 30 DAY
        GROUP BY log_date, status_code
        ORDER BY log_date ASC, status_code ASC
        "
        );

        $errorStats = $db->selectQuery(
            "SELECT DATE(created_at) as day, status_code, COUNT(*) as count
             FROM http_logs
             WHERE status_code >= 400 AND created_at > NOW() - INTERVAL 30 DAY
             GROUP BY day, status_code
             ORDER BY day"
        );

        $userGrowth = $db->selectQuery(
            "SELECT DATE(created_at) as day, COUNT(*) as count
             FROM users
             WHERE created_at > NOW() - INTERVAL 30 DAY
             GROUP BY day"
        );

        $topCommentUsers = $db->selectQuery(
            "SELECT u.id, u.name, u.lastName, u.patronymic, COUNT(*) as comments
             FROM comments c
             JOIN users u ON u.id = c.user_id
             GROUP BY u.id
             ORDER BY comments DESC
             LIMIT 10"
        );

        $commentStats = $db->selectQuery(
            "SELECT DATE(created_at) as day, COUNT(*) as count
             FROM comments
             WHERE created_at > NOW() - INTERVAL 30 DAY
             GROUP BY day"
        );

        Core::log($commentStats);

        $blockOccupancy = $db->selectQuery(
            "SELECT 
                CONCAT(b.floor, b.name) AS block_code,
                SUM(r.places) AS total_places,
                COUNT(u.id) AS occupied_places
             FROM blocks b
             LEFT JOIN rooms r ON r.block_id = b.id
             LEFT JOIN users u ON u.room_id = r.id
             GROUP BY block_code
             ORDER BY block_code"
        );

        return $this->view('Dashboard - Admin', [
            'statusStats'     => $statusStats,
            'errorStats'      => $errorStats,
            'userGrowth'      => $userGrowth,
            'topCommentUsers' => $topCommentUsers,
            'commentStats'    => $commentStats,
            'blockOccupancy'  => $blockOccupancy,
        ]);
    }

    #[Access(['admin'])]
    public function actionConstructor()
    {
        if (Request::method() === "POST") {
            return $this->handleConstructorPost();
        }

        if (Request::get('ajax') == 1) {
            return $this->getPageSectionsJson();
        }

        return $this->renderPages();
    }

    protected function handleConstructorPost()
    {
        $savedata = Request::all();

        if (!empty($savedata['slug']) && !empty($savedata['sections']) && is_array($savedata['sections'])) {
            AdminPageModules::editPageModules($savedata);
            return ['status' => 'success'];
        }

        http_response_code(400);
        return ['error' => 'Неправильні дані'];
    }

    protected function getPageSectionsJson()
    {
        $slug = Request::get('slug', null);
        if (!$slug) {
            http_response_code(400);
            return ['error' => 'Не вказано slug'];
        }

        $pg = PageController::getInstance();
        $sections = $pg->getModulesBySection($slug);

        if (!is_array($sections)) {
            http_response_code(404);
            return ['error' => 'Сторінку не знайдено'];
        }

        return ['sections' => $sections];
    }

    protected function renderPages()
    {
        $pg = PageController::getInstance();
        $pages = array_column($pg->getAllPages(), "slug");

        $firstSlug = $pages[0] ?? null;
        $pageModules = $firstSlug
            ? $pg->getModulesBySection($firstSlug)
            : [];

        $modules = $pg->getAllModules();

        $params  = [
            "modules"     => $modules,
            "pages"       => $pages,
            "pageModules" => $pageModules,
        ];

        return $this->view('Pages - Admin', $params, "views/admin/pages.php");
    }

    #[Access(['admin'])]
    public function actionModules()
    {
        if (Request::method() === 'POST') {
            return $this->handleModulesPost();
        }

        return $this->renderModulesPage();
    }

    protected function handleModulesPost()
    {
        $title = Request::post("name", null);
        $description = Request::post("description", null);

        if ($title === "" || $title === null) {
            http_response_code(400);
            return ['html' => '<div class="alert alert-danger">Компонент не знайдено</div>'];
        }

        if (Request::post("delete", null) !== null) {
            AdminModules::deleteModule($title);
            return ['status' => 'deleted'];
        } elseif (Request::post("add", null) !== null && $description !== null) {
            AdminModules::AddModule($title, $description);
            return ['status' => 'added'];
        } elseif (Request::post("put", null) !== null && $description !== null) {
            AdminModules::editModule($title, $description);
            return ['status' => 'edited'];
        }

        http_response_code(400);
        return ['html' => '<div class="alert alert-danger">Невідома дія</div>'];
    }

    protected function renderModulesPage()
    {
        $modules = PageController::getInstance()->getAllModules();

        $componentDir = __DIR__ . '/../views/components';
        $files = scandir($componentDir);
        $notAddedModules = array_filter($files, fn($f) => (
            (pathinfo($f, PATHINFO_EXTENSION) === 'php' || pathinfo($f, PATHINFO_EXTENSION) === 'svg') &&
            !in_array($f, array_column($modules, "name"))
        ));

        $params = [
            'modules' => $modules,
            'notAddedModules' => $notAddedModules,
        ];

        $title = Request::post("name", null);
        if ($title !== null) $params['selected'] = $title;

        return $this->view('Modules - Admin', $params);
    }

    #[Access(['admin'])]
    public function actionSettings()
    {
        if (Request::isAjax()) {
            if (Request::method() === "POST") {
                $this->handleAdminPost(Settings::class);
                exit;
            }
            if (Request::method() === "GET") {
                $settings = Settings::findById(1, true);
                $layouts = Layouts::findAll();
                return [
                    'id' => $settings['id'],
                    'layouts' => [
                        'current_layout_id' => $settings['current_layout_id'],
                        'available' => $layouts,
                    ],
                    'to_cache' => $settings['to_cache'],
                    'cache_lifetime' => $settings['cache_lifetime'],
                    'exclude_cache' => $settings['exclude_cache'],
                ];
            }
        }
        return $this->view('Settings - Admin');
    }

    #[Access(['admin'])]
    public function actionRooms()
    {
        $rooms = Rooms::findByCondition(['name' => "1%"]);
        $slots = MapSVG::getSlots(1, $rooms);
        $map = MapSVG::generateSVG($slots);
        return $this->view('Rooms - Admin', ["map" => $map]);
    }

    #[Access(['admin'])]
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

    #[Access(['admin'])]
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
        $enums = Core::getInstance()->db->selectQuery(
            "SELECT COLUMN_TYPE 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = :table 
               AND COLUMN_NAME = :column",
            ['table' => "rooms", 'column' => "room_type"]
        );

        if (!empty($enums)) {
            preg_match_all("/'([^']+)'/", $enums[0]['COLUMN_TYPE'], $matches);
            $enumValues = $matches[1];
        } else {
            $enumValues = [];
        }

        $roomInfo[] = [
            "room" => $room,
            "block" => $block,
            "residents" => $residents ?? "",
            "warden" => $warden ?? "",
            "room_type" => $enumValues ?? []
        ];

        return ["roomInfo" => $roomInfo];
    }

    #[Access(['admin'])]
    public function actionUpdateRoom()
    {
        $core = Core::getInstance();
        if (!Request::isAjax()) {
            $core->respondError(400, "Неправильний тип запиту");
            exit;
        }

        $data = Request::all();
        $roomId = $data['room_id'] ?? null;
        $places = $data['places'] ?? null;
        $residents = $data['residents'] ?? [];
        $roomType = $data['room_type'] ?? null;

        if ($roomId === null || $places === null) {
            $core->respondError(400, "Відсутні необхідні параметри");
            exit;
        }

        $room = Rooms::findById($roomId);
        if (!$room) {
            $core->respondError(404, "Кімната не знайдена");
            exit;
        }

        if ($places !== null) {
            $room->places = (int)$places;
        }

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/rooms/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $tmpName = $_FILES['image']['tmp_name'];
            $filename = basename($_FILES['image']['name']);
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $newFileName = 'room_' . $roomId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $destination)) {
                // видалення старого фото
                // if ($room->img && file_exists($uploadDir . $room->img)) {
                //     @unlink($uploadDir . $room->img);
                // }
                $room->img = $newFileName;
            } else {
                $core->respondError(500, "Помилка завантаження зображення");
                exit;
            }
        }

        if ($roomType != null) {
            $room->room_type = $roomType;
        }

        if (!$room->save()) {
            $core->respondError(500, "Не вдалося оновити кімнату");
            exit;
        }

        $res = Users::findByCondition(["room_id" => $roomId]);
        if ($res != null) {
            foreach ($res as $resident) {
                if (!in_array($resident['id'], $residents)) {
                    $r = Users::findById($resident['id']);
                    if ($r != null) {
                        $r->room_id = null;
                        $r->save();
                    }
                }
            }
        }

        if (is_array($residents) && count($residents) > 0) {
            foreach ($residents as $residentId) {
                $r = Users::findById($residentId);
                $room = Rooms::findById($roomId);
                if ($r != null && $room != null) {
                    $r->room_id = $roomId;
                    $r->save();
                }
            }
        }

        return ['success' => true];
    }

    #[Access(['admin'])]
    public function actionSearchUsers()
    {
        if (Request::method() !== 'GET' || Request::get("query", null) === null) {
            http_response_code(400);
            return ['error' => 'Invalid request'];
        }

        $query = trim(Request::get("query", null));
        $roomId = Request::get("room_id", null);
        if ($roomId != null) {
            $roomId = (int)$roomId;
        }

        if (mb_strlen($query) < 2) {
            return [];
        }

        $users = Users::findByCondition(["name" => "%$query%", "lastname" => "%$query%", "patronymic" => "%$query%", "email" => "%$query%"], limit: 5, or: true);
        if ($users) {
            foreach ($users as $key => $user) {
                if ($user["room_id"] === $roomId) {
                    unset($users[$key]);
                }
            }
        }

        header('Content-Type: application/json');
        return [$users];
    }

    #[Access(['admin'])]
    public function actionRenderModule()
    {
        $name = Request::get('name', '');
        $filePath = __DIR__ . '/../views/components/' . basename($name);

        if (!file_exists($filePath)) {
            http_response_code(404);
            return json_encode(['html' => '<div class="alert alert-danger">Компонент не знайдено</div>']);
        }

        ob_start();
        include $filePath;
        return ob_get_clean();
    }

    #[Access(['admin'])]
    public function actionTableConfigs()
    {
        if (Request::method() === 'POST') {
            return $this->handleTableConfigsPost();
        }

        if (Request::get('ajax') == 1) {
            $id = Request::get('id', null);
            if ($id !== null) {
                $config = TableConfigs::findById($id, true);
                return $this->json($config);
            }

            $tableName = Request::get('table_name', null);
            if ($tableName !== null) {
                $configs = TableConfigs::findByCondition(['table_name' => $tableName]);
            }

            return $this->json($configs ?? []);
        }


        return $this->renderTableConfigsPage();
    }

    protected function handleTableConfigsPost()
    {
        $id = Request::post("id", null);
        $table = Request::post("table_name", null);
        $field = Request::post("field", null);
        $label = Request::post("label", "");
        $type = Request::post("type", "text");
        $sortable = (int)Request::post("sortable", 0);
        $searchable = (int)Request::post("searchable", 0);
        $visible = (int)Request::post("visible", 1);
        $position = (int)Request::post("position", 0);

        if (Request::post("delete", null) !== null && $id !== null) {
            $toDelete = TableConfigs::findById($id);
            $table = $toDelete->table_name;
            TableConfigs::deleteById($id);
            return $this->json($table);
        }

        if ($field === null || $table === null) {
            http_response_code(400);
            return ['html' => '<div class="alert alert-danger">Назва таблиці або поле не вказані</div>'];
        }

        $data = [
            'table_name' => $table,
            'field' => $field,
            'label' => $label,
            'type' => $type,
            'sortable' => $sortable,
            'searchable' => $searchable,
            'visible' => $visible,
            'position' => $position,
        ];

        if (Request::post("add", null) !== null) {
            $config = new TableConfigs();
            foreach ($data as $key => $value) {
                $config->$key = $value;
            }

            $config->save();
            return $this->json($config);
        }

        if (Request::post("put", null) !== null && $id !== null) {
            $config = TableConfigs::findById($id);
            if ($config) {
                foreach ($data as $key => $value) {
                    $config->$key = $value;
                }
                $config->save();
                return $this->json($config);
            } else {
                http_response_code(404);
                return ['html' => '<div class="alert alert-danger">Конфігурацію не знайдено</div>'];
            }
        }

        http_response_code(400);
        return ['html' => '<div class="alert alert-danger">Невідома дія</div>'];
    }

    protected function renderTableConfigsPage()
    {
        // Отримаємо всі унікальні назви таблиць для select
        $db = Core::getInstance()->db;
        $tables = $db->selectQuery("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
        $tableNames = array_column($tables, "TABLE_NAME");

        return $this->view('admin/table-configs', [
            'tableNames' => $tableNames,
            'configs' => [],
        ]);
    }

    #[Access(['admin'])]
    public function actionAutoGenerateConfigs()
    {
        if (!Request::isAjax()) {
            http_response_code(403);
            return ['message' => 'Доступ заборонено'];
        }

        $table = Request::get('table');
        if (!$table) {
            http_response_code(400);
            return ['message' => 'Не вказано назву таблиці'];
        }

        try {
            $this->autoGenerateTableConfigs($table);
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            http_response_code(500);
            return ['message' => 'Помилка: ' . $e->getMessage()];
        }
    }

    protected function autoGenerateTableConfigs(string $table)
    {
        $db = Core::getInstance()->db;
        $existingFields = array_map(fn($x) => $x['field'], TableConfigs::findByCondition(['table_name' => $table]) ?? []);

        $columns = $db->selectQuery("
        SELECT COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = :table AND TABLE_SCHEMA = DATABASE()
    ", ['table' => $table]);

        $configs = TableConfigs::findByCondition(['table_name' => $table]);
        if (!is_array($configs)) $configs = [];

        $position = 0;
        foreach ($configs as $cfg) {
            $pos = (int)($cfg->position ?? 0);
            if ($pos > $position) $position = $pos;
        }

        foreach ($columns as $column) {
            $field = $column['COLUMN_NAME'];
            $type = $this->mapDataType($column['DATA_TYPE']);
            if (in_array($field, $existingFields)) continue;

            $data = [
                'table_name' => $table,
                'field' => $field,
                'label' => ucfirst(str_replace('_', ' ', $field)), // автоматичний заголовок
                'type' => $type,
                'sortable' => 1,
                'searchable' => 1,
                'visible' => 1,
                'position' => ++$position,
            ];

            $config = new TableConfigs();
            foreach ($data as $key => $value) {
                $config->$key = $value;
            }
            $config->save();
        }
    }

    protected function mapDataType(string $sqlType): string
    {
        return match (strtolower($sqlType)) {
            'varchar', 'text', 'char' => 'text',
            'int', 'bigint', 'smallint', 'decimal', 'float', 'double' => 'number',
            'date', 'datetime', 'timestamp' => 'date',
            'tinyint' => 'bool',
            default => 'text',
        };
    }
}
