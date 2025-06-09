<?php

namespace controllers;

use classes\Controller;
use classes\Core;
use classes\Request;
use models\Categories;
use models\Comments;
use models\Likes;
use models\Subcategories;
use models\Threads;
use models\Users;
use Utils\Access;

class ForumController extends Controller
{
    #[Access(['student'])]
    public function actionIndex()
    {
        $db = Core::getInstance()->db;
        $categories = Categories::findAll();
        $subcategories = Subcategories::findAll();

        $subcategoriesGrouped = [];
        if ($subcategories) {
            foreach ($subcategories as $subcat) {
                $subcategoriesGrouped[$subcat['category_id']][] = $subcat;
            }
        }

        // Кількість тем у підкатегоріях
        $threadsCountRows = $db->selectQuery("
        SELECT subcategory_id, COUNT(*) as cnt
        FROM threads
        GROUP BY subcategory_id
    ");
        $countThreads = [];
        foreach ($threadsCountRows as $row) {
            $countThreads[$row['subcategory_id']] = (int)$row['cnt'];
        }

        // Кількість коментарів у підкатегоріях
        $messagesCountRows = $db->selectQuery("
        SELECT t.subcategory_id, COUNT(c.id) as cnt
        FROM comments c
        JOIN threads t ON c.thread_id = t.id
        GROUP BY t.subcategory_id
    ");
        $countMessages = [];
        foreach ($messagesCountRows as $row) {
            $countMessages[$row['subcategory_id']] = (int)$row['cnt'];
        }

        // Остання активність (користувач + дата) у підкатегоріях
        $lastActiveRaw = $db->selectQuery("
        SELECT subcategory_id, CONCAT(name, ' ', lastname, ' ', patronymic) as username, created_at FROM (
            SELECT 
                t.subcategory_id, 
                u.name, u.lastname, u.patronymic,
                th.created_at
            FROM threads th
            JOIN users u ON th.user_id = u.id
            JOIN threads t ON th.id = t.id

            UNION ALL

            SELECT 
                t.subcategory_id,
                u.name, u.lastname, u.patronymic,
                c.created_at
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN threads t ON c.thread_id = t.id
        ) AS combined
        WHERE (subcategory_id, created_at) IN (
            SELECT subcategory_id, MAX(created_at)
            FROM (
                SELECT t.subcategory_id, th.created_at
                FROM threads th
                JOIN threads t ON th.id = t.id

                UNION ALL

                SELECT t.subcategory_id, c.created_at
                FROM comments c
                JOIN threads t ON c.thread_id = t.id
            ) AS sub
            GROUP BY subcategory_id
        )
    ");

        $lastActiveUser = [];
        foreach ($lastActiveRaw as $row) {
            $lastActiveUser[$row['subcategory_id']] = [
                'username' => $row['username'],
                'created_at' => $row['created_at'],
            ];
        }

        // Останні 5 постів (threads)
        $latestPosts = $db->selectQuery("
        SELECT th.id, th.title, th.created_at, CONCAT(u.name, ' ', u.lastname) AS username
        FROM threads th
        JOIN users u ON th.user_id = u.id
        ORDER BY th.created_at DESC
        LIMIT 5
    ");

        // Загальна статистика
        $statisticsRows = $db->selectQuery("
        SELECT 
            (SELECT COUNT(*) FROM threads) AS threads,
            (SELECT COUNT(*) FROM comments) AS messages,
            (SELECT COUNT(*) FROM users) AS members
    ");
        $statistics = $statisticsRows[0] ?? ['threads' => 0, 'messages' => 0, 'members' => 0];

        return $this->view('Forum - Hostel', [
            'categories' => $categories,
            'subcategoriesGrouped' => $subcategoriesGrouped,
            'countThreads' => $countThreads,
            'countMessages' => $countMessages,
            'lastActiveUser' => $lastActiveUser,
            'latestPosts' => $latestPosts,
            'statistics' => $statistics,
        ]);
    }

    #[Access(['student'])]
    public function actionLike()
    {
        $core = Core::getInstance();
        if (Request::method() !== "POST" || !Request::isAjax()) {
            $core->respondError(400, "Недозволений метод");
        } else {
            $userid = Request::post('user_id', null);
            if ($userid === null || $userid != $core->session->get('user')['id']) {
                $core->respondError(400, "Користувачі не збігаються");
            }
            $data = Request::all();

            $existing = Likes::findByCondition([
                'user_id' => $data['user_id'],
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
            ]);

            if ($existing) {
                $existing = $existing[0]; // ← перший запис
                Likes::deleteById($existing['id']);

                if ($existing['type'] !== $data['type']) {
                    $this->handleBasePost(Likes::class);
                }
            } else {
                $this->handleBasePost(Likes::class);
            }

            $likes = $core->db->count('likes', [
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'type' => 'like'
            ]);

            $dislikes = $core->db->count('likes', [
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'type' => 'dislike'
            ]);

            return ['new_likes' => $likes, 'new_dislikes' => $dislikes];
        }
    }

    #[Access(['student'])]
    public function actionSubcategory()
    {
        $db = Core::getInstance()->db;
        $subcategorySlug = Request::get("subcategory", null);
        $page = max(1, (int)Request::get("page", 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        if (!$subcategorySlug) {
            return ["error" => "no slug"];
        }

        // Знайдемо підкатегорію
        $subcategory = Subcategories::findByCondition(['slug' => $subcategorySlug])[0];
        if (!$subcategory) {
            return ["error" => "no subcategory"];
        }

        $subcategoryId = $subcategory['id'];

        // Загальна кількість тредів (для пагінації)
        $totalCount = $db->selectQuery("
            SELECT COUNT(*) AS count FROM threads WHERE subcategory_id = :subcategory_id
        ", ['subcategory_id' => $subcategoryId])[0]['count'];

        $totalPages = ceil($totalCount / $pageSize);

        // Список тредів з обмеженням
        $threads = $db->selectQuery("
            SELECT 
            t.id,
            t.title,
            t.created_at,
            t.pinned,
            CONCAT(u.name, ' ', u.lastname) AS author_name,
        
            -- Коментарі: якщо немає — вивести 1
            (
                SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id
            ) + 1 AS comments_count,
            
            -- Лайки як є
            (SELECT COUNT(*) FROM likes l WHERE l.target_type = 'thread' AND l.target_id = t.id) AS likes_count,
            
            -- Якщо немає коментарів — автор треду
            COALESCE((
                SELECT CONCAT(us.name, ' ', us.lastname)
                FROM comments c2
                JOIN users us ON c2.user_id = us.id
                WHERE c2.thread_id = t.id
                ORDER BY c2.created_at DESC
                LIMIT 1
            ), CONCAT(u.name, ' ', u.lastname)) AS last_comment_user,
        
            -- Якщо немає коментарів — дата створення треду
            COALESCE((
                SELECT c2.created_at
                FROM comments c2
                WHERE c2.thread_id = t.id
                ORDER BY c2.created_at DESC
                LIMIT 1
            ), t.created_at) AS last_comment_date,
        
            -- Якщо немає коментарів — аватар автора треду
            COALESCE((
                SELECT us.avatar
                FROM comments c2
                JOIN users us ON c2.user_id = us.id
                WHERE c2.thread_id = t.id
                ORDER BY c2.created_at DESC
                LIMIT 1
            ), u.avatar) AS last_comment_avatar
        
        FROM threads t
        JOIN users u ON t.user_id = u.id
        WHERE t.subcategory_id = :subcategory_id
        ORDER BY 
            CASE WHEN t.pinned IS NULL OR t.pinned = 0 THEN 1 ELSE 0 END,
            t.pinned ASC,
            t.created_at DESC

        LIMIT :limit OFFSET :offset    
        ", [
            'subcategory_id' => $subcategoryId,
            'limit' => $pageSize,
            'offset' => $offset
        ]);


        return $this->view('Треди - ' . $subcategory['name'], [
            'threads' => $threads,
            'subcategory' => $subcategory,
            'page' => $page,
            'totalPages' => $totalPages,
            'subcategorySlug' => $subcategorySlug
        ]);
    }

    #[Access(['student'])]
    public function actionThread()
    {
        if (Request::isAjax()) {
            if (Request::method() === "POST") {
                return $this->handleBasePost(Threads::class);
            }
        }

        $db = Core::getInstance()->db;
        $threadId = (int) Request::get("id", 0);
        $page     = max(1, (int) Request::get("page", 1));
        $pageSize = 10;
        $offset   = ($page - 1) * $pageSize;

        // 1. Дістати сам тред
        $thread = $db->selectQuery("
            SELECT t.*, 
                   CONCAT(u.name, ' ', u.lastname) AS author_name,
                   u.avatar AS author_avatar, 
                   s.slug AS subcategory_slug,

                   -- Кількість лайків треду
                   (
                       SELECT COUNT(*) 
                       FROM likes l 
                       WHERE l.target_type = 'thread' 
                         AND l.target_id   = t.id 
                         AND l.type        = 'like'
                   ) AS likes_count,
           
                   -- Кількість дизлайків треду
                   (
                       SELECT COUNT(*) 
                       FROM likes l 
                       WHERE l.target_type = 'thread' 
                         AND l.target_id   = t.id 
                         AND l.type        = 'dislike'
                   ) AS dislikes_count

            FROM threads t
            JOIN users u ON t.user_id = u.id
            JOIN subcategories s ON t.subcategory_id = s.id
            WHERE t.id = :id
            LIMIT 1
        ", ['id' => $threadId])[0] ?? null;

        if (!$thread) {
            return ["error" => "no thread"];
        }

        // 2. Дістати ВСІ коментарі цього треду (без обмеження) для подальшої логіки
        //    Ми їх підвантажуємо у строгу послідовність ORDER BY created_at ASC, щоби зберігати хронологію.
        $allComments = $db->selectQuery("
            SELECT c.*, 
                   CONCAT(u.name, ' ', u.lastname) AS author_name,
                   u.avatar AS author_avatar,

                   -- Лайки коментаря
        (
            SELECT COUNT(*) 
            FROM likes l 
            WHERE l.target_type = 'comment' 
              AND l.target_id   = c.id 
              AND l.type        = 'like'
        ) AS likes_count,

        -- Дизлайки коментаря
        (
            SELECT COUNT(*) 
            FROM likes l 
            WHERE l.target_type = 'comment' 
              AND l.target_id   = c.id 
              AND l.type        = 'dislike'
        ) AS dislikes_count

            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.thread_id = :thread_id
            ORDER BY c.created_at ASC
        ", ['thread_id' => $threadId]);

        // 3. Створимо масив для швидкого доступу за id
        $commentById = [];
        foreach ($allComments as $c) {
            $commentById[(int)$c['id']] = $c;
        }

        // 4. Знайдемо «топ-предка» (ancestor) для кожного коментаря:
        //    Якщо parent_id = 0/NULL/'' → сам коментар вважаємо топ-рівневим.
        //    Інакше «підіймаємось» угору по ланцюжку parent_id, поки не натрапимо на вузол з parent_id = 0.
        $topAncestorOf = []; // [ comment_id => top_level_id ]
        foreach ($allComments as $c) {
            $id  = (int)$c['id'];
            $pid = ($c['parent_id'] === null || $c['parent_id'] === '' ? 0 : (int)$c['parent_id']);

            if ($pid === 0) {
                // Самій себе вважаємо топом
                $topAncestorOf[$id] = $id;
            } else {
                // Підіймаємось угору по parent_id
                $current = $c;
                while (true) {
                    $p = ($current['parent_id'] === null || $current['parent_id'] === ''
                        ? 0
                        : (int)$current['parent_id']);
                    if ($p === 0) {
                        // Знайшли вузол з parent_id = 0
                        $topAncestorOf[$id] = (int)$current['id'];
                        break;
                    }
                    // Якщо з якихось причин батька немає в commentById, 
                    // вважаємо самій себе top-рівнем (захист від дивних даних у БД)
                    if (!isset($commentById[$p])) {
                        $topAncestorOf[$id] = $id;
                        break;
                    }
                    // Інакше переходимо до запису-«батька»
                    $current = $commentById[$p];
                }
            }
        }

        // 5. Виділимо список усіх топ-коментарів (де parent_id = 0)
        $topComments = [];
        foreach ($allComments as $c) {
            $pid = ($c['parent_id'] === null || $c['parent_id'] === '' ? 0 : (int)$c['parent_id']);
            if ($pid === 0) {
                $topComments[] = $c;
            }
        }

        // 6. З огляду на $pageSize і $page обчислимо загальну кількість сторінок:
        $totalCount = count($topComments);
        $totalPages = (int) ceil($totalCount / $pageSize);

        // Якщо $page пішов за межі, клацнемо на останню сторінку
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        // 7. Зараз вибираємо «видимі» top-коментарі для поточної сторінки:
        //    Вони будуть у порядку, в якому стоять у $topComments
        $visibleTopComments = array_slice($topComments, $offset, $pageSize);
        // Витягнемо їхні id
        $visibleTopIds = array_map(function ($c) {
            return (int)$c['id'];
        }, $visibleTopComments);

        // 8. Скільки сторінок, якщо topComments = 0? 
        //    У такому випадку $totalPages = 0, але ми маємо обійти помилку / нормалізувати
        if ($totalPages === 0) {
            $totalPages = 1;
        }

        // 9. Тепер зі всієї маси $allComments відібрати ті, 
        //    чий topAncestor належить до $visibleTopIds (або це сам top-коментар).
        $commentsToShow = [];
        foreach ($allComments as $c) {
            $cid     = (int)$c['id'];
            $topOfCi = $topAncestorOf[$cid];
            if (in_array($topOfCi, $visibleTopIds, true)) {
                $commentsToShow[] = $c;
            }
        }

        // 10. Передамо до view лише ті коментарі, які необхідно рендерити зараз:
        return $this->view('Тема - ' . $thread['title'], [
            'thread'      => $thread,
            'comments'    => $commentsToShow,  // плоский масив: всі top- і їхні нащадки
            'currentPage' => $page,
            'totalPages'  => $totalPages,
        ]);
    }

    #[Access(['student'])]
    public function actionComment()
    {
        if (Request::isAjax() && Request::method() === "POST") {
            $core = Core::getInstance();
            $sessionUser = $core->session->get('user');

            if (!$sessionUser || !isset($sessionUser['id'])) {
                $core->respondError(401, "Необхідна автентифікація.");
                exit;
            }

            $pid = (int) Request::post("parent_id", 0);
            $threadId = (int) Request::post("thread_id");
            $userId = (int) Request::post("user_id");

            if (!$threadId) {
                $core->respondError(400, "Не вказано ідентифікатор теми.");
                exit;
            }

            $thread = Threads::findById($threadId);
            if (!$thread) {
                $core->respondError(404, "Тему не знайдено.");
                exit;
            }

            if ($userId !== (int)$sessionUser['id']) {
                $core->respondError(403, "Недопустимий користувач.");
                exit;
            }

            if (Request::post("add") !== null && $pid !== 0) {
                $parent = Comments::findById($pid, true);
                if (!$parent) {
                    $core->respondError(404, "Відповідь на неіснуючий коментар.");
                    exit;
                }

                if ((int)$parent['thread_id'] !== $threadId) {
                    $core->respondError(400, "Коментар не належить цій темі.");
                    exit;
                }
            }

            return $this->handleBasePost(Comments::class);
        }

        Core::getInstance()->respondError(405, "Метод не дозволений.");
        exit;
    }

    #[Access(['student'])]
    public function actionCommentImage()
    {
        if (!Request::isAjax() || Request::method() !== "POST") {
            Core::getInstance()->respondError(405, 'Метод не дозволений.');
            exit;
        }

        if (!isset($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
            Core::getInstance()->respondError(400, 'Файл не надіслано або сталася помилка.');
            exit;
        }

        $file = $_FILES['upload'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            Core::getInstance()->respondError(415, 'Недопустимий тип файлу.');
            exit;
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        };

        $filename = uniqid('img_', true) . '.' . $ext;
        $dir = __DIR__ . '/../public/threads/';
        $path = $dir . $filename;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            Core::getInstance()->respondError(500, 'Не вдалося зберегти файл.');
            exit;
        }

        echo json_encode(['url' => "/public/threads/" . $filename]);
        exit;
    }

    protected function handleBaseAction(string $modelClass, string $tableName, string $title)
    {
        if (Request::isAjax()) {
            if (Request::method() === "POST") {
                return $this->handleBasePost($modelClass);
            }

            $page = (int)Request::get('page', 1);
            $limit = (int)Request::get('limit', 10);
            $sort = Request::get('sort', 'id');
            $dir = strtolower(Request::get('dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
            $search = trim(Request::get('search', ''));

            $orderBy = "$sort $dir";

            $items = $modelClass::findAll($page, $limit, $orderBy);
            $total = Core::getInstance()->db->count($tableName);

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
                "apiUrl" => "/forum/$tableName",
            ]
        );
    }

    protected function handleBasePost(string $modelClass)
    {
        $data = Request::all();
        $id = $data['id'] ?? null;

        if (Request::post("delete") !== null) {
            if (empty($id)) {
                Core::getInstance()->respondError(400, 'Не вказано ID для видалення.');
                exit;
            }

            $item = $modelClass::find($id);
            if (!$item) {
                Core::getInstance()->respondError(404, 'Обʼєкт для видалення не знайдено.');
                exit;
            }

            $modelClass::apiDelete($id);
            return ['edited' => true];
        }

        if (Request::post("put") !== null) {
            if (empty($id)) {
                Core::getInstance()->respondError(400, 'Не вказано ID для оновлення.');
                exit;
            }

            $item = $modelClass::find($id);
            if (!$item) {
                Core::getInstance()->respondError(404, 'Обʼєкт для оновлення не знайдено.');
                exit;
            }

            $modelClass::apiUpdate($data);
            return ['edited' => true];
        }

        if (Request::post("add") !== null) {
            $modelClass::apiCreate($data);
            return ['edited' => true];
        }

        Core::getInstance()->respondError(400, 'Невідома дія.');
    }

    #[Access(['student'])]
    public function actionCreateThread()
    {
        if (Request::method() === "POST" && Request::isAjax()) {
            $data = Request::all();
            Core::log($data);

            $core = Core::getInstance();
            $userid = Request::post('user_id', null);
            $title = Request::post('title', null);
            $content = Request::post('content', null);

            if ($userid === null || $userid != Core::getInstance()->session->get('user')['id']) {
                $core->respondError(400, "Користувачі не збігаються");
                exit;
            }
            // 1 tag - 7 symbols
            if ($title === null || $content === null || strlen($title) < 5 || strlen($content) < 17) {
                $core->respondError(400, "Вміст або заголовок відсутні або закороткі. Вміст має бути більше 10 символів, заголовок більше 5");
                exit;
            }
            return $this->handleBasePost(Threads::class);
        }
        $subcategory = Request::get("subcategory");
        $subcategory = Subcategories::findByCondition(['slug' => $subcategory]);
        Core::log($subcategory);
        if ($subcategory === null) {
            Core::log("not found, 404");
            Core::getInstance()->respondError(404, "Підкатегорію не знайдено");
            exit;
        }
        $subcategory = $subcategory[0];
        return $this->view($subcategory['slug'] . 'Create - Hostel', ['subcategory' => $subcategory, 'userid' => Core::getInstance()->session->get('user')['id']]);
    }
}
