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

        $threadsCountRows = Threads::threadCounts();
        $countThreads = [];
        foreach ($threadsCountRows as $row) {
            $countThreads[$row['subcategory_id']] = (int)$row['cnt'];
        }

        $messagesCountRows = Comments::countCommentsInThread();
        $countMessages = [];
        foreach ($messagesCountRows as $row) {
            $countMessages[$row['subcategory_id']] = (int)$row['cnt'];
        }

        $lastActiveRaw = Threads::getLatestActiveThreads();

        $lastActiveUser = [];
        foreach ($lastActiveRaw as $row) {
            $lastActiveUser[$row['subcategory_id']] = [
                'username' => $row['username'],
                'created_at' => $row['created_at'],
            ];
        }

        $latestPosts = Threads::getLatestThreads();
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
            $data = Request::all();
            $data['user_id'] = (int)Core::getInstance()->session->get('user')['id'];
            $_POST['user_id'] = $data['user_id'];
            $existing = Likes::findByCondition([
                'user_id' => $data['user_id'],
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
            ]);

            Core::log($data);
            Core::log($existing);

            if ($existing) {
                $existing = $existing[0];
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
        $subcategorySlug = Request::get("subcategory", null);
        $page = max(1, (int)Request::get("page", 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        if (!$subcategorySlug) {
            return ["error" => "no slug"];
        }

        $subcategory = Subcategories::findByCondition(['slug' => $subcategorySlug])[0];
        if (!$subcategory) {
            return ["error" => "no subcategory"];
        }

        $subcategoryId = $subcategory['id'];
        $totalCount = Threads::countThreadsInSubcategory($subcategoryId);
        $totalPages = ceil($totalCount / $pageSize);
        $threads = Threads::getThreads($subcategoryId, $pageSize, $offset);

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
        if (Request::isAjax() && Request::method() === "POST") {
            return $this->handleBasePost(Threads::class);
        }

        $threadId = (int) Request::get("id", 0);
        $page     = max(1, (int) Request::get("page", 1));
        $pageSize = 10;
        $offset   = ($page - 1) * $pageSize;

        $thread = Threads::getThread($threadId);
        $allComments = Comments::getCommentsThread($threadId);
        $commentById = [];
        foreach ($allComments as $c) {
            $commentById[(int)$c['id']] = $c;
        }

        $topAncestorOf = [];
        foreach ($allComments as $c) {
            $id  = (int)$c['id'];
            $pid = ($c['parent_id'] === null || $c['parent_id'] === '' ? 0 : (int)$c['parent_id']);

            if ($pid === 0) {
                $topAncestorOf[$id] = $id;
            } else {
                $current = $c;
                while (true) {
                    $p = ($current['parent_id'] === null || $current['parent_id'] === ''
                        ? 0
                        : (int)$current['parent_id']);
                    if ($p === 0) {
                        $topAncestorOf[$id] = (int)$current['id'];
                        break;
                    }
                    if (!isset($commentById[$p])) {
                        $topAncestorOf[$id] = $id;
                        break;
                    }
                    $current = $commentById[$p];
                }
            }
        }

        $topComments = [];
        foreach ($allComments as $c) {
            $pid = ($c['parent_id'] === null || $c['parent_id'] === '' ? 0 : (int)$c['parent_id']);
            if ($pid === 0) {
                $topComments[] = $c;
            }
        }

        $totalCount = count($topComments);
        $totalPages = (int) ceil($totalCount / $pageSize);

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $visibleTopComments = array_slice($topComments, $offset, $pageSize);
        $visibleTopIds = array_map(function ($c) {
            return (int)$c['id'];
        }, $visibleTopComments);

        if ($totalPages === 0) {
            $totalPages = 1;
        }

        $commentsToShow = [];
        foreach ($allComments as $c) {
            $cid     = (int)$c['id'];
            $topOfCi = $topAncestorOf[$cid];
            if (in_array($topOfCi, $visibleTopIds, true)) {
                $commentsToShow[] = $c;
            }
        }

        return $this->view('Тема - ' . $thread['title'], [
            'thread'      => $thread,
            'comments'    => $commentsToShow,
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
            $_POST['user_id'] = $sessionUser['id'];
            if (!$sessionUser) {
                $core->respondError(401, "Необхідна автентифікація.");
                exit;
            }

            $pid = (int) Request::post("parent_id", 0);
            $threadId = (int) Request::post("thread_id");

            if (!$threadId) {
                $core->respondError(400, "Не вказано ідентифікатор теми.");
                exit;
            }

            $thread = Threads::findById($threadId);
            if (!$thread) {
                $core->respondError(404, "Тему не знайдено.");
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
            $_POST['user_id'] = Core::getInstance()->session->get('user')['id'];
            Core::log(Request::all());
            $core = Core::getInstance();
            $title = Request::post('title', null);
            $content = Request::post('content', null);

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
